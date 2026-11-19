# Esquema de Base de Datos — 2026-08-18
## Proveedores: documentos de OC, correo saliente y servicios recurrentes

**Framework:** CodeIgniter 4 (Migraciones)
**Motor:** MySQL / MariaDB (InnoDB)
**Cotejamiento:** `utf8mb4_unicode_ci`
**Uso:** Exportar / recrear entidades en Lucidchart (Lucidapp).

### Reglas generales
- PK `id`: `INT(11) UNSIGNED AUTO_INCREMENT`.
- FK: `INT(11) UNSIGNED` (mismo tipo que la PK referenciada).
- Soft deletes: `created_at`, `updated_at`, `deleted_at` (DATETIME, `deleted_at` NULL).
- Fechas de negocio: `DATE` en ISO `YYYY-MM-DD`.
- Montos: `DECIMAL(15,2)`.

### Leyenda Lucid
- **CREATE** = entidad nueva.
- **ALTER** = columnas nuevas sobre tabla ya existente. No recrear la tabla.
- **YA EXISTE** = contexto / relaciones; no migrar de nuevo.

---

## Diagrama de relaciones (Lucid / ER)

```
org_company_profile 1 ──< org_branches
org_branches 1 ──< fin_purchase_orders
org_branches 1 ──< fin_recurring_expenses     [ALTER]
org_branches 1 ──< fin_expenses               [ALTER]

fin_suppliers 1 ──< fin_purchase_orders
fin_suppliers 1 ──< fin_supplier_contacts
fin_suppliers 1 ──< fin_recurring_expenses
fin_suppliers 1 ──< fin_expenses

fin_purchase_orders 1 ──< fin_purchase_order_items
fin_purchase_orders 1 ──< fin_purchase_order_documents          [CREATE]
fin_purchase_orders 1 ──< fin_purchase_order_email_logs         [CREATE]

fin_purchase_order_documents M ──< fin_po_email_log_documents >── N fin_purchase_order_email_logs  [CREATE pivot]

fin_recurring_expenses 1 ──< fin_expenses                      [ALTER FK]
fin_expenses 1 ──< fin_expense_documents                       [CREATE]
fin_expense_categories 1 ──< fin_expenses
fin_expense_categories 1 ──< fin_recurring_expenses
fin_accounts 1 ──< fin_recurring_expenses
users 1 ──< fin_purchase_order_documents.uploaded_by
users 1 ──< fin_purchase_order_email_logs.sent_by
users 1 ──< fin_expense_documents.uploaded_by
```

---

## YA EXISTE (no crear, solo relacionar)

### `fin_suppliers`
- `id`, `commercial_name`, `legal_name`, `supplier_type`, `tax_id`, `contact_email`, `contact_phone`, `bank_details`, `status`, timestamps + soft delete.

### `fin_supplier_contacts`
- `id`, `fin_supplier_id` (FK CASCADE/CASCADE), `contact_name`, `job_title`, `email`, `phone`, `is_primary`, timestamps + soft delete.

### `fin_purchase_orders`
- `id`
- `fin_supplier_id` (FK `fin_suppliers.id`, RESTRICT/CASCADE)
- `org_branch_id` (FK `org_branches.id`, RESTRICT/CASCADE) — ya migrado 2026-06-03
- `po_number` VARCHAR(50) UNIQUE
- `status` ENUM('draft','sent','approved','completed','cancelled') DEFAULT 'draft'
- `total_amount` DECIMAL(15,2) DEFAULT 0.00
- `currency` VARCHAR(3) DEFAULT 'MXN'
- `issue_date` DATE NULL
- `delivery_date` DATE NULL
- `notes` TEXT NULL
- Soft deletes: Sí

### `fin_purchase_order_items`
- `id`
- `fin_purchase_order_id` (FK CASCADE/CASCADE)
- `catalog_product_id` (FK SET NULL/SET NULL, NULL)
- `description` VARCHAR(255)
- `quantity` INT(11) DEFAULT 1
- `unit_price` DECIMAL(15,2)
- `total_price` DECIMAL(15,2)
- Soft deletes: No

