# Task 007: Fixes de auditoría — OC / documentos / recurrentes

## Objective
Apply **only** the corrections found by the orchestrator after Phase 0→6. Do **not** reimplement the module. Do **not** edit migrations that already ran (`2026-08-18-120000` … `120400`).

**MUST follow** `DOCUMENTACION_TECNICA.md` (§1 AJAX, §2 CSRF, §5 DataTables column_order, §7 permissions, §8 logs, §9 transactions in models, §15 outputData, §17 notifyShow, §19 validation).

---

## Recommended Model
- **Composer 2.5** or **Claude Sonnet**
- Scope is surgical: 1 new migration + a handful of PHP/JS files.

---

## Do NOT
- Recreate tables or alter the 5 existing 2026-08-18 migrations (they already ran).
- Touch `fin_ledger_entries` / `fin_payment_folios`.
- Hardcode SMTP credentials or change `Email.php` protocol (operator fills SMTP later).
- Parse CFDI XML.
- Put files in `FCPATH` / `public_html`.
- Run transactions in controllers.
- Commit unless the user asks.
- Invent new tables.

---

## BLOCKING — 1. Nueva migración (única)

Create **exactly**:
`app/Database/Migrations/Accounting/2026-08-18-120500_FixFinExpensesRecurringIndexes.php`

Namespace: `App\Database\Migrations\Accounting`.

`up()` must use **raw SQL** (`$this->db->query(...)`). **Do not** use `$this->forge->addKey()` after `addColumn()` — that was why indexes never appeared.

### A. `fin_expenses.category_id` nullable
Original column is `INT(11) UNSIGNED NOT NULL`. Recurring templates created from the supplier offcanvas almost always have `category_id = NULL`. `RecurringExpenseScheduler::generate()` copies that value and **MySQL rejects the insert**.

```sql
ALTER TABLE fin_expenses
  MODIFY category_id INT(11) UNSIGNED NULL;
```

Keep the existing FK to `fin_expense_categories`. If the FK blocks MODIFY, drop/re-add it with `ON DELETE SET NULL ON UPDATE CASCADE` (or RESTRICT if SET NULL is incompatible with the current FK name `fin_expenses_category_id_foreign`). Prefer keeping the FK.

### B. Indexes that never applied (verified missing in live DB)

`fin_expenses` — add **if not exists** (check `SHOW INDEX` / information_schema first to be idempotent):

- UNIQUE `(recurring_expense_id, period_label)`  
  Name suggestion: `uk_fin_expenses_recurring_period`  
  MySQL allows multiple NULLs in unique keys; generator always sets `period_label` for recurring rows.
- KEY `due_date`
- KEY `status`

`fin_recurring_expenses`:

- KEY `next_due_date`
- KEY `is_active`  
  (`org_branch_id` already indexed via `fk_fin_recurring_org_branch` — do not duplicate.)

Optional same migration: `paid_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00` (today it is NULL-able).

`down()`: reverse MODIFY category_id only if no NULLs, drop the new indexes by name.

Then run: `php spark migrate`

---

## BLOCKING — 2. CSRF DataTables (§2)

Bug: `refreshCsrf(r)` uses `r.headers.get(...)` (Fetch `Response`). DataTables `xhr.dt` passes **jqXHR**, which has `getResponseHeader()`. First table POST works; later filter/reload can **403**.

Fix in **both**:
- `app/Views/Financial/PurchaseOrders/purchase_orders_index.php`
- `app/Views/Financial/Recurring/recurring_index.php`

Replace the helper with one that supports both:

```javascript
function refreshCsrf(r) {
    if (!r) return;
    var header = '<?= csrf_header() ?>';
    var t = null;
    if (r.headers && typeof r.headers.get === 'function') {
        t = r.headers.get(header);
    } else if (typeof r.getResponseHeader === 'function') {
        t = r.getResponseHeader(header);
    }
    if (t) {
        var el = document.getElementById('csrf_token');
        if (el) el.value = t;
    }
}
```

Keep Fetch mutations with CSRF **in HTTP header** + `X-Requested-With: XMLHttpRequest`. Keep DataTables injecting the token in `data` + `xhr.dt` calling this helper.

---

## IMPORTANT — 3. DataTable `$datatableConfig['column_order']` (§5)

`DataTableTrait` maps `order[0][column]` **directly** to `column_order[$column_index]`. A leading `null` (leftover from a `#` column that does not exist) shifts sort by one.

**`FinPurchaseOrderModel`** — list columns are: PO#, Proveedor, Sucursal, Estatus, Total, Moneda, Emisión, Entrega, Acciones (indexes 0–8).

```php
'column_order' => [
    'fin_purchase_orders.po_number',
    'fin_suppliers.commercial_name',
    'org_branches.name',
    'fin_purchase_orders.status',
    'fin_purchase_orders.total_amount',
    'fin_purchase_orders.currency',
    'fin_purchase_orders.issue_date',
    'fin_purchase_orders.delivery_date',
    null, // actions
],
```

