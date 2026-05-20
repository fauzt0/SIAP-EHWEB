# Task 003: Suppliers Management Module

## Objective
Implement a Suppliers management module, including supplier profiles (contact, fiscal, and bank information) and associate them with catalog products along with their base costs.

---

## Recommended Model
- **Model:** `Composer 2.5` or `Sonnet 4.6`
- **Reason:** Requires generating multiple new MVC components (migrations, models, views, controllers), forms, and relationship binding between products and suppliers.

---

## Technical Specifications

### 1. Database Migrations
Create two new tables:
- **`catalog_suppliers`**
  - `id` (INT AI PK)
  - `name` (VARCHAR 150)
  - `rfc_tax_id` (VARCHAR 20, NULL)
  - `contact_email` (VARCHAR 100)
  - `contact_phone` (VARCHAR 20, NULL)
  - `bank_name` (VARCHAR 100, NULL)
  - `bank_account` (VARCHAR 50, NULL)
  - `bank_clabe` (VARCHAR 18, NULL)
  - `active` (TINYINT, default 1)
  - `created_at` / `updated_at` / `deleted_at` (Soft Delete support)
- **`catalog_product_suppliers`** (Pivot table)
  - `id` (INT AI PK)
  - `product_id` (INT FK to `catalog_products.id`)
  - `supplier_id` (INT FK to `catalog_suppliers.id`)
  - `base_cost` (DECIMAL 10,2, default 0.00)
  - `supplier_sku` (VARCHAR 100, NULL)

### 2. Suppliers CRUD
- **Controller:** `app/Controllers/Catalog/SupplierController.php` (must extend `BaseCatalogController` or `BaseController`).
- **List Page:** Server-side DataTables using `DataTableTrait` (§5 & §16 of `DOCUMENTACION_TECNICA.md`).
- **Form:** Alta/Edición with fields matching `catalog_suppliers`.
- **Permissions:** Guard routes with `admin.manage-suppliers` or `catalog.manage`.

### 3. Product Integration
- **Product Form (`product_form.php`):** Add a tab or section to associate suppliers with the product.
- Allow adding multiple suppliers with different base costs and supplier SKUs.
- **Save Logic:** Handle multi-table insertions/updates of pivot records inside a transaction within `CatalogProductModel.php` (§9 of `DOCUMENTACION_TECNICA.md`).

---

## Quality & Standards
- **Icons:** Use Solid/Regular FontAwesome icons (§12).
- **Notifications:** Use `notifyShow` for AJAX success/error feedback (§17).
- **Validation:** Define validations in the Model properties, not in the controller (§19).
