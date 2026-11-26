<?php

declare(strict_types=1);

namespace App\Models\Projects;

use CodeIgniter\Model;

class PrmProjectItemModel extends Model
{
    protected $table            = 'prm_project_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'project_id', 'product_id', 'quote_item_id', 'order_item_id', 'item_name',
        'catalog_price', 'unit_price', 'quantity', 'billing_cycle', 'status',
    ];
    protected $useTimestamps = true;
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'project_id' => 'required|is_natural_no_zero',
        'item_name'  => 'required|max_length[255]',
        'unit_price' => 'permit_empty|decimal',
    ];
}
