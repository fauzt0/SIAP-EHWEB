<?php

namespace App\Database\Migrations\Catalog;

use CodeIgniter\Database\Migration;

class AddBusinessUnitIdToCatalogProducts extends Migration
{
    public function up()
    {
        $fields = [
            'business_unit_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'brand_id',
            ],
        ];
        $this->forge->addColumn('catalog_products', $fields);

        // Asignar sucursal principal (o la primera activa) a productos existentes
        $branch = $this->db->table('org_branches')
            ->where('active', 1)
            ->where('deleted_at', null)
            ->orderBy('is_main', 'DESC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getRow();

        if ($branch) {
            $this->db->table('catalog_products')
                ->where('business_unit_id', null)
                ->update(['business_unit_id' => $branch->id]);
        }

        $this->db->query(
            'ALTER TABLE catalog_products MODIFY business_unit_id INT(11) UNSIGNED NOT NULL'
        );

        $this->db->query(
            'ALTER TABLE catalog_products ADD CONSTRAINT fk_catalog_products_business_unit '
            . 'FOREIGN KEY (business_unit_id) REFERENCES org_branches(id) '
            . 'ON DELETE RESTRICT ON UPDATE CASCADE'
        );
    }

    public function down()
    {
        $this->db->query('ALTER TABLE catalog_products DROP FOREIGN KEY fk_catalog_products_business_unit');
        $this->forge->dropColumn('catalog_products', 'business_unit_id');
    }
}
