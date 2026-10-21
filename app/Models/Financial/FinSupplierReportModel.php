<?php

namespace App\Models\Financial;

use CodeIgniter\Database\BaseConnection;

/**
 * Lectura de reportes de proveedores. No escribe tablas.
 * El dinero pagado y pendiente sale de fin_expenses.
 * Las órdenes de compra no tienen paid_amount: aquí solo cuentan estatus e importe.
 */
class FinSupplierReportModel
{
    private const PO_STATUSES = ['draft', 'sent', 'approved', 'completed', 'cancelled'];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * @param array{supplier_id?:int|string|null} $filters
     * @return array<string, mixed>
     */
    public function snapshot(array $filters = []): array
    {
        $supplierId = (int) ($filters['supplier_id'] ?? 0);
        $supplierId = $supplierId > 0 ? $supplierId : null;
        $today      = date('Y-m-d');

        $suppliers = $this->suppliers($supplierId);
        $expenses  = $this->expensesBySupplierCurrency($supplierId);
        $orders    = $this->ordersBySupplierCurrencyStatus($supplierId);
        $recurring = $this->activeRecurringCounts($supplierId);

        return [
            'today'         => $today,
            'supplier_id'   => $supplierId,
            'money'         => $this->moneyByCurrency($supplierId),
            'po_status'     => $this->poStatusSummary($orders),
            'suppliers'     => $this->supplierRows($suppliers, $expenses, $orders, $recurring),
            'reminders'     => $this->reminders($supplierId, $today),
            'open_orders'   => $this->openOrders($supplierId),
        ];
    }

    /**
     * @return list<object>
     */
    private function suppliers(?int $supplierId): array
    {
        $builder = $this->db->table('fin_suppliers')
            ->select('id, commercial_name, status')
            ->where('deleted_at IS NULL')
            ->orderBy('commercial_name', 'ASC');

        if ($supplierId !== null) {
            $builder->where('id', $supplierId);
        }

        return $builder->get()->getResult();
    }

    /**
     * @return array<string, array{currency:string,total:float,paid:float,outstanding:float,count:int,percent:?float}>
     */
    private function moneyByCurrency(?int $supplierId): array
    {
        $builder = $this->db->table('fin_expenses')
            ->select(
                "COALESCE(NULLIF(currency, ''), 'MXN') AS currency, COUNT(*) AS cnt, COALESCE(SUM(total_amount), 0) AS total, COALESCE(SUM(paid_amount), 0) AS paid, COALESCE(SUM(total_amount - paid_amount), 0) AS outstanding",
                false
            )
            ->where('deleted_at IS NULL')
            ->whereNotIn('status', ['cancelled'])
            ->groupBy("COALESCE(NULLIF(currency, ''), 'MXN')", false)
            ->orderBy('currency', 'ASC');

        if ($supplierId !== null) {
            $builder->where('supplier_id', $supplierId);
        }

        $out = [];
        foreach ($builder->get()->getResult() as $row) {
            $currency = (string) $row->currency;
            $total    = round((float) $row->total, 2);
            $paid     = round((float) $row->paid, 2);
            $out[$currency] = [
                'currency'    => $currency,
                'total'       => $total,
                'paid'        => $paid,
                'outstanding' => round((float) $row->outstanding, 2),
                'count'       => (int) $row->cnt,
                'percent'     => $this->percent($paid, $total),
            ];
        }

        return $out;
    }

