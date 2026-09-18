# Task 006: Completar módulo Proveedores (OC, documentos, email, recurrentes)

## Objective
Complement the existing `nat/suppliers` module so purchase orders become a full expediente: fiscal PDF (branch or company), inbound invoice archive (PDF/XML/image — NOT emailed), outbound attachments (payment proofs, clarifications — remain on disk), SMTP email via native CI4 Email (send PO + reply with attachments), extra Shield permissions, and calendarized recurring supplier services (electricity, internet, trash, etc.) with pending vs paid amounts.

**THIS FILE IS THE EXECUTION PROMPT.** Implement ALL phases in order. Do not skip architectural rules.

---

## Recommended Model
- **Model:** `Composer 2.5` (UI: offcanvas, modals, DataTables, TinyMCE, FullCalendar) **or** `Claude Sonnet` for migrations/models/controllers.
- **Reason:** Mix of schema, MVC, and dense AppStack UI that must match existing suppliers/HR patterns.

---

## IMPORTANT INSTRUCTION FOR THE IMPLEMENTING AGENT

You MUST strictly follow `DOCUMENTACION_TECNICA.md`. Do not invent new structures.

**Mandatory companion files (read first):**
1. `DOCUMENTACION_TECNICA.md` — entire file (§1–§21).
2. `agentic/2026-08-18_tables_po_documents_email_recurring.md` — **source of truth for schema and migration filenames**. Create exactly those 5 migrations. Do not invent extra tables.
3. Existing code: `app/Controllers/Financial/*`, `app/Models/Financial/*`, `app/Views/Financial/**`, `app/Config/Routes.php` (group `suppliers`), `app/Config/AuthGroups.php`, `app/Config/Email.php`, `app/Libraries/PdfLibrary.php`, `app/Controllers/HR/WorkerDocumentController.php` (secure download pattern), `app/Views/HR/contracts/worker_contract_generate.php` (TinyMCE pattern).

**Do NOT:**
- Recreate `fin_suppliers`, `fin_purchase_orders`, `fin_purchase_order_items`, `fin_recurring_expenses`, `fin_expenses`.
- Parse CFDI XML (store as file only; optional `notes` for UUID typed by the user).
- Touch `fin_ledger_entries` or implement `fin_payment_folios` approval flow.
- Put financial files under `FCPATH` / `public_html`. Use `WRITEPATH . 'uploads/...'` (exception to §18 public uploads; same as HR documents).
- Run transactions in controllers (§9). No `\Config\Database::connect()` in controllers.
- Use Lucide icons (§12). FontAwesome `fas`/`far` only.
- Hardcode SMTP credentials. Structure `AppMailer` to use `Config\Email` / `Services::email()`. The operator will fill SMTP later.
- Delete files from disk after sending email. They must remain.

**Language:** Code, methods, variables in English. UI copy and comments in Spanish.

---

## Architectural Compliance (checklist — apply on every endpoint)

| § | Rule |
|---|---|
| §1 | AJAX-only endpoints: if `!$this->request->isAJAX()` throw `PageNotFoundException`. Fetch MUST send `X-Requested-With: XMLHttpRequest`. |
| §2 | Hidden `#csrf_token` in `main`. Fetch mutations: CSRF in **HTTP header** (`<?= csrf_header() ?>`) AND refresh from response. DataTables: inject token in `data`, listen `xhr.dt`. |
| §3 | Named routes `['as' => '...']`. GET list/show, POST store/update/delete. |
| §4 | Sidebar: `url_is('nat/suppliers*')` already exists; add Recurring item with `url_is('nat/suppliers/recurring*')`. |
| §5 §16 | DataTableTrait; `$datatableConfig` or `$column_order`/`$column_search`; override `_get_datatables_query` for JOINs. |
| §6 | `if ($.fn.dataTable.isDataTable('#id')) return;` |
| §7 | Route filters `permission:...` AND `auth()->user()->can()` in views. |
| §8 | Every mutation: `UserActivityLogsModel->logActivity(...)`. |
| §9 | Multi-table writes inside the Model (`$this->db->transStart()`). |
| §10 | Select2 in modals: `dropdownParent: $('#modalId')`. |
| §11 | DB dates ISO; UI `d/m/Y`; forms `<input type="date">`. |
| §12 | FontAwesome only. |
| §13 | `App\Libraries\Breadcrumb` into `$this->viewData['breadcrumb']`. |
| §14 | `$this->extend($layout)` or `Layouts/user_loggedin_layout`; sections `title`, `main`, `scripts` / `pageFooterScripts` (match existing suppliers views). |
| §15 | HTML: `$this->viewData` + `setViewSuccess` + `setPageTittleAhead` + `renderLayout`. AJAX: `$this->outputData` + `setOutputSuccess` / `setOutputError` + `csrf` hash. |
| §17 | `notifyShow(message, type)` — never native `alert()` except as fallback. |
| §19 | Soft deletes, `$allowedFields`, `$validationRules` in the Model. |
| §20 | Recurring `service_type` stays in `FinRecurringExpenseModel::SERVICE_TYPES`. |
| §21 | Add `_detectOverdueRecurringExpenses()` in `AlertController` when Phase 6 is done. |

