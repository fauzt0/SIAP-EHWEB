<?php

declare(strict_types=1);

namespace App\Controllers\Sales;

use App\Models\CRM\CrmFollowupModel;
use App\Models\Sales\BillingPaymentModel;
use App\Models\Sales\CustomerServiceModel;
use App\Models\Sales\SalesOrderItemModel;
use App\Models\Sales\SalesOrderModel;
use App\Models\Sales\SalesPaymentProofModel;
use App\Models\Sales\SalesReceiptModel;
use App\Models\Users\UserActivityLogsModel;
use CodeIgniter\HTTP\ResponseInterface;

class BillingController extends BaseSalesController
{
    public function index(): string
    {
        $this->setViewSuccess('Recibos y cobros');
        $this->setPageTittleAhead('Facturación', 'Recibos internos');
        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'       => base_url(),
            'Ventas'       => route_to('sales.orders'),
            'Facturación'  => '',
        ]);
        $receipts = new SalesReceiptModel();
        $this->viewData['response'] = [
            'orders' => (new SalesOrderModel())
                ->select('sales_orders.id, sales_orders.order_number, sales_orders.total_amount, sales_orders.status, crm_clients.company_name')
                ->join('crm_clients', 'crm_clients.id = sales_orders.client_id', 'left')
                ->whereIn('sales_orders.status', ['draft', 'pending', 'partial', 'confirmed', 'paid'])
                ->orderBy('sales_orders.id', 'DESC')
                ->findAll(100),
            'forecast' => $receipts->forecastNextMonth(),
            'open'     => $receipts->openCollections(null, 8),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Sales/billing_index');
    }

    public function list_ajax(): ResponseInterface
    {
        if (! $this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $model = new SalesReceiptModel();
        $rows  = $model->select('sales_receipts.*, crm_clients.company_name, sales_orders.order_number, sales_orders.status AS order_status')
            ->join('crm_clients', 'crm_clients.id = sales_receipts.client_id', 'left')
            ->join('sales_orders', 'sales_orders.id = sales_receipts.order_id', 'left')
            ->orderBy('sales_receipts.id', 'DESC')
            ->findAll(200);

        $data = [];
        foreach ($rows as $row) {
            $kind = ($row->receipt_kind ?? 'one_time') === 'renewal' ? 'Renovación' : 'Único';
            $coll = $row->collection_status ?? $row->status;
            $data[] = [
                '<span class="font-monospace">' . esc($row->receipt_number) . '</span>',
                esc($row->company_name ?? '—'),
                esc($row->order_number ?? ('#' . $row->order_id)),
                '<span class="badge badge-subtle-info">' . esc($kind) . '</span> '
                    . esc($row->period_label ?? '—'),
                '$' . number_format((float) $row->total_amount, 2),
                '<span class="badge badge-subtle-primary">' . esc($coll) . '</span>',
                esc($row->due_date ?? ''),
                '<div class="d-flex gap-1 flex-wrap"><button class="btn btn-outline-info btn-sm btn-show-receipt" data-id="' . (int) $row->id . '"><i class="fas fa-eye"></i></button>'
                    . $this->pdfButtons('receipt', (int) $row->id) . '</div>',
            ];
        }

        return $this->response->setJSON([
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => count($data),
            'recordsFiltered' => count($data),
            'data'            => $data,
        ]);
    }

    public function show_ajax(int $id): ResponseInterface
    {
        if (! $this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $receipt = (new SalesReceiptModel())->detail($id);
        if (! $receipt) {
            $this->setOutputError('Recibo no encontrado.');

            return $this->response->setJSON($this->outputData);
        }

        $payments = (new BillingPaymentModel())->where('receipt_id', (int) $receipt->id)->findAll();
        $proofs   = (new SalesPaymentProofModel())->where('client_id', (int) $receipt->client_id)->where('receipt_id', (int) $receipt->id)->findAll();
        $paid     = (new BillingPaymentModel())->paidTotalForReceipt((int) $receipt->id);

        $this->setOutputSuccess('Recibo', [
            'receipt'  => $receipt,
            'payments' => $payments,
            'proofs'   => $proofs,
            'paid'     => $paid,
            'balance'  => max(0, (float) $receipt->total_amount - $paid),
        ]);
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function issue(): ResponseInterface
    {
        if (! $this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $orderId = (int) $this->request->getPost('order_id');
        $model   = new SalesReceiptModel();
        $id      = $model->issueFromOrder($orderId, auth()->id(), $this->request->getPost('notes'));
        if (! $id) {
            $this->setOutputError('No se pudo emitir el recibo.', $model->errors());
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $receipt = $model->find($id);
        (new CrmFollowupModel())->createVersion([
            'client_id'      => (int) $receipt->client_id,
            'followup_type'  => 'order',
            'title'          => 'Recibo ' . $receipt->receipt_number,
            'comment'        => 'Recibo emitido. Factura interna en borrador para timbrado posterior.',
            'sales_order_id' => $orderId,
            'created_by'       => auth()->id(),
            'change_source'    => 'auto',
            'followup_status'  => 'closed',
        ]);
        (new UserActivityLogsModel())->logActivity(
            'sales_receipt_issue',
            'Recibo ' . $receipt->receipt_number . ' orden ID ' . $orderId
        );
        $this->setOutputSuccess('Recibo emitido', ['id' => $id]);
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function pay(): ResponseInterface
    {
        if (! $this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $model = new BillingPaymentModel();
        $id    = $model->register([
            'order_id'       => (int) $this->request->getPost('order_id'),
            'receipt_id'     => $this->request->getPost('receipt_id') ?: null,
            'payment_method' => $this->request->getPost('payment_method') ?: 'transfer',
            'amount_paid'    => $this->request->getPost('amount_paid'),
            'transaction_id' => $this->request->getPost('transaction_id') ?: null,
            'notes'          => $this->request->getPost('notes'),
            'created_by'     => auth()->id(),
            'status'         => 'completed',
        ]);

        if (! $id) {
            $this->setOutputError('No se registró el cobro.', $model->errors());
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $pay = $model->find($id);
        if (! empty($pay->client_id)) {
            (new CrmFollowupModel())->createVersion([
                'client_id'       => (int) $pay->client_id,
                'followup_type'   => 'order',
                'title'           => 'Cobro registrado',
                'comment'         => 'Pago ' . $pay->amount_paid . ' vía ' . $pay->payment_method,
                'sales_order_id'  => (int) $pay->order_id,
                'created_by'      => auth()->id(),
                'change_source'   => 'auto',
                'followup_status' => 'closed',
            ]);
        }
        (new UserActivityLogsModel())->logActivity('sales_payment_register', 'Cobro ID ' . $id . ' orden ' . $pay->order_id);
        $this->setOutputSuccess('Cobro registrado', ['id' => $id]);
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function upload_proof(): ResponseInterface
    {
        if (! $this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $clientId = (int) $this->request->getPost('client_id');
        $file     = $this->request->getFile('proof_file');
        $model    = new SalesPaymentProofModel();
        $id       = $model->storeProof($clientId, $file, [
            'order_id'    => $this->request->getPost('order_id') ?: null,
            'payment_id'  => $this->request->getPost('payment_id') ?: null,
            'receipt_id'  => $this->request->getPost('receipt_id') ?: null,
            'notes'       => $this->request->getPost('notes'),
            'uploaded_by' => auth()->id(),
        ]);

        if (! $id) {
            $this->setOutputError('Comprobante no válido (PDF o imagen, máx. 8 MB).');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        (new UserActivityLogsModel())->logActivity('sales_proof_upload', 'Comprobante ID ' . $id . ' cliente ' . $clientId);
        $this->setOutputSuccess('Comprobante cargado', ['id' => $id]);
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function download_proof(int $id)
    {
        $row = (new SalesPaymentProofModel())->find($id);
        if (! $row) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Comprobante no encontrado.');
        }
        $path = (new SalesPaymentProofModel())->absolutePath($row);
        if (! is_file($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Archivo no encontrado.');
        }

        return $this->response->download($path, null)->setFileName($row->original_name);
    }

    public function activate_item(int $itemId): ResponseInterface
    {
        return $this->activate('item', $itemId);
    }

    public function activate_service(int $serviceId): ResponseInterface
    {
        return $this->activate('service', $serviceId);
    }

    private function activate(string $kind, int $id): ResponseInterface
    {
        if (! $this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        if ($kind === 'item') {
            $model = new SalesOrderItemModel();
        } else {
            $model = new CustomerServiceModel();
        }
        $row = $model->find($id);
        if (! $row) {
            $this->setOutputError('Registro no encontrado.');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $model->update($id, ['activation_status' => 'activated']);
        (new UserActivityLogsModel())->logActivity(
            'sales_activate',
            'Activó ' . $kind . ' ID ' . $id
        );
        $this->setOutputSuccess('Activado');
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function generate_renewals(): ResponseInterface
    {
        if (! $this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $result = (new \App\Services\SalesReceiptScheduler())->generateDue(null, auth()->id());
        (new UserActivityLogsModel())->logActivity(
            'sales_receipts_generate',
            'Generó recibos de renovación: ' . ($result['created'] ?? 0)
        );
        $this->setOutputSuccess(
            'Revisión de mes: ' . (int) ($result['created'] ?? 0) . ' recibo(s) nuevo(s).',
            $result
        );
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function forecast_ajax(): ResponseInterface
    {
        if (! $this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $forecast = (new SalesReceiptModel())->forecastNextMonth();
        \App\Libraries\PluginSystem\PluginHookRegistry::doAction('sales.collections.forecast', $forecast, null);
        $this->setOutputSuccess('Cobranza aproximada del siguiente mes', $forecast);
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }
}
