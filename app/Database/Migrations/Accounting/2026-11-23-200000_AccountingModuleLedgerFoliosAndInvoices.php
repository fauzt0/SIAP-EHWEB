<?php

namespace App\Database\Migrations\Accounting;

use CodeIgniter\Database\Migration;

/**
 * Completa el módulo de contabilidad: cuentas por sucursal, origen de gastos,
 * facturas de proveedor y columnas de auditoría en solicitudes de fondos.
 */
class AccountingModuleLedgerFoliosAndInvoices extends Migration
{
    public function up()
    {
        $this->extendAccounts();
        $this->extendExpenses();
        $this->extendPaymentFolios();
        $this->extendLedger();
        $this->createSupplierInvoices();
        $this->seedDefaults();
    }

    public function down()
    {
        if ($this->db->tableExists('fin_supplier_invoices')) {
            $this->forge->dropTable('fin_supplier_invoices', true);
        }
    }

    private function extendAccounts(): void
    {
        if (! $this->db->tableExists('fin_accounts')) {
            return;
        }
        $this->addColumnIfMissing('fin_accounts', 'company_id', [
            'type'     => 'INT',
            'unsigned' => true,
            'null'     => true,
            'after'    => 'id',
        ]);
        $this->addColumnIfMissing('fin_accounts', 'branch_id', [
            'type'     => 'INT',
            'unsigned' => true,
            'null'     => true,
            'after'    => 'company_id',
        ]);
        $this->addIndexIfMissing('fin_accounts', 'idx_fin_accounts_company', 'company_id');
    }

    private function extendExpenses(): void
    {
        if (! $this->db->tableExists('fin_expenses')) {
            return;
        }
        $this->addColumnIfMissing('fin_expenses', 'source_type', [
            'type'       => 'VARCHAR',
            'constraint' => 40,
            'null'       => true,
            'after'      => 'status',
        ]);
        $this->addColumnIfMissing('fin_expenses', 'source_id', [
            'type'     => 'INT',
            'unsigned' => true,
            'null'     => true,
            'after'    => 'source_type',
        ]);
        $this->addColumnIfMissing('fin_expenses', 'payroll_period_id', [
            'type'     => 'INT',
            'unsigned' => true,
            'null'     => true,
            'after'    => 'source_id',
        ]);
        $this->addColumnIfMissing('fin_expenses', 'supplier_invoice_id', [
            'type'     => 'INT',
            'unsigned' => true,
            'null'     => true,
            'after'    => 'payroll_period_id',
        ]);
        $this->addColumnIfMissing('fin_expenses', 'purchase_order_id', [
            'type'     => 'INT',
            'unsigned' => true,
            'null'     => true,
            'after'    => 'supplier_invoice_id',
        ]);
        $this->addIndexIfMissing('fin_expenses', 'idx_fin_expenses_source', 'source_type');
        $this->addIndexIfMissing('fin_expenses', 'idx_fin_expenses_payroll', 'payroll_period_id');
    }

    private function extendPaymentFolios(): void
    {
        if (! $this->db->tableExists('fin_payment_folios')) {
            return;
        }
        $this->addColumnIfMissing('fin_payment_folios', 'company_id', [
            'type'     => 'INT',
            'unsigned' => true,
            'null'     => true,
            'after'    => 'id',
        ]);
        $this->addColumnIfMissing('fin_payment_folios', 'branch_id', [
            'type'     => 'INT',
            'unsigned' => true,
            'null'     => true,
            'after'    => 'company_id',
        ]);
        $this->addColumnIfMissing('fin_payment_folios', 'account_id', [
            'type'     => 'INT',
            'unsigned' => true,
            'null'     => true,
            'after'    => 'branch_id',
        ]);
        $this->addColumnIfMissing('fin_payment_folios', 'expense_id', [
            'type'     => 'INT',
            'unsigned' => true,
            'null'     => true,
            'after'    => 'account_id',
        ]);
        $this->addColumnIfMissing('fin_payment_folios', 'requested_by', [
            'type'     => 'INT',
            'unsigned' => true,
            'null'     => true,
            'after'    => 'created_by',
        ]);
        $this->addColumnIfMissing('fin_payment_folios', 'approved_by', [
            'type'     => 'INT',
            'unsigned' => true,
            'null'     => true,
            'after'    => 'requested_by',
        ]);
        $this->addColumnIfMissing('fin_payment_folios', 'executed_by', [
            'type'     => 'INT',
            'unsigned' => true,
            'null'     => true,
            'after'    => 'approved_by',
        ]);
        $this->addColumnIfMissing('fin_payment_folios', 'approved_at', [
            'type' => 'DATETIME',
            'null' => true,
            'after'=> 'executed_by',
        ]);
        $this->addColumnIfMissing('fin_payment_folios', 'executed_at', [
            'type' => 'DATETIME',
            'null' => true,
            'after'=> 'approved_at',
        ]);
        $this->addIndexIfMissing('fin_payment_folios', 'idx_fin_folios_status', 'status');
        $this->addIndexIfMissing('fin_payment_folios', 'idx_fin_folios_company', 'company_id');
    }

