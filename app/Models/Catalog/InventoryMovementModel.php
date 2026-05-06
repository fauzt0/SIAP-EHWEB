<?php

namespace App\Models\Catalog;

use CodeIgniter\Model;

class InventoryMovementModel extends Model
{
    protected $table            = 'inventory_movements';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'catalog_product_id', 'type', 'reference_id', 'org_branch_id', 
        'target_org_branch_id', 'quantity', 'notes'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at'; // updated_at existe en migración original
    protected $deletedField  = 'deleted_at';

    public function getHistory(int $productId)
    {
        return $this->where('catalog_product_id', $productId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
}
