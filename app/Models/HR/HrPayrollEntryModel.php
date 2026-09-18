<?php

namespace App\Models\HR;

use CodeIgniter\Model;

class HrPayrollEntryModel extends Model
{
    use \App\Traits\DataTableTrait;

    public const PAYMENT_METHODS = [
        'bank_transfer' => 'Transferencia bancaria',
        'cash'          => 'Efectivo',
        'check'         => 'Cheque',
        'other'         => 'Otro',
    ];

    public const PAYMENT_STATUSES = [
        'pending' => 'Pendiente',
        'paid'    => 'Pagado',
        'partial' => 'Parcial',
    ];

    protected $table            = 'hr_payroll_entries';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'period_id',
        'hr_profile_id',
        'base_salary',
        'worked_days',
        'overtime_hours',
        'gross_amount',
        'imss_employee',
        'infonavit',
        'isr',
        'other_deductions',
        'bonuses',
        'benefits',
        'net_amount',
        'payment_method',
        'payment_status',
        'payment_date',
        'payment_reference',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'period_id'        => 'required|is_natural_no_zero',
        'hr_profile_id'    => 'required|is_natural_no_zero',
        'base_salary'      => 'permit_empty|decimal',
        'worked_days'      => 'permit_empty|integer',
        'overtime_hours'   => 'permit_empty|decimal',
        'gross_amount'     => 'permit_empty|decimal',
        'imss_employee'    => 'permit_empty|decimal',
        'infonavit'        => 'permit_empty|decimal',
        'isr'              => 'permit_empty|decimal',
        'other_deductions' => 'permit_empty|decimal',
        'bonuses'          => 'permit_empty|decimal',
        'benefits'         => 'permit_empty|decimal',
        'net_amount'       => 'permit_empty|decimal',
        'payment_method'   => 'permit_empty|in_list[bank_transfer,cash,check,other]',
        'payment_status'   => 'permit_empty|in_list[pending,paid,partial]',
        'payment_date'     => 'permit_empty|valid_date',
        'payment_reference'=> 'permit_empty|max_length[100]',
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    protected $datatableConfig = [
        'column_order'  => [
            'worker_name',
            'job_name',
            'worked_days',
            'base_salary',
            'bonuses',
            'net_amount',
            'payment_method',
            'payment_status',
            null,
        ],
        'column_search' => [
            'hp.first_name',
            'hp.last_name',
            'users.first_name',
            'users.last_name',
            'j.name',
        ],
        'order' => ['worker_name' => 'ASC'],
    ];

