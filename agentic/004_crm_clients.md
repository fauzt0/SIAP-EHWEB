# Task 004: CRM & Clients Module

## Objective
Implement the CRM/Clients module to manage buyers, multiple contact profiles (technical, administrative, etc.), and billing configurations.

---

## Recommended Model
- **Model:** `Composer 2.5` (For visual interface development and tabbed configuration layout).
- **Reason:** Best suited for generating highly functional, multi-section user interfaces (e.g. Clients, Contacts tab, Billing tab) and linking complex models.

---

## Technical Specifications

### 1. Database Migrations
Create three tables:
- **`crm_clients`**
  - `id` (INT AI PK)
  - `company_name` (VARCHAR 255)
  - `rfc_tax_id` (VARCHAR 20, NULL)
  - `website` (VARCHAR 255, NULL)
  - `active` (TINYINT, default 1)
  - `created_at` / `updated_at` / `deleted_at` (Soft Delete support)
- **`crm_client_contacts`** (One Client has many Contacts)
  - `id` (INT AI PK)
  - `client_id` (INT FK to `crm_clients.id`)
  - `contact_type` (Enum/Varchar: 'buyer', 'technical', 'billing', 'admin')
  - `name` (VARCHAR 100)
  - `email` (VARCHAR 100)
  - `phone` (VARCHAR 20, NULL)
- **`crm_billing_profiles`** (One Client can have multiple Billing Profiles)
  - `id` (INT AI PK)
  - `client_id` (INT FK to `crm_clients.id`)
  - `legal_name` (VARCHAR 255) (Razón Social)
  - `rfc_tax_id` (VARCHAR 20)
  - `tax_regime` (VARCHAR 10) (Régimen Fiscal)
  - `postal_code` (VARCHAR 10)
  - `address` (TEXT, NULL)
  - `cfdi_use` (VARCHAR 5) (Uso de CFDI)

### 2. CRM Clients CRUD
- **Controller:** `app/Controllers/CRM/ClientController.php`.
- **List Page:** Server-side DataTables using `DataTableTrait`.
- **Form:** A tabbed layout or single form containing:
  - Tab 1: General Info (Client basic details).
  - Tab 2: Contacts (Dynamic list: add/edit/delete contacts on the fly or as a repeater).
  - Tab 3: Billing Profiles (Multiple billing entities per client).
- **Permissions:** Guard routes with `crm.manage` or similar.

---

## Architectural Compliance
- **MVC & Transactions:** Saving a client with their nested contacts or billing profiles must use a single transaction block inside the `CrmClientModel` (§9 of `DOCUMENTACION_TECNICA.md`).
- **AJAX & CSRF:** Ensure forms post through Fetch and handle CSRF header rotation (§2).
- **Icons:** Use solid/regular FontAwesome icons (§12).
