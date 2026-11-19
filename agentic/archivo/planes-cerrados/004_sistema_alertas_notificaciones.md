# Plan de Implementación: Sistema de Alertas y Notificaciones

> **Versión:** 1.0  
> **Módulo:** Sistema (Core)  
> **Dificultad:** Avanzado  
> **Dependencias:** CodeIgniter 4, Shield (Auth), BaseController, Layout AppStack

---

## 📋 Índice

1. [Objetivo](#1-objetivo)
2. [Arquitectura General](#2-arquitectura-general)
3. [Estructura de Base de Datos](#3-estructura-de-base-de-datos)
4. [Migraciones](#4-migraciones)
5. [Modelos](#5-modelos)
6. [Servicio de Alertas (Service Layer)](#6-servicio-de-alertas-service-layer)
7. [Controlador de Alertas (AJAX)](#7-controlador-de-alertas-ajax)
8. [Vistas y Partials](#8-vistas-y-partials)
9. [Rutas](#9-rutas)
10. [Integración con Módulos Existentes (Ejemplos)](#10-integración-con-módulos-existentes-ejemplos)
11. [Permisos Shield](#11-permisos-shield)
12. [Bitácora de Auditoría](#12-bitácora-de-auditoría)
13. [Checklist de Implementación](#13-checklist-de-implementación)

---

## 1. Objetivo

Construir un **sistema centralizado de alertas y notificaciones** con las siguientes características:

- **Dropdown global** en el topbar (`alertsDropdown`) que muestre alertas no leídas del usuario autenticado.
- **Filtrado por permisos Shield**: cada alerta requiere un permiso específico para ser visible. Un usuario sin permiso `purchasing.suppliers` no verá alertas de proveedores.
- **Enlace directo** (`target_url`) a la sección que originó la alerta.
- **Marcar como leída**: al visualizarla en el dropdown, la alerta desaparece del contador.
- **Historial completo**: enlace "Show all notifications" que muestra todas las alertas (leídas y no leídas) del usuario.
- **Arquitectura extensible**: cualquier módulo futuro podrá emitir alertas con una sola línea de código.
- **Soft deletes** en todas las tablas nuevas.
- **Iconografía FontAwesome** (según regla #12 del proyecto), con soporte para Lucide donde AppStack lo requiera.

---

## 2. Arquitectura General

```
┌──────────────────────────────────────────────────────────┐
│                    TOPBAR (loggedin_topbar.php)           │
│  ┌────────────────────────────────────────────────────┐  │
│  │  alertsDropdown  (carga vía AJAX al hacer hover)   │  │
│  │  ● Muestra últimas 5 alertas no leídas            │  │
│  │  ● Badge con contador de no leídas                │  │
│  │  ● Link "Show all notifications"                  │  │
│  └────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘
                           │
                           ▼
┌──────────────────────────────────────────────────────────┐
│              AlertService (App/Services/)                │
│  ● getUnreadByUser(userId) → array                       │
│  ● getAllByUser(userId, page) → paginated array          │
│  ● markAsRead(alertId, userId) → bool                    │
│  ● markAllAsRead(userId) → bool                          │
│  ● createAlert(data) → int (alertId)                     │
│  ● getUnreadCount(userId) → int                          │
│  ● dispatchToUsers(alertData, permissions[]) → void      │
└──────────────────────────────────────────────────────────┘
                           │
                           ▼
┌──────────────────────────────────────────────────────────┐
│           AlertUserController (AJAX Only)                │
│  ● get_unread_ajax() → JSON                              │
│  ● mark_read_ajax() → JSON                               │
│  ● mark_all_read_ajax() → JSON                           │
│  ● history() → View (página completa)                    │
│  ● history_ajax() → JSON (DataTable)                     │
└──────────────────────────────────────────────────────────┘
                           │
                           ▼
┌──────────────────────────────────────────────────────────┐
│                    Modelos                               │
│  ┌────────────────┐  ┌───────────────────────────────┐  │
│  │  AlertModel    │  │  AlertUserModel               │  │
│  │  sys_alerts    │  │  sys_alert_user               │  │
│  └────────────────┘  └───────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘
```

### Flujo de creación de alerta

```
Módulo (ej. SupplierController)
    │
    ├── $alertService = new \App\Services\AlertService();
    │
    ├── $alertId = $alertService->createAlert([
    │       'title'              => 'Pago recurrente vencido',
    │       'message'            => 'El proveedor ABC tiene un pago pendiente desde...',
    │       'type'               => 'warning',
    │       'icon'               => 'fa-exclamation-triangle',
    │       'target_url'         => route_to('suppliers.show', $supplierId),
    │       'required_permission'=> 'purchasing.suppliers',
    │       'module'             => 'suppliers',
    │       'reference_type'     => 'supplier',
    │       'reference_id'       => $supplierId,
    │   ]);
    │
    │   // Opción A: Asignar a usuarios específicos
    │   $alertService->assignToUsers($alertId, [$userId1, $userId2]);
    │
    │   // Opción B: Asignar a todos los usuarios con cierto permiso
    │   $alertService->assignByPermission($alertId, 'purchasing.suppliers');
    │
    └── listo ✓
```

---

## 3. Estructura de Base de Datos

### 3.1 Tabla: `sys_alerts`

Almacena la **definición única** de cada alerta. Un solo registro por evento.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| `id` | INT(11) UNSIGNED | PK, Auto Increment | Identificador único |
| `title` | VARCHAR(200) | NOT NULL | Título corto de la alerta |
| `message` | TEXT | NULL | Mensaje descriptivo (puede contener HTML básico) |
| `type` | VARCHAR(20) | NOT NULL, DEFAULT 'info' | 'info', 'success', 'warning', 'danger' |
| `icon` | VARCHAR(60) | NOT NULL, DEFAULT 'fa-bell' | Clase FontAwesome (ej. `fa-exclamation-triangle`) |
| `target_url` | VARCHAR(500) | NULL | URL de acceso directo (usar `route_to()` o `base_url()`) |
| `required_permission` | VARCHAR(100) | NULL | Permiso Shield requerido (NULL = visible para todos) |
| `module` | VARCHAR(50) | NULL | Identificador del módulo (ej. 'suppliers', 'hr', 'catalog') |
| `reference_type` | VARCHAR(50) | NULL | Tipo de referencia (ej. 'supplier', 'worker', 'purchase_order') |
| `reference_id` | INT(11) UNSIGNED | NULL | ID del registro referenciado |
| `created_at` | DATETIME | NULL | ISO 8601 |
| `updated_at` | DATETIME | NULL | ISO 8601 |
| `deleted_at` | DATETIME | NULL | Soft delete |

**Índices:**
- `PRIMARY KEY` (`id`)
- `INDEX` `idx_module` (`module`)
- `INDEX` `idx_permission` (`required_permission`)
- `INDEX` `idx_created_at` (`created_at`)

### 3.2 Tabla: `sys_alert_user`

Tabla pivote que registra qué usuarios han recibido/leído cada alerta.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| `id` | INT(11) UNSIGNED | PK, Auto Increment | Identificador único |
| `alert_id` | INT(11) UNSIGNED | NOT NULL, FK → sys_alerts.id | Alerta asociada |
| `user_id` | INT(11) UNSIGNED | NOT NULL, FK → users.id | Usuario destinatario |
| `is_read` | TINYINT(1) | NOT NULL, DEFAULT 0 | 0 = no leída, 1 = leída |
| `read_at` | DATETIME | NULL | Momento en que se marcó como leída |
| `created_at` | DATETIME | NULL | ISO 8601 |
| `updated_at` | DATETIME | NULL | ISO 8601 |
| `deleted_at` | DATETIME | NULL | Soft delete |

**Índices:**
- `PRIMARY KEY` (`id`)
- `UNIQUE KEY` `uq_alert_user` (`alert_id`, `user_id`) — Evita duplicados
- `INDEX` `idx_user_read` (`user_id`, `is_read`)
- `FK` `fk_alert_user_alert` → `sys_alerts.id` ON DELETE CASCADE
- `FK` `fk_alert_user_user` → `users.id` ON DELETE CASCADE

### 3.3 Esquema ER

```
┌───────────────────┐       ┌──────────────────────────┐
│    sys_alerts     │       │      sys_alert_user      │
├───────────────────┤       ├──────────────────────────┤
│ id (PK)           │──┐    │ id (PK)                  │
│ title             │  │    │ alert_id (FK) ───────────┘
│ message           │  └─── │ user_id (FK) ────────────┐
│ type              │       │ is_read                  │
│ icon              │       │ read_at                  │
│ target_url        │       │ created_at               │
│ required_permission│      │ updated_at               │
│ module            │       │ deleted_at               │
│ reference_type    │       └──────────────────────────┘
│ reference_id      │                │
│ created_at        │                │ FK
│ updated_at        │       ┌──────────────────┐
│ deleted_at        │       │     users        │
└───────────────────┘       │ (Shield)         │
                            └──────────────────┘
```

---

## 4. Migraciones

### Archivo: `app/Database/Migrations/Config/2026-06-05-000001_CreateSysAlertsTables.php`

La migración debe:

1. Crear `sys_alerts` con los campos definidos en §3.1.
2. Crear `sys_alert_user` con los campos definidos en §3.2.
3. **NO** eliminar la tabla antigua `sys_notifications` — se eliminará manualmente al final tras verificar que ningún código la referencia. De hecho, `AlertService` puede migrar datos existentes.

**Consideraciones:**
- Usar `$this->forge->addField()` con tipos exactos.
- Los `INT(11) UNSIGNED` para todas las PK y FK (regla del proyecto).
- `$this->forge->addUniqueKey(['alert_id', 'user_id'], 'uq_alert_user')` para el unique compuesto.
- Implementar `up()` y `down()`.
- En `down()` dropear en orden inverso: `sys_alert_user` primero, luego `sys_alerts`.

---

## 5. Modelos

### 5.1 `app/Models/System/AlertModel.php`

Extiende `CodeIgniter\Model`.

```php
namespace App\Models\System;

use CodeIgniter\Model;

class AlertModel extends Model
{
    protected $table            = 'sys_alerts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'title',
        'message',
        'type',
        'icon',
        'target_url',
        'required_permission',
        'module',
        'reference_type',
        'reference_id',
    ];

    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = 'deleted_at';

    protected $validationRules  = [
        'title'       => 'required|max_length[200]',
        'type'        => 'required|in_list[info,success,warning,danger]',
        'icon'        => 'required|max_length[60]',
        'target_url'  => 'permit_empty|max_length[500]',
    ];
}
```

### 5.2 `app/Models/System/AlertUserModel.php`

```php
namespace App\Models\System;

use CodeIgniter\Model;

class AlertUserModel extends Model
{
    protected $table            = 'sys_alert_user';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'alert_id',
        'user_id',
        'is_read',
        'read_at',
    ];

    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = 'deleted_at';
}
```

> **Nota:** Los `$validationRules` son opcionales en la tabla pivote; las validaciones se manejan desde el servicio.

---

## 6. Servicio de Alertas (Service Layer)

### Archivo: `app/Services/AlertService.php`

Este es el **corazón del sistema**. Centraliza toda la lógica de negocio.

#### Métodos requeridos:

| Método | Descripción |
|--------|-------------|
| `createAlert(array $data): int` | Crea una alerta en `sys_alerts`. Retorna el ID. |
| `assignToUsers(int $alertId, array $userIds): bool` | Asigna alerta existente a usuarios específicos. |
| `assignByPermission(int $alertId, string $permission): bool` | Busca todos los usuarios con ese permiso y asigna la alerta. |
| `getUnreadByUser(int $userId, int $limit = 5): array` | Obtiene alertas no leídas del usuario. JOIN con `sys_alerts` filtrando por `required_permission`. |
| `getAllByUser(int $userId, int $page = 1, int $perPage = 20): array` | Paginación de todas las alertas del usuario (leídas y no leídas). |
| `getUnreadCount(int $userId): int` | Contador de alertas no leídas para el badge. |
| `markAsRead(int $alertId, int $userId): bool` | Marca una alerta como leída. |
| `markAllAsRead(int $userId): bool` | Marca TODAS las alertas del usuario como leídas. |
| `getUnreadByUserWithPermission(int $userId): array` | Similar a `getUnreadByUser` pero además verifica que el permiso requerido exista en el sistema (Shield). |

#### Lógica de filtrado por permisos:

```php
/**
 * Obtiene las alertas no leídas para un usuario,
 * filtrando aquellas cuyo required_permission el usuario posee.
 */
public function getUnreadByUser(int $userId, int $limit = 5): array
{
    $user = auth()->getProvider()->findById($userId);
    if (!$user) return [];

    $alerts = $this->alertUserModel
        ->select('sys_alert_user.*, sys_alerts.title, sys_alerts.message, 
                  sys_alerts.type, sys_alerts.icon, sys_alerts.target_url,
                  sys_alerts.required_permission, sys_alerts.module,
                  sys_alerts.reference_type, sys_alerts.reference_id')
        ->join('sys_alerts', 'sys_alerts.id = sys_alert_user.alert_id')
        ->where('sys_alert_user.user_id', $userId)
        ->where('sys_alert_user.is_read', 0)
        ->where('sys_alerts.deleted_at', null)
        ->orderBy('sys_alerts.created_at', 'DESC')
        ->limit($limit)
        ->findAll();

    // Filtrar por permisos Shield
    return array_filter($alerts, function ($alert) use ($user) {
        if (empty($alert->required_permission)) {
            return true; // Visible para todos
        }
        return $user->can($alert->required_permission);
    });
}
```

#### Método helper de dispatch rápido:

```php
/**
 * Método de alto nivel: crea la alerta y la asigna a todos los usuarios
 * que posean el permiso especificado.
 *
 * Ejemplo de uso (una sola línea desde cualquier controlador):
 *   $alertService->dispatch(
 *       'Pago recurrente vencido',
 *       'El proveedor X tiene un adeudo...',
 *       'warning',
 *       'fa-exclamation-triangle',
 *       route_to('suppliers.show', $id),
 *       'purchasing.suppliers',
 *       'suppliers',
 *       'supplier',
 *       $id
 *   );
 */
public function dispatch(
    string $title,
    string $message,
    string $type = 'info',
    string $icon = 'fa-bell',
    ?string $targetUrl = null,
    ?string $requiredPermission = null,
    ?string $module = null,
    ?string $referenceType = null,
    ?int $referenceId = null
): int {
    $alertId = $this->createAlert([
        'title'               => $title,
        'message'             => $message,
        'type'                => $type,
        'icon'                => $icon,
        'target_url'          => $targetUrl,
        'required_permission' => $requiredPermission,
        'module'              => $module,
        'reference_type'      => $referenceType,
        'reference_id'        => $referenceId,
    ]);

    if ($requiredPermission) {
        $this->assignByPermission($alertId, $requiredPermission);
    }

    return $alertId;
}
```

#### Asignación por permiso (query a Shield):

```php
public function assignByPermission(int $alertId, string $permission): bool
{
    // Obtener usuarios con ese permiso
    $users = model('UserModel')
        ->select('users.id')
        ->join('auth_groups_users', 'auth_groups_users.user_id = users.id')
        ->join('auth_groups_permissions', 'auth_groups_permissions.group_id = auth_groups_users.group_id')
        ->where('auth_groups_permissions.permission', $permission)
        ->findAll();

    // Asignar alerta a cada usuario
    foreach ($users as $user) {
        $this->assignToUsers($alertId, [$user->id]);
    }

    return true;
}
```

> **⚠️ Atención:** Shield almacena permisos en `auth_groups_permissions` (permisos de grupo) y `auth_users_permissions` (permisos individuales). La consulta debe considerar ambas tablas para ser precisa. Ver §11 para más detalle.

---

## 7. Controlador de Alertas (AJAX)

### Archivo: `app/Controllers/System/AlertController.php`

Controlador exclusivo para peticiones AJAX (regla #1 del proyecto). Debe extender `App\Controllers\BaseController`.

#### 7.1 `get_unread_ajax()` — Obtener alertas no leídas para el dropdown

```php
/**
 * GET via AJAX
 * Devuelve JSON con las últimas N alertas no leídas del usuario autenticado.
 * 
 * Endpoint: /nat/alerts/get-unread
 * Uso: Llamado desde el topbar al cargar la página o al hacer clic en el dropdown.
 */
public function get_unread_ajax(): ResponseInterface
{
    if (!$this->request->isAJAX()) {
        throw PageNotFoundException::forPageNotFound();
    }

    $alertService = new \App\Services\AlertService();
    $userId = auth()->id();
    $limit = $this->request->getGet('limit') ?? 5;

    $alerts = $alertService->getUnreadByUser($userId, $limit);
    $count  = $alertService->getUnreadCount($userId);

    $this->setOutputSuccess('Alertas obtenidas correctamente', [
        'alerts' => $alerts,
        'count'  => $count,
    ]);

    return $this->response->setJSON($this->outputData);
}
```

#### 7.2 `mark_read_ajax()` — Marcar una alerta como leída

```php
/**
 * POST via AJAX
 * Marca una alerta específica como leída para el usuario actual.
 * 
 * Endpoint: /nat/alerts/mark-read
 * Body: { alert_id: 123 }
 */
public function mark_read_ajax(): ResponseInterface
{
    if (!$this->request->isAJAX()) {
        throw PageNotFoundException::forPageNotFound();
    }

    $alertId = (int) $this->request->getPost('alert_id');
    if ($alertId <= 0) {
        return $this->response->setJSON(
            $this->setOutputError('ID de alerta inválido')
        );
    }

    $alertService = new \App\Services\AlertService();
    $result = $alertService->markAsRead($alertId, auth()->id());

    if ($result) {
        $this->setOutputSuccess('Alerta marcada como leída');
        $this->outputData['csrf'] = csrf_hash();
    } else {
        $this->setOutputError('No se pudo marcar la alerta');
    }

    return $this->response->setJSON($this->outputData);
}
```

#### 7.3 `mark_all_read_ajax()` — Marcar todas como leídas

```php
/**
 * POST via AJAX
 * Marca todas las alertas no leídas del usuario como leídas.
 * 
 * Endpoint: /nat/alerts/mark-all-read
 */
public function mark_all_read_ajax(): ResponseInterface
{
    if (!$this->request->isAJAX()) {
        throw PageNotFoundException::forPageNotFound();
    }

    $alertService = new \App\Services\AlertService();
    $result = $alertService->markAllAsRead(auth()->id());

    if ($result) {
        $this->setOutputSuccess('Todas las alertas marcadas como leídas');
        $this->outputData['csrf'] = csrf_hash();
    } else {
        $this->setOutputError('No se pudieron marcar las alertas');
    }

    return $this->response->setJSON($this->outputData);
}
```

#### 7.4 `history()` y `history_ajax()` — Página de historial

```php
/**
 * GET — Vista HTML
 * Muestra la página completa con el historial de todas las alertas del usuario.
 * 
 * Endpoint: /nat/alerts/history
 */
public function history(): string
{
    $this->setPageTittleAhead('Notificaciones', 'Historial de Notificaciones');
    
    $this->viewData['breadCrumb'] = $this->breadcrumb->getBreadCrumbHtml([
        route_to('dashboard.index') => 'Inicio',
        ''                          => 'Notificaciones',
    ]);

    return $this->renderLayout('Layouts/user_loggedin_layout', 'System/alerts_history');
}

/**
 * POST via AJAX (DataTable)
 * Devuelve JSON paginado con todas las alertas del usuario.
 * 
 * Endpoint: /nat/alerts/history-ajax
 */
public function history_ajax(): ResponseInterface
{
    if (!$this->request->isAJAX()) {
        throw PageNotFoundException::forPageNotFound();
    }

    $alertService = new \App\Services\AlertService();
    $userId = auth()->id();
    $page   = (int) ($this->request->getPost('start') ?? 0) / 10 + 1;

    $alerts = $alertService->getAllByUser($userId, $page, 10);

    $this->setOutputSuccess('Historial obtenido', $alerts);
    return $this->response->setJSON($this->outputData);
}
```

---

## 8. Vistas y Partials

### 8.1 Modificar `app/Views/Partials/loggedin_topbar.php`

Reemplazar el bloque `alertsDropdown` actual (líneas 135-199) con una versión dinámica que cargue las alertas vía AJAX.

**Estructura propuesta:**

```html
<li class="nav-item dropdown">
    <a class="nav-icon dropdown-toggle" href="#" id="alertsDropdown" 
       data-bs-toggle="dropdown" data-bs-auto-close="outside">
        <div class="position-relative">
            <i class="align-middle text-body" data-lucide="bell"></i>
            <span class="indicator" id="alertBadge">0</span>
        </div>
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0" 
         aria-labelledby="alertsDropdown">
        <div class="dropdown-menu-header">
            <span id="alertHeader">Notificaciones</span>
        </div>
        <div class="list-group" id="alertList">
            <!-- Se llena vía JavaScript -->
            <div class="text-center py-3 text-muted">
                <i class="fas fa-spinner fa-spin fa-fw"></i> Cargando...
            </div>
        </div>
        <div class="dropdown-menu-footer">
            <a href="<?= route_to('alerts.history') ?>" class="text-muted">
                Ver todas las notificaciones
            </a>
        </div>
    </div>
</li>
```

**Script JS de carga (al final del topbar o en `pageFooterScripts` del layout):**

```javascript
/**
 * Carga las alertas no leídas en el dropdown y actualiza el badge.
 * Se ejecuta al cargar la página y cada 60 segundos (polling).
 */
function loadAlerts() {
    const csrfToken = document.getElementById('csrf_token')?.value;

    fetch('<?= route_to('alerts.get_unread') ?>', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_header() ?>': csrfToken
        }
    })
    .then(response => {
        const newToken = response.headers.get('<?= csrf_header() ?>');
        if (newToken) {
            document.getElementById('csrf_token').value = newToken;
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.response) {
            renderAlerts(data.response.alerts, data.response.count);
        }
    })
    .catch(error => console.error('Error cargando alertas:', error));
}

function renderAlerts(alerts, count) {
    const badge = document.getElementById('alertBadge');
    const list  = document.getElementById('alertList');
    const header = document.getElementById('alertHeader');

    // Actualizar badge
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline' : 'none';
    }

    // Actualizar header
    if (header) {
        header.textContent = count + ' Notificaciones nuevas';
    }

    // Renderizar lista
    if (!list) return;

    if (!alerts || alerts.length === 0) {
        list.innerHTML = `
            <a href="#" class="list-group-item">
                <div class="row g-0 align-items-center">
                    <div class="col-2 text-center">
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                    <div class="col-10">
                        <div class="text-muted">No hay notificaciones nuevas</div>
                    </div>
                </div>
            </a>
        `;
        return;
    }

    let html = '';
    alerts.forEach(alert => {
        const iconClass = getTypeIcon(alert.type);
        const textClass = getTypeClass(alert.type);
        const url = alert.target_url || '#';
        const timeAgo = timeAgoFormat(alert.created_at);

        html += `
            <a href="${url}" class="list-group-item alert-item" 
               data-alert-id="${alert.id}"
               onclick="markAlertRead(${alert.id})">
                <div class="row g-0 align-items-center">
                    <div class="col-2 text-center">
                        <i class="fas ${alert.icon || iconClass} ${textClass}"></i>
                    </div>
                    <div class="col-10 ps-1">
                        <div class="fw-semibold">${escapeHtml(alert.title)}</div>
                        <div class="text-muted small mt-1">${escapeHtml(alert.message)}</div>
                        <div class="text-muted small mt-1">${timeAgo}</div>
                    </div>
                </div>
            </a>
        `;
    });

    list.innerHTML = html;
}

function markAlertRead(alertId) {
    const csrfToken = document.getElementById('csrf_token')?.value;

    fetch('<?= route_to('alerts.mark_read') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_header() ?>': csrfToken,
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'alert_id=' + alertId
    })
    .then(response => {
        const newToken = response.headers.get('<?= csrf_header() ?>');
        if (newToken) {
            document.getElementById('csrf_token').value = newToken;
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Recargar alertas para actualizar badge
            loadAlerts();
        }
    });
}

// --- Helper functions ---
function getTypeIcon(type) {
    const map = { 'info': 'fa-info-circle', 'success': 'fa-check-circle', 
                  'warning': 'fa-exclamation-triangle', 'danger': 'fa-times-circle' };
    return map[type] || 'fa-bell';
}

function getTypeClass(type) {
    const map = { 'info': 'text-primary', 'success': 'text-success', 
                  'warning': 'text-warning', 'danger': 'text-danger' };
    return map[type] || 'text-secondary';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function timeAgoFormat(dateStr) {
    if (!dateStr) return '';
    const now = new Date();
    const date = new Date(dateStr.replace(' ', 'T') + 'Z');
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return 'Ahora';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
}

// Cargar alertas al inicio
document.addEventListener('DOMContentLoaded', function() {
    loadAlerts();
    // Polling cada 60 segundos
    setInterval(loadAlerts, 60000);
});
```

### 8.2 Vista de historial: `app/Views/System/alerts_history.php`

Vista completa con DataTable que muestra todas las alertas del usuario.

```php
<?php $this->extend($layout); ?>

<?php $this->section('title'); ?>
<?= esc($headTitle) ?>
<?php $this->endSection(); ?>

<?php $this->section('main'); ?>
<main class="content">
    <div class="container-fluid p-0">
        <div class="mb-3">
            <h1 class="h3 d-inline align-middle"><?= esc($headTitle) ?></h1>
        </div>

        <!-- CSRF Token -->
        <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

        <!-- Botón "Marcar todo como leído" -->
        <div class="mb-3">
            <button type="button" class="btn btn-outline-primary" id="btnMarkAllRead">
                <i class="fas fa-check-double fa-fw"></i> Marcar todo como leído
            </button>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Historial de Notificaciones</h5>
            </div>
            <div class="card-body">
                <table id="alertsTable" class="table table-striped table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th width="40px"></th>
                            <th>Título</th>
                            <th>Mensaje</th>
                            <th>Módulo</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th width="50px">Acción</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</main>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
if ($.fn.dataTable.isDataTable('#alertsTable')) return;

const alertsTable = $('#alertsTable').DataTable({
    ajax: {
        url: '<?= route_to('alerts.history_ajax') ?>',
        type: 'POST',
        data: function(d) {
            d['<?= csrf_token() ?>'] = document.getElementById('csrf_token').value;
        }
    },
    columns: [
        { data: 'icon', render: function(data, type, row) {
            const cls = row.type === 'warning' ? 'text-warning' : 
                       row.type === 'danger' ? 'text-danger' :
                       row.type === 'success' ? 'text-success' : 'text-primary';
            return `<i class="fas ${data || 'fa-bell'} ${cls}"></i>`;
        }},
        { data: 'title' },
        { data: 'message', render: function(data) {
            return data ? data.substring(0, 80) + (data.length > 80 ? '...' : '') : '';
        }},
        { data: 'module', render: function(data) {
            return data ? data.charAt(0).toUpperCase() + data.slice(1) : '-';
        }},
        { data: 'created_at', render: function(data) {
            return data ? new Date(data).toLocaleDateString('es-MX') : '';
        }},
        { data: 'is_read', render: function(data) {
            return data == 1 
                ? '<span class="badge bg-secondary">Leída</span>'
                : '<span class="badge bg-primary">Nueva</span>';
        }},
        { data: 'target_url', render: function(data) {
            return data 
                ? `<a href="${data}" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt"></i></a>`
                : '';
        }}
    ],
    order: [[4, 'desc']],
    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' },
    pageLength: 10,
});

// Actualizar CSRF en cada recarga
$('#alertsTable').on('xhr.dt', function(e, settings, json, xhr) {
    if (xhr && xhr.getResponseHeader('<?= csrf_header() ?>')) {
        document.getElementById('csrf_token').value = xhr.getResponseHeader('<?= csrf_header() ?>');
    }
});

// Botón "Marcar todo como leído"
document.getElementById('btnMarkAllRead').addEventListener('click', function() {
    const csrfToken = document.getElementById('csrf_token').value;
    
    fetch('<?= route_to('alerts.mark_all_read') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_header() ?>': csrfToken,
        }
    })
    .then(response => {
        const newToken = response.headers.get('<?= csrf_header() ?>');
        if (newToken) document.getElementById('csrf_token').value = newToken;
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alertsTable.ajax.reload();
            if (typeof notifyShow === 'function') {
                notifyShow(data.message, 'success');
            }
        }
    });
});
</script>
<?php $this->endSection(); ?>
```

---

## 9. Rutas

Agregar en [`app/Config/Routes.php`](app/Config/Routes.php) dentro del grupo protegido por `'filter' => 'session'` (después de los grupos existentes):

```php
// ── Sistema de Alertas / Notificaciones ─────────────────────
$routes->group('alerts', function ($routes) {
    // AJAX: Obtener alertas no leídas para el dropdown
    $routes->get('get-unread', 'System\AlertController::get_unread_ajax', 
                 ['as' => 'alerts.get_unread']);
    
    // AJAX: Marcar una alerta como leída
    $routes->post('mark-read', 'System\AlertController::mark_read_ajax', 
                  ['as' => 'alerts.mark_read']);
    
    // AJAX: Marcar todas como leídas
    $routes->post('mark-all-read', 'System\AlertController::mark_all_read_ajax', 
                  ['as' => 'alerts.mark_all_read']);
    
    // Vista HTML: Historial completo
    $routes->get('history', 'System\AlertController::history', 
                 ['as' => 'alerts.history']);
    
    // AJAX: DataTable del historial
    $routes->post('history-ajax', 'System\AlertController::history_ajax', 
                  ['as' => 'alerts.history_ajax']);
});
```

> **Nota:** Estas rutas NO llevan filtro de permiso específico porque el controlador filtra por usuario autenticado. Cualquier usuario con sesión activa puede ver sus propias alertas.

---

## 10. Integración con Módulos Existentes (Ejemplos)

### 10.1 Ejemplo: Alerta de pago recurrente vencido (Proveedores)

En [`app/Controllers/Financial/SupplierController.php`](app/Controllers/Financial/SupplierController.php), dentro del método que detecta pagos vencidos:

```php
use App\Services\AlertService;

// ... dentro de un método que verifica pagos recurrentes
$alertService = new AlertService();
$alertService->dispatch(
    title: 'Pago recurrente vencido',
    message: "El servicio {$expense->service_name} del proveedor {$supplier->name} venció el " . date('d/m/Y', strtotime($expense->due_date)),
    type: 'warning',
    icon: 'fa-exclamation-triangle',
    targetUrl: route_to('suppliers.show', $supplier->id),
    requiredPermission: 'purchasing.suppliers',
    module: 'suppliers',
    referenceType: 'supplier',
    referenceId: $supplier->id
);
```

### 10.2 Ejemplo: Nueva orden de compra pendiente de aprobación

En [`app/Controllers/Financial/PurchaseOrderController.php`](app/Controllers/Financial/PurchaseOrderController.php):

```php
$alertService = new AlertService();
$alertService->dispatch(
    title: 'OC pendiente de aprobación',
    message: "Orden de compra #{$po->po_number} de {$supplier->name} requiere aprobación.",
    type: 'info',
    icon: 'fa-file-invoice',
    targetUrl: route_to('suppliers.po.show', $po->id),
    requiredPermission: 'purchasing.access',
    module: 'purchasing',
    referenceType: 'purchase_order',
    referenceId: $po->id
);
```

### 10.3 Ejemplo: Trabajador próximo a cumplir años (RRHH)

En un cron job o al cargar el dashboard de RRHH:

```php
$alertService = new AlertService();
$alertService->dispatch(
    title: 'Cumpleaños próximo',
    message: "El trabajador {$worker->full_name} cumple años el " . date('d/m', strtotime($worker->birth_date)),
    type: 'success',
    icon: 'fa-birthday-cake',
    targetUrl: route_to('hr.worker.edit', $worker->id),
    requiredPermission: 'hr.view',
    module: 'hr',
    referenceType: 'worker',
    referenceId: $worker->id
);
```

### 10.4 Ejemplo: Producto con stock mínimo (Catálogo)

```php
$alertService->dispatch(
    title: 'Stock mínimo alcanzado',
    message: "El producto {$product->name} tiene sólo {$stock->quantity} unidades en existencia.",
    type: 'danger',
    icon: 'fa-box-open',
    targetUrl: route_to('catalog.products.edit', $product->id),
    requiredPermission: 'inventory.access',
    module: 'catalog',
    referenceType: 'product',
    referenceId: $product->id
);
```

---

## 11. Permisos Shield

### 11.1 Nuevos permisos a registrar (opcional)

El sistema de alertas en sí mismo no requiere permisos nuevos porque las alertas se filtran por los permisos existentes de cada módulo. Sin embargo, se recomienda agregar estos permisos en [`app/Config/AuthGroups.php`](app/Config/AuthGroups.php):

```php
// Alertas / Notificaciones
'alerts.view' => 'Can view personal notifications',
```

Y agregarlo a la matrix de los grupos relevantes:

```php
'superadmin' => [
    // ... permisos existentes ...
    'alerts.*',
],
'admin' => [
    // ... permisos existentes ...
    'alerts.*',
],
```

### 11.2 Query de usuarios por permiso en `assignByPermission()`

Shield almacena permisos en dos tablas:
- `auth_groups_permissions` → permisos asignados a **grupos**
- `auth_users_permissions` → permisos asignados **directamente** a usuarios

La consulta en `AlertService::assignByPermission()` debe considerar ambas:

```php
public function assignByPermission(int $alertId, string $permission): bool
{
    $db = \Config\Database::connect();
    
    // Usuarios con permiso vía grupo
    $sql = "
        SELECT DISTINCT u.id
        FROM users u
        LEFT JOIN auth_groups_users agu ON agu.user_id = u.id
        LEFT JOIN auth_groups_permissions agp ON agp.group_id = agu.group_id
        LEFT JOIN auth_users_permissions aup ON aup.user_id = u.id
        WHERE agp.permission = ? OR aup.permission = ?
    ";
    
    $userIds = $db->query($sql, [$permission, $permission])->getResultArray();
    $userIds = array_column($userIds, 'id');
    
    if (empty($userIds)) {
        return false;
    }
    
    return $this->assignToUsers($alertId, $userIds);
}
```

---

## 12. Bitácora de Auditoría

Cada operación relevante debe registrarse en `UserActivityLogsModel` (regla #8 del proyecto):

```php
// En AlertService
$logModel = new \App\Models\Users\UserActivityLogsModel();

// Al crear alerta
$logModel->logActivity(
    'alert_create',
    'Creó alerta: ' . $data['title'] . ' (ID: ' . $alertId . ')'
);

// Al asignar a usuarios
$logModel->logActivity(
    'alert_assign',
    'Asignó alerta #' . $alertId . ' a ' . count($userIds) . ' usuarios'
);
```

---

## 13. Checklist de Implementación

### Fase 1: Estructura de Datos
- [ ] Crear migración `2026-06-05-000001_CreateSysAlertsTables.php`
- [ ] Ejecutar migración (`php spark migrate`)
- [ ] Crear `app/Models/System/AlertModel.php`
- [ ] Crear `app/Models/System/AlertUserModel.php`

### Fase 2: Lógica de Negocio
- [ ] Crear `app/Services/AlertService.php` con todos los métodos descritos
- [ ] Implementar `dispatch()` como método de alto nivel (una línea)

### Fase 3: Controlador y Rutas
- [ ] Crear `app/Controllers/System/AlertController.php`
- [ ] Implementar `get_unread_ajax()`, `mark_read_ajax()`, `mark_all_read_ajax()`
- [ ] Implementar `history()` y `history_ajax()`
- [ ] Agregar rutas en `Routes.php`

### Fase 4: Vistas
- [ ] Modificar `app/Views/Partials/loggedin_topbar.php` (alertsDropdown dinámico)
- [ ] Crear `app/Views/System/alerts_history.php`
- [ ] Agregar JS de polling y manejo de alertas en el topbar

### Fase 5: Integración y Pruebas
- [ ] Integrar alerta de ejemplo en módulo de proveedores (pagos recurrentes)
- [ ] Integrar alerta de ejemplo en módulo de órdenes de compra
- [ ] Probar filtrado por permisos (crear usuario sin permiso y verificar que no vea la alerta)
- [ ] Probar marcado como leído y actualización del badge
- [ ] Probar página de historial con DataTable
- [ ] Probar botón "Marcar todo como leído"
- [ ] Verificar que el polling funcione correctamente
- [ ] Probar CSRF en todas las peticiones AJAX

### Fase 6: Limpieza
- [ ] Verificar que ningún código referencia `SysNotificationModel` o `sys_notifications`
- [ ] Eliminar tabla `sys_notifications` (migración Down)
- [ ] Eliminar archivo `app/Models/System/SysNotificationModel.php`
- [ ] Actualizar `DOCUMENTACION_TECNICA.md` con la nueva funcionalidad

---

## 📦 Resumen de Archivos a Crear/Modificar

| Archivo | Acción | Descripción |
|---------|--------|-------------|
| `app/Database/Migrations/Config/2026-06-05-000001_CreateSysAlertsTables.php` | ✅ Crear | Migración con `sys_alerts` y `sys_alert_user` |
| `app/Models/System/AlertModel.php` | ✅ Crear | Modelo para `sys_alerts` |
| `app/Models/System/AlertUserModel.php` | ✅ Crear | Modelo para `sys_alert_user` |
| `app/Services/AlertService.php` | ✅ Crear | Lógica central del sistema de alertas |
| `app/Controllers/System/AlertController.php` | ✅ Crear | Endpoints AJAX y vista de historial |
| `app/Config/Routes.php` | ✏️ Modificar | Agregar grupo de rutas `alerts` |
| `app/Views/Partials/loggedin_topbar.php` | ✏️ Modificar | Reemplazar HTML estático por dropdown dinámico |
| `app/Views/System/alerts_history.php` | ✅ Crear | Vista del historial completo |
| `app/Config/AuthGroups.php` | ✏️ Modificar | (Opcional) Agregar permiso `alerts.view` |
| `DOCUMENTACION_TECNICA.md` | ✏️ Modificar | Agregar sección del sistema de alertas |

---

> **Fin del plan.** Este documento sirve como guía completa para que un desarrollador avanzado implemente el sistema de alertas de principio a fin, siguiendo todas las reglas y estándares del proyecto.
