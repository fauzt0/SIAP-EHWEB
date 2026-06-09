<?php

namespace App\Database\Migrations\Accounting;

use CodeIgniter\Database\Migration;

class AddWeeklyFrequencyToFinRecurringExpenses extends Migration
{
    public function up()
    {
        $this->db->query(
            "ALTER TABLE fin_recurring_expenses MODIFY frequency "
            . "ENUM('weekly', 'monthly', 'quarterly', 'yearly') NOT NULL DEFAULT 'monthly'"
        );
    }

    public function down()
    {
        $this->db->query(
            "UPDATE fin_recurring_expenses SET frequency = 'monthly' WHERE frequency = 'weekly'"
        );

        $this->db->query(
            "ALTER TABLE fin_recurring_expenses MODIFY frequency "
            . "ENUM('monthly', 'quarterly', 'yearly') NOT NULL DEFAULT 'monthly'"
        );
    }
}
