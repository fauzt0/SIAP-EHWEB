<?php

namespace App\Database\Migrations\Accounting;

use CodeIgniter\Database\Migration;

/**
 * Vincula fin_expenses a plantillas recurrentes y sucursales.
 */
class AlterFinExpensesForRecurringOccurrences extends Migration
{
    public function up()
    {
        $this->forge->addColumn('fin_expenses', [
            'recurring_expense_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'org_branch_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'period_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'paid_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
            ],
            'currency' => [
                'type'       => 'VARCHAR',
                'constraint' => 3,
                'default'    => 'MXN',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);

        $this->db->query(
            'ALTER TABLE fin_expenses ADD CONSTRAINT fk_fin_expenses_recurring '
            . 'FOREIGN KEY (recurring_expense_id) REFERENCES fin_recurring_expenses(id) '
            . 'ON DELETE SET NULL ON UPDATE CASCADE'
        );

        $this->db->query(
            'ALTER TABLE fin_expenses ADD CONSTRAINT fk_fin_expenses_org_branch '
            . 'FOREIGN KEY (org_branch_id) REFERENCES org_branches(id) '
            . 'ON DELETE RESTRICT ON UPDATE CASCADE'
        );

        $this->forge->addKey('recurring_expense_id');
        $this->forge->addKey('org_branch_id');
        $this->forge->addKey('due_date');
        $this->forge->addKey('status');
        $this->forge->addUniqueKey(['recurring_expense_id', 'period_label']);
    }

    public function down()
    {
        $this->db->query('ALTER TABLE fin_expenses DROP FOREIGN KEY fk_fin_expenses_recurring');
        $this->db->query('ALTER TABLE fin_expenses DROP FOREIGN KEY fk_fin_expenses_org_branch');
        $this->forge->dropColumn('fin_expenses', [
            'recurring_expense_id',
            'org_branch_id',
            'period_label',
            'paid_amount',
            'currency',
            'notes',
        ]);
    }
}
