# Esquema de Base de Datos - Módulo de Proveedores y Órdenes de Compra
**Framework Objetivo:** CodeIgniter 4 (Migraciones)
**Motor de BD:** MySQL / MariaDB (InnoDB)
**Reglas Generales:**
- Todas las llaves primarias (`id`) deben ser `INT(11) UNSIGNED AUTO_INCREMENT`.
- Todas las llaves foráneas deben ser `INT(11) UNSIGNED` para coincidir con las primarias.
- Las tablas que soporten Soft Deletes deben incluir `created_at` (DATETIME), `updated_at` (DATETIME) y `deleted_at` (DATETIME, NULL).
- Cotejamiento predeterminado: `utf8mb4_unicode_ci`.

---

## 1. Tabla: `fin_supplier_contacts`
**Descripción:** Contactos adicionales y secundarios de los proveedores.
- `id`: PK.
- `fin_supplier_id`: INT(11) UNSIGNED, NOT NULL. (FK a `fin_suppliers.id`, CASCADE/CASCADE).
- `contact_name`: VARCHAR(255), NOT NULL.
- `job_title`: VARCHAR(150), NULL. (Puesto del contacto).
- `email`: VARCHAR(255), NULL.
- `phone`: VARCHAR(50), NULL.
- `is_primary`: TINYINT(1), NOT NULL DEFAULT 0. (Indicador de contacto principal).
- Soft Deletes: Sí (`deleted_at`).

---

## 2. Tabla: `fin_purchase_orders`
**Descripción:** Órdenes de compra emitidas a los proveedores.
- `id`: PK.
- `fin_supplier_id`: INT(11) UNSIGNED, NOT NULL. (FK a `fin_suppliers.id`, RESTRICT/CASCADE).
- `po_number`: VARCHAR(50), NOT NULL, UNIQUE. (Número único de la orden de compra, ej. PO-20260526-XXXX).
- `status`: ENUM('draft', 'sent', 'approved', 'completed', 'cancelled'), NOT NULL DEFAULT 'draft'.
- `total_amount`: DECIMAL(15,2), NOT NULL DEFAULT 0.00.
- `currency`: VARCHAR(3), NOT NULL DEFAULT 'MXN'.
- `issue_date`: DATE, NULL. (Fecha de emisión).
- `delivery_date`: DATE, NULL. (Fecha estimada de entrega).
- `notes`: TEXT, NULL.
- Soft Deletes: Sí (`deleted_at`).

---

## 3. Tabla: `fin_purchase_order_items`
**Descripción:** Partidas (ítems) individuales que componen una orden de compra.
- `id`: PK.
- `fin_purchase_order_id`: INT(11) UNSIGNED, NOT NULL. (FK a `fin_purchase_orders.id`, CASCADE/CASCADE).
- `catalog_product_id`: INT(11) UNSIGNED, NULL. (FK a `catalog_products.id`, SET NULL/SET NULL).
- `description`: VARCHAR(255), NOT NULL. (Descripción del ítem o servicio).
- `quantity`: INT(11), NOT NULL DEFAULT 1.
- `unit_price`: DECIMAL(15,2), NOT NULL DEFAULT 0.00.
- `total_price`: DECIMAL(15,2), NOT NULL DEFAULT 0.00.
- Soft Deletes: No (Borrado físico o en cascada al eliminar la orden).
