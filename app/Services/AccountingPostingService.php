<?php

namespace App\Services;

use App\Libraries\PluginSystem\PluginHookRegistry;
use App\Models\Financial\FinAccountModel;
use App\Models\Financial\FinExpenseCategoryModel;
use App\Models\Financial\FinExpenseModel;
use App\Models\Financial\FinLedgerEntryModel;
use App\Models\Financial\FinPaymentFolioModel;
use App\Models\Financial\FinPurchaseOrderModel;
use App\Models\Financial\FinSupplierInvoiceModel;
use App\Models\HR\HrPayrollPeriodModel;

/**
 * Asiento único de tesorería: actualiza saldo de cuenta y escribe el mayor.
 * Idempotente por (reference_type, reference_id).
 */
class AccountingPostingService
{
    private FinAccountModel $accounts;
    private FinLedgerEntryModel $ledger;
    private FinExpenseModel $expenses;
    private FinExpenseCategoryModel $categories;

    public function __construct()
    {
        $this->accounts   = new FinAccountModel();
        $this->ledger     = new FinLedgerEntryModel();
        $this->expenses   = new FinExpenseModel();
        $this->categories = new FinExpenseCategoryModel();
    }

    /**
     * @return array{ok:bool,id?:int,skipped?:bool,message?:string}
     */
    public function post(array $payload): array
    {
        $refType = (string) ($payload['reference_type'] ?? '');
        $refId   = (int) ($payload['reference_id'] ?? 0);
        $amount  = round((float) ($payload['amount'] ?? 0), 2);
        $type    = (($payload['type'] ?? 'credit') === 'debit') ? 'debit' : 'credit';

        if ($refType === '' || $refId < 1 || $amount <= 0) {
            return ['ok' => false, 'message' => 'Asiento incompleto.'];
        }
        if ($this->ledger->alreadyPosted($refType, $refId)) {
            return ['ok' => true, 'skipped' => true];
        }

        $accountId = (int) ($payload['account_id'] ?? 0);
        if ($accountId < 1) {
            $accountId = (int) ($this->accounts->defaultAccountId() ?? 0);
        }
        $account = $accountId > 0 ? $this->accounts->find($accountId) : null;
        if (! $account) {
            return ['ok' => false, 'message' => 'No hay cuenta de tesorería activa.'];
        }

        $balance = (float) $account->balance;
        $balance = $type === 'debit' ? $balance + $amount : $balance - $amount;

        $this->accounts->db->transStart();
        $this->accounts->update((int) $account->id, ['balance' => $balance]);
        $id = $this->ledger->insert([
            'account_id'      => (int) $account->id,
            'reference_type'  => $refType,
            'reference_id'    => $refId,
            'type'            => $type,
            'amount'          => $amount,
            'balance_after'   => $balance,
            'notes'           => $payload['notes'] ?? null,
            'entry_date'      => $payload['entry_date'] ?? date('Y-m-d H:i:s'),
            'company_id'      => $payload['company_id'] ?? null,
            'branch_id'       => $payload['branch_id'] ?? null,
        ], true);
        $this->accounts->db->transComplete();

        if (! $this->accounts->db->transStatus() || ! $id) {
            return ['ok' => false, 'message' => 'No se pudo registrar el asiento.'];
        }

        PluginHookRegistry::doAction('accounting.ledger.posted', (int) $id, $payload);

        return ['ok' => true, 'id' => (int) $id];
    }

    public function fromExpensePayment(int $expenseId, ?int $accountId = null): array
    {
        $expense = $this->expenses->find($expenseId);
        if (! $expense) {
            return ['ok' => false, 'message' => 'Gasto no encontrado.'];
        }
        $paid = (float) ($expense->paid_amount ?? 0);
        if ($paid <= 0) {
            return ['ok' => false, 'message' => 'El gasto no tiene monto pagado.'];
        }

        return $this->post([
            'account_id'     => $accountId,
            'reference_type' => 'expense',
            'reference_id'   => $expenseId,
            'type'           => 'credit',
            'amount'         => $paid,
            'notes'          => 'Pago de gasto #' . $expenseId . ' ' . ($expense->period_label ?? ''),
            'branch_id'      => $expense->org_branch_id ?? null,
        ]);
    }