    /**
     * @return array<string, array<string, array{total:float,paid:float,outstanding:float,pending:int,partial:int,paid_n:int}>>
     */
    private function expensesBySupplierCurrency(?int $supplierId): array
    {
        $builder = $this->db->table('fin_expenses')
            ->select(
                "supplier_id, COALESCE(NULLIF(currency, ''), 'MXN') AS currency,
                COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total_amount ELSE 0 END), 0) AS total,
                COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN paid_amount ELSE 0 END), 0) AS paid,
                COALESCE(SUM(CASE WHEN status IN ('pending','partial') THEN total_amount - paid_amount ELSE 0 END), 0) AS outstanding,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_n,
                SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) AS partial_n,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid_n",
                false
            )
            ->where('deleted_at IS NULL')
            ->where('supplier_id IS NOT NULL', null, false)
            ->groupBy("supplier_id, COALESCE(NULLIF(currency, ''), 'MXN')", false);

        if ($supplierId !== null) {
            $builder->where('supplier_id', $supplierId);
        }

        $out = [];
        foreach ($builder->get()->getResult() as $row) {
            $id       = (int) $row->supplier_id;
            $currency = (string) $row->currency;
            $out[$id][$currency] = [
                'total'       => round((float) $row->total, 2),
                'paid'        => round((float) $row->paid, 2),
                'outstanding' => round((float) $row->outstanding, 2),
                'pending'     => (int) $row->pending_n,
                'partial'     => (int) $row->partial_n,
                'paid_n'      => (int) $row->paid_n,
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array<string, array<string, array{n:int,amount:float}>>>
     */
    private function ordersBySupplierCurrencyStatus(?int $supplierId): array
    {
        $builder = $this->db->table('fin_purchase_orders')
            ->select(
                "fin_supplier_id, COALESCE(NULLIF(currency, ''), 'MXN') AS currency, status, COUNT(*) AS n, COALESCE(SUM(total_amount), 0) AS amount",
                false
            )
            ->where('deleted_at IS NULL')
            ->groupBy("fin_supplier_id, COALESCE(NULLIF(currency, ''), 'MXN'), status", false);

        if ($supplierId !== null) {
            $builder->where('fin_supplier_id', $supplierId);
        }

        $out = [];
        foreach ($builder->get()->getResult() as $row) {
            $id       = (int) $row->fin_supplier_id;
            $currency = (string) $row->currency;
            $status   = (string) $row->status;
            $out[$id][$currency][$status] = [
                'n'      => (int) $row->n,
                'amount' => round((float) $row->amount, 2),
            ];
        }

        return $out;
    }

    /**
     * @return array<int, int>
     */
    private function activeRecurringCounts(?int $supplierId): array
    {
        $builder = $this->db->table('fin_recurring_expenses')
            ->select('supplier_id, COUNT(*) AS n')
            ->where('deleted_at IS NULL')
            ->where('is_active', 1)
            ->where('supplier_id IS NOT NULL', null, false)
            ->groupBy('supplier_id');

        if ($supplierId !== null) {
            $builder->where('supplier_id', $supplierId);
        }

        $out = [];
        foreach ($builder->get()->getResult() as $row) {
            $out[(int) $row->supplier_id] = (int) $row->n;
        }

        return $out;
    }

    /**
     * @param list<object> $suppliers
     * @param array<int, array<string, array<string, mixed>>> $expenses
     * @param array<int, array<string, array<string, array{n:int,amount:float}>>> $orders
     * @param array<int, int> $recurring
     * @return list<array<string, mixed>>
     */
    private function supplierRows(array $suppliers, array $expenses, array $orders, array $recurring): array
    {
        $rows = [];

        foreach ($suppliers as $supplier) {
            $id         = (int) $supplier->id;
            $currencies = array_unique(array_merge(
                array_keys($expenses[$id] ?? []),
                array_keys($orders[$id] ?? [])
            ));
            sort($currencies);
            if ($currencies === []) {
                $currencies = [''];
            }

            foreach ($currencies as $currency) {
                $money = $expenses[$id][$currency] ?? [
                    'total' => 0.0, 'paid' => 0.0, 'outstanding' => 0.0,
                    'pending' => 0, 'partial' => 0, 'paid_n' => 0,
                ];
                $po = $orders[$id][$currency] ?? [];
                $openCount  = (int) (($po['sent']['n'] ?? 0) + ($po['approved']['n'] ?? 0));
                $openAmount = round((float) (($po['sent']['amount'] ?? 0) + ($po['approved']['amount'] ?? 0)), 2);

                $rows[] = [
                    'supplier_id'      => $id,
                    'name'             => (string) $supplier->commercial_name,
                    'active'           => (int) $supplier->status === 1,
                    'currency'         => $currency,
                    'expense_total'    => $money['total'],
                    'paid'             => $money['paid'],
                    'outstanding'      => $money['outstanding'],
                    'percent'          => $currency === '' ? null : $this->percent($money['paid'], $money['total']),
                    'pending_n'        => $money['pending'],
                    'partial_n'        => $money['partial'],
                    'paid_n'           => $money['paid_n'],
                    'po'               => $po,
                    'po_open_count'    => $openCount,
                    'po_open_amount'   => $openAmount,
                    'recurring_active' => $recurring[$id] ?? 0,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, array<string, array{n:int,amount:float}>>> $orders
     * @return array<string, array{count:int,by_currency:array<string,float>}>
     */
    private function poStatusSummary(array $orders): array
    {
        $summary = [];
        foreach (self::PO_STATUSES as $status) {
            $summary[$status] = ['count' => 0, 'by_currency' => []];
        }

        foreach ($orders as $byCurrency) {
            foreach ($byCurrency as $currency => $byStatus) {
                foreach ($byStatus as $status => $cell) {
                    if (! isset($summary[$status])) {
                        continue;
                    }
                    $summary[$status]['count'] += $cell['n'];
                    $summary[$status]['by_currency'][$currency] = round(
                        ($summary[$status]['by_currency'][$currency] ?? 0) + $cell['amount'],
                        2
                    );
                }
            }
        }

        foreach ($summary as $status => $cell) {
            ksort($summary[$status]['by_currency']);
        }

        return $summary;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function reminders(?int $supplierId, string $today): array
    {
        $items = array_merge(
            $this->expenseReminders($supplierId, $today),
            $this->ungeneratedReminders($supplierId, $today)
        );

        usort($items, static function (array $a, array $b): int {
            $rank = ['overdue' => 0, 'ungenerated' => 1, 'due_soon' => 2];

            return [$rank[$a['kind']] ?? 9, $a['due_date'], $a['supplier_name']]
                <=> [$rank[$b['kind']] ?? 9, $b['due_date'], $b['supplier_name']];
        });

        return $items;
    }

    private function reminderWindowSql(string $today): string
    {
        $day = $this->db->escape($today);

        return "(fe.due_date < {$day}"
            . " OR (fe.due_date >= {$day} AND ("
            . "(re.id IS NOT NULL AND re.notify_days_before > 0 AND fe.due_date <= DATE_ADD({$day}, INTERVAL re.notify_days_before DAY))"
            . " OR (fe.recurring_expense_id IS NULL AND fe.due_date <= DATE_ADD({$day}, INTERVAL 7 DAY))"
            . ')))';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function expenseReminders(?int $supplierId, string $today): array
    {
        $builder = $this->db->table('fin_expenses fe')
            ->select(
                'fe.id, fe.supplier_id, fe.due_date, fe.total_amount, fe.paid_amount, fe.currency, fe.status, fe.period_label, s.commercial_name, re.description, re.service_type, re.notify_days_before'
            )
            ->join('fin_suppliers s', 's.id = fe.supplier_id', 'left')
            ->join('fin_recurring_expenses re', 're.id = fe.recurring_expense_id', 'left')
            ->where('fe.deleted_at IS NULL')
            ->whereIn('fe.status', ['pending', 'partial'])
            ->where($this->reminderWindowSql($today), null, false)
            ->orderBy('fe.due_date', 'ASC')
            ->limit(300);

        if ($supplierId !== null) {
            $builder->where('fe.supplier_id', $supplierId);
        }

        $items = [];
        foreach ($builder->get()->getResult() as $row) {
            $outstanding = round((float) $row->total_amount - (float) $row->paid_amount, 2);
            $kind        = $row->due_date < $today ? 'overdue' : 'due_soon';
            $label       = (string) ($row->description ?: ($row->period_label ?: 'Gasto'));
            $items[]     = $this->reminderRow(
                $kind,
                $row->supplier_id !== null ? (int) $row->supplier_id : null,
                (string) ($row->commercial_name ?: 'Sin proveedor'),
                $label,
                (string) ($row->service_type ?? ''),
                (string) $row->due_date,
                (string) ($row->currency ?: 'MXN'),
                $outstanding,
                (string) $row->status,
                (int) $row->id,
                null
            );
        }

        return $items;
    }

    /**
     * Plantilla activa cuyo próximo vencimiento ya entra en la ventana de aviso
     * y todavía no tiene un gasto generado para esa fecha.
     *
     * @return list<array<string, mixed>>
     */
    private function ungeneratedReminders(?int $supplierId, string $today): array
    {
        $builder = $this->db->table('fin_recurring_expenses re')
            ->select('re.id, re.supplier_id, re.description, re.service_type, re.amount, re.currency, re.next_due_date, re.notify_days_before, s.commercial_name')
            ->join('fin_suppliers s', 's.id = re.supplier_id', 'left')
            ->where('re.deleted_at IS NULL')
            ->where('re.is_active', 1)
            ->where('re.next_due_date IS NOT NULL', null, false)
            ->where(
                're.next_due_date <= DATE_ADD(' . $this->db->escape($today) . ', INTERVAL GREATEST(IFNULL(re.notify_days_before, 0), 0) DAY)',
                null,
                false
            )
            ->where(
                'NOT EXISTS (SELECT 1 FROM fin_expenses fe WHERE fe.recurring_expense_id = re.id AND fe.deleted_at IS NULL AND fe.due_date = re.next_due_date AND fe.status IN (\'pending\',\'partial\',\'paid\'))',
                null,
                false
            )
            ->orderBy('re.next_due_date', 'ASC')
            ->limit(200);

        if ($supplierId !== null) {
            $builder->where('re.supplier_id', $supplierId);
        }

        $items = [];
        foreach ($builder->get()->getResult() as $row) {
            $items[] = $this->reminderRow(
                'ungenerated',
                $row->supplier_id !== null ? (int) $row->supplier_id : null,
                (string) ($row->commercial_name ?: 'Sin proveedor'),
                (string) $row->description,
                (string) $row->service_type,
                (string) $row->next_due_date,
                (string) ($row->currency ?: 'MXN'),
                round((float) $row->amount, 2),
                'pending',
                null,
                (int) $row->id
            );
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function reminderRow(
        string $kind,
        ?int $supplierId,
        string $supplierName,
        string $label,
        string $serviceType,
        string $dueDate,
        string $currency,
        float $outstanding,
        string $status,
        ?int $expenseId,
        ?int $templateId
    ): array {
        return [
            'kind'           => $kind,
            'supplier_id'    => $supplierId,
            'supplier_name'  => $supplierName,
            'label'          => $label,
            'service_type'   => $serviceType,
            'service_label'  => FinRecurringExpenseModel::getServiceTypeLabel($serviceType),
            'due_date'       => $dueDate,
            'currency'       => $currency,
            'outstanding'    => $outstanding,
            'status'         => $status,
            'expense_id'     => $expenseId,
            'template_id'    => $templateId,
        ];
    }

    /**
     * @return list<object>
     */
    private function openOrders(?int $supplierId): array
    {
        $builder = $this->db->table('fin_purchase_orders po')
            ->select('po.id, po.po_number, po.status, po.total_amount, po.currency, po.issue_date, po.delivery_date, po.fin_supplier_id, s.commercial_name')
            ->join('fin_suppliers s', 's.id = po.fin_supplier_id', 'left')
            ->where('po.deleted_at IS NULL')
            ->whereIn('po.status', ['sent', 'approved'])
            ->orderBy('po.delivery_date', 'ASC')
            ->orderBy('po.issue_date', 'ASC')
            ->limit(200);

        if ($supplierId !== null) {
            $builder->where('po.fin_supplier_id', $supplierId);
        }

        return $builder->get()->getResult();
    }

    private function percent(float $paid, float $total): ?float
    {
        if ($total <= 0) {
            return null;
        }

        return round(($paid / $total) * 100, 1);
    }
}
