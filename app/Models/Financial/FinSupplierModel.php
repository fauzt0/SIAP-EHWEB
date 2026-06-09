<?php

namespace App\Models\Financial;

use CodeIgniter\Model;
use App\Traits\DataTableTrait;

/**
 * FinSupplierModel
 * 
 * Modelo para la gestión de proveedores (fin_suppliers).
 * Implementa DataTableTrait para Server-Side Rendering.
 * Las transacciones multi-tabla se encapsulan aquí, nunca en el controlador.
 */
class FinSupplierModel extends Model
{
    use DataTableTrait;

    protected $table            = 'fin_suppliers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'supplier_type',
        'legal_name',
        'commercial_name',
        'tax_id',
        'contact_person',
        'contact_email',
        'contact_phone',
        'website',
        'address',
        'bank_details',
        'notes',
        'status',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'supplier_type' => [
            'label' => 'Tipo de Proveedor',
            'rules' => 'required|in_list[infrastructure,hardware,services,administrative]',
        ],
        'commercial_name' => [
            'label' => 'Nombre Comercial',
            'rules' => 'required|max_length[255]',
        ],
        'legal_name' => [
            'label' => 'Razón Social',
            'rules' => 'permit_empty|max_length[255]',
        ],
        'tax_id' => [
            'label' => 'RFC/Identificación Fiscal',
            'rules' => 'permit_empty|max_length[50]',
        ],
        'contact_email' => [
            'label' => 'Correo de Contacto',
            'rules' => 'permit_empty|valid_email|max_length[255]',
        ],
        'contact_phone' => [
            'label' => 'Teléfono de Contacto',
            'rules' => 'permit_empty|max_length[50]',
        ],
        'status' => [
            'label' => 'Estatus',
            'rules' => 'permit_empty|in_list[0,1]',
        ],
    ];

    protected $validationMessages = [
        'supplier_type' => [
            'required' => 'El tipo de proveedor es obligatorio.',
            'in_list'  => 'El tipo de proveedor seleccionado no es válido.',
        ],
        'commercial_name' => [
            'required'   => 'El nombre comercial es obligatorio.',
            'max_length' => 'El nombre comercial no puede exceder los 255 caracteres.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // DataTable configuration
    protected $datatableConfig = [
        'column_order'  => [
            null,
            'fin_suppliers.supplier_type',
            'fin_suppliers.commercial_name',
            'fin_suppliers.legal_name',
            'fin_suppliers.tax_id',
            'fin_suppliers.contact_email',
            'fin_suppliers.contact_phone',
            'fin_suppliers.status',
            null,
        ],
        'column_search' => [
            'fin_suppliers.commercial_name',
            'fin_suppliers.legal_name',
            'fin_suppliers.tax_id',
            'fin_suppliers.contact_email',
            'fin_suppliers.contact_phone',
        ],
        'order' => ['fin_suppliers.id' => 'desc'],
    ];

    /**
     * _get_datatables_query
     * 
     * Construye la consulta JOIN para el listado de proveedores con DataTable.
     * Incluye conteo de contactos y total de órdenes de compra asociadas.
     */
    protected function _get_datatables_query($postData = [])
    {
        $this->builder()
            ->select([
                'fin_suppliers.*',
                '(SELECT COUNT(*) FROM fin_supplier_contacts WHERE fin_supplier_contacts.fin_supplier_id = fin_suppliers.id AND fin_supplier_contacts.deleted_at IS NULL) AS contacts_count',
                '(SELECT COUNT(*) FROM fin_purchase_orders WHERE fin_purchase_orders.fin_supplier_id = fin_suppliers.id AND fin_purchase_orders.deleted_at IS NULL) AS orders_count',
            ]);

        $this->_applyStatusFilter($postData);

        // Filtro por tipo de proveedor
        if (!empty($postData['filter_supplier_type'])) {
            $this->builder()->where(
                'fin_suppliers.supplier_type',
                $postData['filter_supplier_type']
            );
        }

        $this->_apply_datatables_filters($postData);
    }

    /**
     * _applyStatusFilter
     *
     * Aplica el alcance del listado según filter_status:
     * - ''        → solo no eliminados
     * - '1' / '0' → activos/inactivos (sin eliminados)
     * - 'deleted' → solo papelera (soft delete)
     */
    protected function _applyStatusFilter(array $postData = []): void
    {
        $filterStatus = $postData['filter_status'] ?? '';

        if ($filterStatus === 'deleted') {
            $this->builder()->where('fin_suppliers.deleted_at IS NOT NULL', null, false);
            return;
        }

        $this->builder()->where('fin_suppliers.deleted_at IS NULL', null, false);

        if ($filterStatus !== '') {
            $this->builder()->where('fin_suppliers.status', (int) $filterStatus);
        }
    }

    /**
     * count_all
     * 
     * Retorna el total de proveedores sin soft-delete para DataTables.
     */
    public function count_all($where = [], array $postData = [])
    {
        $builder = $this->db->table($this->table);

        $filterStatus = $postData['filter_status'] ?? '';
        if ($filterStatus === 'deleted') {
            $builder->where('deleted_at IS NOT NULL', null, false);
        } else {
            $builder->where('deleted_at IS NULL', null, false);
            if ($filterStatus !== '') {
                $builder->where('status', (int) $filterStatus);
            }
        }

        if (!empty($where)) {
            $builder->where($where);
        }

        return $builder->countAllResults();
    }

    /**
     * createSupplierWithContacts
     * 
     * Crea un proveedor junto con sus contactos en una sola transacción.
     * Cumple el estándar MVC: el controlador solo llama este método.
     *
     * @param array $supplierData Datos del proveedor (fin_suppliers)
     * @param array $contacts     Array de contactos adicionales
     * @return int|false ID del proveedor creado, o false si falla
     */
    public function createSupplierWithContacts(array $supplierData, array $contacts = []): int|false
    {
        $this->db->transStart();

        $newId = $this->insert($supplierData);

        if ($newId && !empty($contacts)) {
            $contactModel = new FinSupplierContactModel();
            foreach ($contacts as $contact) {
                $contact['fin_supplier_id'] = $newId;
                $contactModel->insert($contact);
            }
        }

        $this->db->transComplete();

        return $this->db->transStatus() ? $newId : false;
    }

    /**
     * updateSupplierWithContacts
     * 
     * Actualiza un proveedor y reemplaza sus contactos en una sola transacción.
     *
     * @param int   $id            ID del proveedor
     * @param array $supplierData  Datos actualizados del proveedor
     * @param array $contacts      Nuevos contactos (reemplaza existentes)
     * @return bool
     */
    public function updateSupplierWithContacts(int $id, array $supplierData, array $contacts = []): bool
    {
        $this->db->transStart();

        $this->update($id, $supplierData);

        // Reemplazar contactos: soft-delete los anteriores, insertar los nuevos
        $contactModel = new FinSupplierContactModel();
        $contactModel->where('fin_supplier_id', $id)->delete();

        if (!empty($contacts)) {
            foreach ($contacts as $contact) {
                $contact['fin_supplier_id'] = $id;
                $contactModel->insert($contact);
            }
        }

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * getActiveSuppliers
     * 
     * Obtiene la lista de proveedores activos para selects/dropdowns.
     *
     * @return array
     */
    public function getActiveSuppliers(): array
    {
        return $this->where('status', 1)
            ->orderBy('commercial_name', 'ASC')
            ->findAll();
    }

    /**
     * getSupplierTypes
     * 
     * Retorna los tipos de proveedor disponibles para formularios.
     *
     * @return array
     */
    public function getSupplierTypes(): array
    {
        return [
            'infrastructure'  => 'Infraestructura',
            'hardware'        => 'Hardware',
            'services'        => 'Servicios',
            'administrative'  => 'Administrativo',
        ];
    }

    /**
     * linkProduct
     *
     * Inserts a row into catalog_product_suppliers linking a resellable product
     * to this supplier. Soft-delete aware (restores if a deleted row exists).
     *
     * @param array $data Keys: catalog_product_id, fin_supplier_id, purchase_cost,
     *                         purchase_currency, supplier_due_date, is_auto_renew
     * @return int|false Inserted/restored row ID, or false on failure
     */
    public function linkProduct(array $data): int|false
    {
        // Upsert: restore if a soft-deleted row already exists
        $existing = $this->db->table('catalog_product_suppliers')
            ->where('catalog_product_id', $data['catalog_product_id'])
            ->where('fin_supplier_id', $data['fin_supplier_id'])
            ->get()->getRow();

        if ($existing) {
            $this->db->table('catalog_product_suppliers')
                ->where('id', $existing->id)
                ->update(array_merge($data, ['deleted_at' => null, 'updated_at' => date('Y-m-d H:i:s')]));
            return $existing->id;
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->table('catalog_product_suppliers')->insert($data);
        return $this->db->insertID() ?: false;
    }

    /**
     * unlinkProduct
     *
     * Soft-deletes a catalog_product_suppliers row, ensuring it belongs to the
     * given supplier to prevent cross-tenant deletions.
     *
     * @param int $linkId     Row ID in catalog_product_suppliers
     * @param int $supplierId Expected fin_supplier_id (ownership guard)
     * @return bool
     */
    public function unlinkProduct(int $linkId, int $supplierId): bool
    {
        $row = $this->db->table('catalog_product_suppliers')
            ->where('id', $linkId)
            ->where('fin_supplier_id', $supplierId)
            ->where('deleted_at IS NULL')
            ->get()->getRow();

        if (!$row) {
            return false;
        }

        $this->db->table('catalog_product_suppliers')
            ->where('id', $linkId)
            ->update(['deleted_at' => date('Y-m-d H:i:s')]);

        return $this->db->affectedRows() > 0;
    }

    /**
     * getLinkedProducts
     *
     * Returns all active catalog_product_suppliers rows for the given supplier,
     * joined with catalog_products for display.
     *
     * @param int $supplierId
     * @return array
     */
    public function getLinkedProducts(int $supplierId): array
    {
        return $this->db->table('catalog_product_suppliers cps')
            ->select('cps.*, cp.commercial_name AS product_name, cp.sku AS product_sku, cp.product_type')
            ->join('catalog_products cp', 'cp.id = cps.catalog_product_id', 'left')
            ->where('cps.fin_supplier_id', $supplierId)
            ->where('cps.deleted_at IS NULL')
            ->orderBy('cp.commercial_name', 'ASC')
            ->get()->getResultObject();
    }

    /**
     * getStats
     *
     * Estadísticas para el dashboard del módulo de proveedores.
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total'           => $this->count_all(),
            'active'          => $this->where('status', 1)->countAllResults(),
            'inactive'        => $this->where('status', 0)->countAllResults(),
            'with_products'   => $this->db->table('catalog_product_suppliers')
                ->where('deleted_at IS NULL')
                ->countAllResults(),
        ];
    }

    /**
     * softDelete
     *
     * Marca un proveedor como eliminado (soft delete).
     *
     * @param int $id ID del proveedor
     * @return bool
     */
    public function softDelete(int $id): bool
    {
        if (!$this->find($id)) {
            return false;
        }

        return (bool) $this->delete($id);
    }

    /**
     * restore
     *
     * Restaura un proveedor previamente eliminado con soft delete.
     * Usa el builder porque deleted_at no está en allowedFields.
     *
     * @param int $id ID del proveedor
     * @return bool
     */
    public function restore(int $id): bool
    {
        $supplier = $this->withDeleted()->find($id);
        if (!$supplier || empty($supplier->deleted_at)) {
            return false;
        }

        $this->builder()->where('id', $id)->update(['deleted_at' => null]);

        return $this->db->affectedRows() > 0;
    }

    /**
     * toggleStatus
     *
     * Alterna el estatus activo/inactivo de un proveedor.
     *
     * @param int $id ID del proveedor
     * @return int|false Nuevo estatus (0|1) o false si falla
     */
    public function toggleStatus(int $id): int|false
    {
        $supplier = $this->find($id);
        if (!$supplier) {
            return false;
        }

        $newStatus = $supplier->status ? 0 : 1;

        if (!$this->update($id, ['status' => $newStatus])) {
            return false;
        }

        return $newStatus;
    }
}
