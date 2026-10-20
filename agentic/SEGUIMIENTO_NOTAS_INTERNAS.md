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

### 404 web (causa real, corregida)

`php spark routes` listaba las cuatro rutas y el navegador respondía *Can't find a route for 'GET: nat/plugins/notas-internas'*. CLI no dispara `pre_system`. En la web, `Events.php` fusionaba `permissionCatalog()` dentro de `AuthGroups.permissions` y después `bootActivePlugins()` comparaba el permiso del plugin contra ese catálogo ya fusionado. `notasinternas.access` parecía un permiso del ERP, `instantiateRow()` lanzaba, el plugin no entraba en `$loadedPlugins` y la ruta no se registraba. El log dice: `no se cargó notas-internas: El permiso 'notasinternas.access' ya existe en el ERP.` El reintento de arranque lo repetía; no lo resolvía.

La foto del catálogo se toma ahora con `captureBasePermissions()` **antes** de la fusión. `permissionErrors()` compara solo contra esa foto. La fusión sigue después, para que Shield acepte el permiso en la UI.

El reintento (`bootAttempts` / `ensureBooted`) se queda: un fallo de BD no debe marcar el arranque como listo. Si tras el pull el 404 sigue, recarga PHP-FPM (OPcache puede servir la clase anterior).

### Vista y guardado (vistos al validar por HTTP)

- `view()` no acepta una ruta absoluta. El índice usa un renderer no compartido con raíz en `plugins/NotasInternas/Views` y `render('notes_index')`. El layout sigue resolviéndose desde `app/Views`.
- En PHP 8.3, `$casts` del modelo tiene que declararse `array` (el padre ya lo tipa). Sin eso, el listado es un fatal.
- El cast `int-bool` de CI 4.5 solo acepta `bool` al escribir. `save()` manda `true`/`false`, no `1`/`0`.

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
| **B** | `plugins/NotasInternas/Models/NoteModel.php` — `forUser()`, `ownedBy()`, timestamps, `protected array $casts` |
| Manifiesto | `plugin.json` v1.2.0, icon `fa-sticky-note` |
| Fix permisos | `captureBasePermissions()` antes de fusionar el catálogo; la fusión sigue para que Shield acepte el permiso en la UI |
| **C–D** | Rutas + `getSidebarItems()` |
| **E–F** | `NotesController` índice, `list_ajax`, `save`, `delete`. Vista con editor (negrita, cursiva, subrayado), colores, fijar y borrado. Cada usuario solo ve sus notas. |
| **G** | CSS y JS van en la vista. `public_html/plugins/*` está en `.gitignore`, así que no se versionan assets sueltos. |
| 404 | La colisión de permisos se evalúa contra el catálogo de antes de la fusión. Un fallo de BD no marca el arranque como listo. |

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

Validado por HTTP el 2026-10-20, con el plugin instalado y activo en 1.2.0:

- Invitado: GET `/nat/plugins/notas-internas` → 302 a `/nat/login` (la ruta existe).
- Con `notasinternas.access`: 200, compositor y enlace del sidebar.
- Crear, fijar, listar (fijadas primero), borrar. El cuerpo sale sin `<script>` ni atributos. Color ajeno vuelve a `#fff9c4`. Nota vacía → 400. Id ajeno o inexistente → 404 y no aparece en el listado.
- Bitácora `users_activity_logs`, type `notasinternas`.

---

## Handoff

La demo queda comprobada de punta a punta. El núcleo cambió en dos puntos: el reintento si la BD falla al arrancar, y la foto de permisos antes de fusionar el catálogo. Sin esa foto el navegador seguía en 404 aunque `php spark routes` mostrara la ruta.
