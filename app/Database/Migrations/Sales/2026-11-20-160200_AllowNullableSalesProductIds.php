<?php

namespace App\Database\Migrations\Sales;

use CodeIgniter\Database\Migration;

class AllowNullableSalesProductIds extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('sales_order_items') && $this->db->fieldExists('product_id', 'sales_order_items')) {
            $this->db->query('ALTER TABLE sales_order_items MODIFY COLUMN product_id INT(11) UNSIGNED NULL');
        }
        if ($this->db->tableExists('customer_services') && $this->db->fieldExists('product_id', 'customer_services')) {
            $this->db->query('ALTER TABLE customer_services MODIFY COLUMN product_id INT(11) UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        // No revertimos a NOT NULL: puede haber renglones sin producto de catálogo.
    }
}