---

## Permissions (AuthGroups.php)

**Add these permissions:**
```
'catalog.po-documents'     => 'Can upload and delete purchase order documents',
'catalog.po-send-email'    => 'Can send purchase order emails with attachments',
'catalog.recurring-manage' => 'Can calendarize and mark recurring supplier services as paid',
```

**Keep:**
- `catalog.access` — view module, list, PDF, download (no delete/send).
- `catalog.manage-suppliers` — supplier CRUD, create/edit PO, status transitions.

**Matrix updates:**
- `superadmin` / `admin`: already `catalog.*` — no change needed.
- `purchasing`: ADD `catalog.access`, `catalog.manage-suppliers`, `catalog.po-documents`, `catalog.po-send-email`, `catalog.recurring-manage`.
- `finance`: ADD `catalog.access`, `catalog.po-documents`, `catalog.po-send-email`, `catalog.recurring-manage`.

Route filters:
- Document upload/delete → `permission:catalog.po-documents`
- Email send → `permission:catalog.po-send-email`
- Recurring generate / mark paid / edit template dates → `permission:catalog.recurring-manage`
- Recurring list/calendar GET → parent `catalog.access` is enough.

Hide buttons in views with `can()`.

---

## Two document channels (do not mix)

1. **`inbound_archive`** — supplier invoices (pdf, xml, jpg/png/webp). Historical record of paid orders. **Never** shown in the email attachment picker.
2. **`outbound`** — payment proofs, clarifications, other. Stay in the system. **Selectable** when composing email.

PO PDF is generated on the fly (`PdfLibrary`) and may be attached to email via flag `attached_po_pdf`. Do not require storing the PDF as a document row.

---

## Migrations (create exactly these 5)

See `agentic/2026-08-18_tables_po_documents_email_recurring.md` for every column, ENUM, FK and index.

| File | Action |
|---|---|
| `app/Database/Migrations/Accounting/2026-08-18-120000_CreateFinPurchaseOrderDocuments.php` | CREATE `fin_purchase_order_documents` |
| `app/Database/Migrations/Accounting/2026-08-18-120100_CreateFinPurchaseOrderEmailLogs.php` | CREATE `fin_purchase_order_email_logs` + `fin_po_email_log_documents` |
| `app/Database/Migrations/Accounting/2026-08-18-120200_AlterFinRecurringExpensesForCalendar.php` | ALTER `fin_recurring_expenses` |
| `app/Database/Migrations/Accounting/2026-08-18-120300_AlterFinExpensesForRecurringOccurrences.php` | ALTER `fin_expenses` |
| `app/Database/Migrations/Accounting/2026-08-18-120400_CreateFinExpenseDocuments.php` | CREATE `fin_expense_documents` |

Namespace: `App\Database\Migrations\Accounting`.
Use `$this->db->disableForeignKeyChecks()` around create/drop like existing Accounting migrations.
Run `php spark migrate` after creating them.

---

## Phase 0 — Permissions + standalone PO list usable

**Gap today:** `PurchaseOrderController::index()` already puts `suppliers`, `statuses`, `branches`, `default_branch_id` in `$this->viewData['response']`, but `purchase_orders_index.php` is list-only. `.btn-edit-po` is rendered in `list_ajax` but **not bound**. Some fetch URLs use hardcoded `site_url('nat/suppliers/...')` instead of `route_to()`. CSRF is in FormData but docs require header too.

**Do:**
1. AuthGroups permissions + matrix (above).
2. Complete `app/Views/Financial/PurchaseOrders/purchase_orders_index.php`:
   - Button Nueva OC if `catalog.manage-suppliers`.
   - Reuse / extract PO form from `#modalGeneratePO` in `suppliers_index.php` (supplier Select2 + branch + items repeater). Prefer a **partial** `app/Views/Financial/PurchaseOrders/partials/po_form_modal.php` included from both views to avoid duplication.
   - Wire `.btn-edit-po` → `get_ajax` → same modal.
   - Offcanvas 360° for PO detail: header, branch, items, notes, later documents/emails.
   - Filters: status, supplier, branch (model `_get_datatables_query` already has status/supplier; add `filter_branch_id`).
   - Column Sucursal (`branch_name` already in JOIN).
   - All fetch: `route_to()`, CSRF **header**, `X-Requested-With`, `notifyShow`.