    /**
     * Entradas de un periodo con datos del trabajador, puesto y departamento.
     */
    public function getByPeriod(int $periodId): array
    {
        return $this->builder()
            ->select("
                hr_payroll_entries.*,
                TRIM(CONCAT(
                    COALESCE(NULLIF(hp.first_name, ''), users.first_name, ''),
                    ' ',
                    COALESCE(NULLIF(hp.last_name, ''), users.last_name, '')
                )) AS worker_name,
                hp.rfc AS worker_rfc,
                hp.nss AS worker_nss,
                hp.curp AS worker_curp,
                e.employee_number,
                e.current_salary AS profile_current_salary,
                e.daily_salary AS profile_daily_salary,
                j.name AS job_name,
                d.name AS department_name
            ")
            ->join('hr_profiles hp', 'hp.id = hr_payroll_entries.hr_profile_id', 'left')
            ->join('users', 'users.id = hp.user_id', 'left')
            ->join('hr_employment_data e', 'e.profile_id = hp.id', 'left')
            ->join('hr_cat_jobs j', 'j.id = e.job_id', 'left')
            ->join('hr_cat_departments d', 'd.id = e.department_id', 'left')
            ->where('hr_payroll_entries.period_id', $periodId)
            ->where('hr_payroll_entries.deleted_at', null)
            ->orderBy('worker_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Recibo de una entrada: trabajador + periodo + sucursal + empresa.
     */
    public function getReceiptData(int $entryId): ?array
    {
        $row = $this->builder()
            ->select("
                hr_payroll_entries.*,
                TRIM(CONCAT(
                    COALESCE(NULLIF(hp.first_name, ''), users.first_name, ''),
                    ' ',
                    COALESCE(NULLIF(hp.last_name, ''), users.last_name, '')
                )) AS worker_name,
                hp.rfc AS worker_rfc,
                hp.nss AS worker_nss,
                hp.curp AS worker_curp,
                hp.bank_name,
                hp.bank_account,
                hp.bank_clabe,
                e.employee_number,
                j.name AS job_name,
                d.name AS department_name,
                p.name AS period_name,
                p.period_type,
                p.start_date,
                p.end_date,
                p.status AS period_status,
                p.branch_id,
                ob.name AS branch_name,
                ob.commercial_name AS branch_commercial_name,
                ob.legal_name AS branch_legal_name,
                ob.tax_id AS branch_tax_id,
                ob.address AS branch_address,
                ob.phone AS branch_phone,
                ob.email AS branch_email,
                ob.logo_path AS branch_logo_path
            ")
            ->join('hr_profiles hp', 'hp.id = hr_payroll_entries.hr_profile_id', 'left')
            ->join('users', 'users.id = hp.user_id', 'left')
            ->join('hr_employment_data e', 'e.profile_id = hp.id', 'left')
            ->join('hr_cat_jobs j', 'j.id = e.job_id', 'left')
            ->join('hr_cat_departments d', 'd.id = e.department_id', 'left')
            ->join('hr_payroll_periods p', 'p.id = hr_payroll_entries.period_id', 'left')
            ->join('org_branches ob', 'ob.id = p.branch_id', 'left')
            ->where('hr_payroll_entries.id', $entryId)
            ->where('hr_payroll_entries.deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Suma gross, deductions y net de un periodo (entradas no eliminadas).
     */
    public function calculateTotals(int $periodId): array
    {
        $row = $this->builder()
            ->select('
                COALESCE(SUM(gross_amount), 0) AS gross,
                COALESCE(SUM(imss_employee + infonavit + isr + other_deductions), 0) AS deductions,
                COALESCE(SUM(net_amount), 0) AS net
            ', false)
            ->where('period_id', $periodId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return [
            'gross'      => round((float) ($row['gross'] ?? 0), 2),
            'deductions' => round((float) ($row['deductions'] ?? 0), 2),
            'net'        => round((float) ($row['net'] ?? 0), 2),
        ];
    }

    /**
     * Recalcula importes de una entrada y actualiza totales del periodo (transacción §9).
     */
    public function updateEntryFields(int $entryId, array $data): bool
    {
        $entry = $this->find($entryId);
        if (! $entry) {
            return false;
        }

        $merged = array_merge($entry, $data);

        $gross = (float) ($merged['base_salary'] ?? 0)
            + (float) ($merged['bonuses'] ?? 0)
            + (float) ($merged['benefits'] ?? 0);

        $deductions = (float) ($merged['imss_employee'] ?? 0)
            + (float) ($merged['infonavit'] ?? 0)
            + (float) ($merged['isr'] ?? 0)
            + (float) ($merged['other_deductions'] ?? 0);

        $data['gross_amount'] = round($gross, 2);
        $data['net_amount']   = round($gross - $deductions, 2);

        $this->db->transStart();
        $this->update($entryId, $data);

        $periodModel = new HrPayrollPeriodModel();
        $periodModel->recalculatePeriodTotals((int) $entry['period_id']);

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Marca una entrada como pagada y, si todas lo están, el periodo (transacción §9).
     */
    public function markEntryPaid(int $entryId, string $paymentDate, ?string $reference = null): bool
    {
        $entry = $this->find($entryId);
        if (! $entry) {
            return false;
        }

        $this->db->transStart();

        $this->update($entryId, [
            'payment_status'    => 'paid',
            'payment_date'      => $paymentDate,
            'payment_reference' => $reference,
        ]);

        $pending = $this->where('period_id', $entry['period_id'])
            ->where('payment_status !=', 'paid')
            ->countAllResults();

        if ($pending === 0) {
            (new HrPayrollPeriodModel())->update((int) $entry['period_id'], ['status' => 'paid']);
        }

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    public static function mapEmploymentPaymentMethod(?string $method): string
    {
        return match ($method) {
            'transferencia', 'bank_transfer' => 'bank_transfer',
            'efectivo', 'cash'               => 'cash',
            'cheque', 'check'                => 'check',
            default                          => 'bank_transfer',
        };
    }
}
