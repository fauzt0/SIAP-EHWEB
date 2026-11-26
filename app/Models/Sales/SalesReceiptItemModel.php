<?php

declare(strict_types=1);

namespace App\Models\Sales;

use CodeIgniter\Model;

class SalesReceiptItemModel extends Model
{
    protected $table            = 'sales_receipt_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'receipt_id', 'order_item_id', 'service_id', 'product_id', 'item_name',
        'billing_cycle', 'quantity', 'unit_price', 'line_total', 'plugin_ref',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
