<?php

namespace App\Database\Migrations\Catalog;

use CodeIgniter\Database\Migration;

class AddTimestampsToCatalog extends Migration
{
    public function up()
    {
        $tables = [
            'catalog_categories',
            'catalog_products',
            'catalog_product_plans',
            'catalog_product_attributes',
            'catalog_product_relations',
            'marketing_discounts'
        ];

        $fields = [
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ];

        foreach ($tables as $table) {
            $this->forge->addColumn($table, $fields);
        }

        // Para las tablas que ya tenían updated_at
        $this->forge->addColumn('catalog_product_stock', [
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addColumn('inventory_movements', [
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
    }

    public function down()
    {
        $tables = [
            'catalog_categories',
            'catalog_products',
            'catalog_product_plans',
            'catalog_product_attributes',
            'catalog_product_relations',
            'marketing_discounts'
        ];

        foreach ($tables as $table) {
            $this->forge->dropColumn($table, ['created_at', 'updated_at', 'deleted_at']);
        }

        $this->forge->dropColumn('catalog_product_stock', ['created_at', 'deleted_at']);
        $this->forge->dropColumn('inventory_movements', ['created_at', 'deleted_at']);
    }
}
