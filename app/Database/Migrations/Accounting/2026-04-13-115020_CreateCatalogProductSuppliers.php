<?php

namespace App\Database\Migrations\Accounting;

use CodeIgniter\Database\Migration;

class CreateCatalogProductSuppliers extends Migration
{
    public function up()
    {
        $this->db->disableForeignKeyChecks();

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'catalog_product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'fin_supplier_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'purchase_cost' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'purchase_currency' => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'USD'],
            'supplier_due_date' => ['type' => 'DATE', 'null' => true],
            'is_auto_renew' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('catalog_product_id', 'catalog_products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('fin_supplier_id', 'fin_suppliers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('catalog_product_suppliers', true);
        
        $this->db->enableForeignKeyChecks();
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();
        $this->forge->dropTable('catalog_product_suppliers');
        $this->db->enableForeignKeyChecks();
    }
}
