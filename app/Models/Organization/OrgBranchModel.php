<?php

namespace App\Models\Organization;

use App\Traits\DataTableTrait;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Model;

class OrgBranchModel extends Model
{
    use DataTableTrait;

    protected $table            = 'org_branches';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'company_id', 'name', 'commercial_name', 'legal_name', 'tax_id',
        'branch_code', 'is_main', 'address', 'phone', 'email', 'website_url',
        'pos_printer_name', 'logo_path', 'active',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'company_id' => [
            'label' => 'Compañía',
            'rules' => 'required|is_natural_no_zero',
        ],
        'name' => [
            'label' => 'Nombre de la Sucursal',
            'rules' => 'required|max_length[100]',
        ],
        'commercial_name' => [
            'label' => 'Nombre Comercial',
            'rules' => 'required|max_length[255]',
        ],
        'legal_name' => [
            'label' => 'Razón Social',
            'rules' => 'permit_empty|max_length[255]',
        ],
        'tax_id' => [
            'label' => 'RFC',
            'rules' => 'permit_empty|max_length[20]',
        ],
        'website_url' => [
            'label' => 'Sitio Web',
            'rules' => 'permit_empty|valid_url|max_length[255]',
        ],
        'branch_code' => [
            'label' => 'Código',
            'rules' => 'required|max_length[10]|is_unique[org_branches.branch_code,id,{id}]',
        ],
        'email' => [
            'label' => 'Correo',
            'rules' => 'permit_empty|valid_email|max_length[100]',
        ],
        'phone' => [
            'label' => 'Teléfono',
            'rules' => 'permit_empty|max_length[20]',
        ],
        'active' => [
            'label' => 'Estatus',
            'rules' => 'permit_empty|in_list[0,1]',
        ],
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'El nombre de la sucursal es obligatorio.',
        ],
        'branch_code' => [
            'required'  => 'El código de sucursal es obligatorio.',
            'is_unique' => 'Ese código de sucursal ya está registrado.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    protected $datatableConfig = [
        'column_order'  => [null, 'name', 'branch_code', 'phone', 'email', 'active', null],
        'column_search' => ['name', 'commercial_name', 'branch_code', 'tax_id', 'phone', 'email'],
        'order'         => ['name' => 'ASC'],
    ];

    protected function _get_datatables_query($postData = [])
    {
        if (isset($postData['filter_active']) && $postData['filter_active'] !== '') {
            $this->builder()->where($this->table . '.active', (int) $postData['filter_active']);
        }

        $this->_apply_datatables_filters($postData);
    }

    public function getStats(): array
    {
        $table = $this->table;

        $total = (int) $this->db->table($table)
            ->where('deleted_at IS NULL', null, false)
            ->countAllResults();

        $active = (int) $this->db->table($table)
            ->where('deleted_at IS NULL', null, false)
            ->where('active', 1)
            ->countAllResults();

        $main = (int) $this->db->table($table)
            ->where('deleted_at IS NULL', null, false)
            ->where('is_main', 1)
            ->countAllResults();

        return [
            'total'    => $total,
            'active'   => $active,
            'inactive' => max(0, $total - $active),
            'main'     => $main,
        ];
    }

    public function getActive(): array
    {
        return $this->where('active', 1)->orderBy('name', 'ASC')->findAll();
    }

    /**
     * ID de la sucursal principal (is_main) o la primera activa.
     */
    public function getMainBranchId(): ?int
    {
        $main = $this->where('active', 1)->where('is_main', 1)->first();
        if ($main) {
            return (int) $main->id;
        }

        $first = $this->where('active', 1)->orderBy('id', 'ASC')->first();

        return $first ? (int) $first->id : null;
    }

    /**
     * Datos para encabezados de factura, OC y PDF (por sucursal).
     */
    public function getFiscalProfile(int $branchId): ?object
    {
        $branch = $this->find($branchId);
        if (! $branch) {
            return null;
        }

        return (object) [
            'branch_id'        => (int) $branch->id,
            'commercial_name'  => $branch->commercial_name ?: $branch->name,
            'legal_name'       => $branch->legal_name,
            'tax_id'           => $branch->tax_id,
            'phone'            => $branch->phone,
            'email'            => $branch->email,
            'address'          => $branch->address,
            'website_url'      => $branch->website_url ?? null,
            'logo_path'        => $branch->logo_path,
            'branch_code'      => $branch->branch_code,
        ];
    }

    public function getActiveForForm(?int $includeBranchId = null): array
    {
        $branches = $this->getActive();

        if ($includeBranchId === null || $includeBranchId <= 0) {
            return $branches;
        }

        foreach ($branches as $branch) {
            if ((int) $branch->id === $includeBranchId) {
                return $branches;
            }
        }

        $assigned = $this->find($includeBranchId);
        if ($assigned) {
            $branches[] = $assigned;
            usort($branches, static fn ($a, $b) => strcmp($a->name, $b->name));
        }

        return $branches;
    }

    /**
     * Alta o edición de sucursal con logo opcional y sucursal principal única.
     *
     * @return int|false
     */
    public function saveBranch(array $data, ?UploadedFile $logoFile = null): int|false
    {
        $id       = ! empty($data['id']) ? (int) $data['id'] : null;
        $existing = $id ? $this->find($id) : null;

        if ($id && ! $existing) {
            return false;
        }

        unset($data['id']);

        if ($logoFile !== null) {
            helper('upload');
            $stored = store_public_upload($logoFile, 'organization', $existing->logo_path ?? null);
            if ($stored !== null) {
                $data['logo_path'] = $stored;
            }
        }

        $data['is_main'] = ! empty($data['is_main']) ? 1 : 0;
        $data['active']  = ! empty($data['active']) ? 1 : 0;

        if (empty($data['commercial_name']) && ! empty($data['name'])) {
            $data['commercial_name'] = $data['name'];
        }

        $this->db->transStart();

        if ($id) {
            $this->setValidationRule(
                'branch_code',
                'required|max_length[10]|is_unique[org_branches.branch_code,id,' . $id . ']'
            );
            $ok = (bool) $this->update($id, $data);
        } else {
            $this->setValidationRule(
                'branch_code',
                'required|max_length[10]|is_unique[org_branches.branch_code]'
            );
            $id  = $this->insert($data);
            $ok  = (bool) $id;
        }

        if ($ok && ! empty($data['is_main'])) {
            $this->where('company_id', $data['company_id'])
                ->where('id !=', $id)
                ->set(['is_main' => 0])
                ->update();
        }

        $this->db->transComplete();

        return $this->db->transStatus() ? (int) $id : false;
    }

    public function setActiveStatus(int $id, int $status): bool
    {
        if (! in_array($status, [0, 1], true)) {
            return false;
        }

        return $this->update($id, ['active' => $status]);
    }

    public function removeBranch(int $id): bool
    {
        return (bool) $this->delete($id);
    }

    public function count_all($where = [], array $postData = []): int
    {
        $builder = $this->db->table($this->table);
        $builder->where('deleted_at IS NULL', null, false);

        if (! empty($where)) {
            $builder->where($where);
        }

        return $builder->countAllResults();
    }
}
