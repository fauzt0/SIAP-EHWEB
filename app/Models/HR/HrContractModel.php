<?php

namespace App\Models\HR;

use CodeIgniter\Model;

class HrContractModel extends Model
{
    protected $table            = 'hr_contracts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'contract_type_id',
        'start_date',
        'end_date',
        'salary_at_signing',
        'contract_content',
        'file_path',
        'status'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'user_id'           => 'required|is_natural_no_zero',
        'contract_type_id'  => 'permit_empty|is_natural_no_zero',
        'start_date'        => 'required|valid_date',
        'end_date'          => 'permit_empty|valid_date',
        'salary_at_signing' => 'required|numeric|greater_than_equal_to[0]',
        'contract_content'  => 'required',
        'file_path'         => 'permit_empty|max_length[255]',
        'status'            => 'required|in_list[active,expired,renewed,cancelled]'
    ];
    
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
