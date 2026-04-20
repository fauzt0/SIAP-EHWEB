<?php

namespace App\Database\Migrations\Accounting;

use CodeIgniter\Database\Migration;

class CreateFinancialModule extends Migration
{
    public function up()
    {
        $this->db->disableForeignKeyChecks();

        // 1. fin_accounts
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'type' => ['type' => 'ENUM', 'constraint' => ['cash', 'bank', 'digital'], 'default' => 'bank'],
            'currency' => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'MXN'],
            'balance' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('fin_accounts', true);

        // 2. fin_suppliers
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'commercial_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'tax_id' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'contact_email' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('fin_suppliers', true);

        // 3. fin_expense_categories
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'expense_type' => ['type' => 'ENUM', 'constraint' => ['fixed', 'variable']],
            'parent_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('parent_id', 'fin_expense_categories', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('fin_expense_categories', true);

        // 4. fin_expenses
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'supplier_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'category_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'total_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'due_date' => ['type' => 'DATE'],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'partial', 'paid', 'cancelled'], 'default' => 'pending'],
            'invoice_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('supplier_id', 'fin_suppliers', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('category_id', 'fin_expense_categories', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('fin_expenses', true);

        // 5. fin_payment_folios
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'folio_number' => ['type' => 'VARCHAR', 'constraint' => 20, 'unique' => true],
            'expense_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'account_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'amount_paid' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'concept' => ['type' => 'TEXT'],
            'status' => ['type' => 'ENUM', 'constraint' => ['requested', 'approved', 'executed', 'rejected'], 'default' => 'requested'],
            'requested_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'approved_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('expense_id', 'fin_expenses', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('account_id', 'fin_accounts', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('requested_by', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('approved_by', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('fin_payment_folios', true);

        $this->db->enableForeignKeyChecks();
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();
        $this->forge->dropTable('fin_payment_folios');
        $this->forge->dropTable('fin_expenses');
        $this->forge->dropTable('fin_expense_categories');
        $this->forge->dropTable('fin_suppliers');
        $this->forge->dropTable('fin_accounts');
        $this->db->enableForeignKeyChecks();
    }
}
