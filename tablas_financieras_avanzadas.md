# Esquema de Base de Datos - Módulo Contable y Automatización (Fase 2)
**Framework:** CodeIgniter 4 (Migraciones)
**Motor:** MySQL/MariaDB (InnoDB)

---

## 1. Tabla: `fin_recurring_expenses`
**Descripción:** Plantillas para gastos que se repiten (Luz, Internet, Renta, Nómina). n8n usará esta tabla para generar registros automáticos en `fin_expenses`.
- `id`: INT(11) UNSIGNED PK AI.
- `supplier_id`: INT(11) UNSIGNED FK (fin_suppliers.id).
- `category_id`: INT(11) UNSIGNED FK (fin_expense_categories.id).
- `account_id`: INT(11) UNSIGNED FK (fin_accounts.id). (Cuenta sugerida para el pago).
- `description`: VARCHAR(255) NOT NULL. (Ej. "Pago Mensual Totalplay").
- `amount`: DECIMAL(15,2) NOT NULL.
- `currency`: VARCHAR(3) DEFAULT 'MXN'.
- `billing_day`: TINYINT(2) NOT NULL. (Día del mes en que se genera, ej: 15).
- `frequency`: ENUM('monthly', 'quarterly', 'yearly') DEFAULT 'monthly'.
- `last_generated_at`: DATETIME NULL. (Para evitar que n8n duplique el gasto el mismo mes).
- `is_active`: TINYINT(1) DEFAULT 1.
- `created_at`, `updated_at`, `deleted_at`: DATETIME NULL.

---

## 2. Tabla: `fin_ledger_entries` (Libro Mayor)
**Descripción:** El corazón contable. Registra cada movimiento de dinero (Entradas y Salidas) para generar el Estado de Resultados (P&L).
- `id`: INT(11) UNSIGNED PK AI.
- `account_id`: INT(11) UNSIGNED FK (fin_accounts.id).
- `reference_type`: VARCHAR(50). (Ej: 'sale', 'expense', 'refund', 'transfer').
- `reference_id`: INT(11) UNSIGNED. (ID de la tabla sales_orders o fin_payment_folios).
- `type`: ENUM('debit', 'credit') NOT NULL. (Debit: Entrada, Credit: Salida).
- `amount`: DECIMAL(15,2) NOT NULL.
- `balance_after`: DECIMAL(15,2) NOT NULL. (Saldo de la cuenta después del movimiento).
- `notes`: TEXT NULL.
- `entry_date`: DATETIME DEFAULT CURRENT_TIMESTAMP.
- `created_at`, `updated_at`, `deleted_at`: DATETIME NULL.

---

## 3. Tabla: `fin_assets` (Activos Fijos)
**Descripción:** Control de equipo (Servidores, laptops, telescopios). No es un gasto directo, es una inversión que se deprecia.
- `id`: INT(11) UNSIGNED PK AI.
- `name`: VARCHAR(255) NOT NULL. (Ej. "Servidor Dell PowerEdge R740").
- `supplier_id`: INT(11) UNSIGNED FK (fin_suppliers.id).
- `purchase_date`: DATE NOT NULL.
- `purchase_price`: DECIMAL(15,2) NOT NULL.
- `currency`: VARCHAR(3) DEFAULT 'MXN'.
- `serial_number`: VARCHAR(100) NULL.
- `depreciation_months`: INT(3) DEFAULT 36. (Meses en los que el activo pierde su valor contable).
- `status`: ENUM('active', 'retired', 'maintenance', 'sold') DEFAULT 'active'.
- `notes`: TEXT NULL.
- `created_at`, `updated_at`, `deleted_at`: DATETIME NULL.