3. DataTable column_order array must stay in sync if you insert the branch column.

---

## Phase 1 — Fiscal PDF (branch or company)

**Gap:** `getOrderWithItems()` does not select supplier `tax_id`, `legal_name`, `contact_email`. PDF template reads `$po->tax_id` (empty). No logo. No company fallback.

**Do:**
1. Enrich `FinPurchaseOrderModel::getOrderWithItems()`:
   - Supplier: `legal_name`, `tax_id`, `contact_email`, `contact_phone`.
   - Branch: keep fiscal fields + `logo_path`.
2. In `PurchaseOrderController::download()`, also load `OrgCompanyModel::getProfile()` and pass `company` to the view.
3. Header rule: branch fiscal profile first (`OrgBranchModel::getFiscalProfile`); missing fields fall back to company profile; logo branch → company.
4. Keep `?action=view` inline vs attachment. Filename `OC-{po_number}.pdf`.
5. This PDF is what “Enviar OC” attaches.

---

## Phase 2A — Inbound archive (invoices)

**Model:** `app/Models/Financial/FinPurchaseOrderDocumentModel.php`
- Soft deletes, validation in model.
- Methods: `getByOrder(int $poId, ?string $channel = null)`, `storeDocument(int $poId, $file, array $meta): int|false` (move file + insert in transaction), `softDeleteDocument(int $id): bool`.
- MIME whitelist inbound: pdf, xml (`application/xml`,`text/xml`), jpg, jpeg, png, webp. Max ~10MB.
- Path: `WRITEPATH/uploads/financial/po/{poId}/` + `getRandomName()`. Store relative path `financial/po/{poId}/{name}`.

**Controller methods** (can live on `PurchaseOrderController` or a slim `PurchaseOrderDocumentController` extending `BaseFinancialController`):
- `GET  purchase-orders/(:num)/documents` → list AJAX (`catalog.access`)
- `POST purchase-orders/(:num)/documents` → upload (`catalog.po-documents`)
- `GET  purchase-orders/documents/download/(:num)` → secure download like `WorkerDocumentController::downloadDocument` (`catalog.access`)
- `POST purchase-orders/documents/delete/(:num)` → soft delete (`catalog.po-documents`)

**UI:** Offcanvas tab “Facturas del proveedor”. Multiple file input. No email picker.

---

## Phase 2B — Outbound attachments

Same table/endpoints. `doc_channel=outbound`, types `payment_proof|clarification|other`.
Whitelist: pdf, jpg, jpeg, png, webp (no xml required).
UI tab “Comprobantes y aclaraciones”. Files remain after later emails.

---

## Phase 3 — Email (CI4 native)

**Service:** `app/Services/AppMailer.php`
```php
public function sendHtml(array $to, array $cc, string $subject, string $bodyHtml, array $absoluteAttachmentPaths = []): bool
```
Use `\Config\Services::email()`, `setMailType('html')`, `setAttachment()` per path, `from` from config. Return false on failure; expose `getError()`.

**Do not** change SMTP host/user/pass. Optionally set in `Email.php` comments that `$protocol` should become `smtp` when credentials exist. Default `$mailType` may stay `text`; override per send.

**Models:** `FinPurchaseOrderEmailLogModel` with `logSend(...)` writing log + pivot rows in one transaction (§9).

**Controller:**
- `GET  purchase-orders/(:num)/email/compose` AJAX — prefill to (supplier email + contacts), subject, TinyMCE body templates:
  - `send_po`: Enviar orden {po_number}
  - `reply_with_attachments`: Comprobantes / respuesta OC {po_number}
- `POST purchase-orders/(:num)/email/send` (`catalog.po-send-email`) — fields: kind, to, cc, subject, body_html, attached_po_pdf (0/1), document_ids[] (outbound only; reject inbound ids).
  - If `attached_po_pdf`: generate PDF to temp under WRITEPATH, attach, then unlink **temp only** (not document files).
  - Persist log even on SMTP failure with `smtp_status=failed`.

**UI:** Modal + TinyMCE 6.8.3 CDN (copy init style from HR contracts). Checkboxes of outbound docs. Permission-gated button.

