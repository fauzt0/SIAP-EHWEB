# Task 009: Bitácora Global de Administrador

## Objetivo
Implementar una vista global de bitácora para administradores que muestre **todos los movimientos** del sistema
(inicio de sesión, altas, ediciones, eliminaciones) con filtros avanzados. Actualmente solo existe una vista
por usuario individual (`user_activity.php` / `UserController::activity($userId)`). Se necesita una vista
global accesible solo para administradores con el permiso `users.audit-log` (o `admin.audit-log`).

---

## Modelo de Datos
La tabla `user_activity_logs` ya existe. Verificar que tiene (al menos):
- `id`, `user_id` (FK → users.id), `action`, `description`, `ip_address`, `created_at`

Si falta `ip_address` o `created_at`, agregar una migración para añadirlos.

---

## Cambios requeridos

### 1. Migración (solo si faltan columnas)
Archivo: `app/Database/Migrations/Users/YYYY-MM-DD-HHMMSS_AddIpToUserActivityLogs.php`
- Verificar primero con `SHOW COLUMNS FROM user_activity_logs;`.
- Si falta `ip_address`, agregar `VARCHAR(45) NULL AFTER description`.

### 2. Ruta nueva
Archivo: `app/Config/Routes.php`, dentro del grupo con `filter => permission:users.audit-log` (crear permiso si no existe):

```php
$routes->get('audit-log', 'Users\UserController::auditLog', ['as' => 'admin.audit_log', 'filter' => 'permission:admin.audit-log']);
$routes->post('audit-log/ajax', 'Users\UserController::auditLog_ajax', ['as' => 'admin.audit_log_ajax', 'filter' => 'permission:admin.audit-log']);
```

Agregarlas dentro del grupo `nat/admin` que ya existe.

### 3. Controller — `app/Controllers/Users/UserController.php`
Añadir dos métodos al final:

```php
public function auditLog(): string
{
    $this->assertPermission('admin.audit-log');
    $data = [
        'title' => 'Bitácora Global',
        'breadcrumbs' => [
            ['label' => 'Administrador', 'url' => '#'],
            ['label' => 'Bitácora', 'url' => '#'],
        ],
    ];
    return $this->setViewData($data)->render('Users/global_activity');
}

public function auditLog_ajax(): ResponseInterface
{
    $this->assertAjaxPost();
    $this->assertPermission('admin.audit-log');

    $model = new \App\Models\Users\UserActivityLogsModel();
    $filters = [
        'user_id'     => $this->request->getPost('filter_user'),
        'action'      => $this->request->getPost('filter_action'),
        'date_from'   => $this->request->getPost('filter_date_from'),
        'date_to'     => $this->request->getPost('filter_date_to'),
    ];

    return $this->datatableResponse($model->getGlobalActivity($filters));
}
```

### 4. Model — `app/Models/Users/UserActivityLogsModel.php`
Añadir método:

```php
public function getGlobalActivity(array $filters = []): array
{
    $query = $this->db->table('user_activity_logs ual')
        ->select('ual.id, ual.action, ual.description, ual.ip_address, ual.created_at,
                  u.username, u.first_name, u.last_name')
        ->join('users u', 'u.id = ual.user_id', 'left')
        ->orderBy('ual.created_at', 'DESC');

    if (!empty($filters['user_id'])) {
        $query->where('ual.user_id', $filters['user_id']);
    }
    if (!empty($filters['action'])) {
        $query->like('ual.action', $filters['action']);
    }
    if (!empty($filters['date_from'])) {
        $query->where('ual.created_at >=', $filters['date_from'] . ' 00:00:00');
    }
    if (!empty($filters['date_to'])) {
        $query->where('ual.created_at <=', $filters['date_to'] . ' 23:59:59');
    }

    return $query->get()->getResultArray();
}
```

### 5. Vista — `app/Views/Users/global_activity.php`
- Extiende `Layouts/user_loggedin_layout`.
- Breadcrumb: Administrador > Bitácora.
- Filtros en card superior: selector de usuario (Select2 via AJAX `user.select_ajax`), campo de acción (texto), rango de fechas (date_from / date_to).
- DataTable server-side con columnas: ID, Usuario, Acción, Descripción, IP, Fecha.
- Botón exportar CSV/Excel si es factible.
- Usar §5 (DataTables), §6 (Select2), §12 (FontAwesome), §11 (Breadcrumbs).

