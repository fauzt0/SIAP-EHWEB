<?php

namespace App\Models\Catalog;

use CodeIgniter\Model;
use App\Traits\DataTableTrait;

class CatalogCategoryModel extends Model
{
    use DataTableTrait;

    protected $table            = 'catalog_categories';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'parent_id', 'name', 'slug', 'description', 'icon', 'active'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $datatableConfig = [
        'column_order'  => ['id', 'name', 'parent_id', 'active', null],
        'column_search' => ['name', 'slug', 'description'],
        'order'         => ['id' => 'asc']
    ];

    /**
     * Obtiene categorías con el nombre del padre
     */
    protected function _get_datatables_query($postData = [])
    {
        $this->builder()
            ->select('catalog_categories.*, p.name as parent_name')
            ->join('catalog_categories p', 'p.id = catalog_categories.parent_id', 'left');
            
        $this->_apply_datatables_filters($postData);
    }

    /**
     * Obtiene el árbol jerárquico
     */
    public function getHierarchy($parentId = null)
    {
        return $this->where('parent_id', $parentId)
                    ->where('active', 1)
                    ->findAll();
    }
}
