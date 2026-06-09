<?php

namespace App\Controllers\Financial;

use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Financial\FinPurchaseOrderModel;
use App\Models\Financial\FinPurchaseOrderItemModel;
use App\Models\Users\UserActivityLogsModel;

/**
 * PurchaseOrderController
 *
 * Slim controller for the Purchase Orders sub-module.
 * All endpoints that mutate data are AJAX-only (§1).
 * Complex transactions are delegated entirely to FinPurchaseOrderModel (§9).
 * Every mutation is logged to the audit trail (§8).
 */
class PurchaseOrderController extends BaseFinancialController
{
    // ─────────────────────────────────────────────────────────────────────────
    // INDEX — Standalone PO listing view (HTML render, §15-A)
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): string
    {
        $this->setViewSuccess('Órdenes de Compra');
        $this->setPageTittleAhead('Órdenes de Compra', 'Gestión de Órdenes de Compra');

        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'              => base_url(),
            'Proveedores'         => route_to('suppliers.index'),
            'Órdenes de Compra'   => '',
        ]);

        $branchModel = new \App\Models\Organization\OrgBranchModel();

        $this->viewData['response'] = [
            'suppliers'           => $this->supplierModel->getActiveSuppliers(),
            'statuses'            => $this->_getStatusMap(),
            'branches'            => $branchModel->getActive(),
            'default_branch_id'   => $branchModel->getMainBranchId(),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Financial/PurchaseOrders/purchase_orders_index');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX — DataTables server-side source (§1, §5)
    // ─────────────────────────────────────────────────────────────────────────

    public function list_ajax()
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $postData = $this->request->getPost();
        $list     = $this->purchaseOrderModel->get_datatables($postData);
        $data     = [];
        $no       = (int) $this->request->getPost('start');
        $statusMap = $this->_getStatusMap();

        foreach ($list as $po) {
            $no++;

            $statusInfo  = $statusMap[$po->status] ?? ['label' => ucfirst($po->status), 'color' => 'secondary'];
            $statusBadge = '<span class="badge badge-subtle-' . $statusInfo['color'] . '">'
                         . '<i class="fas fa-fw ' . $statusInfo['icon'] . ' me-1"></i>'
                         . $statusInfo['label'] . '</span>';

            $issueDate    = !empty($po->issue_date)    ? date('d/m/Y', strtotime($po->issue_date))    : '—';
            $deliveryDate = !empty($po->delivery_date) ? date('d/m/Y', strtotime($po->delivery_date)) : '—';

            $amount = number_format((float) ($po->total_amount ?? 0), 2) . ' ' . esc($po->currency ?? 'MXN');

            // Actions (§7)
            $pid     = $po->id;
            $actions = '<div class="d-flex gap-1 align-items-center">';
            
            $actions .= '<a href="' . route_to('suppliers.po.download', $pid) . '?action=view" target="_blank" rel="noopener" '
                      . 'class="btn btn-outline-info btn-sm" title="Ver PDF"><i class="fas fa-file-pdf"></i></a>';
            
            $actions .= '<a href="' . route_to('suppliers.po.download', $pid) . '" '
                      . 'class="btn btn-outline-secondary btn-sm" title="Descargar PDF"><i class="fas fa-download"></i></a>';

            if (auth()->user()->can('catalog.manage-suppliers')) {
                if (in_array($po->status, ['draft', 'sent'])) {
                    $actions .= '<button class="btn btn-outline-warning btn-sm btn-edit-po" '
                              . 'data-id="' . $pid . '" title="Editar"><i class="fas fa-edit"></i></button>';
                }

                // Status transition buttons
                $transitions = $this->_allowedTransitionButtons($po->status, $pid);
                $actions    .= $transitions;

                if (!in_array($po->status, ['completed', 'cancelled'])) {
                    $actions .= '<button class="btn btn-outline-danger btn-sm btn-delete-po" '
                              . 'data-id="' . $pid . '" title="Eliminar"><i class="fas fa-trash"></i></button>';
                }
            }
            $actions .= '</div>';

            $data[] = [
                '<span class="font-monospace small fw-semibold">' . esc($po->po_number) . '</span>',
                esc($po->supplier_name ?? '—'),
                $statusBadge,
                $amount,
                esc($po->currency ?? 'MXN'),
                $issueDate,
                $deliveryDate,
                $actions,
            ];
        }

        return $this->response->setJSON([
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $this->purchaseOrderModel->count_all(),
            'recordsFiltered' => $this->purchaseOrderModel->count_filtered($postData),
            'data'            => $data,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX — Get PO detail with items for Offcanvas / modal (§1, §15-B)
    // ─────────────────────────────────────────────────────────────────────────

    public function show(int $id)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $order = $this->purchaseOrderModel->getOrderWithItems($id);
        if (!$order) {
            $this->setOutputError('Orden de compra no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $this->setOutputSuccess('Datos de la orden cargados.');
        $this->outputData['response'] = ['order' => $order];

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX — Get PO data for edit modal population (§1)
    // ─────────────────────────────────────────────────────────────────────────

    public function get_ajax(int $id)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $order = $this->purchaseOrderModel->getOrderWithItems($id);
        if (!$order) {
            $this->setOutputError('Orden de compra no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $this->setOutputSuccess('OK');
        $this->outputData['response'] = ['order' => $order];

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX — Generate next PO number (helper for form auto-fill)
    // ─────────────────────────────────────────────────────────────────────────

    public function generate_po_number()
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $this->setOutputSuccess('OK');
        $this->outputData['response'] = [
            'po_number' => $this->purchaseOrderModel->generatePoNumber(),
        ];

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST AJAX — Create new purchase order with items (§1, §8, §9)
    // ─────────────────────────────────────────────────────────────────────────

    public function store()
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $poData = $this->_extractPoData();
        $items  = $this->_extractItems();

        // Delegate the full transaction to the model (§9)
        $newId = $this->purchaseOrderModel->createPurchaseOrderWithItems($poData, $items);

        if (!$newId) {
            $this->setOutputError(
                'Error al guardar la orden de compra. Verifique los datos e intente nuevamente.',
                $this->purchaseOrderModel->errors()
            );
            return $this->response->setJSON($this->outputData);
        }

        // Audit log (§8)
        (new UserActivityLogsModel())->logActivity(
            'create_purchase_order',
            'Alta de OC ID: ' . $newId . ' | PO#: ' . ($poData['po_number'] ?? 'auto')
                . ' | Proveedor ID: ' . $poData['fin_supplier_id']
        );

        $this->setOutputSuccess('Orden de compra creada correctamente.');
        $this->outputData['response'] = ['id' => $newId];
        $this->outputData['csrf']     = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST AJAX — Update purchase order with items (§1, §8, §9)
    // ─────────────────────────────────────────────────────────────────────────

    public function update(int $id)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $order = $this->purchaseOrderModel->find($id);
        if (!$order) {
            $this->setOutputError('Orden de compra no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        // Only draft or sent orders can be modified
        if (!in_array($order->status, ['draft', 'sent'])) {
            $this->setOutputError('Solo se pueden editar órdenes en estado Borrador o Enviada.');
            return $this->response->setJSON($this->outputData);
        }

        $poData = $this->_extractPoData();
        $items  = $this->_extractItems();

        // Delegate the full transaction to the model (§9)
        $ok = $this->purchaseOrderModel->updatePurchaseOrderWithItems($id, $poData, $items);

        if (!$ok) {
            $this->setOutputError(
                'Error al actualizar la orden de compra.',
                $this->purchaseOrderModel->errors()
            );
            return $this->response->setJSON($this->outputData);
        }

        // Audit log (§8)
        (new UserActivityLogsModel())->logActivity(
            'update_purchase_order',
            'Edición de OC ID: ' . $id . ' | PO#: ' . $order->po_number
        );

        $this->setOutputSuccess('Orden de compra actualizada correctamente.');
        $this->outputData['response'] = ['id' => $id];
        $this->outputData['csrf']     = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST AJAX — Update PO status (workflow transitions) (§1, §8)
    // ─────────────────────────────────────────────────────────────────────────

    public function update_status(int $id)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $order = $this->purchaseOrderModel->find($id);
        if (!$order) {
            $this->setOutputError('Orden de compra no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $newStatus = trim((string) $this->request->getPost('status'));

        // Transition validation is delegated to the model (§9)
        $ok = $this->purchaseOrderModel->updateStatus($id, $newStatus);

        if (!$ok) {
            $this->setOutputError(
                'Transición de estado no válida o no permitida para "' . $order->status . '" → "' . $newStatus . '".'
            );
            return $this->response->setJSON($this->outputData);
        }

        // Audit log (§8)
        (new UserActivityLogsModel())->logActivity(
            'update_po_status',
            'Cambió estado de OC ID: ' . $id . ' | PO#: ' . $order->po_number
                . ' | ' . $order->status . ' → ' . $newStatus
        );

        $this->setOutputSuccess('Estado de la orden actualizado a: ' . ucfirst($newStatus) . '.');
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST AJAX — Soft-delete purchase order (§1, §8)
    // ─────────────────────────────────────────────────────────────────────────

    public function delete(int $id)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $order = $this->purchaseOrderModel->find($id);
        if (!$order) {
            $this->setOutputError('Orden de compra no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        if (!$this->purchaseOrderModel->softDelete($id)) {
            if (in_array($order->status, ['completed', 'approved'], true)) {
                $this->setOutputError('No se pueden eliminar órdenes en estado Aprobada o Completada.');
            } else {
                $this->setOutputError('No se pudo eliminar la orden de compra.');
            }
            return $this->response->setJSON($this->outputData);
        }

        // Audit log (§8)
        (new UserActivityLogsModel())->logActivity(
            'delete_purchase_order',
            'Eliminó (soft) OC ID: ' . $id . ' | PO#: ' . $order->po_number
        );

        $this->setOutputSuccess('Orden de compra eliminada correctamente.');
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST AJAX — Restore soft-deleted purchase order (§1, §8)
    // ─────────────────────────────────────────────────────────────────────────

    public function restore(int $id)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $order = $this->purchaseOrderModel->withDeleted()->find($id);
        if (!$order) {
            $this->setOutputError('Orden de compra no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        if (!$this->purchaseOrderModel->restore($id)) {
            $this->setOutputError('No se pudo restaurar la orden de compra.');
            return $this->response->setJSON($this->outputData);
        }

        // Audit log (§8)
        (new UserActivityLogsModel())->logActivity(
            'restore_purchase_order',
            'Restauró OC ID: ' . $id . ' | PO#: ' . $order->po_number
        );

        $this->setOutputSuccess('Orden de compra restaurada correctamente.');
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET — Download / Preview Purchase Order PDF (§9)
    // Pattern: WorkerContractController::downloadContract()
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * download
     *
     * Genera y descarga (o previsualiza) el PDF de una orden de compra.
     * - ?action=view → inline (previsualización en el navegador)
     * - Sin action → attachment (descarga directa)
     *
     * @param int $id ID de la orden de compra
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function download(int $id)
    {
        $order = $this->purchaseOrderModel->getOrderWithItems($id);

        if (!$order) {
            return redirect()->to(route_to('suppliers.po.index'))
                ->with('error', 'Orden de compra no encontrada.');
        }

        // Determinar modo: view (inline) o download (attachment)
        $action = $this->request->getGet('action') === 'view' ? 'I' : 'D';

        // Renderizar la vista PDF con los datos de la orden
        $html = view('Financial/PurchaseOrders/purchase_order_pdf', ['po' => $order]);

        // Generar el PDF usando la librería centralizada (formato Letter para OC)
        $pdf = new \App\Libraries\PdfLibrary(['format' => 'Letter']);
        $pdf->loadHtml($html);

        // Nombre del archivo: OC-{po_number}.pdf
        $filename = 'OC-' . $order->po_number . '.pdf';

        // Devolver la respuesta con el PDF
        $response = $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', ($action === 'I' ? 'inline' : 'attachment') . '; filename="' . $filename . '"')
            ->setBody($pdf->getAsString());

        return $response;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET AJAX — Orders for a specific supplier (Offcanvas tab)
    // ─────────────────────────────────────────────────────────────────────────

    public function by_supplier(int $supplierId)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $supplier = $this->supplierModel->find($supplierId);
        if (!$supplier) {
            $this->setOutputError('Proveedor no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $this->setOutputSuccess('OK');
        $this->outputData['response'] = [
            'orders' => $this->purchaseOrderModel->getOrdersBySupplier($supplierId),
        ];

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Extracts and sanitizes purchase order header fields from the request.
     */
    private function _extractPoData(): array
    {
        return [
            'fin_supplier_id' => (int) $this->request->getPost('fin_supplier_id'),
            'org_branch_id'   => (int) $this->request->getPost('org_branch_id'),
            'po_number'       => trim((string) $this->request->getPost('po_number')) ?: null,
            'status'          => $this->request->getPost('status') ?? 'draft',
            'currency'        => strtoupper(trim((string) $this->request->getPost('currency') ?: 'MXN')),
            'issue_date'      => $this->request->getPost('issue_date') ?: null,
            'delivery_date'   => $this->request->getPost('delivery_date') ?: null,
            'notes'           => trim((string) $this->request->getPost('notes')) ?: null,
        ];
    }

    /**
     * Extracts line items array from the request.
     * Expects items[0][description], items[0][quantity], items[0][unit_price], etc.
     */
    private function _extractItems(): array
    {
        $raw   = $this->request->getPost('items') ?? [];
        $items = [];

        if (!is_array($raw)) {
            return $items;
        }

        foreach ($raw as $item) {
            $description = trim((string) ($item['description'] ?? ''));
            if (empty($description)) {
                continue;
            }
            $quantity  = max(1, (int) ($item['quantity'] ?? 1));
            $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));

            $items[] = [
                'catalog_product_id' => !empty($item['catalog_product_id']) ? (int) $item['catalog_product_id'] : null,
                'description'        => $description,
                'quantity'           => $quantity,
                'unit_price'         => $unitPrice,
                'total_price'        => round($quantity * $unitPrice, 2),
            ];
        }

        return $items;
    }

    /**
     * Returns the status map (label, color, icon) for badges and transitions.
     */
    private function _getStatusMap(): array
    {
        return [
            'draft'     => ['label' => 'Borrador',  'color' => 'secondary', 'icon' => 'fa-file-alt'],
            'sent'      => ['label' => 'Enviada',   'color' => 'info',      'icon' => 'fa-paper-plane'],
            'approved'  => ['label' => 'Aprobada',  'color' => 'success',   'icon' => 'fa-check-circle'],
            'completed' => ['label' => 'Completada','color' => 'primary',   'icon' => 'fa-flag-checkered'],
            'cancelled' => ['label' => 'Cancelada', 'color' => 'danger',    'icon' => 'fa-ban'],
        ];
    }

    /**
     * Returns action buttons for valid status transitions (§7 — UI reactivity).
     */
    private function _allowedTransitionButtons(string $currentStatus, int $poId): string
    {
        $validNext = [
            'draft'    => [['status' => 'sent',      'label' => 'Enviar',    'color' => 'outline-info',    'icon' => 'fa-paper-plane'],
                           ['status' => 'cancelled',  'label' => 'Cancelar', 'color' => 'outline-danger',  'icon' => 'fa-ban']],
            'sent'     => [['status' => 'approved',   'label' => 'Aprobar',  'color' => 'outline-success', 'icon' => 'fa-check'],
                           ['status' => 'cancelled',  'label' => 'Cancelar', 'color' => 'outline-danger',  'icon' => 'fa-ban']],
            'approved' => [['status' => 'completed',  'label' => 'Completar','color' => 'outline-primary', 'icon' => 'fa-flag-checkered'],
                           ['status' => 'cancelled',  'label' => 'Cancelar', 'color' => 'outline-danger',  'icon' => 'fa-ban']],
        ];

        $html    = '';
        $buttons = $validNext[$currentStatus] ?? [];

        foreach ($buttons as $btn) {
            $html .= '<button class="btn btn-' . $btn['color'] . ' btn-sm btn-update-po-status" '
                   . 'data-id="' . $poId . '" '
                   . 'data-status="' . $btn['status'] . '" '
                   . 'title="' . $btn['label'] . '">'
                   . '<i class="fas fa-fw ' . $btn['icon'] . '"></i>'
                   . '</button>';
        }

        return $html;
    }
}
