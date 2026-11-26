<?php

namespace App\Database\Migrations\Sales;

use CodeIgniter\Database\Migration;

/**
 * Enlaza ventas existentes con CRM, cotizaciones y ciclos semestrales.
 * No recrea sales_orders / customer_services: solo columnas y tablas nuevas.
 */
class LinkSalesToCrmAndQuotes extends Migration
{
    public function up(): void
    {
        $this->db->disableForeignKeyChecks();

        if (! $this->db->tableExists('sales_orders')) {
            $this->forge->addField([
                'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'order_number'    => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
                'user_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'client_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'quote_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'status'          => ['type' => 'ENUM', 'constraint' => ['draft', 'pending', 'partial', 'paid', 'cancelled', 'refunded', 'confirmed'], 'default' => 'draft'],
                'channel'         => ['type' => 'ENUM', 'constraint' => ['online', 'pos', 'backend', 'auto_renewal'], 'default' => 'backend'],
                'order_type'      => ['type' => 'ENUM', 'constraint' => ['new', 'renewal', 'upgrade', 'downgrade'], 'default' => 'new'],
                'subtotal'        => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'tax_amount'      => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'discount_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'total_amount'    => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'currency'        => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'MXN'],
                'notes'           => ['type' => 'TEXT', 'null' => true],
                'plugin_ref'      => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_at'      => ['type' => 'DATETIME', 'null' => true],
                'updated_at'      => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('sales_orders', true);
        }

        if ($this->db->tableExists('sales_orders')) {
            if (! $this->db->fieldExists('client_id', 'sales_orders')) {
                $this->forge->addColumn('sales_orders', [
                    'client_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                        'null'       => true,
                        'after'      => 'user_id',
                    ],
                    'quote_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                        'null'       => true,
                        'after'      => 'client_id',
                    ],
                    'order_number' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 32,
                        'null'       => true,
                        'after'      => 'id',
                    ],
                    'plugin_ref' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 120,
                        'null'       => true,
                    ],
                ]);
            }

            $this->db->query("ALTER TABLE sales_orders MODIFY COLUMN user_id INT(11) UNSIGNED NULL");
            $this->db->query("
                ALTER TABLE sales_orders
                MODIFY COLUMN status ENUM('draft','pending','partial','paid','cancelled','refunded','confirmed')
                NOT NULL DEFAULT 'draft'
            ");
            if (! $this->db->fieldExists('channel', 'sales_orders')) {
                $this->forge->addColumn('sales_orders', [
                    'channel' => [
                        'type' => 'ENUM',
                        'constraint' => ['online', 'pos', 'backend', 'auto_renewal'],
                        'default' => 'backend',
                    ],
                    'order_type' => [
                        'type' => 'ENUM',
                        'constraint' => ['new', 'renewal', 'upgrade', 'downgrade'],
                        'default' => 'new',
                    ],
                ]);
            }
            if (! $this->db->fieldExists('deleted_at', 'sales_orders')) {
                $this->forge->addColumn('sales_orders', [
                    'deleted_at' => ['type' => 'DATETIME', 'null' => true],
                ]);
            }
        }

