<?php

namespace App\Models\HR;

use CodeIgniter\Model;

class HrPayrollPeriodModel extends Model
{
    use \App\Traits\DataTableTrait;

    public const PERIOD_TYPES = [
        'weekly'    => 'Semanal',
        'biweekly'  => 'Quincenal',
        'monthly'   => 'Mensual',
    ];

    public const STATUSES = [
        'draft'     => 'Borrador',
        'approved'  => 'Aprobada',
        'paid'      => 'Pagada',
        'cancelled' => 'Cancelada',
    ];

    public const STATUS_COLORS = [
        'draft'     => 'secondary',
        'approved'  => 'info',
        'paid'      => 'success',
        'cancelled' => 'danger',
    ];

    protected $table            = 'hr_payroll_periods';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'period_type',
        'start_date',
        'end_date',
        'status',
        'branch_id',
        'total_gross',
        'total_deductions',
        'total_net',
        'created_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'name'        => 'required|max_length[100]',
        'period_type' => 'required|in_list[weekly,biweekly,monthly]',
        'start_date'  => 'required|valid_date',
        'end_date'    => 'required|valid_date',
        'status'      => 'permit_empty|in_list[draft,approved,paid,cancelled]',
        'branch_id'   => 'permit_empty|is_natural_no_zero',
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * §5/§16 — columnas ordenables y buscables para DataTableTrait.
     *
     * @var array<int, string|null>
     */
    private $column_order = [
        'hr_payroll_periods.name',
        'hr_payroll_periods.period_type',
        'hr_payroll_periods.start_date',
        'worker_count',
        'hr_payroll_periods.total_gross',
        'hr_payroll_periods.total_net',
        'hr_payroll_periods.status',
        null,
    ];

    /**
     * @var array<int, string>
     */
    private $column_search = [
        'hr_payroll_periods.name',
        'hr_payroll_periods.period_type',
        'org_branches.name',
        'org_branches.commercial_name',
    ];

    protected $datatableConfig = [
        'column_order'  => [
            'hr_payroll_periods.name',
            'hr_payroll_periods.period_type',
            'hr_payroll_periods.start_date',
            'worker_count',
            'hr_payroll_periods.total_gross',
            'hr_payroll_periods.total_net',
            'hr_payroll_periods.status',
            null,
        ],
        'column_search' => [
            'hr_payroll_periods.name',
            'hr_payroll_periods.period_type',
            'org_branches.name',
            'org_branches.commercial_name',
        ],
        'order' => ['hr_payroll_periods.start_date' => 'DESC'],
    ];

