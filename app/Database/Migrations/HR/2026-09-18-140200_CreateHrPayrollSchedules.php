<?php

namespace App\Database\Migrations\HR;

use CodeIgniter\Database\Migration;

/**
 * Calendario / planeador de nóminas recurrentes.
 */
class CreateHrPayrollSchedules extends Migration
{
    public function up(): void
    {
        $this->db->disableForeignKeyChecks();

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'period_type' => [
                'type'       => 'ENUM',
                'constraint' => ['weekly', 'biweekly', 'monthly'],
            ],
            'next_payment_date' => [
                'type' => 'DATE',
            ],
            'recurrence_day' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'null'       => true,
            ],
            'branch_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('branch_id');
        $this->forge->addKey('next_payment_date');
        $this->forge->addForeignKey('branch_id', 'org_branches', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('hr_payroll_schedules', true);

        $this->db->enableForeignKeyChecks();
    }

    public function down(): void
    {
        $this->db->disableForeignKeyChecks();
        $this->forge->dropTable('hr_payroll_schedules', true);
        $this->db->enableForeignKeyChecks();
    }
}
