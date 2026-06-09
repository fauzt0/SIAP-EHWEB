<?php

namespace App\Database\Migrations\Accounting;

use CodeIgniter\Database\Migration;

class AddServiceTypeToFinRecurringExpenses extends Migration
{
    public function up()
    {
        $this->forge->addColumn('fin_recurring_expenses', [
            'service_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'other',
                'null'       => false,
                'after'      => 'description',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('fin_recurring_expenses', 'service_type');
    }
}
