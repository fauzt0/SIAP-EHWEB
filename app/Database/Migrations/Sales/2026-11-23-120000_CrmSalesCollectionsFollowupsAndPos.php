<?php

namespace App\Database\Migrations\Sales;

use CodeIgniter\Database\Migration;

/**
 * Recibos por periodo (one-time vs renovación), partidas de recibo, OC del cliente,
 * documentos de orden, cumplimiento por producto y seguimiento CRM por contacto.
 */
class CrmSalesCollectionsFollowupsAndPos extends Migration
{
    public function up(): void
    {
        $this->db->disableForeignKeyChecks();

        $this->extendReceipts();
        $this->createReceiptItems();
        $this->extendOrderItems();
        $this->extendCustomerServices();
        $this->createOrderDocuments();
        $this->createCustomerPos();
        $this->extendFollowups();

        $this->db->enableForeignKeyChecks();
    }

    public function down(): void
    {
        $this->db->disableForeignKeyChecks();
        $this->forge->dropTable('sales_customer_po_items', true);
        $this->forge->dropTable('sales_customer_pos', true);
        $this->forge->dropTable('sales_order_documents', true);
        $this->forge->dropTable('sales_receipt_items', true);
        $this->db->enableForeignKeyChecks();
    }

    private function extendReceipts(): void
    {
        if (! $this->db->tableExists('sales_receipts')) {
            return;
        }

        $columns = [
            'service_id' => [
                'type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'order_id',
            ],
            'order_item_id' => [
                'type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'service_id',
            ],
            'customer_po_id' => [
                'type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true,
            ],
            'receipt_kind' => [
                'type' => 'ENUM', 'constraint' => ['one_time', 'renewal'], 'default' => 'one_time',
            ],
            'is_initial' => [
                'type' => 'TINYINT', 'constraint' => 1, 'default' => 1,
            ],
            'period_label' => [
                'type' => 'VARCHAR', 'constraint' => 32, 'null' => true,
            ],
            'period_start' => ['type' => 'DATE', 'null' => true],
            'period_end'   => ['type' => 'DATE', 'null' => true],
            'due_date'     => ['type' => 'DATE', 'null' => true],
            'collection_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'partial', 'paid', 'overdue', 'void'],
                'default'    => 'pending',
            ],
            'plugin_ref' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
        ];

        foreach ($columns as $name => $def) {
            if (! $this->db->fieldExists($name, 'sales_receipts')) {
                $this->forge->addColumn('sales_receipts', [$name => $def]);
            }
        }

        $existing = array_map(static fn ($idx) => strtolower($idx->name), $this->db->getIndexData('sales_receipts'));
        if (! in_array('idx_sales_receipts_collection', $existing, true)) {
            $this->db->query('ALTER TABLE sales_receipts ADD INDEX idx_sales_receipts_collection (collection_status, due_date)');
        }
        if (! in_array('idx_sales_receipts_service_period', $existing, true)) {
            $this->db->query('ALTER TABLE sales_receipts ADD INDEX idx_sales_receipts_service_period (service_id, period_label)');
        }

