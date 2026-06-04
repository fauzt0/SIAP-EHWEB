<?php

namespace App\Controllers\Financial;

use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Financial\FinSupplierModel;
use App\Models\Financial\FinSupplierContactModel;
use App\Models\Users\UserActivityLogsModel;
use App\Models\Catalog\CatalogProductModel;

/**
 * SupplierController
 *
 * Slim controller for the Suppliers Management module.
 * Orchestrates HTTP traffic only — all DB mutations are delegated to models (§9).
 * All mutations are logged to the audit trail (§8).
 */
class SupplierController extends BaseFinancialController
{
    // ─────────────────────────────────────────────────────────────────────────
    // INDEX — Main listing view (HTML render, §15-A)
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): string
    {
        $this->setViewSuccess('Listado de Proveedores');
        $this->setPageTittleAhead('Proveedores', 'Gestión de Proveedores');

        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'      => base_url(),
            'Proveedores' => '',
        ]);

        // Stats cards (cached 5 min)
        $stats = cache()->get('fin_supplier_stats');
        if (!$stats) {
            $stats = $this->supplierModel->getStats();
            cache()->save('fin_supplier_stats', $stats, 300);
        }

        $branchModel = new \App\Models\Organization\OrgBranchModel();

        $this->viewData['response'] = [
            'stats'                 => $stats,
            'supplier_types'        => $this->supplierModel->getSupplierTypes(),
            'branches'              => $branchModel->getActive(),
            'default_branch_id'     => $branchModel->getMainBranchId(),
            'recurring_service_types' => \App\Models\Financial\FinRecurringExpenseModel::getServiceTypeGroups(),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Financial/Suppliers/suppliers_index');
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
        $list     = $this->supplierModel->get_datatables($postData);
        $data     = [];
        $no       = (int) $this->request->getPost('start');

        foreach ($list as $supplier) {
            $no++;

            // Status badge (activo/inactivo/eliminado)
            if (!empty($supplier->deleted_at)) {
                $statusBadge = '<span class="badge badge-subtle-dark"><i class="fas fa-trash me-1"></i>Eliminado</span>';
            } else {
                $statusBadge = $supplier->status
                    ? '<span class="badge badge-subtle-success"><i class="fas fa-check-circle me-1"></i>Activo</span>'
                    : '<span class="badge badge-subtle-danger"><i class="fas fa-times-circle me-1"></i>Inactivo</span>';
            }

            // Supplier type badge (§12 — fas icons only)
            $typeMap = [
                'infrastructure' => ['label' => 'Infraestructura', 'color' => 'info',      'icon' => 'fa-server'],
                'hardware'       => ['label' => 'Hardware',        'color' => 'secondary',  'icon' => 'fa-hdd'],
                'services'       => ['label' => 'Servicios',       'color' => 'primary',    'icon' => 'fa-cloud'],
                'administrative' => ['label' => 'Administrativo',  'color' => 'warning',    'icon' => 'fa-briefcase'],
            ];
            $typeInfo    = $typeMap[$supplier->supplier_type] ?? ['label' => ucfirst($supplier->supplier_type ?? ''), 'color' => 'secondary', 'icon' => 'fa-tag'];
            $typeBadge   = '<span class="badge badge-subtle-' . $typeInfo['color'] . '">'
                         . '<i class="fas fa-fw ' . $typeInfo['icon'] . ' me-1"></i>'
                         . $typeInfo['label'] . '</span>';

            // Contact counts
            $contactsCount = (int) ($supplier->contacts_count ?? 0);
            $ordersCount   = (int) ($supplier->orders_count ?? 0);

            $contactsBadge = $contactsCount > 0
                ? '<span class="badge bg-secondary rounded-pill">' . $contactsCount . '</span>'
                : '<span class="text-muted small">—</span>';

            $ordersBadge = $ordersCount > 0
                ? '<span class="badge bg-primary rounded-pill">' . $ordersCount . '</span>'
                : '<span class="text-muted small">—</span>';

            // Actions (§7 — permission checks)
            $sid     = $supplier->id;
            $actions = '<div class="d-flex gap-1 align-items-center">';

            if (auth()->user()->can('catalog.manage-suppliers') && !empty($supplier->deleted_at)) {
                $actions .= '<button class="btn btn-outline-success btn-sm btn-restore-supplier" '
                          . 'data-id="' . $sid . '" title="Restaurar proveedor">'
                          . '<i class="fas fa-trash-restore"></i></button>';
            } else {
                $actions .= '<button class="btn btn-outline-info btn-sm btn-show-supplier" '
                          . 'data-id="' . $sid . '" title="Ver perfil">'
                          . '<i class="fas fa-eye"></i></button>';

                if (auth()->user()->can('catalog.manage-suppliers')) {
                    $actions .= '<button class="btn btn-outline-warning btn-sm btn-edit-supplier" '
                              . 'data-id="' . $sid . '" title="Editar">'
                              . '<i class="fas fa-edit"></i></button>';

                    $toggleIcon  = $supplier->status ? 'fa-eye-slash' : 'fa-eye';
                    $toggleTitle = $supplier->status ? 'Desactivar' : 'Activar';
                    $toggleColor = $supplier->status ? 'outline-danger' : 'outline-success';
                    $actions    .= '<button class="btn btn-' . $toggleColor . ' btn-sm btn-toggle-supplier" '
                                 . 'data-id="' . $sid . '" title="' . $toggleTitle . '">'
                                 . '<i class="fas ' . $toggleIcon . '"></i></button>';

                    $actions .= '<button class="btn btn-outline-danger btn-sm btn-delete-supplier" '
                              . 'data-id="' . $sid . '" title="Eliminar">'
                              . '<i class="fas fa-trash"></i></button>';
                }
            }

            $actions .= '</div>';

            $data[] = [
                $typeBadge,
                '<div class="fw-semibold">' . esc($supplier->commercial_name) . '</div>'
                . '<small class="text-muted">' . esc($supplier->legal_name ?? '') . '</small>',
                '<span class="font-monospace small">' . esc($supplier->tax_id ?? '—') . '</span>',
                esc($supplier->contact_email ?? '—'),
                esc($supplier->contact_phone ?? '—'),
                $contactsBadge,
                $ordersBadge,
                $statusBadge,
                $actions,
            ];
        }

        return $this->response->setJSON([
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $this->supplierModel->count_all([], $postData),
            'recordsFiltered' => $this->supplierModel->count_filtered($postData),
            'data'            => $data,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX — Show supplier profile for Offcanvas (§1, §15-B)
    // ─────────────────────────────────────────────────────────────────────────

    public function show(int $id)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $supplier = $this->supplierModel->find($id);
        if (!$supplier) {
            $this->setOutputError('Proveedor no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $contactModel = new FinSupplierContactModel();
        $contacts     = $contactModel->getContactsBySupplier($id);
        $orders       = $this->purchaseOrderModel->getOrdersBySupplier($id);

        $this->setOutputSuccess('Datos del proveedor cargados.');
        $this->outputData['response'] = [
            'supplier' => $supplier,
            'contacts' => $contacts,
            'orders'   => $orders,
        ];

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX — Load supplier data into the edit modal (§1, §15-B)
    // ─────────────────────────────────────────────────────────────────────────

    public function get_ajax(int $id)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $supplier = $this->supplierModel->find($id);
        if (!$supplier) {
            $this->setOutputError('Proveedor no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $contactModel = new FinSupplierContactModel();

        $this->setOutputSuccess('OK');
        $this->outputData['response'] = [
            'supplier' => $supplier,
            'contacts' => $contactModel->getContactsBySupplier($id),
        ];

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST AJAX — Store new supplier (§1, §8, §9, §15-B)
    // ─────────────────────────────────────────────────────────────────────────

    public function store()
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $supplierData = $this->_extractSupplierData();
        $contacts     = $this->_extractContacts();

        // Delegate transaction to model (§9)
        $newId = $this->supplierModel->createSupplierWithContacts($supplierData, $contacts);

        if (!$newId) {
            $this->setOutputError(
                'Error al guardar el proveedor. Verifique los datos e intente nuevamente.',
                $this->supplierModel->errors()
            );
            return $this->response->setJSON($this->outputData);
        }

        cache()->delete('fin_supplier_stats');

        // Audit log (§8)
        (new UserActivityLogsModel())->logActivity(
            'create_supplier',
            'Alta de proveedor ID: ' . $newId . ' | ' . $supplierData['commercial_name']
        );

        $this->setOutputSuccess('Proveedor creado correctamente.');
        $this->outputData['response'] = ['id' => $newId];
        $this->outputData['csrf']     = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST AJAX — Update existing supplier (§1, §8, §9, §15-B)
    // ─────────────────────────────────────────────────────────────────────────

    public function update(int $id)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $supplier = $this->supplierModel->find($id);
        if (!$supplier) {
            $this->setOutputError('Proveedor no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $supplierData = $this->_extractSupplierData();
        $contacts     = $this->_extractContacts();

        // Delegate transaction to model (§9)
        $ok = $this->supplierModel->updateSupplierWithContacts($id, $supplierData, $contacts);

        if (!$ok) {
            $this->setOutputError(
                'Error al actualizar el proveedor.',
                $this->supplierModel->errors()
            );
            return $this->response->setJSON($this->outputData);
        }

        cache()->delete('fin_supplier_stats');

        // Audit log (§8)
        (new UserActivityLogsModel())->logActivity(
            'update_supplier',
            'Edición de proveedor ID: ' . $id . ' | ' . $supplierData['commercial_name']
        );

        $this->setOutputSuccess('Proveedor actualizado correctamente.');
        $this->outputData['response'] = ['id' => $id];
        $this->outputData['csrf']     = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST AJAX — Soft-delete supplier (§1, §8)
    // ─────────────────────────────────────────────────────────────────────────

    public function delete(int $id)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $supplier = $this->supplierModel->find($id);
        if (!$supplier) {
            $this->setOutputError('Proveedor no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $this->supplierModel->softDelete($id);
        cache()->delete('fin_supplier_stats');

        // Audit log (§8)
        (new UserActivityLogsModel())->logActivity(
            'delete_supplier',
            'Eliminó (soft) proveedor ID: ' . $id . ' | ' . $supplier->commercial_name
        );

        $this->setOutputSuccess('Proveedor eliminado correctamente.');
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST AJAX — Restore soft-deleted supplier (§1, §8)
    // ─────────────────────────────────────────────────────────────────────────

    public function restore(int $id)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $supplier = $this->supplierModel->withDeleted()->find($id);
        if (!$supplier) {
            $this->setOutputError('Proveedor no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        if (!$this->supplierModel->restore($id)) {
            $this->setOutputError('No se pudo restaurar el proveedor.');
            return $this->response->setJSON($this->outputData);
        }

        cache()->delete('fin_supplier_stats');

        // Audit log (§8)
        (new UserActivityLogsModel())->logActivity(
            'restore_supplier',
            'Restauró proveedor ID: ' . $id . ' | ' . $supplier->commercial_name
        );

        $this->setOutputSuccess('Proveedor restaurado correctamente.');
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST AJAX — Toggle active/inactive status (§1, §8)
    // ─────────────────────────────────────────────────────────────────────────

    public function toggle_status(int $id)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $supplier = $this->supplierModel->find($id);
        if (!$supplier) {
            $this->setOutputError('Proveedor no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $newStatus = $this->supplierModel->toggleStatus($id);
        if ($newStatus === false) {
            $this->setOutputError('No se pudo actualizar el estatus del proveedor.');
            return $this->response->setJSON($this->outputData);
        }

        cache()->delete('fin_supplier_stats');

        $actionStr = $newStatus ? 'Activó' : 'Desactivó';

        // Audit log (§8)
        (new UserActivityLogsModel())->logActivity(
            'update_supplier_status',
            $actionStr . ' proveedor ID: ' . $id . ' | ' . $supplier->commercial_name
        );

        $this->setOutputSuccess('Proveedor ' . strtolower($actionStr) . ' correctamente.');
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST AJAX — Link resellable product to supplier via catalog_product_suppliers
    // ─────────────────────────────────────────────────────────────────────────

    public function link_product(int $supplierId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $supplier = $this->supplierModel->find($supplierId);
        if (!$supplier) {
            $this->setOutputError('Proveedor no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $linkData = [
            'catalog_product_id' => (int) $this->request->getPost('catalog_product_id'),
            'fin_supplier_id'    => $supplierId,
            'purchase_cost'      => (float) ($this->request->getPost('purchase_cost') ?? 0),
            'purchase_currency'  => strtoupper(trim((string) $this->request->getPost('purchase_currency') ?: 'USD')),
            'supplier_due_date'  => $this->request->getPost('supplier_due_date') ?: null,
            'is_auto_renew'      => $this->request->getPost('is_auto_renew') ? 1 : 0,
        ];

        if (empty($linkData['catalog_product_id'])) {
            $this->setOutputError('El producto del catálogo es obligatorio.');
            return $this->response->setJSON($this->outputData);
        }

        // Verify product exists
        $productModel = new CatalogProductModel();
        if (!$productModel->find($linkData['catalog_product_id'])) {
            $this->setOutputError('El producto especificado no existe.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        // Delegate to model (§9)
        $newId = $this->supplierModel->linkProduct($linkData);

        if (!$newId) {
            $this->setOutputError('Error al asociar el producto al proveedor.');
            return $this->response->setJSON($this->outputData);
        }

        // Audit log (§8)
        (new UserActivityLogsModel())->logActivity(
            'link_supplier_product',
            'Asoció producto ID: ' . $linkData['catalog_product_id'] . ' a proveedor ID: ' . $supplierId
        );

        $this->setOutputSuccess('Producto asociado correctamente.');
        $this->outputData['response'] = ['id' => $newId];
        $this->outputData['csrf']     = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST AJAX — Unlink product from supplier (catalog_product_suppliers)
    // ─────────────────────────────────────────────────────────────────────────

    public function unlink_product(int $supplierId, int $linkId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $ok = $this->supplierModel->unlinkProduct($linkId, $supplierId);

        if (!$ok) {
            $this->setOutputError('Asociación no encontrada o no se pudo eliminar.');
            return $this->response->setJSON($this->outputData);
        }

        // Audit log (§8)
        (new UserActivityLogsModel())->logActivity(
            'unlink_supplier_product',
            'Desvinculó asociación ID: ' . $linkId . ' del proveedor ID: ' . $supplierId
        );

        $this->setOutputSuccess('Asociación eliminada correctamente.');
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET AJAX — Activity timeline for a supplier (§10, §8)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * activity
     *
     * Obtiene las últimas 20 entradas de auditoría relacionadas con un proveedor.
     * Se usa en el Offcanvas de perfil para mostrar la línea de tiempo de actividad.
     *
     * @param int $id ID del proveedor
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function activity(int $id)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $logs = (new UserActivityLogsModel())
            ->like('description', 'proveedor ID: ' . $id)
            ->orderBy('created_at', 'DESC')
            ->findAll(20);

        $this->setOutputSuccess('OK');
        $this->outputData['response'] = ['logs' => $logs];

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET AJAX — List recurring services for a supplier (§11)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * list_services
     *
     * Obtiene todos los gastos recurrentes (servicios periódicos) asociados
     * a un proveedor. Se usa en la pestaña de Servicios del Offcanvas.
     *
     * @param int $id ID del proveedor
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function list_services(int $id)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $supplier = $this->supplierModel->find($id);
        if (!$supplier) {
            $this->setOutputError('Proveedor no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $recurringModel = new \App\Models\Financial\FinRecurringExpenseModel();
        $services = $recurringModel->getBySupplier($id);

        $this->setOutputSuccess('OK');
        $this->outputData['response'] = ['services' => $services];

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST AJAX — Store a recurring service for a supplier (§11, §8)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * store_service
     *
     * Crea un nuevo gasto recurrente (servicio periódico) asociado a un proveedor.
     * Registra la acción en la bitácora de auditoría.
     *
     * @param int $id ID del proveedor
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function store_service(int $id)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $supplier = $this->supplierModel->find($id);
        if (!$supplier) {
            $this->setOutputError('Proveedor no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        // Extraer datos del formulario y delegar al modelo (§9, §19)
        $frequency = $this->request->getPost('frequency') ?? 'monthly';

        $data = [
            'description'   => trim((string) $this->request->getPost('description')),
            'service_type'  => trim((string) ($this->request->getPost('service_type') ?: 'other')),
            'amount'        => (float) ($this->request->getPost('amount') ?? 0),
            'currency'      => strtoupper(trim((string) ($this->request->getPost('currency') ?: 'MXN'))),
            'billing_day'   => $frequency === 'weekly' ? null : (int) ($this->request->getPost('billing_day') ?? 1),
            'frequency'     => $frequency,
            'is_active'     => $this->request->getPost('is_active') !== null ? (int) $this->request->getPost('is_active') : 1,
        ];

        $recurringModel = new \App\Models\Financial\FinRecurringExpenseModel();
        $newId = $recurringModel->createForSupplier($id, $data);

        if (!$newId) {
            $this->setOutputError(
                'Error al guardar el servicio recurrente.',
                $recurringModel->errors()
            );
            return $this->response->setJSON($this->outputData);
        }

        // Audit log (§8)
        (new UserActivityLogsModel())->logActivity(
            'create_recurring_expense',
            'Alta de gasto recurrente ID: ' . $newId
                . ' | Servicio: ' . $data['description']
                . ' | Proveedor ID: ' . $id
        );

        $this->setOutputSuccess('Servicio recurrente guardado correctamente.');
        $this->outputData['response'] = ['id' => $newId];
        $this->outputData['csrf']     = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET AJAX — List products linked to a supplier
    // ─────────────────────────────────────────────────────────────────────────

    public function get_linked_products(int $supplierId)
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
            'products' => $this->supplierModel->getLinkedProducts($supplierId),
        ];

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET AJAX — Active suppliers list for Select2 / dropdowns
    // ─────────────────────────────────────────────────────────────────────────

    public function select_ajax()
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $this->setOutputSuccess('OK');
        $this->outputData['response'] = [
            'suppliers' => $this->supplierModel->getActiveSuppliers(),
        ];

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS — Input extraction (no DB calls here)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Extracts and sanitizes supplier fields from the HTTP request.
     */
    private function _extractSupplierData(): array
    {
        return [
            'supplier_type'  => $this->request->getPost('supplier_type') ?? 'services',
            'legal_name'     => trim((string) $this->request->getPost('legal_name')) ?: null,
            'commercial_name'=> trim((string) $this->request->getPost('commercial_name')),
            'tax_id'         => trim((string) $this->request->getPost('tax_id')) ?: null,
            'contact_person' => trim((string) $this->request->getPost('contact_person')) ?: null,
            'contact_email'  => trim((string) $this->request->getPost('contact_email')) ?: null,
            'contact_phone'  => trim((string) $this->request->getPost('contact_phone')) ?: null,
            'website'        => trim((string) $this->request->getPost('website')) ?: null,
            'address'        => trim((string) $this->request->getPost('address')) ?: null,
            'bank_details'   => trim((string) $this->request->getPost('bank_details')) ?: null,
            'notes'          => trim((string) $this->request->getPost('notes')) ?: null,
            'status'         => $this->request->getPost('status') !== null ? (int) $this->request->getPost('status') : 1,
        ];
    }

    /**
     * Extracts additional contacts array from the HTTP request.
     * Expects contacts[0][contact_name], contacts[0][email], etc.
     */
    private function _extractContacts(): array
    {
        $raw      = $this->request->getPost('contacts') ?? [];
        $contacts = [];

        if (!is_array($raw)) {
            return $contacts;
        }

        foreach ($raw as $contact) {
            $name = trim((string) ($contact['contact_name'] ?? ''));
            if (empty($name)) {
                continue;
            }
            $contacts[] = [
                'contact_name' => $name,
                'job_title'    => trim((string) ($contact['job_title'] ?? '')) ?: null,
                'email'        => trim((string) ($contact['email'] ?? '')) ?: null,
                'phone'        => trim((string) ($contact['phone'] ?? '')) ?: null,
                'is_primary'   => isset($contact['is_primary']) ? 1 : 0,
            ];
        }

        return $contacts;
    }
}
