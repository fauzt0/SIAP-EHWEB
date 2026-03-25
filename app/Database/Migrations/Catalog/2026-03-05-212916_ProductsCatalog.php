<?php

namespace App\Database\Migrations\Catalog;

use CodeIgniter\Database\Migration;

class ProductsCatalog extends Migration
{
    public function up()
    {        
        $this->db->disableForeignKeyChecks();

        // 1. catalog_categories (Con slug y active)
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'parent_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'slug'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'TEXT', 'null' => true],
            'icon'        => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'fas fa-box'],
            'active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('parent_id', 'catalog_categories', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('catalog_categories', true);

        // 2. catalog_products (Con barcode, internal_name y product_type)
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'category_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'sku'              => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'barcode'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'internal_name'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'commercial_name'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'description_short'=> ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description_long' => ['type' => 'TEXT', 'null' => true],
            'product_type'     => ['type' => 'ENUM', 'constraint' => ['service', 'physical', 'digital'], 'default' => 'service'],
            'active'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('category_id', 'catalog_categories', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('catalog_products', true);

        // 3. catalog_product_plans (Precios y recurrencia)
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'product_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'plan_name'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'billing_cycle'  => ['type' => 'ENUM', 'constraint' => ['one_time', 'monthly', 'yearly', 'custom'], 'default' => 'monthly'],
            'sale_price'     => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'renewal_price'  => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'setup_fee'      => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'is_active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('product_id', 'catalog_products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('catalog_product_plans', true);

        // 4. catalog_product_attributes (RAM, Disco, etc.)
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'product_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'attr_key'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'attr_value'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'is_highlight' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('product_id', 'catalog_products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('catalog_product_attributes', true);

        // 5. catalog_product_relations (Bundles / Regalos - Corregida)
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'main_product_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'related_product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'relation_type'      => ['type' => 'ENUM', 'constraint' => ['bundle', 'gift', 'upsell'], 'default' => 'gift'],
            'override_price'     => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'duration_months'    => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('main_product_id', 'catalog_products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('related_product_id', 'catalog_products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('catalog_product_relations', true);

        // 6. marketing_discounts (Cupones y reglas)
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code'              => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'discount_type'     => ['type' => 'ENUM', 'constraint' => ['percentage', 'fixed'], 'default' => 'percentage'],
            'value'             => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'min_amount'        => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'max_discount'      => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
            'target'            => ['type' => 'ENUM', 'constraint' => ['global', 'category', 'product'], 'default' => 'global'],
            'target_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'starts_at'         => ['type' => 'DATETIME', 'null' => true],
            'ends_at'           => ['type' => 'DATETIME', 'null' => true],
            'usage_limit'       => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'apply_to_renewal'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('marketing_discounts', true);

        //creamos la tabla para los stocks en caso de existir
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'product_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'org_branches_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'stock'             => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'min_alert'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('product_id', 'catalog_products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('org_branches_id', 'org_branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('catalog_product_stock', true);

        //creamos la tabla de inventory_movements(kardex) para registrar entradas, salidas de productos y cambios de sucursal
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'catalog_product_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'type'              => ['type' => 'ENUM', 'constraint' => ['entry', 'exit', 'transfer'], 'default' => 'entry'],
            'reference_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'org_branch_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'target_org_branch_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],            
            'quantity'          => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],            
            'notes'             => ['type' => 'TEXT', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('catalog_product_id', 'catalog_products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('org_branch_id', 'org_branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('target_org_branch_id', 'org_branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('inventory_movements', true);



        ////
        $this->db->enableForeignKeyChecks();


    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();

        $this->forge->dropTable('catalog_categories');
        $this->forge->dropTable('catalog_products');
        $this->forge->dropTable('catalog_product_plans');
        $this->forge->dropTable('catalog_product_attributes');
        $this->forge->dropTable('catalog_product_relations');
        $this->forge->dropTable('marketing_discounts');

        $this->db->enableForeignKeyChecks();
    }
}