    public function fromPayrollPeriod(int $periodId, ?int $accountId = null): array
    {
        $period = (new HrPayrollPeriodModel())->find($periodId);
        if (! $period) {
            return ['ok' => false, 'message' => 'Periodo de nómina no encontrado.'];
        }
        $amount = (float) ($period['total_net'] ?? 0);
        if ($amount <= 0) {
            return ['ok' => true, 'skipped' => true, 'message' => 'Nómina sin neto a pagar.'];
        }

        $categoryId = $this->categories->idByName('Nómina');
        $existing   = $this->expenses
            ->where('payroll_period_id', $periodId)
            ->first();
        if (! $existing && $categoryId) {
            $this->expenses->insert([
                'category_id'       => $categoryId,
                'total_amount'      => $amount,
                'paid_amount'       => $amount,
                'due_date'          => date('Y-m-d'),
                'status'            => 'paid',
                'currency'          => 'MXN',
                'period_label'      => mb_substr((string) ($period['name'] ?? $periodId), 0, 20),
                'notes'             => 'Nómina periodo #' . $periodId,
                'source_type'       => 'payroll',
                'source_id'         => $periodId,
                'payroll_period_id' => $periodId,
            ]);
        }

        return $this->post([
            'account_id'     => $accountId,
            'reference_type' => 'payroll_period',
            'reference_id'   => $periodId,
            'type'           => 'credit',
            'amount'         => $amount,
            'notes'          => 'Pago de nómina periodo #' . $periodId,
        ]);
    }

    public function fromFundRequest(int $folioId): array
    {
        $folios = new FinPaymentFolioModel();
        $folio  = $folios->find($folioId);
        if (! $folio) {
            return ['ok' => false, 'message' => 'Folio no encontrado.'];
        }

        $categoryId = $this->categories->idByName('Solicitud de fondos');
        $expenseId  = (int) ($folio->expense_id ?? 0);
        if ($expenseId < 1 && $categoryId) {
            $expenseId = (int) $this->expenses->insert([
                'category_id'  => $categoryId,
                'total_amount' => (float) $folio->amount_paid,
                'paid_amount'  => (float) $folio->amount_paid,
                'due_date'     => date('Y-m-d'),
                'status'       => 'paid',
                'currency'     => 'MXN',
                'notes'        => (string) $folio->concept,
                'source_type'  => 'fund_request',
                'source_id'    => $folioId,
            ], true);
            $folios->update($folioId, ['expense_id' => $expenseId]);
        }

        return $this->post([
            'account_id'     => (int) $folio->account_id,
            'reference_type' => 'fund_request',
            'reference_id'   => $folioId,
            'type'           => 'credit',
            'amount'         => (float) $folio->amount_paid,
            'notes'          => $folio->folio_number . ' — ' . $folio->concept,
            'branch_id'      => $folio->branch_id ?? null,
            'company_id'     => $folio->company_id ?? null,
        ]);
    }

    public function fromPurchaseOrder(int $poId, ?int $accountId = null): array
    {
        $po = (new FinPurchaseOrderModel())->find($poId);
        if (! $po) {
            return ['ok' => false, 'message' => 'Orden de compra no encontrada.'];
        }

        $invoices = new FinSupplierInvoiceModel();
        $invoice  = $invoices->findByPurchaseOrder($poId);
        if ($invoice) {
            return ['ok' => true, 'skipped' => true, 'id' => (int) $invoice->id];
        }

        $total = (float) $po->total_amount;
        $id    = $invoices->insert([
            'folio'             => $invoices->nextFolio(),
            'supplier_id'       => (int) $po->fin_supplier_id,
            'purchase_order_id' => $poId,
            'invoice_number'    => $po->po_number,
            'invoice_date'      => date('Y-m-d'),
            'due_date'          => $po->delivery_date ?? date('Y-m-d'),
            'subtotal'          => $total,
            'tax_amount'        => 0,
            'total'             => $total,
            'paid_amount'       => 0,
            'status'            => 'open',
            'branch_id'         => $po->org_branch_id ?? null,
            'account_id'        => $accountId,
            'notes'             => 'Generada desde OC ' . $po->po_number,
            'created_by'        => auth()->id(),
        ], true);

        if (! $id) {
            return ['ok' => false, 'message' => 'No se pudo generar la factura de proveedor.'];
        }

        return ['ok' => true, 'id' => (int) $id];
    }

    public function fromSalesReceipt(int $receiptId, float $amount, ?int $accountId = null): array
    {
        if ($amount <= 0) {
            return ['ok' => true, 'skipped' => true];
        }

        return $this->post([
            'account_id'     => $accountId,
            'reference_type' => 'sales_receipt',
            'reference_id'   => $receiptId,
            'type'           => 'debit',
            'amount'         => $amount,
            'notes'          => 'Cobro de recibo de venta #' . $receiptId,
        ]);
    }

    public function fromSalesPayment(int $paymentId, float $amount, ?int $accountId = null): array
    {
        if ($amount <= 0) {
            return ['ok' => true, 'skipped' => true];
        }

        return $this->post([
            'account_id'     => $accountId,
            'reference_type' => 'sales_payment',
            'reference_id'   => $paymentId,
            'type'           => 'debit',
            'amount'         => $amount,
            'notes'          => 'Ingreso por cobro de venta #' . $paymentId,
        ]);
    }
}