    private function extendLedger(): void
    {
        if (! $this->db->tableExists('fin_ledger_entries')) {
            return;
        }
        $this->addColumnIfMissing('fin_ledger_entries', 'company_id', [
            'type'     => 'INT',
            'unsigned' => true,
            'null'     => true,
            'after'    => 'id',
        ]);
        $this->addColumnIfMissing('fin_ledger_entries', 'branch_id', [
            'type'     => 'INT',
            'unsigned' => true,
            'null'     => true,
            'after'    => 'company_id',
        ]);
        $this->addIndexIfMissing('fin_ledger_entries', 'idx_fin_ledger_reference', 'reference_type');
        $this->addIndexIfMissing('fin_ledger_entries', 'idx_fin_ledger_company', 'company_id');
    }

    private function createSupplierInvoices(): void
    {
        if ($this->db->tableExists('fin_supplier_invoices')) {
            return;
        }

        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'folio'              => ['type' => 'VARCHAR', 'constraint' => 40],
            'supplier_id'        => ['type' => 'INT', 'unsigned' => true],
            'purchase_order_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'invoice_number'     => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'invoice_date'       => ['type' => 'DATE'],
            'due_date'           => ['type' => 'DATE', 'null' => true],
            'subtotal'           => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'tax_amount'         => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'total'              => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'paid_amount'        => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'status'             => ['type' => 'ENUM', 'constraint' => ['draft', 'open', 'partial', 'paid', 'cancelled'], 'default' => 'open'],
            'company_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'branch_id'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'account_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'notes'              => ['type' => 'TEXT', 'null' => true],
            'created_by'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('folio');
        $this->forge->addKey(['supplier_id', 'status']);
        $this->forge->addKey('purchase_order_id');
        $this->forge->createTable('fin_supplier_invoices', true);
    }

    private function seedDefaults(): void
    {
        if ($this->db->tableExists('fin_expense_categories')) {
            $now = date('Y-m-d H:i:s');
            $defaults = [
                ['name' => 'Nómina', 'expense_type' => 'fixed'],
                ['name' => 'Gastos fijos', 'expense_type' => 'fixed'],
                ['name' => 'Gastos variables', 'expense_type' => 'variable'],
                ['name' => 'Servicios recurrentes', 'expense_type' => 'fixed'],
                ['name' => 'Compras a proveedores', 'expense_type' => 'variable'],
                ['name' => 'Solicitud de fondos', 'expense_type' => 'variable'],
            ];
            foreach ($defaults as $row) {
                $exists = $this->db->table('fin_expense_categories')->where('name', $row['name'])->countAllResults();
                if ($exists === 0) {
                    $this->db->table('fin_expense_categories')->insert($row + [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        if ($this->db->tableExists('fin_accounts')) {
            $now = date('Y-m-d H:i:s');
            foreach ([
                ['name' => 'Caja chica', 'type' => 'cash'],
                ['name' => 'Banco principal', 'type' => 'bank'],
            ] as $acc) {
                $exists = $this->db->table('fin_accounts')->where('name', $acc['name'])->countAllResults();
                if ($exists === 0) {
                    $this->db->table('fin_accounts')->insert($acc + [
                        'currency'   => 'MXN',
                        'balance'    => 0,
                        'is_active'  => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function addColumnIfMissing(string $table, string $column, array $definition): void
    {
        if ($this->db->fieldExists($column, $table)) {
            return;
        }
        $this->forge->addColumn($table, [$column => $definition]);
    }

    private function addIndexIfMissing(string $table, string $indexName, string $column): void
    {
        $indexes = $this->db->getIndexData($table);
        foreach ($indexes as $idx) {
            if (strcasecmp((string) $idx->name, $indexName) === 0) {
                return;
            }
        }
        $this->db->query('ALTER TABLE `' . $table . '` ADD INDEX `' . $indexName . '` (`' . $column . '`)');
    }
}