        if (! $this->db->tableExists('sales_order_items')) {
            $this->forge->addField([
                'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'order_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'product_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'plan_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'item_name'       => ['type' => 'VARCHAR', 'constraint' => 255],
                'unit_price'      => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'quantity'        => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
                'billing_cycle'   => ['type' => 'ENUM', 'constraint' => ['one_time', 'monthly', 'semiannual', 'yearly', 'annual', 'custom'], 'default' => 'one_time'],
                'period_start'    => ['type' => 'DATE', 'null' => true],
                'period_end'      => ['type' => 'DATE', 'null' => true],
                'service_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'discount_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'tax_amount'      => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('order_id', 'sales_orders', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('sales_order_items', true);
        }

        if ($this->db->tableExists('sales_order_items') && ! $this->db->fieldExists('billing_cycle', 'sales_order_items')) {
            $this->forge->addColumn('sales_order_items', [
                'billing_cycle' => [
                    'type'       => 'ENUM',
                    'constraint' => ['one_time', 'monthly', 'semiannual', 'yearly', 'annual', 'custom'],
                    'default'    => 'one_time',
                    'after'      => 'quantity',
                ],
            ]);
        }

        if (! $this->db->tableExists('customer_services')) {
            $this->forge->addField([
                'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'user_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'client_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'order_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'product_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'plan_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'reference_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'status'         => ['type' => 'ENUM', 'constraint' => ['active', 'suspended', 'terminated'], 'default' => 'active'],
                'billing_cycle'  => ['type' => 'ENUM', 'constraint' => ['one_time', 'monthly', 'semiannual', 'yearly', 'annual', 'custom'], 'default' => 'monthly'],
                'auto_renew'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'amount'         => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'next_due_date'  => ['type' => 'DATE', 'null' => true],
                'last_renewal_at'=> ['type' => 'DATETIME', 'null' => true],
                'plugin_ref'     => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'created_at'     => ['type' => 'DATETIME', 'null' => true],
                'updated_at'     => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('customer_services', true);
        }

        if ($this->db->tableExists('customer_services')) {
            if (! $this->db->fieldExists('client_id', 'customer_services')) {
                $this->forge->addColumn('customer_services', [
                    'client_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                        'null'       => true,
                        'after'      => 'user_id',
                    ],
                    'order_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                        'null'       => true,
                        'after'      => 'client_id',
                    ],
                    'amount' => [
                        'type'       => 'DECIMAL',
                        'constraint' => '15,2',
                        'default'    => 0.00,
                    ],
                    'plugin_ref' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 120,
                        'null'       => true,
                    ],
                ]);
            }

            $this->db->query("ALTER TABLE customer_services MODIFY COLUMN user_id INT(11) UNSIGNED NULL");
            $this->db->query("ALTER TABLE customer_services MODIFY COLUMN next_due_date DATE NULL");
            $this->db->query("
                ALTER TABLE customer_services
                MODIFY COLUMN billing_cycle ENUM('one_time','monthly','semiannual','yearly','annual','custom')
                NOT NULL DEFAULT 'monthly'
            ");
        }

        if ($this->db->tableExists('catalog_product_plans')) {
            $this->db->query("
                ALTER TABLE catalog_product_plans
                MODIFY COLUMN billing_cycle ENUM('one_time','monthly','semiannual','yearly','annual','custom')
                NOT NULL DEFAULT 'monthly'
            ");
        }

        if (! $this->db->tableExists('sales_quotes')) {
            $this->forge->addField([
                'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'quote_number'   => ['type' => 'VARCHAR', 'constraint' => 32],
                'client_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'parent_quote_id'=> ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'version'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 1],
                'status'         => ['type' => 'ENUM', 'constraint' => ['draft', 'sent', 'approved', 'rejected', 'expired', 'converted'], 'default' => 'draft'],
                'valid_until'    => ['type' => 'DATE', 'null' => true],
                'subtotal'       => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'tax_amount'     => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'total_amount'   => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'currency'       => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'MXN'],
                'notes'          => ['type' => 'TEXT', 'null' => true],
                'converted_order_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'plugin_ref'     => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'created_by'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_at'     => ['type' => 'DATETIME', 'null' => true],
                'updated_at'     => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('quote_number');
            $this->forge->addKey('client_id');
            $this->forge->addForeignKey('client_id', 'crm_clients', 'id', 'CASCADE', 'RESTRICT');
            $this->forge->createTable('sales_quotes', true);
        }

        if (! $this->db->tableExists('sales_quote_items')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'quote_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'product_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'plan_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'item_name'     => ['type' => 'VARCHAR', 'constraint' => 255],
                'unit_price'    => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'quantity'      => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
                'billing_cycle' => ['type' => 'ENUM', 'constraint' => ['one_time', 'monthly', 'semiannual', 'yearly', 'annual', 'custom'], 'default' => 'one_time'],
                'line_total'    => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
                'created_at'    => ['type' => 'DATETIME', 'null' => true],
                'updated_at'    => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('quote_id', 'sales_quotes', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('sales_quote_items', true);
        }

        $this->db->enableForeignKeyChecks();
    }

    public function down(): void
    {
        $this->db->disableForeignKeyChecks();
        $this->forge->dropTable('sales_quote_items', true);
        $this->forge->dropTable('sales_quotes', true);
        $this->db->enableForeignKeyChecks();
    }
}
