<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExtendHrProfilesAndEmployment extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        $db->transStart();

        // ========================================================================
        // 1. Modificar hr_profiles
        // ========================================================================
        // a) Eliminar Primary Key existente (user_id) y agregar 'id' como nueva PK
        $db->query('ALTER TABLE hr_profiles DROP PRIMARY KEY, ADD id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY FIRST');

        // b) Modificar user_id para que sea NULL (trabajadores sin usuario) y ÚNICO
        $db->query('ALTER TABLE hr_profiles MODIFY user_id INT(11) UNSIGNED NULL');
        $db->query('ALTER TABLE hr_profiles ADD UNIQUE INDEX (user_id)');

        // c) Agregar nuevos campos personales
        $profileFields = [
            'first_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'user_id'
            ],
            'last_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'first_name'
            ],
            'nationality' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'last_name'
            ],
            'personal_email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'phone_personal'
            ],
            'beneficiaries' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'emergency_contact_phone'
            ],
            'tax_regime' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
                'after' => 'rfc'
            ],
            'bank_account' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'bank_name'
            ]
        ];
        $this->forge->addColumn('hr_profiles', $profileFields);


        // ========================================================================
        // 2. Modificar hr_employment_data
        // ========================================================================
        // a) Eliminar Primary Key existente (user_id)
        $db->query('ALTER TABLE hr_employment_data DROP PRIMARY KEY');

        // b) Renombrar user_id a profile_id y hacerlo Primary Key
        $db->query('ALTER TABLE hr_employment_data CHANGE user_id profile_id INT(11) UNSIGNED NOT NULL');
        $db->query('ALTER TABLE hr_employment_data ADD PRIMARY KEY (profile_id)');
        $db->query('ALTER TABLE hr_employment_data ADD CONSTRAINT fk_employment_profile FOREIGN KEY (profile_id) REFERENCES hr_profiles(id) ON DELETE CASCADE');

        // c) Agregar nuevos campos laborales y de nómina
        $employmentFields = [
            'worker_type' => [
                'type' => 'ENUM',
                'constraint' => ['planta', 'temporal', 'proyecto', 'honorarios'],
                'default' => 'planta',
                'after' => 'status'
            ],
            'direct_manager_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'location_id'
            ],
            'corporate_email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'employee_number'
            ],
            'payroll_type' => [
                'type' => 'ENUM',
                'constraint' => ['quincenal', 'mensual', 'semanal'],
                'default' => 'quincenal',
                'after' => 'daily_salary'
            ],
            'payment_method' => [
                'type' => 'ENUM',
                'constraint' => ['transferencia', 'efectivo', 'cheque'],
                'default' => 'transferencia',
                'after' => 'payroll_type'
            ],
            'alimony_percent' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
                'default' => 0.00
            ],
            'alimony_fixed_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
                'default' => 0.00
            ],
            'isr_retention' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
                'default' => 0.00
            ],
            'imss_fee' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
                'default' => 0.00
            ],
            'infonavit_contribution' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
                'default' => 0.00
            ],
            'afore_contribution' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
                'default' => 0.00
            ],
            'benefits_infonavit' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0
            ],
            'benefits_fonacot' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0
            ],
            'benefits_afore' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0
            ],
            'benefits_vacations' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1
            ]
        ];
        $this->forge->addColumn('hr_employment_data', $employmentFields);

        // d) Agregar llave foránea para jefe directo
        $db->query('ALTER TABLE hr_employment_data ADD CONSTRAINT fk_employment_manager FOREIGN KEY (direct_manager_id) REFERENCES hr_profiles(id) ON DELETE SET NULL');

        $db->transComplete();
    }

    public function down()
    {
        // Nota: Un 'down' para reversar esta migración destructiva sería complejo (devolver a usar user_id obligatorio).
        // Por la naturaleza de este cambio estructural, se asume que solo avanza hacia adelante en Sandbox.
    }
}
