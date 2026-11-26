<?php

declare(strict_types=1);

namespace App\Models\CRM;

use CodeIgniter\Model;

class CrmClientContactModel extends Model
{
    protected $table            = 'crm_client_contacts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'client_id', 'contact_type', 'name', 'email', 'phone', 'is_primary', 'notes',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'client_id'    => 'required|is_natural_no_zero',
        'name'         => 'required|max_length[150]',
        'contact_type' => 'required|in_list[buyer,technical,billing,admin]',
        'email'        => 'permit_empty|valid_email',
    ];
}
