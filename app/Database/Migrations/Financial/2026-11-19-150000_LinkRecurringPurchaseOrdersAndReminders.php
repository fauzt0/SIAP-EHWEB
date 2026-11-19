<?php

namespace App\Database\Migrations\Financial;

use CodeIgniter\Database\Migration;

class LinkRecurringPurchaseOrdersAndReminders extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('fin_purchase_orders') && ! $this->db->fieldExists('source', 'fin_purchase_orders')) {
            $this->forge->addColumn('fin_purchase_orders', [
                'source' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'manual',
                    'null'       => false,
                    'after'      => 'status',
                ],
                'recurring_expense_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'source',
                ],
                'period_label' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'null'       => true,
                    'after'      => 'recurring_expense_id',
                ],
            ]);
            $this->db->query(
                'ALTER TABLE fin_purchase_orders ADD INDEX idx_po_source (source)'
            );
            $this->db->query(
                'ALTER TABLE fin_purchase_orders ADD UNIQUE INDEX uk_po_recurring_period (recurring_expense_id, period_label)'
            );
        }

        if ($this->db->tableExists('fin_expenses') && ! $this->db->fieldExists('purchase_order_id', 'fin_expenses')) {
            $this->forge->addColumn('fin_expenses', [
                'purchase_order_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'recurring_expense_id',
                ],
            ]);
            $this->db->query('ALTER TABLE fin_expenses ADD INDEX idx_expense_po (purchase_order_id)');
        }

        if (! $this->db->tableExists('fin_supplier_reminder_logs')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'channel' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                ],
                'event_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'queued',
                ],
                'subject' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'payload' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'error_message' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'reference_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
                'reference_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
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
            $this->forge->addKey(['event_type', 'status']);
            $this->forge->createTable('fin_supplier_reminder_logs', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('fin_supplier_reminder_logs')) {
            $this->forge->dropTable('fin_supplier_reminder_logs', true);
        }

        if ($this->db->tableExists('fin_expenses') && $this->db->fieldExists('purchase_order_id', 'fin_expenses')) {
            $this->forge->dropColumn('fin_expenses', 'purchase_order_id');
        }

        if ($this->db->tableExists('fin_purchase_orders') && $this->db->fieldExists('source', 'fin_purchase_orders')) {
            $this->db->query('ALTER TABLE fin_purchase_orders DROP INDEX uk_po_recurring_period');
            $this->db->query('ALTER TABLE fin_purchase_orders DROP INDEX idx_po_source');
            $this->forge->dropColumn('fin_purchase_orders', ['source', 'recurring_expense_id', 'period_label']);
        }
    }
}
