<?php

namespace App\Database\Migrations\Sales;

use CodeIgniter\Database\Migration;

/**
 * RefactorSalesAndBilling
 *
 * Migración de refactorización del módulo de Ventas y Facturación.
 * Este archivo NO destruye ni recrea las tablas originales.
 * Únicamente agrega y modifica columnas sobre las tablas existentes
 * generadas en el batch 1 (2026-03-05-213506_SalesAndBilling).
 *
 * Objetivos:
 *  1. Soporte Omnicanal (POS, Online, Backend, Renovación Automática).
 *  2. Parcialidades y estado 'partial' en órdenes y pagos.
 *  3. Registro de comprobantes físicos (receipt_route).
 *  4. Integración de Mercado Pago y otros métodos de pago.
 *  5. Generalización de customer_services (agnóstico al tipo de producto).
 *  6. Llaves foráneas correctamente registradas con CI4 Forge.
 *  7. Soft Deletes (deleted_at) en todas las tablas que les faltaba.
 */
class RefactorSalesAndBilling extends Migration
{
    public function up()
    {
        $this->db->disableForeignKeyChecks();

        // =====================================================================
        // TABLA: sales_orders
        // =====================================================================
        // Cambios:
        //   - Se agrega 'channel': indica el origen del pedido (tienda online,
        //     punto de venta físico, generada por staff desde backend, o
        //     renovación automática disparada por un cron/webhook).
        //   - Se agrega 'order_type': diferencia contablemente una venta nueva
        //     de una renovación, upgrade o downgrade.
        //   - Se extiende 'status' para incluir 'partial' (pago incompleto o en
        //     parcialidades, la orden queda abierta hasta ser saldada al 100%).
        //     NOTA: MySQL no permite modificar un ENUM directamente con Forge en
        //     columnas ya existentes; usamos una consulta DB::query para el ALTER.
        //   - Se agrega 'discount_code': almacena el cupón o justificación del
        //     descuento para trazabilidad de marketing.
        //   - Se agrega 'created_by': FK al usuario del sistema (staff) que
        //     registró la venta en POS o desde el panel (nulo = venta propia del cliente).
        //   - Se agrega 'deleted_at' para Soft Delete estándar de CI4.
        // =====================================================================

        $this->forge->addColumn('sales_orders', [
            'channel' => [
                'type' => 'ENUM',
                'constraint' => ['online', 'pos', 'backend', 'auto_renewal'],
                'default' => 'online',
                'after' => 'user_id',
            ],
            'order_type' => [
                'type' => 'ENUM',
                'constraint' => ['new', 'renewal', 'upgrade', 'downgrade'],
                'default' => 'new',
                'after' => 'channel',
            ],
            'discount_code' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'discount_amount',
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'notes',
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'updated_at',
            ],
        ]);

