# Task 001 - Part 3: Suppliers Management Module (Views & UI)

**IMPORTANT INSTRUCTION FOR LLM (DEEPSEEK/CURSOR):** You MUST strictly follow the architecture and standards defined in `DOCUMENTACION_TECNICA.md`. Do not invent new structures or ignore these directives.

## Technical Specifications (Part 3)

### 5. Views & UI (Partials, Templates, Layouts)
- **Layouts (§14):** Views must extend standard layouts using `<?php $this->extend('Layouts/user_loggedin_layout'); ?>` and use sections: `title`, `main`, `scripts`.
- **Breadcrumbs (§13):** Implement `App\Libraries\Breadcrumb`.
- **List Page (Supplier Registry):** 
  - Server-side DataTables must be used. Guard Initialization (§6) with `if ($.fn.dataTable.isDataTable('#mi-tabla')) return;`
  - CSRF (§2): Include global hidden CSRF token input. Listen to `xhr.dt` to update the token.

### 6. Dynamic Data Entry (Modals) & The Offcanvas Profile
**CRITICAL ARCHITECTURAL DIRECTIVE:**
1. **Creation and Edition ONLY via Modals:** The action to Add (Alta) a new supplier or Edit an existing supplier's base info MUST be performed using **Bootstrap Modals**.
2. **The 360° Profile via Offcanvas:** Clicking on a supplier row in the DataTable opens a **Bootstrap Offcanvas** (matching HR/Users logic). This Offcanvas serves strictly as a dashboard to display all details: contacts, activity logs/follow-ups, accesses, and associations.
3. **Managing Associations from the Offcanvas:** 
   - Inside the Offcanvas, any action to add/manage relationships (e.g., adding an additional contact, associating a catalog product, setting up a recurring utility service) must be done by clicking a button that triggers a **Modal** layered over the Offcanvas.
4. **Select2 Configuration (§10):** Since forms are in Modals, you MUST configure `dropdownParent: $('#modalId')` for all Select2 inputs to prevent z-index issues.

### 7. Offcanvas Content Structure (Tabs or Accordions)
The Offcanvas must neatly organize:
- **General Info & Logs:** Basic supplier details and an activity timeline (follow-ups/accesses).
- **Contacts:** List of `fin_supplier_contacts` (Button to open "Add Contact Modal").
- **Products & Services Association:** 
  - *Resellable Products:* List of mapped `catalog_product_suppliers` (Button to open "Associate Catalog Product Modal").
  - *Internal Services (Light, Phone, Trash):* List of mapped `fin_recurring_expenses` for this supplier (Button to open "Add Recurring Service Modal").
- **Purchase Orders:** History of POs (Button to open "Generate PO Modal"). Each PO row in the list must include buttons to:
  - **Preview PDF:** Opens the preview URL (`purchase-orders/download/ID?action=view`) in a new tab.
  - **Download PDF:** Directly triggers the download URL (`purchase-orders/download/ID`).

### 8. Frontend Mechanics
- Form submissions must use Fetch API with `X-Requested-With: XMLHttpRequest` (§1) and handle CSRF headers (§2).
- Use `notifyShow(message, type)` for all AJAX feedback (§17).
- Exclusively use FontAwesome Solid (`fas`) or Regular (`far`) icons (§12). No Lucide.

---

## ⚠️ Pending Tasks — Implement in This Session
> The main view `app/Views/Financial/Suppliers/suppliers_index.php` is complete.
> The DB migration ran (Batch 18). All 4 models are verified complete.
> The tasks below are the ONLY remaining work. Implement them in this order.

### 9. PDF Export for Purchase Orders [HIGH — causes 404 today]

**9.1 Add route in `app/Config/Routes.php`** inside the existing `suppliers` group (after `suppliers.po.restore`):
```php
$routes->get('purchase-orders/download/(:num)', 'Financial\PurchaseOrderController::download/$1', ['as' => 'suppliers.po.download']);
```
Note: This is a **GET** route, no `permission` filter needed beyond the parent `catalog.access` group filter.

**9.2 Add method `download(int $id)` to `app/Controllers/Financial/PurchaseOrderController.php`**:
- Fetch the PO with its items using `$this->purchaseOrderModel->getOrderWithItems($id)` (method already exists).
- If not found, redirect to `route_to('suppliers.po.index')` with an error flash.
- Render the HTML using `view('Financial/PurchaseOrders/purchase_order_pdf', ['po' => $po])`.
- Pass the rendered HTML to `new \App\Libraries\PdfLibrary(['format' => 'Letter'])`.
- Read `?action=view` param: if `view`, set `Content-Disposition: inline`, else `attachment`.
- Filename format: `OC-{$po->po_number}.pdf`.
- Pattern to follow: `app/Controllers/HR/WorkerContractController.php::downloadContract()`.

