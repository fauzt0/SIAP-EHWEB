<?php

namespace App\Database\Migrations\Financial;

use CodeIgniter\Database\Migration;

/**
 * Crea la tabla fin_supplier_categories y siembra las 4 categorías
 * equivalentes al enum histórico de supplier_type.
 */
class CreateFinSupplierCategories extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'description' => ['type' => 'TEXT', 'null' => true],
            'active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('fin_supplier_categories', true);

        $now = date('Y-m-d H:i:s');
        $this->db->table('fin_supplier_categories')->insertBatch([
            ['name' => 'Infraestructura', 'description' => 'Proveedores de infraestructura', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Hardware',        'description' => 'Proveedores de hardware',        'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Servicios',       'description' => 'Proveedores de servicios',       'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Administrativo',  'description' => 'Proveedores administrativos',    'active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('fin_supplier_categories', true);
    }
}
