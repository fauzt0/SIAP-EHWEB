<?php

namespace App\Models\Financial;

use CodeIgniter\Model;
use App\Traits\DataTableTrait;

/**
 * FinPurchaseOrderModel
 * 
 * Modelo para la gestión de órdenes de compra (fin_purchase_orders).
 * Implementa DataTableTrait para listados Server-Side.
 * Las transacciones multi-tabla se encapsulan aquí, nunca en el controlador.
 */
class FinPurchaseOrderModel extends Model
{
    use DataTableTrait;

    protected $table            = 'fin_purchase_orders';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'fin_supplier_id',
        'org_branch_id',
        'po_number',
        'status',
        'total_amount',
        'currency',
        'issue_date',
        'delivery_date',
        'notes',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'fin_supplier_id' => [
            'label' => 'Proveedor',
            'rules' => 'required|is_natural_no_zero|is_not_unique[fin_suppliers.id]',
        ],
        'org_branch_id' => [
            'label' => 'Sucursal',
            'rules' => 'required|is_natural_no_zero|is_not_unique[org_branches.id]',
        ],
        'po_number' => [
            'label' => 'Número de Orden',
            'rules' => 'required|max_length[50]|is_unique[fin_purchase_orders.po_number,id,{id}]',
        ],
        'status' => [
            'label' => 'Estatus',
            'rules' => 'required|in_list[draft,sent,approved,completed,cancelled]',
        ],
        'total_amount' => [
            'label' => 'Monto Total',
            'rules' => 'permit_empty|decimal|greater_than_equal_to[0]',
        ],
        'currency' => [
            'label' => 'Moneda',
            'rules' => 'permit_empty|max_length[3]',
        ],
        'issue_date' => [
            'label' => 'Fecha de Emisión',
            'rules' => 'permit_empty|valid_date',
        ],
        'delivery_date' => [
            'label' => 'Fecha de Entrega',
            'rules' => 'permit_empty|valid_date',
        ],
    ];

    protected $validationMessages = [
        'fin_supplier_id' => [
            'required'      => 'El proveedor es obligatorio.',
            'is_natural_no_zero' => 'El proveedor no es válido.',
            'is_not_unique' => 'El proveedor especificado no existe.',
        ],
        'po_number' => [
            'required'   => 'El número de orden es obligatorio.',
            'is_unique'  => 'Este número de orden ya está registrado.',
            'max_length' => 'El número de orden no puede exceder los 50 caracteres.',
        ],
        'status' => [
            'required' => 'El estatus es obligatorio.',
            'in_list'  => 'El estatus seleccionado no es válido.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Estados de orden de compra permitidos para transiciones
    private const VALID_TRANSITIONS = [
        'draft'     => ['sent', 'cancelled'],
        'sent'      => ['approved', 'cancelled'],
        'approved'  => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    // DataTable configuration
    protected $datatableConfig = [
        'column_order'  => [
            null,
            'fin_purchase_orders.po_number',
            'fin_suppliers.commercial_name',
            'fin_purchase_orders.status',
            'fin_purchase_orders.total_amount',
            'fin_purchase_orders.currency',
            'fin_purchase_orders.issue_date',
            'fin_purchase_orders.delivery_date',
            null,
        ],
        'column_search' => [
            'fin_purchase_orders.po_number',
            'fin_suppliers.commercial_name',
            'fin_purchase_orders.notes',
        ],
        'order' => ['fin_purchase_orders.id' => 'desc'],
    ];

    /**
     * _get_datatables_query
     * 
     * Construye la consulta JOIN para el listado de órdenes de compra con DataTable.
     */
    protected function _get_datatables_query($postData = [])
    {
        $this->builder()
            ->select([
                'fin_purchase_orders.*',
                'fin_suppliers.commercial_name AS supplier_name',
                'org_branches.name AS branch_name',
                'org_branches.commercial_name AS branch_commercial_name',
                'org_branches.branch_code AS branch_code',
            ])
            ->join('fin_suppliers', 'fin_suppliers.id = fin_purchase_orders.fin_supplier_id', 'left')
            ->join('org_branches', 'org_branches.id = fin_purchase_orders.org_branch_id', 'left')
            ->where('fin_purchase_orders.deleted_at IS NULL');

        // Filtro por estatus de la orden
        if (!empty($postData['filter_status'])) {
            $this->builder()->where(
                'fin_purchase_orders.status',
                $postData['filter_status']
            );
        }

        // Filtro por proveedor
        if (!empty($postData['filter_supplier_id'])) {
            $this->builder()->where(
                'fin_purchase_orders.fin_supplier_id',
                (int) $postData['filter_supplier_id']
            );
        }

        $this->_apply_datatables_filters($postData);
    }

    /**
     * count_all
     * 
     * Retorna el total de órdenes sin soft-delete para DataTables.
     */
    public function count_all($where = [])
    {
        $builder = $this->db->table($this->table);
        $builder->where('deleted_at IS NULL');
        if (!empty($where)) {
            $builder->where($where);
        }
        return $builder->countAllResults();
    }

    /**
     * generatePoNumber
     * 
     * Genera un número de orden de compra secuencial.
     * Formato: PO-YYYYMMDD-XXXX
     *
     * @return string
     */
    public function generatePoNumber(): string
    {
        $date = date('Ymd');
        $lastPo = $this->like('po_number', "PO-{$date}-", 'after')
            ->orderBy('id', 'DESC')
            ->first();

        if ($lastPo) {
            $parts = explode('-', $lastPo->po_number);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return 'PO-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * createPurchaseOrderWithItems
     * 
     * Crea una orden de compra con sus partidas en una sola transacción.
     * Calcula automáticamente el total_amount basado en las partidas.
     *
     * @param array $poData Datos de la orden de compra
     * @param array $items  Array de partidas (item_data)
     * @return int|false ID de la orden creada, o false si falla
     */
    public function createPurchaseOrderWithItems(array $poData, array $items = []): int|false
    {
        $this->db->transStart();

        // Generar número de orden si no se proporcionó
        if (empty($poData['po_number'])) {
            $poData['po_number'] = $this->generatePoNumber();
        }

        // Calcular total si hay partidas
        if (!empty($items)) {
            $total = 0;
            foreach ($items as $item) {
                $total += ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
            }
            $poData['total_amount'] = $total;
        }

        $newId = $this->insert($poData);

        if ($newId && !empty($items)) {
            $itemModel = new FinPurchaseOrderItemModel();
            foreach ($items as $item) {
                $item['fin_purchase_order_id'] = $newId;
                $item['total_price'] = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
                $itemModel->insert($item);
            }
        }

        $this->db->transComplete();

        return $this->db->transStatus() ? $newId : false;
    }

    /**
     * updatePurchaseOrderWithItems
     * 
     * Actualiza una orden de compra y reemplaza sus partidas en una sola transacción.
     *
     * @param int   $id     ID de la orden de compra
     * @param array $poData Datos actualizados
     * @param array $items  Nuevas partidas (reemplaza existentes)
     * @return bool
     */
    public function updatePurchaseOrderWithItems(int $id, array $poData, array $items = []): bool
    {
        $this->db->transStart();

        // Verificar que la orden pueda modificarse
        $currentOrder = $this->find($id);
        if ($currentOrder && !in_array($currentOrder->status, ['draft', 'sent'])) {
            $this->db->transRollback();
            return false;
        }

        $this->update($id, $poData);

        // Reemplazar partidas: eliminar las anteriores, insertar las nuevas
        $itemModel = new FinPurchaseOrderItemModel();
        $itemModel->where('fin_purchase_order_id', $id)->delete();

        if (!empty($items)) {
            $total = 0;
            foreach ($items as $item) {
                $item['fin_purchase_order_id'] = $id;
                $item['total_price'] = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
                $total += $item['total_price'];
                $itemModel->insert($item);
            }
            // Actualizar el total de la orden
            $this->update($id, ['total_amount' => $total]);
        }

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * updateStatus
     * 
     * Actualiza el estatus de una orden de compra validando la transición.
     *
     * @param int    $id        ID de la orden
     * @param string $newStatus Nuevo estatus
     * @return bool
     */
    public function updateStatus(int $id, string $newStatus): bool
    {
        $order = $this->find($id);
        if (!$order) {
            return false;
        }

        $allowedTransitions = self::VALID_TRANSITIONS[$order->status] ?? [];
        if (!in_array($newStatus, $allowedTransitions)) {
            return false;
        }

        return $this->update($id, ['status' => $newStatus]);
    }

    /**
     * getOrderWithItems
     * 
     * Obtiene una orden de compra con todas sus partidas.
     *
     * @param int $id ID de la orden
     * @return object|null
     */
    public function getOrderWithItems(int $id): ?object
    {
        $order = $this->select([
                'fin_purchase_orders.*',
                'fin_suppliers.commercial_name AS supplier_name',
                'org_branches.name AS branch_name',
                'org_branches.commercial_name AS branch_commercial_name',
                'org_branches.legal_name AS branch_legal_name',
                'org_branches.tax_id AS branch_tax_id',
                'org_branches.address AS branch_address',
                'org_branches.phone AS branch_phone',
                'org_branches.email AS branch_email',
            ])
            ->join('fin_suppliers', 'fin_suppliers.id = fin_purchase_orders.fin_supplier_id', 'left')
            ->join('org_branches', 'org_branches.id = fin_purchase_orders.org_branch_id', 'left')
            ->find($id);

        if (!$order) {
            return null;
        }

        $itemModel = new FinPurchaseOrderItemModel();
        $order->items = $itemModel->getItemsByOrder($id);

        return $order;
    }

    /**
     * getOrdersBySupplier
     * 
     * Obtiene las órdenes de compra de un proveedor específico.
     *
     * @param int $supplierId ID del proveedor
     * @return array
     */
    public function getOrdersBySupplier(int $supplierId): array
    {
        return $this->where('fin_supplier_id', $supplierId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * softDelete
     *
     * Elimina (soft) una orden de compra si su estatus lo permite.
     *
     * @param int $id ID de la orden
     * @return bool
     */
    public function softDelete(int $id): bool
    {
        $order = $this->find($id);
        if (!$order) {
            return false;
        }

        if (in_array($order->status, ['completed', 'approved'], true)) {
            return false;
        }

        return (bool) $this->delete($id);
    }

    /**
     * restore
     *
     * Restaura una orden de compra previamente eliminada con soft delete.
     *
     * @param int $id ID de la orden
     * @return bool
     */
    public function restore(int $id): bool
    {
        $order = $this->withDeleted()->find($id);
        if (!$order || empty($order->deleted_at)) {
            return false;
        }

        $this->builder()->where('id', $id)->update(['deleted_at' => null]);

        return $this->db->affectedRows() > 0;
    }
}
