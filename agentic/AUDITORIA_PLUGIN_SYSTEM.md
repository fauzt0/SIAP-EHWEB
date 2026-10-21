# Auditoría — Sistema de Plugins (parche previo a PLUGIN-000)

> **Fecha:** 2026-10-12 | **Rama:** `feat/plugin-system-architecture` | **Commit base:** `2233d17`
> **Alcance de este informe:** 3 bloqueantes de arquitectura + 4 defectos de ciclo de vida del `Plugin System Core`.
> **Estado:** aplicada el 2026-10-12. Los 7 parches están incorporados en `ARQUITECTURA_PLUGIN_SYSTEM.md` (v1.1) y en `plugin_000_plugin_system_core.md` (v1.1, spec de implementación vigente). La codificación queda a cargo de otro agente; este documento conserva la evidencia y la justificación de cada cambio.
> **Método:** contraste línea a línea del diseño contra el código real — `app/`, CI4 `4.5.3` y Shield `1.1` en `vendor/`, y la base de datos del entorno.

---

## 1. Veredicto

La arquitectura conceptual es correcta y debe conservarse: aislamiento por carpeta y namespace, prefijo de tablas `plg_{slug}_`, ciclo *backup-first* (Desactivar → Desinstalar → Restaurar → Purgar), cuatro tablas `sys_*`, manifiesto `plugin.json` y puntos de enganche únicos al ERP.

Sin embargo, **tal como está especificada, la implementación fallará**: el modelo de permisos queda inutilizable, las migraciones de cada plugin no se ejecutarán (y el *install* reportará éxito), y el mecanismo de autoload propuesto contradice el modelo *drop-in* del Market. Se suman cuatro defectos que rompen el ciclo de vida en el primer plugin real.

| Categoría | Cantidad | En este parche |
|---|---|---|
| Bloqueantes de arquitectura | 3 | Sí (P0-1 … P0-3) |
| Defectos de ciclo de vida | 4 | Sí (C-1 … C-4) |
| Hallazgos adicionales (anexo A) | 8 | No |
| Gap del contrato de hooks (anexo B) | 1 | No — bloquea specs 001/006 |

**Recomendación:** aplicar los 7 parches de este informe antes de lanzar PLUGIN-000. No requieren reescribir la arquitectura; son puntuales y verificables.

---

## 2. Bloqueantes de arquitectura

### P0-1 — El modelo de permisos no permite asignar permisos de plugin, y al guardar los borra

**Evidencia**

| Ubicación | Qué demuestra |
|---|---|
| `app/Controllers/Users/UserController.php:851` | El catálogo de permisos disponibles es `setting('AuthGroups.permissions')`. `sys_plugin_permissions` no aparece en la UI. |
| `app/Controllers/Users/UserController.php:950` | `permissions_save()` descarta lo que no exista en ese catálogo: `array_key_exists($sp, $allPermissions)`. |
| `app/Controllers/Users/UserController.php:956` | `$user->syncPermissions(...)` **reemplaza el set completo**: cualquier permiso de plugin ya concedido se pierde al guardar. |
| `vendor/codeigniter4/shield/src/Models/PermissionModel.php:40-49` | `getForUser()` lee únicamente `auth_permissions_users` (asignaciones directas). |
| `vendor/codeigniter4/shield/src/Authorization/Traits/Authorizable.php:227-234` | `hasPermission()` es un `in_array` exacto sobre esa lista. |
| `vendor/codeigniter4/shield/src/Authorization/Traits/Authorizable.php:267,279-284` | Los permisos de grupo y los comodines se resuelven con `setting('AuthGroups.matrix')` dentro de `can()`. |
| `app/Config/AuthGroups.php:131,201,214` | Catálogo `$permissions`, matriz `$matrix` y comodín `'admin.*'`. |
| `vendor/codeigniter4/shield/src/Filters/PermissionFilter.php` | El filtro `permission:` usa `$user->can()`; su alias lo registra Shield (`vendor/codeigniter4/shield/src/Config/Registrar.php`) y `app/Config/Filters.php:22-31` no lo sobreescribe. |

