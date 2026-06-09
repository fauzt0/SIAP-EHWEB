<?php

namespace App\Database\Migrations\Accounting;

use CodeIgniter\Database\Migration;

class CreateSupplierModuleTables extends Migration
{
    public function up()
    {
        $this->db->disableForeignKeyChecks();

        // 1. fin_supplier_contacts - Contactos adicionales del proveedor
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'fin_supplier_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'contact_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'job_title' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'is_primary' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('fin_supplier_id');
        $this->forge->addForeignKey(
            'fin_supplier_id',
            'fin_suppliers',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->forge->createTable('fin_supplier_contacts', true);

        // 2. fin_purchase_orders - Órdenes de compra
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'fin_supplier_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'po_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'sent', 'approved', 'completed', 'cancelled'],
                'default'    => 'draft',
            ],
            'total_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
            ],
            'currency' => [
                'type'       => 'VARCHAR',
                'constraint' => 3,
                'default'    => 'MXN',
            ],
            'issue_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'delivery_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('po_number');
        $this->forge->addKey('fin_supplier_id');
        $this->forge->addKey('status');
        $this->forge->addForeignKey(
            'fin_supplier_id',
            'fin_suppliers',
            'id',
            'RESTRICT',
            'CASCADE'
        );
        $this->forge->createTable('fin_purchase_orders', true);

        // 3. fin_purchase_order_items - Partidas de la orden de compra
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'fin_purchase_order_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'catalog_product_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'quantity' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'unit_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
            ],
            'total_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('fin_purchase_order_id');
        $this->forge->addForeignKey(
            'fin_purchase_order_id',
            'fin_purchase_orders',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->forge->addForeignKey(
            'catalog_product_id',
            'catalog_products',
            'id',
            'SET NULL',
            'SET NULL'
        );
        $this->forge->createTable('fin_purchase_order_items', true);

        $this->db->enableForeignKeyChecks();
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();

        $this->forge->dropTable('fin_purchase_order_items', true);
        $this->forge->dropTable('fin_purchase_orders', true);
        $this->forge->dropTable('fin_supplier_contacts', true);

        $this->db->enableForeignKeyChecks();
    }
}
