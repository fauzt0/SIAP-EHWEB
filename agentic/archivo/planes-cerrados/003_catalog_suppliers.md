# Task 001: Suppliers Management Module (Financial & Catalog Integration)

## Objective
Implement a Suppliers management module (`fin_suppliers`) that acts as a bridge between the Financial Accounting system and the Catalog Products. It includes supplier profiles (contact, fiscal, and bank information) and associates them with catalog products (hardware/licenses). 

**IMPORTANT INSTRUCTION FOR LLM (DEEPSEEK/CURSOR):** You MUST strictly follow the architecture and standards defined in `DOCUMENTACION_TECNICA.md`. Do not invent new structures or ignore these directives.

## Technical Specifications

### 1. Database & Models
The table `fin_suppliers` already exists (along with other `fin_*` tables). **DO NOT touch them or create migrations for them.**
- **`fin_suppliers` Table Structure:** `id`, `business_name`, `commercial_name`, `supplier_type` (ENUM), `tax_id`, `contact_email`, `contact_phone`, `bank_details`, `active`, `created_at`, `updated_at`, `deleted_at`.

- **`catalog_product_suppliers` (Pivot Table to Create/Verify):**
  - `id` (INT 11 UNSIGNED AI PK)
  - `catalog_product_id` (INT 11 UNSIGNED FK to `catalog_products.id`)
  - `fin_supplier_id` (INT 11 UNSIGNED FK to `fin_suppliers.id`)
  - `purchase_cost` (DECIMAL 15,2, default 0.00)
  - `purchase_currency` (VARCHAR 3, default 'USD')
  - `supplier_sku` (VARCHAR 100, NULL)

### 2. Models Setup
- **`FinSupplierModel`:**
  - Define `$allowedFields`, `$protectFields = true;`.
  - Enable `$useSoftDeletes = true;` (Ensure `deleted_at` is respected).
  - **Validation:** Define strictly in the Model properties (`$validationRules`), NOT in the controller (§19).
  - **DataTableTrait:** Include `DataTableTrait` (§5, §16). Define `$column_order` and `$column_search`. Override `_get_datatables_query` if JOINS are needed.
- **`CatalogProductSupplierModel`:**
  - Manage the pivot table interactions.
  - **Transactions:** Any multi-table operations (e.g. `saveProductSuppliers`) MUST wrap operations in `$this->db->transStart()` and `$this->db->transComplete()` within the model (§9). NEVER run transactions in the controller.

### 3. Suppliers CRUD Controller (`SupplierController.php`)
- **Extend:** `BaseController` (or admin equivalent). Do NOT interact with `\Config\Database::connect()` from the controller.
- **Routes:** Use named routes (`['as' => 'route.name']`) and appropriate HTTP verbs (GET for list/show, POST/PUT for store/update, POST/DELETE for delete) in `Routes.php` (§3).
- **Security (§7):** Guard routes with Shield CI4 filters (e.g., `permission:catalog.manage-suppliers`). Check `auth()->user()->can('...')` in views to hide/show buttons.
- **Data Flow (§15):** 
  - For HTML responses, use `$this->viewData` (e.g. `$this->setViewSuccess()`, `$this->setPageTittleAhead()`) and return `$this->renderLayout('Layouts/user_loggedin_layout', 'ViewName')`.
  - For AJAX responses, use `$this->outputData` and methods like `$this->setOutputSuccess()`, `$this->setOutputError()`, returning JSON.
- **Methods:**
  - `index()`: Pass `$this->request->getPost()` to `$model->get_datatables()`.
  - `store()` / `update($id)`: Validate via model. **Log activities** (§8) using `UserActivityLogsModel->logActivity(...)`.
  - `show($id)`: Fetch data via model methods.

### 4. Views & UI (Partials, Templates, Layouts)
- **Layouts (§14):** Views must extend standard layouts using `<?php $this->extend('Layouts/user_loggedin_layout'); ?>` and use sections: `title`, `main`, `scripts`.
- **Breadcrumbs (§13):** Implement `App\Libraries\Breadcrumb` in the controller and pass it to `$this->viewData['breadcrumb']`.
- **List Page:** Server-side DataTables. 
  - *Guard Initialization (§6):* `if ($.fn.dataTable.isDataTable('#mi-tabla')) return;`
  - *CSRF (§2):* Include global hidden CSRF token input. Listen to `xhr.dt` to update the token.
- **Profile Page (Tabs):**
  - General: Fiscal, contact, bank info.
  - Catalog: Assigned catalog products.
  - Financial Ledger: Join with `fin_expenses`.
- **AJAX & Fetch (§1, §2):** Any Fetch API request MUST include `X-Requested-With: XMLHttpRequest` and the current CSRF token in headers, updating the token from the response. Controller must verify `$this->request->isAJAX()`.
- **Notifications (§17):** Use `notifyShow(message, type)` for all AJAX responses.
- **Select2 (§10):** If rendering inside a modal/offcanvas, ensure `dropdownParent` is configured.
- **Icons (§12):** Exclusively use FontAwesome Solid (`fas`) or Regular (`far`). No Lucide.

### 5. Product Integration
- **Product Form (`product_form.php`):** Add a UI section to associate suppliers with a specific product.
- Allow adding multiple suppliers with different `purchase_cost`, `purchase_currency`, and `supplier_sku`.
- **Save Logic:** Handle multi-table insertions/updates of pivot records inside a transaction within `CatalogProductModel.php` or `CatalogProductSupplierModel.php` (§9).

## Quality & Standards
- **Language:** Code, variables, and methods MUST be in English. Comments and UI text can be in Spanish.
- **Strict Separation of Concerns:** Controllers orchestrate, Models validate and mutate (including DB transactions), Views render.