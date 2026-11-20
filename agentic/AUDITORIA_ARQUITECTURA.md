# 📋 Auditoría de Arquitectura — ERP/CRM CodeIgniter 4

> **Rol de este archivo:** Punto central de control de calidad arquitectónica.  
> **Instrucción para agentes implementadores:** Antes de cerrar tu sesión de trabajo, copia la plantilla de la sección **"📝 PLANTILLA DE REPORTE"** y pégala al final del archivo, llenando cada campo con honestidad. El orquestador auditará el cumplimiento.  
> **Fuente de verdad:** `DOCUMENTACION_TECNICA.md` (§1–§21).

---

> [!IMPORTANT]
> ## ⚠️ DOS MANDATOS ABSOLUTOS — Sin excepciones ni N/A
>
> Todo agente que implemente código en este sistema debe cumplir OBLIGATORIAMENTE con los siguientes dos pilares, independientemente del módulo, del tamaño de la tarea o del modelo usado:
>
> ### 🔒 PILAR 1 — Permisos de Acceso (§7 Shield CI4)
> **Toda ruta que mutua datos o exponga información sensible DEBE tener un filtro de permiso Shield.**
> - En `Routes.php`: `'filter' => 'permission:modulo.accion'`
> - En Vistas: `auth()->user()->can('modulo.accion')` para ocultar botones/secciones
> - **El auditor rechazará cualquier endpoint sin permiso explícito.** La sesión sin filtro no es suficiente.
> - **Referencia:** Sección §7 de `DOCUMENTACION_TECNICA.md`
>
> ### 📝 PILAR 2 — Bitácora de Auditoría (§8 `UserActivityLogsModel`)
> **Todo movimiento que mute datos debe quedar grabado en la bitácora del sistema.**
> - Acciones obligatorias a registrar: CREATE, UPDATE, DELETE (físico o lógico), uploads de archivos, cambios de contraseña, cambios de permisos/roles.
> - Implementación: `(new \App\Models\Users\UserActivityLogsModel())->logActivity('tipo_accion', 'Descripción detallada con ID y nombre del registro afectado');`
> - **El auditor rechazará cualquier mutación sin entrada en bitácora.**
> - **Referencia:** Sección §8 de `DOCUMENTACION_TECNICA.md`

---

## 📑 Índice

