# Seguimiento — Plugin NotasInternas (demo Keep)

**Última actualización:** 2026-10-20  
**Rama:** `feat/plugin-system-architecture`  
**Repo:** https://github.com/fauzt0/SIAP-EHWEB.git  

---

## Pendiente inmediato

Nada de código. En el VPS, después de `git pull`:

```bash
sudo systemctl reload php-fpm
php spark plugin:action update notas-internas
php spark plugin:doctor
php spark routes | grep notasinternas
```

Luego abre `/nat/plugins/notas-internas` con el permiso `notasinternas.access` asignado.

### 404 web (corregido en este corte)

La ruta ya estaba bien declarada: por eso `php spark routes` la veía y el navegador respondía *Can't find a route*. En la web, `pre_system` arranca los plugins antes de cargar `Routes.php`. Si ese arranque fallaba (la BD todavía no respondía, o la clase del plugin lanzaba), el núcleo marcaba el arranque como hecho y `loadRoutes()` no volvía a intentarlo. CLI no dispara `pre_system`, así que ahí la ruta sí existía.

Ahora un fallo de BD no deja el arranque marcado, y un plugin que no pudo instanciarse se reintenta una vez al registrar rutas, sidebar y assets. Si el 404 continúa después del pull, recarga PHP-FPM: OPcache puede estar sirviendo el `NotasInternasPlugin.php` anterior, el que no registraba rutas. En el log, busca `PluginSystem`.

---

## Identidad del plugin

| Clave | Valor |
|--------|--------|
| Slug | `notas-internas` |
| Carpeta | `plugins/NotasInternas/` |
| Clase | `Plugins\NotasInternas\NotasInternasPlugin` |
| Tabla | `plg_notasinternas_items` |
| Permiso | `notasinternas.access` |
| Versión manifiesto | `1.2.0` |
| Estado esperado | Instalado y **activo**. Tras este corte: `php spark plugin:action update notas-internas` |

---

## Hecho (pasos A–G)

| Ítem | Detalle |
|------|---------|
| **A** | Migración `2026-10-19-185422_AddFieldsToNotasInternasItems.php` — `user_id`, `body`, `color`, `is_pinned`, `updated_at`, índice `idx_plg_notasinternas_user_list` |
| **B** | `plugins/NotasInternas/Models/NoteModel.php` — `forUser()`, `ownedBy()`, timestamps |
| Manifiesto | `plugin.json` v1.2.0, icon `fa-sticky-note` |
| Fix permisos | `app/Config/Events.php` — Shield acepta permisos de plugin al guardar en UI |
| **C–D** | Rutas + `getSidebarItems()` |
| **E–F** | `NotesController` índice, `list_ajax`, `save`, `delete`. Vista con editor (negrita, cursiva, subrayado), colores, fijar y borrado. Cada usuario solo ve sus notas. |
| **G** | CSS y JS van en la vista. `public_html/plugins/*` está en `.gitignore`, así que no se versionan assets sueltos. |
| 404 | `PluginManager` no marca el arranque como listo si la BD falla, y reintenta una vez el plugin que no pudo instanciarse antes de registrar rutas. |

**No tocar:** migración inicial `2026-10-16-231040_CreateNotasInternasItems.php` (ya aplicada en install).

---

## Rutas

Grupo: `nat/plugins/notas-internas` — namespace `Plugins\NotasInternas\Controllers` — filter `session`.

| Método | Ruta | Acción | Alias |
|--------|------|--------|--------|
| GET | `/` | `NotesController::index` | `notasinternas.index` |
| POST | `list_ajax` | `list_ajax` | `notasinternas.list_ajax` |
| POST | `save` | `save` | `notasinternas.save` |
| POST | `delete/(:num)` | `delete/$1` | `notasinternas.delete` |

Cada ruta: `filter` => `permission:notasinternas.access`.

---

## Reglas (no romper)

- **NO** modificar `app/Libraries/PluginSystem/` salvo bug crítico acordado.
- **NO** plugins 001–006 ni PLUGIN-000b en esta tarea.
- Migraciones del plugin **solo** en `plugins/NotasInternas/Database/Migrations/`; aplicar con `plugin:action update`, no `php spark migrate` global para schema del plugin.
- No cambiar `slug`, `directory`, `table_prefix`, `class` en `plugin.json` tras install.
- Sidebar: `getSidebarItems()`, no hook `sidebar.render`.

**Normativa:** `agentic/plugin_000_plugin_system_core.md` (§0b prevalece).

---

## Verificación

```bash
php tests/smoke_notas_internas.php
php spark plugin:doctor
php spark routes | grep notasinternas
```

Navegador: sidebar → crear / editar / fijar / borrar notas (solo las del usuario logueado).

---

## Handoff

CRUD de la demo cerrado. El núcleo solo cambió en el reintento de arranque (`bootAttempts` / `ensureBooted`), que era el 404 web vs CLI.