**Impacto:** `permission:servers.access` y cualquier `plugin_can()` devuelven siempre falso. Los plugins se instalan pero son inaccesibles, y no existe forma de conceder sus permisos desde la interfaz. El `PluginPermissionFilter` propuesto en `ARQUITECTURA_PLUGIN_SYSTEM.md §12` no resuelve el problema: añade una segunda comprobación sobre una tabla que nadie escribe. Además, el helper `plugin_can()` de esa misma sección consulta `auth_permissions_users` directamente, por lo que ignoraría los permisos concedidos por grupo.

**Parche propuesto**

1. Integrar los permisos de plugin al catálogo que ya consumen la UI, el guardado y `can()`:

```php
// app/Controllers/Users/UserController.php — nuevo helper privado
private function getAllPermissions(): array
{
    $permissions = setting('AuthGroups.permissions');

    // Plugin System: permisos declarados por los plugins instalados.
    if (db_connect()->tableExists('sys_plugin_permissions')) {
        foreach (db_connect()->table('sys_plugin_permissions')->get()->getResultArray() as $row) {
            $permissions[$row['permission']] = '[Plugin] ' . $row['description'];
        }
    }

    return $permissions;
}
```

Sustituir `setting('AuthGroups.permissions')` por `$this->getAllPermissions()` en `permissions()` (línea 851) y en `permissions_save()` (línea 926). El merge en `permissions_save()` **no es opcional**: sin él, `syncPermissions()` sigue borrando los permisos del plugin.

2. Eliminar `plugin_can()` como consulta directa a `auth_permissions_users` y usar la API nativa:

```php
function plugin_can(string $permission): bool
{
    return auth()->user()?->can($permission) ?? false;
}
```

3. Descartar `PluginPermissionFilter` (arquitectura §12). Si se conserva por defensa en profundidad, debe **sobreescribir el alias `permission`** en `app/Config/Filters.php` y devolver el error estándar de §15/§17 en peticiones AJAX: el filtro de Shield responde con un `RedirectResponse` (`AbstractAuthFilter::before()`), lo que rompe los POST hechos con `fetch()`.

4. Opcional (decisión de producto): para que un grupo reciba los permisos del plugin por defecto, escribir el override en `Settings` de `AuthGroups.matrix` al instalar (por ejemplo `'admin' => [... 'servers.*']`). Es la única fuente que leen `can()` y `permissions_save()`; nunca editar `app/Config/AuthGroups.php` desde un plugin.

**Verificación:** asignar un permiso de plugin desde `/nat/user/permissions/{id}`, guardar dos veces y comprobar con un script CLI que `$user->can('servers.access')` sigue siendo `true`.

---

### P0-2 — Las migraciones del plugin no se ejecutarán: fallo silencioso en el install

**Evidencia**

| Ubicación | Qué demuestra |
|---|---|
| `ARQUITECTURA_PLUGIN_SYSTEM.md §4` — `BasePlugin::runMigrations()` | Usa `Services::migrations()->setNamespace('Plugins\\' . $this->manifest['slug_pascal'])`. |
| `vendor/codeigniter4/framework/system/Database/MigrationRunner.php:477-482` | `setNamespace()` solo asigna el string; no registra rutas. |
| `vendor/.../MigrationRunner.php:414-427` | Sin `$this->path`, resuelve con `$locator->listNamespaceFiles($namespace, '/Database/Migrations/')`. |
| `vendor/.../Autoloader/FileLocator.php:357-383` | Delega en `Autoloader::getNamespace($prefix)`. |
| `vendor/.../Autoloader/Autoloader.php:226-232` | `getNamespace()` es un **lookup exacto de clave**: `getNamespace('Plugins\ServerAdmin')` devuelve `[]` cuando lo registrado es el prefijo `Plugins\`. |
| `vendor/.../MigrationRunner.php:157-173` | `latest()` retorna `true` cuando `$migrations === []`. |

**Impacto:** la instalación reporta éxito, `sys_plugins` queda registrado como `active`, y **no se crea ninguna tabla** ni se escribe nada en el log. El plugin falla después, en runtime. Es el peor modo de fallo posible: silencioso en el punto donde el administrador cree haber terminado.

**Parche propuesto**

```php
// app/Libraries/PluginSystem/BasePlugin.php
protected function pluginNamespace(): string
{
    // 'Plugins\ServerAdmin' — namespace real del plugin (deriva de la carpeta)
    return 'Plugins\\' . ($this->manifest['directory'] ?? basename($this->pluginPath));
}