1. [Tarjeta de Referencia Técnica Rápida](#1-tarjeta-de-referencia-técnica-rápida)
2. [Estado de Tareas Registradas](#2-estado-de-tareas-registradas)
3. [Evaluaciones del Auditor](#3-evaluaciones-del-auditor)
4. [Plantilla de Reporte para Agentes](#4-plantilla-de-reporte-para-agentes)
5. [Historial de Reportes de Agentes](#5-historial-de-reportes-de-agentes)

---

## 1. Tarjeta de Referencia Técnica Rápida

Esta tarjeta resume los **21 estándares** de `DOCUMENTACION_TECNICA.md`. Úsala como checklist al implementar cualquier módulo.

| § | Estándar | Regla clave | Incumplimiento Crítico |
|---|----------|-------------|------------------------|
| §1 | **AJAX — Validación backend** | Todo endpoint AJAX debe verificar `$this->request->isAJAX()` y lanzar `PageNotFoundException` si no lo es. | ❌ Exponer endpoints sin guard AJAX |
| §1 | **AJAX — Cabeceras Fetch** | Todo `fetch()` debe incluir `'X-Requested-With': 'XMLHttpRequest'`. | ❌ Fetch sin la cabecera |
| §2 | **CSRF — Input global** | `<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">` al inicio del `<main>`, SIEMPRE. | ❌ Vista sin CSRF hidden |
| §2 | **CSRF — Renovación DataTables** | Escuchar `xhr.dt` y actualizar `#csrf_token` con el nuevo hash del header. | ❌ No renovar en DataTables |
| §2 | **CSRF — Fetch mutations** | Inyectar token en **cabecera HTTP** (`csrf_header()`), no solo en el body. Capturar el nuevo token de la respuesta. | ❌ Token solo en body |
| §3 | **Rutas — Alias** | Toda ruta DEBE tener `['as' => 'modulo.accion']`. | ❌ URL hardcodeada |
| §3 | **Rutas — Verbos HTTP** | GET para consultas, POST para creación/edición/borrado. | ❌ POST para listados |
| §4 | **Sidebar activo** | Usar `url_is('ruta*')` en `loggedin_sidebar.php` para clases `active`/`collapsed`/`show`. | ❌ Sidebar sin activación dinámica |
| §5/§16 | **DataTableTrait** | Modelos con DataTables DEBEN incluir el trait, definir `$column_order` y `$column_search`. Sobrescribir `_get_datatables_query` para JOINs. | ❌ Paginación manual en controlador |
| §6 | **DataTables Guard** | Toda inicialización de tabla debe comenzar con `if ($.fn.dataTable.isDataTable('#id')) return;`. | ❌ Error de re-inicialización |
| 🔒 §7 | **Permisos Shield** ⚠️ MANDATORIO | Rutas sensibles con `'filter' => 'permission:...'`. Botones/UI con `auth()->user()->can('...')`. **Sin permiso = rechazado automático.** | ❌ Endpoint sin permiso |
| 📝 §8 | **Bitácora de Auditoría** ⚠️ MANDATORIO | Toda mutación (crear, editar, eliminar, subir archivos, cambio de contraseña/rol) DEBE registrarse via `UserActivityLogsModel->logActivity(tipo, 'Descripción con nombre e ID')`. **Sin log = rechazado automático.** | ❌ Operación sin bitácora |
| §9 | **MVC — Transacciones** | `$db->transStart()` / `$db->transComplete()` NUNCA en controladores. Solo en la capa Modelo usando `$this->db`. | ❌ Transacción en controlador |
| §9 | **MVC — DB en Controladores** | PROHIBIDO `\Config\Database::connect()` en controladores. | ❌ `connect()` en controlador |
| §10 | **Select2 en Modales** | `dropdownParent: $('#modalId')` obligatorio si el select está dentro de un modal/offcanvas. | ❌ Select2 sin `dropdownParent` |
| §11 | **Fechas** | BD: `YYYY-MM-DD`. UI: `d/m/Y`. Formularios: `<input type="date">`. | ❌ Fechas mezcladas |
| §12 | **Iconografía** | Solo FontAwesome `fas`/`far`. **PROHIBIDO** Lucide o cualquier otra librería JS-based en contenido AJAX/DataTables. | ❌ Lucide en AJAX/DataTables |
| §13 | **Breadcrumbs** | Usar `App\Libraries\Breadcrumb` en controlador. Pasar a `$this->viewData['breadcrumb']`. | ❌ Breadcrumb hardcodeado |
| §14 | **Layout** | Vistas con `<?php $this->extend($layout); ?>` y secciones `title`, `main`, `scripts`. | ❌ Vista sin extend |
| §15 | **Flujo de datos** | HTML: `$this->viewData` + `setViewSuccess()` + `renderLayout()`. AJAX: `$this->outputData` + `setOutputSuccess()`/`setOutputError()` + `csrf_hash()` en respuesta. | ❌ JSON manual sin outputData |
| §17 | **Notificaciones UI** | Usar `notifyShow(message, type)` para feedback AJAX. Nunca `alert()` nativo. | ❌ `alert()` nativo |
| §18 | **Uploads públicos** | `FCPATH . 'uploads/{modulo}/'`. Nombre aleatorio `getRandomName()`. Guardar ruta relativa en BD. **Excepción:** documentos privados (HR, financieros) van en `WRITEPATH/uploads/`. | ❌ Ruta absoluta en BD |
| §19 | **Modelos** | `$useSoftDeletes = true`, `$allowedFields` definidos, `$validationRules` en el modelo (NO en controlador). | ❌ Validación en controlador |
| §20 | **Catálogos estáticos** | Usar constantes en el Modelo (e.g. `SERVICE_TYPES`) en lugar de tablas de configuración para catálogos pequeños. | ❌ Tabla extra innecesaria |
| §21 | **Sistema de Alertas** | Nuevos detectores automáticos: método `_detect*()` privado en `AlertController`, integrado en `_detectAll()` con `_safeDetect()` y permiso Shield. Alertas manuales via `AlertService::dispatch()`. | ❌ Alerta hardcodeada en frontend |

---

## 2. Estado de Tareas Registradas

> Planes cerrados en `agentic/archivo/planes-cerrados/`. Índice: [INDICE.md](INDICE.md).  
> **Última depuración de reportes:** 2026-11-19.

| ID | Archivo | Módulo | Implementación | Notas |
|----|---------|--------|----------------|-------|
| 001 | `archivo/planes-cerrados/001_...` | Docs legales sucursales | ✅ Código | `OrgBranchModel` csf_path, etc. |
| 002 | `002_hr_advanced_incidents.md` | RRHH incidencias avanzadas | ❌ Pendiente | Spec viva |
| 003 | `archivo/planes-cerrados/003_*` | Proveedores (fases) | ✅ Código | Archivado |
| 004a | `archivo/planes-cerrados/004_sistema_alertas_*` | Alertas | ✅ Código | Ver doc §21 |
| 004b | `004_crm_clients.md` | CRM clientes | ✅ Código | `crm_*` + ventas + hooks |
| 005 | `archivo/planes-cerrados/005_*` | Transacciones MVC | ✅ Código | |
| 006–008 | `archivo/planes-cerrados/006–008_*` | OC / recurrentes | ✅ Código | |
| 009 | `archivo/planes-cerrados/009_*` | Bitácora global | ✅ Código | |
| 010 | `archivo/planes-cerrados/010_*` | Categorías proveedor | ✅ Código | |
| 011 | `archivo/planes-cerrados/011_*` | Nóminas | ✅ Código | |
| PLUGIN-000 | `plugin_000_...` (sin depurar) | Núcleo plugins | ✅ Código | Ver `AUDITORIA_PLUGIN_SYSTEM.md` |

**Leyenda:** ✅ en producción en rama feat · ❌ sin implementar · Specs plugin sin revisar en esta pasada.

---

## 3. Evaluaciones del Auditor

> Los reportes detallados REPORTE-008–016 y evaluaciones puntuales (2026-09/10) se **purgan** del documento activo.  
> **Copia archivada:** [archivo/auditorias/HISTORIAL_PURGADO_2026-11-19.md](archivo/auditorias/HISTORIAL_PURGADO_2026-11-19.md)  
> **Auditoría actual del codebase:** [AUDITORIA_CUMPLIMIENTO_DOC_TECNICA.md](AUDITORIA_CUMPLIMIENTO_DOC_TECNICA.md) (2026-11-18/19).  
> Los agentes implementadores **no** editan §3; usan §4 y pegan reportes nuevos en §5.

### Checklist de Auditoría por Módulo

#### Para cada módulo implementado, verificar:

```
MÓDULO: _______________
AGENTE: _______________
FECHA:  _______________

───────────────────────────────────────────────────
 🔒 BLOQUE 1 — SEGURIDAD Y TRAZABILIDAD (MANDATORIOS — NUNCA "N/A")
───────────────────────────────────────────────────
[🔒] §7  — PERMISOS: Cada ruta de mutación (POST/PUT/DELETE) tiene filtro 'permission:modulo.accion' en Routes.php
[🔒] §7  — PERMISOS: Cada ruta de consulta sensible tiene filtro de permiso o al menos 'session'
[🔒] §7  — PERMISOS: Botones y secciones de UI ocultados con auth()->user()->can('...') cuando aplica
[📝] §8  — BITÁCORA: CREATE de cualquier registro → logActivity('create_X', 'Creó X: {nombre} (ID: {id})')
[📝] §8  — BITÁCORA: UPDATE de cualquier registro → logActivity('update_X', 'Editó X: {nombre} (ID: {id})')
[📝] §8  — BITÁCORA: DELETE (lógico o físico) → logActivity('delete_X', 'Eliminó X: {nombre} (ID: {id})')
[📝] §8  — BITÁCORA: Upload de archivo → logActivity('upload_X', 'Subió archivo a X ID: {id}')
[📝] §8  — BITÁCORA: Cambio de contraseña / permiso / rol → logActivity('security_X', '...')
[📝] §8  — BITÁCORA: El modelo usado es UserActivityLogsModel->logActivity(), NO ningún otro
───────────────────────────────────────────────────
 BLOQUE 2 — ARQUITECTURA AJAX Y CSRF
───────────────────────────────────────────────────
[ ] §1  — Todos los endpoints AJAX tienen guard isAJAX() con PageNotFoundException
[ ] §1  — Todos los fetch() incluyen X-Requested-With: XMLHttpRequest
[ ] §2  — #csrf_token hidden presente en el <main> de cada vista
[ ] §2  — DataTables: escucha xhr.dt y renueva token con getResponseHeader() (NO headers.get)
[ ] §2  — Fetch mutations: CSRF en cabecera HTTP (csrf_header()) + captura token de respuesta
───────────────────────────────────────────────────
 BLOQUE 3 — RUTAS Y NAVEGACIÓN
───────────────────────────────────────────────────
[ ] §3  — Todas las rutas tienen alias ['as' => 'modulo.accion']
[ ] §3  — Verbos HTTP correctos (GET consultas, POST mutaciones)
[ ] §3  — CERO site_url() hardcodeados en JS/Vistas — solo route_to() o alias
[ ] §4  — Sidebar activo con url_is('ruta*') en loggedin_sidebar.php
[ ] §13 — Breadcrumb con App\Libraries\Breadcrumb en controlador
───────────────────────────────────────────────────
 BLOQUE 4 — MODELOS Y BASE DE DATOS
───────────────────────────────────────────────────
[ ] §9  — CERO instancias de \Config\Database::connect() en controladores
[ ] §9  — Transacciones multi-tabla con $this->db->transStart() SOLO en Modelos
[ ] §19 — $useSoftDeletes = true en modelos de datos críticos
[ ] §19 — $allowedFields definidos explícitamente en todos los modelos
[ ] §19 — $validationRules en el Modelo (NUNCA en el controlador)
[ ] §5  — DataTableTrait en modelos con paginación server-side
[ ] §5  — $column_order alineado exactamente con columnas JS (mismo número, mismo orden)
[ ] §6  — Guard if ($.fn.dataTable.isDataTable('#id')) return; en cada DataTable
───────────────────────────────────────────────────
 BLOQUE 5 — INTERFAZ Y EXPERIENCIA DE USUARIO
───────────────────────────────────────────────────
[ ] §10 — Select2 en modales/offcanvas con dropdownParent: $('#modalId')
[ ] §11 — BD: YYYY-MM-DD · UI: d/m/Y · Formularios: <input type="date">
[ ] §12 — SOLO FontAwesome fas/far. PROHIBIDO Lucide en contenido AJAX/DataTables
[ ] §14 — Vistas extienden layout maestro, secciones title/main/scripts (o pageFooterScripts)
[ ] §15 — HTML: $this->viewData + setViewSuccess() + renderLayout()
[ ] §15 — AJAX: $this->outputData + setOutputSuccess/Error() + csrf_hash() en respuesta
[ ] §17 — notifyShow(msg, type) para feedback AJAX. Sin alert() nativo
[ ] §18 — Uploads públicos: FCPATH/uploads/{modulo}/ · Privados: WRITEPATH/uploads/
[ ] §18 — Nombres: getRandomName() · BD guarda ruta relativa (nunca absoluta)
[ ] §21 — Si el módulo genera condiciones alertables: _detect*() en AlertController
[ ] §21 — Alertas manuales via AlertService::dispatch() (nunca SysNotificationModel)

RESULTADO: ✅ APROBADO / ⚠️ APROBADO CON OBSERVACIONES / ❌ RECHAZADO — REQUIERE FIX

> Si cualquier ítem del BLOQUE 1 (🔒📝) está sin marcar → resultado automático: ❌ RECHAZADO
```

---

## 4. Plantilla de Reporte para Agentes

> **Instrucción:** Copia el bloque a continuación, pégalo en la sección §5 y llena cada campo. No borres la plantilla original. Sé explícito sobre lo que implementaste y lo que NO pudo completarse.

```markdown
---

### REPORTE-[NNN]: [Nombre del módulo/tarea]

**Tarea ID:** [001 / 002 / 003 / etc.]
**Archivo de plan:** `agentic/[nombre_del_archivo].md`
**Agente / Modelo:** [nombre del modelo usado, ej. Claude Sonnet 4.6]
**Fecha de ejecución:** [YYYY-MM-DD]
**Sesión de chat:** [título breve de la sesión]

---

#### Archivos Creados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `app/Models/...` | Nuevo | ... |
| `app/Controllers/...` | Nuevo | ... |
| `app/Views/...` | Nuevo | ... |
| `app/Config/Routes.php` | Modificado | Se añadieron rutas: ... |

#### Migraciones Ejecutadas

| Archivo de Migración | Acción | Tablas Afectadas | ¿Corrió OK? |
|----------------------|--------|------------------|-------------|
| `...` | CREATE/ALTER | `...` | Sí / No |

#### 🔒 Sección Obligatoria — Permisos (§7) y Bitácora (§8)

> **MANDATORIO.** El auditor rechazará automáticamente el reporte si estas dos secciones están incompletas o en ❌.

**§7 — Mapa de permisos implementados:**

| Ruta / Acción | Tipo | Filtro en Routes.php | Guard can() en Vista |
|---------------|------|----------------------|----------------------|
| `[ruta]` | GET/POST | `permission:xxx` / `session` | Sí / No / N/A |
| `[ruta]` | GET/POST | `permission:xxx` / `session` | Sí / No / N/A |

**§8 — Mapa de entradas en bitácora:**

| Acción de negocio | Tipo logActivity | Descripción registrada |
|-------------------|-----------------|------------------------|
| Crear [registro] | `'create_x'` | `'Creó X: {nombre} (ID: {id})'` |
| Editar [registro] | `'update_x'` | `'Editó X: {nombre} (ID: {id})'` |
| Eliminar [registro] | `'delete_x'` | `'Eliminó X: {nombre} (ID: {id})'` |
| Subir archivo | `'upload_x'` | `'Subió archivo a X ID: {id}'` |

> Si NO implementaste logActivity en alguna mutación, descríbelo como deuda técnica en la sección siguiente.

---

#### Cumplimiento de Estándares Técnicos

| § | Estándar | ¿Cumplido? | Evidencia / Notas |
|---|----------|------------|-------------------|
| §1 | Guard AJAX en endpoints | ✅ / ⚠️ / ❌ | [descripción] |
| §2 | CSRF renovación DataTables | ✅ / ⚠️ / ❌ | [descripción] |
| §2 | CSRF en header Fetch | ✅ / ⚠️ / ❌ | [descripción] |
| §3 | Rutas con alias | ✅ / ⚠️ / ❌ | [descripción] |
| §5 | DataTableTrait + column_order | ✅ / ⚠️ / ❌ | [descripción] |
| §6 | Guard DataTable isDataTable | ✅ / ⚠️ / ❌ | [descripción] |
| **§7** | **Filtros de permiso Shield** | **✅ / ⚠️ / ❌** | **[Ver tabla arriba — MANDATORIO]** |
| **§8** | **Bitácora de actividad** | **✅ / ⚠️ / ❌** | **[Ver tabla arriba — MANDATORIO]** |
| §9 | Transacciones en Modelo | ✅ / ⚠️ / ❌ | [descripción] |
| §10 | Select2 dropdownParent | ✅ / ⚠️ / ❌ | N/A si no hay Select2 en modales |
| §12 | Solo FontAwesome | ✅ / ⚠️ / ❌ | [descripción] |
| §13 | Breadcrumb | ✅ / ⚠️ / ❌ | [descripción] |
| §14 | Extend layout maestro | ✅ / ⚠️ / ❌ | [descripción] |
| §15 | viewData / outputData | ✅ / ⚠️ / ❌ | [descripción] |
| §17 | notifyShow() | ✅ / ⚠️ / ❌ | [descripción] |
| §18 | Uploads organizados | ✅ / ⚠️ / ❌ | N/A si no hay uploads |
| §19 | Soft deletes + allowedFields + validationRules | ✅ / ⚠️ / ❌ | [descripción] |

#### Deuda Técnica y Pendientes

> Lista aquí todo lo que NO se pudo completar, decisiones de diseño no estándar o desvíos conscientes de la arquitectura.

- [ ] [Descripción del pendiente o deuda]
- [ ] [Otro pendiente]

#### Notas para el Siguiente Agente

> Contexto importante que el siguiente agente DEBE saber antes de continuar en este módulo.

- [Nota 1]
- [Nota 2]

---
```

---

## 4. Plantilla de Reporte para Agentes

> **Instrucción:** Copia el bloque a continuación, pégalo en la sección §5 y llena cada campo. No borres la plantilla original. Sé explícito sobre lo que implementaste y lo que NO pudo completarse.

```markdown
---

### REPORTE-[NNN]: [Nombre del módulo/tarea]

**Tarea ID:** [001 / 002 / 003 / etc.]
**Archivo de plan:** `agentic/[nombre_del_archivo].md`
**Agente / Modelo:** [nombre del modelo usado, ej. Claude Sonnet 4.6]
**Fecha de ejecución:** [YYYY-MM-DD]
**Sesión de chat:** [título breve de la sesión]

---

#### Archivos Creados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `app/Models/...` | Nuevo | ... |
| `app/Controllers/...` | Nuevo | ... |
| `app/Views/...` | Nuevo | ... |
| `app/Config/Routes.php` | Modificado | Se añadieron rutas: ... |

#### Migraciones Ejecutadas

| Archivo de Migración | Acción | Tablas Afectadas | ¿Corrió OK? |
|----------------------|--------|------------------|-------------|
| `...` | CREATE/ALTER | `...` | Sí / No |

#### 🔒 Sección Obligatoria — Permisos (§7) y Bitácora (§8)

> **MANDATORIO.** El auditor rechazará automáticamente el reporte si estas dos secciones están incompletas o en ❌.

**§7 — Mapa de permisos implementados:**

| Ruta / Acción | Tipo | Filtro en Routes.php | Guard can() en Vista |
|---------------|------|----------------------|----------------------|
| `[ruta]` | GET/POST | `permission:xxx` / `session` | Sí / No / N/A |
| `[ruta]` | GET/POST | `permission:xxx` / `session` | Sí / No / N/A |

**§8 — Mapa de entradas en bitácora:**

| Acción de negocio | Tipo logActivity | Descripción registrada |
|-------------------|-----------------|------------------------|
| Crear [registro] | `'create_x'` | `'Creó X: {nombre} (ID: {id})'` |
| Editar [registro] | `'update_x'` | `'Editó X: {nombre} (ID: {id})'` |
| Eliminar [registro] | `'delete_x'` | `'Eliminó X: {nombre} (ID: {id})'` |
| Subir archivo | `'upload_x'` | `'Subió archivo a X ID: {id}'` |

> Si NO implementaste logActivity en alguna mutación, descríbelo como deuda técnica en la sección siguiente.

---

#### Cumplimiento de Estándares Técnicos

| § | Estándar | ¿Cumplido? | Evidencia / Notas |
|---|----------|------------|-------------------|
| §1 | Guard AJAX en endpoints | ✅ / ⚠️ / ❌ | [descripción] |
| §2 | CSRF renovación DataTables | ✅ / ⚠️ / ❌ | [descripción] |
| §2 | CSRF en header Fetch | ✅ / ⚠️ / ❌ | [descripción] |
| §3 | Rutas con alias | ✅ / ⚠️ / ❌ | [descripción] |
| §5 | DataTableTrait + column_order | ✅ / ⚠️ / ❌ | [descripción] |
| §6 | Guard DataTable isDataTable | ✅ / ⚠️ / ❌ | [descripción] |
| **§7** | **Filtros de permiso Shield** | **✅ / ⚠️ / ❌** | **[Ver tabla arriba — MANDATORIO]** |
| **§8** | **Bitácora de actividad** | **✅ / ⚠️ / ❌** | **[Ver tabla arriba — MANDATORIO]** |
| §9 | Transacciones en Modelo | ✅ / ⚠️ / ❌ | [descripción] |
| §10 | Select2 dropdownParent | ✅ / ⚠️ / ❌ | N/A si no hay Select2 en modales |
| §12 | Solo FontAwesome | ✅ / ⚠️ / ❌ | [descripción] |
| §13 | Breadcrumb | ✅ / ⚠️ / ❌ | [descripción] |
| §14 | Extend layout maestro | ✅ / ⚠️ / ❌ | [descripción] |
| §15 | viewData / outputData | ✅ / ⚠️ / ❌ | [descripción] |
| §17 | notifyShow() | ✅ / ⚠️ / ❌ | [descripción] |
| §18 | Uploads organizados | ✅ / ⚠️ / ❌ | N/A si no hay uploads |
| §19 | Soft deletes + allowedFields + validationRules | ✅ / ⚠️ / ❌ | [descripción] |

#### Deuda Técnica y Pendientes

> Lista aquí todo lo que NO se pudo completar, decisiones de diseño no estándar o desvíos conscientes de la arquitectura.

- [ ] [Descripción del pendiente o deuda]
- [ ] [Otro pendiente]

#### Notas para el Siguiente Agente

> Contexto importante que el siguiente agente DEBE saber antes de continuar en este módulo.

- [Nota 1]
- [Nota 2]

---
```

---

## 5. Historial de Reportes de Agentes

> Vacío tras purga 2026-11-19. Pega aquí nuevos reportes con la plantilla de §4.

*(Sin reportes activos.)*

