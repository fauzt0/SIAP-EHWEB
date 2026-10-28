# Seguimiento — Plugin NotasInternas (demo Keep)

**Estado:** **CERRADO** (demo de referencia PLUGIN-000)  
**Última actualización:** 2026-10-27  
**Rama:** `feat/plugin-system-architecture`  
**Manifiesto:** `notas-internas` v**1.3.1**  
**Repo:** https://github.com/fauzt0/SIAP-EHWEB.git  

---

## Resumen para el siguiente plugin

Esta demo validó de punta a punta el núcleo: manifiesto, migraciones (`install` / `update`), permiso Shield, rutas propias con `session` + `permission:`, sidebar (`getSidebarItems`), `PluginBaseController`, hook v1 `layout.footer`, vistas bajo `plugins/…/Views`, tabla `plg_*`, bitácora `notasinternas`. **No ampliar funcionalidad aquí** salvo bugfix; los plugins 001–006 siguen sus specs en `agentic/plugin_001_*.md`.

---

## Alcance entregado (A–G)

| Área | Entregable |
|------|------------|
| Datos | `plg_notasinternas_items` + migración de campos Keep |
| Modelo | `NoteModel` — `forUser()`, `ownedBy()` |
| UI principal | `/nat/plugins/notas-internas` — grid, editor básico, colores, fijar |
| Acceso rápido | Icono topbar, modal, nota fijada en `/nat/` (`quick_access.php` + `layout.footer`) |
| API | `list_ajax`, `save`, `delete` — CSRF, solo notas del usuario |
| Seguridad | `NoteContent::sanitizeBody`, colores acotados, 404 en notas ajenas |
| Permiso | `notasinternas.access` |

---

## Operación en sandbox

```bash
php spark plugin:action update notas-internas   # tras pull si cambia versión/manifiesto
php spark plugin:doctor
php tests/smoke_notas_internas.php
php spark routes | grep notasinternas
```

Permiso: asignar `notasinternas.access` en `/nat/user/permissions`. Tras cambios en `PluginManager` / `Events.php`, recargar PHP-FPM si el navegador no ve rutas.

---

## Identidad (no cambiar tras install)

| Clave | Valor |
|--------|--------|
| Slug | `notas-internas` |
| Carpeta | `plugins/NotasInternas/` |
| Clase | `Plugins\NotasInternas\NotasInternasPlugin` |
| Tabla | `plg_notasinternas_items` |

---

## Rutas

Grupo `nat/plugins/notas-internas` — `Plugins\NotasInternas\Controllers` — `session` + `permission:notasinternas.access`.

| Método | Alias |
|--------|--------|
| GET `/` | `notasinternas.index` |
| POST `list_ajax` | `notasinternas.list_ajax` |
| POST `save` | `notasinternas.save` |
| POST `delete/(:num)` | `notasinternas.delete` |

---

## Incidencias resueltas (histórico)

- **404 web:** colisión al evaluar permisos del plugin contra `AuthGroups` ya fusionado con `permissionCatalog()` → `captureBasePermissions()` en el núcleo.
- **Vistas:** renderer con raíz en `plugins/NotasInternas/Views` (no `view(ROOTPATH…)`).
- **PHP 8.3:** `$casts` tipado `array`; booleanos en `save` para CI 4.5.

Detalle técnico ampliado en commits `a66e0fd`, `456af78`, `bdd8da7` y en `app/Libraries/PluginSystem/PluginManager.php` (solo lo acordado para arranque/permisos).

---

## Verificación registrada

- Smoke local: `tests/smoke_notas_internas.php` (18 comprobaciones).
- `plugin:doctor`: 0 errores.
- HTTP 2026-10-20: login, CRUD, fijado en dashboard, modal, sanitización HTML.

---

## Siguiente paso del producto

Elegir spec **PLUGIN-001** … **006** (p. ej. `agentic/plugin_001_server_admin.md`), copiar patrones de esta carpeta, **sin** reutilizar slug ni `table_prefix` de Notas internas.
