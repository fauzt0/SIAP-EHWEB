# Avance del núcleo de plugins

**Última actualización:** 2026-10-16  
**Rama de trabajo:** `feat/plugin-system-architecture` (publicada en `origin`)  
**HEAD:** último commit en `origin/feat/plugin-system-architecture` (tabla siguiente; puede incluir ajustes menores de este `.md`)  
**Estado general:** **[PLUGIN-000] cerrado** en código y documentación v1.2. Pendientes de producto: **PLUGIN-000b** y plugins **001–006** (solo specs en `agentic/`).

---

## Resumen ejecutivo

| Área | Estado |
|------|--------|
| Spec v1.2 (`plugin_000` §0b) | Cerrada antes de codificar (`30f4e9a`) |
| Núcleo PHP (mercado, ciclo de vida, respaldos, hooks) | Implementado (`ec844b6`) |
| Verificación post-implementación + parches | Hecha (`ea37139` y ajustes de ajustes vacíos) |
| Auditoría de seguridad del núcleo | Documentada en `agentic/AUDITORIA_PLUGIN_SYSTEM.md` §7 y `agentic/AUDITORIA_ARQUITECTURA.md` REPORTE-016 |
| UI del mercado en navegador | No verificada (401 Basic Auth en sandbox, DT-5) |
| Merge a `main` | Pendiente de PR; ramas divergentes (ver § Integración con `main`) |
| Auditoría por otro modelo | Prompt listo al final de este archivo |
| ¿Choca con `AUDITORIA_ARQUITECTURA.md`? | **No.** Ver sección «Compatibilidad con la auditoría de arquitectura del ERP» |

---

## Commits de esta línea (orden cronológico)

| Commit | Descripción |
|--------|-------------|
| `13fd1af` … `2233d17` | Arquitectura y roadmap en `agentic/` |
| `7e1dbe5`, `85ec18f` | Auditoría y parches v1.1 |
| `5e5ab78`, `5b56874` | TODO y contrato sidebar / hooks v1 |
| `30f4e9a` | Cierre spec v1.2 del núcleo antes de codificar |
| `ec844b6` | Implementación del núcleo (~34 archivos) |
| `ea37139` | Correcciones tras verificación (restore, purge, assets, doctor, `avance.md` inicial) |
| `79c8b15` | `avance.md` cerrado, `PLUGIN_000_PROMPT` marcado obsoleto, `saveSettings` sin éxito vacío |

El sprint `8945ab0` (documentos legales, bitácora, nóminas, etc.) está en la historia de esta rama **después** de `13839da` (`main` actual) y **antes** de los commits de plugins.

---

## Checklist PLUGIN-000 (alineado con `TODO.md`)

- [x] `PluginManager`, `PluginInterface`, `BasePlugin`, `PluginBackupService`, `PluginHookRegistry`, `PluginMarket`, `PluginBaseController`
- [x] Cuatro migraciones `sys_*` (sin columna `config`; `class_dir` en respaldos)
- [x] Siete touch points: `Autoload`, `Events`, `Routes`, `loggedin_sidebar`, `user_loggedin_layout`, `AlertController`, `UserController` (`getAllPermissions()` en lectura y guardado)
- [x] `PluginMarketController` + `plugins_market.php` (Desactivar → Desinstalar → Restaurar → Purgar)
- [x] Respaldo: `RENAME TABLE` atómico, SQL en `writable/plugins/backups/`, registro antes de renombrar; purga con `ELIMINAR`
- [x] `php spark plugin:doctor` y `plugin:action` (install, activate, deactivate, uninstall, restore, purge, update, settings)

**Fuera de PLUGIN-000 (abierto):**

- [ ] **[PLUGIN-000b]** Tabs en offcanvas + `dashboard.widgets` (bloquea 001 y 006)
- [ ] **[PLUGIN-001]** … **[PLUGIN-006]** — revisar hooks contra contrato v1; no codificar sin revisión humana

---

## Qué hace el núcleo

- Tablas: `sys_plugins`, `sys_plugin_permissions`, `sys_plugin_settings`, `sys_plugin_backups`.
- Mercado admin: rutas bajo el grupo `nat` → `session` → `admin`, namespace `App\Controllers\Admin`, alias `admin.plugins.*`, permiso `admin.manage-plugins` (cubierto por `admin.*` en grupos admin/superadmin).
- Hooks v1 con dispatch real: `alert.detect` (collect), `layout.footer` (action). Sidebar vía `getSidebarItems()`, rutas vía `loadRoutes()`, assets vía `renderAssets()`.
- Desinstalar **no** hace `DROP`. Restaurar deja `inactive` y repone historial `migrations` del namespace `Plugins\{Directory}`.
- Permisos de plugin: validados contra catálogo Shield y prefijos comodín de `AuthGroups.matrix` (`admin`, `superadmin`, `users`, `finance`, `inventory`, `sales`, `purchasing`, `hr`, `catalog`).

