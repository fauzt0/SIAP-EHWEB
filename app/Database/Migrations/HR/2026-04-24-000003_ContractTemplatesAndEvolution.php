<?php

namespace App\Database\Migrations\HR;

use CodeIgniter\Database\Migration;

class ContractTemplatesAndEvolution extends Migration
{
    public function up(): void
    {
        // 1. Crear tabla de Plantillas de Contrato
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'              => ['type' => 'VARCHAR', 'constraint' => 100],
            'description'       => ['type' => 'TEXT', 'null' => true],
            'content'           => ['type' => 'LONGTEXT'], // HTML con variables {{...}}
            'base_model'        => ['type' => 'ENUM', 'constraint' => ['lft', 'modern', 'classic', 'corporate'], 'default' => 'lft'],
            'is_default'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'header_logo'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'footer_text'       => ['type' => 'TEXT', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('hr_cat_contract_templates');

        // 2. Evolucionar la tabla hr_contracts (ajustar arquitectura)
        // Eliminamos la tabla anterior para recrearla correctamente vinculada a profile_id y con soporte de plantillas
        $this->forge->dropTable('hr_contracts', true);

        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'profile_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true], // Vínculo al trabajador, no al usuario
            'template_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'contract_type_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'content_snapshot'  => ['type' => 'LONGTEXT'], // El contrato final renderizado (con variables reemplazadas)
            'reason'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], // Ej: "Aumento salarial", "Alta"
            'file_path'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], // Path al PDF generado (opcional)
            'status'            => ['type' => 'ENUM', 'constraint' => ['active', 'archived', 'draft'], 'default' => 'active'],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('profile_id', 'hr_profiles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('template_id', 'hr_cat_contract_templates', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('contract_type_id', 'hr_cat_contract_types', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('hr_contracts');

        // 3. Ajustar hr_documents para que apunte a profile_id
        $this->forge->dropTable('hr_documents', true);
        $this->forge->addField([
            'id'               => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'profile_id'       => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true],
            'document_type_id' => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true, 'null' => true],
            'file_path'        => ['type' => 'VARCHAR',  'constraint' => 255],
            'notes'            => ['type' => 'TEXT',     'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('profile_id',       'hr_profiles',           'id', 'CASCADE',  'CASCADE');
        $this->forge->addForeignKey('document_type_id', 'hr_cat_document_types', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('hr_documents');
    }

    public function down(): void
    {
        $this->forge->dropTable('hr_cat_contract_templates', true);
        // No es necesario recrear las anteriores aquí ya que esto es una evolución destructiva controlada
    }
}
