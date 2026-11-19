# Task 001 - Part 2: Suppliers Management Module (Controllers & Routing)

**IMPORTANT INSTRUCTION FOR LLM (DEEPSEEK/CURSOR):** You MUST strictly follow the architecture and standards defined in `DOCUMENTACION_TECNICA.md`. Do not invent new structures or ignore these directives.

## Technical Specifications (Part 2)

### 3. Suppliers CRUD & Operations Controller (`SupplierController.php`, `SupplierContactController.php`, `PurchaseOrderController.php`)
- **Extend:** `BaseController` (or admin equivalent). Do NOT interact with `\Config\Database::connect()` from any controller.
- **Routes:** Use named routes (`['as' => 'route.name']`) and RESTful HTTP verbs in `Routes.php` (§3).
- **Security (§7):** Guard routes with Shield CI4 filters (e.g., `permission:catalog.manage-suppliers`). Check `auth()->user()->can('...')` before executing sensitive logic.
- **Data Flow (§15):** 
  - For HTML responses (like `index()`), use `$this->viewData` (e.g. `$this->setViewSuccess()`) and return `$this->renderLayout()`.
  - For AJAX responses, use `$this->outputData` and return JSON via `$this->setOutputSuccess()`, `$this->setOutputError()`.
  - **Log activities** (§8) using `UserActivityLogsModel->logActivity(...)` for creations, updates, and deletions.

### 4. Financial & Automation Logic (Controller -> Model delegation)
- **Data Entry (Modals & Offcanvas):** 
  - Creation (Alta) and Edition must be done exclusively via Modals, so `store()` and `update()` endpoints must strictly return JSON. 
  - The `show($id)` endpoint (used to render the Offcanvas profile) must return the HTML content or JSON payload needed to populate the Offcanvas UI.
- **Purchase Orders Automation & Exports:**
  - Create endpoints to generate POs (via Modal submissions). The controller parses the request and delegates the complex insertion to `FinPurchaseOrderModel->generatePO($data, $items)`.
  - **PDF Export Endpoint:** Implement `download(int $id)` in `PurchaseOrderController` matching the `WorkerContractController` pattern. Use `\App\Libraries\PdfLibrary` to render a clean HTML layout of the PO to PDF. If the URL contains `action=view` parameter, set `Content-Disposition: inline; filename="..."` header for in-browser preview. Otherwise, set `Content-Disposition: attachment; filename="..."` header for download.
- **Products & Services Association:**
  - Implement endpoints to link Resellable Products to `catalog_product_suppliers`.
  - Implement endpoints to link Internal/Utility Services (electricity, internet) directly to `fin_recurring_expenses`.
  - The Controller delegates these associations to the respective Models.

## Quality & Standards
- **Language:** Code, variables, and methods MUST be in English. Comments and UI text can be in Spanish.
- **Strict Separation of Concerns:** Controllers orchestrate, Models validate and mutate (including DB transactions), Views render.
