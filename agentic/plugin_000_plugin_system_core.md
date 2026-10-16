# Plugin 000 — Plugin System Core (Prerequisito) — v1.2

> **Tipo:** tarea del ERP base (no es un plugin) | **Versión:** 1.2.0 (2026-10-16; v1.1: 2026-10-12)
> **Referencia conceptual:** `ARQUITECTURA_PLUGIN_SYSTEM.md` (v1.1)
> **Origen de los cambios:** `AUDITORIA_PLUGIN_SYSTEM.md` (commit `7e1dbe5`)
> **Debe completarse ANTES de implementar cualquier plugin (001–006)**

> **AVISO IMPORTANTE.** Esta versión reemplaza a la v1.0. El código de las clases de `ARQUITECTURA_PLUGIN_SYSTEM.md §4` y `§5` quedó obsoleto: **la especificación de implementación vigente es este documento**. No implementar desde aquellos bloques; ver §7.

---

## 0. Registro de cambios v1.1 (7 parches aplicados)

| ID | Cambio | Sección |
|----|--------|---------|
| P0-1 | Permisos de plugin integrados al catálogo de Shield. Se elimina `PluginPermissionFilter` y el helper `plugin_can()` basado en consulta directa. | §4.7, §12 |
| P0-2 | Migraciones: registro del namespace exacto con `Autoloader::addNamespace()` + guarda contra el fallo silencioso. | §7.2 |
| P0-2b | Arranque tolerante (guarda `tableExists` + `try/catch` por plugin): sin esto `php spark migrate` no puede crear las tablas del propio sistema. | §7.4 |
| P0-3 | Autoload vía `Config\Autoload::$psr4`; se elimina la dependencia de `composer dump-autoload`. | §5, §4.1 |
| C-1 | `uninstall()` devuelve `array{success, backup_file, archived_tables}` en lugar de `bool`. | §7.1, §7.2, §7.4 |
| C-2 | Archivado, restauración y purga extraídos a `PluginBackupService` (sin métodos `protected` invocados desde fuera). | §7.3 |
| C-3 | Campo `directory` obligatorio en el manifiesto; `class_dir` guarda la carpeta, no el slug; `class_dir` en `sys_plugin_backups`. | §3, §6, §7.4 |
| C-4 | Limpieza del historial de migraciones del plugin al desinstalar. | §7.2, §7.4 |

**Consecuencias naturales de C-2** (el código se reescribió al extraerlo): el archivado usa `listTables()` en lugar de `LIKE` (el `_` es comodín), el `RENAME TABLE` es atómico en una sola sentencia y el registro del respaldo se escribe **antes** de renombrar. La purga ya no necesita los archivos del plugin.

**Corrección de coherencia A-5:** `sys_plugins.config` desaparece. La única fuente de verdad de la configuración es `sys_plugin_settings` (clave/valor), que es la que leen `getConfig()`/`setConfig()`.

**Pendientes que siguen fuera de alcance:** caché del listado de plugins (parte de A-6), `version_compare()` de `min_erp_version` (el ERP no publica una constante de versión), subida ZIP, mecanismo de tabs en offcanvas y hooks reservados (anexo B). No implementar los plugins 001–006.

## 0b. Correcciones v1.2 (obligatorias al codificar)

Estas reglas pisan cualquier bloque anterior de este archivo que las contradiga. El marco está en `ARQUITECTURA_PLUGIN_SYSTEM.md` v1.2.

1. **Arranque en CLI (V12-1).** `PluginManager::loadRoutes()` llama primero a `bootActivePlugins()`. El guard `$booted` hace la segunda llamada un no-op en la petición web, donde `pre_system` ya corrió. `php spark` no dispara `pre_system`.
2. **Runner propio (V12-2).** `runMigrations()` usa `\Config\Services::migrations(null, null, false)`, registra el namespace exacto, comprueba `findNamespaceMigrations()` y llama `latest()` sin argumentos. Nunca `setGroup($slug)`.
3. **Catálogo de permisos (V12-3).** `PluginManager::permissionCatalog(): array` fusiona `setting('AuthGroups.permissions')` con `sys_plugin_permissions` (con `tableExists`). `UserController::getAllPermissions()` solo delega ahí. El controlador no llama `db_connect()` (§9).
4. **Manifiesto (V12-4).** Antes de `new $class`:
   - `slug`: `^[a-z0-9]+(?:-[a-z0-9]+)*$`
   - `directory`: `^[A-Z][A-Za-z0-9]+$` y `realpath` de `plugins/{directory}` dentro de `realpath(ROOTPATH.'plugins')`
   - `class`: `Plugins\{directory}\{Nombre}` y el archivo de la clase, según `ReflectionClass`, dentro de esa carpeta
   - `table_prefix`: `^plg_[a-z0-9]+_$`
   - cada permiso: `^[a-z][a-z0-9]*(\.[a-z0-9][a-z0-9_-]*)+$`, ausente del catálogo del ERP, y sin un prefijo que `AuthGroups.matrix` ya conceda por comodín (`admin.*`, `hr.*`, `users.*`, etc.). Si no, el grupo recibiría el permiso del plugin sin asignarlo.
5. **SQL (V12-5).** Solo identificadores `[A-Za-z0-9_]`. El `DROP` y el `RENAME` de vuelta exigen el patrón `_bak_{prefijo}…_{YYYYmmdd}_{His}`. `backup_file` debe ser `plugins/backups/{slug}_{stamp}.sql` y su `realpath` debe quedar bajo `WRITEPATH/plugins/backups/`. Si `RENAME TABLE` lanza, se elimina la fila recién insertada y el `.sql`: la sentencia es atómica, así que no hay tablas a medio renombrar.
6. **Bus de hooks (V12-6).** `doAction`, `doActionCollect` y `applyFilter` envuelven cada callback en `try/catch` + `log_message('error')`. Un filtro que falla deja el valor anterior. No hay caché del listado.
7. **Assets y gitignore (V12-7).** `renderAssets()` sigue leyendo `FCPATH.'plugins/{slug}/'`. En web `FCPATH` es `public_html/`. Ignorar `public_html/plugins/*` y `writable/plugins/backups/*` (conservar `index.html`).
8. **Footer (V12-8).** El `doAction('layout.footer')` va después de `pageFooterScripts` y `scripts`, antes de `</html>`.
9. **Market en el sidebar (V12-9).** Ítem «Plugins» visible con `admin.manage-plugins`.
10. **CLI (V12-10).** `plugin:doctor` y `plugin:action {install|activate|deactivate|uninstall|restore|purge|update}`. `purge` y la desinstalación destructiva exigen el argumento `ELIMINAR` donde corresponda. `uninstall` sigue exigiendo status `inactive`.
11. **Sin ZIP y sin versión de ERP (V12-11).**
12. **Restore/purge (V12-12).** No restaurar si `restored_at` ya tiene valor o si el slug sigue en `sys_plugins`. Tras restaurar las tablas, se reponen en `migrations` las migraciones ya existentes del plugin: si no, la siguiente actualización intentaría crearlas otra vez. `purge()` borra `sys_plugin_settings` solo cuando no queda otro respaldo sin purgar de ese slug. El header de sección del sidebar solo se imprime si el ítem es visible para el usuario. El dump SQL se guarda con modo `0640`. Si el nombre del archivo ya existe en ese segundo, se espera al siguiente.
13. **Identidad y alertas (v1.2.1, auditoría Sonnet 5.5).** Un `slug` o `table_prefix` no puede repetirse en dos carpetas. `update()` rechaza un cambio de `table_prefix`; `sys_plugins.table_prefix` guarda el prefijo instalado y `uninstall` archiva con ese valor. Nombre de tabla viva ≤ 43 caracteres (`_bak_{tabla}_{Ymd_His}` cabe en MySQL 64). Un permiso no puede estar en otro `plugin_slug`. `runAlertDetectors()` solo entrega objetos (p. ej. `stdClass`); un array asociativo suelto no entra al historial. `purge()` se niega si `restored_at` está puesto. `deactivate`/`uninstall` se niegan si otro plugin activo lo lista en `requires`.

