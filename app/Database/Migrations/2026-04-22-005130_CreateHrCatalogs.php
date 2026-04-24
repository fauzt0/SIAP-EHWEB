<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHrCatalogs extends Migration
{
    public function up()
    {
        $commonFields = [
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'TEXT', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
        ];

        // 1. Departamentos
        $this->forge->addField($commonFields);
        $this->forge->addKey('id', true);
        $this->forge->createTable('hr_cat_departments');

        // 2. Puestos
        $this->forge->addField($commonFields);
        $this->forge->addKey('id', true);
        $this->forge->createTable('hr_cat_jobs');

        // 3. Ubicaciones / Sucursales
        $locationFields = $commonFields;
        $locationFields['address'] = ['type' => 'TEXT', 'null' => true];
        $this->forge->addField($locationFields);
        $this->forge->addKey('id', true);
        $this->forge->createTable('hr_cat_locations');

        // 4. Tipos de Contrato
        $this->forge->addField($commonFields);
        $this->forge->addKey('id', true);
        $this->forge->createTable('hr_cat_contract_types');

        // 5. Tipos de Documento
        $this->forge->addField($commonFields);
        $this->forge->addKey('id', true);
        $this->forge->createTable('hr_cat_document_types');
    }

    public function down()
    {
        $this->forge->dropTable('hr_cat_departments', true);
        $this->forge->dropTable('hr_cat_jobs', true);
        $this->forge->dropTable('hr_cat_locations', true);
        $this->forge->dropTable('hr_cat_contract_types', true);
        $this->forge->dropTable('hr_cat_document_types', true);
    }
}