        $this->db->query("UPDATE sales_receipts SET due_date = DATE(COALESCE(issued_at, created_at, NOW())) WHERE due_date IS NULL");
        $this->db->query("UPDATE sales_receipts SET period_label = CONCAT('ONE-', order_id) WHERE period_label IS NULL AND (receipt_kind = 'one_time' OR receipt_kind IS NULL)");
    }

    private function createReceiptItems(): void
    {
        if ($this->db->tableExists('sales_receipt_items')) {
            return;
        }

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'receipt_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'order_item_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'service_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'product_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'item_name'     => ['type' => 'VARCHAR', 'constraint' => 255],
            'billing_cycle' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'one_time'],
            'quantity'      => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            'unit_price'    => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'line_total'    => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'plugin_ref'    => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('receipt_id');
        $this->forge->addForeignKey('receipt_id', 'sales_receipts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sales_receipt_items', true);
    }

    private function extendOrderItems(): void
    {
        if (! $this->db->tableExists('sales_order_items')) {
            return;
        }

        $columns = [
            'line_code' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'product_type' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'fulfillment_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'processing', 'shipped', 'delivered', 'activated', 'cancelled'],
                'default'    => 'pending',
            ],
            'plugin_ref' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
        ];
        foreach ($columns as $name => $def) {
            if (! $this->db->fieldExists($name, 'sales_order_items')) {
                $this->forge->addColumn('sales_order_items', [$name => $def]);
            }
        }
    }

    private function extendCustomerServices(): void
    {
        if ($this->db->tableExists('customer_services') && ! $this->db->fieldExists('order_item_id', 'customer_services')) {
            $this->forge->addColumn('customer_services', [
                'order_item_id' => [
                    'type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'order_id',
                ],
            ]);
        }
    }

    private function createOrderDocuments(): void
    {
        if ($this->db->tableExists('sales_order_documents')) {
            return;
        }

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'order_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'order_item_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'doc_type'      => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'other'],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 180],
            'file_path'     => ['type' => 'VARCHAR', 'constraint' => 255],
            'mime_type'     => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'notes'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'plugin_ref'    => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'uploaded_by'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('order_id');
        $this->forge->addForeignKey('order_id', 'sales_orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sales_order_documents', true);
    }

    private function createCustomerPos(): void
    {
        if (! $this->db->tableExists('sales_customer_pos')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'po_number'   => ['type' => 'VARCHAR', 'constraint' => 32],
                'order_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'client_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'status'      => ['type' => 'ENUM', 'constraint' => ['draft', 'issued', 'cancelled'], 'default' => 'issued'],
                'subtotal'    => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'tax_amount'  => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'total_amount'=> ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'currency'    => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'MXN'],
                'notes'       => ['type' => 'TEXT', 'null' => true],
                'plugin_ref'  => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'created_by'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
                'updated_at'  => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('po_number');
            $this->forge->addKey('order_id');
            $this->forge->addForeignKey('order_id', 'sales_orders', 'id', 'CASCADE', 'RESTRICT');
            $this->forge->addForeignKey('client_id', 'crm_clients', 'id', 'CASCADE', 'RESTRICT');
            $this->forge->createTable('sales_customer_pos', true);
        }

        if ($this->db->tableExists('sales_customer_po_items')) {
            return;
        }

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'customer_po_id'=> ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'order_item_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'line_code'     => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'item_name'     => ['type' => 'VARCHAR', 'constraint' => 255],
            'quantity'      => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            'unit_price'    => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'billing_cycle' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('customer_po_id');
        $this->forge->addForeignKey('customer_po_id', 'sales_customer_pos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sales_customer_po_items', true);
    }

    private function extendFollowups(): void
    {
        if (! $this->db->tableExists('crm_followups')) {
            return;
        }

        if (! $this->db->fieldExists('contact_id', 'crm_followups')) {
            $this->forge->addColumn('crm_followups', [
                'contact_id' => [
                    'type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'client_id',
                ],
            ]);
        }
        if (! $this->db->fieldExists('followup_status', 'crm_followups')) {
            $this->forge->addColumn('crm_followups', [
                'followup_status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['open', 'in_progress', 'waiting', 'closed'],
                    'default'    => 'open',
                    'after'      => 'status',
                ],
            ]);
        }
        if (! $this->db->fieldExists('incident', 'crm_followups')) {
            $this->forge->addColumn('crm_followups', [
                'incident' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true, 'after' => 'title'],
            ]);
        }

        $this->db->query("
            ALTER TABLE crm_followups
            MODIFY COLUMN followup_type ENUM(
                'comment','status_change','meeting','call','email','quote','order','service',
                'whatsapp','ticket','support'
            ) NOT NULL DEFAULT 'comment'
        ");
    }
}
