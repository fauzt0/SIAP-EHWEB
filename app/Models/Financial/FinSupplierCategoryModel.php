<?php

namespace App\Models\Financial;

use CodeIgniter\Model;
use App\Traits\DataTableTrait;

/**
 * FinSupplierCategoryModel
 *
 * Catálogo administrable de categorías de proveedores (fin_supplier_categories).
 * Soft-delete obligatorio para preservar históricos de proveedores.
 */
class FinSupplierCategoryModel extends Model
{
    use DataTableTrait;

    protected $table            = 'fin_supplier_categories';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'description', 'active'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // §5/§16 — propiedades requeridas por el plan (el trait lee $datatableConfig)
    protected $column_order  = [null, 'name', 'description', 'active', null];
    protected $column_search = ['name', 'description'];

    protected $datatableConfig = [
        'column_order'  => [null, 'name', 'description', 'active', null],
        'column_search' => ['name', 'description'],
        'order'         => ['id' => 'desc'],
    ];

    protected $validationRules = [
        'id' => [
            'label' => 'ID',
            'rules' => 'permit_empty|is_natural_no_zero',
        ],
        'name' => [
            'label' => 'Nombre',
            'rules' => 'required|max_length[100]|is_unique[fin_supplier_categories.name,id,{id}]',
        ],
        'description' => [
            'label' => 'Descripción',
            'rules' => 'permit_empty',
        ],
        'active' => [
            'label' => 'Estatus',
            'rules' => 'permit_empty|in_list[0,1]',
        ],
    ];

    protected $validationMessages = [
        'name' => [
            'required'   => 'El nombre de la categoría es obligatorio.',
            'max_length' => 'El nombre no puede exceder los 100 caracteres.',
            'is_unique'  => 'Ya existe una categoría con ese nombre.',
        ],
    ];

    /**
     * _get_datatables_query
     *
     * Incluye conteo de proveedores vinculados y filtro de estatus/papelera.
     */
    protected function _get_datatables_query($postData = [])
    {
        $this->withDeleted();

        $this->builder()
            ->select([
                'fin_supplier_categories.*',
                '(SELECT COUNT(*) FROM fin_suppliers WHERE fin_suppliers.category_id = fin_supplier_categories.id AND fin_suppliers.deleted_at IS NULL) AS suppliers_count',
            ]);

        $filterStatus = $postData['filter_status'] ?? '';

        if ($filterStatus === 'deleted') {
            $this->builder()->where('fin_supplier_categories.deleted_at IS NOT NULL', null, false);
        } else {
            $this->builder()->where('fin_supplier_categories.deleted_at IS NULL', null, false);

            if ($filterStatus === '1' || $filterStatus === '0') {
                $this->builder()->where('fin_supplier_categories.active', (int) $filterStatus);
            }
        }

        $this->_apply_datatables_filters($postData);
    }

    /**
     * count_all
     *
     * Total de registros según el mismo alcance de filter_status (sin búsqueda).
     */
    public function count_all($where = [], array $postData = [])
    {
        $builder = $this->db->table($this->table);

        $filterStatus = $postData['filter_status'] ?? '';
        if ($filterStatus === 'deleted') {
            $builder->where('deleted_at IS NOT NULL', null, false);
        } else {
            $builder->where('deleted_at IS NULL', null, false);
            if ($filterStatus === '1' || $filterStatus === '0') {
                $builder->where('active', (int) $filterStatus);
            }
        }

        if (! empty($where)) {
            $builder->where($where);
        }

        return $builder->countAllResults();
    }

    /**
     * get_datatables_total
     *
     * Alias pedido por el plan; equivale a count_all con el mismo filtro.
     */
    public function get_datatables_total($postData = []): int
    {
        return $this->count_all([], is_array($postData) ? $postData : []);
    }

    /**
     * getForSelect
     *
     * Categorías activas para Select2 (id => name).
     */
    public function getForSelect(): array
    {
        $rows = $this->where('active', 1)
            ->orderBy('name', 'ASC')
            ->findAll();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row->id] = $row->name;
        }

        return $out;
    }

    /**
     * hasSuppliers
     *
     * True si hay proveedores (no eliminados) vinculados a la categoría.
     */
    public function hasSuppliers(int $id): bool
    {
        return $this->db->table('fin_suppliers')
            ->where('category_id', $id)
            ->where('deleted_at', null)
            ->countAllResults() > 0;
    }

    /**
     * restore
     *
     * Restaura una categoría soft-deleted. Usa el builder porque
     * deleted_at no está en allowedFields.
     */
    public function restore(int $id): bool
    {
        $category = $this->withDeleted()->find($id);
        if (! $category || empty($category->deleted_at)) {
            return false;
        }

        $this->builder()->where('id', $id)->update(['deleted_at' => null]);

        return $this->db->affectedRows() > 0;
    }
}
