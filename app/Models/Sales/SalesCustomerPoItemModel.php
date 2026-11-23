<?php

declare(strict_types=1);

namespace App\Models\Sales;

use CodeIgniter\Model;

class SalesCustomerPoItemModel extends Model
{
    protected $table            = 'sales_customer_po_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'customer_po_id', 'order_item_id', 'line_code', 'item_name',
        'quantity', 'unit_price', 'billing_cycle',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