---

## 1. Objetivo

Implementar la infraestructura del sistema de plugins en el ERP base: clases, tablas y puntos de enganche para que cualquier plugin se instale, active, desinstale, restaure y purgue sin modificar el ERP.

**No hay lógica de negocio en esta tarea.** No implementar los plugins 001–006.

---

## 2. Contrato v1 congelado

| Pieza | Decisión |
|---|---|
| Autoload | Prefix `Plugins\` → `ROOTPATH . 'plugins'` en `app/Config/Autoload.php`. Sin Composer. |
| Manifiesto | `plugin.json` con `slug` (kebab, identidad), `directory` (PascalCase, carpeta real) y `settings` (nunca `settings_defaults`). |
| Configuración | `sys_plugin_settings` clave/valor. Sin columna `config`. |
| Permisos | Se registran en `sys_plugin_permissions` y se reflejan en el catálogo `AuthGroups.permissions` que consume la UI de usuarios. La asignación usa las tablas de Shield. Sin filtro propio. |
| Migraciones | Namespace exacto `Plugins\{Directory}` registrado en runtime solo en el flujo de instalación/actualización. Historial acotado por namespace y borrado al desinstalar. |
| Data safety | Nunca `DROP TABLE` en desinstalación: `RENAME` a `_bak_*` + dump SQL + fila en `sys_plugin_backups`. La purga es la única operación destructiva y exige confirmación textual. |
| Hooks v1 cableados | `alert.detect` (Collect) y `layout.footer` (Action). El resto del catálogo queda **reservado** y no debe usarse (ver Anexo de hooks en `ARQUITECTURA_PLUGIN_SYSTEM.md §6`). |
| Sidebar y assets | API del plugin: `getSidebarItems(User)` y `renderAssets()` guiado por manifiesto. No se despacha `sidebar.render` ni `layout.head` en v1. |
| Base de controladores | Los controladores de plugin extienden `App\Controllers\PluginSystem\PluginBaseController` (contrato estable, §8). |

---

## 3. Archivos nuevos a crear

```
app/Libraries/PluginSystem/
├── PluginInterface.php
├── BasePlugin.php
├── PluginManager.php
├── PluginBackupService.php
├── PluginHookRegistry.php
└── PluginMarket.php

app/Controllers/PluginSystem/
└── PluginBaseController.php      (base común del Market y de los controladores de plugin)

app/Controllers/Admin/
└── PluginMarketController.php

app/Views/Admin/
└── plugins_market.php

app/Views/PluginSystem/
└── sidebar_section.php

app/Database/Migrations/System/
├── 2026-09-18-200000_CreateSysPlugins.php
├── 2026-09-18-200001_CreateSysPluginPermissions.php
├── 2026-09-18-200002_CreateSysPluginSettings.php
└── 2026-09-18-200003_CreateSysPluginBackups.php

app/Commands/
└── PluginDoctor.php              (verificación CLI — §13)

plugins/
└── .gitkeep
```

---

## 4. Archivos del ERP a modificar (7 touch points, una sola vez)

### 4.1 `app/Config/Autoload.php` — autoload de plugins (P0-3)

```php
public $psr4 = [
    APP_NAMESPACE => APPPATH,
    'Config'      => APPPATH . 'Config',
    'Plugins'     => ROOTPATH . 'plugins',   // Plugin System
];
```

No tocar `composer.json` ni ejecutar `composer dump-autoload`. El autoloader de CI4 resuelve `Plugins\ServerAdmin\ServerAdmin` desde `plugins/ServerAdmin/ServerAdmin.php`, y las clases que aparezcan tras subir un ZIP se resuelven igual sin regenerar mapas.

> Invariante a documentar: este prefijo **no** expone migraciones (`plugins/Database/Migrations/` no existe). Por eso el namespace exacto se registra solo dentro del flujo de instalación/actualización, y el `php spark migrate` global sigue viendo únicamente las migraciones del ERP.

### 4.2 `app/Config/Events.php` — arranque de plugins

Al final del archivo:

```php
// ── Plugin System: carga de plugins activos en cada request ───────────────
Events::on('pre_system', static function () {
    if (class_exists(\App\Libraries\PluginSystem\PluginManager::class)) {
        \App\Libraries\PluginSystem\PluginManager::bootActivePlugins();
    }
});
```

### 4.3 `app/Config/Routes.php` — rutas de plugins

Al final del archivo, después del último grupo del ERP:

```php
// ── Plugin System: rutas de plugins activos ───────────────────────────────
if (class_exists(\App\Libraries\PluginSystem\PluginManager::class)) {
    \App\Libraries\PluginSystem\PluginManager::loadRoutes($routes);
}
```

> Las rutas de plugin se cargan **fuera** del grupo `nat` y de su grupo con `filter => session`. Cada plugin debe declarar en su `Config/Routes.php` el `namespace`, el `filter => session` y el `permission:` de cada ruta. `Config\Routing::$autoRoute` es `false`: no hay descubrimiento automático.

### 4.4 `app/Views/Partials/loggedin_sidebar.php` — sidebar de plugins

**Punto de inserción exacto:** después del cierre del bloque de Proveedores (`<?php endif; ?>` que sigue a `suppliers-nav`, línea ~138) y **antes** del header de demo `Apps` (línea ~140). No lo pongas al final del archivo: ahí viven las secciones de demostración de AppStack.

```php
<?= \App\Libraries\PluginSystem\PluginManager::renderSidebar(auth()->user()) ?>
```

### 4.5 `app/Views/Layouts/user_loggedin_layout.php` — assets y hooks

Antes de `</head>`:

```php
<?= \App\Libraries\PluginSystem\PluginManager::renderAssets() ?>
```

Antes de `</body>`:

```php
<?php \App\Libraries\PluginSystem\PluginHookRegistry::doAction('layout.footer'); ?>
```

### 4.6 `app/Controllers/System/AlertController.php` — detectores de plugins

Dentro de `_detectAll()`, justo antes del `return $alerts;` final:

```php
// ── Plugin System: detectores automáticos de plugins activos ──────────────
$alerts = array_merge($alerts, \App\Libraries\PluginSystem\PluginManager::runAlertDetectors($user));
```

### 4.7 `app/Controllers/Users/UserController.php` — catálogo de permisos (P0-1)

Añadir un helper privado y usarlo en los dos puntos donde hoy se lee el catálogo:

```php
/**
 * Permisos disponibles: los del ERP + los declarados por los plugins instalados.
 */
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

