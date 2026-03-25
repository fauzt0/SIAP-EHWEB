<?php

namespace App\Database\Migrations\Sales;

use CodeIgniter\Database\Migration;

class SalesAndBilling extends Migration
{
    public function up()
    {
        $this->db->disableForeignKeyChecks();

        // 1. sales_orders (Cabecera de la venta)
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'status'           => ['type' => 'ENUM', 'constraint' => ['pending', 'paid', 'cancelled', 'refunded'], 'default' => 'pending'],
            'subtotal'         => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'tax_amount'       => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'discount_amount'  => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'total_amount'     => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'currency'         => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'MXN'],
            'notes'            => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('sales_orders', true);

        // 2. sales_order_items (Detalle con fechas independientes)
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'order_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'product_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'plan_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'item_name'        => ['type' => 'VARCHAR', 'constraint' => 255], // Snapshot del nombre al momento de compra
            'unit_price'       => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'quantity'         => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            'period_start'     => ['type' => 'DATE', 'null' => true], // Resuelve desfase de hosting/dominio
            'period_end'       => ['type' => 'DATE', 'null' => true],
            'service_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true], // Link a servicio activo
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('order_id', 'sales_orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sales_order_items', true);

        // 3. billing_invoices (Facturas CFDI/Fiscales)
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'order_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'invoice_number'   => ['type' => 'VARCHAR', 'constraint' => 50],
            'uuid'             => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true], // Para SAT en México
            'status'           => ['type' => 'ENUM', 'constraint' => ['draft', 'issued', 'cancelled'], 'default' => 'issued'],
            'pdf_url'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'xml_url'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('billing_invoices', true);

        // 4. billing_payments (El recibo o comprobante de ingreso)
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'order_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'invoice_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'payment_method'   => ['type' => 'ENUM', 'constraint' => ['paypal', 'stripe', 'transfer', 'cash'], 'default' => 'transfer'],
            'transaction_id'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true], // ID de la pasarela
            'amount_paid'      => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'currency'         => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'MXN'],
            'payment_date'     => ['type' => 'DATETIME'],
            'notes'            => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('order_id', 'sales_orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('billing_payments', true);

        // 5. customer_services (Los servicios activos que generan renovaciones)
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'product_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'host_name'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'           => ['type' => 'ENUM', 'constraint' => ['active', 'suspended', 'terminated'], 'default' => 'active'],
            'next_due_date'    => ['type' => 'DATE'],
            'last_renewal_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('customer_services', true);

        // 6. service_renewal_logs (Historial de renovaciones)
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'service_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'order_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'old_due_date'     => ['type' => 'DATE'],
            'new_due_date'     => ['type' => 'DATE'],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('service_renewal_logs', true);


        //transacciones del monedero del cliente (saldos positivos y negativos)
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'order_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'type'             => ['type' => 'ENUM', 'constraint' => ['credit', 'debit', 'refund', 'adjustment'], 'default' => 'credit'],
            'amount'           => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'description'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'balance_before'   => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'balance_after'    => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('customer_wallet_transactions', true);


        $this->db->enableForeignKeyChecks();
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();
        $this->forge->dropTable('service_renewal_logs');
        $this->forge->dropTable('customer_services');
        $this->forge->dropTable('billing_invoices');
        $this->forge->dropTable('sales_order_items');
        $this->forge->dropTable('sales_orders');
        $this->db->enableForeignKeyChecks();
    }
}
