<?php

namespace App\Database\Migrations\Accounting;

use CodeIgniter\Database\Migration;

/**
 * Campos de calendarización para plantillas de gastos recurrentes.
 */
class AlterFinRecurringExpensesForCalendar extends Migration
{
    public function up()
    {
        $this->forge->addColumn('fin_recurring_expenses', [
            'org_branch_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'supplier_id',
            ],
            'start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'end_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'next_due_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'billing_weekday' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'null'       => true,
            ],
            'notify_days_before' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'null'       => true,
                'default'    => 3,
            ],
        ]);

        $this->db->query(
            'ALTER TABLE fin_recurring_expenses ADD CONSTRAINT fk_fin_recurring_org_branch '
            . 'FOREIGN KEY (org_branch_id) REFERENCES org_branches(id) '
            . 'ON DELETE RESTRICT ON UPDATE CASCADE'
        );

        $this->forge->addKey('org_branch_id');
        $this->forge->addKey('next_due_date');
        $this->forge->addKey('is_active');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE fin_recurring_expenses DROP FOREIGN KEY fk_fin_recurring_org_branch');
        $this->forge->dropColumn('fin_recurring_expenses', [
            'org_branch_id',
            'start_date',
            'end_date',
            'next_due_date',
            'billing_weekday',
            'notify_days_before',
        ]);
    }
}
