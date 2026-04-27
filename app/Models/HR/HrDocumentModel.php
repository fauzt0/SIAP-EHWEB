<?php

namespace App\Models\HR;

use CodeIgniter\Model;

class HrDocumentModel extends Model
{
    protected $table            = 'hr_documents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'profile_id',
        'document_type_id',
        'file_path',
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
        'profile_id'       => 'required|is_natural_no_zero',
        'document_type_id' => 'permit_empty|is_natural_no_zero',
        'file_path'        => 'required|max_length[255]',
        'notes'            => 'permit_empty'
    ];
    
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