**Normativa:** `agentic/plugin_000_plugin_system_core.md` v1.2 §**0b** (prevalece sobre bloques anteriores del mismo archivo). `agentic/ARQUITECTURA_PLUGIN_SYSTEM.md` v1.2 §4 y §5 = historial. `DOCUMENTACION_TECNICA.md` §1–§21 = norma ERP.

---

## Verificación realizada

### Entorno sandbox (CLI)

- `php spark migrate` — tablas `sys_*` creadas.
- `php spark plugin:doctor` — 0 errores / 0 advertencias sin plugins en disco.
- `php spark routes` — `/nat/admin/plugins` y POST del mercado con filtro `permission`.
- Ciclo **fixture** temporal (carpeta y datos eliminados al cerrar): install → settings inválidos rechazados → deactivate → uninstall → reinstall bloqueado → restore → update → purge con `ELIMINAR`.

### Navegador

`https://sandbox.especialistashosting.com/nat/admin/plugins` → HTTP **401**, `WWW-Authenticate: Basic realm="Area de desarrollo"` (nginx, antes de Shield). No se evadió. La vista `plugins_market.php` (modales, CSRF vía `#csrf_token`, purga hasta escribir `ELIMINAR`) queda sin prueba manual.

### Entorno Cloud Agent (`/workspace`)

Este clon puede no tener PHP ni base de datos. La verificación ejecutable del núcleo sigue siendo en el sandbox o en CI con PHP 8.1+ y MySQL.

---

## Correcciones incorporadas (post `ec844b6`)

| Tema | Cambio |
|------|--------|
| `restore()` | Sin concatenar ruta si `pluginDirectory()` es null; rollback completo si falla insert, `registerPermissions` o `markMigrationsApplied` |
| `purge()` | Validar todos los nombres archivados antes de cualquier `DROP` |
| Respaldos sin tablas | Sin prefijo ficticio `plg_none_` |
| `archive()` | `directory` debe ser PascalCase |
| `uniqueStamp()` | Máximo 5 reintentos (1 s entre ellos) |
| `saveSettings()` | Rechaza claves no declaradas, clave vacía y petición sin claves |
| `plugin:action settings` | Exige `plugin:action settings <slug> <clave> <valor>` |
| `renderAssets()` | `realpath` confinado a `public_html/plugins/{slug}/` |
| `bootActivePlugins()` | Ignora slug de BD con formato inválido |
| `plugin:doctor` | `class_dir` validado; manifiesto en disco no instalado con carpeta distinta = **WARN**, no error fatal |

---

## Integración con `main`

```
origin/main ─────────────────────────────► 13839da
                                              │
origin/feat/plugin-system-architecture ───────┼──► 8945ab0 ──► … plugins … ──► ea37139
```

`main` no contiene el sprint `8945ab0` ni el núcleo de plugins. Para integrar: abrir PR desde `feat/plugin-system-architecture` hacia `main`, resolver conflictos si los hay, y ejecutar `php spark migrate` + `plugin:doctor` en el entorno destino.

---

## Riesgos y limitaciones aceptadas

- Plugin instalado = PHP y SQL con el usuario de la aplicación (no sandbox).
- `min_erp_version` en manifiesto no se compara (sin constante de versión ERP).
- Sin caché del listado de plugins; hooks reservados sin dispatch.
- Rollback de `restore()` probado por diseño; no hay test automatizado que fuerce excepción en `registerPermissions`.
- Asignaciones directas en `auth_permissions_users` para permisos de plugin desinstalados pueden quedar huérfanas hasta limpieza manual (el catálogo en UI deja de listarlos vía `sys_plugin_permissions`).
- Manifiesto inválido solo en disco: `plugin:doctor` emite advertencia; no tumba el comando.

---

## Documentación relacionada

| Archivo | Uso |
|---------|-----|
| `agentic/plugin_000_plugin_system_core.md` | Contrato de implementación v1.2 |
| `agentic/AUDITORIA_PLUGIN_SYSTEM.md` §6–§7 | Cierre v1.2 + reporte de implementación y seguridad |
| `agentic/AUDITORIA_ARQUITECTURA.md` REPORTE-016 | Pilares §7/§8 del mercado |
| `TODO.md` | Roadmap PLUGIN-000b y 001–006 |
| `agentic/PLUGIN_000_PROMPT.md` | **Obsoleto** (pre-código, v1.1). Usar el prompt de auditoría de este archivo para revisiones |