protected function runMigrations(): bool
{
    $migrationsPath = $this->pluginPath . '/Database/Migrations';
    if (! is_dir($migrationsPath)) {
        return true;
    }

    // 1. Registrar el namespace EXACTO del plugin. FileLocator::listNamespaceFiles()
    //    resuelve con Autoloader::getNamespace(), que es un lookup exacto de clave:
    //    el prefijo amplio 'Plugins\' NO basta para encontrar las migraciones.
    \Config\Services::autoloader()->addNamespace($this->pluginNamespace(), $this->pluginPath);

    // 2. Ejecutar. getHistory() filtra el historial por namespace, así que este
    //    runner solo ve las migraciones de este plugin.
    $runner = \Config\Services::migrations();
    $runner->setNamespace($this->pluginNamespace());

    // 3. Guarda contra el fallo silencioso: si hay archivos en disco y no se
    //    descubren, devolver error en lugar de 'instalado sin tablas'.
    if ($runner->findNamespaceMigrations($this->pluginNamespace()) === []) {
        log_message('error', '[PluginSystem] {slug}: migraciones presentes pero no descubiertas.', [
            'slug' => $this->manifest['slug'],
        ]);

        return false;
    }

    return $runner->latest() !== false;
}
```

**Tres restricciones verificadas de la API de CI4 4.5.3 que condicionan este parche:**

1. **No existe `setPath()`.** `MigrationRunner` solo expone la ruta vía `force($path, $namespace, $group)`, documentado como *"not recommended, provided mostly for testing"*. El descubrimiento estándar de migraciones se hace por namespace vía `FileLocator::listNamespaceFiles()`.
2. **`setNamespace()` no registra nada**: solo asigna el string (`MigrationRunner.php:477-482`). El namespace exacto hay que registrarlo en el autoloader con `Autoloader::addNamespace()` (`Autoloader.php:195-216`).
3. **No usar `setGroup()` como etiqueta.** `group` es el grupo de conexión de base de datos: si `groupFilter` no coincide con `$instance->getDBGroup()`, `migrate()` marca `groupSkip = true` y retorna `true` **sin ejecutar** la migración, pero `addHistory()` la registra como aplicada (`MigrationRunner.php:838-853`). Es la misma clase de fallo silencioso que motiva P0-2.

**Invariante de aislamiento a documentar:** el prefijo amplio `Plugins\` → `plugins/` (P0-3) habilita el autoload de clases pero **no** expone migraciones, porque `plugins/Database/Migrations/` no existe. Por eso el namespace exacto (`Plugins\ServerAdmin\`) debe registrarse **solo dentro del flujo de instalación/actualización**, no en `bootActivePlugins()`: si el boot lo registra, el `php spark migrate` global pasará a recorrer las migraciones de todos los plugins instalados y una migración rota de un plugin romperá el comando del ERP.

**Verificación:** instalar un plugin fixture y comprobar con `php spark db:table <tabla_del_plugin>` que las tablas existen; añadir una segunda migración al fixture y repetir el flujo de actualización.

---

### P0-3 — `composer dump-autoload` como paso de instalación rompe el modelo *drop-in*

**Evidencia**

| Ubicación | Qué demuestra |
|---|---|
| `composer.json` | No existe sección `psr-4` para la app; solo `exclude-from-classmap`. |
| `plugin_000_plugin_system_core.md §4` | Propone añadir `"Plugins\\": "plugins/"` a Composer y ejecutar `composer dump-autoload`. |
| `plugin_000_plugin_system_core.md §7` | El Market incluye subir un plugin (ZIP) en runtime. |
| `vendor/.../Autoloader/Autoloader.php:146-152,370` | El autoloader de CI4 ya mezcla en runtime los namespaces de Composer (`loadComposerNamespaces()`). |

**Impacto:** el binario `composer` no está garantizado en el servidor ni es usable en un flujo iniciado desde el navegador; cada plugin nuevo exigiría una intervención por SSH y un borrado selectivo de opcache. Además, `composer.json` no es hoy la fuente de verdad de namespaces de esta app (lo es `app/Config/Autoload.php`).

**Parche propuesto**

```php
// app/Config/Autoload.php
public $psr4 = [
    APP_NAMESPACE => APPPATH,
    'Config'      => APPPATH . 'Config',
    'Plugins'     => ROOTPATH . 'plugins',   // Plugin System
];
```

Una sola línea, un único cambio de ERP, sin `composer dump-autoload` y sin tocar `composer.json`. El autoloader de CI4 resuelve `Plugins\X\...` desde `plugins/X/...`, y las clases nuevas que aparezcan tras subir un ZIP quedan disponibles sin regenerar mapas.

**Verificación:** subir una carpeta de plugin con una clase nueva, sin ejecutar Composer, y comprobar que `class_exists('Plugins\\X\\X')` es `true` vía `php spark`.

---

## 3. Defectos de ciclo de vida

### C-1 — `uninstall()` devuelve `bool`, el Manager espera el array del backup

`PluginInterface::uninstall(): bool` y `BasePlugin::uninstall()` retornan `true`; `PluginManager::uninstall()` hace `basename($backup['backup_file'])` sobre ese booleano y el mensaje de éxito pierde la referencia al respaldo. Además `backupPluginData()` retorna un array que hoy se descarta.

**Parche:** alinear el contrato en la especificación del interface y en el Manager.

```php
// PluginInterface
/** @return array{success: bool, backup_file: ?string, archived_tables: array} */
public function uninstall(): array;

