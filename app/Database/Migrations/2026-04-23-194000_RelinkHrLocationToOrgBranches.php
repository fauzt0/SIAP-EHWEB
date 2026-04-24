<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RelinkHrLocationToOrgBranches extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // 1. Eliminar la FK antigua que apunta a hr_cat_locations
        $db->query('ALTER TABLE hr_employment_data DROP FOREIGN KEY hr_employment_data_location_id_foreign');

        // 2. La columna location_id ya existe y tiene el tipo correcto (INT UNSIGNED NULL),
        //    solo necesitamos agregar la nueva FK apuntando a org_branches
        $db->query('ALTER TABLE hr_employment_data ADD CONSTRAINT fk_hr_employment_branch FOREIGN KEY (location_id) REFERENCES org_branches(id) ON UPDATE CASCADE ON DELETE SET NULL');

        // 3. Eliminar la tabla redundante hr_cat_locations (estaba vacía)
        $this->forge->dropTable('hr_cat_locations', true);
    }

    public function down()
    {
        $db = \Config\Database::connect();

        // Revertir: quitar FK a org_branches
        $db->query('ALTER TABLE hr_employment_data DROP FOREIGN KEY fk_hr_employment_branch');

        // Recrear hr_cat_locations
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'TEXT', 'null' => true],
            'address'     => ['type' => 'TEXT', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('hr_cat_locations');

        // Restaurar FK original a hr_cat_locations
        $db->query('ALTER TABLE hr_employment_data ADD CONSTRAINT fk_employment_profile_location FOREIGN KEY (location_id) REFERENCES hr_cat_locations(id) ON UPDATE CASCADE ON DELETE SET NULL');
    }
}
