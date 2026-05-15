<?php

namespace App\Database\Migrations\Catalog;

use CodeIgniter\Database\Migration;

class CreateCatalogBrands extends Migration
{
    public function up()
    {
        // 1. Crear tabla de marcas
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'logo_path' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
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
        $this->forge->createTable('catalog_brands');

        // 2. Agregar brand_id a catalog_products
        $fields = [
            'brand_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'category_id',
            ],
        ];
        $this->forge->addColumn('catalog_products', $fields);
        
        // Agregar llave foránea
        $this->db->query("ALTER TABLE catalog_products ADD CONSTRAINT fk_catalog_products_brand FOREIGN KEY (brand_id) REFERENCES catalog_brands(id) ON DELETE SET NULL ON UPDATE CASCADE");
    }

    public function down()
    {
        $this->forge->dropForeignKey('catalog_products', 'fk_catalog_products_brand');
        $this->forge->dropColumn('catalog_products', 'brand_id');
        $this->forge->dropTable('catalog_brands');
    }
}