// BasePlugin
public function uninstall(): array
{
    $backup = $this->backupPluginData();   // ['backup_file' => ..., 'archived_tables' => [...]]
    $this->removePermissions();

    return ['success' => true] + $backup;
}
```

Ajustar los tres puntos de uso: `PluginManager::uninstall()`, el mensaje del `PluginMarketController::uninstall()` y `logActivity`.

### C-2 — `restore()` y `purge()` invocan métodos `protected` del plugin

`PluginManager::restore()` llama `$instance->restoreBackupTables($backupId)` y `$instance->registerPermissions()`, y `PluginManager::purge()` llama `$instance->purgeBackup($backupId)`. Los tres son `protected` en `BasePlugin`: cualquier intento termina en `Error: Call to protected method`.

**Parche (recomendado):** extraer el archivado, la restauración y la purga a un servicio del core, `app/Libraries/PluginSystem/PluginBackupService.php`, que opera con la fila de `sys_plugin_backups` y **no necesita instanciar la clase del plugin**. Ventajas: elimina el error de visibilidad, saca de `BasePlugin` una responsabilidad que no es del plugin, y resuelve el hallazgo A-3 del anexo (la purga deja de requerir `plugin.json` en disco). `BasePlugin::uninstall()` delega: `PluginBackupService::archive($manifest)`.

### C-3 — `class_dir` guarda el slug (kebab) pero el boot busca la carpeta (PascalCase)

`PluginManager::install()` inserta `'class_dir' => $slug` (`server-admin`) mientras el boot arma `ROOTPATH . 'plugins/' . $row['class_dir']` y la convención de directorios es `plugins/ServerAdmin/`. `restore()` comete el mismo error (`'class_dir' => $backup['plugin_slug']`). Resultado tras instalar el primer plugin: `file_exists($pluginPath . '/plugin.json')` es falso, el boot hace `continue` y el plugin queda "instalado" **sin hooks, sin rutas y sin sidebar**, otra vez en silencio.

**Parche:**

1. Añadir el campo obligatorio `"directory": "ServerAdmin"` al manifiesto (`§3` de la arquitectura), distinto de `slug` (identidad y URLs) y de `route_prefix`.
2. Validar en `install()`: `is_dir(ROOTPATH . 'plugins/' . $manifest['directory'])` y que `plugin.json` exista; abortar con error explícito si no.
3. Persistir la carpeta, no el slug: `'class_dir' => $manifest['directory']`.
4. Añadir `class_dir VARCHAR(100)` a `sys_plugin_backups` (la migración aún no está implementada, por lo que el cambio no tiene coste) y usarlo en `restore()` y `purge()`.

### C-4 — El historial de migraciones no se limpia al desinstalar: reinstalar deja el plugin sin tablas

El archivado renombra las tablas a `_bak_*`, pero las filas de la tabla `migrations` (`version`, `group`, `namespace` — `vendor/.../MigrationRunner.php:595-598`) permanecen. Al reinstalar (no restaurar), el runner del plugin (`runMigrations()` de P0-2) considera esas migraciones ya aplicadas, no crea tablas y el plugin arranca contra un esquema inexistente.

**Parche:**

1. Ejecutar las migraciones con el `runMigrations()` corregido de P0-2. El historial queda automáticamente acotado al plugin porque `getHistory()` aplica `where('namespace', $this->namespace)` cuando hay namespace (`vendor/.../MigrationRunner.php:648-651`).

> **Advertencia:** no usar `setGroup()`/`latest($slug)` con el slug del plugin. En CI4 `group` es el **grupo de conexión de base de datos**: `migrate()` calcula `$group = $instance->getDBGroup() ?? $this->group` y, si `groupFilter` no coincide, marca `groupSkip = true` y retorna `true` **sin ejecutar la migración** (`vendor/.../MigrationRunner.php:838-853`), pero `addHistory()` la registra igualmente como aplicada. Es decir: `setGroup('server-admin')` produce exactamente el mismo fallo silencioso que P0-2. Y borrar filas por `group` es peligroso: el valor real es `'default'` y arrastraría migraciones del ERP.

2. En la desinstalación, borrar únicamente el historial de ese plugin, identificado por namespace:

```php
db_connect()->table('migrations')
    ->where('namespace', $this->pluginNamespace())   // 'Plugins\ServerAdmin'
    ->delete();
