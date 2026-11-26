<?php

declare(strict_types=1);

namespace App\Models\Sales;

use App\Libraries\PluginSystem\PluginHookRegistry;
use App\Services\SalesBillingCycle;
use CodeIgniter\Model;

class SalesReceiptModel extends Model
{
    protected $table            = 'sales_receipts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'receipt_number', 'client_id', 'order_id', 'service_id', 'order_item_id',
        'customer_po_id', 'invoice_id', 'status', 'receipt_kind', 'is_initial',
        'period_label', 'period_start', 'period_end', 'due_date', 'collection_status',
        'subtotal', 'tax_amount', 'total_amount', 'currency', 'notes', 'plugin_ref',
        'issued_at', 'created_by', 'branch_id',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'client_id' => 'required|is_natural_no_zero',
        'order_id'  => 'required|is_natural_no_zero',
        'status'    => 'required|in_list[draft,issued,void]',
    ];

    public function nextNumber(): string
    {
        $year = date('Y');
        $row  = $this->db->query(
            'SELECT receipt_number FROM sales_receipts WHERE receipt_number LIKE ? ORDER BY id DESC LIMIT 1',
            ['REC-' . $year . '-%']
        )->getRow();
        $seq = 1;
        if ($row && preg_match('/(\d+)$/', (string) $row->receipt_number, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('REC-%s-%04d', $year, $seq);
    }

    public function findByOrder(int $orderId): ?object
    {
        return $this->where('order_id', $orderId)
            ->where('status !=', 'void')
            ->orderBy('id', 'DESC')
            ->first();
    }

    public function findByServicePeriod(int $serviceId, string $periodLabel): ?object
    {
        return $this->where('service_id', $serviceId)
            ->where('period_label', $periodLabel)
            ->where('status !=', 'void')
            ->first();
    }

    public function findInitialOneTime(int $orderId): ?object
    {
        return $this->where('order_id', $orderId)
            ->where('receipt_kind', 'one_time')
            ->where('status !=', 'void')
            ->first();
    }

    /**
     * Emite recibo con prefactura (billing_invoices en borrador) y partidas propias.
     *
     * @param array<string,mixed>       $header
     * @param list<array<string,mixed>> $lines
     */
    public function issue(array $header, array $lines): int|false
    {
        if ($lines === [] || empty($header['client_id']) || empty($header['order_id'])) {
            return false;
        }

        $subtotal = 0.0;
        foreach ($lines as $line) {
            $qty   = (int) ($line['quantity'] ?? 1);
            $price = (float) ($line['unit_price'] ?? 0);
            $subtotal += $qty * $price;
        }
        $tax   = round($subtotal * 0.16, 2);
        $total = round($subtotal + $tax, 2);

        $this->db->transBegin();

        $branchId = $header['branch_id'] ?? null;
        if (! $branchId) {
            $orderRow = $this->db->table('sales_orders')->select('branch_id')->where('id', (int) $header['order_id'])->get()->getRow();
            $branchId = $orderRow->branch_id ?? null;
        }

        $receiptId = $this->insert([
            'receipt_number'    => $header['receipt_number'] ?? $this->nextNumber(),
            'client_id'         => (int) $header['client_id'],
            'order_id'          => (int) $header['order_id'],
            'branch_id'         => $branchId ?: null,
            'service_id'        => $header['service_id'] ?? null,
            'order_item_id'     => $header['order_item_id'] ?? null,
            'customer_po_id'    => $header['customer_po_id'] ?? null,
            'status'            => 'issued',
            'receipt_kind'      => $header['receipt_kind'] ?? 'one_time',
            'is_initial'        => $header['is_initial'] ?? 1,
            'period_label'      => $header['period_label'] ?? null,
            'period_start'      => $header['period_start'] ?? null,
            'period_end'        => $header['period_end'] ?? null,
            'due_date'          => $header['due_date'] ?? date('Y-m-d'),
            'collection_status' => 'pending',
            'subtotal'          => round($subtotal, 2),
            'tax_amount'        => $tax,
            'total_amount'      => $total,
            'currency'          => $header['currency'] ?? 'MXN',
            'notes'             => $header['notes'] ?? null,
            'plugin_ref'        => $header['plugin_ref'] ?? null,
            'issued_at'         => date('Y-m-d H:i:s'),
            'created_by'        => $header['created_by'] ?? null,
        ]);

        if (! $receiptId) {
            $this->db->transRollback();

            return false;
        }

        $itemModel = new SalesReceiptItemModel();
        foreach ($lines as $line) {
            $qty   = (int) ($line['quantity'] ?? 1);
            $price = (float) ($line['unit_price'] ?? 0);
            $ok    = $itemModel->insert([
                'receipt_id'    => $receiptId,
                'order_item_id' => $line['order_item_id'] ?? null,
                'service_id'    => $line['service_id'] ?? ($header['service_id'] ?? null),
                'product_id'    => $line['product_id'] ?? null,
                'item_name'     => $line['item_name'] ?? 'Partida',
                'billing_cycle' => $line['billing_cycle'] ?? 'one_time',
                'quantity'      => $qty,
                'unit_price'    => $price,
                'line_total'    => round($qty * $price, 2),
                'plugin_ref'    => $line['plugin_ref'] ?? null,
            ]);
            if (! $ok) {
                $this->db->transRollback();

                return false;
            }
        }

        $invoiceNumber = 'FAC-BORRADOR-' . $receiptId;
        $this->db->table('billing_invoices')->insert([
            'order_id'       => (int) $header['order_id'],
            'client_id'      => (int) $header['client_id'],
            'receipt_id'     => $receiptId,
            'invoice_number' => $invoiceNumber,
            'status'         => 'draft',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        $invoiceId = (int) $this->db->insertID();
        $this->update($receiptId, ['invoice_id' => $invoiceId]);

        if ($this->db->transStatus() === false) {
            $this->db->transRollback();

            return false;
        }

        $this->db->transCommit();

        PluginHookRegistry::doAction('sales.receipt.issued', (int) $receiptId);

        return (int) $receiptId;
    }

    /**
     * Compatibilidad: emite el paquete inicial de la orden (one-time + primer periodo recurrente).
     */
    public function issueFromOrder(int $orderId, ?int $userId, ?string $notes = null): int|false
    {
        $result = (new \App\Services\SalesReceiptScheduler())->issueInitialForOrder($orderId, $userId, $notes);
        $first  = $result['one_time'] ?? ($result['renewals'][0] ?? 0);

        return $first ? (int) $first : false;
    }

    public function detail(int $id): ?object
    {
        $row = $this->select('sales_receipts.*, crm_clients.company_name, crm_clients.client_code, sales_orders.order_number')
            ->join('crm_clients', 'crm_clients.id = sales_receipts.client_id', 'left')
            ->join('sales_orders', 'sales_orders.id = sales_receipts.order_id', 'left')
            ->find($id);
        if (! $row) {
            return null;
        }
        $ownItems = (new SalesReceiptItemModel())->where('receipt_id', $id)->findAll();
        $row->items = $ownItems !== []
            ? $ownItems
            : (new SalesOrderItemModel())->where('order_id', (int) $row->order_id)->findAll();

        return $row;
    }

    /**
     * Cobranza aproximada del siguiente mes: recibos ya emitidos + servicios aún sin recibo.
     *
     * @return array{period: array{start:string,end:string}, receipts_due: float, services_pending: float, total_estimated: float, count: int, rows: list<array<string,mixed>>}
     */
    public function forecastNextMonth(?int $clientId = null): array
    {
        $start = date('Y-m-01', strtotime('first day of next month'));
        $end   = date('Y-m-t', strtotime('first day of next month'));

        return $this->forecastBetween($start, $end, $clientId);
    }

    /**
     * @return array{period: array{start:string,end:string}, receipts_due: float, services_pending: float, total_estimated: float, count: int, rows: list<array<string,mixed>>}
     */
    public function forecastBetween(string $start, string $end, ?int $clientId = null): array
    {
        $builder = $this->db->table('sales_receipts')
            ->select('sales_receipts.*, crm_clients.company_name')
            ->join('crm_clients', 'crm_clients.id = sales_receipts.client_id', 'left')
            ->where('sales_receipts.status !=', 'void')
            ->whereIn('sales_receipts.collection_status', ['pending', 'partial', 'overdue'])
            ->where('sales_receipts.due_date >=', $start)
            ->where('sales_receipts.due_date <=', $end)
            ->where('sales_receipts.deleted_at', null);
        if ($clientId) {
            $builder->where('sales_receipts.client_id', $clientId);
        }
        $receipts = $builder->get()->getResult();

        $rows          = [];
        $receiptsDue   = 0.0;
        foreach ($receipts as $row) {
            $amount = (float) $row->total_amount;
            $receiptsDue += $amount;
            $rows[] = [
                'source'     => 'receipt',
                'id'         => (int) $row->id,
                'label'      => $row->receipt_number,
                'client'     => $row->company_name ?? '',
                'client_id'  => (int) $row->client_id,
                'due_date'   => $row->due_date,
                'amount'     => $amount,
                'kind'       => $row->receipt_kind ?? 'one_time',
            ];
        }

        $svcBuilder = $this->db->table('customer_services')
            ->select('customer_services.*, crm_clients.company_name')
            ->join('crm_clients', 'crm_clients.id = customer_services.client_id', 'left')
            ->where('customer_services.status', 'active')
            ->where('customer_services.deleted_at', null)
            ->where('customer_services.next_due_date >=', $start)
            ->where('customer_services.next_due_date <=', $end)
            ->whereNotIn('customer_services.billing_cycle', ['one_time']);
        if ($clientId) {
            $svcBuilder->where('customer_services.client_id', $clientId);
        }
        $services = $svcBuilder->get()->getResult();

        $servicesPending = 0.0;
        foreach ($services as $svc) {
            $label = SalesBillingCycle::periodLabel((string) $svc->billing_cycle, (string) $svc->next_due_date);
            if ($this->findByServicePeriod((int) $svc->id, $label)) {
                continue;
            }
            $amount = round(((float) $svc->amount) * 1.16, 2);
            $servicesPending += $amount;
            $rows[] = [
                'source'     => 'service',
                'id'         => (int) $svc->id,
                'label'      => $svc->reference_name ?: ('Servicio #' . $svc->id),
                'client'     => $svc->company_name ?? '',
                'client_id'  => (int) $svc->client_id,
                'due_date'   => $svc->next_due_date,
                'amount'     => $amount,
                'kind'       => 'renewal',
            ];
        }

        return [
            'period'           => ['start' => $start, 'end' => $end],
            'receipts_due'     => round($receiptsDue, 2),
            'services_pending' => round($servicesPending, 2),
            'total_estimated'  => round($receiptsDue + $servicesPending, 2),
            'count'            => count($rows),
            'rows'             => $rows,
        ];
    }

    /**
     * Recibos abiertos de cobranza (pendiente / parcial / vencido).
     *
     * @return list<object>
     */
    public function openCollections(?int $clientId = null, int $limit = 50): array
    {
        $builder = $this->select('sales_receipts.*, crm_clients.company_name, sales_orders.order_number')
            ->join('crm_clients', 'crm_clients.id = sales_receipts.client_id', 'left')
            ->join('sales_orders', 'sales_orders.id = sales_receipts.order_id', 'left')
            ->whereIn('sales_receipts.collection_status', ['pending', 'partial', 'overdue'])
            ->where('sales_receipts.status !=', 'void')
            ->orderBy('sales_receipts.due_date', 'ASC');
        if ($clientId) {
            $builder->where('sales_receipts.client_id', $clientId);
        }

        return $builder->findAll($limit);
    }

    public function refreshOverdue(): int
    {
        if (! $this->db->fieldExists('collection_status', 'sales_receipts')) {
            return 0;
        }

        $this->db->table('sales_receipts')
            ->whereIn('collection_status', ['pending', 'partial'])
            ->where('due_date <', date('Y-m-d'))
            ->where('status !=', 'void')
            ->update(['collection_status' => 'overdue']);

        return $this->db->affectedRows();
    }
}
