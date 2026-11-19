# Task 010: CRUD de Categorías de Proveedores (Refactor de supplier_type)

## Objetivo
Actualmente el campo `supplier_type` en `fin_suppliers` es un enum hardcodeado con 4 valores fijos
(`infrastructure, hardware, services, administrative`). El requerimiento en `pendientes.md` dice que las
categorías deben ser **administrables desde un apartado propio, con alertas en caso de eliminar y
soft-delete para preservar históricos**.

Este task reemplaza el enum por una relación FK a una tabla `fin_supplier_categories`.

---

## Base de datos

### Migración 1: Crear tabla `fin_supplier_categories`
Archivo: `app/Database/Migrations/Financial/2026-09-18-000001_CreateFinSupplierCategories.php`

```php
$this->forge->addField([
    'id'          => ['type' => 'INT', 'auto_increment' => true],
    'name'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
    'description' => ['type' => 'TEXT', 'null' => true],
    'active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
    'created_at'  => ['type' => 'DATETIME', 'null' => true],
    'updated_at'  => ['type' => 'DATETIME', 'null' => true],
    'deleted_at'  => ['type' => 'DATETIME', 'null' => true],  // Soft-delete
]);
$this->forge->addKey('id', true);
$this->forge->createTable('fin_supplier_categories', true);

// Seed inicial: migrar los 4 valores fijos del enum actual
$this->db->table('fin_supplier_categories')->insertBatch([
    ['name' => 'Infraestructura', 'description' => 'Proveedores de infraestructura'],
    ['name' => 'Hardware',        'description' => 'Proveedores de hardware'],
    ['name' => 'Servicios',       'description' => 'Proveedores de servicios'],
    ['name' => 'Administrativo',  'description' => 'Proveedores administrativos'],
]);
```

### Migración 2: Alterar `fin_suppliers` — reemplazar `supplier_type` por `category_id`
Archivo: `app/Database/Migrations/Financial/2026-09-18-000002_AlterFinSuppliersAddCategoryId.php`

```php
// 1. Añadir columna category_id nullable
$this->forge->addColumn('fin_suppliers', [
    'category_id' => ['type' => 'INT', 'null' => true, 'after' => 'supplier_type'],
]);
// 2. Migrar datos: mapear supplier_type string → category_id
$this->db->query("
    UPDATE fin_suppliers fs
    JOIN fin_supplier_categories fsc ON (
        (fs.supplier_type = 'infrastructure' AND fsc.name = 'Infraestructura') OR
        (fs.supplier_type = 'hardware'       AND fsc.name = 'Hardware')        OR
        (fs.supplier_type = 'services'       AND fsc.name = 'Servicios')       OR
        (fs.supplier_type = 'administrative' AND fsc.name = 'Administrativo')
    )
    SET fs.category_id = fsc.id
");
// 3. Añadir FK
$this->forge->addForeignKey('category_id', 'fin_supplier_categories', 'id', 'SET NULL', 'CASCADE', 'fin_suppliers');
// 4. Eliminar columna antigua
$this->forge->dropColumn('fin_suppliers', 'supplier_type');
```

⚠️ **down()** debe revertir en orden inverso: restaurar `supplier_type`, mapear de regreso, eliminar FK y columna `category_id`.

---

## Modelo — `app/Models/Financial/FinSupplierCategoryModel.php`
- `$table = 'fin_supplier_categories'`
- `$useSoftDeletes = true`
- `$allowedFields = ['name', 'description', 'active']`
- Validación: `name` required|max_length[100]|is_unique[fin_supplier_categories.name,id,{id}]
- Método `getForSelect(): array` → para Select2 en alta/edición de proveedor
- Método `hasSuppliers(int $id): bool` → para alerta antes de eliminar

### Actualizar `app/Models/Financial/FinSupplierModel.php`
- Reemplazar `'supplier_type'` por `'category_id'` en `$allowedFields`.
- Reemplazar regla `in_list[...]` por `permit_empty|is_natural_no_zero`.
- En `getListData()` hacer JOIN: `->join('fin_supplier_categories fsc', 'fsc.id = fs.category_id', 'left')` y añadir `fsc.name as category_name` al SELECT.

---

## Controlador — `app/Controllers/Financial/SupplierCategoryController.php`
Extiende `BaseFinancialController`. Métodos:
- `index(): string` — lista paginada con DataTable
- `list_ajax(): ResponseInterface` — server-side DT
- `store(): ResponseInterface` — crear categoría (logActivity `create_supplier_category`)
- `update(int $id): ResponseInterface` — editar (logActivity `update_supplier_category`)
- `delete(int $id): ResponseInterface` — soft-delete **con alerta si hay proveedores vinculados**:
  ```php
  if ($this->categoryModel->hasSuppliers($id)) {
      return $this->setOutputError('Esta categoría tiene proveedores vinculados y no puede eliminarse.')->jsonWithCsrf($this->outputData);
  }
  ```
  logActivity `delete_supplier_category`.
- `restore(int $id): ResponseInterface` — restaurar soft-deleted

Permisos:
- Lectura: `catalog.access`
- Escritura: `catalog.manage-suppliers`

### Actualizar `SupplierController.php`
- En `store()` y `update()`: cambiar validación de `supplier_type` por `category_id`.
- En `getCsrfToken()` o `create()`: pasar `$categories = $this->categoryModel->getForSelect()` a la vista.

---

