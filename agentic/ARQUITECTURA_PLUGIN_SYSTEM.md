# Arquitectura del Sistema de Plugins — ERP CI4

> **Versión:** 1.1.0 | **Fecha:** 2026-10-12 (v1.0: 2026-09-18) | **Autor:** Arquitecto ERP
> **Analogía de referencia:** Similar al sistema de plugins de WordPress (actions/filters), pero construido sobre CodeIgniter 4, Shield CI4 y los estándares del proyecto (§1–§21 de `DOCUMENTACION_TECNICA.md`).

> ### Nota de versión 1.1 — parches aplicados
> Esta versión incorpora los 7 parches de `AUDITORIA_PLUGIN_SYSTEM.md` (commit `7e1dbe5`), verificados contra CI4 4.5.3 y Shield 1.1:
>
> | ID | Corrección en este documento |
> |----|------------------------------|
> | P0-1 | §12 reescrita: permisos de plugin integrados al catálogo de Shield. Se elimina `PluginPermissionFilter` y el helper `plugin_can()` de consulta directa. |
> | P0-2 | §6 y §16: descubrimiento de migraciones con namespace exacto (`Autoloader::addNamespace()`); advertencia sobre `group`/`setGroup()`. |
> | P0-2b | §17: arranque tolerante (guarda de esquema + aislamiento por plugin). |
> | P0-3 | §16 reescrita: autoload por `Config\Autoload::$psr4`. Se elimina la dependencia de `composer dump-autoload`. |
> | C-1 | §4: `uninstall()` devuelve `array`, no `bool`. |
> | C-2 | §4/§5: archivado, restauración y purga pasan a `PluginBackupService`; ningún método `protected` se invoca desde el Manager. |
> | C-3 | §3, §8 y §9: campo `directory` obligatorio en el manifiesto; `class_dir` guarda la carpeta y se guarda también en `sys_plugin_backups`. |
> | C-4 | §9: limpieza del historial de migraciones al desinstalar. |
>
> **El código de referencia vigente de las clases del core vive en `agentic/plugin_000_plugin_system_core.md` (v1.1), no en §4/§5 de este documento.** Los bloques de código de §4 y §5 se conservan solo como contexto histórico: están marcados como obsoletos y no deben implementarse.

---

## 📑 Índice

