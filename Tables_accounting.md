# Esquema de Base de Datos - Módulo Financiero (Egresos y Cuentas)
**Framework Objetivo:** CodeIgniter 4 (Migraciones)
**Motor de BD:** MySQL / MariaDB (InnoDB)
**Reglas Generales:**
- Todas las llaves primarias (`id`) deben ser `INT(11) UNSIGNED AUTO_INCREMENT`.
- Todas las llaves foráneas deben ser `INT(11) UNSIGNED` para coincidir con las primarias.
- Todas las tablas deben incluir `created_at` (DATETIME), `updated_at` (DATETIME) y `deleted_at` (DATETIME, NULL) para Soft Deletes.
- Cotejamiento predeterminado: `utf8mb4_unicode_ci`.

---

## 1. Tabla: `fin_accounts`
**Descripción:** Origen de los fondos (Caja chica, cuentas bancarias, pasarelas de pago).
- `id`: PK.
- `name`: VARCHAR(100), NOT NULL. (Ej. "Caja Chica Oficina", "BBVA Principal").
- `type`: ENUM('cash', 'bank', 'digital'), NOT NULL DEFAULT 'bank'.
- `currency`: VARCHAR(3), NOT NULL DEFAULT 'MXN'.
- `balance`: DECIMAL(15,2), NOT NULL DEFAULT 0.00. (Saldo actual).
- `is_active`: TINYINT(1), NOT NULL DEFAULT 1. (1 = Activa, 0 = Inactiva).

---

## 2. Tabla: `fin_suppliers`
**Descripción:** Catálogo de proveedores de servicios o productos.
- `id`: PK.
- `commercial_name`: VARCHAR(255), NOT NULL.
- `tax_id`: VARCHAR(50), NULL. (RFC o Identificador fiscal).
- `contact_email`: VARCHAR(255), NULL.
- `status`: TINYINT(1), NOT NULL DEFAULT 1.

---

## 3. Tabla: `fin_expense_categories`
**Descripción:** Clasificación contable de los gastos (Infraestructura, Servicios, Papelería).
- `id`: PK.
- `name`: VARCHAR(100), NOT NULL.
- `expense_type`: ENUM('fixed', 'variable'), NOT NULL. (Gastos fijos o variables).
- `parent_id`: INT(11) UNSIGNED, NULL. (Llave foránea recursiva a `fin_expense_categories.id` para subcategorías).

---

## 4. Tabla: `fin_expenses`
**Descripción:** Cuentas por pagar u obligaciones de pago (facturas recibidas antes del desembolso).
- `id`: PK.
- `supplier_id`: INT(11) UNSIGNED, NULL. (FK a `fin_suppliers.id`).
- `category_id`: INT(11) UNSIGNED, NOT NULL. (FK a `fin_expense_categories.id`).
- `total_amount`: DECIMAL(15,2), NOT NULL.
- `due_date`: DATE, NOT NULL. (Fecha límite de pago).
- `status`: ENUM('pending', 'partial', 'paid', 'cancelled'), NOT NULL DEFAULT 'pending'.
- `invoice_url`: VARCHAR(255), NULL. (Ruta al archivo o comprobante).

---

## 5. Tabla: `fin_payment_folios`
**Descripción:** Registro de desembolsos reales, solicitudes de fondos y autorización directiva.
- `id`: PK.
- `folio_number`: VARCHAR(20), NOT NULL, UNIQUE. (Número de seguimiento alfanumérico, híbrido manual/automático).
- `expense_id`: INT(11) UNSIGNED, NULL. (FK a `fin_expenses.id`. Nulo si es gasto rápido sin cuenta por pagar previa).
- `account_id`: INT(11) UNSIGNED, NOT NULL. (FK a `fin_accounts.id`. De dónde sale el dinero).
- `amount_paid`: DECIMAL(15,2), NOT NULL. (Monto autorizado/pagado).
- `concept`: TEXT, NOT NULL. (Justificación del gasto).
- `status`: ENUM('requested', 'approved', 'executed', 'rejected'), NOT NULL DEFAULT 'requested'.
- `requested_by`: INT(11) UNSIGNED, NOT NULL. (FK a la tabla de usuarios del sistema - Empleado que solicita).
- `approved_by`: INT(11) UNSIGNED, NULL. (FK a la tabla de usuarios del sistema - Directivo que autoriza).