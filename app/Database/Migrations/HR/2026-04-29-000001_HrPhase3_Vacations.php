<?php

namespace App\Database\Migrations\HR;

use CodeIgniter\Database\Migration;

class HrPhase3Vacations extends Migration
{
    public function up()
    {
        // -------------------------------------------------------------------
        // 1. Tabla de Historial de Vacaciones
        // -------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'profile_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'start_date' => [
                'type' => 'DATE',
            ],
            'end_date' => [
                'type' => 'DATE',
            ],
            'total_days' => [
                'type'       => 'INT',
                'constraint' => 5,
                'comment'    => 'Días hábiles efectivos que se descuentan',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pendiente', 'aprobado', 'rechazado'],
                'default'    => 'pendiente',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'updated_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('profile_id');
        $this->forge->addKey('status');
        
        // Asumiendo que la tabla hr_profiles existe.
        $this->forge->addForeignKey('profile_id', 'hr_profiles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'SET NULL');
        
        $this->forge->createTable('hr_vacations', true);
    }

    public function down()
    {
        $this->forge->dropTable('hr_vacations', true);
    }
}