- En `permissions()` (línea ~851): sustituir `$allPermissions = setting('AuthGroups.permissions');` por `$allPermissions = $this->getAllPermissions();`.
- En `permissions_save()` (línea ~926): la misma sustitución. **Imprescindible**: `syncPermissions()` reemplaza el set completo, y sin este merge el guardado borraría los permisos de plugin del usuario.

---

## 5. Autoload y descubrimiento de migraciones (por qué así)

| Intento | Resultado |
|---|---|
| Composer `psr-4` + `dump-autoload` | Descartado: exige CLI en cada instalación y rompe el modelo drop-in del Market. |
| `setNamespace('Plugins\\X')` sin registrar nada | **Falla en silencio.** `FileLocator::listNamespaceFiles()` resuelve con `Autoloader::getNamespace()`, que es un lookup exacto de clave: el prefijo amplio `Plugins\` no cubre `Plugins\ServerAdmin`. Cero migraciones ⇒ `latest()` devuelve `true` ⇒ "instalado" sin tablas. |
| `Autoloader::addNamespace('Plugins\ServerAdmin', $pluginPath)` + `setNamespace(...)` + `latest()` | **Correcto.** El namespace exacto existe durante la vida del request, el descubrimiento funciona y el historial queda acotado por namespace. |
| `setPath()` / `setGroup($slug)` | No usar. `setPath()` no existe en CI4 4.5.3 (`force()` está documentado como *not recommended*). `group` es el **grupo de conexión de BD**: si no coincide, `migrate()` marca `groupSkip = true`, retorna `true` **sin ejecutar** y `addHistory()` la registra como aplicada. |

---

## 6. Migraciones del sistema de plugins

> Ejecutar las 4 migraciones con `php spark migrate` antes de usar el sistema. Ninguna toca tablas del ERP.

### `2026-09-18-200000_CreateSysPlugins.php`

Namespace: `App\Database\Migrations\System`. Sin columna `config` (P0-1/A-5: la configuración vive en `sys_plugin_settings`).

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
        'status'       => ['type' => 'ENUM', 'constraint' => ['active', 'inactive', 'installing', 'error'], 'default' => 'inactive'],
        'installed_at' => ['type' => 'DATETIME', 'null' => true],
        'updated_at'   => ['type' => 'DATETIME', 'null' => true],
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
$this->forge->addField([
    'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
    'plugin_slug' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
    'permission'  => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
    'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
]);
$this->forge->addKey('id', true);
$this->forge->addUniqueKey(['plugin_slug', 'permission']);
$this->forge->createTable('sys_plugin_permissions', true);
```

### `2026-09-18-200002_CreateSysPluginSettings.php`

Fuente única de verdad de la configuración de cada plugin.

> Nota: `key` es palabra reservada en MySQL. El query builder de CI4 la protege con backticks en `insert()`, `update()` y `where()`; si en algún punto se escribe SQL crudo, hay que escaparla.

```php
$this->forge->addField([
    'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
    'plugin_slug' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
    'key'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
    'value'       => ['type' => 'TEXT',    'null' => true],
    'updated_at'  => ['type' => 'DATETIME', 'null' => true],
]);
$this->forge->addKey('id', true);
$this->forge->addUniqueKey(['plugin_slug', 'key']);
$this->forge->createTable('sys_plugin_settings', true);
```

### `2026-09-18-200003_CreateSysPluginBackups.php`

Con `class_dir` (C-3): es lo que permite restaurar y purgar sin depender del slug.

```php
$this->forge->addField([
    'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
    'plugin_slug'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
    'plugin_name'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
    'plugin_version'   => ['type' => 'VARCHAR', 'constraint' => 20,  'null' => false],
    'class_dir'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
    'backup_file'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
    'archived_tables'  => ['type' => 'JSON',    'null' => true],
    'backup_size_kb'   => ['type' => 'INT',     'null' => true],
    'uninstalled_at'   => ['type' => 'DATETIME', 'null' => false],
    'uninstalled_by'   => ['type' => 'INT', 'null' => false, 'default' => 0],
    'purged_at'        => ['type' => 'DATETIME', 'null' => true],
    'purged_by'        => ['type' => 'INT', 'null' => true],
    'restored_at'      => ['type' => 'DATETIME', 'null' => true],
    'restored_by'      => ['type' => 'INT', 'null' => true],
    'notes'            => ['type' => 'TEXT', 'null' => true],
]);
$this->forge->addKey('id', true);
$this->forge->addKey('plugin_slug');
$this->forge->createTable('sys_plugin_backups', true);
```

---

## 7. Clases del core (código de referencia vigente)

### 7.1 `PluginInterface.php`

```php
<?php

namespace App\Libraries\PluginSystem;

use CodeIgniter\Shield\Entities\User;

interface PluginInterface
{
    /** Ejecutado una vez al instalar. Corre migraciones y registra permisos. */
    public function install(): bool;

    /**
     * Ejecutado una vez al desinstalar (el plugin debe estar INACTIVE).
     * Archiva los datos y limpia permisos e historial de migraciones.
     *
     * @return array{success: bool, backup_id: ?int, backup_file: ?string, archived_tables: array}
     */
    public function uninstall(): array;

    /** Registra hooks. Se llama en cada request cuando el plugin está activo. */
    public function activate(): void;

    public function deactivate(): void;

    public function registerHooks(): void;

    public function getSidebarItems(User $user): array;

    public function loadRoutes($routes): void;

    public function getConfig(): array;

    public function setConfig(array $config): bool;

    public function getManifest(): array;
}
```

### 7.2 `BasePlugin.php`

