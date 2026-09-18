<?php

namespace App\Models\Financial;

use CodeIgniter\Model;
use App\Traits\DataTableTrait;

/**
 * Vencimientos / ocurrencias de gastos (fin_expenses).
 */
class FinExpenseModel extends Model
{
    use DataTableTrait;

    protected $table            = 'fin_expenses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'supplier_id',
        'category_id',
        'recurring_expense_id',
        'org_branch_id',
        'total_amount',
        'paid_amount',
        'currency',
        'due_date',
        'status',
        'period_label',
        'notes',
        'invoice_url',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'supplier_id'  => 'permit_empty|is_natural_no_zero',
        'total_amount' => 'required|decimal|greater_than_equal_to[0]',
        'due_date'     => 'required|valid_date',
        'status'       => 'required|in_list[pending,partial,paid,cancelled]',
        'currency'     => 'permit_empty|max_length[3]',
        'paid_amount'  => 'permit_empty|decimal|greater_than_equal_to[0]',
    ];

    protected $datatableConfig = [
        'column_order'  => [
            'fin_expenses.due_date',
            'fin_suppliers.commercial_name',
            'fin_recurring_expenses.description',
            'org_branches.name',
            'fin_expenses.period_label',
            'fin_expenses.total_amount',
            'fin_expenses.paid_amount',
            'fin_expenses.status',
            null,
        ],
        'column_search' => [
            'fin_suppliers.commercial_name',
            'fin_recurring_expenses.description',
            'fin_expenses.period_label',
            'org_branches.name',
        ],
        'order' => ['fin_expenses.due_date' => 'asc'],
    ];

    protected function _get_datatables_query($postData = [])
    {
        $this->builder()
            ->select([
                'fin_expenses.*',
                'fin_suppliers.commercial_name AS supplier_name',
                'fin_recurring_expenses.description AS template_description',
                'fin_recurring_expenses.service_type',
                'org_branches.name AS branch_name',
            ])
            ->join('fin_suppliers', 'fin_suppliers.id = fin_expenses.supplier_id', 'left')
            ->join('fin_recurring_expenses', 'fin_recurring_expenses.id = fin_expenses.recurring_expense_id', 'left')
            ->join('org_branches', 'org_branches.id = fin_expenses.org_branch_id', 'left')
            ->where('fin_expenses.deleted_at IS NULL');

        if (!empty($postData['filter_status'])) {
            $this->builder()->where('fin_expenses.status', $postData['filter_status']);
        }

        if (!empty($postData['filter_branch_id'])) {
            $this->builder()->where('fin_expenses.org_branch_id', (int) $postData['filter_branch_id']);
        }

        if (!empty($postData['filter_recurring_only'])) {
            $this->builder()->where('fin_expenses.recurring_expense_id IS NOT NULL', null, false);
        }

        if (!empty($postData['filter_supplier_id'])) {
            $this->builder()->where('fin_expenses.supplier_id', (int) $postData['filter_supplier_id']);
        }

        if (!empty($postData['filter_service_type'])) {
            $this->builder()->where('fin_recurring_expenses.service_type', $postData['filter_service_type']);
        }

        if (!empty($postData['filter_month'])) {
            $month = trim((string) $postData['filter_month']);
            if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                $this->builder()->like('fin_expenses.due_date', $month, 'after');
            }
        }

        $this->_apply_datatables_filters($postData);
    }

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
     * Marca pago parcial o total de un vencimiento.
     */
    public function markPaid(int $id, float $paidAmount): bool
    {
        $expense = $this->find($id);
        if (!$expense) {
            return false;
        }

        $total  = (float) $expense->total_amount;
        $paid   = min($paidAmount, $total);
        $status = $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'pending');

        return $this->update($id, [
            'paid_amount' => $paid,
            'status'      => $status,
        ]);
    }

    /**
     * Eventos para FullCalendar.
     */
    public function getCalendarEvents(?string $start = null, ?string $end = null): array
    {
        $builder = $this->builder()
            ->select([
                'fin_expenses.id',
                'fin_expenses.due_date',
                'fin_expenses.status',
                'fin_expenses.total_amount',
                'fin_expenses.paid_amount',
                'fin_expenses.currency',
                'fin_expenses.period_label',
                'fin_recurring_expenses.description AS title',
                'fin_suppliers.commercial_name AS supplier_name',
            ])
            ->join('fin_recurring_expenses', 'fin_recurring_expenses.id = fin_expenses.recurring_expense_id', 'left')
            ->join('fin_suppliers', 'fin_suppliers.id = fin_expenses.supplier_id', 'left')
            ->where('fin_expenses.deleted_at IS NULL')
            ->where('fin_expenses.recurring_expense_id IS NOT NULL', null, false);

        if ($start) {
            $builder->where('fin_expenses.due_date >=', $start);
        }
        if ($end) {
            $builder->where('fin_expenses.due_date <=', $end);
        }

        $rows = $builder->get()->getResult();
        $today = date('Y-m-d');
        $events = [];

        foreach ($rows as $row) {
            $color = 'warning';
            if ($row->status === 'paid') {
                $color = 'success';
            } elseif ($row->status === 'partial') {
                $color = 'info';
            } elseif ($row->due_date < $today && in_array($row->status, ['pending', 'partial'], true)) {
                $color = 'danger';
            }

            $label = $row->title ?: ($row->supplier_name ?? 'Gasto recurrente');
            $events[] = [
                'id'              => (int) $row->id,
                'title'           => $label . ' (' . number_format((float) $row->total_amount, 2) . ' ' . ($row->currency ?? 'MXN') . ')',
                'start'           => $row->due_date,
                'end'             => $row->due_date,
                'backgroundColor' => $color === 'warning' ? '#ffc107' : ($color === 'success' ? '#198754' : ($color === 'danger' ? '#dc3545' : '#0dcaf0')),
                'borderColor'     => 'transparent',
                'extendedProps'   => [
                    'status'       => $row->status,
                    'period_label' => $row->period_label,
                    'supplier'     => $row->supplier_name,
                ],
            ];
        }

        return $events;
    }

    public function getDashboardStats(): array
    {
        $today      = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-t');

        $pendingThisMonth = $this->_aggregateRecurring(
            ['pending', 'partial'],
            $monthStart,
            $monthEnd,
            false
        );
        $overdue = $this->_aggregateRecurring(
            ['pending', 'partial'],
            null,
            $today,
            true
        );
        $paidThisMonth = $this->_aggregateRecurring(
            ['paid'],
            $monthStart,
            $monthEnd,
            false,
            'paid_amount'
        );
        $dueSoon = $this->_aggregateDueSoon();

        return [
            'pending_this_month'        => $pendingThisMonth['count'],
            'pending_this_month_amount' => $pendingThisMonth['outstanding'],
            'overdue'                   => $overdue['count'],
            'overdue_amount'            => $overdue['outstanding'],
            'paid_this_month'           => $paidThisMonth['count'],
            'paid_this_month_amount'    => $paidThisMonth['sum'],
            'due_soon'                  => $dueSoon['count'],
            'due_soon_amount'           => $dueSoon['outstanding'],
            'currency'                  => 'MXN',
        ];
    }

    /**
     * @param list<string> $statuses
     * @return array{count:int,sum:float,outstanding:float}
     */
    private function _aggregateRecurring(
        array $statuses,
        ?string $from,
        ?string $to,
        bool $overdueOnly,
        string $sumField = 'total_amount'
    ): array {
        $builder = $this->db->table($this->table);
        $builder->select(
            'COUNT(*) AS cnt, COALESCE(SUM(' . $sumField . '), 0) AS amt, COALESCE(SUM(total_amount - paid_amount), 0) AS outstanding'
        )
            ->where('deleted_at IS NULL')
            ->where('recurring_expense_id IS NOT NULL', null, false)
            ->whereIn('status', $statuses);

        if ($from !== null) {
            $builder->where('due_date >=', $from);
        }
        if ($to !== null) {
            if ($overdueOnly) {
                $builder->where('due_date <', $to);
            } else {
                $builder->where('due_date <=', $to);
            }
        }

        $row = $builder->get()->getRow();

        return [
            'count'       => (int) ($row->cnt ?? 0),
            'sum'         => (float) ($row->amt ?? 0),
            'outstanding' => (float) ($row->outstanding ?? 0),
        ];
    }

    /**
     * @return array{count:int,outstanding:float}
     */
    private function _aggregateDueSoon(): array
    {
        $today = date('Y-m-d');
        $row   = $this->db->table('fin_expenses fe')
            ->select('COUNT(*) AS cnt, COALESCE(SUM(fe.total_amount - fe.paid_amount), 0) AS outstanding')
            ->join('fin_recurring_expenses re', 're.id = fe.recurring_expense_id')
            ->where('fe.deleted_at IS NULL')
            ->where('re.deleted_at IS NULL')
            ->whereIn('fe.status', ['pending', 'partial'])
            ->where('fe.due_date >=', $today)
            ->where('re.notify_days_before >', 0)
            ->where('fe.due_date <= DATE_ADD(' . $this->db->escape($today) . ', INTERVAL re.notify_days_before DAY)', null, false)
            ->get()
            ->getRow();

        return [
            'count'       => (int) ($row->cnt ?? 0),
            'outstanding' => (float) ($row->outstanding ?? 0),
        ];
    }

    public function countOverdueRecurring(): int
    {
        return $this->db->table($this->table)
            ->where('deleted_at IS NULL')
            ->where('recurring_expense_id IS NOT NULL', null, false)
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date <', date('Y-m-d'))
            ->countAllResults();
    }

    public function countDueSoonRecurring(): int
    {
        return $this->_aggregateDueSoon()['count'];
    }

    public function getDetail(int $id): ?object
    {
        return $this->select([
                'fin_expenses.*',
                'fin_suppliers.commercial_name AS supplier_name',
                'fin_recurring_expenses.description AS template_description',
                'fin_recurring_expenses.service_type',
                'fin_recurring_expenses.notify_days_before',
                'org_branches.name AS branch_name',
            ])
            ->join('fin_suppliers', 'fin_suppliers.id = fin_expenses.supplier_id', 'left')
            ->join('fin_recurring_expenses', 'fin_recurring_expenses.id = fin_expenses.recurring_expense_id', 'left')
            ->join('org_branches', 'org_branches.id = fin_expenses.org_branch_id', 'left')
            ->where('fin_expenses.id', $id)
            ->first();
    }

    public function cancelOccurrence(int $id): bool
    {
        $expense = $this->find($id);
        if (!$expense || !in_array($expense->status, ['pending', 'partial'], true)) {
            return false;
        }

        return $this->update($id, ['status' => 'cancelled']);
    }

    public function rescheduleOccurrence(int $id, string $dueDate): bool
    {
        $expense = $this->find($id);
        if (!$expense || in_array($expense->status, ['paid', 'cancelled'], true)) {
            return false;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            return false;
        }

        return $this->update($id, ['due_date' => $dueDate]);
    }
}
