<?php

declare(strict_types=1);

namespace App\Models\Sales;

use App\Traits\DataTableTrait;
use CodeIgniter\Model;

class SalesOrderModel extends Model
{
    use DataTableTrait;

    protected $table            = 'sales_orders';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'order_number', 'user_id', 'client_id', 'quote_id', 'status', 'channel',
        'order_type', 'subtotal', 'tax_amount', 'discount_amount', 'total_amount',
        'currency', 'discount_code', 'notes', 'created_by', 'plugin_ref',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'client_id' => 'permit_empty|is_natural_no_zero',
        'status'    => 'required|in_list[draft,pending,partial,paid,cancelled,refunded,confirmed]',
    ];

    protected $datatableConfig = [
        'column_order'  => [
            'sales_orders.order_number',
            'crm_clients.company_name',
            'sales_orders.status',
            'sales_orders.channel',
            'sales_orders.total_amount',
            'sales_orders.created_at',
            null,
        ],
        'column_search' => [
            'sales_orders.order_number',
            'crm_clients.company_name',
            'sales_orders.status',
        ],
        'order' => ['sales_orders.id' => 'desc'],
    ];

    protected function _get_datatables_query($postData = [])
    {
        $this->builder()
            ->select('sales_orders.*, crm_clients.company_name, crm_clients.client_code')
            ->join('crm_clients', 'crm_clients.id = sales_orders.client_id', 'left');

        if (! empty($postData['filter_status'])) {
            $this->builder()->where('sales_orders.status', $postData['filter_status']);
        }
        if (! empty($postData['filter_client'])) {
            $this->builder()->where('sales_orders.client_id', (int) $postData['filter_client']);
        }

        $this->_apply_datatables_filters($postData);
    }

    public function nextNumber(): string
    {
        $year = date('Y');
        $row  = $this->db->query(
            'SELECT order_number FROM sales_orders WHERE order_number LIKE ? ORDER BY id DESC LIMIT 1',
            ['VTA-' . $year . '-%']
        )->getRow();
        $seq = 1;
        if ($row && preg_match('/(\d+)$/', (string) $row->order_number, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('VTA-%s-%04d', $year, $seq);
    }

    /**
     * @param list<array<string,mixed>> $items
     */
    public function createWithItems(array $order, array $items): int|false
    {
        $this->db->transStart();

        if (empty($order['order_number'])) {
            $order['order_number'] = $this->nextNumber();
        }
        $order['channel']    = $order['channel'] ?? 'backend';
        $order['order_type'] = $order['order_type'] ?? 'new';
        $order['currency']   = $order['currency'] ?? 'MXN';
        $order['status']     = $order['status'] ?? 'confirmed';

        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += ((float) ($item['unit_price'] ?? 0)) * ((int) ($item['quantity'] ?? 1));
        }
        $tax = round($subtotal * 0.16, 2);
        $order['subtotal']     = round($subtotal, 2);
        $order['tax_amount']   = $tax;
        $order['total_amount'] = round($subtotal + $tax, 2);

        $id = $this->insert($order);
        if (! $id) {
            $this->db->transRollback();

            return false;
        }

        $itemModel    = new SalesOrderItemModel();
        $serviceModel = new CustomerServiceModel();
        foreach ($items as $item) {
            $cycle = $item['billing_cycle'] ?? 'one_time';
            $item['order_id']      = $id;
            $item['billing_cycle'] = $cycle;
            if (empty($item['product_id'])) {
                $item['product_id'] = null;
            }
            $item['activation_status'] = 'pending';
            $itemId = $itemModel->insert($item);
            if (! $itemId) {
                $this->db->transRollback();

                return false;
            }

            if ($cycle !== 'one_time' && ! empty($order['client_id'])) {
                $start = $item['period_start'] ?? date('Y-m-d');
                $svcOk = $serviceModel->insert([
                    'client_id'      => $order['client_id'],
                    'user_id'        => $order['user_id'] ?? null,
                    'order_id'       => $id,
                    'product_id'     => $item['product_id'] ?? null,
                    'plan_id'        => $item['plan_id'] ?? null,
                    'reference_name' => $item['item_name'] ?? '',
                    'status'         => 'active',
                    'billing_cycle'  => $this->normalizeCycle((string) $cycle),
                    'amount'         => $item['unit_price'] ?? 0,
                    'next_due_date'      => $this->nextDue((string) $cycle, (string) $start),
                    'auto_renew'         => 0,
                    'activation_status'  => 'pending',
                ]);
                if (! $svcOk) {
                    $this->db->transRollback();

                    return false;
                }
            }
        }

        $this->db->transComplete();

        return $this->db->transStatus() ? (int) $id : false;
    }

    public function normalizeCycle(string $cycle): string
    {
        return match ($cycle) {
            'annual' => 'yearly',
            default  => $cycle,
        };
    }

    public function nextDue(string $cycle, string $from): string
    {
        $base = strtotime($from) ?: time();

        return match ($cycle) {
            'monthly'    => date('Y-m-d', strtotime('+1 month', $base)),
            'semiannual' => date('Y-m-d', strtotime('+6 months', $base)),
            'yearly', 'annual' => date('Y-m-d', strtotime('+1 year', $base)),
            default      => date('Y-m-d', $base),
        };
    }

    public function getWithItems(int $id): ?object
    {
        $order = $this->select('sales_orders.*, crm_clients.company_name, crm_clients.client_code')
            ->join('crm_clients', 'crm_clients.id = sales_orders.client_id', 'left')
            ->find($id);
        if (! $order) {
            return null;
        }
        $order->items    = (new SalesOrderItemModel())->where('order_id', $id)->findAll();
        $order->services = (new CustomerServiceModel())->where('order_id', $id)->findAll();

        return $order;
    }
}