```php
<?php

namespace App\Libraries\PluginSystem;

use CodeIgniter\Shield\Entities\User;
use RuntimeException;

abstract class BasePlugin implements PluginInterface
{
    protected array $manifest = [];
    protected ?array $config = null;
    protected string $pluginPath;

    public function __construct(string $pluginPath)
    {
        $this->pluginPath = rtrim($pluginPath, '/\\') . DIRECTORY_SEPARATOR;
        $manifestFile     = $this->pluginPath . 'plugin.json';

        if (! is_file($manifestFile)) {
            throw new RuntimeException("plugin.json no encontrado en {$this->pluginPath}");
        }

        $manifest = json_decode((string) file_get_contents($manifestFile), true);

        if (! is_array($manifest) || empty($manifest['slug']) || empty($manifest['class'])) {
            throw new RuntimeException("plugin.json inválido en {$this->pluginPath}");
        }

        $this->manifest = $manifest;
    }

    public function getManifest(): array
    {
        return $this->manifest;
    }

    public function getSlug(): string
    {
        return $this->manifest['slug'];
    }

    /** Carpeta real del plugin: PascalCase, distinta del slug (C-3). */
    public function getDirectory(): string
    {
        return $this->manifest['directory'] ?? basename(rtrim($this->pluginPath, DIRECTORY_SEPARATOR));
    }

    /** Namespace real del plugin, usado para descubrir y limpiar migraciones. */
    public function getNamespace(): string
    {
        return 'Plugins\\' . $this->getDirectory();
    }

    // ── Configuración: sys_plugin_settings es la única fuente de verdad ─────

    public function getConfig(): array
    {
        if ($this->config !== null) {
            return $this->config;
        }

        $settings = [];
        foreach (($this->manifest['settings'] ?? []) as $key => $definition) {
            $settings[$key] = $definition['default'] ?? null;
        }

        $rows = db_connect()->table('sys_plugin_settings')
            ->where('plugin_slug', $this->getSlug())
            ->get()->getResultArray();

        foreach ($rows as $row) {
            $decoded = json_decode((string) $row['value'], true);
            $settings[$row['key']] = is_array($decoded) ? $decoded : $row['value'];
        }

        return $this->config = $settings;
    }

    public function setConfig(array $config): bool
    {
        $db = db_connect();

        foreach ($config as $key => $value) {
            $stored = is_scalar($value) || $value === null
                ? (string) $value
                : json_encode($value);

            $exists = $db->table('sys_plugin_settings')
                ->where(['plugin_slug' => $this->getSlug(), 'key' => $key])
                ->countAllResults() > 0;

            if ($exists) {
                $db->table('sys_plugin_settings')
                    ->where(['plugin_slug' => $this->getSlug(), 'key' => $key])
                    ->update(['value' => $stored, 'updated_at' => date('Y-m-d H:i:s')]);
            } else {
                $db->table('sys_plugin_settings')->insert([
                    'plugin_slug' => $this->getSlug(),
                    'key'         => $key,
                    'value'       => $stored,
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->config = null;   // invalidar caché local

        return true;
    }

    // ── Migraciones (P0-2) ──────────────────────────────────────────────────

    protected function runMigrations(): bool
    {
        $migrationsPath = $this->pluginPath . 'Database/Migrations';

        if (! is_dir($migrationsPath)) {
            return true;
        }

        $namespace = $this->getNamespace();

        // FileLocator::listNamespaceFiles() resuelve con Autoloader::getNamespace(),
        // que es un lookup EXACTO de clave: el prefijo amplio 'Plugins\' no basta.
        \Config\Services::autoloader()->addNamespace($namespace, $this->pluginPath);

        $runner = \Config\Services::migrations();
        $runner->setNamespace($namespace);

        // Guarda contra el fallo silencioso: si hay archivos y no se descubren, error.
        if ($runner->findNamespaceMigrations($namespace) === []) {
            log_message('error', '[PluginSystem] {slug}: migraciones en disco no descubiertas.', [
                'slug' => $this->getSlug(),
            ]);

            return false;
        }

        return $runner->latest() !== false;
    }

    /** Borra el historial de migraciones de ESTE plugin (C-4). */
    protected function removeMigrationHistory(): void
    {
        db_connect()->table('migrations')
            ->where('namespace', $this->getNamespace())
            ->delete();
    }

    // ── Ciclo de vida por defecto ───────────────────────────────────────────

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
        // 1. Archivar datos: RENAME atómico a _bak_* + dump SQL + registro. Nunca DROP.
        $backup = PluginBackupService::archive($this->manifest, $this->pluginPath);

        // 2. Permisos y estado.
        PluginManager::removePermissions($this->getSlug());

        // 3. Historial de migraciones: sin esto, reinstalar no recrearía las tablas.
        $this->removeMigrationHistory();

        return ['success' => true] + $backup;
    }

    public function activate(): void
    {
        $this->registerHooks();
    }

    public function deactivate(): void
    {
        // Los hooks dejan de registrarse al no bootearse el plugin.
    }

    public function getSidebarItems(User $user): array
    {
        return [];
    }

    public function loadRoutes($routes): void
    {
        // Override en cada plugin: require de Config/Routes.php y callback.
    }
}
```

### 7.3 `PluginBackupService.php` (C-2)

