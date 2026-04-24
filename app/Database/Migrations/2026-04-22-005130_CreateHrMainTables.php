<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHrMainTables extends Migration
{
    public function up()
    {
        // 1. hr_profiles (Extensión Personal)
        $this->forge->addField([
            'user_id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'curp'                    => ['type' => 'VARCHAR', 'constraint' => 18, 'unique' => true],
            'rfc'                     => ['type' => 'VARCHAR', 'constraint' => 13, 'unique' => true],
            'nss'                     => ['type' => 'VARCHAR', 'constraint' => 11, 'unique' => true, 'null' => true],
            'birth_date'              => ['type' => 'DATE'],
            'gender'                  => ['type' => 'ENUM', 'constraint' => ['M', 'F', 'O']],
            'marital_status'          => ['type' => 'ENUM', 'constraint' => ['soltero', 'casado', 'divorciado', 'viudo', 'union_libre']],
            'phone_personal'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'address_full'            => ['type' => 'TEXT', 'null' => true],
            'bank_name'               => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'bank_clabe'              => ['type' => 'VARCHAR', 'constraint' => 18, 'null' => true],
            'emergency_contact_name'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'emergency_contact_phone' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
            'updated_at'              => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('hr_profiles');

        // 2. hr_employment_data (Situación Laboral Actual)
        $this->forge->addField([
            'user_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'employee_number' => ['type' => 'VARCHAR', 'constraint' => 20, 'unique' => true],
            'department_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'job_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'location_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'current_salary'  => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'daily_salary'    => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'hiring_date'     => ['type' => 'DATE'],
            'termination_date'=> ['type' => 'DATE', 'null' => true],
            'status'          => ['type' => 'ENUM', 'constraint' => ['active', 'on_leave', 'terminated', 'suspended'], 'default' => 'active'],
            'notes'           => ['type' => 'TEXT', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('department_id', 'hr_cat_departments', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('job_id', 'hr_cat_jobs', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('location_id', 'hr_cat_locations', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('hr_employment_data');
    }

    public function down()
    {
        $this->forge->dropTable('hr_employment_data', true);
        $this->forge->dropTable('hr_profiles', true);
    }
}