        // Extender el ENUM status de sales_orders para incluir 'partial'.
        // CI4 Forge no soporta modificación de columnas ENUM ya existentes;
        // se ejecuta la sentencia DDL directamente para máxima compatibilidad.
        $this->db->query("
            ALTER TABLE `sales_orders`
            MODIFY COLUMN `status`
            ENUM('pending', 'partial', 'paid', 'cancelled', 'refunded')
            NOT NULL DEFAULT 'pending'
        ");

        // FK: sales_orders.created_by → users.id (Vendedor o Agente de soporte)
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'SET NULL');
        // CI4 Forge crea las FKs por tabla, se aplica con processIndexes:
        $this->db->query("
            ALTER TABLE `sales_orders`
            ADD CONSTRAINT `fk_sales_orders_created_by`
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
            ON DELETE SET NULL ON UPDATE SET NULL
        ");

        // =====================================================================
        // TABLA: sales_order_items
        // =====================================================================
        // Cambios:
        //   - Se añaden 'discount_amount' y 'tax_amount' a nivel de ítem para
        //     soportar descuentos parciales por línea y facturación con IVA mixto.
        //   - Se agrega 'deleted_at' para Soft Delete.
        //   - Se agrega FK explícita a catalog_products para integridad referencial.
        // =====================================================================

        $this->forge->addColumn('sales_order_items', [
            'discount_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0.00,
                'after' => 'unit_price',
            ],
            'tax_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0.00,
                'after' => 'discount_amount',
            ],
            'total_line' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0.00,
                'after' => 'tax_amount',
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

        // FK: sales_order_items.product_id → catalog_products.id
        // La columna existía como INT sin restricción referencial. Se añade ahora.
        $this->db->query("
            ALTER TABLE `sales_order_items`
            ADD CONSTRAINT `fk_order_items_product_id`
            FOREIGN KEY (`product_id`) REFERENCES `catalog_products`(`id`)
            ON DELETE RESTRICT ON UPDATE RESTRICT
        ");

        // =====================================================================
        // TABLA: billing_payments
        // =====================================================================
        // Cambios:
        //   - Se extiende 'payment_method' para incluir mercadopago, wallet y
        //     pos_terminal (tarjeta física en mostrador).
        //   - Se agrega 'status': los pagos físicos por transferencia quedan en
        //     'pending' hasta ser verificados; los de pasarela quedan en 'completed'
        //     automáticamente al recibir el webhook.
        //   - Se agrega 'receipt_route': ruta al comprobante físico (foto de
        //     depósito, folio de transferencia, etc.).
        //   - Se agrega 'gateway_reference': ID de referencia de la pasarela de
        //     pago (MercadoPago preference_id, Stripe charge_id, etc.) para
        //     conciliación y auditorías antifraude.
        //   - FK billing_payments.order_id → sales_orders.id (faltaba en original).
        // =====================================================================

        // Extender el ENUM payment_method con todos los métodos ominicanal.
        $this->db->query("
            ALTER TABLE `billing_payments`
            MODIFY COLUMN `payment_method`
            ENUM('paypal', 'stripe', 'mercadopago', 'transfer', 'cash', 'wallet', 'pos_terminal')
            NOT NULL DEFAULT 'transfer'
        ");

        $this->forge->addColumn('billing_payments', [
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'completed', 'failed', 'refunded'],
                'default' => 'pending',
                'after' => 'notes',
            ],
            'receipt_route' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'status',
                // Comprobantes físicos: depósito, transferencia, recibo de caja POS.
            ],
            'gateway_reference' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'receipt_route',
                // JSON o ID de la pasarela (Mercado Pago, Stripe) para auditorías.
            ],
        ]);

        // FK: billing_payments.order_id → sales_orders.id (faltaba en el original)
        $this->db->query("
            ALTER TABLE `billing_payments`
            ADD CONSTRAINT `fk_billing_payments_order_id`
            FOREIGN KEY (`order_id`) REFERENCES `sales_orders`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ");

        // =====================================================================
        // TABLA: customer_services
        // =====================================================================
        // Cambios:
        //   - Se renombra 'host_name' a 'reference_name' para que sea genérico
        //     y sirva para cualquier producto: dominio, VPS, consultoría, etc.
        //   - Se agrega 'plan_id': congela el plan contratado en el servicio activo
        //     para que la renovación sepa qué precio cobrar aunque el catálogo cambie.
        //   - Se agrega 'billing_cycle': ciclo de facturación del servicio (mensual,
        //     anual, etc.) copiado del plan al momento de la contratación.
        //   - Se agrega 'auto_renew': si es 1, el cron/webhook intenta cobrar
        //     automáticamente; si es 0 solo envía aviso de vencimiento al cliente.
        //   - Se agregan timestamps estándar de CI4: created_at, updated_at, deleted_at.
        //   - FK customer_services.product_id → catalog_products.id
        //   - FK customer_services.plan_id → catalog_product_plans.id
        // =====================================================================

        // NOTA: MySQL no permite renombrar columnas con Forge en versiones antiguas.
        // Se usa ALTER TABLE RENAME COLUMN para máxima compatibilidad con MariaDB 10.5+.
        $this->db->query("
            ALTER TABLE `customer_services`
            RENAME COLUMN `host_name` TO `reference_name`
        ");

        $this->forge->addColumn('customer_services', [
            'plan_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'product_id',
            ],
            'billing_cycle' => [
                'type' => 'ENUM',
                'constraint' => ['one_time', 'monthly', 'yearly', 'custom'],
                'default' => 'monthly',
                'after' => 'plan_id',
            ],
            'auto_renew' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'billing_cycle',
                // 1 = Pasarela cobra automáticamente; 0 = Se notifica al cliente (pago manual).
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'last_renewal_at',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'created_at',
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'updated_at',
            ],
        ]);

        // FK: customer_services.product_id → catalog_products.id
        $this->db->query("
            ALTER TABLE `customer_services`
            ADD CONSTRAINT `fk_customer_services_product_id`
            FOREIGN KEY (`product_id`) REFERENCES `catalog_products`(`id`)
            ON DELETE RESTRICT ON UPDATE RESTRICT
        ");

        // FK: customer_services.plan_id → catalog_product_plans.id
        $this->db->query("
            ALTER TABLE `customer_services`
            ADD CONSTRAINT `fk_customer_services_plan_id`
            FOREIGN KEY (`plan_id`) REFERENCES `catalog_product_plans`(`id`)
            ON DELETE SET NULL ON UPDATE SET NULL
        ");

        // =====================================================================
        // TABLA: service_renewal_logs
        // =====================================================================
        // Cambios:
        //   - FK service_renewal_logs.service_id → customer_services.id (faltaba).
        //   - FK service_renewal_logs.order_id → sales_orders.id (faltaba).
        // =====================================================================

        $this->db->query("
            ALTER TABLE `service_renewal_logs`
            ADD CONSTRAINT `fk_renewal_logs_service_id`
            FOREIGN KEY (`service_id`) REFERENCES `customer_services`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ");

        $this->db->query("
            ALTER TABLE `service_renewal_logs`
            ADD CONSTRAINT `fk_renewal_logs_order_id`
            FOREIGN KEY (`order_id`) REFERENCES `sales_orders`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ");

        // Agregar campos de auditoría faltantes
        $this->forge->addColumn('service_renewal_logs', [
            'updated_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'created_at'],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'updated_at'],
        ]);

        $this->forge->addColumn('customer_wallet_transactions', [
            'updated_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'created_at'],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'updated_at'],
        ]);

        $this->db->enableForeignKeyChecks();
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();

        // Revertir FKs agregadas (deben eliminarse antes de modificar columnas)
        $this->db->query('ALTER TABLE `sales_orders` DROP FOREIGN KEY `fk_sales_orders_created_by`');
        $this->db->query('ALTER TABLE `sales_order_items` DROP FOREIGN KEY `fk_order_items_product_id`');
        $this->db->query('ALTER TABLE `billing_payments` DROP FOREIGN KEY `fk_billing_payments_order_id`');
        $this->db->query('ALTER TABLE `customer_services` DROP FOREIGN KEY `fk_customer_services_product_id`');
        $this->db->query('ALTER TABLE `customer_services` DROP FOREIGN KEY `fk_customer_services_plan_id`');
        $this->db->query('ALTER TABLE `service_renewal_logs` DROP FOREIGN KEY `fk_renewal_logs_service_id`');
        $this->db->query('ALTER TABLE `service_renewal_logs` DROP FOREIGN KEY `fk_renewal_logs_order_id`');

        // Revertir sales_orders
        $this->forge->dropColumn('sales_orders', ['channel', 'order_type', 'discount_code', 'created_by', 'deleted_at']);
        $this->db->query("ALTER TABLE `sales_orders` MODIFY COLUMN `status` ENUM('pending','paid','cancelled','refunded') NOT NULL DEFAULT 'pending'");

        // Revertir sales_order_items
        $this->forge->dropColumn('sales_order_items', ['discount_amount', 'tax_amount', 'deleted_at']);

        // Revertir billing_payments
        $this->db->query("ALTER TABLE `billing_payments` MODIFY COLUMN `payment_method` ENUM('paypal','stripe','transfer','cash') NOT NULL DEFAULT 'transfer'");
        $this->forge->dropColumn('billing_payments', ['status', 'receipt_route', 'gateway_reference']);

        // Revertir customer_services
        $this->db->query("ALTER TABLE `customer_services` RENAME COLUMN `reference_name` TO `host_name`");
        $this->forge->dropColumn('customer_services', ['plan_id', 'billing_cycle', 'auto_renew', 'created_at', 'updated_at', 'deleted_at']);

        // Revertir campos de auditoría agregados
        $this->forge->dropColumn('service_renewal_logs', ['updated_at', 'deleted_at']);
        $this->forge->dropColumn('customer_wallet_transactions', ['updated_at', 'deleted_at']);

        $this->db->enableForeignKeyChecks();
    }
}
