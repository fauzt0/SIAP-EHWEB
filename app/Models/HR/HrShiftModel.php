<?php

namespace App\Models\HR;

use CodeIgniter\Model;

class HrShiftModel extends Model
{
    protected $table            = 'hr_cat_shifts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'start_time',
        'end_time',
        'grace_period',
        'work_days',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'name'       => 'required|max_length[100]',
        'start_time' => 'required',
        'end_time'   => 'required',
        'work_days'  => 'required|min_length[1]',
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'El nombre del turno es obligatorio.',
        ],
        'work_days' => [
            'required' => 'Debe seleccionar al menos un día laboral.',
        ],
    ];
}
