<?php

namespace App\Models\Organization;

use CodeIgniter\Model;

class OrgCompanyModel extends Model
{
    protected $table            = 'org_company_profile';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'commercial_name', 'legal_name', 'tax_id', 'logo_path', 'favicon_path',
        'primary_email', 'primary_phone', 'website_url',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'commercial_name' => 'required|max_length[255]',
        'primary_email'   => 'required|valid_email|max_length[100]',
        'primary_phone'   => 'required|max_length[20]',
    ];

    /**
     * Obtiene el perfil de la compañía principal. Como solo debe haber uno, 
     * retorna el primero que encuentre o null si no existe.
     */
    public function getProfile()
    {
        return $this->first();
    }
}