### 6. Sidebar — `app/Views/Partials/loggedin_sidebar.php`
Dentro del bloque `if (auth()->user()->can('admin.manage-users'))` agregar:
```php
<li class="sidebar-item <?= url_is('nat/admin/audit-log*') ? 'active' : '' ?>">
  <a class="sidebar-link" href="<?= route_to('admin.audit_log') ?>">
    <i class="fas fa-fw fa-clipboard-list me-1"></i> Bitácora Global
  </a>
</li>
```

### 7. Permiso nuevo
Registrar en la tabla de permisos de Shield (o en `DatabaseSeeder`/`AuthPermissionsSeeder`):
- `admin.audit-log` → Descripción: "Ver bitácora global del sistema"

---

## Cumplimiento TOTAL con DOCUMENTACION_TECNICA.md

| § | Estándar | Aplicación en esta tarea |
|---|----------|--------------------------|
| §1 | AJAX guard | `auditLog_ajax()` llama `$this->assertAjaxPost()` al inicio |
| §2 | CSRF | `<input hidden csrf>` al inicio del `<main>` en `global_activity.php`. DataTable escucha `xhr.dt` para renovar el token. Respuesta via `jsonWithCsrf()` |
| §3 | Rutas con alias | `'as' => 'admin.audit_log'` y `'as' => 'admin.audit_log_ajax'`. Usar `route_to()` en la vista, nunca `site_url()` |
| §4 | Sidebar activo | `url_is('nat/admin/audit-log*') ? 'active' : ''` en `loggedin_sidebar.php` |
| §5/§16 | DataTableTrait | `UserActivityLogsModel` usa `use \App\Traits\DataTableTrait`. Definir `$column_order` y `$column_search`. Sobrescribir `_get_datatables_query()` para el JOIN con `users` y los filtros de fecha/acción/usuario |
| §6 | DataTables Guard | `if ($.fn.dataTable.isDataTable('#audit-log-table')) return;` al inicio del init JS |
| §7 | Permisos Shield | `'filter' => 'permission:admin.audit-log'` en ambas rutas. Botón en sidebar con `<?php if (auth()->user()->can('admin.audit-log')): ?>` |
| §8 | Bitácora | No aplica — vista de lectura sin mutaciones |
| §9 | Transacciones | No aplica |
| §10 | Select2 | Select de usuario usar Select2 con `dropdownParent: $('#filtros-card')` o el contenedor padre correcto |
| §11 | Fechas | Filtros `date_from` y `date_to` usan `<input type="date">` (HTML5 nativo, no flatpickr). En la tabla mostrar fechas como `date('d/m/Y H:i', strtotime($row->created_at))`. Backend guarda en ISO |
| §12 | FontAwesome | `fas fa-clipboard-list` para el ícono del sidebar. `fas fa-filter` para botón de filtros |
| §13 | Breadcrumbs | En `auditLog()`: `$this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml(['Inicio' => route_to('dashboard.index'), 'Administrador' => '#', 'Bitácora Global' => ''])` |
| §14 | Vistas/Layouts | `global_activity.php` debe usar `<?= $this->extend($layout) ?>` y secciones `title`, `main`, `scripts` |
| §15 | viewData/outputData | `auditLog()` usa `$this->viewData['response'] = [...]` y `renderLayout()`. `auditLog_ajax()` usa `$this->setOutputSuccess()` y `$this->jsonWithCsrf($this->outputData)` |
| §17 | notifyShow | No aplica (no hay mutaciones con feedback) |
| §18 | Uploads | No aplica |
| §19 | Soft Delete | No aplica — tabla de logs es append-only |
| §20 | Catálogos estáticos | No aplica |
| §21 | Sistema de alertas | No aplica |

---

## Modelo recomendado
`Composer 2.5` — Genera la vista tabular con filtros avanzados y la integración Select2 de manera eficiente.