```php
<?php

namespace App\Libraries\PluginSystem;

/**
 * Archivado, restauración y purga de datos de plugins.
 * Opera con sys_plugin_backups y NO necesita instanciar la clase del plugin:
 * la purga funciona aunque los archivos ya no existan.
 */
class PluginBackupService
{
    /** @return array{backup_id: ?int, backup_file: ?string, archived_tables: array} */
    public static function archive(array $manifest, string $pluginPath): array
    {
        $db     = db_connect();
        $slug   = $manifest['slug'];
        $prefix = $manifest['table_prefix'];
        $stamp  = date('Ymd_His');

        // 1. Tablas del plugin. str_starts_with, no LIKE: '_' es comodín en LIKE.
        $tables = [];
        foreach ($db->listTables() as $table) {
            if (str_starts_with($table, $prefix)) {
                $tables[] = $table;
            }
        }

        $backupDir = WRITEPATH . 'plugins/backups/';
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $backupFile = $backupDir . "{$slug}_{$stamp}.sql";
        $archived   = [];
        foreach ($tables as $table) {
            $archived[] = '_bak_' . $table . '_' . $stamp;
        }

        // 2. Registrar ANTES de renombrar: si el proceso muere a mitad, el
        //    registro permite localizar y recuperar las tablas huérfanas.
        $db->table('sys_plugin_backups')->insert([
            'plugin_slug'     => $slug,
            'plugin_name'     => $manifest['name'],
            'plugin_version'  => $manifest['version'],
            'class_dir'       => $manifest['directory'],
            'backup_file'     => $tables === [] ? null : 'plugins/backups/' . basename($backupFile),
            'archived_tables' => json_encode($archived),
            'backup_size_kb'  => 0,
            'uninstalled_at'  => date('Y-m-d H:i:s'),
            'uninstalled_by'  => auth()->id() ?? 0,
        ]);
        $backupId = (int) $db->insertID();

        // 3. Dump SQL (estructura + datos) como respaldo secundario.
        if ($tables !== []) {
            file_put_contents($backupFile, self::dumpSql($db, $tables, $manifest));
            $db->table('sys_plugin_backups')->where('id', $backupId)
               ->update(['backup_size_kb' => (int) round(filesize($backupFile) / 1024)]);
        }

        // 4. RENAME atómico: MySQL renombra todo el grupo en una sola sentencia.
        if ($tables !== []) {
            $parts = [];
            foreach ($tables as $i => $table) {
                $parts[] = "`{$table}` TO `{$archived[$i]}`";
            }
            $db->query('RENAME TABLE ' . implode(', ', $parts));
        }

        return [
            'backup_id'       => $backupId,
            'backup_file'     => $tables === [] ? null : $backupFile,
            'archived_tables' => $archived,
        ];
    }

    /**
     * Restaura las tablas _bak_* de un respaldo y reinserta el plugin como INACTIVE.
     * Nunca lo deja activo: lo activa un humano tras verificar.
     */
    public static function restore(int $backupId): array
    {
        $db     = db_connect();
        $backup = $db->table('sys_plugin_backups')->where('id', $backupId)->get()->getRowArray();

        if (! $backup) {
            return ['success' => false, 'message' => 'Respaldo no encontrado.'];
        }

        if (! empty($backup['purged_at'])) {
            return ['success' => false, 'message' => 'Este respaldo ya fue purgado.'];
        }

        $pluginPath   = ROOTPATH . 'plugins/' . $backup['class_dir'];
        $manifestFile = $pluginPath . '/plugin.json';

        if (! is_file($manifestFile)) {
            return ['success' => false, 'message' => 'Faltan los archivos del plugin. Súbelos antes de restaurar.'];
        }

        $manifest = json_decode((string) file_get_contents($manifestFile), true);
        $archived = json_decode((string) ($backup['archived_tables'] ?? '[]'), true) ?: [];

        // RENAME de vuelta, en una sola sentencia.
        $parts = [];
        foreach ($archived as $name) {
            if (preg_match('/^_bak_(.*)_\d{8}_\d{6}$/', $name, $m)
                && str_starts_with($m[1], $manifest['table_prefix'] ?? '')) {
                $parts[] = "`{$name}` TO `{$m[1]}`";
            }
        }

        if ($parts !== []) {
            $db->query('RENAME TABLE ' . implode(', ', $parts));
        }

        if (! $db->table('sys_plugins')->where('slug', $manifest['slug'])->countAllResults()) {
            $db->table('sys_plugins')->insert([
                'slug'         => $manifest['slug'],
                'name'         => $manifest['name'],
                'version'      => $manifest['version'],
                'main_class'   => $manifest['class'],
                'class_dir'    => $backup['class_dir'],
                'description'  => $manifest['description'] ?? '',
                'icon'         => $manifest['icon'] ?? 'fa-plug',
                'status'       => 'inactive',
                'installed_at' => date('Y-m-d H:i:s'),
            ]);
        }

        PluginManager::registerPermissions($manifest);

        $db->table('sys_plugin_backups')->where('id', $backupId)->update([
            'restored_at' => date('Y-m-d H:i:s'),
            'restored_by' => auth()->id() ?? 0,
        ]);

        return ['success' => true, 'message' => "Plugin '{$manifest['name']}' restaurado. Actívalo para usarlo."];
    }

    /** Purga definitiva: DROP de las tablas _bak_* + borrado del SQL. IRREVERSIBLE. */
    public static function purge(int $backupId): array
    {
        $db     = db_connect();
        $backup = $db->table('sys_plugin_backups')->where('id', $backupId)->get()->getRowArray();

        if (! $backup) {
            return ['success' => false, 'message' => 'Respaldo no encontrado.'];
        }

        if ($db->table('sys_plugins')->where('slug', $backup['plugin_slug'])->countAllResults()) {
            return ['success' => false, 'message' => 'No puedes purgar un plugin que está instalado.'];
        }

        foreach (json_decode((string) ($backup['archived_tables'] ?? '[]'), true) ?: [] as $table) {
            $db->query("DROP TABLE IF EXISTS `{$table}`");
        }

        $sqlFile = WRITEPATH . ($backup['backup_file'] ?? '');
        if (! empty($backup['backup_file']) && is_file($sqlFile)) {
            unlink($sqlFile);
        }

        $db->table('sys_plugin_backups')->where('id', $backupId)->update([
            'purged_at'  => date('Y-m-d H:i:s'),
            'purged_by'  => auth()->id() ?? 0,
            'notes'      => trim(($backup['notes'] ?? '') . "\nDatos purgados definitivamente."),
        ]);

        return ['success' => true, 'message' => "Datos del plugin '{$backup['plugin_name']}' eliminados definitivamente."];
    }

    /** Dump SQL de estructura + datos de las tablas indicadas. */
    private static function dumpSql($db, array $tables, array $manifest): string
    {
        $sql  = "-- Plugin backup: {$manifest['slug']} | " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Plugin version: {$manifest['version']}\n\n";

        foreach ($tables as $table) {
            $create = $db->query("SHOW CREATE TABLE `{$table}`")->getRowArray();
            $sql   .= array_values($create)[1] . ";\n\n";

            $rows = $db->query("SELECT * FROM `{$table}`")->getResultArray();
            if ($rows === []) {
                continue;
            }

            $columns = '`' . implode('`, `', array_keys($rows[0])) . '`';
            $values  = [];
            foreach ($rows as $row) {
                $cells = array_map(
                    static fn ($value) => $value === null ? 'NULL' : $db->escape($value),
                    $row
                );
                $values[] = '(' . implode(', ', $cells) . ')';
            }

            $sql .= "INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $values) . ";\n\n";
        }

        return $sql;
    }
}
```

### 7.4 `PluginManager.php`

Métodos y reglas (implementar con el código de `§7.3` como referencia de estilo):

