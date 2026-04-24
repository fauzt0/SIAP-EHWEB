<?php

namespace App\Models\HR;

use CodeIgniter\Model;

class HrEmploymentModel extends Model
{
    protected $table            = 'hr_employment_data';
    protected $primaryKey       = 'profile_id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'profile_id',
        'employee_number',
        'worker_type',
        'direct_manager_id',
        'corporate_email',
        'department_id',
        'job_id',
        'location_id',
        'current_salary',
        'daily_salary',
        'payroll_type',
        'payment_method',
        'alimony_percent',
        'alimony_fixed_amount',
        'isr_retention',
        'imss_fee',
        'infonavit_contribution',
        'afore_contribution',
        'benefits_infonavit',
        'benefits_fonacot',
        'benefits_afore',
        'benefits_vacations',
        'hiring_date',
        'termination_date',
        'status',
        'notes'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'profile_id'      => 'required|is_natural_no_zero|is_unique[hr_employment_data.profile_id,profile_id,{profile_id}]',
        'employee_number' => 'required|max_length[20]|is_unique[hr_employment_data.employee_number,profile_id,{profile_id}]',
        'department_id'   => 'permit_empty|is_natural_no_zero',
        'job_id'          => 'permit_empty|is_natural_no_zero',
        'location_id'     => 'permit_empty|is_natural_no_zero',
        'current_salary'  => 'required|numeric|greater_than_equal_to[0]',
        'daily_salary'    => 'required|numeric|greater_than_equal_to[0]',
        'hiring_date'     => 'required|valid_date',
        'termination_date'=> 'permit_empty|valid_date',
        'status'          => 'required|in_list[active,on_leave,terminated,suspended]',
        'worker_type'     => 'required|in_list[planta,temporal,proyecto,honorarios]',
        'payroll_type'    => 'required|in_list[quincenal,mensual,semanal]',
        'payment_method'  => 'required|in_list[transferencia,efectivo,cheque]',
        'corporate_email' => 'permit_empty|valid_email'
    ];
    
    protected $validationMessages   = [
        'employee_number' => [
            'is_unique' => 'Este número de empleado ya está asignado a otro trabajador.'
        ],
        'current_salary' => [
            'numeric' => 'El salario debe ser un valor numérico válido.'
        ]
    ];
    
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