---

## Compatibilidad con la auditoría de arquitectura del ERP

**Veredicto: no se empalman ni se invalidan.** Puedes correr la auditoría del sistema de plugins con Sonnet.

`agentic/AUDITORIA_ARQUITECTURA.md` es el **control de calidad del ERP** (`DOCUMENTACION_TECNICA.md` §1–§21): pilares Shield (§7) y bitácora (§8), más reportes de módulos (sucursales, proveedores, RRHH, nóminas, alertas). El núcleo de plugins **aplicó** esos estándares; no los sustituye.

Capas distintas:

| Documento | Qué cubre | Qué no debe hacer el auditor de plugins |
|-----------|-----------|----------------------------------------|
| `AUDITORIA_ARQUITECTURA.md` | Convenios del ERP y deuda de módulos 001–011 | Reabrir FIX-A…D, CRM 004b, legal 001, granularidad de turnos HR, ISR SAT |
| `plugin_000` v1.2 §0b + código `PluginSystem/` | Contrato del núcleo (manifiesto, respaldos, hooks, Market) | Reescribir el ERP para “parecerse” a un módulo HR/catálogo |
| `AUDITORIA_PLUGIN_SYSTEM.md` | Diseño del plugin system (P0, ciclo de vida) | Tratar §4/§5 de `ARQUITECTURA_PLUGIN_SYSTEM.md` como código a implementar |

**REPORTE-016** (al final de `AUDITORIA_ARQUITECTURA.md`) ya mapeó PLUGIN-000 contra §1–§21. La auditoría de plugins **confirma o corrige el código**; no vuelve a auditar nóminas ni proveedores.

Los touch points del núcleo **respetan** los hallazgos de arquitectura; no los deshacen:

- §7/§8: rutas `permission:admin.manage-plugins`, `can()` en sidebar, `logActivity` en cada mutación.
- §9: `PluginMarketController` y `UserController::getAllPermissions()` **no** llaman `db_connect()`. La persistencia vive en librerías (`PluginManager`, `PluginBackupService`). `DOCUMENTACION_TECNICA.md` §9 permite **Modelo o Service Pattern**; esas clases son el servicio.
- §21: `_detectAll()` sigue agrupando detectores del ERP por permiso; al final fusiona `PluginManager::runAlertDetectors()` en `try/catch`. No reemplaza `_safeDetect()` ni los `_detect*` existentes.
- P0-1 de plugins: el catálogo de usuarios llama `permissionCatalog()`. Quitar ese merge **rompería** permisos de plugin y reabriría el hallazgo de arquitectura §7 al guardar usuarios (`syncPermissions`).

Los `db_connect()` que siguen en `AlertController` (detectores `_detectWorkers*` etc.) **son anteriores al núcleo**. No forman parte de PLUGIN-000. Un auditor de plugins no debe “arreglarlos” en esa auditoría.

### Excepciones deliberadas (no son interferencia)

Un auditor que lea solo la tarjeta §1–§21 de `AUDITORIA_ARQUITECTURA.md` podría marcar estos tres puntos. **No son incumplimientos del núcleo**; están escritos en `plugin_000` §14 y en REPORTE-016:

1. **§9 “solo Modelos”** — el núcleo no crea `SysPluginModel`. Usa librerías de servicio. Correcto según DT §9 (Service Pattern).
2. **§19 soft-delete** — tablas `sys_*` son de registro (instalar/archivar/purgar), sin `$useSoftDeletes`.
3. **§21 “nuevo detector = método `_detect*` en AlertController”** — v1 cablea el hook `alert.detect` para que cada plugin aporte detectores **sin** tocar el controlador otra vez.

Nada de lo abierto en arquitectura (legal 001, CRM 004b, turnos HR solo con `hr.access`, ISR SAT) exige cambiar el núcleo de plugins.

---

## Próximos pasos sugeridos

1. **PR** `feat/plugin-system-architecture` → `main` con descripción que enlace este avance y AUDITORIA §7.
2. **Auditoría** con otro modelo usando el prompt siguiente (sin reimplementar 001–006).
3. **PLUGIN-000b** — diseño de tabs offcanvas y `dashboard.widgets` antes de codificar 001/006.
4. **UI** — probar mercado cuando haya sesión más allá del Basic Auth del sandbox.
5. Revisión humana de `agentic/plugin_001_*.md` … `plugin_006_*.md` antes de codificar.

