<?php

declare(strict_types=1);

namespace App\Models\CRM;

use CodeIgniter\Model;

class CrmBillingProfileModel extends Model
{
    protected $table            = 'crm_billing_profiles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'client_id', 'legal_name', 'rfc_tax_id', 'tax_regime',
        'postal_code', 'address', 'cfdi_use', 'is_default',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'client_id'  => 'required|is_natural_no_zero',
        'legal_name' => 'required|max_length[255]',
        'rfc_tax_id' => 'required|max_length[20]',
    ];
}