```

3. Regla de negocio explícita: reinstalar tras desinstalar exige **Restaurar** (los datos viven en `_bak_*` y el SQL). `install()` debe detectar un respaldo pendiente en `sys_plugin_backups` para ese slug y ofrecer `restore()` en lugar de crear tablas nuevas.

---

## 4. Orden de aplicación sugerido

1. `app/Config/Autoload.php` → prefijo `Plugins` (P0-3).
2. Especificar `PluginBackupService` y reescribir en la spec `runMigrations()`, `uninstall()`, `restore()` y `purge()` (C-1, C-2, C-4, P0-2).
3. `sys_plugin_backups` + `class_dir`; manifiesto + `directory`; corregir `settings_defaults` → `settings` (C-3 y A-5).
4. `UserController::getAllPermissions()` y sus dos usos; retirar `PluginPermissionFilter` (P0-1).
5. Congelar el contrato de hooks v1 y el `PluginBaseController` antes de escribir las specs 001-006 (anexo B).

---

## 5. Criterios de aceptación del parche

- `php spark migrate` crea las cuatro tablas `sys_*` y no falla en un checkout sin plugins.
- Un plugin fixture crea sus tablas reales (verificado con `php spark db:table`), no solo "instala".
- `php spark routes` lista las rutas del plugin; `/nat/admin/plugins` responde con sesión válida.
- Ciclo completo sin errores fatales: activar → desactivar → desinstalar (verificar `_bak_*` y fila en `sys_plugin_backups`) → restaurar → purgar (verificar `DROP` y limpieza del SQL).
- Reinstalar después de desinstalar no deja el plugin en estado "sin tablas".
- Un permiso de plugin asignado desde la UI sobrevive a un segundo guardado de permisos del mismo usuario.
- Un plugin con error fatal en `registerHooks()` no impide cargar el ERP ni el Market.

> Nota operativa: el sandbox responde **401 Basic Auth** antes del login de Shield (DT-5 de `TODO.md`), así que la verificación debe ser CLI (`php spark`, `php -l`, PHPUnit) y no de navegador.

---

## Anexo A — Hallazgos fuera de este parche

| # | Hallazgo | Riesgo |
|---|---|---|
| A-1 | `SHOW TABLES LIKE 'plg_x_'` usa `_` como comodín | Puede archivar tablas ajenas que hagan match; usar `ESCAPE` o `str_starts_with()` |
| A-2 | `RENAME TABLE` una a una y fila de respaldo insertada después | Un fallo a mitad deja tablas `_bak_*` huérfanas, irrecuperables desde la UI; usar un solo `RENAME TABLE a TO b, c TO d` y registrar antes |
| A-3 | `purge()` exige `plugin.json` en disco | El caso típico de purga es un plugin desinstalado y ya borrado; se resuelve con `PluginBackupService` (C-2) |
| A-4 | `renderAssets()` busca `FCPATH/plugins/{slug}/css` y el origen es `plugins/{Pascal}/Assets/css`; `public_html/plugins/` fuera de `.gitignore` | Assets que no cargan y artefactos de deploy versionados |
| A-5 | Manifiesto: `settings_defaults` vs `settings`; `sys_plugin_settings` sin uso | Dos fuentes de verdad para la configuración; elegir una |
| A-6 | Arranque en `pre_system` sin `tableExists()`, try/catch ni caché; `doAction()`/`applyFilter()` sin try/catch | Un plugin roto o un checkout sin migrar tumba el ERP completo, el Market y `php spark`; comparar con `_safeDetect()` de `AlertController.php:288` |
| A-7 | `function plugin_can()` y `admin.*` — el paso 10 del plan (añadir a la matriz) es redundante | Cualquier permiso `admin.*` ya se concede por comodín; solo hace falta el catálogo `$permissions` |
| A-8 | `.gitignore` no cubre `writable/plugins/backups/` ni `public_html/plugins/` | Los dumps SQL con datos de clientes pueden acabar en un commit |

---

## Anexo B — Contrato de hooks: bloqueante para las specs 001 y 006

El catálogo de `ARQUITECTURA_PLUGIN_SYSTEM.md §6` promete 12 hooks; los 5 touch points solo entregan 3–4, y dos de ellos se invocan de forma directa, sin pasar por el bus (`renderSidebar()` en lugar de `sidebar.render`; `renderAssets()` en lugar de `layout.head`).

Además, varios destinos documentados **no existen**:

- `supplier.show.tabs`: `SupplierController::show()` (`app/Controllers/Financial/SupplierController.php:158`) es un endpoint AJAX que devuelve JSON; los tabs viven en el offcanvas de `app/Views/Financial/Suppliers/suppliers_index.php:439`.
- `catalog.product.show.tabs`: `CatalogProductController` no tiene `show()`.
- `worker.show.tabs`: `HR\WorkerController` no tiene `show()`, solo `show_ajax()`.
- `dashboard.widgets`, `layout.head`, `user.can`, `routes.after_load`, `supplier.products.filter`: sin punto de dispatch en el código.
- Las specs registran hooks fuera del catálogo: `quotation.show.actions` (003), `chatbot.contact.identified` (005), `quotation.converted` (006).

**Consecuencia para la orquestación:** 001 y 006 no pueden desarrollarse en paralelo con 000 como indica el roadmap; dependen de puntos de dispatch que no existen. Antes de escribir esas specs hay que (a) congelar un contrato de hooks v1 con lo que sí está cableado, (b) definir el mecanismo real de tabs en offcanvas (inyección por JSON o partial por plugin) como tarea propia —PLUGIN-000b— y (c) corregir el número de touch points: son más de 5 en cuanto se quiera que el catálogo deje de ser aspiracional.

---

*Informe de auditoría aplicado. Los 7 parches descritos están incorporados en la documentación v1.1. La revisión final v1.2 (2026-10-16) cierra los hallazgos que seguían abiertos y que afectaban al núcleo: A-4/A-8 (`.gitignore` y docroot `public_html`), A-6 en su parte de aislamiento del bus (la caché no), y los huecos de seguridad del manifiesto y del SQL descritos en `plugin_000` §0b. Siguen fuera del núcleo: hooks reservados y tabs de offcanvas (anexo B, PLUGIN-000b), caché del listado y `min_erp_version`.*

---

## 6. Cierre v1.2 — hallazgos que la codificación del núcleo debe absorber

| Hallazgo | Decisión v1.2 |
|---|---|
| A-1 `LIKE` con `_` | Ya resuelto en v1.1 (`str_starts_with`). Se añade whitelist `[A-Za-z0-9_]`. |
| A-2 `RENAME` no atómico | Ya resuelto en v1.1. Si la sentencia falla, se retira la fila de respaldo. |
| A-3 purga exige `plugin.json` | Ya resuelto con `PluginBackupService`. |
| A-4 assets / gitignore | `.gitignore` entra en el núcleo. El destino público es `public_html/plugins/{slug}/` porque ese `index.php` define `FCPATH`. |
| A-5 dos fuentes de configuración | Ya resuelto: solo `sys_plugin_settings`. |
| A-6 arranque frágil | `tableExists` + `try/catch` por plugin (v1.1) y por callback del bus (v1.2). Sin caché. |
| A-7 `plugin_can` / `admin.*` | No existe `plugin_can`. Un plugin no puede declarar permisos `admin.*` ni `superadmin.*`. |
| A-8 dumps en git | `writable/plugins/backups/*` ignorado. |
| Anexo B | Sin cambios. El núcleo no cablea hooks reservados. |
| §9 vs `db_connect()` en `UserController` | El merge del catálogo vive en `PluginManager::permissionCatalog()`. |
| `spark` sin `pre_system` | `loadRoutes()` arranca los plugins activos. |
| Runner compartido | Instancia no compartida de `MigrationRunner`. |

---

## 7. Reporte de implementación PLUGIN-000

**Fecha:** 2026-10-16. Núcleo codificado. Los plugins 001–006 no se implementaron.

### Archivos creados

`app/Libraries/PluginSystem/` (`PluginInterface`, `BasePlugin`, `PluginManager`, `PluginBackupService`, `PluginHookRegistry`, `PluginMarket`), `app/Controllers/PluginSystem/PluginBaseController.php`, `app/Controllers/Admin/PluginMarketController.php`, `app/Views/Admin/plugins_market.php`, `app/Views/PluginSystem/sidebar_section.php`, cuatro migraciones en `app/Database/Migrations/System/`, `app/Commands/PluginDoctor.php`, `app/Commands/PluginAction.php`, `plugins/.gitkeep`.

### Archivos modificados

Los 7 touch points (`Autoload`, `Events`, `Routes`, `loggedin_sidebar`, `user_loggedin_layout`, `AlertController`, `UserController`), `AuthGroups` (`admin.manage-plugins`), `.gitignore`, y `UserActivityLogsModel` para que la bitácora no reviente en CLI (no existe `getUserAgent()`).

### Verificación CLI

`php spark migrate` creó las cuatro tablas `sys_*`. `php spark plugin:doctor` termina con 0 errores y 0 advertencias, sin plugins instalados. `php spark routes` lista `/nat/admin/plugins` con filtro `permission`.

Ciclo con un plugin fixture (carpeta eliminada al terminar, tablas purgadas): instalar crea `plg_fix_items` y `class_dir = Fixture`; desactivar → desinstalar deja `_bak_plg_fix_*`, fila en `sys_plugin_backups` y cero filas de `Plugins\Fixture` en `migrations`; reinstalar se niega y pide restaurar; restaurar devuelve la tabla y deja el plugin `inactive`; `update` posterior no intenta recrearla; purgar sin la palabra `ELIMINAR` se niega; purgar con esa palabra hace `DROP` y borra el `.sql`.

Un plugin cuyo `registerHooks()` lanza no impide `php spark routes`. Un callback que lanza dentro del bus se registra en el log y el filtro conserva el valor anterior. Un manifiesto con `directory: ../Etc` y un permiso `hr.spy` se rechazan.

### Desvíos respecto de la spec v1.1, incorporados en v1.2

Arranque también desde `loadRoutes()` porque `spark` no dispara `pre_system`. Runner de migraciones no compartido. Catálogo de permisos en `PluginManager::permissionCatalog()` (§9). `layout.footer` después de los scripts del layout. Enlace de sidebar del Market. Comando `plugin:action`. Al restaurar se reponen las filas de `migrations` del plugin. Los permisos no pueden usar un prefijo ya cubierto por un comodín de `AuthGroups.matrix`.

### Auditoría de seguridad del núcleo

| Control | Resultado |
|---|---|
| Rutas del Market | Dentro del grupo con `session`, cada una con `permission:admin.manage-plugins` y el CSRF global. Los POST exigen AJAX. |
| Bitácora | install, uninstall, activate, deactivate, restore, purge, update y settings. La descripción de settings no incluye el valor. |
| Manifiesto | `slug`, `directory` y `class` validados. `realpath` de la clase tiene que quedar dentro de `plugins/{Directory}`. No hay subida ZIP. |
| Permisos | No se pisan los del ERP y no se acepta un prefijo que la matriz ya concede por comodín. Probado con `hr.spy`. |
| SQL | Identificadores `[A-Za-z0-9_]`. `DROP`/`RENAME` solo de `_bak_` + prefijo `plg_`. `backup_file` confinado a `writable/plugins/backups/` y modo `0640`. |
| Aislamiento | `try/catch` por plugin en el arranque y por callback en el bus. Probado. |
| Secretos | Un ajuste `password` no se reimprime en el formulario. El catálogo remoto solo acepta `https`, sin redirecciones, y no es instalable. `saveSettings()` rechaza claves no declaradas, clave vacía y peticiones sin claves. |
| Datos de prueba | El fixture, sus tablas, sus dumps y las filas de bitácora del ensayo se eliminaron. `plugin:doctor` queda en 0 errores. |

Riesgo aceptado: un plugin que ya está en disco, una vez instalado por un administrador, corre con el usuario de base de datos de la aplicación. El núcleo impide que el manifiesto apunte fuera de su carpeta; no es un sandbox. `min_erp_version` sigue sin compararse. La UI del Market no se recorrió en el navegador: `https://sandbox.especialistashosting.com/nat/admin/plugins` responde `401` con `WWW-Authenticate: Basic realm="Area de desarrollo"` antes de Shield.

---

## 8. Auditoría Sonnet 5.5 (2026-10-16) — hallazgos absorbidos

**Veredicto del auditor:** 0 bloqueantes, 5 defectos, notas. CLI; UI no ejercida (401).

| ID | Hallazgo | Resolución en código |
|---|---|---|
| D1 | Dos carpetas, mismo slug/prefijo, `doctor` en silencio | `manifestErrors($checkNeighbors)` + `install()` cuenta coincidencias |
| D2 | `update()` aceptaba otro `table_prefix` y uninstall dejaba tablas vivas | Columna `sys_plugins.table_prefix`; `update()` rechaza el cambio; uninstall archiva con el prefijo guardado; mensaje honesto si 0 tablas |
| D3 | Tabla > 43 caracteres no se puede `RENAME` a `_bak_*` | `MAX_LIVE_TABLE_NAME`; fallo en archive/install/update/doctor |
| D4 | Mismo permiso en dos plugins | `permissionOwner()` disco + `sys_plugin_permissions` |
| D5 | `alert.detect` mezclaba arrays asociativos → TypeError en `history_ajax` | `runAlertDetectors()` solo objetos / arrays con title\|message |

Notas aplicadas: `purge()` se niega si `restored_at`; la UI oculta Purgar/Restaurar en ese caso; `file_put_contents` comprobado; `getConfig()` no convierte texto JSON-like en array; `select` validado y pintado; `PLUGIN_MARKET_URL` entra por `getAvailable()` → `getCatalog()`; `requires` en deactivate/uninstall; `activate()` instancia y llama `registerHooks()` antes de marcar activo; `plugin:doctor` captura fallo de BD; caché de `onDiskManifests()`; respuesta remota ≤ 512 KiB.

Pendientes conscientes (no bloquean el núcleo): dump SQL entero en memoria; `class_exists` al abrir el Market carga PHP del plugin aún no instalado; huérfanos `auth_permissions_users`; `min_erp_version` / caché de listado / hooks reservados.

### Verificación final del núcleo (2026-10-21)

`php spark plugin:doctor` en 0 errores con Notas internas activa. El ciclo install → update sigue rechazando slug, carpeta, prefijo, permisos de comodín y descripciones vacías. Un ajuste `password` se guarda cifrado (`enc1:` + `encryption.key`) y `plugin:action settings` lo rechaza para que el secreto no quede en el historial del shell. Los demás tipos siguen pudiendo fijarse por CLI. Sigue fuera de alcance: hooks reservados, caché del listado, `min_erp_version` y los plugins 001–006.
