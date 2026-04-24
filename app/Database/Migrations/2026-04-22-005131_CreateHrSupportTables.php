<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHrSupportTables extends Migration
{
    public function up()
    {
        // 1. hr_contracts (Histórico de Contratos)
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'contract_type_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'start_date'         => ['type' => 'DATE'],
            'end_date'           => ['type' => 'DATE', 'null' => true],
            'salary_at_signing'  => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'contract_content'   => ['type' => 'LONGTEXT'],
            'file_path'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'             => ['type' => 'ENUM', 'constraint' => ['active', 'expired', 'renewed', 'cancelled'], 'default' => 'active'],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('contract_type_id', 'hr_cat_contract_types', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('hr_contracts');

        // 2. hr_documents (Expediente Digital)
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'document_type_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'file_name'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'file_path'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'upload_date'      => ['type' => 'DATETIME', 'null' => true],
            'notes'            => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('document_type_id', 'hr_cat_document_types', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('hr_documents');
    }

    public function down()
    {
        $this->forge->dropTable('hr_documents', true);
        $this->forge->dropTable('hr_contracts', true);
    }
}