---

## Prompt de auditoría (otro agente / otro modelo)

Copia el bloque. **No** implementes plugins 001–006 ni PLUGIN-000b como parte de la auditoría.

```text
Audita el núcleo del sistema de plugins de este ERP (CodeIgniter 4.5.3 + Shield 1.1). Rama: feat/plugin-system-architecture, commit de referencia ea37139 o posterior. Objetivo: hallar fallos reales en el código existente, no reescribir la arquitectura ni codificar agentic/plugin_001_*.md … plugin_006_*.md.

Relación con agentic/AUDITORIA_ARQUITECTURA.md (leída, no es trabajo de esta auditoría):
- Es el control de calidad ERP (DT §1–§21). El núcleo YA se mapeó ahí en REPORTE-016.
- NO reabras módulos 001–011, FIX-A…D, CRM, documentos legales, nóminas ni turnos HR.
- NO muevas PluginManager/PluginBackupService a Models “por §9”: DT §9 permite Service Pattern; el núcleo usa Libraries.
- NO exijas soft-delete en sys_* (§19) ni conviertas alert.detect en métodos _detect* nuevos de AlertController (§21).
- NO toques los db_connect() preexistentes de AlertController (detectores HR/catálogo). El núcleo solo añade runAlertDetectors() al final de _detectAll().
- NO reviertas UserController::getAllPermissions() → permissionCatalog(): eso cumple §9 y evita que syncPermissions borre permisos de plugin (§7).

Lectura obligatoria (orden):
1. DOCUMENTACION_TECNICA.md §1–§21
2. agentic/plugin_000_plugin_system_core.md v1.2 — §0b prevalece sobre el resto del archivo
3. agentic/ARQUITECTURA_PLUGIN_SYSTEM.md v1.2 — §4 y §5 son historial, no contrato
4. agentic/AUDITORIA_PLUGIN_SYSTEM.md §6–§7 y agentic/AUDITORIA_ARQUITECTURA.md SOLO REPORTE-016 + sección «Compatibilidad» de agentic/avance.md
5. agentic/avance.md — estado, verificación, lista “Ya corregido” y compatibilidad con la auditoría de arquitectura

Código principal:
- app/Libraries/PluginSystem/*
- app/Controllers/Admin/PluginMarketController.php, app/Controllers/PluginSystem/PluginBaseController.php
- app/Views/Admin/plugins_market.php, app/Views/PluginSystem/sidebar_section.php
- app/Commands/PluginDoctor.php, app/Commands/PluginAction.php
- app/Database/Migrations/System/2026-09-18-200000_* … 200003_*
- Touch points: app/Config/{Autoload,Events,Routes,AuthGroups}.php, loggedin_sidebar, user_loggedin_layout, AlertController, UserController
- app/Models/Users/UserActivityLogsModel.php (CLI / user_agent)

Hechos CI4 ya validados (no “arreglar” al revés):
- Runner de migraciones no compartido; sin setGroup(slug) ni setPath()
- Namespace exacto Plugins\{Directory} solo en install/update/restore
- spark no dispara pre_system → loadRoutes() llama bootActivePlugins()
- FCPATH web = public_html/; spark usa public/
- where('id !=', $id) válido en CI4 4.5.3
- Bitácora: tabla users_activity_logs; users_id nullable; CLI sin getUserAgent()

Contrato de seguridad (muestra):
- slug, directory PascalCase, table_prefix plg_[a-z0-9]+_, identificadores [A-Za-z0-9_]
- uninstall sin DROP; RENAME atómico; purga con ELIMINAR; backup_file bajo writable/plugins/backups/
- permisos sin prefijos ya cubiertos por comodines de AuthGroups.matrix
- renderAssets sin salir de public_html/plugins/{slug}/; mercado remoto HTTPS no instalable; sin ZIP
- controladores del mercado sin db_connect()

Ya corregido (no reabrir salvo regresión en código):
restore null-path, rollback restore, purge validate-then-drop, plg_none_, archive directory, uniqueStamp cap, saveSettings claves inválidas/vacías, renderAssets traversal, boot slug inválido, doctor class_dir y WARN en manifiesto suelto.

Verificación sin basura: php -l, php spark plugin:doctor; ciclo fixture temporal y limpieza de plugins/, writable/plugins/backups/*.sql, sys_*, migrations Plugins\%, users_activity_logs plugin_%.

Entrega: Bloqueantes / Defectos / Notas, cada uno con archivo+líneas, contrato roto y reproducción. Si no hay bloqueantes, decirlo explícitamente.
```
