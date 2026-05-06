<?php

namespace App\Models\Catalog;

use CodeIgniter\Model;

class CatalogAttributeModel extends Model
{
    protected $table            = 'catalog_product_attributes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'product_id', 'attr_key', 'attr_value', 'is_highlight'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getByProduct(int $productId)
    {
        return $this->where('product_id', $productId)->findAll();
    }
}
