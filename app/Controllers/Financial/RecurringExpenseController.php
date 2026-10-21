<?php

namespace App\Controllers\Financial;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\Financial\FinRecurringExpenseModel;
use App\Models\Financial\FinExpenseModel;
use App\Models\Financial\FinExpenseDocumentModel;
use App\Models\Users\UserActivityLogsModel;
use App\Services\RecurringExpenseScheduler;

class RecurringExpenseController extends BaseFinancialController
{
    private FinRecurringExpenseModel $recurringModel;
    private FinExpenseModel $expenseModel;
    private FinExpenseDocumentModel $expenseDocModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->recurringModel  = new FinRecurringExpenseModel();
        $this->expenseModel    = new FinExpenseModel();
        $this->expenseDocModel = new FinExpenseDocumentModel();
    }

    public function index(): string
    {
        $this->setViewSuccess('Servicios Recurrentes');
        $this->setPageTittleAhead('Servicios Recurrentes', 'Calendarización de Gastos');

        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'                 => base_url(),
            'Proveedores'            => route_to('suppliers.index'),
            'Servicios Recurrentes'  => '',
        ]);

        $branchModel = new \App\Models\Organization\OrgBranchModel();

        $this->viewData['response'] = [
            'stats'               => $this->expenseModel->getDashboardStats(),
            'branches'            => $branchModel->getActive(),
            'default_branch_id'   => $branchModel->getMainBranchId(),
            'suppliers'           => $this->supplierModel->getActiveSuppliers(),
            'service_types'       => FinRecurringExpenseModel::getServiceTypeOptions(),
            'service_type_groups' => FinRecurringExpenseModel::getServiceTypeGroups(),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Financial/Recurring/recurring_index');
    }

    public function list_ajax()
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $tab = $this->request->getPost('tab') ?: 'expenses';

        if ($tab === 'templates') {
            return $this->_listTemplatesAjax();
        }

        return $this->_listExpensesAjax();
    }

    private function _listExpensesAjax()
    {
        $postData = $this->request->getPost();
        $list     = $this->expenseModel->get_datatables($postData);
        $data     = [];
        $no       = (int) $this->request->getPost('start');

        foreach ($list as $row) {
            $no++;
            $statusMap = [
                'pending'   => ['label' => 'Pendiente', 'color' => 'warning'],
                'partial'   => ['label' => 'Parcial', 'color' => 'info'],
                'paid'      => ['label' => 'Pagado', 'color' => 'success'],
                'cancelled' => ['label' => 'Cancelado', 'color' => 'secondary'],
            ];
            $st = $statusMap[$row->status] ?? ['label' => $row->status, 'color' => 'secondary'];
            $badge = '<span class="badge badge-subtle-' . $st['color'] . '">' . $st['label'] . '</span>';

            $actions = '<button class="btn btn-outline-primary btn-sm btn-view-expense" data-id="' . $row->id . '" title="Ver"><i class="fas fa-eye"></i></button>';
            if (auth()->user()->can('catalog.recurring-manage') && in_array($row->status, ['pending', 'partial'], true)) {
                $actions .= ' <button class="btn btn-outline-success btn-sm btn-mark-paid" data-id="' . $row->id . '" data-total="' . (float) $row->total_amount . '" title="Marcar pagado"><i class="fas fa-check"></i></button>';
                $actions .= ' <button class="btn btn-outline-secondary btn-sm btn-reschedule-expense" data-id="' . $row->id . '" data-due="' . esc($row->due_date) . '" title="Reprogramar"><i class="fas fa-calendar-day"></i></button>';
                $actions .= ' <button class="btn btn-outline-danger btn-sm btn-cancel-expense" data-id="' . $row->id . '" title="Cancelar periodo"><i class="fas fa-ban"></i></button>';
            }

            $data[] = [
                !empty($row->due_date) ? date('d/m/Y', strtotime($row->due_date)) : '—',
                esc($row->supplier_name ?? '—'),
                esc($row->template_description ?? '—'),
                esc($row->branch_name ?? '—'),
                esc($row->period_label ?? '—'),
                number_format((float) $row->total_amount, 2),
                number_format((float) ($row->paid_amount ?? 0), 2),
                $badge,
                $actions,
            ];
        }

        return $this->response->setJSON([
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $this->expenseModel->count_all(),
            'recordsFiltered' => $this->expenseModel->count_filtered($postData),
            'data'            => $data,
        ]);
    }

    private function _listTemplatesAjax()
    {
        $postData = $this->request->getPost();
        $list     = $this->recurringModel->get_datatables($postData);
        $data     = [];
        $no       = (int) $this->request->getPost('start');

        foreach ($list as $row) {
            $no++;
            $active = (int) $row->is_active === 1
                ? '<span class="badge badge-subtle-success">Activo</span>'
                : '<span class="badge badge-subtle-secondary">Inactivo</span>';

            $freqMap = [
                'weekly'     => 'Semanal',
                'monthly'    => 'Mensual',
                'quarterly'  => 'Trimestral',
                'semiannual' => 'Semestral',
                'yearly'     => 'Anual',
            ];

            $actions = '';
            if (auth()->user()->can('catalog.recurring-manage')) {
                $actions .= '<button class="btn btn-outline-warning btn-sm btn-edit-template" data-id="' . $row->id . '" title="Editar"><i class="fas fa-edit"></i></button>';
                $isActive = (int) $row->is_active === 1;
                $actions .= ' <button class="btn btn-outline-secondary btn-sm btn-toggle-template" data-id="' . $row->id . '" data-active="' . ($isActive ? '1' : '0') . '" title="' . ($isActive ? 'Desactivar' : 'Activar') . '"><i class="fas ' . ($isActive ? 'fa-pause' : 'fa-play') . '"></i></button>';
            }

            $data[] = [
                esc($row->description),
                esc($row->supplier_name ?? '—'),
                esc($row->branch_name ?? '—'),
                esc(FinRecurringExpenseModel::getServiceTypeLabel($row->service_type)),
                number_format((float) $row->amount, 2) . ' ' . esc($row->currency ?? 'MXN'),
                esc($freqMap[$row->frequency] ?? $row->frequency),
                !empty($row->next_due_date) ? date('d/m/Y', strtotime($row->next_due_date)) : '—',
                $active,
                $actions,
            ];
        }

        return $this->response->setJSON([
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $this->recurringModel->count_all(),
            'recordsFiltered' => $this->recurringModel->count_filtered($postData),
            'data'            => $data,
        ]);
    }

    public function calendar_ajax()
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $start  = $this->request->getGet('start');
        $end    = $this->request->getGet('end');
        $events = $this->expenseModel->getCalendarEvents($start, $end);

        $this->setOutputSuccess('OK');
        $this->outputData['response'] = ['events' => $events];

        return $this->response->setJSON($this->outputData);
    }

    public function generate()
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $scheduler = new RecurringExpenseScheduler();
        $result    = $scheduler->generate();

        (new UserActivityLogsModel())->logActivity(
            'generate_recurring_expenses',
            'Generó vencimientos recurrentes: ' . $result['created'] . ' nuevos de ' . $result['templates'] . ' plantillas'
        );

        $this->setOutputSuccess('Generación completada: ' . $result['created'] . ' vencimiento(s) nuevo(s).');
        $this->outputData['response'] = $result;
        $this->outputData['response']['stats'] = $this->expenseModel->getDashboardStats();
        $this->outputData['csrf']     = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function mark_paid(int $id)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $expense = $this->expenseModel->find($id);
        if (!$expense) {
            $this->setOutputError('Vencimiento no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $paidAmount = (float) ($this->request->getPost('paid_amount') ?? $expense->total_amount);

        if (!$this->expenseModel->markPaid($id, $paidAmount)) {
            $this->setOutputError('No se pudo actualizar el pago.');
            return $this->response->setJSON($this->outputData);
        }

        (new UserActivityLogsModel())->logActivity(
            'mark_recurring_expense_paid',
            'Marcó pago vencimiento ID: ' . $id . ' | monto: ' . $paidAmount
        );

        $this->setOutputSuccess('Pago registrado correctamente.');
        $this->outputData['response'] = ['stats' => $this->expenseModel->getDashboardStats()];
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function list_documents(int $expenseId)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $expense = $this->expenseModel->getDetail($expenseId);
        if (!$expense) {
            $this->setOutputError('Vencimiento no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $this->setOutputSuccess('OK');
        $this->outputData['response'] = [
            'documents' => $this->expenseDocModel->getByExpense($expenseId),
            'expense'   => $expense,
        ];

        return $this->response->setJSON($this->outputData);
    }

    public function upload_document(int $expenseId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $expense = $this->expenseModel->find($expenseId);
        if (!$expense) {
            $this->setOutputError('Vencimiento no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $files = $this->_collectUploadedFiles('document_file');
        if (empty($files)) {
            $this->setOutputError('Seleccione uno o más archivos válidos.');
            return $this->response->setJSON($this->outputData);
        }

        $stored = [];
        $failed = 0;
        foreach ($files as $file) {
            $newId = $this->expenseDocModel->storeDocument($expenseId, $file, [
                'doc_type'    => $this->request->getPost('doc_type') ?: 'payment_proof',
                'notes'       => trim((string) $this->request->getPost('notes')) ?: null,
                'uploaded_by' => auth()->id(),
            ]);
            if ($newId) {
                $stored[] = $newId;
            } else {
                $failed++;
            }
        }

        if (empty($stored)) {
            $this->setOutputError('No se pudo guardar el comprobante.');
            return $this->response->setJSON($this->outputData);
        }

        (new UserActivityLogsModel())->logActivity(
            'upload_expense_document',
            'Subió ' . count($stored) . ' comprobante(s) a vencimiento ID: ' . $expenseId
        );

        $msg = count($stored) . ' comprobante(s) cargado(s) correctamente.';
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
        $document = $this->expenseDocModel->find($documentId);
        if (!$document) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Documento no encontrado.');
        }

        $filePath = $this->expenseDocModel->getAbsolutePath($document);
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

    public function store_template()
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $supplierId = (int) $this->request->getPost('supplier_id');
        $supplier   = $this->supplierModel->find($supplierId);
        if (!$supplier) {
            $this->setOutputError('Proveedor no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $frequency = $this->request->getPost('frequency') ?? 'monthly';

        $data = $this->_templatePayloadFromRequest($frequency);

        $newId = $this->recurringModel->createForSupplier($supplierId, $data);

        if (!$newId) {
            $this->setOutputError('Error al guardar la plantilla.', $this->recurringModel->errors());
            return $this->response->setJSON($this->outputData);
        }

        (new UserActivityLogsModel())->logActivity(
            'create_recurring_template',
            'Alta de plantilla recurrente ID: ' . $newId . ' | Proveedor ID: ' . $supplierId
        );

        $this->setOutputSuccess('Plantilla creada correctamente.');
        $this->outputData['response'] = ['id' => $newId];
        $this->outputData['csrf']     = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function get_template(int $id)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $template = $this->recurringModel->find($id);
        if (!$template) {
            $this->setOutputError('Plantilla no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $this->setOutputSuccess('OK');
        $this->outputData['response'] = ['template' => $template];

        return $this->response->setJSON($this->outputData);
    }

    public function update_template(int $id)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $template = $this->recurringModel->find($id);
        if (!$template) {
            $this->setOutputError('Plantilla no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $supplierId = (int) ($this->request->getPost('supplier_id') ?: $template->supplier_id);
        $supplier   = $this->supplierModel->find($supplierId);
        if (!$supplier) {
            $this->setOutputError('Proveedor no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $frequency = $this->request->getPost('frequency') ?? $template->frequency;
        $data      = $this->_templatePayloadFromRequest($frequency);
        $data['supplier_id'] = $supplierId;

        if (!$this->recurringModel->updateForSupplier($id, $data)) {
            $this->setOutputError('Error al actualizar la plantilla.', $this->recurringModel->errors());
            return $this->response->setJSON($this->outputData);
        }

        (new UserActivityLogsModel())->logActivity(
            'update_recurring_template',
            'Actualizó plantilla recurrente ID: ' . $id
        );

        $this->setOutputSuccess('Plantilla actualizada correctamente.');
        $this->outputData['response'] = ['id' => $id];
        $this->outputData['csrf']     = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function toggle_template(int $id)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $template = $this->recurringModel->find($id);
        if (!$template) {
            $this->setOutputError('Plantilla no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $posted = $this->request->getPost('is_active');
        $next   = $posted !== null ? (int) $posted : ((int) $template->is_active === 1 ? 0 : 1);

        if (!$this->recurringModel->update($id, ['is_active' => $next])) {
            $this->setOutputError('No se pudo cambiar el estatus de la plantilla.');
            return $this->response->setJSON($this->outputData);
        }

        (new UserActivityLogsModel())->logActivity(
            'toggle_recurring_template',
            'Cambió is_active=' . $next . ' en plantilla ID: ' . $id
        );

        $this->setOutputSuccess($next === 1 ? 'Plantilla activada.' : 'Plantilla desactivada.');
        $this->outputData['response'] = ['is_active' => $next];
        $this->outputData['csrf']     = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function cancel_expense(int $id)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        if (!$this->expenseModel->cancelOccurrence($id)) {
            $this->setOutputError('No se pudo cancelar el vencimiento.');
            return $this->response->setJSON($this->outputData);
        }

        (new UserActivityLogsModel())->logActivity(
            'cancel_recurring_expense',
            'Canceló vencimiento recurrente ID: ' . $id
        );

        $this->setOutputSuccess('Periodo cancelado.');
        $this->outputData['response'] = ['stats' => $this->expenseModel->getDashboardStats()];
        $this->outputData['csrf']     = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function reschedule_expense(int $id)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $dueDate = trim((string) $this->request->getPost('due_date'));
        if (!$this->expenseModel->rescheduleOccurrence($id, $dueDate)) {
            $this->setOutputError('No se pudo reprogramar el vencimiento.');
            return $this->response->setJSON($this->outputData);
        }

        (new UserActivityLogsModel())->logActivity(
            'reschedule_recurring_expense',
            'Reprogramó vencimiento ID: ' . $id . ' a ' . $dueDate
        );

        $this->setOutputSuccess('Vencimiento reprogramado.');
        $this->outputData['response'] = ['stats' => $this->expenseModel->getDashboardStats()];
        $this->outputData['csrf']     = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function delete_document(int $documentId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $document = $this->expenseDocModel->find($documentId);
        if (!$document) {
            $this->setOutputError('Documento no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        if (!$this->expenseDocModel->softDeleteDocument($documentId)) {
            $this->setOutputError('No se pudo eliminar el comprobante.');
            return $this->response->setJSON($this->outputData);
        }

        (new UserActivityLogsModel())->logActivity(
            'delete_expense_document',
            'Eliminó comprobante ID: ' . $documentId . ' de vencimiento ID: ' . $document->fin_expense_id
        );

        $this->setOutputSuccess('Comprobante eliminado.');
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
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

    private function _templatePayloadFromRequest(string $frequency): array
    {
        return [
            'description'        => trim((string) $this->request->getPost('description')),
            'service_type'       => trim((string) ($this->request->getPost('service_type') ?: 'other')),
            'amount'             => (float) ($this->request->getPost('amount') ?? 0),
            'currency'           => strtoupper(trim((string) ($this->request->getPost('currency') ?: 'MXN'))),
            'billing_day'        => $frequency === 'weekly' ? null : (int) ($this->request->getPost('billing_day') ?? 1),
            'billing_weekday'    => $frequency === 'weekly' ? (int) ($this->request->getPost('billing_weekday') ?? 1) : null,
            'frequency'          => $frequency,
            'org_branch_id'      => (int) ($this->request->getPost('org_branch_id') ?: 0) ?: null,
            'start_date'         => $this->request->getPost('start_date') ?: date('Y-m-d'),
            'end_date'           => $this->request->getPost('end_date') ?: null,
            'notify_days_before' => (int) ($this->request->getPost('notify_days_before') ?? 3),
            'is_active'          => $this->request->getPost('is_active') !== null ? (int) $this->request->getPost('is_active') : 1,
        ];
    }
}
