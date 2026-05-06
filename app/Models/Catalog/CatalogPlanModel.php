<?php

namespace App\Models\Catalog;

use CodeIgniter\Model;

class CatalogPlanModel extends Model
{
    protected $table            = 'catalog_product_plans';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'product_id', 'plan_name', 'billing_cycle', 'sale_price', 
        'renewal_price', 'setup_fee', 'is_active'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Obtiene planes activos de un producto
     */
    public function getByProduct(int $productId)
    {
        return $this->where('product_id', $productId)
                    ->where('is_active', 1)
                    ->findAll();
    }
}
