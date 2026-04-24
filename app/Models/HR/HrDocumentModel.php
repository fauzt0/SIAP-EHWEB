<?php

namespace App\Models\HR;

use CodeIgniter\Model;

class HrDocumentModel extends Model
{
    protected $table            = 'hr_documents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'document_type_id',
        'file_name',
        'file_path',
        'upload_date',
        'notes'
    ];

    // Dates
    protected $useTimestamps = false; // We use a custom upload_date

    // Validation
    protected $validationRules      = [
        'user_id'          => 'required|is_natural_no_zero',
        'document_type_id' => 'permit_empty|is_natural_no_zero',
        'file_name'        => 'required|max_length[255]',
        'file_path'        => 'required|max_length[255]',
        'upload_date'      => 'permit_empty|valid_date'
    ];
    
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
