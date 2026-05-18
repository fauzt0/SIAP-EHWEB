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
        'address', 'phone', 'email', 'pos_printer_name', 'logo_path', 'active',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'name' => 'required|max_length[100]',
    ];

    // Configuración para DataTables
    private $column_order  = [null, 'name', 'branch_code', 'phone', 'email', 'active', null];
    private $column_search = ['name', 'branch_code', 'phone', 'email'];

    /**
     * getActive()
     * Retorna solo las sucursales activas y no eliminadas, ordenadas por nombre.
     * Útil para poblar selectores en cualquier módulo.
     */
    public function getActive(): array
    {
        return $this->where('active', 1)->orderBy('name', 'ASC')->findAll();
    }

    private function _get_datatables_query($postData)
    {
        $this->builder()->select('*');

        $i = 0;
        foreach ($this->column_search as $item) {
            if (!empty($postData['search']['value'])) {
                if ($i === 0) {
                    $this->groupStart();
                    $this->like($item, $postData['search']['value']);
                } else {
                    $this->orLike($item, $postData['search']['value']);
                }
                if (count($this->column_search) - 1 == $i) {
                    $this->groupEnd();
                }
            }
            $i++;
        }

        if (isset($postData['order'])) {
            $this->orderBy($this->column_order[$postData['order']['0']['column']], $postData['order']['0']['dir']);
        } else {
            $this->orderBy('name', 'ASC');
        }
    }

    public function get_datatables($postData)
    {
        $this->_get_datatables_query($postData);
        if ($postData['length'] != -1) {
            $this->limit($postData['length'], $postData['start']);
        }
        return $this->findAll();
    }

    public function count_filtered($postData)
    {
        $this->_get_datatables_query($postData);
        return $this->countAllResults();
    }
}