    protected function _get_datatables_query($postData = [])
    {
        $this->builder()
            ->select("
                hr_payroll_periods.*,
                org_branches.name AS branch_name,
                org_branches.commercial_name AS branch_commercial_name,
                (
                    SELECT COUNT(*)
                    FROM hr_payroll_entries e
                    WHERE e.period_id = hr_payroll_periods.id
                      AND e.deleted_at IS NULL
                ) AS worker_count
            ", false)
            ->join('org_branches', 'org_branches.id = hr_payroll_periods.branch_id', 'left');

        if (! empty($postData['filter_status'])) {
            $this->builder()->where('hr_payroll_periods.status', $postData['filter_status']);
        }

        if (! empty($postData['filter_branch_id'])) {
            $this->builder()->where('hr_payroll_periods.branch_id', (int) $postData['filter_branch_id']);
        }

        $this->_apply_datatables_filters($postData);
    }

    /**
     * Cards del dashboard de nóminas.
     */
    public function getDashboardStats(): array
    {
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-t');

        $periodsThisMonth = (int) $this->db->table('hr_payroll_periods')
            ->where('deleted_at', null)
            ->groupStart()
                ->where('start_date >=', $monthStart)
                ->where('start_date <=', $monthEnd)
            ->groupEnd()
            ->countAllResults();

        $pendingPayments = (int) $this->db->table('hr_payroll_entries')
            ->where('payment_status', 'pending')
            ->where('deleted_at', null)
            ->countAllResults();

        $paidThisMonth = (int) $this->db->table('hr_payroll_entries')
            ->where('payment_status', 'paid')
            ->where('deleted_at', null)
            ->where('payment_date >=', $monthStart)
            ->where('payment_date <=', $monthEnd)
            ->countAllResults();

        $pendingAmountRow = $this->db->table('hr_payroll_entries')
            ->selectSum('net_amount', 'total')
            ->where('payment_status', 'pending')
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return [
            'periods_this_month' => $periodsThisMonth,
            'pending_payments'   => $pendingPayments,
            'paid_this_month'    => $paidThisMonth,
            'pending_amount'     => round((float) ($pendingAmountRow['total'] ?? 0), 2),
        ];
    }

    /**
     * Periodo con entradas y datos de trabajador.
     */
    public function getWithDetails(int $id): ?array
    {
        $period = $this->select('hr_payroll_periods.*, org_branches.name AS branch_name, org_branches.commercial_name AS branch_commercial_name, org_branches.logo_path AS branch_logo_path')
            ->join('org_branches', 'org_branches.id = hr_payroll_periods.branch_id', 'left')
            ->where('hr_payroll_periods.id', $id)
            ->first();

        if (! $period) {
            return null;
        }

        $entryModel          = new HrPayrollEntryModel();
        $period['entries']   = $entryModel->getByPeriod($id);
        $period['worker_count'] = count($period['entries']);

        return $period;
    }

    /**
     * Trabajadores activos (y sueldos de hr_employment_data) para generar entradas.
     * No crea campo salary: usa current_salary / daily_salary / imss_fee / infonavit_contribution.
     */
    public function getActiveWorkersForPayroll(?int $branchId = null): array
    {
        $builder = $this->db->table('hr_profiles p')
            ->select('
                p.id,
                p.first_name,
                p.last_name,
                users.first_name AS user_first_name,
                users.last_name AS user_last_name,
                e.current_salary,
                e.daily_salary,
                e.imss_fee,
                e.infonavit_contribution,
                e.isr_retention,
                e.payment_method,
                e.location_id
            ')
            ->join('hr_employment_data e', 'e.profile_id = p.id')
            ->join('users', 'users.id = p.user_id', 'left')
            ->where('e.status', 'active')
            ->where('p.deleted_at', null)
            ->where('e.deleted_at', null);

        if ($branchId) {
            $builder->where('e.location_id', $branchId);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Crea el periodo y genera entradas automáticas en una sola transacción (§9).
     * El controlador NUNCA debe abrir transStart/transComplete.
     *
     * @param array $periodData Campos del periodo. worked_days es auxiliar (no se persiste en la cabecera).
     * @param array $workers    Filas de getActiveWorkersForPayroll()
     */
    public function createPeriodWithEntries(array $periodData, array $workers): int|false
    {
        $workedDays = (int) ($periodData['worked_days'] ?? 0);
        unset($periodData['worked_days']);

        $periodType = $periodData['period_type'] ?? 'biweekly';
        if ($workedDays <= 0) {
            $workedDays = match ($periodType) {
                'weekly'   => 7,
                'monthly'  => 30,
                default    => 15,
            };
        }

        $periodData['status']           = $periodData['status'] ?? 'draft';
        $periodData['total_gross']      = $periodData['total_gross'] ?? 0;
        $periodData['total_deductions'] = $periodData['total_deductions'] ?? 0;
        $periodData['total_net']        = $periodData['total_net'] ?? 0;

        $this->db->transStart();

        $periodId = $this->insert($periodData);
        if (! $periodId) {
            $this->db->transComplete();

            return false;
        }

        $entryModel = new HrPayrollEntryModel();

        foreach ($workers as $worker) {
            $currentSalary = (float) ($worker['current_salary'] ?? $worker['salary'] ?? 0);
            $dailySalary   = (float) ($worker['daily_salary'] ?? 0);

            if ($dailySalary > 0) {
                $gross = round($dailySalary * $workedDays, 2);
            } else {
                $gross = match ($periodType) {
                    'weekly'  => round($currentSalary / 4, 2),
                    'monthly' => round($currentSalary, 2),
                    default   => round($currentSalary / 2, 2),
                };
            }

            $imss      = round((float) ($worker['imss_fee'] ?? 0), 2);
            $infonavit = round((float) ($worker['infonavit_contribution'] ?? 0), 2);
            $isr       = round((float) ($worker['isr_retention'] ?? 0), 2);
            $net       = round($gross - $imss - $infonavit - $isr, 2);

            $entryModel->skipValidation(true)->insert([
                'period_id'         => $periodId,
                'hr_profile_id'     => (int) $worker['id'],
                'base_salary'       => $currentSalary,
                'worked_days'       => $workedDays,
                'overtime_hours'    => 0,
                'gross_amount'      => $gross,
                'imss_employee'     => $imss,
                'infonavit'         => $infonavit,
                'isr'               => $isr,
                'other_deductions'  => 0,
                'bonuses'           => 0,
                'benefits'          => 0,
                'net_amount'        => $net,
                'payment_method'    => HrPayrollEntryModel::mapEmploymentPaymentMethod($worker['payment_method'] ?? null),
                'payment_status'    => 'pending',
            ]);
        }

        $this->recalculatePeriodTotals((int) $periodId);

        $this->db->transComplete();

        return $this->db->transStatus() ? (int) $periodId : false;
    }

    public function recalculatePeriodTotals(int $periodId): bool
    {
        $totals = (new HrPayrollEntryModel())->calculateTotals($periodId);

        return (bool) $this->update($periodId, [
            'total_gross'      => $totals['gross'],
            'total_deductions' => $totals['deductions'],
            'total_net'        => $totals['net'],
        ]);
    }

    /**
     * Marca todas las entradas del periodo como pagadas (transacción §9).
     */
    public function markAllEntriesPaid(int $periodId, string $paymentDate, ?string $reference = null): bool
    {
        $this->db->transStart();

        $entryModel = new HrPayrollEntryModel();
        $entryModel->where('period_id', $periodId)
            ->where('payment_status !=', 'paid')
            ->set([
                'payment_status'    => 'paid',
                'payment_date'      => $paymentDate,
                'payment_reference' => $reference,
            ])
            ->update();

        $this->update($periodId, ['status' => 'paid']);

        $this->db->transComplete();

        return $this->db->transStatus();
    }
}
