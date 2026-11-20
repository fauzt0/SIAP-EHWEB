<?php

declare(strict_types=1);

namespace App\Models\Sales;

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
        'receipt_number', 'client_id', 'order_id', 'invoice_id', 'status',
        'subtotal', 'tax_amount', 'total_amount', 'currency', 'notes',
        'issued_at', 'created_by',
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
        return $this->where('order_id', $orderId)->where('status !=', 'void')->orderBy('id', 'DESC')->first();
    }

    /**
     * Emite recibo y deja factura interna en borrador (puente CFDI).
     */
    public function issueFromOrder(int $orderId, ?int $userId, ?string $notes = null): int|false
    {
        $orderModel = new SalesOrderModel();
        $order      = $orderModel->find($orderId);
        if (! $order || empty($order->client_id)) {
            return false;
        }

        $existing = $this->findByOrder($orderId);
        if ($existing) {
            return (int) $existing->id;
        }

        $this->db->transBegin();

        $receiptId = $this->insert([
            'receipt_number' => $this->nextNumber(),
            'client_id'      => (int) $order->client_id,
            'order_id'       => $orderId,
            'status'         => 'issued',
            'subtotal'       => $order->subtotal,
            'tax_amount'     => $order->tax_amount,
            'total_amount'   => $order->total_amount,
            'currency'       => $order->currency ?? 'MXN',
            'notes'          => $notes,
            'issued_at'      => date('Y-m-d H:i:s'),
            'created_by'     => $userId,
        ]);

        if (! $receiptId) {
            $this->db->transRollback();

            return false;
        }

        $invoiceNumber = 'FAC-BORRADOR-' . $receiptId;
        $this->db->table('billing_invoices')->insert([
            'order_id'       => $orderId,
            'client_id'      => (int) $order->client_id,
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

        return (int) $receiptId;
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
        $row->items = (new SalesOrderItemModel())->where('order_id', (int) $row->order_id)->findAll();

        return $row;
    }
}
