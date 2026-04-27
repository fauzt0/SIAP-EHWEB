<?php

namespace App\Models\HR;

use CodeIgniter\Model;

class WorkerContractModel extends Model
{
    protected $table            = 'hr_contracts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'profile_id',
        'template_id',
        'contract_type_id',
        'content_snapshot',
        'reason',
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
        'profile_id'       => 'required|is_natural_no_zero',
        'content_snapshot' => 'required',
        'status'           => 'required|in_list[active,archived,draft]',
    ];
    protected $validationMessages   = [
        'profile_id' => [
            'required' => 'El ID del trabajador es obligatorio.',
        ],
        'content_snapshot' => [
            'required' => 'El contenido del contrato es obligatorio.',
        ],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Obtiene el historial de contratos de un trabajador específico.
     * 
     * @param int $profileId
     * @return array
     */
    public function getHistoryByProfile(int $profileId)
    {
        return $this->select('hr_contracts.*, hr_cat_contract_types.name as contract_type_name, hr_cat_contract_templates.name as template_name')
                    ->join('hr_cat_contract_types', 'hr_cat_contract_types.id = hr_contracts.contract_type_id', 'left')
                    ->join('hr_cat_contract_templates', 'hr_cat_contract_templates.id = hr_contracts.template_id', 'left')
                    ->where('hr_contracts.profile_id', $profileId)
                    ->orderBy('hr_contracts.created_at', 'DESC')
                    ->findAll();
    }
}
