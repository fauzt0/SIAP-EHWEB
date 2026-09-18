<?php

namespace App\Database\Migrations\Accounting;

use CodeIgniter\Database\Migration;

/**
 * Corrige category_id nullable e índices que no aplicaron en 120200/120300.
 */
class FixFinExpensesRecurringIndexes extends Migration
{
    public function up()
    {
        // A. category_id nullable (plantillas sin categoría)
        $fkName = $this->getForeignKeyName('fin_expenses', 'category_id');
        if ($fkName) {
            $this->db->query('ALTER TABLE fin_expenses DROP FOREIGN KEY `' . $fkName . '`');
        }

        $this->db->query(
            'ALTER TABLE fin_expenses MODIFY category_id INT(11) UNSIGNED NULL'
        );

        $this->db->query(
            'ALTER TABLE fin_expenses ADD CONSTRAINT fin_expenses_category_id_foreign '
            . 'FOREIGN KEY (category_id) REFERENCES fin_expense_categories(id) '
            . 'ON DELETE SET NULL ON UPDATE CASCADE'
        );

        // paid_amount NOT NULL con default
        $this->db->query(
            'ALTER TABLE fin_expenses MODIFY paid_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00'
        );

        // B. Índices fin_expenses
        if (!$this->indexExists('fin_expenses', 'uk_fin_expenses_recurring_period')) {
            $this->db->query(
                'ALTER TABLE fin_expenses ADD UNIQUE KEY uk_fin_expenses_recurring_period (recurring_expense_id, period_label)'
            );
        }

        if (!$this->indexExists('fin_expenses', 'due_date')) {
            $this->db->query('ALTER TABLE fin_expenses ADD KEY due_date (due_date)');
        }

        if (!$this->indexExists('fin_expenses', 'status')) {
            $this->db->query('ALTER TABLE fin_expenses ADD KEY status (status)');
        }

        // C. Índices fin_recurring_expenses
        if (!$this->indexExists('fin_recurring_expenses', 'next_due_date')) {
            $this->db->query('ALTER TABLE fin_recurring_expenses ADD KEY next_due_date (next_due_date)');
        }

        if (!$this->indexExists('fin_recurring_expenses', 'is_active')) {
            $this->db->query('ALTER TABLE fin_recurring_expenses ADD KEY is_active (is_active)');
        }
    }

    public function down()
    {
        if ($this->indexExists('fin_recurring_expenses', 'is_active')) {
            $this->db->query('ALTER TABLE fin_recurring_expenses DROP INDEX is_active');
        }

        if ($this->indexExists('fin_recurring_expenses', 'next_due_date')) {
            $this->db->query('ALTER TABLE fin_recurring_expenses DROP INDEX next_due_date');
        }

        if ($this->indexExists('fin_expenses', 'status')) {
            $this->db->query('ALTER TABLE fin_expenses DROP INDEX status');
        }

        if ($this->indexExists('fin_expenses', 'due_date')) {
            $this->db->query('ALTER TABLE fin_expenses DROP INDEX due_date');
        }

        if ($this->indexExists('fin_expenses', 'uk_fin_expenses_recurring_period')) {
            $this->db->query('ALTER TABLE fin_expenses DROP INDEX uk_fin_expenses_recurring_period');
        }

        $nullCount = $this->db->query(
            'SELECT COUNT(*) AS c FROM fin_expenses WHERE category_id IS NULL'
        )->getRow()->c ?? 0;

        if ((int) $nullCount === 0) {
            $fkName = $this->getForeignKeyName('fin_expenses', 'category_id');
            if ($fkName) {
                $this->db->query('ALTER TABLE fin_expenses DROP FOREIGN KEY `' . $fkName . '`');
            }

            $this->db->query(
                'ALTER TABLE fin_expenses MODIFY category_id INT(11) UNSIGNED NOT NULL'
            );

            $this->db->query(
                'ALTER TABLE fin_expenses ADD CONSTRAINT fin_expenses_category_id_foreign '
                . 'FOREIGN KEY (category_id) REFERENCES fin_expense_categories(id) '
                . 'ON DELETE RESTRICT ON UPDATE RESTRICT'
            );
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = $this->db->query(
            'SHOW INDEX FROM `' . $table . '` WHERE Key_name = ' . $this->db->escape($indexName)
        );

        return $result->getNumRows() > 0;
    }

    private function getForeignKeyName(string $table, string $column): ?string
    {
        $dbName = $this->db->getDatabase();
        $row    = $this->db->query(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE '
            . 'WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? '
            . 'AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1',
            [$dbName, $table, $column]
        )->getRow();

        return $row->CONSTRAINT_NAME ?? null;
    }
}