| Método | Regla |
|---|---|
| `bootActivePlugins(): void` | Guarda `self::$booted`. `tableExists('sys_plugins')` antes de consultar (P0-2b). Por cada fila: `try/catch` → log y `continue` si el plugin no es cargable. Instancia `new $row['main_class'](ROOTPATH . 'plugins/' . $row['class_dir'])` y llama `registerHooks()`. Nunca propaga una excepción al ERP. Sin caché en v1 (pendiente A-6). |
| `loadRoutes($routes): void` | Llama `bootActivePlugins()` y luego itera `self::$loadedPlugins`. Cada `loadRoutes()` del plugin va en `try/catch` (V12-1). |
| `renderSidebar(?User $user): string` | `null` safe. Itera plugins, `getSidebarItems($user)` y renderiza `PluginSystem/sidebar_section`. |
| `renderAssets(): string` | Por cada plugin activo: `FCPATH . "plugins/{$slug}/css/plugin.css"` y `js/plugin.js` con `?v={version}` del manifiesto. |
| `runAlertDetectors(User $user): array` | `PluginHookRegistry::doActionCollect('alert.detect', $user)`. |
| `install(string $slug): array` | Validar `plugin.json`, `directory` existente y `class` instanciable; `new $class($pluginPath)`; `install()`; insertar en `sys_plugins` con `class_dir = $manifest['directory']`, `status = 'active'`, sin `config`. Si existe un respaldo pendiente para ese slug, no instalar: devolver mensaje que ofrezca **Restaurar**. |
| `uninstall(string $slug): array` | Exigir `status = 'inactive'`. Llamar `$instance->uninstall()` (devuelve array) y luego borrar la fila de `sys_plugins`. |
| `registerPermissions(array $manifest): void` | Solo BD: insertar/actualizar en `sys_plugin_permissions` desde `$manifest['permissions']`. Sin instanciar el plugin. |
| `removePermissions(string $slug): void` | Borrar las filas de ese slug en `sys_plugin_permissions`. |
| `restore(int $backupId): array` | Delegar en `PluginBackupService::restore()`. |
| `purge(int $backupId): array` | Delegar en `PluginBackupService::purge()`. |
| `activate(string $slug): bool` / `deactivate(string $slug): bool` | Actualizar `status`. |
| `update(string $slug): array` | Localizar carpeta por `class_dir`, `new $class($pluginPath)`, `install()` (corre solo migraciones nuevas), actualizar `version` y `updated_at` en `sys_plugins`. |
| `isActive(string $slug): bool` / `getLoaded(): array` | Consultas sobre `self::$loadedPlugins`. |

### 7.5 `PluginHookRegistry.php`

Contrato completo de la clase: `addAction`, `doAction`, `doActionCollect`, `addFilter`, `applyFilter`, todos con prioridad por defecto 10 y ordenadas con `ksort`.

> **v1.2 (V12-6):** cada callback va en `try/catch`. La caché del listado sigue fuera de alcance.

### 7.6 `PluginMarket.php`

Sin cambios: `getCatalog()` (local por `plugins/*/plugin.json` + remoto si `PLUGIN_MARKET_URL`), `getInstalled()`, `getAvailable()`, `scanLocalPlugins()`.

> Al leer un manifiesto, validar que `slug`, `directory` y `class` existen, y que `directory` coincide con la carpeta real. Un manifiesto inválido se muestra como no instalable, nunca se instala a medias.

---

## 8. `PluginBaseController`

Los controladores de plugin (`Plugins\X\Controllers\...`) y el Market necesitan helpers que **no** existen en `app/Controllers/BaseController.php` (hoy viven duplicados en `BaseHrController`, `BaseFinancialController`, `BaseOrganizationController` y `UserController`).

**Crear `app/Controllers/PluginSystem/PluginBaseController.php`** (extiende `BaseController`) con:

| Miembro | Comportamiento |
|---|---|
| `protected UserActivityLogsModel $activityLogModel` | Instanciado en `initController()`. |
| `protected function assertAjaxPost(): void` | Si no es AJAX+POST → `PageNotFoundException`, igual que las bases por módulo. |
| `protected function jsonWithCsrf(array $payload = []): ResponseInterface` | Añade `csrf => csrf_hash()` si falta y devuelve JSON. |
| `protected function assertPermission(string $permission): void` | `auth()->user()->can($permission)` o 403 (§7). |

Este archivo es **contrato congelado**: los plugins 001–006 dependen de él. Cualquier cambio futuro debe ser retrocompatible y registrarse en `§2`.

---

## 9. `PluginMarketController` y vista `plugins_market.php`

Controlador: `app/Controllers/Admin/PluginMarketController.php` extends `PluginBaseController`.
Rutas: ver §11. Permiso único: `admin.manage-plugins`.

| Método | HTTP | Alias | Regla clave |
|---|---|---|---|
| `index()` | GET | `admin.plugins.index` | Instalados + disponibles + respaldos. `renderLayout()`. |
| `install()` | POST | `admin.plugins.install` | `assertAjaxPost()`. Si hay respaldo pendiente → ofrecer restaurar. |
| `uninstall()` | POST | `admin.plugins.uninstall` | Requiere `status = 'inactive'`; nunca `DROP`. |
| `activate()` / `deactivate()` | POST | `admin.plugins.activate` / `.deactivate` | Cambian `status` y registran bitácora. |
| `restore()` | POST | `admin.plugins.restore` | Devuelve el plugin a `INACTIVE`. |
| `purge()` | POST | `admin.plugins.purge` | Requiere `confirmation === 'ELIMINAR'`. IRREVERSIBLE. |
| `update()` | POST | `admin.plugins.update` | Corre solo migraciones nuevas y actualiza versión. |
| `settings(slug)` / `settings_save(slug)` | GET/POST | `admin.plugins.settings[.save]` | Lee/escribe vía `getConfig()`/`setConfig()` sobre `sys_plugin_settings`. |

Reglas de la vista (§2, §13, §14, §17): input CSRF al inicio del `<main>`, breadcrumb Inicio > Administrador > Plugins, cards Bootstrap con icono FontAwesome, modales de confirmación (nunca `confirm()` nativo), `notifyShow()` para feedback, recarga tras operaciones correctas, `Purga` con campo de texto `ELIMINAR` y botón deshabilitado hasta que coincida.

---

## 10. Vista parcial `sidebar_section.php`

Contrato del partial (lo renderiza `PluginManager::renderSidebar()` una vez por plugin con ítems):

```php
// $items = [ [
//     'section'       => 'Herramientas',            // header opcional
//     'slug'          => 'server-admin',            // id estable para el collapse
//     'label'         => 'Servidores',
//     'icon'          => 'fa-server',               // FontAwesome sin 'fas' (§12)
//     'permission'    => 'servers.access',
//     'route'         => 'servers.index',           // alias de route_to()
//     'route_pattern' => 'nat/plugins/servers*',    // patrón para url_is()
//     'children'      => [ ['label' => ..., 'route' => ..., 'permission' => ..., 'route_pattern' => ...] ],
// ], ... ]
```