**`FinExpenseModel`** — columns 0–8: Vencimiento, Proveedor, Servicio, Sucursal, Periodo, Total, Pagado, Estatus, Acciones.

```php
'column_order' => [
    'fin_expenses.due_date',
    'fin_suppliers.commercial_name',
    'fin_recurring_expenses.description',
    'org_branches.name',
    'fin_expenses.period_label',
    'fin_expenses.total_amount',
    'fin_expenses.paid_amount',
    'fin_expenses.status',
    null,
],
```

**`FinRecurringExpenseModel`** — columns 0–8: Servicio, Proveedor, Sucursal, Tipo, Monto, Frecuencia, Próximo, Activo, Acciones.

```php
'column_order' => [
    'fin_recurring_expenses.description',
    'fin_suppliers.commercial_name',
    'org_branches.name',
    'fin_recurring_expenses.service_type',
    'fin_recurring_expenses.amount',
    'fin_recurring_expenses.frequency',
    'fin_recurring_expenses.next_due_date',
    'fin_recurring_expenses.is_active',
    null,
],
```

Do not change the HTML column count unless you also change `column_order`.

---

## IMPORTANT — 4. UI comprobantes de vencimiento (Fase 6 incompleta)

Backend already exists:
- `GET  suppliers.recurring.documents.list`
- `POST suppliers.recurring.documents.upload` (`catalog.recurring-manage`)
- `GET  suppliers.recurring.documents.download`

`RecurringExpenseController::_listExpensesAjax` already renders `.btn-view-expense` but **`recurring_index.php` has no click handler**.

**Do:**
1. Offcanvas or modal (AppStack / Bootstrap 5, FontAwesome only) opened by `.btn-view-expense`.
2. Show expense header (proveedor, servicio, due_date `d/m/Y`, montos, status).
3. List documents via AJAX (`isAJAX` 404 if not, `outputData`).
4. Upload FormData: CSRF **header**, `X-Requested-With`, `notifyShow`.
5. Download via named route; `?action=view` inline.
6. Hide upload if `!auth()->user()->can('catalog.recurring-manage')`.
7. DataTables Guard already present; do not re-init tables.

Reuse patterns from PO offcanvas in `purchase_orders_index.php` (`loadPoDocuments` / upload). Files stay in `WRITEPATH/uploads/financial/expenses/{id}/`.

---

## IMPORTANT — 5. Alta de plantillas desde `/nat/suppliers/recurring`

Pestaña Plantillas is list-only. Users with `catalog.recurring-manage` (e.g. finance) cannot create CFE/internet without `catalog.manage-suppliers`.

**Do:**
- Button “Nueva plantilla” if `can('catalog.recurring-manage')`.
- Modal: supplier Select2 (`dropdownParent: $('#modalId')` §10), branch, service_type (use `FinRecurringExpenseModel::SERVICE_TYPE_GROUPS` already in `viewData['response']`), amount, currency, frequency, billing_day / billing_weekday, start_date, end_date, notify_days_before, is_active.
- POST: either reuse `suppliers.services.store` **or** add `RecurringExpenseController::store_template` + named route `suppliers.recurring.templates.store` with filter `permission:catalog.recurring-manage`. Prefer a dedicated route so finance does not depend on `catalog.manage-suppliers`.
- Delegate insert to `FinRecurringExpenseModel::createForSupplier` (§9, §19). Log §8.
- Reload templates DataTable + `notifyShow`.

**Also change** existing `suppliers.services.store` route filter from `catalog.manage-suppliers` to `catalog.recurring-manage` (creating a recurring service is calendar work, not supplier CRUD). Keep supplier offcanvas button visible with `can('catalog.recurring-manage')` (or both permissions). Check `suppliers_index.php` for the add-service button.

---

## MINOR — 6. Model hygiene

### `FinPurchaseOrderEmailLogModel`
Add `$validationRules` for: `fin_purchase_order_id`, `email_kind` `in_list[send_po,reply_with_attachments]`, `to_emails`, `subject`, `body_html`, `smtp_status` `in_list[sent,failed]`.

### `FinPurchaseOrderDocumentModel::storeDocument` and `FinExpenseDocumentModel::storeDocument`
1. Capture `$size = $file->getSize()`, `$clientName = $file->getClientName()`, `$mime` **before** `move()`.
2. Move file **inside** the DB transaction **or**: if insert fails after move, `unlink` the stored file (no orphans). Soft-delete still must **not** delete the physical file (audit rule).

---

## Scheduler (only if needed after migration)

If generate still fails, skip rows where unique `(recurring_expense_id, period_label)` exists (already does `first()`). Do not swallow other DB errors silently — `transStatus()` should remain.

---

## Verification when done

Reply with:
1. Files created/modified.
2. Whether `php spark migrate` ran and the new version `2026-08-18-120500`.
3. Confirm with SQL (or spark) that UNIQUE `uk_fin_expenses_recurring_period` exists and `category_id` is NULL-able.
4. Anything skipped.

Orchestrator will re-audit `DOCUMENTACION_TECNICA.md` after this task.
