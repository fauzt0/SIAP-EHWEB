# Task 001 - Part 1: Suppliers Management Module (Database & Models)

## Objective
Implement an integral Suppliers management module (`fin_suppliers`) for the Hosting ERP. This module manages diverse suppliers (hosting, VPS, dedicated servers, electricity, telephony, etc.), their contacts, and integrates securely with catalog products and financial recurring expenses.

**IMPORTANT INSTRUCTION FOR LLM (DEEPSEEK/CURSOR):** You MUST strictly follow the architecture and standards defined in `DOCUMENTACION_TECNICA.md`. Do not invent new structures or ignore these directives.

## Technical Specifications (Part 1)

### 1. Database & Schema Verification
The table `fin_suppliers` already exists. **Verify its structure and create migrations only for the new tables listed below.**

- **`fin_suppliers` (Existing):** `id`, `business_name`, `commercial_name`, `supplier_type` (ENUM: 'infrastructure', 'hardware', 'services', 'administrative'), `tax_id`, `contact_email`, `contact_phone`, `bank_details`, `active`, `created_at`, `updated_at`, `deleted_at`. *(This ENUM inherently covers hosting/VPS as infrastructure/hardware, and electricity/phone as services).*
- **`fin_expenses` & `fin_recurring_expenses` (Existing):** Used for internal services (electricity, telephony, trash collection) provided by the supplier.

**New Tables to Create via Migration:**
- **`fin_supplier_contacts` (Additional Contacts):**
  - `id` (PK), `fin_supplier_id` (FK), `contact_name` (VARCHAR), `job_title` (VARCHAR), `email` (VARCHAR), `phone` (VARCHAR), `is_primary` (TINYINT), timestamps & soft deletes.
- **`fin_purchase_orders` (POs):**
  - `id` (PK), `fin_supplier_id` (FK), `po_number` (VARCHAR, unique), `status` (ENUM: draft, sent, approved, completed, cancelled), `total_amount` (DECIMAL), `currency`, `issue_date` (DATE), `delivery_date` (DATE), timestamps & soft deletes.
- **`fin_purchase_order_items`:**
  - `id` (PK), `fin_purchase_order_id` (FK), `catalog_product_id` (FK, nullable), `description` (VARCHAR), `quantity` (INT), `unit_price` (DECIMAL), `total_price` (DECIMAL).
- **`catalog_product_suppliers` (Pivot Table):**
  - `id` (PK), `catalog_product_id` (FK), `fin_supplier_id` (FK), `purchase_cost` (DECIMAL), `purchase_currency` (VARCHAR), `supplier_sku` (VARCHAR). *(Used to associate resellable products/hardware from the catalog to the supplier).*

### 2. Models Setup
- **`FinSupplierModel` & `FinSupplierContactModel`:**
  - Define `$allowedFields`, `$protectFields = true;`. Enable `$useSoftDeletes = true;`.
  - **Validation:** Strictly in `$validationRules` inside the Model (§19).
  - **DataTableTrait:** Implement `DataTableTrait` (§5) on `FinSupplierModel`.
- **Purchase Order Models (`FinPurchaseOrderModel`, `FinPurchaseOrderItemModel`):**
  - Manage PO lifecycle. Ensure state transitions are validated.
- **Transactions (CRITICAL):**
  - Multi-table operations MUST run inside `$this->db->transStart()` and `$this->db->transComplete()` entirely within the Model (§9). NEVER run transactions in the Controller.

## Quality & Standards
- **Language:** Code, variables, and methods MUST be in English. Comments and UI text can be in Spanish.
- **Strict Separation of Concerns:** Controllers orchestrate HTTP, Models validate and execute DB transactions, Views render UI.
