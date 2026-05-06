<?php

namespace App\Models\Catalog;

use CodeIgniter\Model;

class CatalogProductStockModel extends Model
{
    protected $table            = 'catalog_product_stock';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'product_id', 'org_branches_id', 'stock', 'min_alert'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getStock(int $productId, int $branchId = null)
    {
        $query = $this->where('product_id', $productId);
        if ($branchId) {
            return $query->where('org_branches_id', $branchId)->first();
        }
        return $query->findAll();
    }
}