1. [Filosofía y Principios](#1-filosofía-y-principios)
2. [Estructura de Directorios](#2-estructura-de-directorios)
3. [Manifiesto del Plugin (plugin.json)](#3-manifiesto-del-plugin-pluginjson)
4. [Contrato PHP — PluginInterface](#4-contrato-php--plugininterface)
5. [PluginManager — Servicio Central](#5-pluginmanager--servicio-central)
6. [Sistema de Hooks — Acciones y Filtros](#6-sistema-de-hooks--acciones-y-filtros)
7. [Puntos de Extensión del Código Base](#7-puntos-de-extensión-del-código-base)
8. [Base de Datos del Sistema de Plugins](#8-base-de-datos-del-sistema-de-plugins)
9. [Ciclo de Vida del Plugin](#9-ciclo-de-vida-del-plugin)
10. [Plugin Market](#10-plugin-market)
11. [Integración de Rutas](#11-integración-de-rutas)
12. [Integración de Permisos Shield CI4](#12-integración-de-permisos-shield-ci4)
13. [Integración de Assets (CSS/JS)](#13-integración-de-assets-cssjs)
14. [Integración de Alertas (§21)](#14-integración-de-alertas-21)
15. [Nomenclatura de Tablas](#15-nomenclatura-de-tablas)
16. [Autoloading — Composer](#16-autoloading--composer)
17. [Cambios Mínimos al Código Base (One-Time)](#17-cambios-mínimos-al-código-base-one-time)
18. [Desarrollo en Paralelo](#18-desarrollo-en-paralelo)
19. [Cumplimiento §1–§21](#19-cumplimiento-1-21)

---

## 1. Filosofía y Principios

### El principio fundamental
> **El código base del ERP nunca se edita para acomodar un plugin específico. Solo se añaden puntos de enganche (hook points) genéricos una única vez.**

Un plugin es un conjunto autónomo de:
- Controladores, Modelos, Vistas (en su propio namespace)
- Migraciones propias (tablas con prefijo `plg_{slug}_`)
- Rutas propias (bajo `/nat/plugins/{slug}/`)
- Permisos Shield propios
- Hooks registrados en el sistema de eventos

### Principios de diseño

| Principio | Implementación |
|-----------|---------------|
| **Aislamiento** | Cada plugin en `plugins/{Slug}/` con namespace `Plugins\{Slug}` |
| **No-invasivo** | El ERP base tiene 7 puntos de enganche genéricos (ver §17) — ninguno específico de plugin |
| **Reversible** | Desinstalar un plugin revierte sus migraciones y elimina sus permisos |
| **Independiente** | Un plugin puede actualizarse sin tocar el ERP base ni otros plugins |
| **Seguro** | Todos los endpoints de plugin pasan por Shield CI4 (§7) y bitácora (§8) |
| **Paralelo** | Cada plugin puede desarrollarse en su propio repositorio git |

---

## 2. Estructura de Directorios

```
/ (raíz del proyecto CI4)
├── app/                          (ERP base — no editar salvo los 5 hook points)
│   ├── Libraries/
│   │   └── PluginSystem/         ← NUEVA carpeta (parte del ERP base)
│   │       ├── PluginInterface.php
│   │       ├── PluginManager.php
│   │       ├── PluginHookRegistry.php
│   │       └── BasePlugin.php
│   └── ...
│
├── plugins/                      ← NUEVA carpeta raíz para todos los plugins
│   ├── .gitkeep
│   ├── ServerAdmin/              (Plugin: Administrador de Servidores)
│   │   ├── ServerAdmin.php       (clase principal — implementa PluginInterface)
│   │   ├── plugin.json           (manifiesto)
│   │   ├── Config/
│   │   │   ├── Routes.php        (rutas del plugin)
│   │   │   └── AuthGroups.php    (permisos nuevos)
│   │   ├── Controllers/
│   │   │   └── ServerController.php
│   │   ├── Models/
│   │   │   └── ServerModel.php
│   │   ├── Views/
│   │   │   ├── servers_index.php
│   │   │   └── Partials/
│   │   ├── Database/
│   │   │   ├── Migrations/
│   │   │   └── Seeds/
│   │   ├── Hooks/
│   │   │   ├── SidebarHook.php
│   │   │   └── SupplierHook.php
│   │   └── Assets/
│   │       ├── css/plugin.css
│   │       └── js/plugin.js
│   │
│   ├── AdvancedQuotations/
│   ├── DigitalSignature/
│   ├── WordpressAdmin/
│   ├── ChatbotCrm/
│   └── ProjectManager/
│
├── public_html/
│   └── plugins/                  ← Assets públicos de plugins (symlink o copia en deploy)
│       └── server-admin/
│           ├── css/
│           └── js/
│
└── writable/
    └── plugins/                  ← Archivos privados de plugins (uploads, cache)
        └── server-admin/
```

---

## 3. Manifiesto del Plugin (plugin.json)

Cada plugin tiene un `plugin.json` en su raíz. Es el "package.json" del plugin.

```json
{
  "slug": "server-admin",
  "directory": "ServerAdmin",
  "class": "Plugins\\ServerAdmin\\ServerAdmin",
  "name": "Administrador de Servidores",
  "version": "1.2.0",
  "min_erp_version": "1.5.0",
  "description": "Gestión de servidores con integración DirectAdmin, SSH web y monitoreo.",
  "author": "ERP Team",
  "author_url": "https://ejemplo.com",
  "icon": "fa-server",
  "color": "#0d6efd",
  "table_prefix": "plg_srvadm_",
  "route_prefix": "servers",
  "permissions": {
    "servers.access":  "Ver listado de servidores",
    "servers.manage":  "Crear y editar servidores",
    "servers.ssh":     "Acceso a terminal SSH web",
    "servers.delete":  "Eliminar servidores"
  },
  "sidebar": {
    "section": "Herramientas",
    "label": "Servidores",
    "icon": "fa-server",
    "permission": "servers.access",
    "children": [
      { "label": "Listado",       "route": "servers.index",    "permission": "servers.access" },
      { "label": "Monitoreo",     "route": "servers.monitor",  "permission": "servers.access" },
      { "label": "Terminal SSH",  "route": "servers.ssh",      "permission": "servers.ssh" }
    ]
  },
  "hooks": [
    "sidebar.render",
    "alert.detect",
    "supplier.show.tabs",
    "catalog.product.show.tabs",
    "layout.head",
    "layout.footer"
  ],
  "settings": {
    "interserver_api_key": { "type": "password", "label": "InterServer API Key", "default": "" },
    "monitor_interval":    { "type": "number",   "label": "Intervalo de monitoreo (seg)", "default": 60 }
  },
  "requires": [],
  "market_id": "plg-server-admin-001"
}
```

### Campos obligatorios

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `slug` | string | Identificador único. Solo `[a-z0-9-]`. Ej: `server-admin` |
| `directory` | string | **Obligatorio (C-3).** Carpeta real del plugin en `plugins/`, en PascalCase. Debe existir en disco y coincidir con el namespace y con `class`. Ej: `ServerAdmin` |
| `class` | string | FQCN de la clase principal |
| `name` | string | Nombre humano legible |
| `version` | string | SemVer: `MAJOR.MINOR.PATCH` |
| `min_erp_version` | string | Versión mínima del ERP |
| `table_prefix` | string | Prefijo de tablas: `plg_{slug_snake}_` |
| `route_prefix` | string | Prefijo de rutas: aparecerán en `/nat/plugins/{route_prefix}/` |
| `permissions` | object | Mapa `permiso.nombre → descripción` |

> **Nota (A-5 corregido):** los settings se declaran en la clave `settings` (nunca `settings_defaults`) y se persisten en `sys_plugin_settings` (clave/valor), que es la única fuente de verdad. La columna `sys_plugins.config` fue eliminada en v1.1.

---

## 4. Contrato PHP — PluginInterface

> **OBSOLETO EN v1.1.** El código de esta sección se conserva como contexto histórico y **no debe implementarse**.
> Especificación vigente: `agentic/plugin_000_plugin_system_core.md` §7.1 y §7.2.
> Bloques reemplazados: constructor de `BasePlugin` (ya no recibe `$config`), `getConfig()`/`setConfig()` (ahora leen y escriben `sys_plugin_settings`), `runMigrations()` (namespace exacto), `backupPluginData()`, `restoreBackupTables()` y `purgeBackup()` (extraídos a `PluginBackupService`), `install()`/`uninstall()` (firma `array` y limpieza de historial).
> La firma vigente de `uninstall()` es `uninstall(): array` (C-1).

```php
// app/Libraries/PluginSystem/PluginInterface.php
namespace App\Libraries\PluginSystem;

interface PluginInterface
{
    /**
     * Ejecutado UNA VEZ al instalar el plugin.
     * Debe correr migraciones y registrar permisos.
     */
    public function install(): bool;

    /**
     * Ejecutado UNA VEZ al desinstalar (el plugin debe estar INACTIVE).
     * Archiva los datos con RENAME a _bak_* (nunca DROP), elimina permisos y
     * borra el historial de migraciones del plugin. Ver PluginBackupService.
     *
     * @return array{success: bool, backup_id: ?int, backup_file: ?string, archived_tables: array}
     */
    public function uninstall(): array;

    /**
     * Ejecutado al activar el plugin (post-install o tras reactivar).
     * Registra hooks, carga rutas y configura estado activo.
     */
    public function activate(): void;

    /**
     * Ejecutado al desactivar (sin desinstalar).
     * El plugin no carga sus rutas ni hooks pero sus tablas permanecen.
     */
    public function deactivate(): void;

    /**
     * Registra todos los hooks/filters de este plugin en PluginHookRegistry.
     * Se llama en cada request cuando el plugin está ACTIVO.
     */
    public function registerHooks(): void;

    /**
     * Devuelve los items del sidebar que este plugin añade.
     * El sistema base los renderizará en loggedin_sidebar.php.
     */
    public function getSidebarItems(\CodeIgniter\Shield\Entities\User $user): array;

    /**
     * Devuelve rutas del plugin.
     * @param \CodeIgniter\Router\RouteCollection $routes
     */
    public function loadRoutes($routes): void;

    /**
     * Devuelve la configuración actual del plugin (del campo sys_plugins.config).
     */
    public function getConfig(): array;

    /**
     * Actualiza la configuración del plugin.
     */
    public function setConfig(array $config): bool;

    /**
     * Metadata del manifiesto.
     */
    public function getManifest(): array;
}
```

### BasePlugin — Implementación abstracta base

```php
// app/Libraries/PluginSystem/BasePlugin.php
namespace App\Libraries\PluginSystem;

abstract class BasePlugin implements PluginInterface
{
    protected array $manifest = [];
    protected array $config   = [];
    protected string $pluginPath;

    public function __construct(string $pluginPath)
    {
        $this->pluginPath = rtrim($pluginPath, '/\\') . DIRECTORY_SEPARATOR;
        $manifestFile     = $this->pluginPath . 'plugin.json';

        if (! is_file($manifestFile)) {
            throw new \RuntimeException("plugin.json no encontrado en {$this->pluginPath}");
        }

        $this->manifest = json_decode((string) file_get_contents($manifestFile), true);
    }

    public function getManifest(): array { return $this->manifest; }

    // v1.1: la configuración vive en sys_plugin_settings (clave/valor) y se
    // cachea en $this->config. Ver plugin_000 §7.2 para el código vigente.
    public function getConfig(): array   { /* defaults del manifiesto + filas de sys_plugin_settings */ }
    public function setConfig(array $config): bool { /* upsert por clave en sys_plugin_settings */ }

    /**
     * v1.1 (P0-2): registra el namespace EXACTO del plugin antes de descubrir
     * sus migraciones, porque FileLocator::listNamespaceFiles() resuelve con
     * Autoloader::getNamespace(), que es un lookup exacto de clave.
     * Código vigente en plugin_000 §7.2.
     */
    protected function runMigrations(): bool
    {
        $migrationsPath = $this->pluginPath . 'Database/Migrations';
        if (! is_dir($migrationsPath)) return true;

        $namespace = 'Plugins\\' . ($this->manifest['directory'] ?? basename($this->pluginPath));

        \Config\Services::autoloader()->addNamespace($namespace, $this->pluginPath);

        $runner = \Config\Services::migrations();
        $runner->setNamespace($namespace);

        // Guarda contra el fallo silencioso: archivos en disco + 0 descubiertas = error.
        if ($runner->findNamespaceMigrations($namespace) === []) {
            log_message('error', "[PluginSystem] {$this->manifest['slug']}: migraciones no descubiertas.");
            return false;
        }

        return $runner->latest() !== false;
    }

    /**
     * ⚠ OBSOLETO v1.1 (C-2): movido a PluginBackupService::archive().
     * Se conserva como referencia histórica; no implementar desde aquí.
     *
     * Helper: archiva los datos del plugin ANTES de desinstalar.
     * Pasos: (1) exporta SQL a WRITEPATH, (2) renombra tablas con sufijo _bak_{timestamp},
     * (3) registra en sys_plugin_backups.
     * NUNCA hace DROP TABLE. Los datos son recuperables via restore().
     *
     * @return array ['backup_file' => string, 'archived_tables' => array]
     */
    protected function backupPluginData(): array
    {
        $db        = db_connect();
        $prefix    = $this->manifest['table_prefix']; // e.g. 'plg_srvadm_'
        $slug      = $this->manifest['slug'];
        $timestamp = date('Ymd_His');

        // 1. Obtener todas las tablas del plugin
        $result = $db->query("SHOW TABLES LIKE '{$prefix}%'")->getResultArray();
        $tables = array_map('array_values', $result);
        $tables = array_map(fn($r) => $r[0], $tables);

        if (empty($tables)) {
            return ['backup_file' => null, 'archived_tables' => []];
        }

        // 2. Generar SQL de respaldo
        $backupDir  = WRITEPATH . 'plugins/backups/';
        $backupFile = $backupDir . "{$slug}_{$timestamp}.sql";

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $sql  = "-- Plugin backup: {$slug} | Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Plugin version: {$this->manifest['version']}\n\n";

        foreach ($tables as $table) {
            // Estructura de la tabla
            $create = $db->query("SHOW CREATE TABLE `{$table}`")->getRowArray();
            $sql   .= $create['Create Table'] . ";\n\n";

            // Datos
            $rows = $db->query("SELECT * FROM `{$table}`")->getResultArray();
            if (!empty($rows)) {
                $cols    = '`' . implode('`, `', array_keys($rows[0])) . '`';
                $inserts = [];
                foreach ($rows as $row) {
                    $vals = array_map(fn($v) => $v === null ? 'NULL' : $db->escape($v), $row);
                    $inserts[] = '(' . implode(', ', $vals) . ')';
                }
                $sql .= "INSERT INTO `{$table}` ({$cols}) VALUES\n";
                $sql .= implode(",\n", $inserts) . ";\n\n";
            }
        }

        file_put_contents($backupFile, $sql);

        // 3. Renombrar tablas: plg_srvadm_servers → _bak_plg_srvadm_servers_20260918_143022
        $archivedTables = [];
        foreach ($tables as $table) {
            $archiveName = '_bak_' . $table . '_' . $timestamp;
            $db->query("RENAME TABLE `{$table}` TO `{$archiveName}`");
            $archivedTables[] = $archiveName;
        }

        // 4. Registrar en sys_plugin_backups
        $db->table('sys_plugin_backups')->insert([
            'plugin_slug'     => $slug,
            'plugin_name'     => $this->manifest['name'],
            'plugin_version'  => $this->manifest['version'],
            'backup_file'     => 'plugins/backups/' . basename($backupFile),
            'archived_tables' => json_encode($archivedTables),
            'backup_size_kb'  => (int) round(filesize($backupFile) / 1024),
            'uninstalled_at'  => date('Y-m-d H:i:s'),
            'uninstalled_by'  => auth()->id() ?? 0,
        ]);

        return ['backup_file' => $backupFile, 'archived_tables' => $archivedTables];
    }

    /**
     * ⚠ OBSOLETO v1.1 (C-2): movido a PluginBackupService::restore().
     * Se conserva como referencia histórica; no implementar desde aquí.
     *
     * Helper: restaura tablas archivadas de un backup previo.
     * Renombra _bak_plg_* de vuelta a plg_* y actualiza sys_plugin_backups.
     */
    protected function restoreBackupTables(int $backupId): bool
    {
        $db     = db_connect();
        $backup = $db->table('sys_plugin_backups')->where('id', $backupId)->get()->getRowArray();
        if (!$backup) return false;

        $archivedTables = json_decode($backup['archived_tables'], true);
        $prefix         = $this->manifest['table_prefix'];

        foreach ($archivedTables as $archiveName) {
            // _bak_plg_srvadm_servers_20260918_143022 → extraer nombre original
            // Patrón: _bak_{tabla_original}_{timestamp8}_{timestamp6}
            $original = preg_replace('/^_bak_(.*?)_\d{8}_\d{6}$/', '$1', $archiveName);
            if ($original && str_starts_with($original, $prefix)) {
                $db->query("RENAME TABLE `{$archiveName}` TO `{$original}`");
            }
        }

        $db->table('sys_plugin_backups')->where('id', $backupId)->update([
            'restored_at' => date('Y-m-d H:i:s'),
            'restored_by' => auth()->id() ?? 0,
        ]);

        return true;
    }

    /**
     * ⚠ OBSOLETO v1.1 (C-2): movido a PluginBackupService::purge().
     * Se conserva como referencia histórica; no implementar desde aquí.
     *
     * Helper: purga un backup (DROP de tablas _bak_ + elimina archivo SQL).
     * IRREVERSIBLE. Solo llamar tras confirmación explícita del usuario.
     */
    protected function purgeBackup(int $backupId): bool
    {
        $db     = db_connect();
        $backup = $db->table('sys_plugin_backups')->where('id', $backupId)->get()->getRowArray();
        if (!$backup) return false;

        $archivedTables = json_decode($backup['archived_tables'], true);
        foreach ($archivedTables as $archiveName) {
            $db->query("DROP TABLE IF EXISTS `{$archiveName}`");
        }

        $sqlFile = WRITEPATH . $backup['backup_file'];
        if (file_exists($sqlFile)) {
            unlink($sqlFile);
        }

        $db->table('sys_plugin_backups')->where('id', $backupId)->update([
            'purged_at' => date('Y-m-d H:i:s'),
            'purged_by' => auth()->id() ?? 0,
        ]);

        return true;
    }

    /**
     * Helper: registra permisos del plugin en sys_plugin_permissions.
     */
    protected function registerPermissions(): void
    {
        $db = db_connect();
        foreach ($this->manifest['permissions'] as $perm => $desc) {
            $db->table('sys_plugin_permissions')->ignore(true)->insert([
                'plugin_slug' => $this->manifest['slug'],
                'permission'  => $perm,
                'description' => $desc,
            ]);
        }
    }

    /**
     * Helper: elimina permisos del plugin.
     */
    protected function removePermissions(): void
    {
        db_connect()->table('sys_plugin_permissions')
            ->where('plugin_slug', $this->manifest['slug'])
            ->delete();
    }

    /**
     * ⚠ OBSOLETO v1.1 (C-1/C-4): `install()` ya no registra permisos desde la
     * instancia de forma ambigua y `uninstall()` devuelve array y limpia el
     * historial de migraciones. Código vigente: plugin_000 §7.2.
     *
     * Implementaciones por defecto (sobreescribir si se necesita lógica específica)
     */
    public function install(): bool
    {
        if (! $this->runMigrations()) {
            return false;
        }

        PluginManager::registerPermissions($this->manifest);

        return true;
    }

    public function uninstall(): array
    {
        $backup = PluginBackupService::archive($this->manifest, $this->pluginPath);
        PluginManager::removePermissions($this->getSlug());
        db_connect()->table('migrations')->where('namespace', $this->getNamespace())->delete();

        return ['success' => true] + $backup;   // C-4: sin esto, reinstalar no recrea tablas
    }

    /**
     * Desinstala el plugin de forma SEGURA:
     * 1. Archiva datos (SQL export + RENAME tables a _bak_*)
     * 2. Elimina permisos de sys_plugin_permissions
     * Los datos son recuperables hasta que se llame purge().
     * NUNCA hace DROP TABLE directamente.
     */
    public function uninstall(): bool
    {
        $this->backupPluginData(); // archiva + renombra tablas → sys_plugin_backups
        $this->removePermissions();
        return true;
    }

    public function activate(): void   { $this->registerHooks(); }
    public function deactivate(): void { /* hooks se liberan automáticamente al no cargarse */ }
    public function getSidebarItems(\CodeIgniter\Shield\Entities\User $user): array { return []; }
    public function loadRoutes($routes): void { /* override en cada plugin */ }
}
```

---

## 5. PluginManager — Servicio Central

> **OBSOLETO EN v1.1.** El código de esta sección se conserva como contexto histórico y **no debe implementarse**.
> Especificación vigente: `agentic/plugin_000_plugin_system_core.md` §7.4.
> Reemplazos: `bootActivePlugins()` (guarda de esquema + `try/catch` por plugin, P0-2b), `install()` (`class_dir = directory`, sin `config`, detección de respaldo pendiente), `uninstall()` (`uninstall(): array`, borrado de la fila tras archivar), `restore()` y `purge()` (delegados en `PluginBackupService`, que ya no exige `plugin.json` para purgar), `registerPermissions()`/`removePermissions()` (operaciones de BD a partir del manifiesto, sin invocar métodos del plugin) y `update()` (nuevo, corre solo migraciones nuevas).

```php
// app/Libraries/PluginSystem/PluginManager.php
namespace App\Libraries\PluginSystem;

class PluginManager
{
    private static array $loadedPlugins = [];
    private static bool  $booted = false;

    // ── Boot: carga todos los plugins activos en cada request ──────────────

    public static function bootActivePlugins(): void
    {
        if (self::$booted) return;
        self::$booted = true;

        $db = db_connect();
        $plugins = $db->table('sys_plugins')
            ->where('status', 'active')
            ->get()->getResultArray();

        foreach ($plugins as $row) {
            $pluginPath = ROOTPATH . 'plugins/' . $row['class_dir'];
            $class = $row['main_class'];

            if (!file_exists($pluginPath . '/plugin.json')) continue;

            $config = json_decode($row['config'] ?? '{}', true);
            $instance = new $class($pluginPath, $config);
            $instance->registerHooks();

            self::$loadedPlugins[$row['slug']] = $instance;
        }
    }

    // ── Route loading ───────────────────────────────────────────────────────

    public static function loadRoutes($routes): void
    {
        foreach (self::$loadedPlugins as $plugin) {
            $plugin->loadRoutes($routes);
        }
    }

    // ── Sidebar rendering ───────────────────────────────────────────────────

    public static function renderSidebar(\CodeIgniter\Shield\Entities\User $user): string
    {
        $html = '';
        foreach (self::$loadedPlugins as $plugin) {
            $items = $plugin->getSidebarItems($user);
            if (!empty($items)) {
                $html .= view('PluginSystem/sidebar_section', ['items' => $items]);
            }
        }
        return $html;
    }

    // ── Asset rendering ─────────────────────────────────────────────────────

    public static function renderAssets(): string
    {
        $html = '';
        foreach (self::$loadedPlugins as $slug => $plugin) {
            $manifest = $plugin->getManifest();
            $cssPath  = "plugins/{$slug}/css/plugin.css";
            $jsPath   = "plugins/{$slug}/js/plugin.js";

            if (file_exists(FCPATH . $cssPath)) {
                $html .= "<link rel='stylesheet' href='" . base_url($cssPath) . "?v={$manifest['version']}'>\n";
            }
            if (file_exists(FCPATH . $jsPath)) {
                $html .= "<script src='" . base_url($jsPath) . "?v={$manifest['version']}'></script>\n";
            }
        }
        return $html;
    }

    // ── Alert detectors ──────────────────────────────────────────────────────

    public static function runAlertDetectors(\CodeIgniter\Shield\Entities\User $user): array
    {
        return PluginHookRegistry::doActionCollect('alert.detect', $user);
    }

    // ── Install / Uninstall ──────────────────────────────────────────────────

    public static function install(string $slug): array
    {
        $pluginPath = ROOTPATH . "plugins/{$slug}";
        $manifestFile = $pluginPath . '/plugin.json';

        if (!file_exists($manifestFile)) {
            return ['success' => false, 'message' => 'plugin.json no encontrado.'];
        }

        $manifest = json_decode(file_get_contents($manifestFile), true);
        $class = $manifest['class'];

        try {
            $instance = new $class($pluginPath);
            $result = $instance->install();

            if ($result) {
                db_connect()->table('sys_plugins')->insert([
                    'slug'        => $manifest['slug'],
                    'name'        => $manifest['name'],
                    'version'     => $manifest['version'],
                    'main_class'  => $class,
                    'class_dir'   => $slug,
                    'description' => $manifest['description'] ?? '',
                    'icon'        => $manifest['icon'] ?? 'fa-plug',
                    'status'      => 'active',
                    // v1.1: sin 'config' y class_dir = $manifest['directory'] (ver plugin_000 §7.4).
                    'installed_at'=> date('Y-m-d H:i:s'),
                ]);
                return ['success' => true, 'message' => "Plugin '{$manifest['name']}' instalado."];
            }
        } catch (\Throwable $e) {
            log_message('error', '[PluginManager::install] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al instalar: ' . $e->getMessage()];
        }

        return ['success' => false, 'message' => 'Instalación fallida.'];
    }

    /**
     * Desinstala un plugin de forma segura.
     * REQUISITO: el plugin debe estar INACTIVE antes de desinstalar.
     * Los datos se archivan (SQL + RENAME tables) — no se hace DROP TABLE.
     * Los datos son recuperables via restore() hasta que se llame purge().
     */
    public static function uninstall(string $slug): array
    {
        $db  = db_connect();
        $row = $db->table('sys_plugins')->where('slug', $slug)->get()->getRowArray();
        if (!$row) return ['success' => false, 'message' => 'Plugin no encontrado.'];

        // Validar que esté INACTIVE antes de desinstalar
        if ($row['status'] === 'active') {
            return ['success' => false, 'message' => 'Debes desactivar el plugin antes de desinstalarlo.'];
        }

        $pluginPath = ROOTPATH . 'plugins/' . $row['class_dir'];
        $class = $row['main_class'];

        try {
            $instance = new $class($pluginPath, json_decode($row['config'], true));
            $backup = $instance->uninstall(); // archiva datos + renombra tablas

            $db->table('sys_plugins')->where('slug', $slug)->delete();

            $msg = "Plugin '{$row['name']}' desinstalado. Datos respaldados.";
            if (!empty($backup['backup_file'])) {
                $msg .= " Backup: " . basename($backup['backup_file']);
            }
            return ['success' => true, 'message' => $msg, 'backup' => $backup];
        } catch (\Throwable $e) {
            log_message('error', '[PluginManager::uninstall] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al desinstalar: ' . $e->getMessage()];
        }
    }

    /**
     * Restaura un plugin desde un backup previo.
     * Renombra tablas _bak_* de vuelta a plg_*, reinstala en sys_plugins como INACTIVE.
     */
    public static function restore(int $backupId): array
    {
        $db     = db_connect();
        $backup = $db->table('sys_plugin_backups')->where('id', $backupId)->get()->getRowArray();
        if (!$backup) return ['success' => false, 'message' => 'Respaldo no encontrado.'];
        if ($backup['purged_at']) return ['success' => false, 'message' => 'Este respaldo ya fue purgado.'];

        $pluginPath = ROOTPATH . 'plugins/' . $backup['plugin_slug'];
        $manifestFile = $pluginPath . '/plugin.json';
        if (!file_exists($manifestFile)) {
            return ['success' => false, 'message' => 'Los archivos del plugin no están en disco. Sube los archivos primero.'];
        }

        $manifest = json_decode(file_get_contents($manifestFile), true);
        $class    = $manifest['class'];

        try {
            $instance = new $class($pluginPath);
            $instance->restoreBackupTables($backupId);
            $instance->registerPermissions();

            // Reinsertar en sys_plugins como inactive
            $db->table('sys_plugins')->insert([
                'slug'        => $manifest['slug'],
                'name'        => $manifest['name'],
                'version'     => $manifest['version'],
                'main_class'  => $class,
                'class_dir'   => $backup['plugin_slug'],
                'description' => $manifest['description'] ?? '',
                'icon'        => $manifest['icon'] ?? 'fa-plug',
                'status'      => 'inactive', // siempre inactive tras restaurar
                'installed_at'=> date('Y-m-d H:i:s'),
            ]);

            return ['success' => true, 'message' => "Plugin '{$manifest['name']}' restaurado. Actívalo para usarlo."];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Error al restaurar: ' . $e->getMessage()];
        }
    }

    /**
     * Purga definitivamente los datos archivados de un plugin desinstalado.
     * DROP TABLE de las tablas _bak_* + elimina el archivo SQL.
     * IRREVERSIBLE — solo llamar tras confirmación explícita del usuario.
     */
    public static function purge(int $backupId): array
    {
        $db     = db_connect();
        $backup = $db->table('sys_plugin_backups')->where('id', $backupId)->get()->getRowArray();
        if (!$backup) return ['success' => false, 'message' => 'Respaldo no encontrado.'];

        // Verificar que el plugin NO esté instalado actualmente
        $installed = $db->table('sys_plugins')->where('slug', $backup['plugin_slug'])->get()->getRow();
        if ($installed) {
            return ['success' => false, 'message' => 'No puedes purgar un plugin que está instalado.'];
        }

        $pluginPath = ROOTPATH . 'plugins/' . $backup['plugin_slug'];
        $manifest   = json_decode(file_get_contents($pluginPath . '/plugin.json'), true);
        $instance   = new $manifest['class']($pluginPath);
        $instance->purgeBackup($backupId);

        return ['success' => true, 'message' => "Datos del plugin '{$backup['plugin_name']}' eliminados definitivamente."];
    }

    public static function activate(string $slug): bool
    {
        return (bool) db_connect()->table('sys_plugins')
            ->where('slug', $slug)->update(['status' => 'active']);
    }

    public static function deactivate(string $slug): bool
    {
        return (bool) db_connect()->table('sys_plugins')
            ->where('slug', $slug)->update(['status' => 'inactive']);
    }

    public static function getLoaded(): array { return self::$loadedPlugins; }

    public static function isActive(string $slug): bool
    {
        return isset(self::$loadedPlugins[$slug]);
    }
}
```

---

## 6. Sistema de Hooks — Acciones y Filtros

Inspirado en el sistema WordPress `add_action`/`add_filter`, implementado sobre CI4 Events.

```php
// app/Libraries/PluginSystem/PluginHookRegistry.php
namespace App\Libraries\PluginSystem;

class PluginHookRegistry
{
    private static array $actions = [];
    private static array $filters = [];

    // ── ACTIONS (ejecutan código, no retornan valor útil) ──────────────────

    public static function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        self::$actions[$hook][$priority][] = $callback;
    }

    public static function doAction(string $hook, ...$args): void
    {
        if (empty(self::$actions[$hook])) return;
        ksort(self::$actions[$hook]);
        foreach (self::$actions[$hook] as $callbacks) {
            foreach ($callbacks as $cb) {
                call_user_func($cb, ...$args);
            }
        }
    }

    /** Variante que recolecta retornos de callbacks (para alertas, widgets, etc.) */
    public static function doActionCollect(string $hook, ...$args): array
    {
        $results = [];
        if (empty(self::$actions[$hook])) return $results;
        ksort(self::$actions[$hook]);
        foreach (self::$actions[$hook] as $callbacks) {
            foreach ($callbacks as $cb) {
                $result = call_user_func($cb, ...$args);
                if (is_array($result)) {
                    $results = array_merge($results, $result);
                }
            }
        }
        return $results;
    }

    // ── FILTERS (transforman un valor y lo retornan) ───────────────────────

    public static function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        self::$filters[$hook][$priority][] = $callback;
    }

    public static function applyFilter(string $hook, mixed $value, ...$args): mixed
    {
        if (empty(self::$filters[$hook])) return $value;
        ksort(self::$filters[$hook]);
        foreach (self::$filters[$hook] as $callbacks) {
            foreach ($callbacks as $cb) {
                $value = call_user_func($cb, $value, ...$args);
            }
        }
        return $value;
    }
}
```

### Catálogo de hooks — estado real de cableado (v1.1)

Solo los hooks de la tabla **v1** se despachan en el ERP. Los de la tabla **reservado** están documentados pero **no tienen punto de dispatch**: registrarlos no produce ningún efecto (ver `AUDITORIA_PLUGIN_SYSTEM.md`, anexo B).

#### v1 — cableados y utilizables

| Hook | Tipo | Argumentos | Disparado en | Uso típico |
|------|------|-----------|--------------|-----------|
| `alert.detect` | Action (Collect) | `$user` | `AlertController::_detectAll()` | Plugin registra detectores de alertas |
| `layout.footer` | Action | — | `user_loggedin_layout.php`, antes de `</body>` | JS global del plugin |

Además del bus de eventos, el contrato v1 expone tres APIs directas (no son hooks): `getSidebarItems(User)` para el sidebar, `loadRoutes($routes)` para las rutas y `renderAssets()` para los assets declarados en el manifiesto.

#### Reservado — documentado, sin dispatch (no usar en las specs 001–006)

| Hook | Tipo | Destino previsto | Trabajo necesario antes de usarlo |
|------|------|------------------|-----------------------------------|
| `sidebar.render` | Action | `loggedin_sidebar.php` | Duplicado de `getSidebarItems()`: decidir uno de los dos mecanismos |
| `layout.head` | Action | `<head>` del layout | Hoy `renderAssets()` cubre el caso |
| `dashboard.widgets` | Action (Collect) | Vista de dashboard | Añadir el punto de dispatch en la vista |
| `catalog.product.show.tabs` | Action (Collect) | Ficha de producto | `CatalogProductController` no tiene `show()`: definir el mecanismo real |
| `supplier.show.tabs` | Action (Collect) | Offcanvas de proveedor | `SupplierController::show()` devuelve JSON; los tabs viven en `suppliers_index.php` |
| `worker.show.tabs` | Action (Collect) | Ficha de trabajador | `HR\WorkerController` solo tiene `show_ajax()` |
| `supplier.products.filter` | Filter | `FinSupplierModel` | No existe el `applyFilter()` correspondiente |
| `user.can` | Filter | Autorización | No existe el `applyFilter()` correspondiente |
| `routes.after_load` | Action | `Routes.php` | Alternativa redundante a `loadRoutes()` |

> Los manifiestos deben declarar en `hooks` únicamente hooks v1. `php spark plugin:doctor` reporta como advertencia cualquier hook reservado.

---

## 7. Puntos de Extensión del Código Base

> **Cambios únicos al código base — se realizan UNA SOLA VEZ para habilitar todos los plugins futuros.**

### 7.1 `app/Config/Events.php` — Boot de plugins

```php
// Al final del archivo Events.php existente
Events::on('pre_system', function () {
    \App\Libraries\PluginSystem\PluginManager::bootActivePlugins();
});
```

### 7.2 `app/Config/Routes.php` — Carga de rutas de plugins

```php
// Al FINAL de Routes.php (después de todas las rutas ERP)
\App\Libraries\PluginSystem\PluginManager::loadRoutes($routes);
```

### 7.3 `app/Views/Partials/loggedin_sidebar.php` — Sidebar dinámico

```php
<!-- Al final del sidebar, antes del cierre del <nav> -->
<?= \App\Libraries\PluginSystem\PluginManager::renderSidebar(auth()->user()) ?>
```

### 7.4 `app/Views/Layouts/user_loggedin_layout.php` — Assets de plugins

```php
<!-- En el <head>, justo antes de </head> -->
<?= \App\Libraries\PluginSystem\PluginManager::renderAssets() ?>
```

```php
<!-- En el footer, justo antes de </body> -->
<?php \App\Libraries\PluginSystem\PluginHookRegistry::doAction('layout.footer') ?>
```

### 7.5 `app/Controllers/System/AlertController.php` — Detectores de plugins

```php
// En el método _detectAll(), al final de la lista de detecciones:
$pluginAlerts = \App\Libraries\PluginSystem\PluginManager::runAlertDetectors($user);
$alerts = array_merge($alerts, $pluginAlerts);
```

### Resumen de los 7 touch points (v1.1)

| # | Archivo | Ubicación | Código añadido |
|---|---------|-----------|----------------|
| 1 | `app/Config/Autoload.php` | `$psr4` | `'Plugins' => ROOTPATH . 'plugins'` (P0-3) |
| 2 | `app/Config/Events.php` | Al final | `PluginManager::bootActivePlugins()` en `pre_system` (tolerante, P0-2b) |
| 3 | `app/Config/Routes.php` | Al final | `PluginManager::loadRoutes($routes)` |
| 4 | `loggedin_sidebar.php` | Antes del cierre del sidebar | `PluginManager::renderSidebar(auth()->user())` |
| 5 | `user_loggedin_layout.php` | `<head>` + `</body>` | `renderAssets()` + `doAction('layout.footer')` |
| 6 | `AlertController.php` | En `_detectAll()` | `PluginManager::runAlertDetectors($user)` |
| 7 | `UserController.php` | `permissions()` + `permissions_save()` | `getAllPermissions()` (P0-1) |

El código exacto de cada uno está en `agentic/plugin_000_plugin_system_core.md` §4.

---

## 8. Base de Datos del Sistema de Plugins

### Migración: `sys_plugins` (tabla de registro)

```php
// app/Database/Migrations/System/2026-09-18-200000_CreateSysPlugins.php
$this->forge->addField([
    'id'           => ['type' => 'INT', 'auto_increment' => true],
    'slug'         => ['type' => 'VARCHAR', 'constraint' => 100],
    'name'         => ['type' => 'VARCHAR', 'constraint' => 150],
    'version'      => ['type' => 'VARCHAR', 'constraint' => 20],
    'main_class'   => ['type' => 'VARCHAR', 'constraint' => 255],
    'class_dir'    => ['type' => 'VARCHAR', 'constraint' => 100],
    'description'  => ['type' => 'TEXT', 'null' => true],
    'icon'         => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'fa-plug'],
    'status'       => ['type' => 'ENUM', 'constraint' => ['active','inactive','installing','error'], 'default' => 'inactive'],
    'installed_at' => ['type' => 'DATETIME', 'null' => true],
    'updated_at'   => ['type' => 'DATETIME', 'null' => true],
]);
$this->forge->addKey('id', true);
$this->forge->addUniqueKey('slug');
$this->forge->createTable('sys_plugins', true);
```

> **v1.1 (A-5):** la columna `config` fue eliminada. La configuración de cada plugin vive en `sys_plugin_settings` (clave/valor) y se lee/escribe con `BasePlugin::getConfig()`/`setConfig()`.

### Migración: `sys_plugin_permissions` (permisos de plugins)

```php
$this->forge->addField([
    'id'          => ['type' => 'INT', 'auto_increment' => true],
    'plugin_slug' => ['type' => 'VARCHAR', 'constraint' => 100],
    'permission'  => ['type' => 'VARCHAR', 'constraint' => 150],
    'description' => ['type' => 'VARCHAR', 'constraint' => 255],
]);
$this->forge->addKey('id', true);
$this->forge->addUniqueKey(['plugin_slug', 'permission']);
$this->forge->createTable('sys_plugin_permissions', true);
```

### Migración: `sys_plugin_settings` (configuración por plugin)

```php
$this->forge->addField([
    'id'          => ['type' => 'INT', 'auto_increment' => true],
    'plugin_slug' => ['type' => 'VARCHAR', 'constraint' => 100],
    'key'         => ['type' => 'VARCHAR', 'constraint' => 100],
    'value'       => ['type' => 'TEXT', 'null' => true],
    'updated_at'  => ['type' => 'DATETIME', 'null' => true],
]);
$this->forge->addKey('id', true);
$this->forge->addUniqueKey(['plugin_slug', 'key']);
$this->forge->createTable('sys_plugin_settings', true);
```

---

## 9. Ciclo de Vida del Plugin

> **Principio de seguridad de datos:** Nunca se hace `DROP TABLE` directo al desinstalar. Los datos siempre pasan por un estado de respaldo recuperable antes de la eliminación física definitiva.

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                         CICLO DE VIDA COMPLETO                               │
│                                                                              │
│  [Archivos en plugins/]                                                      │
│       │                                                                      │
│       ▼                                                                      │
│  NOT_INSTALLED ──install()──► INSTALLING ──OK──► ACTIVE                     │
│                                    │                │                        │
│                                  ERROR          deactivate()                 │
│                                    ▼               ▼                        │
│                                  ERROR          INACTIVE ◄──activate()──── ACTIVE │
│                                                    │                        │
│                                              uninstall()                     │
│                                                    │                        │
│                                                    ▼                        │
│                        BACKED_UP ◄── "Tablas archivadas + SQL exportado"    │
│                        (en sys_plugin_backups)                               │
│                              │                                               │
│                    ┌─────────┴─────────┐                                    │
│                    ▼                   ▼                                     │
│              restore()             purge()                                   │
│          (vuelve a INACTIVE)   (DROP definitivo — irreversible)              │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Etapa 1 — `deactivate()` (Desactivar — reversible, sin tocar datos)

**Cuándo usar:** El plugin no está funcionando bien, o se quiere pausar temporalmente.

- Cambia `sys_plugins.status = 'inactive'`
- Los hooks, rutas y sidebar del plugin **dejan de cargarse** en cada request
- Las tablas `plg_{slug}_*` permanecen **intactas** en la base de datos
- El plugin puede reactivarse en cualquier momento con `activate()`
- `logActivity('plugin_deactivate', '...')` (§8)

> 🟢 **Completamente reversible. Cero riesgo de pérdida de datos.**

---

### Etapa 2 — `uninstall()` (Desinstalar — datos respaldados, recuperable)

**Cuándo usar:** Se quiere retirar el plugin del sistema pero conservar la posibilidad de recuperar los datos.

El proceso es:

```
1. Validar que el plugin esté INACTIVE (no se puede desinstalar estando ACTIVE)
2. PluginBackupService::archive()   [v1.1 — C-2: antes backupPluginData() en BasePlugin]
   a. Registrar la fila en sys_plugin_backups ANTES de tocar nada
      (si el proceso muere a mitad, las tablas _bak_ quedan localizables)
   b. Exportar las tablas plg_{slug}_* a WRITEPATH/plugins/backups/{slug}_{fecha}.sql
   c. RENAME atómico en una sola sentencia:
      plg_srvadm_servers → _bak_plg_srvadm_servers_20260918_143022
   d. Actualizar backup_size_kb
3. PluginManager::removePermissions(slug) → eliminar de sys_plugin_permissions
4. Borrar el historial de migraciones del namespace del plugin   [v1.1 — C-4]
   DELETE FROM migrations WHERE namespace = 'Plugins\{Directory}'
   (sin esto, reinstalar el plugin no vuelve a crear sus tablas)
5. Eliminar la fila de sys_plugins
6. logActivity('plugin_uninstall', 'Desinstaló plugin: {name}. Backup: {archivo}')
```

**Resultado:** El plugin no existe en `sys_plugins`, pero sus datos viven en:
- `WRITEPATH/plugins/backups/{slug}_{timestamp}.sql` (dump SQL completo)
- Tablas `_bak_plg_{slug}_*_{timestamp}` en la base de datos (renombradas, no eliminadas)
- Registro en `sys_plugin_backups` con metadata

> 🟡 **Recuperable.** Los datos están a salvo hasta que se ejecute `purge()`.

---

### Etapa 3 — `purge()` (Purgar — IRREVERSIBLE, elimina definitivamente)

**Cuándo usar:** Después de confirmar que no se necesitan los datos.

```
1. Modal de confirmación con texto: "Esta acción eliminará PERMANENTEMENTE todos los datos del plugin.
   Escribe ELIMINAR para confirmar."
2. DROP TABLE de las tablas _bak_plg_{slug}_* (irreversible)
3. Eliminar el archivo .sql de WRITEPATH/plugins/backups/
4. Marcar purged_at / purged_by en sys_plugin_backups (la fila se conserva como
   registro de auditoría; nunca se purga un plugin que sigue instalado)
5. logActivity('plugin_purge', 'Purgó datos del plugin: {name}')
```

> **v1.1 (C-2):** la purga se ejecuta desde `PluginBackupService` y **no necesita los archivos del plugin en disco** — el caso típico es un plugin ya desinstalado cuyos archivos se borraron.

> 🔴 **Irreversible. Requiere confirmación explícita por texto.**

---

### Etapa extra — `restore()` (Restaurar desde respaldo)

Si el plugin fue desinstalado por error, el Market muestra una sección "Respaldos disponibles":

```
1. Leer sys_plugin_backups por id (no por slug: un plugin puede tener varios respaldos)
2. Localizar la carpeta con class_dir y validar que plugin.json existe
3. Renombrar las tablas _bak_* de vuelta a su nombre original, en una sola
   sentencia RENAME TABLE (v1.1 — C-2)
4. Reinsertar el plugin en sys_plugins con status = 'inactive' usando class_dir
5. Reinsertar permisos en sys_plugin_permissions
6. Marcar restored_at / restored_by en sys_plugin_backups
7. logActivity('plugin_restore', 'Restauró plugin desde respaldo: {name}')
```

El plugin queda `INACTIVE` — el administrador debe activarlo manualmente después de verificar que todo esté en orden.

---

### Nueva tabla requerida: `sys_plugin_backups`

```sql
id              INT AI PK
plugin_slug     VARCHAR(100)
plugin_name     VARCHAR(150)
plugin_version  VARCHAR(20)
class_dir       VARCHAR(100)        -- carpeta real del plugin (C-3); permite restaurar/purgar sin slug
backup_file     VARCHAR(500)        -- ruta relativa en WRITEPATH
archived_tables JSON               -- lista de tablas _bak_ creadas
backup_size_kb  INT NULL
uninstalled_at  DATETIME
uninstalled_by  INT FK users.id
purged_at       DATETIME NULL
purged_by       INT NULL FK users.id
restored_at     DATETIME NULL
restored_by     INT NULL FK users.id
notes           TEXT NULL
```

---

### Proceso de instalación

1. `PluginMarketController::install(slug)` recibe la petición
2. `PluginManager::install(slug)` valida el manifiesto: `slug`, `directory` (la carpeta debe existir), `class` instanciable
3. **Si existe un respaldo pendiente en `sys_plugin_backups` para ese slug:** no se instala nada; se responde ofreciendo **Restaurar** (los datos viven en `_bak_*`)
4. `BasePlugin::install()` → `runMigrations()` (namespace exacto registrado en runtime) + `PluginManager::registerPermissions($manifest)`
5. Se inserta en `sys_plugins` con `class_dir = manifest.directory` y `status = 'active'`
6. `logActivity('plugin_install', 'Instaló plugin: ' . $name)` (§8)

### Proceso de actualización

1. Subir nuevos archivos a `plugins/{Directory}/` (reemplazar los anteriores)
2. `PluginMarketController::update(slug)` llama `PluginManager::update(slug)`
3. Corre **solo las migraciones nuevas** (el historial está acotado por namespace del plugin)
4. Actualiza `sys_plugins.version` y `updated_at`
5. `logActivity('plugin_update', '...')`

> ⚠️ Las actualizaciones **nunca revierten** migraciones previas. Solo aplican las nuevas. Si una migración de actualización falla, el plugin vuelve a su estado anterior.

---

### Resumen visual de acciones en el Market

| Acción | Datos | Rutas/Hooks | Reversible | Requiere confirmación |
|--------|-------|-------------|------------|----------------------|
| **Activar** | Sin cambio | Se cargan | — | No |
| **Desactivar** | Sin cambio | Se detienen | ✅ Sí | No |
| **Desinstalar** | Archivados (rename + SQL) | Se eliminan | ✅ Sí (restore) | Modal con checkbox |
| **Restaurar** | Se recuperan | Inactivas | — | No |
| **Purgar** | **Eliminados definitivamente** | Ya eliminadas | ❌ No | Modal + texto "ELIMINAR" |
| **Actualizar** | Sin cambio | Sin cambio | — | No |

---

## 10. Plugin Market

### Interfaz visual (`/nat/admin/plugins`)

```
┌─────────────────────────────────────────────────────────────────┐
│  🔌 Market de Plugins                          [⬆ Subir plugin] │
├─────────────────────────────────────────────────────────────────┤
│  INSTALADOS                                                     │
│  ┌──────────────────────────────────────────┐                  │
│  │ 🖥 Administrador de Servidores  v1.2.0    │  [⚙] [❌ Desins] │
│  │   Gestión de servidores DirectAdmin       │  ● Activo        │
│  └──────────────────────────────────────────┘                  │
│                                                                 │
│  DISPONIBLES                                                    │
│  ┌──────────────────────────────────────────┐                  │
│  │ 📋 Cotizaciones Avanzadas    v1.0.0       │  [✚ Instalar]    │
│  │   Builder de cotizaciones con PDF         │  ○ No instalado  │
│  └──────────────────────────────────────────┘                  │
└─────────────────────────────────────────────────────────────────┘
```

### Fuentes del market

```php
// app/Libraries/PluginSystem/PluginMarket.php
class PluginMarket
{
    /**
     * Obtiene el catálogo combinado: local + remoto (si configurado).
     */
    public static function getCatalog(): array
    {
        // 1. Catálogo local (plugins en disco no instalados)
        $local = self::scanLocalPlugins();

        // 2. Catálogo remoto (opcional, URL configurable en .env)
        $remoteUrl = env('PLUGIN_MARKET_URL');
        $remote = $remoteUrl ? self::fetchRemoteCatalog($remoteUrl) : [];

        return array_merge($local, $remote);
    }

    private static function scanLocalPlugins(): array
    {
        $plugins = [];
        $dirs = glob(ROOTPATH . 'plugins/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $manifest = $dir . '/plugin.json';
            if (file_exists($manifest)) {
                $plugins[] = json_decode(file_get_contents($manifest), true);
            }
        }
        return $plugins;
    }
}
```

### Controlador del Market

```
Controller: app/Controllers/Admin/PluginMarketController.php  (extends PluginBaseController)
Ruta base:  GET  /nat/admin/plugins           (as: admin.plugins.index)
            POST /nat/admin/plugins/install    (as: admin.plugins.install)
            POST /nat/admin/plugins/uninstall  (as: admin.plugins.uninstall)   ← exige status inactive
            POST /nat/admin/plugins/activate   (as: admin.plugins.activate)
            POST /nat/admin/plugins/deactivate (as: admin.plugins.deactivate)
            POST /nat/admin/plugins/restore    (as: admin.plugins.restore)     ← devuelve a INACTIVE
            POST /nat/admin/plugins/purge      (as: admin.plugins.purge)       ← requiere texto ELIMINAR
            POST /nat/admin/plugins/update     (as: admin.plugins.update)
            GET  /nat/admin/plugins/settings/(:slug) (as: admin.plugins.settings)
            POST /nat/admin/plugins/settings/(:slug) (as: admin.plugins.settings.save)

Permiso requerido: admin.manage-plugins (nuevo permiso del ERP base)
```

---

## 11. Integración de Rutas

Cada plugin registra sus rutas en `Config/Routes.php`:

```php
// plugins/ServerAdmin/Config/Routes.php
// Namespace: Plugins\ServerAdmin\Controllers

// Este archivo NO se incluye directamente — lo invoca PluginManager::loadRoutes()
// vía $plugin->loadRoutes($routes)

return function ($routes) {
    $routes->group('nat/plugins/servers', [
        'namespace' => 'Plugins\ServerAdmin\Controllers',
        'filter'    => 'session',
    ], function ($routes) {
        // Lectura
        $routes->get('/', 'ServerController::index', ['as' => 'servers.index', 'filter' => 'permission:servers.access']);
        $routes->post('list_ajax', 'ServerController::list_ajax', ['as' => 'servers.list_ajax', 'filter' => 'permission:servers.access']);
        $routes->get('show/(:num)', 'ServerController::show/$1', ['as' => 'servers.show', 'filter' => 'permission:servers.access']);

        // Mutaciones
        $routes->post('store', 'ServerController::store', ['as' => 'servers.store', 'filter' => 'permission:servers.manage']);
        $routes->post('update/(:num)', 'ServerController::update/$1', ['as' => 'servers.update', 'filter' => 'permission:servers.manage']);
        $routes->post('delete/(:num)', 'ServerController::delete/$1', ['as' => 'servers.delete', 'filter' => 'permission:servers.delete']);

        // Terminal SSH web
        $routes->get('ssh/(:num)', 'ServerController::ssh/$1', ['as' => 'servers.ssh', 'filter' => 'permission:servers.ssh']);
        $routes->post('ssh/exec', 'ServerController::sshExec', ['as' => 'servers.ssh.exec', 'filter' => 'permission:servers.ssh']);

        // Monitor
        $routes->get('monitor', 'ServerController::monitor', ['as' => 'servers.monitor', 'filter' => 'permission:servers.access']);
        $routes->post('monitor/ajax', 'ServerController::monitor_ajax', ['as' => 'servers.monitor.ajax', 'filter' => 'permission:servers.access']);
    });
};
```

El método `loadRoutes()` del plugin:

```php
// plugins/ServerAdmin/ServerAdmin.php
public function loadRoutes($routes): void
{
    $routesCallback = require $this->pluginPath . '/Config/Routes.php';
    $routesCallback($routes);
}
```

> **v1.1:** este método se invoca desde `PluginManager::loadRoutes()` al final de `app/Config/Routes.php`, **fuera** del grupo `nat` y de su grupo con `filter => session`. Por eso cada plugin debe declarar en su propio `Config/Routes.php` el `namespace`, el `filter => session` y el `permission:` de cada ruta (plantilla lista en `plugin_000` §11). `Config\Routing::$autoRoute` es `false`: no hay descubrimiento automático de controladores.

---

## 12. Integración de Permisos Shield CI4

### Estrategia v1.1 (P0-1): permisos de plugin integrados al catálogo de Shield

> **Se elimina `PluginPermissionFilter`.** Verificado contra Shield 1.1: `Authorizable::hasPermission()` es un `in_array` exacto sobre `auth_permissions_users`, `can()` resuelve además los comodines de `setting('AuthGroups.matrix')`, y el alias `permission:` ya lo registra Shield. Un filtro propio solo duplica comprobaciones y, además, el filtro de Shield responde con **redirect** (no 403), por lo que tampoco aportaba coherencia en AJAX.

**Flujo completo del permiso de un plugin:**

```
plugin.json (permissions)
   └─ al instalar/restaurar: PluginManager::registerPermissions()
        └─ sys_plugin_permissions          (registro + auditoría + retiro al desinstalar)
             └─ UserController::getAllPermissions()   [§17.6 — único touch point del ERP]
                  └─ UI /nat/user/permissions/{id}   (el permiso se ve y se asigna)
                       └─ Shield guarda en auth_permissions_users
                            └─ permiso:  en rutas · auth()->user()->can(...) en vistas y controladores
                                 └─ detectores de alertas y sidebar
```

**Touch point en `UserController` (una sola vez):**

```php
// app/Controllers/Users/UserController.php
private function getAllPermissions(): array
{
    $permissions = setting('AuthGroups.permissions');

    if (db_connect()->tableExists('sys_plugin_permissions')) {
        foreach (db_connect()->table('sys_plugin_permissions')->get()->getResultArray() as $row) {
            $permissions[$row['permission']] = '[Plugin] ' . $row['description'];
        }
    }

    return $permissions;
}
```

Se usa en `permissions()` (sustituye a `setting('AuthGroups.permissions')`) y en `permissions_save()`. En `permissions_save()` el merge es **imprescindible**: `syncPermissions()` reemplaza el set completo del usuario y, sin el permiso en el catálogo, lo borraría en el siguiente guardado.

**Reglas de uso en plugins:**

- Rutas: `'filter' => 'permission:servers.access'` (filtro nativo de Shield, sin alias propio).
- Vistas y controladores: `auth()->user()->can('servers.access')`.
- No existe ni se crea `plugin_can()`; la comprobación directa contra `auth_permissions_users` ignoraba los permisos concedidos por grupo.
- Los permisos con prefijo `admin.*` ya se conceden por comodín a los grupos `admin` y `superadmin` (`Authorizable::can()`), por lo que no hay que editar `$matrix`.
- Al desinstalar se retiran del registro (`removePermissions()`); las asignaciones huérfanas en `auth_permissions_users` son inertes y su limpieza queda como tarea menor no bloqueante.

---

## 13. Integración de Assets (CSS/JS)

### Convención de rutas (v1.1 — A-4 corregido en la definición)

| Origen (repo) | Destino público (docroot) | Quién lo lee |
|---|---|---|
| `plugins/{Directory}/Assets/css/plugin.css` | `public_html/plugins/{slug}/css/plugin.css` | `PluginManager::renderAssets()` |
| `plugins/{Directory}/Assets/js/plugin.js` | `public_html/plugins/{slug}/js/plugin.js` | `PluginManager::renderAssets()` |

`renderAssets()` lee **siempre** desde `FCPATH . "plugins/{slug}/..."` (el destino público), nunca desde la carpeta del plugin: así el ERP no depende de que el código fuente sea legible por el servidor web.

### En desarrollo (local)

```bash
ln -s ../../plugins/ServerAdmin/Assets public_html/plugins/server-admin
```

### En producción (deploy)

```bash
cp -r plugins/ServerAdmin/Assets/. public_html/plugins/server-admin/
```

### Pendiente (A-4, fuera del alcance de PLUGIN-000)

- Añadir a `.gitignore` los artefactos generados: `public_html/plugins/` (copia de deploy) y `writable/plugins/backups/` (los dumps SQL contienen datos de clientes y no deben versionarse).
- Decidir si `plugins/` (código fuente) se versiona en el repo del ERP o se gestiona como repositorio/submódulo por plugin.

### Versionado de assets (cache busting)

`PluginManager::renderAssets()` añade `?v={version}` del `plugin.json` automáticamente.

---

## 14. Integración de Alertas (§21)

Los detectores de plugins siguen exactamente el mismo patrón que los detectores nativos del ERP:

```php
// plugins/ServerAdmin/Hooks/AlertHook.php
namespace Plugins\ServerAdmin\Hooks;

use App\Libraries\PluginSystem\PluginHookRegistry;

class AlertHook
{
    public static function register(): void
    {
        PluginHookRegistry::addAction('alert.detect', [self::class, 'detect'], 20);
    }

    public static function detect(\CodeIgniter\Shield\Entities\User $user): array
    {
        if (! auth()->user()?->can('servers.access')) return [];

        $db = db_connect();
        $downCount = $db->table('plg_srvadm_servers')
            ->where('status', 'down')
            ->whereNull('deleted_at')
            ->countAllResults();

        if ($downCount === 0) return [];

        // Mismo formato que _buildAutoAlert() del ERP base
        return [[
            'id'         => 0,
            'pivot_id'   => 0,
            'title'      => 'Servidores caídos',
            'message'    => "$downCount servidor(es) sin responder.",
            'type'       => 'danger',
            'icon'       => 'fa-server',
            'target_url' => route_to('servers.monitor'),
            'module'     => 'Servidores',
            'is_auto'    => 1,
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]];
    }
}
```

---

## 15. Nomenclatura de Tablas

| Prefijo | Módulo |
|---------|--------|
| `catalog_` | Catálogo de productos |
| `fin_`     | Financiero (proveedores, OC) |
| `hr_`      | Recursos Humanos |
| `org_`     | Organización (empresa, sucursales) |
| `sys_`     | Sistema (alertas, plugins, logs) |
| `data_`    | Datos de contacto |
| `inventory_` | Inventario |
| **`plg_{slug}_`** | **Tablas de plugins** |

### Ejemplos de prefijos por plugin

| Plugin | Slug | Prefijo de tablas |
|--------|------|------------------|
| Administrador de Servidores | `server-admin` | `plg_srvadm_` |
| Cotizaciones Avanzadas | `adv-quotations` | `plg_quot_` |
| Firma Digital | `digital-signature` | `plg_dsig_` |
| WordPress Admin | `wp-admin` | `plg_wpadm_` |
| Chatbot CRM | `chatbot-crm` | `plg_chat_` |
| Administrador de Proyectos | `project-manager` | `plg_proj_` |

---

## 16. Autoloading (v1.1 — sin Composer)

### Modificación a `app/Config/Autoload.php`

```php
public $psr4 = [
    APP_NAMESPACE => APPPATH,
    'Config'      => APPPATH . 'Config',
    'Plugins'     => ROOTPATH . 'plugins',   // Plugin System
];
```

**No se toca `composer.json` ni se ejecuta `composer dump-autoload`.** El autoloader de CI4 mezcla en runtime los namespaces de Composer (`Autoloader::loadComposerNamespaces()`), pero el prefijo de los plugins vive mejor en `Config\Autoload`: es la fuente de verdad de namespaces de la app y no exige intervención por SSH en cada instalación, lo que preserva el modelo *drop-in* del Market.

### Autoload y descubrimiento de migraciones de plugins

El prefijo amplio resuelve **clases** pero **no descubre migraciones**: `FileLocator::listNamespaceFiles()` resuelve el namespace con `Autoloader::getNamespace()`, que es un lookup exacto de clave (`Plugins\ServerAdmin` no está en el mapa si lo registrado es `Plugins\`). Por eso:

1. El namespace exacto del plugin se registra **en runtime y solo en el flujo de instalación/actualización**, justo antes de correr sus migraciones:

```php
\Config\Services::autoloader()->addNamespace('Plugins\\' . $manifest['directory'], $pluginPath);
$runner = \Config\Services::migrations();
$runner->setNamespace('Plugins\\' . $manifest['directory']);
$runner->latest();
```

2. El `php spark migrate` global **no** recorre migraciones de plugins, porque `plugins/Database/Migrations/` no existe (las de cada plugin viven un nivel más abajo, dentro de su carpeta). Esta es la invariante de aislamiento que hay que conservar.

**Advertencia sobre `setGroup()`:** `group` es el grupo de conexión de base de datos, no una etiqueta libre. Si `groupFilter` no coincide con `$instance->getDBGroup()`, `MigrationRunner::migrate()` marca `groupSkip = true` y retorna `true` **sin ejecutar la migración**, aunque `addHistory()` la registre como aplicada. Nunca usar `setGroup($slug)` para etiquetar migraciones de un plugin.

---

## 17. Cambios Mínimos al Código Base (One-Time)

> **Estos 7 cambios se implementan UNA SOLA VEZ como parte de la implementación del sistema de plugins base. Después, cualquier plugin nuevo funciona sin tocar el ERP.** (v1.1: eran 5; se añaden el autoload y el catálogo de permisos.)

### Implementación (ver `agentic/plugin_000_plugin_system_core.md` v1.1)

```
Task: Implementar PluginSystem Core en el ERP
Archivos a crear (ver plugin_000 §3):
  app/Libraries/PluginSystem/{PluginInterface,BasePlugin,PluginManager,
      PluginBackupService,PluginHookRegistry,PluginMarket}.php
  app/Controllers/PluginSystem/PluginBaseController.php
  app/Controllers/Admin/PluginMarketController.php
  app/Views/Admin/plugins_market.php
  app/Views/PluginSystem/sidebar_section.php
  app/Database/Migrations/System/2026-09-18-20000{0..3}_*.php   (4 migraciones)
  app/Commands/PluginDoctor.php
  plugins/.gitkeep

Archivos a modificar (7 touch points, con su código en plugin_000 §4):
  1. app/Config/Autoload.php       → prefijo 'Plugins' (P0-3; sin Composer)
  2. app/Config/Events.php         → boot en pre_system, tolerante a fallos (P0-2b)
  3. app/Config/Routes.php         → loadRoutes($routes)
  4. loggedin_sidebar.php          → renderSidebar(auth()->user())
  5. user_loggedin_layout.php      → renderAssets() + doAction('layout.footer')
  6. AlertController.php           → runAlertDetectors($user) en _detectAll()
  7. UserController.php            → getAllPermissions() + 2 usos (P0-1)
```

---

## 18. Desarrollo en Paralelo

Cada plugin puede vivir en su propio repositorio git:

```bash
# Repositorio ERP (este)
git clone erp-repo.git

# Plugin en desarrollo local — como submódulo o carpeta independiente
git clone plugin-server-admin.git plugins/ServerAdmin

# En .gitmodules
[submodule "plugins/ServerAdmin"]
    path = plugins/ServerAdmin
    url = git@github.com:org/plugin-server-admin.git
```

### Ventajas del desarrollo paralelo

- El equipo de un plugin no necesita acceso al ERP base
- Los plugins tienen su propio `CHANGELOG.md`, `README.md` y versionado
- Un plugin puede desplegarse en múltiples instalaciones del ERP
- El plugin se prueba contra la interfaz `PluginInterface`, no contra el ERP completo
- CI/CD por separado para cada plugin

### Mock del PluginManager para tests de plugins

```php
// En tests del plugin — mockear PluginManager
class PluginTestCase extends \CodeIgniter\Test\CIUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // No cargar el sistema de plugins completo: registrar solo el plugin bajo prueba.
        // El contrato v1 (PluginInterface, helpers del ERP) se congela en plugin_000 §7.1
        // y PluginBaseController; los tests no bootean plugins ajenos.
        $this->plugin = new ServerAdmin(ROOTPATH . 'plugins/ServerAdmin');
        $this->plugin->registerHooks();
    }
}
```

---

## 19. Cumplimiento §1–§21

| § | Cómo el Plugin System lo garantiza |
|---|-----------------------------------|
| §1 | Los controladores de plugin extienden `App\Controllers\PluginSystem\PluginBaseController`, que aporta `assertAjaxPost()`, `jsonWithCsrf()`, `assertPermission()` y `$activityLogModel`. El `BaseController` del ERP **no** tiene esos helpers (hoy viven duplicados en las bases por módulo) |
| §2 | Los layouts del ERP ya tienen CSRF. Las vistas de plugins extienden el mismo `$layout` que incluye el token |
| §3 | Rutas de plugins con alias `'as' => 'plugin.slug.accion'`. Nunca `site_url()` en vistas de plugins |
| §4 | El sidebar de plugins se renderiza con `PluginManager::renderSidebar()` y sus ítems usan `url_is()` |
| §5/§16 | `DataTableTrait` está disponible para cualquier Modelo de plugin via `use \App\Traits\DataTableTrait` |
| §6 | Guard `isDataTable('#id')` obligatorio en JS de plugins |
| §7 | **CRÍTICO:** Todo endpoint de plugin lleva `'filter' => 'permission:plugin.perm'` (filtro nativo de Shield, sin alias propio). Los permisos se declaran en el manifiesto, se registran en `sys_plugin_permissions` y se integran al catálogo que consume la UI de usuarios (P0-1, §12) |
| §8 | **CRÍTICO:** Todo plugin llama `logActivity('plugin_action', '...')`. El modelo `UserActivityLogsModel` está disponible globalmente |
| §9 | Transacciones en Modelos del plugin, nunca en Controladores del plugin |
| §10 | Select2 con `dropdownParent` en modales de plugins |
| §11 | Fechas en ISO en DB, `d/m/Y` en UI, `<input type="date">` en forms |
| §12 | Solo FontAwesome en vistas de plugins (no Lucide) |
| §13 | Breadcrumbs usando `App\Libraries\Breadcrumb` con las clases del ERP |
| §14 | Vistas de plugins extienden `<?= $this->extend($layout) ?>` — el mismo layout del ERP |
| §15 | `$viewData` + `renderLayout()` para GET. `setOutputSuccess()`/`setOutputError()` + `jsonWithCsrf()` para AJAX |
| §16 | DataTableTrait con `$column_order` y `$column_search` en modelos del plugin |
| §17 | `notifyShow()` para feedback AJAX. Nunca `alert()` |
| §18 | Uploads en `FCPATH . 'uploads/plugins/{slug}/'` (públicos) o `WRITEPATH . 'uploads/plugins/{slug}/'` (privados) |
| §19 | Modelos de plugins: `$useSoftDeletes = true`, `$protectFields = true`, `$allowedFields` explícito |
| §20 | Catálogos estáticos en constantes del modelo del plugin si son fijos |
| §21 | Detectores de alertas siguen el formato `_buildAutoAlert()` y se registran via `PluginHookRegistry::addAction('alert.detect', ...)`. Es uno de los **dos hooks cableados en v1** (§6) |

---

*Documento de arquitectura v1.1 (2026-10-12), basado en la v1.0 del 2026-09-18. Especificación de implementación vigente del core: `agentic/plugin_000_plugin_system_core.md` (v1.1). Justificación de cada cambio: `agentic/AUDITORIA_PLUGIN_SYSTEM.md`. Base para los archivos individuales de cada plugin (`plugin_001_*.md` – `plugin_006_*.md`), que deberán ceñirse al contrato de hooks v1 (§6).*
