<?php

namespace App\Database\Migrations\Catalog;

use CodeIgniter\Database\Migration;

class CreateCatalogProductImages extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'product_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'path' => [
                'type'           => 'VARCHAR',
                'constraint'     => 255,
            ],
            'is_main' => [
                'type'           => 'TINYINT',
                'constraint'     => 1,
                'default'        => 0,
            ],
            'created_at' => [
                'type'           => 'DATETIME',
                'null'           => true,
            ],
            'updated_at' => [
                'type'           => 'DATETIME',
                'null'           => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('product_id', 'catalog_products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('catalog_product_images', true);
    }

    public function down()
    {
        $this->forge->dropTable('catalog_product_images', true);
    }
}
