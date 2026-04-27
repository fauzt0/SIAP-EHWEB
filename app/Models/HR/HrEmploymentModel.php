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
        'profile_id'      => [
            'label' => 'Perfil',
            'rules' => 'required|is_natural_no_zero|is_unique[hr_employment_data.profile_id,profile_id,{profile_id}]'
        ],
        'employee_number' => [
            'label' => 'Número de Empleado',
            'rules' => 'required|max_length[20]|is_unique[hr_employment_data.employee_number,profile_id,{profile_id}]'
        ],
        'department_id'   => [
            'label' => 'Departamento',
            'rules' => 'permit_empty|is_natural_no_zero'
        ],
        'job_id'          => [
            'label' => 'Puesto',
            'rules' => 'permit_empty|is_natural_no_zero'
        ],
        'location_id'     => [
            'label' => 'Sucursal',
            'rules' => 'permit_empty|is_natural_no_zero'
        ],
        'current_salary'  => [
            'label' => 'Salario Mensual',
            'rules' => 'required|numeric|greater_than_equal_to[0]'
        ],
        'daily_salary'    => [
            'label' => 'Salario Diario',
            'rules' => 'required|numeric|greater_than_equal_to[0]'
        ],
        'hiring_date'     => [
            'label' => 'Fecha de Ingreso',
            'rules' => 'required|valid_date'
        ],
        'status'          => [
            'label' => 'Estatus',
            'rules' => 'required|in_list[active,on_leave,terminated,suspended]'
        ],
        'worker_type'     => [
            'label' => 'Tipo de Trabajador',
            'rules' => 'required|in_list[planta,temporal,proyecto,honorarios]'
        ],
        'payroll_type'    => [
            'label' => 'Tipo de Nómina',
            'rules' => 'required|in_list[quincenal,mensual,semanal]'
        ],
        'payment_method'  => [
            'label' => 'Forma de Pago',
            'rules' => 'required|in_list[transferencia,efectivo,cheque]'
        ],
        'corporate_email' => [
            'label' => 'Email Corporativo',
            'rules' => 'permit_empty|valid_email'
        ]
    ];
    
    protected $validationMessages   = [
        'employee_number' => [
            'is_unique' => 'Este número de empleado ya está asignado a otro trabajador.'
        ],
        'current_salary' => [
            'numeric' => 'El salario mensual debe ser un valor numérico.',
            'required' => 'El salario base mensual es obligatorio.'
        ],
        'daily_salary' => [
            'numeric' => 'El salario diario debe ser un valor numérico.',
            'required' => 'El salario diario integrado (SDI) es obligatorio.'
        ]
    ];
    
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