Reglas obligatorias:

- Permiso con la API nativa (`$user->can(...)`); nunca consultas a `auth_permissions_users`.
- Ítems e hijos sin permiso se omiten; si un padre se queda sin hijos visibles, se omite el padre completo.
- `route_to()` para los href y `url_is()` con `route_pattern` para el estado activo (§3, §4).
- Iconos FontAwesome (`<i class="fas fa-fw ...">`), norma §12; no Lucide.
- Estructura Bootstrap idéntica al sidebar del ERP, con `data-bs-parent="#sidebar"`.

```php
<?php
/** @var array $items */
$user = auth()->user();
if ($user === null) {
    return;
}
?>
<?php foreach ($items as $item): ?>
    <?php if (!empty($item['section'])): ?>
        <li class="sidebar-header"><?= esc($item['section']) ?></li>
    <?php endif; ?>

    <?php if (empty($item['permission']) || ! $user->can($item['permission'])): continue; endif; ?>

    <?php $id = 'plg-nav-' . esc($item['slug'] ?? md5((string) $item['route']), 'attr'); ?>
    <?php $isActive = !empty($item['route_pattern']) && url_is($item['route_pattern']); ?>

    <?php if (!empty($item['children'])): ?>
        <?php
        $children = array_filter(
            $item['children'],
            static fn ($child) => ! empty($child['permission']) && auth()->user()->can($child['permission'])
        );
        ?>
        <?php if ($children === []): continue; endif; ?>
        <li class="sidebar-item <?= $isActive ? 'active' : '' ?>">
            <a data-bs-target="#<?= $id ?>" data-bs-toggle="collapse"
               class="sidebar-link <?= $isActive ? '' : 'collapsed' ?>">
                <i class="fas fa-fw <?= esc($item['icon'] ?? 'fa-plug') ?> align-middle me-1"></i>
                <span class="align-middle"><?= esc($item['label']) ?></span>
            </a>
            <ul id="<?= $id ?>" class="sidebar-dropdown list-unstyled collapse <?= $isActive ? 'show' : '' ?>"
                data-bs-parent="#sidebar">
                <?php foreach ($children as $child): ?>
                    <li class="sidebar-item <?= (! empty($child['route_pattern']) && url_is($child['route_pattern'])) ? 'active' : '' ?>">
                        <a class="sidebar-link" href="<?= route_to($child['route']) ?>"><?= esc($child['label']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </li>
    <?php else: ?>
        <li class="sidebar-item <?= $isActive ? 'active' : '' ?>">
            <a class="sidebar-link" href="<?= route_to($item['route']) ?>">
                <i class="fas fa-fw <?= esc($item['icon'] ?? 'fa-plug') ?> align-middle me-1"></i>
                <span class="align-middle"><?= esc($item['label']) ?></span>
            </a>
        </li>
    <?php endif; ?>
<?php endforeach; ?>
```

---

## 11. Rutas

### Market (dentro del grupo existente `nat` → `session`, o declarando el filtro)

```php
$routes->group('nat/admin/plugins', [
    'filter'    => 'session',
    'namespace' => 'App\Controllers\Admin',
], static function ($routes) {
    $routes->get('/', 'PluginMarketController::index', ['as' => 'admin.plugins.index', 'filter' => 'permission:admin.manage-plugins']);
    $routes->post('install', 'PluginMarketController::install', ['as' => 'admin.plugins.install', 'filter' => 'permission:admin.manage-plugins']);
    $routes->post('uninstall', 'PluginMarketController::uninstall', ['as' => 'admin.plugins.uninstall', 'filter' => 'permission:admin.manage-plugins']);
    $routes->post('activate', 'PluginMarketController::activate', ['as' => 'admin.plugins.activate', 'filter' => 'permission:admin.manage-plugins']);
    $routes->post('deactivate', 'PluginMarketController::deactivate', ['as' => 'admin.plugins.deactivate', 'filter' => 'permission:admin.manage-plugins']);
    $routes->post('restore', 'PluginMarketController::restore', ['as' => 'admin.plugins.restore', 'filter' => 'permission:admin.manage-plugins']);
    $routes->post('purge', 'PluginMarketController::purge', ['as' => 'admin.plugins.purge', 'filter' => 'permission:admin.manage-plugins']);
    $routes->post('update', 'PluginMarketController::update', ['as' => 'admin.plugins.update', 'filter' => 'permission:admin.manage-plugins']);
    $routes->get('settings/(:segment)', 'PluginMarketController::settings/$1', ['as' => 'admin.plugins.settings', 'filter' => 'permission:admin.manage-plugins']);
    $routes->post('settings/(:segment)/save', 'PluginMarketController::settings_save/$1', ['as' => 'admin.plugins.settings.save', 'filter' => 'permission:admin.manage-plugins']);
});
```

### Plantilla de rutas de un plugin (referencia para 001–006)

```php
return static function ($routes) {
    $routes->group('nat/plugins/servers', [
        'namespace' => 'Plugins\ServerAdmin\Controllers',
        'filter'    => 'session',
    ], static function ($routes) {
        $routes->get('/', 'ServerController::index', ['as' => 'servers.index', 'filter' => 'permission:servers.access']);
        // ...
    });
};
```

---

## 12. Permiso `admin.manage-plugins` (P0-1)

En `app/Config/AuthGroups.php`, añadir al array `$permissions`:

```php
'admin.manage-plugins' => 'Instalar, desinstalar y configurar plugins del sistema',
```

No hace falta tocar `$matrix`: los grupos `superadmin` y `admin` tienen los comodines `superadmin.*` y `admin.*`, y `Authorizable::can()` resuelve comodines contra `setting('AuthGroups.matrix')`.

**Cómo funcionan los permisos de un plugin (contrato v1):**

1. El manifiesto declara `permissions` (`servers.access`, etc.).
2. Al instalar/restaurar, `PluginManager::registerPermissions()` los inserta en `sys_plugin_permissions` (registro y auditoría).
3. `UserController::getAllPermissions()` (§4.7) los suma al catálogo que ve la UI de usuarios.
4. El administrador los asigna a un usuario; Shield los guarda en `auth_permissions_users` y el guardado (`syncPermissions`) ya no los borra porque existen en el catálogo.
5. La autorización usa Shield sin intermediarios: el filtro `permission:` en rutas, `auth()->user()->can(...)` en vistas y controladores, y los detectores de alertas.
6. Al desinstalar, `removePermissions()` los retira del registro; las asignaciones huérfanas en `auth_permissions_users` son inertes y pueden limpiarse en una tarea posterior (pendiente, no bloqueante).

**No se crea** `PluginPermissionFilter` ni un helper `plugin_can()` paralelo.

---

## 13. Verificación CLI — `php spark plugin:doctor`

