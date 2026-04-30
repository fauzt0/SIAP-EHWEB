<?php

namespace App\Database\Migrations\HR;

use CodeIgniter\Database\Migration;

class HrPhase2 extends Migration
{
    protected $DBGroup = 'default';

    public function up(): void
    {
        // ────────────────────────────────────────────────────────────────────
        // 1. hr_cat_shifts (Catálogo de Turnos)
        // ────────────────────────────────────────────────────────────────────
        $this->forge->addField([
            'id'           => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'         => ['type' => 'VARCHAR',  'constraint' => 100],
            'start_time'   => ['type' => 'TIME'],
            'end_time'     => ['type' => 'TIME'],
            'grace_period' => ['type' => 'INT',      'constraint' => 11, 'default' => 0],
            'work_days'    => ['type' => 'VARCHAR',  'constraint' => 50], // Ej: "1,2,3,4,5"
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('hr_cat_shifts');

        // ────────────────────────────────────────────────────────────────────
        // 2. hr_worker_schedules (Asignación de Horarios)
        // ────────────────────────────────────────────────────────────────────
        $this->forge->addField([
            'id'         => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'profile_id' => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true],
            'shift_id'   => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true],
            'start_date' => ['type' => 'DATE'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('profile_id', 'hr_profiles',   'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('shift_id',   'hr_cat_shifts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('hr_worker_schedules');

        // ────────────────────────────────────────────────────────────────────
        // 3. hr_incidences (Registro de Incidencias)
        // ────────────────────────────────────────────────────────────────────
        $this->forge->addField([
            'id'             => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'profile_id'     => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true],
            'incidence_type' => ['type' => 'ENUM',     'constraint' => ['falta', 'retardo', 'permiso', 'incapacidad']],
            'date'           => ['type' => 'DATE'],
            'justified'      => ['type' => 'TINYINT',  'constraint' => 1,  'default' => 0],
            'status'         => ['type' => 'ENUM',     'constraint' => ['pendiente', 'aprobado', 'rechazado', 'aplicado'], 'default' => 'pendiente'],
            'notes'          => ['type' => 'TEXT',     'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('profile_id', 'hr_profiles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('hr_incidences');

        // ────────────────────────────────────────────────────────────────────
        // 4. hr_vacation_requests (Solicitudes de Vacaciones)
        // ────────────────────────────────────────────────────────────────────
        $this->forge->addField([
            'id'             => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'profile_id'     => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true],
            'start_date'     => ['type' => 'DATE'],
            'end_date'       => ['type' => 'DATE'],
            'days_requested' => ['type' => 'INT',      'constraint' => 11],
            'status'         => ['type' => 'ENUM',     'constraint' => ['pendiente', 'aprobado', 'rechazado'], 'default' => 'pendiente'],
            'approver_id'    => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true, 'null' => true],
            'notes'          => ['type' => 'TEXT',     'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('profile_id',  'hr_profiles', 'id', 'CASCADE',  'CASCADE');
        $this->forge->addForeignKey('approver_id', 'users',       'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('hr_vacation_requests');

        // ────────────────────────────────────────────────────────────────────
        // 5. sys_notifications (Sistema de Notificaciones)
        // ────────────────────────────────────────────────────────────────────
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT',   'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT',      'constraint' => 11, 'unsigned' => true],
            'title'      => ['type' => 'VARCHAR',  'constraint' => 150],
            'message'    => ['type' => 'TEXT'],
            'type'       => ['type' => 'VARCHAR',  'constraint' => 50],
            'is_read'    => ['type' => 'TINYINT',  'constraint' => 1,  'default' => 0],
            'target_url' => ['type' => 'VARCHAR',  'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sys_notifications');
    }

    public function down(): void
    {
        $this->forge->dropTable('sys_notifications',     true);
        $this->forge->dropTable('hr_vacation_requests',  true);
        $this->forge->dropTable('hr_incidences',         true);
        $this->forge->dropTable('hr_worker_schedules',   true);
        $this->forge->dropTable('hr_cat_shifts',         true);
    }
}