## Rutas — `app/Config/Routes.php`
Dentro del grupo `nat/suppliers`:
```php
$routes->group('categories', ['filter' => 'permission:catalog.access'], function ($routes) {
    $routes->get('/', 'Financial\SupplierCategoryController::index', ['as' => 'suppliers.categories.index']);
    $routes->post('list_ajax', 'Financial\SupplierCategoryController::list_ajax', ['as' => 'suppliers.categories.list_ajax']);
    $routes->post('store', 'Financial\SupplierCategoryController::store', ['as' => 'suppliers.categories.store', 'filter' => 'permission:catalog.manage-suppliers']);
    $routes->post('update/(:num)', 'Financial\SupplierCategoryController::update/$1', ['as' => 'suppliers.categories.update', 'filter' => 'permission:catalog.manage-suppliers']);
    $routes->post('delete/(:num)', 'Financial\SupplierCategoryController::delete/$1', ['as' => 'suppliers.categories.delete', 'filter' => 'permission:catalog.manage-suppliers']);
    $routes->post('restore/(:num)', 'Financial\SupplierCategoryController::restore/$1', ['as' => 'suppliers.categories.restore', 'filter' => 'permission:catalog.manage-suppliers']);
});
```

Añadir al sidebar dentro del bloque de Proveedores:
```php
<li class="sidebar-item <?= url_is('nat/suppliers/categories*') ? 'active' : '' ?>">
  <a class="sidebar-link" href="<?= route_to('suppliers.categories.index') ?>">Categorías</a>
</li>
```

---

## Vistas
- `app/Views/Financial/Suppliers/categories_index.php` — lista con DataTable, modal de alta/edición, botón "Restaurar" para eliminados.
- En el modal del proveedor (`supplier_form_modal.php` o equivalente) reemplazar el `<select>` de `supplier_type` con un Select2 que cargue categorías vía `getForSelect()`.

---

## Cumplimiento TOTAL con DOCUMENTACION_TECNICA.md

| § | Estándar | Aplicación en esta tarea |
|---|----------|--------------------------|
| §1 | AJAX guard | `store()`, `update()`, `delete()`, `restore()` llaman `$this->assertAjaxPost()`. `list_ajax()` llama `$this->assertAjaxPost()` |
| §2 | CSRF | `<input hidden csrf>` al inicio del `<main>` en `categories_index.php`. DataTable escucha `xhr.dt`. Fetch del modal inyecta token en header y actualiza DOM con nuevo token de respuesta |
| §3 | Rutas con alias | Todas las rutas con `'as' => 'suppliers.categories.*'`. Vistas solo usan `route_to()`, nunca `site_url()` |
| §4 | Sidebar activo | `url_is('nat/suppliers/categories*') ? 'active' : ''` en la nueva `<li>` del sidebar |
| §5/§16 | DataTableTrait | `FinSupplierCategoryModel` usa `use \App\Traits\DataTableTrait`. Definir `$column_order = [null, 'name', 'description', 'active', null]` y `$column_search = ['name', 'description']`. Método `get_datatables()` y `get_datatables_total()` |
| §6 | DataTables Guard | `if ($.fn.dataTable.isDataTable('#categories-table')) return;` al inicio del init JS |
| §7 | Permisos Shield | Rutas con `permission:catalog.access` (lectura) y `permission:catalog.manage-suppliers` (escritura). Botones de edición/eliminación en vista con `<?php if (auth()->user()->can('catalog.manage-suppliers')): ?>` |
| §8 | Bitácora | `logActivity('create_supplier_category', ...)`, `logActivity('update_supplier_category', ...)`, `logActivity('delete_supplier_category', ...)`, `logActivity('restore_supplier_category', ...)` — incluir nombre e ID de la categoría |
| §9 | Transacciones | No aplica — operaciones simples en tabla única |
| §10 | Select2 | En el modal de categoría (si aplica). En el selector de categoría del proveedor: `dropdownParent: $('#supplier-modal')` obligatorio |
| §11 | Fechas | No aplica en esta entidad directamente |
| §12 | FontAwesome | `fas fa-tags` para ícono de categorías. `fas fa-edit`, `fas fa-trash-alt`, `fas fa-undo` para acciones en DataTable |
| §13 | Breadcrumbs | En `index()`: `['Inicio' => route_to('dashboard.index'), 'Proveedores' => route_to('suppliers.index'), 'Categorías' => '']` |
| §14 | Vistas/Layouts | `categories_index.php` extiende `$layout` con secciones `title`, `main`, `scripts` |
| §15 | viewData/outputData | `index()` usa `renderLayout()` con `$this->viewData`. Métodos AJAX usan `setOutputSuccess()` / `setOutputError()` y devuelven `jsonWithCsrf($this->outputData)` |
| §17 | notifyShow | JS del modal usa `notifyShow(res.message, 'success')` / `notifyShow(res.message, 'danger')`. Nunca `alert()` |
| §18 | Uploads | No aplica |
| §19 | Soft Delete | `$useSoftDeletes = true` en `FinSupplierCategoryModel`. `$protectFields = true`. `$allowedFields` explícito |
| §20 | Catálogos estáticos | **No aplica §20** — el usuario requiere explícitamente tabla DB con softdelete para históricos. Esto override el patrón §20 |
| §21 | Sistema de alertas | El modal de confirmación de delete debe mostrar advertencia visual si `hasSuppliers()` retorna `true`. NO usar `AlertService` para esto — es feedback inmediato via `setOutputError()` |

---

## Modelo recomendado
`Composer 2.5` — Suficiente para migraciones + CRUD estándar + actualización de vistas de proveedor.
