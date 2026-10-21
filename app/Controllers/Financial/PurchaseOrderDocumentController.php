<?php

namespace App\Controllers\Financial;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\Financial\FinPurchaseOrderDocumentModel;
use App\Models\Financial\FinPurchaseOrderEmailLogModel;
use App\Models\Financial\FinSupplierContactModel;
use App\Models\Users\UserActivityLogsModel;
use App\Services\AppMailer;

/**
 * Documentos y correo de órdenes de compra.
 */
class PurchaseOrderDocumentController extends BaseFinancialController
{
    private FinPurchaseOrderDocumentModel $documentModel;
    private FinPurchaseOrderEmailLogModel $emailLogModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->documentModel = new FinPurchaseOrderDocumentModel();
        $this->emailLogModel = new FinPurchaseOrderEmailLogModel();
    }

    public function list_documents(int $poId)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $order = $this->purchaseOrderModel->find($poId);
        if (!$order) {
            $this->setOutputError('Orden de compra no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $channel = $this->request->getGet('channel');
        $docs    = $this->documentModel->getByOrder($poId, $channel ?: null);

        $this->setOutputSuccess('OK');
        $this->outputData['response'] = ['documents' => $docs];

        return $this->response->setJSON($this->outputData);
    }

    public function upload_document(int $poId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $order = $this->purchaseOrderModel->find($poId);
        if (!$order) {
            $this->setOutputError('Orden de compra no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $files = $this->_collectUploadedFiles('document_file');
        if (empty($files)) {
            $this->setOutputError('Seleccione uno o más archivos válidos.');
            return $this->response->setJSON($this->outputData);
        }

        $channel = $this->request->getPost('doc_channel') ?: FinPurchaseOrderDocumentModel::CHANNEL_INBOUND;
        $docType = $this->request->getPost('doc_type') ?: 'other';
        $notes   = trim((string) $this->request->getPost('notes')) ?: null;

        $stored = [];
        $failed = 0;
        foreach ($files as $file) {
            $newId = $this->documentModel->storeDocument($poId, $file, [
                'doc_channel' => $channel,
                'doc_type'    => $docType,
                'notes'       => $notes,
                'uploaded_by' => auth()->id(),
            ]);
            if ($newId) {
                $stored[] = $newId;
            } else {
                $failed++;
            }
        }

        if (empty($stored)) {
            $this->setOutputError('No se pudo guardar el documento. Verifique tipo y formato.');
            return $this->response->setJSON($this->outputData);
        }

        (new UserActivityLogsModel())->logActivity(
            'upload_po_document',
            'Subió ' . count($stored) . ' documento(s) a OC ID: ' . $poId . ' | canal: ' . $channel
        );

        $msg = count($stored) . ' documento(s) cargado(s) correctamente.';
        if ($failed > 0) {
            $msg .= ' ' . $failed . ' archivo(s) no se pudieron guardar.';
        }
        $this->setOutputSuccess($msg);
        $this->outputData['response'] = ['ids' => $stored];
        $this->outputData['csrf']     = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function download_document(int $documentId)
    {
        $document = $this->documentModel->find($documentId);
        if (!$document) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Documento no encontrado.');
        }

        $filePath = $this->documentModel->getAbsolutePath($document);
        if (!is_file($filePath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('El archivo físico no existe.');
        }

        $mimeType = $document->mime_type ?: mime_content_type($filePath);
        $action   = $this->request->getGet('action') === 'view' ? 'inline' : 'attachment';
        $filename = $document->original_name ?: basename($document->file_path);

        return $this->response->download($filePath, null)
            ->setFileName($filename)
            ->setHeader('Content-Type', $mimeType)
            ->setHeader('Content-Disposition', $action . '; filename="' . $filename . '"');
    }

    public function delete_document(int $documentId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $document = $this->documentModel->find($documentId);
        if (!$document) {
            $this->setOutputError('Documento no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        if (!$this->documentModel->softDeleteDocument($documentId)) {
            $this->setOutputError('No se pudo eliminar el documento.');
            return $this->response->setJSON($this->outputData);
        }

        (new UserActivityLogsModel())->logActivity(
            'delete_po_document',
            'Eliminó documento ID: ' . $documentId . ' de OC ID: ' . $document->fin_purchase_order_id
        );

        $this->setOutputSuccess('Documento eliminado correctamente.');
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function compose_email(int $poId)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $order = $this->purchaseOrderModel->getOrderWithItems($poId);
        if (!$order) {
            $this->setOutputError('Orden de compra no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $kind = $this->request->getGet('kind') ?: 'send_po';

        $toEmails = [];
        if (!empty($order->supplier_contact_email)) {
            $toEmails[] = $order->supplier_contact_email;
        }

        $contactModel = new FinSupplierContactModel();
        foreach ($contactModel->where('fin_supplier_id', $order->fin_supplier_id)->where('email !=', '')->findAll() as $c) {
            if (!empty($c->email)) {
                $toEmails[] = $c->email;
            }
        }
        $toEmails = array_values(array_unique($toEmails));

        $subject = $kind === 'reply_with_attachments'
            ? 'Comprobantes / respuesta OC ' . $order->po_number
            : 'Enviar orden ' . $order->po_number;

        $body = $kind === 'reply_with_attachments'
            ? '<p>Estimado proveedor,</p><p>Adjunto comprobantes y documentación relacionada con la orden de compra <strong>' . esc($order->po_number) . '</strong>.</p><p>Saludos cordiales.</p>'
            : '<p>Estimado proveedor,</p><p>Adjunto encontrará la orden de compra <strong>' . esc($order->po_number) . '</strong> para su revisión.</p><p>Saludos cordiales.</p>';

        $outboundDocs = $this->documentModel->getByOrder($poId, FinPurchaseOrderDocumentModel::CHANNEL_OUTBOUND);

        $this->setOutputSuccess('OK');
        $this->outputData['response'] = [
            'order'           => $order,
            'kind'            => $kind,
            'to_emails'       => implode(', ', $toEmails),
            'subject'         => $subject,
            'body_html'       => $body,
            'outbound_docs'   => $outboundDocs,
            'email_logs'      => $this->emailLogModel->getByOrder($poId),
        ];

        return $this->response->setJSON($this->outputData);
    }

    public function send_email(int $poId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $order = $this->purchaseOrderModel->getOrderWithItems($poId);
        if (!$order) {
            $this->setOutputError('Orden de compra no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $kind          = $this->request->getPost('email_kind') ?: 'send_po';
        $toRaw         = trim((string) $this->request->getPost('to_emails'));
        $ccRaw         = trim((string) $this->request->getPost('cc_emails'));
        $subject       = trim((string) $this->request->getPost('subject'));
        $bodyHtml      = (string) $this->request->getPost('body_html');
        $attachPoPdf   = (int) $this->request->getPost('attached_po_pdf') === 1;
        $documentIds   = $this->request->getPost('document_ids') ?? [];
        if (!is_array($documentIds)) {
            $documentIds = [];
        }

        $to = array_filter(array_map('trim', preg_split('/[,;]+/', $toRaw)));
        $cc = array_filter(array_map('trim', preg_split('/[,;]+/', $ccRaw)));

        if (empty($to) || $subject === '') {
            $this->setOutputError('Destinatarios y asunto son obligatorios.');
            return $this->response->setJSON($this->outputData);
        }

        $attachments = [];
        $validDocIds = [];

        foreach ($documentIds as $docId) {
            $doc = $this->documentModel->find((int) $docId);
            if (!$doc || (int) $doc->fin_purchase_order_id !== $poId) {
                continue;
            }
            if ($doc->doc_channel !== FinPurchaseOrderDocumentModel::CHANNEL_OUTBOUND) {
                continue;
            }
            $path = $this->documentModel->getAbsolutePath($doc);
            if (is_file($path)) {
                $attachments[] = $path;
                $validDocIds[] = (int) $doc->id;
            }
        }

        $tempPdf = null;
        if ($attachPoPdf) {
            $tempPdf = $this->_generateTempPdf($order);
            if ($tempPdf) {
                $attachments[] = $tempPdf;
            }
        }

        $mailer = new AppMailer();
        $sent   = $mailer->sendHtml($to, $cc, $subject, $bodyHtml, $attachments);

        if ($tempPdf && is_file($tempPdf)) {
            @unlink($tempPdf);
        }

        $logData = [
            'fin_purchase_order_id' => $poId,
            'email_kind'            => $kind,
            'to_emails'             => implode(', ', $to),
            'cc_emails'             => $ccRaw ?: null,
            'subject'               => $subject,
            'body_html'             => $bodyHtml,
            'attached_po_pdf'       => $attachPoPdf ? 1 : 0,
            'smtp_status'           => $sent ? 'sent' : 'failed',
            'smtp_error'            => $sent ? null : substr((string) $mailer->getError(), 0, 500),
            'sent_by'               => auth()->id(),
            'sent_at'               => date('Y-m-d H:i:s'),
        ];

        $this->emailLogModel->logSend($logData, $validDocIds);

        (new UserActivityLogsModel())->logActivity(
            'send_po_email',
            ($sent ? 'Envió' : 'Falló envío de') . ' correo OC ID: ' . $poId
                . ' | PO#: ' . $order->po_number . ' | tipo: ' . $kind
        );

        if (!$sent) {
            $this->setOutputError('No se pudo enviar el correo. Se registró el intento en el historial.');
            $this->outputData['csrf'] = csrf_hash();
            return $this->response->setJSON($this->outputData);
        }

        $this->setOutputSuccess('Correo enviado correctamente.');
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    private function _generateTempPdf(object $order): ?string
    {
        $company = (new \App\Models\Organization\OrgCompanyModel())->getProfile();
        $html    = view('Financial/PurchaseOrders/purchase_order_pdf', [
            'po'      => $order,
            'company' => $company,
        ]);

        $pdf = new \App\Libraries\PdfLibrary(['format' => 'Letter']);
        $pdf->loadHtml($html);

        $tempPath = WRITEPATH . 'uploads/temp/OC-' . $order->po_number . '-' . uniqid() . '.pdf';
        $dir      = dirname($tempPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($tempPath, $pdf->getAsString());

        return is_file($tempPath) ? $tempPath : null;
    }

    /**
     * @return list<\CodeIgniter\HTTP\Files\UploadedFile>
     */
    private function _collectUploadedFiles(string $field): array
    {
        $files = $this->request->getFileMultiple($field);
        if (is_array($files) && $files !== []) {
            return array_values(array_filter($files, static fn($file) => $file && $file->isValid()));
        }

        $single = $this->request->getFile($field);

        return ($single && $single->isValid()) ? [$single] : [];
    }
}