---

## Phase 4 — Recurring templates (calendar fields)

Extend `FinRecurringExpenseModel` `$allowedFields` + validation for new ALTER columns.
Update `createForSupplier` and add `updateForSupplier`.
UI: existing `#modalAddRecurringService` + new fields (branch, start_date, end_date, weekday if weekly).
New standalone index later in Phase 6; in this phase at least save the new fields from supplier offcanvas.

Default `start_date` = today, `org_branch_id` = main branch if empty.

---

## Phase 5 — Generate occurrences into `fin_expenses`

**New model:** `app/Models/Financial/FinExpenseModel.php` (table already exists).
Soft deletes, DataTableTrait, validation.

**Service:** `app/Services/RecurringExpenseScheduler.php`
- Iterate active templates.
- Compute periods from `start_date` to `min(end_date, horizon)` (horizon = end of next month by default).
- weekly → `period_label` like `2026-W33`, due = that week’s `billing_weekday`.
- monthly → `YYYY-MM`, due = `billing_day` clamped to month length.
- quarterly / semiannual / yearly analogously.
- Insert `fin_expenses` if unique (`recurring_expense_id`,`period_label`) missing: status `pending`, `total_amount` = template amount, `supplier_id`, `org_branch_id`, `category_id` copy, `due_date`, `currency`.
- Update template `last_generated_at`, `next_due_date`.
- All inserts in a model/service transaction (§9).

**Spark command:** `app/Commands/Financial/GenerateRecurringExpenses.php`
- `php spark financial:generate-recurring`
- Callable from UI button POST (`catalog.recurring-manage`).

---

## Phase 6 — Calendar, reports, mark paid

**Routes group** inside `suppliers`:
```
GET  recurring                     RecurringExpenseController::index          as suppliers.recurring.index
POST recurring/list_ajax           RecurringExpenseController::list_ajax      as suppliers.recurring.list_ajax
GET  recurring/calendar_ajax       RecurringExpenseController::calendar_ajax  as suppliers.recurring.calendar_ajax
POST recurring/generate            RecurringExpenseController::generate       as suppliers.recurring.generate  filter catalog.recurring-manage
POST recurring/expenses/(:num)/pay RecurringExpenseController::mark_paid      as suppliers.recurring.pay       filter catalog.recurring-manage
+ document upload/download for fin_expense_documents (catalog.recurring-manage / catalog.access)
```

**View:** `app/Views/Financial/Recurring/recurring_index.php`
- Tabs: Calendario | Vencimientos | Plantillas
- FullCalendar 6 CDN, events from `calendar_ajax` (start/end = due_date). Colors: pending=warning, overdue=danger, paid=success, partial=info.
- DataTable of `fin_expenses` JOIN template + supplier + branch.
- Cards: pending this month, overdue, paid this month (from model aggregates).
- Mark paid / partial: POST `paid_amount`; if `paid_amount >= total_amount` → `paid`, else `partial`.
- Sidebar link “Servicios recurrentes” under Proveedores.

**Alerts §21:** `_detectOverdueRecurringExpenses()` counting `fin_expenses` where status in pending/partial, due_date < today, deleted_at null. `target_url` = `route_to('suppliers.recurring.index')`.

**Expense documents:** upload under WRITEPATH `financial/expenses/{id}/`. Optional in offcanvas of a due item.

---

## Named routes to add (inside existing `suppliers` group)

Keep existing PO routes. Add (names are mandatory):

```
suppliers.po.documents.list
suppliers.po.documents.upload
suppliers.po.documents.download
suppliers.po.documents.delete
suppliers.po.email.compose
suppliers.po.email.send
suppliers.recurring.index
suppliers.recurring.list_ajax
suppliers.recurring.calendar_ajax
suppliers.recurring.generate
suppliers.recurring.pay
suppliers.recurring.documents.list
suppliers.recurring.documents.upload
suppliers.recurring.documents.download
```

Exact URL paths should stay REST-like under `nat/suppliers/...` (§3).

---

## Implementation order (do not jump)

0 → 1 → 2A → 2B → 3 → 4 → 5 → 6

Commit nothing unless the user of that chat asks. Do not write README/markdown except if a migration comment needs it.

When finished, reply with:
1. List of files created/modified.
2. Migrations run (yes/no) and table names.
3. Anything you could not complete.

---

## Orchestrator verification (do not do this yourself)

A separate orchestrator agent will later audit DOCUMENTACION_TECNICA.md compliance. Your job is to implement so that audit passes.
