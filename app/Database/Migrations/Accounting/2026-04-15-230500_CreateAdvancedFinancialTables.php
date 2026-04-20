<?php

namespace App\Database\Migrations\Accounting;

use CodeIgniter\Database\Migration;

class CreateAdvancedFinancialTables extends Migration
{
    public function up()
    {
        $this->db->disableForeignKeyChecks();

        // 1. fin_recurring_expenses
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'supplier_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'category_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'account_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => false,
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => 3,
                'default' => 'MXN',
            ],
            'billing_day' => [
                'type' => 'TINYINT',
                'constraint' => 2,
                'null' => false,
            ],
            'frequency' => [
                'type' => 'ENUM',
                'constraint' => ['monthly', 'quarterly', 'yearly'],
                'default' => 'monthly',
            ],
            'last_generated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
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
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('supplier_id', 'fin_suppliers', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('category_id', 'fin_expense_categories', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('account_id', 'fin_accounts', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('fin_recurring_expenses', true);

        // 2. fin_ledger_entries
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'account_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'reference_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'reference_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'type' => [
                'type' => 'ENUM',
                'constraint' => ['debit', 'credit'],
                'null' => false,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => false,
            ],
            'balance_after' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => false,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'entry_date' => [
                'type' => 'DATETIME',
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
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('account_id', 'fin_accounts', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('fin_ledger_entries', true);

        // 3. fin_assets
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'supplier_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'purchase_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'purchase_price' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => false,
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => 3,
                'default' => 'MXN',
            ],
            'serial_number' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'depreciation_months' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 36,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'retired', 'maintenance', 'sold'],
                'default' => 'active',
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
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('supplier_id', 'fin_suppliers', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('fin_assets', true);

        $this->db->enableForeignKeyChecks();
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();
        $this->forge->dropTable('fin_assets');
        $this->forge->dropTable('fin_ledger_entries');
        $this->forge->dropTable('fin_recurring_expenses');
        $this->db->enableForeignKeyChecks();
    }
}
