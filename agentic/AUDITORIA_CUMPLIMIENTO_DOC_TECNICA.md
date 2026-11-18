# Auditoría de cumplimiento — DOCUMENTACION_TECNICA.md (§1–§21)

**Fecha:** 2026-11-18 (America/Mexico_City)  
**Alcance:** ERP base (`app/`), vistas, rutas, plugins (`plugins/NotasInternas/`)  
**Método:** Revisión automatizada (grep/scripts) + muestreo manual. No sustituye pruebas E2E.  
**Reglas Cursor generadas:** `.cursor/rules/*.mdc` (entorno, mandatos, backend, frontend)

---

## Resumen ejecutivo

| Nivel | Hallazgo |
|-------|----------|
| **Cumplimiento alto** | Transacciones y `Database::connect()` fuera de controladores (salvo `AlertController` según §21); uso extendido de `isAJAX()`, `permission:` en rutas (~116 en `app/Config/Routes.php`), `DataTableTrait` en modelos de listados principales, `logActivity` en la mayoría de controladores con mutaciones. |
| **Cumplimiento parcial** | Iconografía Lucide en layout y varias vistas (§12); 2 vistas DataTable sin guardia `isDataTable`; 2 rutas activas sin alias `as`; vistas legacy (`Usuarios/`) coexistiendo con módulo `Users/`. |
| **Riesgo bajo inmediato** | Rutas home/ejemplo sin `as` (aceptable). CSRF global en layout cubre vistas que no duplican el hidden. |

**Veredicto:** El sistema **sigue en lo esencial** los lineamientos de `DOCUMENTACION_TECNICA.md`. Los hallazgos son deuda técnica priorizable, no bloqueantes para el núcleo plugin ni entornos de desarrollo.

---

## §1 AJAX

| Control | Resultado |
|---------|-----------|
| `isAJAX()` en controladores con endpoints dinámicos | Presente en módulos Financial, HR, Catalog, Users, Organization, System, plugins. |
| Exposición sin guard | No auditado exhaustivamente; recomendar revisión por ruta nueva en PR. |

---

## §2 CSRF

| Control | Resultado |
|---------|-----------|
| Token global en layout autenticado | ✅ `app/Views/Layouts/user_loggedin_layout.php` (`#csrf_token`). |
| Input en cada vista `main` | Parcial: ~24 vistas con `id="csrf_token"`; el resto depende del layout (válido para sesión logueada). |

---

## §3 Rutas

| Control | Resultado |
|---------|-----------|
| Rutas con `['as' => ...]` | ~201 declaraciones `as` en `app/Config/Routes.php`; **215** llamadas `$routes->get/post/...`. |
| Sin alias (activas) | `user/list` (duplicado de `users.list`, línea 31). `hr/.../get_contracts/(:num)` (línea 158). |
| Sin alias (intencional) | `/`, `example_user` (solo no producción), rutas comentadas legacy. |

**Recomendación:** Añadir `as` a `user/list` y `get_contracts` o eliminar alias duplicados si son redundantes.

---

## §4–§6 UI (Sidebar, DataTables)

| Control | Resultado |
|---------|-----------|
| Sidebar `url_is` | Implementado en `loggedin_sidebar.php` (revisión visual recomendada por módulo nuevo). |
| Guard `isDataTable` | **2 incumplimientos:** `app/Views/HR/contracts/templates_index.php`, `app/Views/Usuarios/usuarios_home.php`. |
| Inicializaciones DataTable | ~20 archivos con `.DataTable(`; mayoría con guard. |

---

## §7 Permisos / §8 Bitácora (mandatos)

| Control | Resultado |
|---------|-----------|
| Filtros `permission:` | 116 en rutas base; plugin NotasInternas con permiso en su `Routes.php`. |
| `logActivity` | 24+ archivos PHP con llamadas; mutaciones en plugins (NotasInternas) incluidas. |
| Cobertura 100 % | **No afirmada.** Revisar manualmente cada nuevo endpoint POST sin grep previo en PR. |

Referencia operativa: `agentic/AUDITORIA_ARQUITECTURA.md` (pilares 1 y 2).

---

## §9 MVC y transacciones

| Control | Resultado |
|---------|-----------|
| `transStart` en controladores | ✅ 0 coincidencias. |
| `Database::connect` / `db_connect` en controladores | Solo `AlertController` (patrón documentado §21 para detectores). |

---

## §10–§13 Componentes UI y navegación

| Control | Resultado |
|---------|-----------|
| Select2 `dropdownParent` | No auditado automáticamente; validar en PRs con modales nuevos. |
| Breadcrumbs | Patrón `Breadcrumb` en controladores base de módulos; no verificado en todos los métodos. |

---

## §12 Iconografía (FontAwesome vs Lucide)

| Control | Resultado |
|---------|-----------|
| Lucide en `app/Views/` | **14 archivos**, incl. `loggedin_sidebar.php`, `loggedin_topbar.php`, módulos Users/HR/Shield. |

**Recomendación:** Migración gradual a FontAwesome en shell global (sidebar/topbar) para alinear con §12 y evitar re-init en contenido AJAX.

---

## §14–§15 Vistas y flujo de datos

| Control | Resultado |
|---------|-----------|
| `extend($layout)` | Convención dominante en vistas activas bajo `app/Views/{Modulo}/`. |
| Vista legacy | Carpeta `app/Views/Usuarios/` (duplicado conceptual con `Users/`). |

---

## §16 DataTableTrait

| Control | Resultado |
|---------|-----------|
| Modelos con trait | 14 modelos (Financial, HR, Catalog, Organization, Users, etc.). |
| Controladores con `get_datatables` | Alineados con modelos listados; coherente con §5/§16. |

---

## §17–§19 Notificaciones, uploads, modelos

| Control | Resultado |
|---------|-----------|
| `notifyShow` | Estándar documentado; no contado exhaustivamente vs `alert(`. |
| Soft deletes / validationRules | Revisión por modelo en futuras PRs; §19 es normativo en código nuevo. |

---

## §20 Catálogos estáticos

Cumplimiento por diseño en `FinRecurringExpenseModel::SERVICE_TYPES` (referencia en doc). Sin hallazgos en esta pasada.

---

## §21 Alertas

| Control | Resultado |
|---------|-----------|
| Arquitectura descrita | Coherente con `AlertController`, `AlertService`, topbar polling. |
| Detectores con `db_connect` | Esperado en controlador según doc. |

---

## Plugins

| Control | Resultado |
|---------|-----------|
| Alineación §1–§21 | Plugin NotasInternas: rutas con permiso, controlador con `isAJAX` y bitácora (muestra). |
| Documentación | `agentic/ARQUITECTURA_PLUGIN_SYSTEM.md` v1.2 coherente con `DOCUMENTACION_TECNICA.md`. |

---

## Acciones sugeridas (prioridad)

1. **P2 — Rutas:** alias `as` en `user/list` y `get_contracts/(:num)`.
2. **P2 — DataTables:** añadir guard en `templates_index.php` y `usuarios_home.php` (o retirar vista legacy).
3. **P3 — §12:** plan de sustitución Lucide → FontAwesome en partials globales.
4. **P3 — Legacy:** deprecar `app/Views/Usuarios/` si ya no hay rutas activas.
5. **Proceso:** usar `.cursor/rules/` en Cursor y checklist §1–§21 en cada PR (`AUDITORIA_ARQUITECTURA.md`).

---

## Historial

| Fecha | Notas |
|-------|--------|
| 2026-11-18 | Auditoría inicial post-creación de project rules `.cursor/rules/`. |