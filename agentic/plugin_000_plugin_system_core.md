# Plugin 000 — Plugin System Core (Prerequisito)

> **Tipo:** Tarea del ERP base (no es un plugin) | **Versión:** 1.0.0
> **Referencia:** `ARQUITECTURA_PLUGIN_SYSTEM.md`
> **⚠️ Debe completarse ANTES de implementar cualquier plugin (001–006)**

---

## 📑 Índice

1. [Objetivo](#1-objetivo)
2. [Archivos nuevos a crear](#2-archivos-nuevos-a-crear)
3. [Archivos del ERP base a modificar (5 touch points)](#3-archivos-del-erp-base-a-modificar-5-touch-points)
4. [composer.json](#4-composerjson)
5. [Migraciones del sistema de plugins](#5-migraciones-del-sistema-de-plugins)
6. [Clases del sistema de plugins](#6-clases-del-sistema-de-plugins)
7. [Plugin Market — Controlador y Vista](#7-plugin-market--controlador-y-vista)
8. [Vista parcial: sidebar_section](#8-vista-parcial-sidebar_section)
9. [Rutas del Plugin Market](#9-rutas-del-plugin-market)
10. [Permiso nuevo en AuthGroups](#10-permiso-nuevo-en-authgroups)
11. [Sidebar: enlace al Market](#11-sidebar-enlace-al-market)
12. [Cumplimiento §1–§21](#12-cumplimiento-1-21)
13. [Orden de implementación](#13-orden-de-implementación)

---

## 1. Objetivo

Implementar la **infraestructura del sistema de plugins** en el ERP base. Esta tarea crea las clases, tablas y puntos de enganche necesarios para que cualquier plugin pueda instalarse/desinstalarse de forma autónoma sin modificar el ERP.

**No hay lógica de negocio en esta tarea.** Solo infraestructura.

---

## 2. Archivos Nuevos a Crear

```
app/Libraries/PluginSystem/
├── PluginInterface.php           (contrato PHP de todo plugin)
├── BasePlugin.php                (implementación base — plugins la extienden)
├── PluginManager.php             (servicio central: boot, install, uninstall)
├── PluginHookRegistry.php        (bus de eventos: addAction, addFilter)
└── PluginMarket.php              (catálogo local+remoto de plugins)

app/Controllers/Admin/
└── PluginMarketController.php    (UI del market)

app/Views/Admin/
└── plugins_market.php            (vista del market)

app/Views/PluginSystem/
└── sidebar_section.php           (partial para ítems de sidebar de plugins)

app/Database/Migrations/System/
├── 2026-09-18-200000_CreateSysPlugins.php
├── 2026-09-18-200001_CreateSysPluginPermissions.php
└── 2026-09-18-200002_CreateSysPluginSettings.php

plugins/
└── .gitkeep                      (directorio raíz de plugins — vacío inicialmente)
```

---

## 3. Archivos del ERP Base a Modificar (5 Touch Points)

### 3.1 — `app/Config/Events.php`

**Dónde:** Al final del archivo, dentro del bloque de eventos existente.

```php
// ── Plugin System: boot de plugins activos en cada request ────────────
Events::on('pre_system', static function () {
    if (class_exists(\App\Libraries\PluginSystem\PluginManager::class)) {
        \App\Libraries\PluginSystem\PluginManager::bootActivePlugins();
    }
});
```

> **Nota:** El `class_exists` es un guard para que no falle si el sistema de plugins no está migrado aún.

---

### 3.2 — `app/Config/Routes.php`

**Dónde:** Al final del archivo, después del último grupo de rutas del ERP.

```php
// ── Plugin System: carga de rutas de plugins activos ──────────────────
if (class_exists(\App\Libraries\PluginSystem\PluginManager::class)) {
    \App\Libraries\PluginSystem\PluginManager::loadRoutes($routes);
}
```

---

### 3.3 — `app/Views/Partials/loggedin_sidebar.php`

**Dónde:** Justo antes del cierre `</nav>` o al final del último ítem del sidebar.

```php
<!-- ── Plugin System: secciones de plugins activos ────────────────────── -->
<?php if (class_exists(\App\Libraries\PluginSystem\PluginManager::class)): ?>
    <?= \App\Libraries\PluginSystem\PluginManager::renderSidebar(auth()->user()) ?>
<?php endif; ?>
```

---

### 3.4 — `app/Views/Layouts/user_loggedin_layout.php`

**Dónde A:** Dentro del `<head>`, justo antes del cierre `</head>`.

```php
<?php if (class_exists(\App\Libraries\PluginSystem\PluginManager::class)): ?>
    <?= \App\Libraries\PluginSystem\PluginManager::renderAssets() ?>
<?php endif; ?>
```

**Dónde B:** En el footer, justo antes del cierre `</body>`.

```php
<?php
if (class_exists(\App\Libraries\PluginSystem\PluginHookRegistry::class)) {
    \App\Libraries\PluginSystem\PluginHookRegistry::doAction('layout.footer');
}
?>
```

---

### 3.5 — `app/Controllers/System/AlertController.php`

**Dónde:** Dentro del método `_detectAll()`, al final de todas las detecciones existentes, antes del `return $alerts`.

```php
// ── Plugin System: detectores automáticos de plugins ──────────────────
if (class_exists(\App\Libraries\PluginSystem\PluginManager::class)) {
    $pluginAlerts = \App\Libraries\PluginSystem\PluginManager::runAlertDetectors($user);
    $alerts = array_merge($alerts, $pluginAlerts);
}
```

---

## 4. composer.json

Añadir el autoloading PSR-4 para el directorio `plugins/`:

```json
{
  "autoload": {
    "psr-4": {
      "Plugins\\": "plugins/"
    },
    "exclude-from-classmap": [
      "**/Database/Migrations/**"
    ]
  }
}
```

Después de modificar, ejecutar:
```bash
composer dump-autoload
```

---

## 5. Migraciones del Sistema de Plugins

### `2026-09-18-200000_CreateSysPlugins.php`

Namespace: `App\Database\Migrations\System`

```php
public function up(): void
{
    $this->forge->addField([
        'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
        'slug'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
        'name'         => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
        'version'      => ['type' => 'VARCHAR', 'constraint' => 20,  'null' => false],
        'main_class'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
        'class_dir'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
        'description'  => ['type' => 'TEXT',    'null' => true],
        'icon'         => ['type' => 'VARCHAR', 'constraint' => 50,  'null' => false, 'default' => 'fa-plug'],
        'status'       => ['type' => 'ENUM', 'constraint' => ['active','inactive','installing','error'], 'default' => 'inactive'],
        'config'       => ['type' => 'JSON',    'null' => true],
        'installed_at' => ['type' => 'DATETIME','null' => true],
        'updated_at'   => ['type' => 'DATETIME','null' => true],
    ]);
    $this->forge->addKey('id', true);
    $this->forge->addUniqueKey('slug');
    $this->forge->createTable('sys_plugins', true);
}

public function down(): void
{
    $this->forge->dropTable('sys_plugins', true);
}
```

### `2026-09-18-200001_CreateSysPluginPermissions.php`

```php
public function up(): void
{
    $this->forge->addField([
        'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
        'plugin_slug' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
        'permission'  => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
        'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
    ]);
    $this->forge->addKey('id', true);
    $this->forge->addUniqueKey(['plugin_slug', 'permission']);
    $this->forge->createTable('sys_plugin_permissions', true);
}

public function down(): void
{
    $this->forge->dropTable('sys_plugin_permissions', true);
}
```

### `2026-09-18-200002_CreateSysPluginSettings.php`

```php
public function up(): void
{
    $this->forge->addField([
        'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
        'plugin_slug' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
        'key'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
        'value'       => ['type' => 'TEXT',    'null' => true],
        'updated_at'  => ['type' => 'DATETIME','null' => true],
    ]);
    $this->forge->addKey('id', true);
    $this->forge->addUniqueKey(['plugin_slug', 'key']);
    $this->forge->createTable('sys_plugin_settings', true);
}

public function down(): void
{
    $this->forge->dropTable('sys_plugin_settings', true);
}
```

### `2026-09-18-200003_CreateSysPluginBackups.php`

> ⚠️ **Crítico para la seguridad de datos.** Registra todos los respaldos de plugins desinstalados. Sin esta tabla no hay mecanismo de restauración.

```php
public function up(): void
{
    $this->forge->addField([
        'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
        'plugin_slug'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
        'plugin_name'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
        'plugin_version'   => ['type' => 'VARCHAR', 'constraint' => 20,  'null' => false],
        'backup_file'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],  // ruta relativa en WRITEPATH
        'archived_tables'  => ['type' => 'JSON',    'null' => true],                        // lista de tablas _bak_*
        'backup_size_kb'   => ['type' => 'INT',     'null' => true],
        'uninstalled_at'   => ['type' => 'DATETIME','null' => false],
        'uninstalled_by'   => ['type' => 'INT',     'null' => false, 'default' => 0],
        'purged_at'        => ['type' => 'DATETIME','null' => true],
        'purged_by'        => ['type' => 'INT',     'null' => true],
        'restored_at'      => ['type' => 'DATETIME','null' => true],
        'restored_by'      => ['type' => 'INT',     'null' => true],
        'notes'            => ['type' => 'TEXT',    'null' => true],
    ]);
    $this->forge->addKey('id', true);
    $this->forge->addKey('plugin_slug');
    $this->forge->createTable('sys_plugin_backups', true);
}

public function down(): void
{
    $this->forge->dropTable('sys_plugin_backups', true);
}
```

> ⚠️ Correr las **4 migraciones** con `php spark migrate` antes de usar el sistema de plugins.

---

## 6. Clases del Sistema de Plugins

Ver especificaciones completas en `ARQUITECTURA_PLUGIN_SYSTEM.md` secciones 4 y 5. A continuación se listan los contratos clave que el agente debe implementar exactamente:

### `PluginInterface.php`
Métodos obligatorios:
- `install(): bool`
- `uninstall(): bool`
- `activate(): void`
- `deactivate(): void`
- `registerHooks(): void`
- `getSidebarItems(User $user): array`
- `loadRoutes($routes): void`
- `getConfig(): array`
- `setConfig(array $config): bool`
- `getManifest(): array`

### `BasePlugin.php`
Implementación abstracta. Helpers internos:
- `runMigrations(): bool` — usa `\Config\Services::migrations()` con el namespace del plugin
- `revertMigrations(): bool`
- `registerPermissions(): void` — inserta en `sys_plugin_permissions`
- `removePermissions(): void`
- Implementaciones default de `install()`, `uninstall()`, `activate()`, `deactivate()`

### `PluginManager.php`
Métodos estáticos principales:
- `bootActivePlugins(): void` — carga todos los plugins activos de `sys_plugins`, instancia cada clase, llama `registerHooks()`
- `loadRoutes($routes): void` — itera plugins activos, llama `$plugin->loadRoutes($routes)`
- `renderSidebar(User $user): string` — itera plugins, llama `getSidebarItems()`, renderiza con `view()`
- `renderAssets(): string` — busca `public_html/plugins/{slug}/css/plugin.css` y `js/plugin.js`
- `runAlertDetectors(User $user): array` — `PluginHookRegistry::doActionCollect('alert.detect', $user)`
- `install(string $slug): array` — instancia, llama `install()`, inserta en `sys_plugins`
- `uninstall(string $slug): array` — instancia, llama `uninstall()`, elimina de `sys_plugins`
- `activate(string $slug): bool` — actualiza status a 'active'
- `deactivate(string $slug): bool` — actualiza status a 'inactive'
- `isActive(string $slug): bool`
- `getLoaded(): array`

### `PluginHookRegistry.php`
- `addAction(string $hook, callable $cb, int $priority = 10): void`
- `doAction(string $hook, ...$args): void`
- `doActionCollect(string $hook, ...$args): array` — recolecta retornos de todos los callbacks
- `addFilter(string $hook, callable $cb, int $priority = 10): void`
- `applyFilter(string $hook, mixed $value, ...$args): mixed`

### `PluginMarket.php`
- `getCatalog(): array` — combina plugins locales (scaning `plugins/*/plugin.json`) con remoto (si `PLUGIN_MARKET_URL` en `.env`)
- `getInstalled(): array` — consulta `sys_plugins`
- `getAvailable(): array` — `getCatalog()` minus los ya instalados
- `scanLocalPlugins(): array` — escanea `plugins/` con `glob()`

---

## 7. Plugin Market — Controlador y Vista

### `app/Controllers/Admin/PluginMarketController.php`

Extiende `BaseController` (o el base del ERP).

| Método | HTTP | Ruta alias | Descripción |
|--------|------|-----------|-------------|
| `index()` | GET | `admin.plugins.index` | Lista instalados + disponibles + respaldos |
| `install()` | POST | `admin.plugins.install` | Instala un plugin por slug |
| `uninstall()` | POST | `admin.plugins.uninstall` | **Requiere status=inactive.** Archiva datos (no DROP) |
| `activate()` | POST | `admin.plugins.activate` | Activa plugin inactivo |
| `deactivate()` | POST | `admin.plugins.deactivate` | Desactiva (datos intactos) |
| `restore()` | POST | `admin.plugins.restore` | Restaura desde backup — devuelve plugin a INACTIVE |
| `purge()` | POST | `admin.plugins.purge` | **IRREVERSIBLE.** DROP tablas _bak_ + elimina SQL. Requiere texto "ELIMINAR" |
| `settings(slug)` | GET | `admin.plugins.settings` | Vista de configuración del plugin |
| `settings_save(slug)` | POST | `admin.plugins.settings.save` | Guarda config en `sys_plugin_settings` |

### Lógica de `uninstall()` en el controlador

```php
public function uninstall(): ResponseInterface
{
    $this->assertAjaxPost();
    $this->assertPermission('admin.manage-plugins');

    $slug = $this->request->getPost('slug');

    // Verificar que esté INACTIVE
    $plugin = db_connect()->table('sys_plugins')->where('slug', $slug)->get()->getRowArray();
    if (!$plugin) return $this->setOutputError('Plugin no encontrado.')->jsonWithCsrf($this->outputData);
    if ($plugin['status'] === 'active') {
        return $this->setOutputError('Desactiva el plugin antes de desinstalarlo.')->jsonWithCsrf($this->outputData);
    }

    $result = PluginManager::uninstall($slug);

    if ($result['success']) {
        $this->activityLogModel->logActivity(
            'plugin_uninstall',
            "Desinstaló plugin: {$plugin['name']}. Datos archivados en: " . ($result['backup']['backup_file'] ?? 'N/A')
        );
        $this->setOutputSuccess($result['message']);
    } else {
        $this->setOutputError($result['message']);
    }

    return $this->jsonWithCsrf($this->outputData);
}
```

### Lógica de `purge()` en el controlador

```php
public function purge(): ResponseInterface
{
    $this->assertAjaxPost();
    $this->assertPermission('admin.manage-plugins');

    $backupId    = (int) $this->request->getPost('backup_id');
    $confirmation = trim($this->request->getPost('confirmation'));

    // Confirmar que el usuario escribió "ELIMINAR"
    if ($confirmation !== 'ELIMINAR') {
        return $this->setOutputError('Escribe ELIMINAR para confirmar la eliminación permanente.')
                    ->jsonWithCsrf($this->outputData);
    }

    $result = PluginManager::purge($backupId);

    if ($result['success']) {
        $this->activityLogModel->logActivity('plugin_purge', $result['message']);
        $this->setOutputSuccess($result['message']);
    } else {
        $this->setOutputError($result['message']);
    }

    return $this->jsonWithCsrf($this->outputData);
}
```

**Reglas de este controlador:**
- §1: Todos los métodos POST con `assertAjaxPost()`
- §2: Respuestas via `jsonWithCsrf($this->outputData)`
- §7: Permiso `admin.manage-plugins` en todas las rutas
- §8: `logActivity('plugin_install'/'plugin_uninstall'/'plugin_activate'/'plugin_deactivate', '...')`
- §15: `setOutputSuccess()`/`setOutputError()` para AJAX, `renderLayout()` para GET

### `app/Views/Admin/plugins_market.php`

Estructura visual:

```
┌────────────────────────────────────────────────────────────────────┐
│ 🔌 Market de Plugins                        [🔄 Actualizar catálogo]│
├────────────────────────────────────────────────────────────────────┤
│                                                                    │
│  INSTALADOS Y ACTIVOS                                             │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │ 🖥  Administrador de Servidores    v1.0.0  ● Activo          │  │
│  │    Gestión de servidores DirectAdmin/InterServer            │  │
│  │    [⚙ Configurar]  [⏸ Desactivar]                           │  │
│  └────────────────────────────────────────────────────────────┘  │
│                                                                    │
│  INSTALADOS E INACTIVOS                                           │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │ 📋  Cotizaciones Avanzadas         v1.0.0  ○ Inactivo        │  │
│  │    [▶ Activar]  [❌ Desinstalar] ← archiva datos, no borra   │  │
│  └────────────────────────────────────────────────────────────┘  │
│                                                                    │
│  DISPONIBLES (no instalados)                                      │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │ ✍  Firma Digital                   v1.0.0                    │  │
│  │    Sistema de firma OTP por email                           │  │
│  │    [✚ Instalar]                                              │  │
│  └────────────────────────────────────────────────────────────┘  │
│                                                                    │
│  ── RESPALDOS DE PLUGINS DESINSTALADOS ──────────────────────── │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │ 🖥  Administrador de Servidores    v1.0.0                    │  │
│  │    Desinstalado: 2026-09-18 14:30  │  Respaldo: 2.4 MB      │  │
│  │    [↩ Restaurar datos]   [🔥 Purgar definitivamente]         │  │
│  │    ⚠ Purgar es irreversible — se pierden todos los datos    │  │
│  └────────────────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────────────────┘
```

### Flujo UI del botón "Desinstalar"

1. Botón solo visible si el plugin está **INACTIVE** (si está ACTIVE, mostrar "Primero desactiva")
2. Click → Modal de confirmación:
   ```
   ⚠ Desinstalar "Administrador de Servidores"
   
   Los datos del plugin serán archivados de forma segura. Podrás
   restaurarlos desde la sección "Respaldos" del Market.
   
   No se elimina ningún dato hasta que uses "Purgar".
   
   [Cancelar]  [✅ Confirmar desinstalación]
   ```
3. POST a `admin.plugins.uninstall` → respuesta con notifyShow
4. Página se recarga → plugin aparece en sección "Respaldos"

### Flujo UI del botón "Purgar definitivamente"

1. Modal de confirmación destructivo:
   ```
   🔥 ELIMINACIÓN PERMANENTE — "Administrador de Servidores"

   Esta acción DROP TABLE eliminará TODOS los datos del plugin
   de forma definitiva e irreversible.

   Tipo de dato que se borrará:
   • 3 tablas en la base de datos
   • Archivo SQL de respaldo (2.4 MB)

   Para confirmar, escribe: ELIMINAR

   [________________]

   [Cancelar]  [🔥 Eliminar definitivamente]  ← botón solo activo si texto = "ELIMINAR"
   ```
2. POST a `admin.plugins.purge` con `backup_id` + `confirmation='ELIMINAR'`

**Requerimientos de la vista:**
- CSRF hidden `<input>` al inicio del `<main>` (§2)
- Breadcrumb: Inicio > Administrador > Plugins (§13)
- Extiende `$layout` (§14)
- Cards Bootstrap con ícono FontAwesome del plugin (§12)
- Botones con confirmación via modal Bootstrap (no `confirm()` nativo) (§17)
- Los POST de instalar/desinstalar usan `fetch()` con CSRF en header (§2) + `notifyShow()` (§17)
- Visibilidad de botones según `auth()->user()->can('admin.manage-plugins')` (§7)
- Tras instalar/desinstalar: recargar la página con `window.location.reload()`

---

## 8. Vista Parcial: sidebar_section

### `app/Views/PluginSystem/sidebar_section.php`

Recibe `$items` del plugin (array de objetos/arrays con label, route, icon, permission, children).

```php
<?php
// $items — array de secciones del sidebar del plugin
// Estructura esperada de cada $item:
// [
//   'section'    => 'Herramientas',      // encabezado de grupo (opcional)
//   'label'      => 'Servidores',
//   'icon'       => 'fa-server',
//   'permission' => 'servers.access',
//   'route'      => 'servers.index',     // ruta raíz (para url_is)
//   'route_pattern' => 'nat/plugins/servers*', // para url_is()
//   'children'   => [
//       ['label' => 'Listado',    'route' => 'servers.index',   'permission' => 'servers.access'],
//       ['label' => 'Monitoreo',  'route' => 'servers.monitor', 'permission' => 'servers.access'],
//       ['label' => 'Terminal',   'route' => 'servers.ssh',     'permission' => 'servers.ssh'],
//   ],
// ]
?>
<?php foreach ($items as $item): ?>
    <?php if (!empty($item['section'])): ?>
        <li class="sidebar-header"><?= esc($item['section']) ?></li>
    <?php endif; ?>

    <?php if (!plugin_can($item['permission'])): continue; endif; ?>

    <?php $pattern = $item['route_pattern'] ?? ''; ?>
    <?php $isActive = $pattern && url_is($pattern); ?>

    <?php if (!empty($item['children'])): ?>
        <li class="sidebar-item <?= $isActive ? 'active' : '' ?>">
            <a data-bs-target="#plg-nav-<?= esc($item['route']) ?>"
               data-bs-toggle="collapse"
               class="sidebar-link <?= $isActive ? '' : 'collapsed' ?>">
                <i class="fas fa-fw <?= esc($item['icon']) ?> align-middle me-1"></i>
                <span class="align-middle"><?= esc($item['label']) ?></span>
            </a>
            <ul id="plg-nav-<?= esc($item['route']) ?>"
                class="sidebar-dropdown list-unstyled collapse <?= $isActive ? 'show' : '' ?>"
                data-bs-parent="#sidebar">
                <?php foreach ($item['children'] as $child): ?>
                    <?php if (!plugin_can($child['permission'])): continue; endif; ?>
                    <?php $childPattern = $child['route_pattern'] ?? ''; ?>
                    <li class="sidebar-item <?= ($childPattern && url_is($childPattern)) ? 'active' : '' ?>">
                        <a class="sidebar-link" href="<?= route_to($child['route']) ?>">
                            <?= esc($child['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </li>
    <?php else: ?>
        <li class="sidebar-item <?= $isActive ? 'active' : '' ?>">
            <a class="sidebar-link" href="<?= route_to($item['route']) ?>">
                <i class="fas fa-fw <?= esc($item['icon']) ?> align-middle me-1"></i>
                <span class="align-middle"><?= esc($item['label']) ?></span>
            </a>
        </li>
    <?php endif; ?>
<?php endforeach; ?>
```

---

## 9. Rutas del Plugin Market

En `app/Config/Routes.php`, dentro del grupo existente `nat/admin` (o crear si no existe):

```php
// Plugin Market — solo administradores con permiso admin.manage-plugins
$routes->group('nat/admin/plugins', [
    'filter'    => 'session',
    'namespace' => 'App\Controllers\Admin',
], function ($routes) {
    $routes->get('/', 'PluginMarketController::index',
        ['as' => 'admin.plugins.index', 'filter' => 'permission:admin.manage-plugins']);
    $routes->post('install', 'PluginMarketController::install',
        ['as' => 'admin.plugins.install', 'filter' => 'permission:admin.manage-plugins']);
    $routes->post('uninstall', 'PluginMarketController::uninstall',
        ['as' => 'admin.plugins.uninstall', 'filter' => 'permission:admin.manage-plugins']);
    $routes->post('activate', 'PluginMarketController::activate',
        ['as' => 'admin.plugins.activate', 'filter' => 'permission:admin.manage-plugins']);
    $routes->post('deactivate', 'PluginMarketController::deactivate',
        ['as' => 'admin.plugins.deactivate', 'filter' => 'permission:admin.manage-plugins']);
    $routes->post('restore', 'PluginMarketController::restore',
        ['as' => 'admin.plugins.restore', 'filter' => 'permission:admin.manage-plugins']);
    $routes->post('purge', 'PluginMarketController::purge',
        ['as' => 'admin.plugins.purge', 'filter' => 'permission:admin.manage-plugins']);
    $routes->get('settings/(:segment)', 'PluginMarketController::settings/$1',
        ['as' => 'admin.plugins.settings', 'filter' => 'permission:admin.manage-plugins']);
    $routes->post('settings/(:segment)/save', 'PluginMarketController::settings_save/$1',
        ['as' => 'admin.plugins.settings.save', 'filter' => 'permission:admin.manage-plugins']);
});
```

---

## 10. Permiso Nuevo en AuthGroups

En `app/Config/AuthGroups.php`, añadir al array `$permissions`:

```php
// ── Plugin System ──────────────────────────────────────────────────────
'admin.manage-plugins' => 'Instalar, desinstalar y configurar plugins del sistema',
```

Añadirlo a los grupos `superadmin` y `admin` en el array `$matrix`:

```php
'superadmin' => [
    // ... permisos existentes ...
    'admin.manage-plugins',
],
'admin' => [
    // ... permisos existentes ...
    'admin.manage-plugins',
],
```

---

## 11. Sidebar: Enlace al Market

En `app/Views/Partials/loggedin_sidebar.php`, dentro del bloque `if (auth()->user()->can('admin.manage-users'))` (o el bloque de Administrador), añadir:

```php
<?php if (auth()->user()->can('admin.manage-plugins')): ?>
<li class="sidebar-item <?= url_is('nat/admin/plugins*') ? 'active' : '' ?>">
    <a class="sidebar-link" href="<?= route_to('admin.plugins.index') ?>">
        <i class="fas fa-fw fa-plug align-middle me-1"></i>
        <span class="align-middle">Plugins</span>
    </a>
</li>
<?php endif; ?>
```

---

## 12. Cumplimiento §1–§21

| § | Aplicación en Plugin System Core |
|---|----------------------------------|
| §1 | `PluginMarketController`: todos los POST con `assertAjaxPost()` |
| §2 | CSRF en `plugins_market.php` + fetch con header CSRF en JS + `jsonWithCsrf()` |
| §3 | Rutas `admin.plugins.*` con alias. `route_to()` en vistas |
| §4 | `url_is('nat/admin/plugins*')` en sidebar |
| §5/§16 | No aplica (no hay DataTables en el market — son pocas filas) |
| §6 | No aplica |
| §7 | `permission:admin.manage-plugins` en todas las rutas del market |
| §8 | `logActivity` en install, uninstall, activate, deactivate |
| §9 | No aplica (no hay transacciones multi-tabla en el core) |
| §10 | No aplica |
| §11 | No aplica (el market no maneja fechas de negocio) |
| §12 | `fa-plug`, `fa-check-circle`, `fa-times-circle`, `fa-cog` en el market |
| §13 | Breadcrumb: Inicio > Administrador > Plugins |
| §14 | `plugins_market.php` extiende `$layout` con secciones estándar |
| §15 | `renderLayout()` en `index()`. `jsonWithCsrf()` en POST |
| §17 | `notifyShow()` para feedback de install/uninstall |
| §19 | No aplica (las tablas `sys_plugins*` no tienen soft-delete — son de registro) |
| §21 | No aplica en el core (los detectores los registran los plugins individuales) |

---

## 13. Orden de Implementación

```
1. Crear directorio plugins/ con .gitkeep
2. Modificar composer.json → composer dump-autoload
3. Crear las 3 clases de PluginSystem (Interface, BasePlugin, Manager, HookRegistry, Market)
4. Crear las 3 migraciones → php spark migrate
5. Crear PluginMarketController + vista plugins_market.php
6. Crear sidebar_section.php partial
7. Añadir permiso admin.manage-plugins en AuthGroups
8. Añadir los 5 touch points al código base (Events, Routes, Sidebar, Layout, AlertController)
9. Añadir enlace en sidebar → "Plugins"
10. Verificar con php spark routes | grep plugins
11. Verificar con php spark db:table sys_plugins

✅ Al terminar: el ERP puede navegar a /nat/admin/plugins y el market muestra "No hay plugins instalados."
```

---

## Prompt para el agente implementador

```
Actúa como un desarrollador experto en CodeIgniter 4. Lee y ejecuta EXACTAMENTE el plan:
@agentic/plugin_000_plugin_system_core.md

Lee también @DOCUMENTACION_TECNICA.md y @agentic/ARQUITECTURA_PLUGIN_SYSTEM.md antes de escribir código.

Este task es INFRAESTRUCTURA PURA. No implementes lógica de negocio.
No implementes ningún plugin específico todavía.

Tu objetivo es que al finalizar:
1. Existan las tablas sys_plugins, sys_plugin_permissions, sys_plugin_settings en la BD
2. Existan las clases en app/Libraries/PluginSystem/
3. El ERP tenga los 5 touch points integrados
4. La ruta /nat/admin/plugins funcione y muestre el market vacío
5. php spark routes | grep plugin no arroje errores

Cumple TODOS los §1–§21 de DOCUMENTACION_TECNICA.md.
Al terminar, añade tu reporte al final de @agentic/AUDITORIA_ARQUITECTURA.md.
```