**9.3 Create the PDF template view `app/Views/Financial/PurchaseOrders/purchase_order_pdf.php`**:
- Simple, clean HTML (no Bootstrap JS, minimal CSS inline for mPDF compatibility).
- Must include: Company header, PO number, issue/delivery dates, status badge, supplier name & tax_id, items table (description, qty, unit price, total), grand total, currency, notes.
- Use the `$po` object (has `->supplier_name`, `->items[]` array from `getOrderWithItems()`).

---

### 10. Activity Timeline Endpoint [MEDIUM]

**10.1 Add route in `app/Config/Routes.php`** inside the `suppliers` group:
```php
$routes->get('(:num)/activity', 'Financial\SupplierController::activity/$1', ['as' => 'suppliers.activity']);
```

**10.2 Add method `activity(int $id)` to `app/Controllers/Financial/SupplierController.php`**:
```php
public function activity(int $id) {
    if (!$this->request->isAJAX()) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    $logs = (new \App\Models\Users\UserActivityLogsModel())
        ->like('description', 'proveedor ID: ' . $id)
        ->orderBy('created_at', 'DESC')->findAll(20);
    $this->setOutputSuccess('OK');
    $this->outputData['response'] = ['logs' => $logs];
    return $this->response->setJSON($this->outputData);
}
```

**10.3 Wire the JS in `suppliers_index.php`**: In the `loadSupplierProfile(id)` function (or after the `show` fetch resolves), make a second fetch to `route_to('suppliers.activity', id)` and render results into `#oc-activity-timeline` as an HTML timeline list (timestamp + action description).

---

### 11. Recurring Expenses Association [MEDIUM]

> The table `fin_recurring_expenses` **already exists** (migrated April 2026, Batch 6).
> Its column `supplier_id` is already a nullable FK to `fin_suppliers.id`.
> The UI modal `#modalAddRecurringService` already exists in `suppliers_index.php`.

**11.1 Create `app/Models/Financial/FinRecurringExpenseModel.php`**:
- `$table = 'fin_recurring_expenses'`, `$useSoftDeletes = true`.
- `$allowedFields`: `['supplier_id', 'category_id', 'account_id', 'description', 'amount', 'currency', 'billing_day', 'frequency', 'is_active']`.
- Add method `getBySupplier(int $supplierId): array` — returns active records for that supplier.

**11.2 Add 2 routes in `app/Config/Routes.php`** inside the `suppliers` group:
```php
$routes->get('(:num)/services',  'Financial\SupplierController::list_services/$1',  ['as' => 'suppliers.services.list']);
$routes->post('(:num)/services/store', 'Financial\SupplierController::store_service/$1', ['as' => 'suppliers.services.store', 'filter' => 'permission:catalog.manage-suppliers']);
```

**11.3 Add 2 methods to `SupplierController.php`**:
- `list_services(int $id)`: AJAX GET — returns `FinRecurringExpenseModel->getBySupplier($id)`.
- `store_service(int $id)`: AJAX POST — validates supplier exists, extracts `service_name→description`, `service_type`, `estimated_amount→amount`, `currency`, `billing_day`, `frequency`, sets `supplier_id=$id`, delegates insert to model. Logs activity (§8).

**11.4 Wire JS in `suppliers_index.php`**:
- In the offcanvas load, fetch `route_to('suppliers.services.list', id)` and render results into `#oc-services-list`.
- Wire `#btn-submit-add-service` in `#modalAddRecurringService` to POST to `route_to('suppliers.services.store', currentSupplierId)`.
- Remove the "próximamente" placeholder paragraph from `#oc-services-list`.

---

### 12. Standalone Purchase Orders View [LOW]

**12.1 Create `app/Views/Financial/PurchaseOrders/purchase_orders_index.php`**:
- Extend `Layouts/user_loggedin_layout`. Breadcrumb: Inicio → Proveedores → Órdenes de Compra.
- DataTable SSR using route `suppliers.po.list_ajax` (already exists). Columns: PO#, Proveedor, Estatus, Total, Moneda, Emisión, Entrega, Acciones.
- Actions per row: View PDF (`fas fa-file-pdf`), Download PDF, Change Status, Soft-delete.
- The `PurchaseOrderController::index()` method already renders this view path.

