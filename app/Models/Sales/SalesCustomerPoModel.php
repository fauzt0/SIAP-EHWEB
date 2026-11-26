<?php

declare(strict_types=1);

namespace App\Models\Sales;

use App\Libraries\PluginSystem\PluginHookRegistry;
use CodeIgniter\Model;

class SalesCustomerPoModel extends Model
{
    protected $table            = 'sales_customer_pos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'po_number', 'order_id', 'client_id', 'status', 'subtotal', 'tax_amount',
        'total_amount', 'currency', 'notes', 'plugin_ref', 'created_by',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function nextNumber(): string
    {
        $year = date('Y');
        $row  = $this->db->query(
            'SELECT po_number FROM sales_customer_pos WHERE po_number LIKE ? ORDER BY id DESC LIMIT 1',
            ['OCC-' . $year . '-%']
        )->getRow();
        $seq = 1;
        if ($row && preg_match('/(\d+)$/', (string) $row->po_number, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('OCC-%s-%04d', $year, $seq);
    }

    /**
     * Genera la OC del cliente. Si $itemIds no es vacío, solo esas partidas (separación).
     *
     * @param list<int>|null $itemIds
     */
    public function generateFromOrder(int $orderId, ?array $itemIds = null, ?int $userId = null, ?string $notes = null): int|false
    {
        $order = (new SalesOrderModel())->find($orderId);
        if (! $order || empty($order->client_id)) {
            return false;
        }

        $itemModel = new SalesOrderItemModel();
        $items     = $itemModel->where('order_id', $orderId)->findAll();
        if ($itemIds !== null && $itemIds !== []) {
            $wanted = array_map('intval', $itemIds);
            $items  = array_values(array_filter($items, static fn ($row) => in_array((int) $row->id, $wanted, true)));
        }
        if ($items === []) {
            return false;
        }

        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += ((float) $item->unit_price) * ((int) $item->quantity);
        }
        $tax = round($subtotal * 0.16, 2);

        $this->db->transBegin();
        $poId = $this->insert([
            'po_number'    => $this->nextNumber(),
            'order_id'     => $orderId,
            'client_id'    => (int) $order->client_id,
            'status'       => 'issued',
            'subtotal'     => round($subtotal, 2),
            'tax_amount'   => $tax,
            'total_amount' => round($subtotal + $tax, 2),
            'currency'     => $order->currency ?? 'MXN',
            'notes'        => $notes,
            'created_by'   => $userId,
        ]);
        if (! $poId) {
            $this->db->transRollback();

            return false;
        }

        $lineModel = new SalesCustomerPoItemModel();
        foreach ($items as $item) {
            $ok = $lineModel->insert([
                'customer_po_id' => $poId,
                'order_item_id'  => (int) $item->id,
                'line_code'      => $item->line_code ?? null,
                'item_name'      => $item->item_name,
                'quantity'       => $item->quantity,
                'unit_price'     => $item->unit_price,
                'billing_cycle'  => $item->billing_cycle ?? 'one_time',
            ]);
            if (! $ok) {
                $this->db->transRollback();

                return false;
            }
        }

        if ($this->db->transStatus() === false) {
            $this->db->transRollback();

            return false;
        }
        $this->db->transCommit();

        PluginHookRegistry::doAction('sales.customer_po.created', (int) $poId, $orderId);

        return (int) $poId;
    }

    public function getWithItems(int $id): ?object
    {
        $po = $this->select('sales_customer_pos.*, crm_clients.company_name, crm_clients.client_code, sales_orders.order_number')
            ->join('crm_clients', 'crm_clients.id = sales_customer_pos.client_id', 'left')
            ->join('sales_orders', 'sales_orders.id = sales_customer_pos.order_id', 'left')
            ->find($id);
        if (! $po) {
            return null;
        }
        $po->items = (new SalesCustomerPoItemModel())->where('customer_po_id', $id)->findAll();

        return $po;
    }
}
