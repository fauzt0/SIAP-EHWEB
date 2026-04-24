<?php

namespace App\Database\Migrations\HR;

use CodeIgniter\Database\Migration;

/**
 * CreateHrMainTables
 * ─────────────────────────────────────────────────────────────────────────────
 * Crea las tablas principales del módulo de Recursos Humanos:
 *
 *   hr_profiles         → Datos personales del trabajador (PK: id autoincrementable)
 *                          El campo user_id es OPCIONAL: no todos los trabajadores
 *                          tienen acceso al sistema.
 *
 *   hr_employment_data  → Datos laborales y de nómina del trabajador.
 *                          PK: profile_id (FK → hr_profiles.id)
 *                          location_id apunta a org_branches (fuente única de verdad).
 *
 *   hr_contracts        → Historial de contratos del trabajador.
 *
 *   hr_documents        → Expediente digital de documentos del trabajador.
 */
class CreateHrMainTables extends Migration
{
    public function up(): void
    {
        // ────────────────────────────────────────────────────────────────────
        // 1. hr_profiles  (Perfil personal del trabajador)
        // ────────────────────────────────────────────────────────────────────
        $this->forge->addField([
            'id'                     => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'                => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true, 'null' => true],
            // Nombres capturados manualmente cuando no hay user_id vinculado
            'first_name'             => ['type' => 'VARCHAR',  'constraint' => 100, 'null' => true],
            'last_name'              => ['type' => 'VARCHAR',  'constraint' => 100, 'null' => true],
            'nationality'            => ['type' => 'VARCHAR',  'constraint' => 50,  'null' => true],
            // Identificación oficial
            'curp'                   => ['type' => 'VARCHAR',  'constraint' => 18],
            'rfc'                    => ['type' => 'VARCHAR',  'constraint' => 13],
            'tax_regime'             => ['type' => 'VARCHAR',  'constraint' => 150, 'null' => true],
            'nss'                    => ['type' => 'VARCHAR',  'constraint' => 11,  'null' => true],
            // Datos personales
            'birth_date'             => ['type' => 'DATE'],
            'gender'                 => ['type' => 'ENUM',     'constraint' => ['M', 'F', 'O']],
            'marital_status'         => ['type' => 'ENUM',     'constraint' => ['soltero', 'casado', 'divorciado', 'viudo', 'union_libre']],
            'phone_personal'         => ['type' => 'VARCHAR',  'constraint' => 20,  'null' => true],
            'personal_email'         => ['type' => 'VARCHAR',  'constraint' => 255, 'null' => true],
            'address_full'           => ['type' => 'TEXT',     'null' => true],
            // Datos bancarios
            'bank_name'              => ['type' => 'VARCHAR',  'constraint' => 100, 'null' => true],
            'bank_clabe'             => ['type' => 'VARCHAR',  'constraint' => 18,  'null' => true],
            'bank_account'           => ['type' => 'VARCHAR',  'constraint' => 50,  'null' => true],
            // Contacto de emergencia
            'emergency_contact_name' => ['type' => 'VARCHAR',  'constraint' => 255, 'null' => true],
            'emergency_contact_phone'=> ['type' => 'VARCHAR',  'constraint' => 20,  'null' => true],
            'beneficiaries'          => ['type' => 'TEXT',     'null' => true],
            // Timestamps
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('user_id');
        $this->forge->addUniqueKey('curp');
        $this->forge->addUniqueKey('rfc');
        $this->forge->addUniqueKey('nss');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('hr_profiles');

        // ────────────────────────────────────────────────────────────────────
        // 2. hr_employment_data  (Datos laborales y de nómina)
        // ────────────────────────────────────────────────────────────────────
        $this->forge->addField([
            'profile_id'             => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true],
            'employee_number'        => ['type' => 'VARCHAR',  'constraint' => 20],
            'corporate_email'        => ['type' => 'VARCHAR',  'constraint' => 255, 'null' => true],
            // Catálogos
            'department_id'          => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true, 'null' => true],
            'job_id'                 => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true, 'null' => true],
            'location_id'            => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true, 'null' => true],
            'direct_manager_id'      => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true, 'null' => true],
            // Datos salariales
            'current_salary'         => ['type' => 'DECIMAL',  'constraint' => '15,2'],
            'daily_salary'           => ['type' => 'DECIMAL',  'constraint' => '15,2'],
            'payroll_type'           => ['type' => 'ENUM',     'constraint' => ['quincenal', 'mensual', 'semanal'],              'default' => 'quincenal'],
            'payment_method'         => ['type' => 'ENUM',     'constraint' => ['transferencia', 'efectivo', 'cheque'],          'default' => 'transferencia'],
            // Retenciones fijas
            'alimony_percent'        => ['type' => 'DECIMAL',  'constraint' => '5,2',  'null' => true, 'default' => 0.00],
            'alimony_fixed_amount'   => ['type' => 'DECIMAL',  'constraint' => '15,2', 'null' => true, 'default' => 0.00],
            'isr_retention'          => ['type' => 'DECIMAL',  'constraint' => '15,2', 'null' => true, 'default' => 0.00],
            'imss_fee'               => ['type' => 'DECIMAL',  'constraint' => '15,2', 'null' => true, 'default' => 0.00],
            'infonavit_contribution' => ['type' => 'DECIMAL',  'constraint' => '15,2', 'null' => true, 'default' => 0.00],
            'afore_contribution'     => ['type' => 'DECIMAL',  'constraint' => '15,2', 'null' => true, 'default' => 0.00],
            // Prestaciones (switches booleanos)
            'benefits_infonavit'     => ['type' => 'TINYINT',  'constraint' => 1, 'default' => 0],
            'benefits_fonacot'       => ['type' => 'TINYINT',  'constraint' => 1, 'default' => 0],
            'benefits_afore'         => ['type' => 'TINYINT',  'constraint' => 1, 'default' => 0],
            'benefits_vacations'     => ['type' => 'TINYINT',  'constraint' => 1, 'default' => 1],
            // Datos laborales
            'hiring_date'            => ['type' => 'DATE'],
            'termination_date'       => ['type' => 'DATE',     'null' => true],
            'status'                 => ['type' => 'ENUM',     'constraint' => ['active', 'on_leave', 'terminated', 'suspended'], 'default' => 'active'],
            'worker_type'            => ['type' => 'ENUM',     'constraint' => ['planta', 'temporal', 'proyecto', 'honorarios'],  'default' => 'planta'],
            'notes'                  => ['type' => 'TEXT',     'null' => true],
            // Timestamps
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('profile_id', true);
        $this->forge->addUniqueKey('employee_number');
        $this->forge->addForeignKey('profile_id',        'hr_profiles',      'id', 'CASCADE',  'CASCADE');
        $this->forge->addForeignKey('department_id',     'hr_cat_departments','id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('job_id',            'hr_cat_jobs',       'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('location_id',       'org_branches',      'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('direct_manager_id', 'hr_profiles',       'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('hr_employment_data');

        // ────────────────────────────────────────────────────────────────────
        // 3. hr_contracts  (Historial de contratos)
        // ────────────────────────────────────────────────────────────────────
        $this->forge->addField([
            'id'               => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true],
            'contract_type_id' => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true, 'null' => true],
            'start_date'       => ['type' => 'DATE',     'null' => true],
            'end_date'         => ['type' => 'DATE',     'null' => true],
            'file_path'        => ['type' => 'VARCHAR',  'constraint' => 255, 'null' => true],
            'status'           => ['type' => 'ENUM',     'constraint' => ['active', 'expired', 'terminated'], 'default' => 'active'],
            'notes'            => ['type' => 'TEXT',     'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id',          'users',                'id', 'CASCADE',  'CASCADE');
        $this->forge->addForeignKey('contract_type_id', 'hr_cat_contract_types','id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('hr_contracts');

        // ────────────────────────────────────────────────────────────────────
        // 4. hr_documents  (Expediente digital)
        // ────────────────────────────────────────────────────────────────────
        $this->forge->addField([
            'id'               => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true],
            'document_type_id' => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true, 'null' => true],
            'file_path'        => ['type' => 'VARCHAR',  'constraint' => 255],
            'notes'            => ['type' => 'TEXT',     'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id',          'users',                 'id', 'CASCADE',  'CASCADE');
        $this->forge->addForeignKey('document_type_id', 'hr_cat_document_types', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('hr_documents');
    }

    public function down(): void
    {
        $this->forge->dropTable('hr_documents',      true);
        $this->forge->dropTable('hr_contracts',      true);
        $this->forge->dropTable('hr_employment_data',true);
        $this->forge->dropTable('hr_profiles',       true);
    }
}