El sandbox devuelve **401 Basic Auth** antes del login de Shield (DT-5), así que el Market no puede verificarse en navegador. El comando es obligatorio para cerrar esta tarea.

`app/Commands/PluginDoctor.php` (firma `plugin:doctor`) imprime y devuelve código de salida distinto de cero si algo falla:

1. Las 4 tablas `sys_*` existen y tienen las columnas nuevas (`sys_plugins.class_dir`, `sys_plugin_backups.class_dir`).
2. Cada fila de `sys_plugins` tiene `class_dir` que apunta a una carpeta con `plugin.json`, y `main_class` autoloadable.
3. Cada `plugin.json` en disco tiene `slug`/`directory`/`class` y `directory` coincide con la carpeta.
4. `sys_plugin_permissions` contiene exactamente los permisos declarados por los manifiestos instalados (diferencia reportada).
5. Los hooks declarados en los manifiestos pertenecen al contrato v1 (`alert.detect`, `layout.footer`); cualquier otro se reporta como reservado/no cableado.
6. Migraciones pendientes por plugin (namespace registrado + `findNamespaceMigrations()` > 0 cuando hay carpeta `Database/Migrations`).

Ejemplo de salida:

```
Plugin System — doctor
  [OK]    Tablas sys_plugins, sys_plugin_permissions, sys_plugin_settings, sys_plugin_backups
  [OK]    0 plugins instalados
  [OK]    0 manifiestos en disco
  [OK]    Permisos registrados coherentes
  [WARN]  Hooks v1 disponibles: alert.detect, layout.footer
  0 errores, 0 advertencias
```

---

## 14. Cumplimiento §1–§21

| § | Aplicación |
|---|-----------|
| §1 | `assertAjaxPost()` en todos los POST del Market (heredado de `PluginBaseController`). |
| §2 | CSRF en la vista y en `fetch()` + `jsonWithCsrf()`. |
| §3 | Rutas con alias `admin.plugins.*`; `route_to()` en vistas. |
| §4 | `url_is('nat/admin/plugins*')` en el sidebar. |
| §5/§16 | No aplica (el Market no usa DataTables). |
| §6 | No aplica. |
| §7 | `permission:admin.manage-plugins` en todas las rutas del Market; permisos de plugin vía Shield (§12). |
| §8 | `logActivity` en install, uninstall, activate, deactivate, restore, purge, update. |
| §9 | Sin transacciones en controladores; la escritura de settings vive en `BasePlugin`. |
| §10 | No aplica. |
| §11 | No aplica. |
| §12 | Solo FontAwesome. |
| §13 | Breadcrumb Inicio > Administrador > Plugins. |
| §14 | `plugins_market.php` extiende `$layout`. |
| §15 | `renderLayout()` en GET; `jsonWithCsrf()` en POST. |
| §17 | `notifyShow()` en todo el feedback AJAX. |
| §19 | `sys_*` sin soft-delete (tablas de registro). |
| §21 | El core solo recolecta detectores; no define detectores propios. |

---

## 15. Orden de implementación y criterios de aceptación

```
1. Config/Autoload.php → prefijo Plugins (§4.1)
2. Clases del core: Interface, BasePlugin, PluginBackupService, PluginManager,
   PluginHookRegistry, PluginMarket (§7)
3. PluginBaseController (§8)
4. 4 migraciones → php spark migrate (§6)
5. PluginMarketController + plugins_market.php (§9)
6. sidebar_section.php (§10)
7. Rutas del Market + permiso admin.manage-plugins (§11, §12)
8. Los 7 touch points restantes: Events, Routes, sidebar, layout, AlertController,
   UserController.getAllPermissions() (§4.2–§4.7)
9. Comando plugin:doctor (§13)
10. php spark migrate && php spark plugin:doctor && php spark routes | grep plugins
```

**Criterios de aceptación (todos CLI, sin navegador):**

- `php spark migrate` crea las 4 tablas `sys_*` y no falla en un checkout sin plugins.
- `php spark plugin:doctor` sale con 0 errores.
- Un plugin fixture (`plugins/Fixture/` con `plugin.json` + una migración que cree `plg_fixture_items`) se instala desde CLI, crea su tabla real y aparece en `sys_plugins` con `class_dir = 'Fixture'` y `status = 'active'`.
- Ciclo completo sin errores fatales: activar → desactivar → desinstalar (verificar tablas `_bak_*`, fila en `sys_plugin_backups` y **ausencia** de filas en `migrations` para `Plugins\Fixture`) → restaurar → purgar (verificar `DROP` y limpieza del `.sql`).
- Reinstalar tras desinstalar no deja el plugin en estado "sin tablas".
- Un plugin con error fatal en `registerHooks()` no impide cargar el ERP ni el Market.
- `php -l` limpio en todos los archivos nuevos.

---

## 16. Prompt para el agente implementador

```
Actúa como desarrollador experto en CodeIgniter 4. Implementa EXACTAMENTE la
infraestructura descrita en @agentic/plugin_000_plugin_system_core.md (v1.2).
Si un bloque de §7 contradice §0b, prevalece §0b.

Contexto obligatorio (leer antes de escribir código):
- @agentic/plugin_000_plugin_system_core.md  ← spec vigente, autocontenida
- @agentic/ARQUITECTURA_PLUGIN_SYSTEM.md     ← marco conceptual (§4 y §5 están
                                               obsoletos: prevalece la spec v1.1)
- @agentic/AUDITORIA_PLUGIN_SYSTEM.md        ← por qué existe cada parche
- @DOCUMENTACION_TECNICA.md                  ← §1–§21 obligatorios

Alcance:
- ES INFRAESTRUCTURA PURA. No implementes ninguno de los plugins 001–006.
- No toques composer.json ni ejecutes composer.
- No añadas hooks fuera del contrato v1 (alert.detect, layout.footer).
- Implementa §0b (gitignore, try/catch del bus, validación del manifiesto, CLI).
- No implementes la caché del listado, la subida ZIP, min_erp_version ni los hooks reservados.

Definición de terminado:
1. Las 4 tablas sys_* existen y el arranque es tolerante (checkout sin plugins).
2. Las clases de §7 existen con las firmas de §7.1.
3. Los 7 touch points están integrados.
4. Los criterios de aceptación de §15 pasan (verificación por CLI, no navegador).
5. php spark plugin:doctor reporta 0 errores.

Al terminar, añade tu reporte de implementación al final de
@agentic/AUDITORIA_PLUGIN_SYSTEM.md (sección "Reporte de implementación
PLUGIN-000") con: archivos creados, archivos modificados, salida de
plugin:doctor, resultado del ciclo completo con el plugin fixture y cualquier
desviación respecto a esta spec.
```
