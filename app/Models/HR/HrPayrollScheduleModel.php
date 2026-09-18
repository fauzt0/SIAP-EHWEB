<?php

namespace App\Models\HR;

use CodeIgniter\Model;

class HrPayrollScheduleModel extends Model
{
    protected $table            = 'hr_payroll_schedules';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'period_type',
        'next_payment_date',
        'recurrence_day',
        'branch_id',
        'active',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name'              => 'required|max_length[100]',
        'period_type'       => 'required|in_list[weekly,biweekly,monthly]',
        'next_payment_date' => 'required|valid_date',
        'recurrence_day'    => 'permit_empty|integer',
        'branch_id'         => 'permit_empty|is_natural_no_zero',
        'active'            => 'permit_empty|in_list[0,1]',
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Calendarios próximos (activos) dentro de N días.
     */
    public function getUpcoming(int $days = 30): array
    {
        $until = date('Y-m-d', strtotime('+' . $days . ' days'));

        return $this->select('hr_payroll_schedules.*, org_branches.name AS branch_name, org_branches.commercial_name AS branch_commercial_name')
            ->join('org_branches', 'org_branches.id = hr_payroll_schedules.branch_id', 'left')
            ->where('hr_payroll_schedules.active', 1)
            ->where('hr_payroll_schedules.next_payment_date <=', $until)
            ->orderBy('hr_payroll_schedules.next_payment_date', 'ASC')
            ->findAll();
    }

    /**
     * Listado completo para el planeador (incluye inactivos).
     */
    public function getAllWithBranch(): array
    {
        return $this->select('hr_payroll_schedules.*, org_branches.name AS branch_name, org_branches.commercial_name AS branch_commercial_name')
            ->join('org_branches', 'org_branches.id = hr_payroll_schedules.branch_id', 'left')
            ->orderBy('hr_payroll_schedules.next_payment_date', 'ASC')
            ->findAll();
    }
}