### `org_branches` (perfil fiscal del comprador en el PDF)
- `id`, `company_id`, `name`, `commercial_name`, `legal_name`, `tax_id`, `address`, `phone`, `email`, `logo_path`, `is_main`, `active`, …

### `org_company_profile` (fallback de encabezado PDF)
- `id`, `commercial_name`, `legal_name`, `tax_id`, `logo_path`, `primary_email`, `primary_phone`, `website_url`, …

### `fin_recurring_expenses` (plantilla — ver ALTER más abajo)
Hoy: `id`, `supplier_id`, `category_id`, `account_id`, `description`, `service_type`, `amount`, `currency`, `billing_day`, `frequency` ENUM('weekly','monthly','quarterly','semiannual','yearly'), `last_generated_at`, `is_active`, timestamps + soft delete.

### `fin_expenses` (instancia de pago — ver ALTER más abajo)
Hoy: `id`, `supplier_id`, `category_id`, `total_amount`, `due_date`, `status` ENUM('pending','partial','paid','cancelled'), `invoice_url`, timestamps + soft delete.

---

## CREATE 1 — `fin_purchase_order_documents`

**Descripción:** Expediente de archivos de una OC. Dos canales que **no se mezclan**:
- `inbound_archive` = histórico de facturas del proveedor (PDF / XML / imagen). **No se envían por correo.**
- `outbound` = comprobantes de pago, aclaraciones, oficios. **Sí se pueden adjuntar** a emails. Permanecen en disco tras el envío.

**Migración:** `app/Database/Migrations/Accounting/2026-08-18-120000_CreateFinPurchaseOrderDocuments.php`

- `id`: PK INT(11) UNSIGNED AI
- `fin_purchase_order_id`: INT(11) UNSIGNED NOT NULL. FK `fin_purchase_orders.id` ON DELETE CASCADE ON UPDATE CASCADE
- `doc_channel`: ENUM('inbound_archive','outbound') NOT NULL
- `doc_type`: ENUM(
    'supplier_invoice_pdf',
    'supplier_invoice_xml',
    'supplier_invoice_image',
    'payment_proof',
    'clarification',
    'other'
  ) NOT NULL
- `original_name`: VARCHAR(255) NOT NULL
- `stored_name`: VARCHAR(255) NOT NULL
- `file_path`: VARCHAR(255) NOT NULL  *(relativa a WRITEPATH/uploads/, ej. `financial/po/12/xxxx.pdf`)*
- `mime_type`: VARCHAR(100) NULL
- `file_size`: INT(11) UNSIGNED NULL  *(bytes)*
- `notes`: VARCHAR(255) NULL  *(folio, UUID CFDI capturado a mano, banco — no parsear XML)*
- `uploaded_by`: INT(11) UNSIGNED NULL. FK `users.id` ON DELETE SET NULL ON UPDATE CASCADE
- `created_at` DATETIME NULL
- `updated_at` DATETIME NULL
- `deleted_at` DATETIME NULL  *(soft delete; el archivo físico se conserva)*

**Índices:** PK `id`; KEY `fin_purchase_order_id`; KEY `doc_channel`; KEY `doc_type`.

**Regla de tipos vs canal (validar en modelo):**
- `inbound_archive` → solo `supplier_invoice_pdf` | `supplier_invoice_xml` | `supplier_invoice_image`
- `outbound` → solo `payment_proof` | `clarification` | `other`

---

## CREATE 2 — `fin_purchase_order_email_logs`

**Descripción:** Historial de correos enviados desde una OC (enviar OC y/o respuesta con adjuntos). El cuerpo se guarda como snapshot. Los archivos adjuntos **no se borran**.

**Migración:** `app/Database/Migrations/Accounting/2026-08-18-120100_CreateFinPurchaseOrderEmailLogs.php` *(esta misma migración crea también el pivot)*

