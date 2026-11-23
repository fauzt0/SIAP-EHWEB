<?php

declare(strict_types=1);

namespace App\Models\Sales;

use CodeIgniter\Model;

class BillingPaymentModel extends Model
{
    protected $table            = 'billing_payments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'order_id', 'client_id', 'receipt_id', 'invoice_id', 'payment_method',
        'transaction_id', 'amount_paid', 'currency', 'payment_date', 'status',
        'notes', 'created_by',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'order_id'       => 'required|is_natural_no_zero',
        'amount_paid'    => 'required|decimal|greater_than[0]',
        'payment_method' => 'required|in_list[paypal,stripe,mercadopago,transfer,cash,wallet,pos_terminal]',
        'status'         => 'required|in_list[pending,completed,failed,refunded]',
    ];

    public function paidTotal(int $orderId): float
    {
        $row = $this->selectSum('amount_paid', 'paid')
            ->where('order_id', $orderId)
            ->where('status', 'completed')
            ->first();

        return (float) ($row->paid ?? 0);
    }

    public function paidTotalForReceipt(int $receiptId): float
    {
        $row = $this->selectSum('amount_paid', 'paid')
            ->where('receipt_id', $receiptId)
            ->where('status', 'completed')
            ->first();

        return (float) ($row->paid ?? 0);
    }

    /**
     * Registra cobro y marca la orden pagada cuando el saldo queda en cero.
     */
    public function register(array $data): int|false
    {
        $order = (new SalesOrderModel())->find((int) $data['order_id']);
        if (! $order) {
            return false;
        }

        $data['client_id']    = $data['client_id'] ?? $order->client_id;
        $data['currency']     = $data['currency'] ?? ($order->currency ?? 'MXN');
        $data['status']       = $data['status'] ?? 'completed';
        $data['payment_date'] = $data['payment_date'] ?? date('Y-m-d H:i:s');

        $this->db->transBegin();
        $id = $this->insert($data);
        if (! $id) {
            $this->db->transRollback();

            return false;
        }

        $receiptId = (int) ($data['receipt_id'] ?? 0);
        if ($receiptId > 0) {
            $receipt = (new SalesReceiptModel())->find($receiptId);
            if ($receipt) {
                $paidR = $this->paidTotalForReceipt($receiptId);
                $dueR  = (float) $receipt->total_amount;
                $cStatus = 'pending';
                if ($paidR + 0.009 >= $dueR && $dueR > 0) {
                    $cStatus = 'paid';
                } elseif ($paidR > 0) {
                    $cStatus = 'partial';
                } elseif (! empty($receipt->due_date) && $receipt->due_date < date('Y-m-d')) {
                    $cStatus = 'overdue';
                }
                (new SalesReceiptModel())->update($receiptId, ['collection_status' => $cStatus]);
                \App\Libraries\PluginSystem\PluginHookRegistry::doAction('sales.receipt.paid', $receiptId, $paidR);
            }
        }

        $initialRows = (new SalesReceiptModel())
            ->select('id')
            ->where('order_id', (int) $order->id)
            ->where('is_initial', 1)
            ->where('status !=', 'void')
            ->findAll();
        $initialIds = array_map(static fn ($row) => (int) $row->id, $initialRows);
        $payQuery = (new self())
            ->selectSum('amount_paid', 'paid')
            ->where('order_id', (int) $order->id)
            ->where('status', 'completed');
        if ($initialIds !== []) {
            $payQuery->groupStart()
                ->where('receipt_id', null)
                ->orWhereIn('receipt_id', $initialIds)
                ->groupEnd();
        }
        $initialPaid = $payQuery->first();
        $paid   = (float) ($initialPaid->paid ?? 0);
        $due    = (float) $order->total_amount;
        $status = 'pending';
        if ($paid + 0.009 >= $due && $due > 0) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partial';
        }
        (new SalesOrderModel())->update((int) $order->id, ['status' => $status]);

        if ($this->db->transStatus() === false) {
            $this->db->transRollback();

            return false;
        }
        $this->db->transCommit();

        return (int) $id;
    }
}
