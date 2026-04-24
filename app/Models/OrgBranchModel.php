<?php

namespace App\Models;

use CodeIgniter\Model;

class OrgBranchModel extends Model
{
    protected $table            = 'org_branches';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'company_id', 'name', 'branch_code', 'is_main',
        'address', 'phone', 'email', 'pos_printer_name', 'active',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'name' => 'required|max_length[100]',
    ];

    /**
     * getActive()
     * Retorna solo las sucursales activas y no eliminadas, ordenadas por nombre.
     * Útil para poblar selectores en cualquier módulo.
     */
    public function getActive(): array
    {
        return $this->where('active', 1)->orderBy('name', 'ASC')->findAll();
    }
}
