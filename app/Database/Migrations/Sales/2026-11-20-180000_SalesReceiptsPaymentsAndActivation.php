<?php

namespace App\Database\Migrations\Sales;

use CodeIgniter\Database\Migration;

/**
 * Recibos internos, comprobantes de pago y activación de productos/servicios.
 * billing_invoices queda como puente (borrador) hacia el sistema de facturas CFDI.
 */
class SalesReceiptsPaymentsAndActivation extends Migration
{
    public function up(): void
    {
        $this->db->disableForeignKeyChecks();

        if (! $this->db->tableExists('sales_receipts')) {
            $this->forge->addField([
                'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'receipt_number'  => ['type' => 'VARCHAR', 'constraint' => 32],
                'client_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'order_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'invoice_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'status'          => ['type' => 'ENUM', 'constraint' => ['draft', 'issued', 'void'], 'default' => 'issued'],
                'subtotal'        => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'tax_amount'      => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'total_amount'    => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'currency'        => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'MXN'],
                'notes'           => ['type' => 'TEXT', 'null' => true],
                'issued_at'       => ['type' => 'DATETIME', 'null' => true],
                'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_at'      => ['type' => 'DATETIME', 'null' => true],
                'updated_at'      => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('receipt_number');
            $this->forge->addKey('client_id');
            $this->forge->addKey('order_id');
            $this->forge->addForeignKey('client_id', 'crm_clients', 'id', 'CASCADE', 'RESTRICT');
            $this->forge->addForeignKey('order_id', 'sales_orders', 'id', 'CASCADE', 'RESTRICT');
            $this->forge->createTable('sales_receipts', true);
        }

        if (! $this->db->tableExists('billing_invoices')) {
            $this->forge->addField([
                'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'order_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'client_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'receipt_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'invoice_number' => ['type' => 'VARCHAR', 'constraint' => 50],
                'uuid'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'status'         => ['type' => 'ENUM', 'constraint' => ['draft', 'issued', 'cancelled'], 'default' => 'draft'],
                'pdf_url'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'xml_url'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at'     => ['type' => 'DATETIME', 'null' => true],
                'updated_at'     => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('billing_invoices', true);
        } else {
            if (! $this->db->fieldExists('client_id', 'billing_invoices')) {
                $this->forge->addColumn('billing_invoices', [
                    'client_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'order_id'],
                ]);
            }
            if (! $this->db->fieldExists('receipt_id', 'billing_invoices')) {
                $this->forge->addColumn('billing_invoices', [
                    'receipt_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'client_id'],
                ]);
            }
        }

        if (! $this->db->tableExists('billing_payments')) {
            $this->forge->addField([
                'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'order_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'client_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'receipt_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'invoice_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'payment_method' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'transfer'],
                'transaction_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'amount_paid'    => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'currency'       => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'MXN'],
                'payment_date'   => ['type' => 'DATETIME', 'null' => true],
                'status'         => ['type' => 'ENUM', 'constraint' => ['pending', 'completed', 'failed', 'refunded'], 'default' => 'completed'],
                'notes'          => ['type' => 'TEXT', 'null' => true],
                'created_by'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_at'     => ['type' => 'DATETIME', 'null' => true],
                'updated_at'     => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('order_id', 'sales_orders', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('billing_payments', true);
        } else {
            if (! $this->db->fieldExists('client_id', 'billing_payments')) {
                $this->forge->addColumn('billing_payments', [
                    'client_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                ]);
            }
            if (! $this->db->fieldExists('receipt_id', 'billing_payments')) {
                $this->forge->addColumn('billing_payments', [
                    'receipt_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                ]);
            }
            if (! $this->db->fieldExists('created_by', 'billing_payments')) {
                $this->forge->addColumn('billing_payments', [
                    'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                ]);
            }
        }

        if (! $this->db->tableExists('sales_payment_proofs')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'client_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'order_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'payment_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'receipt_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'file_path'     => ['type' => 'VARCHAR', 'constraint' => 255],
                'original_name' => ['type' => 'VARCHAR', 'constraint' => 180],
                'mime_type'     => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
                'notes'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'uploaded_by'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_at'    => ['type' => 'DATETIME', 'null' => true],
                'updated_at'    => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('client_id');
            $this->forge->addForeignKey('client_id', 'crm_clients', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('sales_payment_proofs', true);
        }

        if ($this->db->tableExists('customer_services') && ! $this->db->fieldExists('activation_status', 'customer_services')) {
            $this->forge->addColumn('customer_services', [
                'activation_status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['pending', 'activated'],
                    'default'    => 'pending',
                ],
            ]);
        }

        if ($this->db->tableExists('sales_order_items') && ! $this->db->fieldExists('activation_status', 'sales_order_items')) {
            $this->forge->addColumn('sales_order_items', [
                'activation_status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['pending', 'activated'],
                    'default'    => 'pending',
                ],
            ]);
        }

        $this->db->enableForeignKeyChecks();
    }

    public function down(): void
    {
        $this->forge->dropTable('sales_payment_proofs', true);
        $this->forge->dropTable('sales_receipts', true);
    }
}
