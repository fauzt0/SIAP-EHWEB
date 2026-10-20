# Seguimiento — Plugin NotasInternas (demo Keep)

**Última actualización:** 2026-10-20  
**Rama:** `feat/plugin-system-architecture`  
**Repo:** https://github.com/fauzt0/SIAP-EHWEB.git  

---

## Pendiente inmediato

### 1. Revisión (agente / modelo más robusto)

Commits a revisar en `feat/plugin-system-architecture` (no reescribir PLUGIN-000 ni vendor CI4):

- `d6ce358` — migración Note fields, `NoteModel`, `plugin.json` 1.1.0, **único toque ERP:** `app/Config/Events.php` (fusionar `permissionCatalog()` en `AuthGroups` para que Shield acepte `notasinternas.access` al guardar).
- (este commit) — pasos C–E del plugin: rutas, sidebar, `NotesController::index` + vista estática.

Hallazgo abierto: en navegador `GET nat/plugins/notas-internas` devolvió *Can't find a route*; en CLI `php spark routes` y `php spark filter:check GET nat/plugins/notas-internas` sí resuelven. No se cambió `PluginSystem/` ni el router de CI4 para “arreglarlo”.

### 2. Permiso Shield

- Asignar `notasinternas.access` al usuario en `/nat/user/permissions`.
- Requiere el fix en `app/Config/Events.php` (fusionar `permissionCatalog()` en `AuthGroups` al `pre_system`); incluido en `d6ce358`.

### 3. Migración en sandbox (si otro clon no la aplicó)

```bash
php spark plugin:action update notas-internas
php spark plugin:doctor
```

---

## Identidad del plugin

| Clave | Valor |
|--------|--------|
| Slug | `notas-internas` |
| Carpeta | `plugins/NotasInternas/` |
| Clase | `Plugins\NotasInternas\NotasInternasPlugin` |
| Tabla | `plg_notasinternas_items` |
| Permiso | `notasinternas.access` |
| Versión manifiesto | `1.1.0` |
| Estado esperado | Instalado y **activo** |

---

## Hecho (pasos A–E + fix ERP)

| Ítem | Detalle |
|------|---------|
| **A** | Migración `2026-10-19-185422_AddFieldsToNotasInternasItems.php` — `user_id`, `body`, `color`, `is_pinned`, `updated_at`, índice `idx_plg_notasinternas_user_list` |
| **B** | `plugins/NotasInternas/Models/NoteModel.php` — `forUser()`, `ownedBy()`, timestamps |
| Manifiesto | `plugin.json` v1.1.0, icon `fa-sticky-note` |
| Fix permisos | `app/Config/Events.php` — Shield acepta permisos de plugin al guardar en UI |
| Skill sync | `.cursor/skills/sync-rama/` (traer cambios de agentes cloud) |
| **C** | `plugins/NotasInternas/Config/Routes.php` + `loadRoutes()` |
| **D** | `getSidebarItems()` en `NotasInternasPlugin.php` |
| **E** | `Controllers/NotesController.php` (`index` solamente) + `Views/notes_index.php` |

**No tocar:** migración inicial `2026-10-16-231040_CreateNotasInternasItems.php` (ya aplicada en install).

---

## Por hacer (F → G)

| Paso | Qué | Comprobación |
|------|-----|----------------|
| **404 web** | Confirmar si `GET /nat/plugins/notas-internas` carga tras recarga; si no, diagnosticar sin tocar `PluginSystem/` ni vendor | Navegador vs `php spark filter:check` |
| **F** | `list_ajax`, `save`, `delete` + JS (CSRF, `strip_tags` en body) | CRUD por usuario |
| **G** | `public_html/plugins/notas-internas/js|css` (opcional) | Assets en layout |

### Rutas sugeridas

Grupo: `nat/plugins/notas-internas` — namespace `Plugins\NotasInternas\Controllers` — filter `session`.

| Método | Ruta | Acción | Alias |
|--------|------|--------|--------|
| GET | `/` | `NotesController::index` | `notasinternas.index` |
| POST | `list_ajax` | `list_ajax` | `notasinternas.list_ajax` |
| POST | `save` | `save` | `notasinternas.save` |
| POST | `delete/(:num)` | `delete/$1` | `notasinternas.delete` |

Cada ruta: `filter` => `permission:notasinternas.access`.

Controlador: extiende `App\Controllers\PluginSystem\PluginBaseController`.

---

## Reglas (no romper)

- **NO** modificar `app/Libraries/PluginSystem/` salvo bug crítico acordado.
- **NO** plugins 001–006 ni PLUGIN-000b en esta tarea.
- Migraciones del plugin **solo** en `plugins/NotasInternas/Database/Migrations/`; aplicar con `plugin:action update`, no `php spark migrate` global para schema del plugin.
- No cambiar `slug`, `directory`, `table_prefix`, `class` en `plugin.json` tras install.
- Sidebar: `getSidebarItems()`, no hook `sidebar.render`.

**Normativa:** `agentic/plugin_000_plugin_system_core.md` (§0b prevalece).

---

## Verificación final (cuando C–F estén listos)

```bash
php spark plugin:doctor
php spark routes | grep notasinternas
```

Navegador: sidebar → crear / editar / borrar notas (solo las del usuario logueado).

---

## Handoff para agente cloud

Continuar F–G y el 404 web. No modificar `app/Libraries/PluginSystem/` ni `vendor/`. El único archivo ERP ya tocado es `app/Config/Events.php` (permisos Shield).