- `id`: PK INT(11) UNSIGNED AI
- `fin_purchase_order_id`: INT(11) UNSIGNED NOT NULL. FK `fin_purchase_orders.id` ON DELETE CASCADE ON UPDATE CASCADE
- `email_kind`: ENUM('send_po','reply_with_attachments') NOT NULL
- `to_emails`: TEXT NOT NULL  *(lista separada por comas o JSON de destinatarios)*
- `cc_emails`: TEXT NULL
- `subject`: VARCHAR(255) NOT NULL
- `body_html`: TEXT NOT NULL
- `attached_po_pdf`: TINYINT(1) NOT NULL DEFAULT 0
- `smtp_status`: ENUM('sent','failed') NOT NULL DEFAULT 'sent'
- `smtp_error`: VARCHAR(500) NULL
- `sent_by`: INT(11) UNSIGNED NULL. FK `users.id` ON DELETE SET NULL ON UPDATE CASCADE
- `sent_at`: DATETIME NULL
- `created_at` DATETIME NULL
- `updated_at` DATETIME NULL
- `deleted_at` DATETIME NULL

**Índices:** PK `id`; KEY `fin_purchase_order_id`; KEY `email_kind`.

---

## CREATE 3 — `fin_po_email_log_documents` (pivot)

**Descripción:** Qué documentos **outbound** viajaron en cada email. Nunca incluir `inbound_archive`.

**Migración:** incluida en `2026-08-18-120100_CreateFinPurchaseOrderEmailLogs.php`

- `id`: PK INT(11) UNSIGNED AI
- `email_log_id`: INT(11) UNSIGNED NOT NULL. FK `fin_purchase_order_email_logs.id` ON DELETE CASCADE ON UPDATE CASCADE
- `document_id`: INT(11) UNSIGNED NOT NULL. FK `fin_purchase_order_documents.id` ON DELETE RESTRICT ON UPDATE CASCADE
- `created_at` DATETIME NULL

**Índices:** PK `id`; UNIQUE (`email_log_id`, `document_id`); KEY `document_id`.
**Soft deletes:** No (tabla pivot).

---

## ALTER 1 — `fin_recurring_expenses`

**Descripción:** Convierte la plantilla de servicio (luz, internet, basura, etc.) en un ítem calendarizable: sucursal, vigencia, próximo vencimiento.

**Migración:** `app/Database/Migrations/Accounting/2026-08-18-120200_AlterFinRecurringExpensesForCalendar.php`

Columnas a **añadir** (no borrar las existentes):

- `org_branch_id`: INT(11) UNSIGNED NULL. FK `org_branches.id` ON DELETE RESTRICT ON UPDATE CASCADE. After `supplier_id`.
- `start_date`: DATE NULL  *(inicio del servicio / contrato)*
- `end_date`: DATE NULL  *(renovación o fin; NULL = indefinido)*
- `next_due_date`: DATE NULL  *(próximo vencimiento calculado)*
- `billing_weekday`: TINYINT(1) UNSIGNED NULL  *(1=lunes … 7=domingo; solo si frequency=weekly)*
- `notify_days_before`: TINYINT(3) UNSIGNED NULL DEFAULT 3

**Índices nuevos:** KEY `org_branch_id`; KEY `next_due_date`; KEY `is_active`.

---

## ALTER 2 — `fin_expenses`

**Descripción:** Cada fila es un **vencimiento generado** (ocurrencia) de una plantilla recurrente. `status` ya existe: pending / partial / paid / cancelled. Contabilidad futura usará `category_id` + `fin_payment_folios`; **no** crear pólizas ahora.

**Migración:** `app/Database/Migrations/Accounting/2026-08-18-120300_AlterFinExpensesForRecurringOccurrences.php`

Columnas a **añadir**:

- `recurring_expense_id`: INT(11) UNSIGNED NULL. FK `fin_recurring_expenses.id` ON DELETE SET NULL ON UPDATE CASCADE
- `org_branch_id`: INT(11) UNSIGNED NULL. FK `org_branches.id` ON DELETE RESTRICT ON UPDATE CASCADE
- `period_label`: VARCHAR(20) NULL  *(ej. `2026-08`, `2026-W33`, `2026-Q3`)*
- `paid_amount`: DECIMAL(15,2) NOT NULL DEFAULT 0.00
- `currency`: VARCHAR(3) NOT NULL DEFAULT 'MXN'
- `notes`: TEXT NULL

**Índices nuevos:**
- KEY `recurring_expense_id`
- KEY `org_branch_id`
- KEY `due_date`
- KEY `status`
- UNIQUE (`recurring_expense_id`, `period_label`) — permite varios NULL de `recurring_expense_id` en MySQL; el generador nunca inserta `period_label` vacío en ocurrencias recurrentes.

---

## CREATE 4 — `fin_expense_documents`

**Descripción:** Comprobantes ligados a un **vencimiento recurrente** (CFE, internet, basura). Distinto de los documentos de OC. No se mezclan.

**Migración:** `app/Database/Migrations/Accounting/2026-08-18-120400_CreateFinExpenseDocuments.php`

- `id`: PK INT(11) UNSIGNED AI
- `fin_expense_id`: INT(11) UNSIGNED NOT NULL. FK `fin_expenses.id` ON DELETE CASCADE ON UPDATE CASCADE
- `doc_type`: ENUM('payment_proof','invoice','other') NOT NULL DEFAULT 'payment_proof'
- `original_name`: VARCHAR(255) NOT NULL
- `stored_name`: VARCHAR(255) NOT NULL
- `file_path`: VARCHAR(255) NOT NULL  *(ej. `financial/expenses/45/xxxx.pdf`)*
- `mime_type`: VARCHAR(100) NULL
- `file_size`: INT(11) UNSIGNED NULL
- `notes`: VARCHAR(255) NULL
- `uploaded_by`: INT(11) UNSIGNED NULL. FK `users.id` ON DELETE SET NULL ON UPDATE CASCADE
- `created_at` DATETIME NULL
- `updated_at` DATETIME NULL
- `deleted_at` DATETIME NULL

**Índices:** PK `id`; KEY `fin_expense_id`.

---

## Resumen de migraciones a crear

| Archivo | Acción |
|---|---|
| `2026-08-18-120000_CreateFinPurchaseOrderDocuments.php` | CREATE `fin_purchase_order_documents` |
| `2026-08-18-120100_CreateFinPurchaseOrderEmailLogs.php` | CREATE `fin_purchase_order_email_logs` + `fin_po_email_log_documents` |
| `2026-08-18-120200_AlterFinRecurringExpensesForCalendar.php` | ALTER `fin_recurring_expenses` |
| `2026-08-18-120300_AlterFinExpensesForRecurringOccurrences.php` | ALTER `fin_expenses` |
| `2026-08-18-120400_CreateFinExpenseDocuments.php` | CREATE `fin_expense_documents` |

**No crear migraciones para:** `fin_suppliers`, `fin_supplier_contacts`, `fin_purchase_orders`, `fin_purchase_order_items`, `fin_ledger_entries`, `fin_payment_folios`, `org_branches`, `org_company_profile`.

**Disco (no es tabla):**
- OC: `WRITEPATH/uploads/financial/po/{po_id}/`
- Gastos: `WRITEPATH/uploads/financial/expenses/{expense_id}/`
- Servir archivos solo por controlador autenticado (mismo patrón que RRHH). Nunca `public_html`.

---

## Entidades Lucid — checklist de importación

1. `fin_purchase_order_documents` (nueva)
2. `fin_purchase_order_email_logs` (nueva)
3. `fin_po_email_log_documents` (nueva, pivot)
4. `fin_expense_documents` (nueva)
5. Actualizar `fin_recurring_expenses` (6 campos nuevos)
6. Actualizar `fin_expenses` (6 campos nuevos)
7. Relaciones a `fin_purchase_orders`, `fin_suppliers`, `org_branches`, `users`
