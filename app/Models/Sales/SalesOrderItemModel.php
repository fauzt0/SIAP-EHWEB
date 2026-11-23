<?php

declare(strict_types=1);

namespace App\Models\Sales;

use CodeIgniter\Model;

class SalesOrderItemModel extends Model
{
    protected $table            = 'sales_order_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'order_id', 'product_id', 'plan_id', 'item_name', 'unit_price', 'quantity',
        'period_start', 'period_end', 'service_id', 'discount_amount', 'tax_amount',
        'billing_cycle', 'activation_status', 'line_code', 'product_type',
        'fulfillment_status', 'plugin_ref',
    ];
    protected $useTimestamps = false;
}
