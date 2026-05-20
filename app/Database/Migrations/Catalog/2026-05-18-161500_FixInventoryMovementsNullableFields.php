<?php

namespace App\Database\Migrations\Catalog;

use CodeIgniter\Database\Migration;

class FixInventoryMovementsNullableFields extends Migration
{
    public function up()
    {
        // 1. Quitar llaves foráneas temporalmente para modificar columnas
        $this->db->query('ALTER TABLE inventory_movements DROP FOREIGN KEY inventory_movements_target_org_branch_id_foreign');

        // 2. Modificar columnas para permitir NULL
        $fields = [
            'target_org_branch_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'reference_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
            ],
        ];
        $this->forge->modifyColumn('inventory_movements', $fields);

        // 3. Restaurar llave foránea
        $this->db->query('ALTER TABLE inventory_movements ADD CONSTRAINT inventory_movements_target_org_branch_id_foreign FOREIGN KEY (target_org_branch_id) REFERENCES org_branches(id) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down()
    {
        // No es recomendable volver a NOT NULL si ya hay datos NULL, pero para cumplir el estándar:
        $this->db->query('ALTER TABLE inventory_movements DROP FOREIGN KEY inventory_movements_target_org_branch_id_foreign');
        
        $fields = [
            'target_org_branch_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'reference_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
        ];
        $this->forge->modifyColumn('inventory_movements', $fields);

        $this->db->query('ALTER TABLE inventory_movements ADD CONSTRAINT inventory_movements_target_org_branch_id_foreign FOREIGN KEY (target_org_branch_id) REFERENCES org_branches(id) ON DELETE CASCADE ON UPDATE CASCADE');
    }
}
