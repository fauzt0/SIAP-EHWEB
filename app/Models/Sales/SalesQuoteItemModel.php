<?php

declare(strict_types=1);

namespace App\Models\Sales;

use CodeIgniter\Model;

class SalesQuoteItemModel extends Model
{
    protected $table            = 'sales_quote_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'quote_id', 'product_id', 'plan_id', 'item_name', 'unit_price',
        'quantity', 'billing_cycle', 'line_total',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
