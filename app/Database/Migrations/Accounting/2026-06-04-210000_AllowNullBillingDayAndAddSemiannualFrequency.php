<?php

namespace App\Database\Migrations\Accounting;

use CodeIgniter\Database\Migration;

class AllowNullBillingDayAndAddSemiannualFrequency extends Migration
{
    public function up()
    {
        // Hacer billing_day nullable para frecuencias que no requieren día específico (weekly)
        $this->db->query(
            "ALTER TABLE fin_recurring_expenses "
            . "MODIFY billing_day TINYINT(2) UNSIGNED NULL DEFAULT NULL"
        );

        // Actualizar ENUM de frequency para incluir 'semiannual'
        $this->db->query(
            "ALTER TABLE fin_recurring_expenses MODIFY frequency "
            . "ENUM('weekly', 'monthly', 'quarterly', 'semiannual', 'yearly') "
            . "NOT NULL DEFAULT 'monthly'"
        );
    }

    public function down()
    {
        // Revertir frequency: quitar 'semiannual'
        $this->db->query(
            "UPDATE fin_recurring_expenses SET frequency = 'quarterly' WHERE frequency = 'semiannual'"
        );
        $this->db->query(
            "ALTER TABLE fin_recurring_expenses MODIFY frequency "
            . "ENUM('weekly', 'monthly', 'quarterly', 'yearly') "
            . "NOT NULL DEFAULT 'monthly'"
        );

        // Revertir billing_day a NOT NULL
        $this->db->query(
            "UPDATE fin_recurring_expenses SET billing_day = 1 WHERE billing_day IS NULL"
        );
        $this->db->query(
            "ALTER TABLE fin_recurring_expenses "
            . "MODIFY billing_day TINYINT(2) UNSIGNED NOT NULL DEFAULT 1"
        );
    }
}
