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

| ID | Archivo Agentic | Módulo | Estado Plan | Reporte Agente | Auditoría |
|----|----------------|--------|-------------|----------------|-----------|
| 001 | `001_branch_legal_documents.md` | Documentos Legales de Sucursales | ✅ Definido | ✅ REPORTE-012 | ❌ Pendiente de auditoría |
| 002 | `002_hr_advanced_incidents.md` | RRHH — Incidencias Avanzadas | ✅ Definido | ✅ FIX-A aplicado (2026-09-18) | ✅ §9 §21 §8 §15 verificados |
| 003a | `003_catalog_suppliers.md` | Proveedores — Plan inicial | ✅ Definido | ✅ REPORTE-009 | ✅ Auditado |
| 003b | `003_catalog_suppliers_part1_models.md` | Proveedores — Modelos/BD | ✅ Definido | ✅ REPORTE-009 | ✅ Auditado |
| 003c | `003_catalog_suppliers_part2_controllers.md` | Proveedores — Controladores | ✅ Definido | ✅ REPORTE-009 | ✅ Auditado |
| 003d | `003_catalog_suppliers_part3_views.md` | Proveedores — Vistas/UI | ✅ Definido | ✅ REPORTE-009 + FIX-D aplicado (2026-09-18) | ✅ 0 site_url — 26 route_to verificados |
| 004a | `004_sistema_alertas_notificaciones.md` | Sistema de Alertas | ✅ Definido | ✅ AlertService + AlertController implementados | ✅ Auditado |
| 004b | `004_crm_clients.md` | CRM — Clientes | ✅ Definido | ❌ No implementado | ❌ Tarea abierta |
| 005 | `005_refactor_db_transactions.md` | Refactor Transacciones MVC | ✅ Definido | ✅ FIX-A aplicado — 0 `Database::connect()` en Controllers | ✅ Verificado con grep |
| 006 | `006_suppliers_po_docs_email_recurring.md` | Proveedores — OC/Docs/Email/Recurrentes | ✅ Definido | ✅ REPORTE-009 | ✅ Auditado |
| 007 | `007_fix_po_recurring_audit.md` | Fixes de Auditoría OC/Recurrentes | ✅ Definido | ✅ REPORTE-009 (refreshCsrf dual, column_order OK) | ✅ Verificado en disco |
| 010 | `010_supplier_categories_crud.md` | Proveedores — Categorías CRUD | ✅ Definido | ✅ REPORTE-014 | ❌ Pendiente de auditoría |
| 011 | `011_hr_payroll_nominas.md` | RRHH — Nóminas (Payroll) | ✅ Definido | ✅ REPORTE-015 | ❌ Pendiente de auditoría |
| HR-v | `2026-08-18_ALTER_hr_contracts_versioning.md` | RRHH — Versionado Contratos (Esquema) | ✅ Definido | ✅ REPORTE-010 | ✅ FIX-B y FIX-C aplicados (2026-09-18) |
| HR-f | `2026-08-18_ALTER_hr_contracts_versioning_fix.md` | RRHH — Fix Versionado Contratos | ✅ Definido | ✅ REPORTE-010 | ✅ Re-audit Go (2026-09-18) |
| DB-po | `2026-08-18_tables_po_documents_email_recurring.md` | OC/Docs/Email/Recurrentes (Esquema) | ✅ Definido | ✅ 6 migraciones en disco (batch 24–27) | ✅ Verificado en disco |

**Leyenda:** ✅ Completado · ⚠️ Parcial · ❌ Incumplido · ⬜ Pendiente

---

## 3. Evaluaciones del Auditor

> Esta sección es mantenida por el agente auditor. Los agentes implementadores **NO** deben editarla directamente; deben llenar la sección §5.

### 🔎 AUDITORÍA REAL — Verificación en Código (2026-09-18)

> Resultados de inspección directa de archivos PHP. Esto **reemplaza** los riesgos preliminares del análisis estático.

#### ✅ RESUELTO (2026-09-18) — FIX-A: `WorkerIncidenceController.php` — §9, §21, §8, §15

**Verificado en disco:**
- `Database::connect()` en controlador → **0 ocurrencias** ✅
- `transStart/transComplete` en controlador → **0 ocurrencias** ✅
- `SysNotificationModel` en controlador → **0 ocurrencias** ✅
- `recordIncidence()` delega a `HrIncidenceModel::recordIncidence()` (línea 87) ✅
- `updateIncidenceStatus()` delega al modelo (línea 95) ✅
- `HrIncidenceModel` contiene `$this->db->transStart/Complete` (líneas 42/63 y 78/98) ✅
- `AlertService::dispatch()` en `HrIncidenceModel` (línea 114) — reemplaza SysNotificationModel ✅
- `logActivity('create_incidence'/'update_incidence')` en el controlador (§8) — líneas 40 y 68 ✅
- `$this->outputData['csrf'] = csrf_hash()` en respuestas JSON (§15) — líneas 49 y 77 ✅

---

#### ✅ RESUELTO (2026-09-18) — FIX-B: `WorkerController` — §1 guard `isAJAX()`

**Verificado en disco:**
- `create()` línea 339: `isAJAX()` ✅
- `update()` línea 513: `isAJAX()` ✅
- Sin `is('post')` sueltos — otros métodos usan `isAJAX() || is('post')` (correcto) ✅

---

#### ✅ RESUELTO (2026-09-18) — FIX-C: `HrWorkerScheduleModel::assignSchedule()` — §9 transacción atómica

**Verificado en disco:**
- `$this->db->transStart()` línea 76 ✅
- delete + insert envueltos en el bloque de transacción ✅
- `$this->db->transComplete()` línea 83 ✅
- `$this->db->transStatus()` línea 85 — retorna `bool` correctamente ✅
- Solo el path "turno diferente" usa transacción; el path "primer turno" hace insert simple (correcto) ✅

---

#### ✅ RESUELTO (2026-09-18) — FIX-D: `suppliers_index.php` — §3 URLs con `route_to()`

**Verificado en disco:**
- `site_url(` → **0 ocurrencias** ✅
- `route_to(` → **26 ocurrencias** (todos los fetch usan aliases nombrados) ✅
- **Bonus §9:** `grep Database::connect() en app/Controllers/` → **0 ocurrencias en todo el proyecto** ✅

---

#### ✅ VERIFICADO CORRECTO — Elementos reportados como ⚠️ que SÍ están bien en disco

| Elemento | Estado real |
|----------|-------------|
| `refreshCsrf()` en `purchase_orders_index.php` | ✅ Implementación dual correcta (Fetch + jqXHR) |
| `column_order` en `FinPurchaseOrderModel` | ✅ 9 entradas alineadas con columnas HTML |
| `column_order` en `FinExpenseModel` | ✅ 9 entradas alineadas |
| `column_order` en `FinRecurringExpenseModel` | ✅ 9 entradas alineadas |
| `AlertService` en `Libraries/` vs `Services/` | ✅ Funcional, ruta distinta al plan pero aceptable |
| Migración `120500` (category_id nullable + índices) | ✅ Existe en disco, batch 26 según REPORTE-009 |

---

#### 🔒📝 RETROAUDITORÍA §7/§8 — Estado en módulos ya implementados

> Verificación en código de los dos mandatos absolutos en todo lo que ya existe en disco.

| Módulo / Controlador | §7 Permisos ¿OK? | §8 Bitácora ¿OK? | Observación |
|----------------------|-----------------|-----------------|-------------|
| `SupplierController` | ✅ `catalog.manage-suppliers` en rutas mutación | ✅ `logActivity` en create/update/delete | OK |
| `PurchaseOrderController` | ✅ `catalog.manage-suppliers` en mutaciones | ✅ `logActivity` en store/update | OK |
| `PurchaseOrderDocumentController` | ✅ `catalog.po-documents` en upload/delete | ✅ `logActivity` en upload/delete docs | OK |
| `RecurringExpenseController` | ✅ `catalog.recurring-manage` en generate/pay | ✅ `logActivity` en generate/pay/store_template | OK |
| `WorkerController` | ✅ `hr.create`/`hr.edit` en rutas | ✅ `logActivity` en create/update | OK |
| `WorkerContractController` | ✅ `hr.access` en grupo | ✅ `logActivity` con versión real `v{n}` | OK |
| `CatalogController` (turnos HR) | ⚠️ Solo `hr.access`, sin granular para mutaciones | ✅ `logActivity` en save/delete turno | **Riesgo §7 bajo — cualquier usuario HR puede mutar turnos** |
| `WorkerIncidenceController` | ✅ `isAJAX()` en métodos públicos | ✅ `logActivity` en create/update + `AlertService` reemplaza legacy | ✅ FIX-A aplicado (2026-09-18) |
| `AlertController` | ✅ Solo sesión (alertas propias del usuario) | ✅ No muta datos críticos — solo marca leídas | OK |
| `OrganizationController` | ⬜ No verificado | ⬜ No verificado | Pendiente auditoría |

---

#### ❌ NO IMPLEMENTADO — Tareas sin código en disco

| Task | Evidencia |
|------|-----------|
| **001 — Branch Legal Docs** | `grep csf_path app/Models/Organization/OrgBranchModel.php` → 0 resultados. Sin migración de columnas. Sin UI en `branch_form.php`. |
| **004b — CRM Clientes** | `ls app/Controllers/CRM/` → directorio no existe. Sin tablas `crm_*` en migraciones. |

---

### Puntos de Riesgo Arquitectónico Identificados (Análisis Estático de Planes)

Basado en la revisión de los planes de tarea, se identifican los siguientes riesgos antes de la implementación:

#### 🔴 Riesgo CRÍTICO — Task 005: Refactor Transacciones
El `005_refactor_db_transactions.md` exige eliminar `\Config\Database::connect()` de **todos** los controladores. Si algún agente previo (001–004) implementó transacciones en el controlador, la deuda técnica ya existe en código. El agente que ejecute Task 005 debe auditar activamente los controladores, no asumir que están limpios.

#### 🟡 Riesgo MEDIO — Task 003/006: CSRF en DataTables (§2)
Task 007 ya detectó un bug real: `refreshCsrf(r)` usaba `r.headers.get(...)` (API Fetch), pero el evento `xhr.dt` de DataTables pasa un `jqXHR` con `getResponseHeader()`. El helper incorrecto causaba errores 403 en recarga. **Corrección obligatoria en todos los DataTables del módulo de proveedores y recurrentes.**

#### 🟡 Riesgo MEDIO — Task 006: `column_order` y columna extra (§5/§16)
Task 007 detectó que `FinPurchaseOrderModel`, `FinExpenseModel` y `FinRecurringExpenseModel` tenían `column_order` desalineados del HTML (se agregó columna Sucursal sin actualizar el array). Esto rompe el ordenamiento silenciosamente. Verificar que el número de columnas JS coincida con `column_order`.

#### 🟡 Riesgo MEDIO — Task 006/007: `category_id` nullable en `fin_expenses`
El scheduler `RecurringExpenseScheduler` copia `category_id` de la plantilla (que puede ser `NULL`), pero la columna original tenía `NOT NULL`. Task 007 exige la migración `2026-08-18-120500` para corregirlo. **Verificar que la migración efectivamente corrió.**

#### 🟡 Riesgo MEDIO — Task 004a: Integración CHISA → CI4
`SISTEMA_ALERTA_NOTIFICACIONES.MD` documenta el sistema legacy (CI3, sin filtrado por usuario, sin persistencia, polling cada 2 min). El `004_sistema_alertas_notificaciones.md` propone reemplazarlo con el sistema CI4 (`AlertService` + `sys_alerts` + `sys_alert_user`). Verificar que el frontend NO mezcle el sistema viejo con el nuevo.

#### 🟢 Riesgo BAJO — Tasks 001/002: Uploads
Task 001 (documentos de sucursal) usa `FCPATH . 'uploads/organization/'` (correcto, §18 público). Task 002 (justificaciones HR) usa `WRITEPATH . 'uploads/hr/justifications/'` (correcto, documentos privados). Verificar consistencia.

---

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

## 5. Historial de Reportes de Agentes

> Los agentes pegan aquí sus reportes completados usando la plantilla de §4.

---

### REPORTE-008: Proveedores — cierre de gaps OC / documentos / email / recurrentes

**Tarea ID:** Proveedores-Gaps-008
**Archivo de plan:** `agentic/008_close_po_recurring_gaps.md` (cierre de gaps post-007; el plan original es `agentic/006_suppliers_po_docs_email_recurring.md` y los fixes `agentic/007_fix_po_recurring_audit.md`)
**Agente / Modelo:** Cursor Grok 4.6
**Fecha de ejecución:** 2026-09-18
**Sesión de chat:** Cierre de pendientes del módulo proveedores (plantillas, calendario, montos, adjuntos múltiples, SMTP vía `.env`, restore OC) + resumen para auditoría

> Alcance de **este** agente: implementación del cierre de gaps (008) sobre código ya existente de las tasks 006/007 (otro implementador). No se reescribieron migraciones. No se ejecutó `financial:generate-recurring` (muta `fin_expenses`).

---

#### Archivos Creados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `agentic/008_close_po_recurring_gaps.md` | Nuevo | Brief de auditoría del cierre de gaps (rutas, checklist, pendientes de operador). |

#### Archivos Creados/Modificados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `app/Config/Email.php` | Modificado | Constructor lee `email.*` desde `.env`; si hay `SMTPHost`+`SMTPUser` pasa a `smtp`. Sin credenciales en código. |
| `app/Config/Routes.php` | Modificado | Rutas nuevas: `templates.get/update/toggle`, `recurring.cancel`, `recurring.reschedule`, `recurring.documents.delete`. |
| `env` | Modificado | Bloque comentado EMAIL/SMTP de ejemplo. |
| `.env` | Modificado | Mismos placeholders SMTP **comentados**. No se escribió password. |
| `app/Controllers/Financial/RecurringExpenseController.php` | Modificado | CRUD plantillas, cancelar/reprogramar, subida múltiple, borrar comprobante, stats en respuestas, `getDetail` en offcanvas. |
| `app/Controllers/Financial/PurchaseOrderController.php` | Modificado | Botón restaurar en DataTable; `count_all` con papelera; filtro `filter_deleted`. |
| `app/Controllers/Financial/PurchaseOrderDocumentController.php` | Modificado | Upload múltiple (`getFileMultiple`) + helper `_collectUploadedFiles`. |
| `app/Controllers/System/AlertController.php` | Modificado | Detector `_detectDueSoonRecurringExpenses` integrado en `_detectAll()` con `_safeDetect`. |
| `app/Models/Financial/FinExpenseModel.php` | Modificado | Stats SUM (montos + conteos), filtros DataTable, `getDetail`, `cancelOccurrence`, `rescheduleOccurrence`, `countDueSoonRecurring`. |
| `app/Models/Financial/FinExpenseDocumentModel.php` | Modificado | `softDeleteDocument()`. |
| `app/Models/Financial/FinPurchaseOrderModel.php` | Modificado | Papelera (`withDeleted` + `filter_deleted`); `count_all` con `only_deleted`. |
| `app/Models/Financial/FinRecurringExpenseModel.php` | Modificado | Filtros plantilla; `updateForSupplier` acepta `supplier_id`. |
| `app/Commands/Financial/GenerateRecurringExpenses.php` | Modificado | `$usage` / `$arguments` / `$options` del spark. |
| `app/Services/AppMailer.php` | Modificado | Comentario: SMTP desde `email.*` en `.env`. |
| `app/Views/Financial/Recurring/recurring_index.php` | Modificado | Cards en montos, filtros, calendar `eventClick`, editar/toggle plantilla, cancelar/reprogramar, upload múltiple, borrar docs, datos de proveedor/servicio en offcanvas. |
| `app/Views/Financial/PurchaseOrders/purchase_orders_index.php` | Modificado | Papelera, restore, `?open=`/`?tab=`, upload múltiple, TinyMCE cdnjs. |
| `app/Views/Financial/Suppliers/suppliers_index.php` | Modificado | Tab OC: botones a documentos/correo (`?open=` / `?tab=emails`). |

Verificados existentes (no creados en esta sesión; pertenecen a 006/007): `RecurringExpenseController.php`, `PurchaseOrderDocumentController.php`, `AppMailer.php`, `RecurringExpenseScheduler.php`, `GenerateRecurringExpenses.php`, vistas recurring/PO, modelos de documentos/email/gastos.

---

#### Migraciones Ejecutadas

| Archivo de Migración | Acción | Tablas Afectadas | ¿Corrió OK? |
|----------------------|--------|------------------|-------------|
| — | N/A | Esta sesión **no** creó ni ejecutó migraciones. | N/A |

Esquema ya presente (tasks 006/007, **no** corrido por este agente; archivos verificados en disco):

| Archivo de Migración | Acción | Tablas Afectadas | ¿Corrió OK? |
|----------------------|--------|------------------|-------------|
| `app/Database/Migrations/Accounting/2026-08-18-120000_CreateFinPurchaseOrderDocuments.php` | CREATE | `fin_purchase_order_documents` | No verificado en esta sesión |
| `app/Database/Migrations/Accounting/2026-08-18-120100_CreateFinPurchaseOrderEmailLogs.php` | CREATE | `fin_purchase_order_email_logs`, `fin_po_email_log_documents` | No verificado en esta sesión |
| `app/Database/Migrations/Accounting/2026-08-18-120200_AlterFinRecurringExpensesForCalendar.php` | ALTER | `fin_recurring_expenses` | No verificado en esta sesión |
| `app/Database/Migrations/Accounting/2026-08-18-120300_AlterFinExpensesForRecurringOccurrences.php` | ALTER | `fin_expenses` | No verificado en esta sesión |
| `app/Database/Migrations/Accounting/2026-08-18-120400_CreateFinExpenseDocuments.php` | CREATE | `fin_expense_documents` | No verificado en esta sesión |
| `app/Database/Migrations/Accounting/2026-08-18-120500_FixFinExpensesRecurringIndexes.php` | ALTER | `fin_expenses` (`category_id` nullable, UNIQUE periodo, índices) | No verificado en esta sesión; 007 reportó que sí corrió |

---

#### Cumplimiento de Estándares Técnicos

| § | Estándar | ¿Cumplido? | Evidencia / Notas |
|---|----------|------------|-------------------|
| §1 | Guard AJAX en endpoints | ✅ | Mutaciones y GET AJAX de recurring/PO docs usan `isAJAX()` + `PageNotFoundException`. **Excepción consciente:** `download_document` / `po.download` son GET de archivo (no AJAX), igual que RH. |
| §1 | Cabecera Fetch `X-Requested-With` | ✅ | Calendar, offcanvas, uploads, pay/cancel/reschedule, templates, PO restore y email incluyen `XMLHttpRequest`. |
| §2 | CSRF input global | ✅ | `#csrf_token` al inicio de la sección `main` en `recurring_index.php` y `purchase_orders_index.php`. |
| §2 | CSRF renovación DataTables | ✅ | `xhr.dt` + `refreshCsrf(xhr)` dual (`headers.get` Fetch y `getResponseHeader` jqXHR). Fix de 007 conservado. |
| §2 | CSRF en header Fetch | ✅ | POST de mutación inyectan `'<?= csrf_header() ?>': getCsrf()`. |
| §3 | Rutas con alias | ✅ | Todas las rutas nuevas tienen `as`. GET consulta / POST mutación. |
| §3 | Verbos HTTP | ✅ | `list_ajax` es POST (DataTables). Downloads GET. |
| §4 | Sidebar activo | ✅ | No se tocó en esta sesión. Ya existe `url_is('nat/suppliers/recurring*')` y `purchase-orders*` en `loggedin_sidebar.php`. |
| §5/§16 | DataTableTrait + column_order | ✅ | `FinExpenseModel`, `FinRecurringExpenseModel`, `FinPurchaseOrderModel` usan trait + `$datatableConfig`. 9 columnas HTML = 9 entradas `column_order` (última `null` = acciones). Filtros extra en `_get_datatables_query`. |
| §6 | Guard DataTable isDataTable | ✅ | Gastos, plantillas y OC: `if ($.fn.dataTable.isDataTable('#...')) return;`. |
| §7 | Filtros de permiso Shield | ✅ | Mutaciones nuevas con `catalog.recurring-manage` / `catalog.po-documents` / `catalog.manage-suppliers`. UI con `can()`. GET list/get/download quedan en el grupo `catalog.access` (sesión), igual que el resto del módulo. |
| §8 | Bitácora de actividad | ✅ | `logActivity` en generate, pay, upload/delete docs, store/update/toggle template, cancel, reschedule. PO restore/upload ya lo tenían. |
| §9 | Transacciones en Modelo | ✅ | Cero `transStart` / `Database::connect()` en los tres controladores Financial tocados. Uploads transaccionan en modelos; el scheduler transacciona en `RecurringExpenseScheduler` (Service). |
| §10 | Select2 dropdownParent | ✅ | `#tpl-supplier-id` → `$('#modalNewTemplate')`; OC `#po-supplier-select` → `$('#modalGeneratePO')`. |
| §11 | Fechas | ✅ | BD ISO; UI `d/m/Y` / `formatDate`; formularios `<input type="date">` y `<input type="month">`. |
| §12 | Solo FontAwesome | ✅ | `fas` en vistas/acciones DataTable. Sin Lucide. |
| §13 | Breadcrumb | ✅ | `RecurringExpenseController::index` usa `Breadcrumb` + `viewData['breadcrumb']`. No se cambió el de OC. |
| §14 | Extend layout maestro | ⚠️ | Vistas extienden `Layouts/user_loggedin_layout` y usan secciones `title` / `main` / `pageFooterScripts`. El estándar §14 nombra `scripts`; el layout real renderiza `pageFooterScripts` (convención del proyecto). |
| §15 | viewData / outputData | ⚠️ | HTML: `setViewSuccess` + `renderLayout`. Mutaciones AJAX: `setOutputSuccess/Error` + `csrf` en JSON. **Desvío:** `list_ajax` (gastos/plantillas/OC) devuelve el protocolo DataTables (`draw/recordsTotal/data`) sin `$outputData` — patrón ya usado en el ERP. GET AJAX (`get_template`, `list_documents`, `calendar_ajax`) no meten `csrf` en el JSON; el token se renueva por header. |
| §17 | notifyShow() | ✅ | Feedback AJAX con `notifyShow`. No hay `alert()` nativo. Sí hay `confirm()` nativo (cancelar/borrar/restaurar), igual que proveedores/RH. |
| §18 | Uploads organizados | ✅ | Excepción §18 documentos financieros: `WRITEPATH/uploads/financial/po/{id}/` y `.../expenses/{id}/`. `getRandomName()`, ruta relativa en BD. No se usa `FCPATH`. |
| §19 | Soft deletes + allowedFields + validationRules | ✅ | Modelos de gasto/docs/OC/plantilla: `$useSoftDeletes`, `$allowedFields`, reglas en modelo. Cancel/reschedule validan estatus en el modelo. |
| §20 | Catálogos estáticos | ✅ | No se tocó `SERVICE_TYPES`; el filtro de tipo reutiliza `getServiceTypeOptions()`. |
| §21 | Sistema de Alertas | ✅ | `_detectDueSoonRecurringExpenses()` + `_safeDetect` bajo `catalog.access`. Overlay de vencidos ya existía. No se usó el sistema legacy CHISA. |

---

#### Deuda Técnica y Pendientes

- [ ] **SMTP operador:** `protocol` sigue en `mail` hasta que se descomenten y llenen `email.SMTPHost/User/Pass` en `.env`. No hay envío real verificado.
- [ ] **Cron no instalado:** el spark `financial:generate-recurring` existe; no se programó crontab en el SO. El operador debe: `0 6 * * * cd /home/admin/domains/sandbox.especialistashosting.com && php spark financial:generate-recurring`.
- [ ] **UI no verificada en navegador:** `https://sandbox.especialistashosting.com/nat/suppliers` responde HTTP 401 (Basic Auth). Esta sesión no pudo ejercer flujos click/type.
- [ ] **Migración 120500 no re-verificada** contra el schema live (si `category_id` sigue NOT NULL, el scheduler vuelve a fallar).
- [ ] **§15 DataTables:** `list_ajax` no usa `$outputData` (desvío heredado, no introducido como protocolo nuevo).
- [ ] **Filtros de plantillas** reutilizan los `<select>` del tab Vencimientos (`filter-exp-supplier` etc.); no hay filtros propios visibles en el tab Plantillas.
- [ ] **Docs/email desde perfil proveedor** no embeben el offcanvas: redirigen a `suppliers.po.index?open={id}`. Es deliberado para no duplicar el offcanvas.
- [ ] **Ledgers / folios / parseo CFDI:** fuera de alcance a propósito. XML inbound sigue siendo archivo histórico.
- [ ] **`confirm()` nativo** en borrados/cancelaciones (no viola la letra de §17 que prohíbe `alert()`, pero no usa un modal AppStack).

---

#### Notas para el Siguiente Agente

- Permisos a reutilizar, no inventar: `catalog.po-documents`, `catalog.po-send-email`, `catalog.recurring-manage`, `catalog.manage-suppliers`. El grupo de rutas vive bajo `permission:catalog.access`.
- Dos canales de documentos de OC: `inbound_archive` (facturas PDF/XML/imagen, **nunca** se adjuntan al mail) vs `outbound` (comprobantes/aclaraciones, seleccionables en el compose).
- Plantillas = `fin_recurring_expenses`; ocurrencias = `fin_expenses` con `recurring_expense_id` + `period_label` UNIQUE. Cancelar un periodo no desactiva la plantilla.
- `$this->forge->addKey()` **después** de `addColumn()` no crea índices; 007 usó SQL crudo en 120500. No repetir el antipatrón.
- CSRF DataTables: nunca usar `r.headers.get` sobre el `jqXHR` de `xhr.dt`.
- Uploads financieros en `WRITEPATH`, no en `public_html`.
- No ejecutar `php spark financial:generate-recurring` en auditoría a menos que el usuario lo pida: inserta filas reales.
- Brief paralelo: `agentic/008_close_po_recurring_gaps.md`.

---

### REPORTE-009: Proveedores — OC, documentos, email y servicios recurrentes (Task 006)

**Tarea ID:** Proveedores-006-OC-Docs-Email-Recurrentes  
**Archivo de plan:** `agentic/006_suppliers_po_docs_email_recurring.md` (esquema: `agentic/2026-08-18_tables_po_documents_email_recurring.md`)  
**Agente / Modelo:** Composer (agente implementador, sesión de arquitectura 006)  
**Fecha de ejecución:** 2026-08-18 (migraciones y código base); verificación de auditoría: 2026-09-18  
**Sesión de chat:** Implementación fases 0→6 — permisos, listado OC, PDF fiscal, documentos inbound/outbound, email CI4, plantillas recurrentes, scheduler, calendario y alerta de vencidos  

> **Nota de convivencia con otros reportes:** El código base de esta sesión fue ampliado después por el agente del **REPORTE-008** (gaps, SMTP `.env`, offcanvas recurrentes, papelera OC, etc.). Este reporte describe lo **introducido en la sesión 006** y el estado verificado en disco hoy. Donde 008 modificó los mismos archivos, se indica en “Notas”.

---

#### Archivos Creados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `app/Database/Migrations/Accounting/2026-08-18-120000_CreateFinPurchaseOrderDocuments.php` | Nuevo | CREATE `fin_purchase_order_documents` |
| `app/Database/Migrations/Accounting/2026-08-18-120100_CreateFinPurchaseOrderEmailLogs.php` | Nuevo | CREATE `fin_purchase_order_email_logs` + pivot `fin_po_email_log_documents` |
| `app/Database/Migrations/Accounting/2026-08-18-120200_AlterFinRecurringExpensesForCalendar.php` | Nuevo | ALTER calendarización en `fin_recurring_expenses` |
| `app/Database/Migrations/Accounting/2026-08-18-120300_AlterFinExpensesForRecurringOccurrences.php` | Nuevo | ALTER ocurrencias en `fin_expenses` |
| `app/Database/Migrations/Accounting/2026-08-18-120400_CreateFinExpenseDocuments.php` | Nuevo | CREATE `fin_expense_documents` |
| `app/Models/Financial/FinPurchaseOrderDocumentModel.php` | Nuevo | Documentos OC (canales, MIME, `storeDocument`) |
| `app/Models/Financial/FinPurchaseOrderEmailLogModel.php` | Nuevo | Log correo + pivot en transacción |
| `app/Models/Financial/FinExpenseModel.php` | Nuevo | Vencimientos recurrentes + DataTableTrait + calendario |
| `app/Models/Financial/FinExpenseDocumentModel.php` | Nuevo | Comprobantes de `fin_expenses` |
| `app/Services/AppMailer.php` | Nuevo | Envío HTML vía `Services::email()` / `Config\Email` |
| `app/Services/RecurringExpenseScheduler.php` | Nuevo | Generación de ocurrencias por plantilla activa |
| `app/Commands/Financial/GenerateRecurringExpenses.php` | Nuevo | Comando `financial:generate-recurring` |
| `app/Controllers/Financial/PurchaseOrderDocumentController.php` | Nuevo | Docs OC, compose/send email, descarga segura |
| `app/Controllers/Financial/RecurringExpenseController.php` | Nuevo | Índice recurrentes, list_ajax, calendario, generate, pay, docs |
| `app/Views/Financial/PurchaseOrders/partials/po_form_modal.php` | Nuevo | Modal OC reutilizable (proveedor readonly o Select2) |
| `app/Views/Financial/Recurring/recurring_index.php` | Nuevo | Calendario FullCalendar + tabs vencimientos/plantillas (base; ampliado luego en 008) |

**No creado en sesión 006** (otro agente / Task 007):  
`app/Database/Migrations/Accounting/2026-08-18-120500_FixFinExpensesRecurringIndexes.php` — existe en disco y está migrada; corregir índices/`category_id` nullable.

#### Archivos Modificados (sesión 006)

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `app/Config/AuthGroups.php` | Modificado | Permisos `catalog.po-documents`, `catalog.po-send-email`, `catalog.recurring-manage`; matrix `purchasing` y `finance` |
| `app/Config/Routes.php` | Modificado | Rutas nombradas PO docs/email y `suppliers.recurring.*` (rutas extra de plantillas pueden haberse añadido en 008) |
| `app/Config/Email.php` | Modificado | Comentario operador SMTP (sin credenciales); 008 añadió lectura `.env` |
| `app/Controllers/Financial/PurchaseOrderController.php` | Modificado | Columna sucursal en list_ajax, PDF con `company`, botón ver detalle |
| `app/Controllers/Financial/SupplierController.php` | Modificado | `store_service` con campos calendarización |
| `app/Controllers/System/AlertController.php` | Modificado | `_detectOverdueRecurringExpenses()` en `_detectAll()` |
| `app/Models/Financial/FinPurchaseOrderModel.php` | Modificado | `filter_branch_id`, datos fiscales proveedor/sucursal en `getOrderWithItems`, `column_order` con sucursal |
| `app/Models/Financial/FinRecurringExpenseModel.php` | Modificado | DataTableTrait, campos ALTER, `createForSupplier` / `updateForSupplier` |
| `app/Views/Financial/PurchaseOrders/purchase_orders_index.php` | Modificado | Filtros, modal OC, offcanvas 360°, tabs docs/email (evolución posterior en 008) |
| `app/Views/Financial/PurchaseOrders/purchase_order_pdf.php` | Modificado | Fallback matriz, logo, RFC proveedor |
| `app/Views/Financial/Suppliers/suppliers_index.php` | Modificado | Include `po_form_modal`; campos recurrentes en modal servicio |
| `app/Views/Partials/loggedin_sidebar.php` | Modificado | Enlace “Servicios recurrentes” |

---

#### Migraciones Ejecutadas

| Archivo de Migración | Acción | Tablas Afectadas | ¿Corrió OK? |
|----------------------|--------|------------------|-------------|
| `2026-08-18-120000_CreateFinPurchaseOrderDocuments.php` | CREATE | `fin_purchase_order_documents` | **Sí** — `php spark migrate` batch 24 (`2026-08-18 19:42:24`) |
| `2026-08-18-120100_CreateFinPurchaseOrderEmailLogs.php` | CREATE | `fin_purchase_order_email_logs`, `fin_po_email_log_documents` | **Sí** — mismo batch |
| `2026-08-18-120200_AlterFinRecurringExpensesForCalendar.php` | ALTER | `fin_recurring_expenses` | **Sí** — mismo batch |
| `2026-08-18-120300_AlterFinExpensesForRecurringOccurrences.php` | ALTER | `fin_expenses` | **Sí** — mismo batch |
| `2026-08-18-120400_CreateFinExpenseDocuments.php` | CREATE | `fin_expense_documents` | **Sí** — mismo batch |
| `2026-08-18-120500_FixFinExpensesRecurringIndexes.php` | ALTER | `fin_expenses` (índices, `category_id` nullable) | **Sí** en entorno — batch 26; **no** autor de sesión 006 |

---

#### Cumplimiento de Estándares Técnicos

| § | Estándar | ¿Cumplido? | Evidencia / Notas |
|---|----------|------------|-------------------|
| §1 | Guard AJAX en endpoints | ✅ | `PurchaseOrderDocumentController`, `RecurringExpenseController`, métodos PO existentes: `isAJAX()` + `PageNotFoundException`. Downloads GET de archivo sin AJAX (patrón RH). |
| §1 | Cabecera Fetch `X-Requested-With` | ⚠️ | `purchase_orders_index.php` y flujos PO nuevos usan `route_to()` + header. **`suppliers_index.php`** conserva varios `site_url('nat/suppliers/...')` en tabs contactos/productos/OC (legado; no refactorizado en 006). |
| §2 | CSRF input global | ✅ | `#csrf_token` en `main` de `purchase_orders_index.php` y `recurring_index.php`. |
| §2 | CSRF renovación DataTables | ✅ | `xhr.dt` en tablas PO/recurrentes. En disco hoy: `refreshCsrf` dual Fetch/jqXHR (mejora post-006 / alineada con 007). |
| §2 | CSRF en header Fetch | ✅ | Mutaciones PO/recurrentes inyectan `csrf_header()` en headers. |
| §3 | Rutas con alias | ✅ | Rutas del spec 006 con `as` en grupo `suppliers`. Ruta extra `suppliers.recurring.templates.store` añadida en 006 (no listada en el doc mínimo de rutas). |
| §3 | Verbos HTTP | ✅ | `list_ajax` POST; consultas GET. |
| §4 | Sidebar activo | ✅ | `url_is('nat/suppliers/recurring*')` y OC en `loggedin_sidebar.php`. |
| §5/§16 | DataTableTrait + column_order | ⚠️ | Trait en `FinPurchaseOrderModel`, `FinExpenseModel`, `FinRecurringExpenseModel`; columna Sucursal añadida en 006 — riesgo histórico de desalineación corregido en 007/008 (9 cols HTML). Auditor debe revalidar ordenamiento. |
| §6 | Guard DataTable isDataTable | ✅ | Presente en PO y recurrentes (verificado en vistas actuales). |
| §7 | Filtros de permiso Shield | ✅ | `catalog.po-documents`, `catalog.po-send-email`, `catalog.recurring-manage` en rutas; `catalog.manage-suppliers` en `purchasing`; UI con `can()`. |
| §8 | Bitácora de actividad | ✅ | `logActivity` en upload/delete docs, send email, generate recurring, mark paid (controladores nuevos). |
| §9 | Transacciones en Modelo | ✅ | Sin `transStart`/`Database::connect()` en controladores Financial nuevos. Transacciones en modelos de docs/email/PO; scheduler usa `RecurringExpenseScheduler` (service con `$this->templateModel->db`). |
| §10 | Select2 dropdownParent | ✅ | Modal OC: `#modalGeneratePO` en partial y `purchase_orders_index` / `suppliers_index`. |
| §11 | Fechas | ✅ | ISO en modelos; UI `d/m/Y` en listados; `<input type="date">` en formularios. |
| §12 | Solo FontAwesome | ✅ | Iconos `fas` en vistas y acciones DataTable del módulo. |
| §13 | Breadcrumb | ✅ | `BaseFinancialController` + breadcrumbs en `PurchaseOrderController` / `RecurringExpenseController::index`. |
| §14 | Extend layout maestro | ⚠️ | `extend('Layouts/user_loggedin_layout')`; sección `pageFooterScripts` (convención del proyecto, no `scripts` literal del doc). |
| §15 | viewData / outputData | ⚠️ | Mutaciones AJAX usan `setOutputSuccess/Error` + `csrf`. `list_ajax` DataTables devuelve JSON protocolo DataTables sin `$outputData` (patrón ERP heredado). |
| §17 | notifyShow() | ⚠️ | `notifyShow` en flujos nuevos. **`confirm()`** nativo en cambio de estatus/borrado OC (no es `alert()`, pero no es modal AppStack). |
| §18 | Uploads organizados | ✅ | Financiero privado: `WRITEPATH/uploads/financial/po/{id}/` y `.../expenses/{id}/`; rutas relativas en BD; `getRandomName()`. |
| §19 | Soft deletes + allowedFields + validationRules | ✅ | Modelos nuevos con soft deletes, campos y reglas en modelo; validación canal/tipo en `FinPurchaseOrderDocumentModel`. |
| §20 | Catálogos estáticos | ✅ | `FinRecurringExpenseModel::SERVICE_TYPES` / grupos sin tabla extra. |
| §21 | Sistema de Alertas | ✅ | `_detectOverdueRecurringExpenses()` bajo `catalog.access` + `_safeDetect`. 008 añadió detector “por vencer” aparte. |

---

#### Deuda Técnica y Pendientes

- [ ] **Migración 120200:** `$this->forge->addKey()` tras `addColumn()` puede no crear índices en MySQL; 120500 y notas 007/008 abordan parte del esquema — validar índices reales en BD.
- [ ] **`suppliers_index.php`:** URLs hardcodeadas con `site_url()` en varios fetch de tabs (fuera del alcance estricto del listado OC, pero incumple espíritu §3).
- [ ] **SMTP:** En 006 solo comentario en `Email.php`; envío real depende de operador (008 añadió `.env`).
- [ ] **`php spark financial:generate-recurring`:** Comando creado; no se ejecutó en sesión 006 (inserta datos reales).
- [ ] **UI recurrentes (006 inicial):** Vista base sin offcanvas rico de vencimiento; 008 completó gaps de UX.
- [ ] **TinyMCE:** 006 usó CDN; en disco hoy cdnjs 6.8.3 + `license_key: 'gpl'` en PO (verificar política Tiny vs RRHH).
- [ ] **Pruebas E2E:** No verificadas en navegador en sesión 006.
- [ ] **Scheduler:** Lógica de `period_label` (trimestral `YYYY-Qn`, semestral `YYYY-Hn`) debe validarse con casos borde de calendario.

---

#### Notas para el Siguiente Agente

- Fuente de verdad de esquema: `agentic/2026-08-18_tables_po_documents_email_recurring.md` (5 migraciones 120000–120400). La **120500** es fix de auditoría 007, no del implementador 006.
- Canales de documentos OC: **`inbound_archive`** (nunca al correo) vs **`outbound`** (picker de email); rechazo de inbound en `send_email`.
- Archivos financieros solo por controlador autenticado; **no** `public_html`.
- Tras cambios en `AuthGroups`, validar permisos efectivos en usuarios `purchasing` / `finance`.
- Para auditoría completa del módulo en estado actual, leer también **REPORTE-008** (mismo archivo §5).
- Task registry §2: marcar fila **006** / **DB-po** como “reporte presente” lo hace el orquestador, no este agente.

---

### REPORTE-010: RRHH — Contratos (versionado, sucursales, horarios) — auditoría + fix F1–F15

**Tarea ID:** RRHH-Contratos-Versionado  
**Archivo de plan:** `agentic/2026-08-18_ALTER_hr_contracts_versioning.md` + fix `agentic/2026-08-18_ALTER_hr_contracts_versioning_fix.md`  
**Agente / Modelo:** Cursor Grok 4.6 (orquestador / auditor). Implementación PHP: otro agente programador, ejecutando el prompt de corrección F1–F15.  
**Fecha de ejecución:** 2026-08-18 (código y migraciones); re-auditoría y este reporte: 2026-09-18  
**Sesión de chat:** Auditoría del módulo HR contratos → prompt de corrección → re-auditoría Go con observaciones menores  

> **Honestidad de autoría:** Este agente **no escribió** el PHP de negocio. Audito, contrasté con `DOCUMENTACION_TECNICA.md` y MySQL vivo, redacté el prompt F1–F15, re-verifiqué la entrega y documento aquí el estado **real del código**. Los archivos listados existen en disco y fueron inspeccionados el 2026-09-18.

---

#### Archivos Creados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `app/Database/Migrations/HR/2026-08-18-000001_HrContractVersioning.php` | Nuevo | ALTER `hr_contracts`: `parent_contract_id`, `version`, `change_source`; FK self; seed de 3 turnos si `hr_cat_shifts` vacío |
| `app/Database/Migrations/HR/2026-08-18-000002_HrContractVersioningFix.php` | Nuevo | Backfill 1 active/perfil, cadena `parent_id`, MODIFY NOT NULL, índice `idx_hr_contracts_profile_version` |
| `agentic/2026-08-18_ALTER_hr_contracts_versioning.md` | Nuevo | Spec Lucid del ALTER inicial |
| `agentic/2026-08-18_ALTER_hr_contracts_versioning_fix.md` | Nuevo | Spec Lucid del fix de producción |
| `app/Commands/VerifyHrContracts.php` | Nuevo | Comando aux `php spark hr:verify-contracts` (F1/F3/schema). No es runtime de la app |

#### Archivos Creados/Modificados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `app/Services/ContractService.php` | Modificado | Fork condicional, comparación DECIMAL/IDs/fechas, variables sucursal/horario, `buildPdfHtml`, overnight +24h, saveAsTemplate clona `{{}}` |
| `app/Models/HR/WorkerContractModel.php` | Modificado | `createVersion` con `transBegin`/`rollback`, `FOR UPDATE`, `validateVersionData`, árbol `parent_contract_id` |
| `app/Models/HR/HrWorkerScheduleModel.php` | Modificado | `assignSchedule` (soft-close / update vigencia), `getCurrentSchedule`, `countByShiftId` |
| `app/Models/HR/HrShiftModel.php` | Modificado | `$protectFields`, `$validationRules` |
| `app/Controllers/HR/WorkerController.php` | Modificado | Alta: horario + contrato + `contract_warning`. Update: snapshot + `forkOnDataChange` (no fork si `shift_id` vacío) |
| `app/Controllers/HR/WorkerContractController.php` | Modificado | Historial/árbol, preview via `buildPdfHtml`, log manual con `v{version}` |
| `app/Controllers/HR/CatalogController.php` | Modificado | `saveShift`/`deleteShift` (isAJAX, bitácora, bloqueo si turno en uso) |
| `app/Controllers/HR/ContractTemplateController.php` | Modificado | Persistencia de `footer_text` (trabajo previo del programador de contratos) |
| `app/Config/Routes.php` | Modificado | `hr.catalogs.shift.save` / `shift.delete` (POST, con `as`) |
| `app/Views/HR/worker_form.php` | Modificado | Turno + `schedule_start_date` en tab laboral |
| `app/Views/HR/contracts/worker_contracts_history.php` | Modificado | Árbol de forks, badges versión/`change_source`, `route_to` download, `#csrf_token` |
| `app/Views/HR/contracts/worker_contract_generate.php` | Modificado | `parent_contract_id`, preview con `template_id` |
| `app/Views/HR/contracts/template_form.php` | Modificado | `footer_text`, badges de variables (nss, teléfono, email, comercial, patrón) |
| `app/Views/HR/catalogs/index.php` | Modificado | Tab Turnos + modal CRUD; fetch de turnos con `csrf_header()` |

Verificados existentes (no creados en esta sesión): `HrContractModel.php` (legado, **sin usos**), `OrgBranchModel::getFiscalProfile`.

---

#### Migraciones Ejecutadas

| Archivo de Migración | Acción | Tablas Afectadas | ¿Corrió OK? |
|----------------------|--------|------------------|-------------|
| `app/Database/Migrations/HR/2026-08-18-000001_HrContractVersioning.php` | ALTER + SEED | `hr_contracts` (columnas + FK `fk_hr_contracts_parent`); seed `hr_cat_shifts` | **Sí** — batch 25 (`2026-08-18 20:04:15`) |
| `app/Database/Migrations/HR/2026-08-18-000002_HrContractVersioningFix.php` | ALTER + backfill | `hr_contracts` (`version`/`change_source` NOT NULL, índice compuesto, 1 active/perfil, cadena parent) | **Sí** — batch 27 (`2026-08-18 20:58:09`) |

Verificación 2026-09-18: `version` Null=NO Default=1; `change_source` Null=NO Default=auto; profile_id=1 → 1 active / 9 total (cadena 4→…→12); 0 perfiles con `actives > 1`; índice `idx_hr_contracts_profile_version` presente. `php spark hr:verify-contracts` → F1 PASS, F3 PASS.

---

#### Cumplimiento de Estándares Técnicos

| § | Estándar | ¿Cumplido? | Evidencia / Notas |
|---|----------|------------|-------------------|
| §1 | Guard AJAX en endpoints | ⚠️ | `saveShift`/`deleteShift`/`saveManualContract`/`getRenderedTemplateAjax` usan `isAJAX()` + 404. `previewPdfRaw` es POST de formulario (excepción de diseño). **`WorkerController::create`/`update` responden JSON y no tienen `isAJAX()`** (solo `is('post')`) — legado no cerrado en el fix. |
| §1 | Cabecera Fetch `X-Requested-With` | ✅ | Catálogos turnos, historial, generate y `worker_form` incluyen `XMLHttpRequest`. |
| §2 | CSRF input global | ✅ | `#csrf_token` al inicio de `main` en historial, generate, template_form y catalogs. `worker_form` usa `csrf_field()` en el form. |
| §2 | CSRF renovación DataTables | N/A | Este workstream no añade DataTables nuevas (historial es tabla HTML + búsqueda JS). |
| §2 | CSRF en header Fetch | ⚠️ | Fetch de **turnos** inyecta `csrf_header()`. `worker_form` y `save-manual` mandan token en **body** (FormData) y renuevan por header de respuesta / JSON `csrf` — desvío §2.C en esos fetch. |
| §3 | Rutas con alias | ⚠️ | Rutas nuevas de turnos y contratos manuales tienen `as`. **`get_contracts/(:num)` sigue sin `as`** (legado, no tocado). |
| §5 | DataTableTrait + column_order | N/A | No hay DataTable server-side nuevo en contratos/horarios. |
| §6 | Guard DataTable isDataTable | N/A | Sin DataTables nuevas. |
| §7 | Filtros de permiso Shield | ⚠️ | Grupo `hr` con `permission:hr.access`. CRUD trabajador usa `hr.create`/`hr.edit`. **Catálogos (incl. turnos) no tienen filtro granular extra** — cualquiera con `hr.access` muta turnos (patrón previo de dept/puestos). |
| §8 | Bitácora de actividad | ✅ | `logActivity` en save/delete turno, alta/edición trabajador, contrato manual con versión real (`v{n}`). |
| §9 | Transacciones en Modelo | ⚠️ | `WorkerContractModel::createVersion` usa `$this->db->transBegin()` / rollback / commit (modelo, no controlador). **`HrWorkerScheduleModel::assignSchedule`**: soft-delete + insert **sin** transacción — si el insert falla, el horario previo queda cerrado. Cero `Database::connect()` en controladores HR tocados. |
| §10 | Select2 dropdownParent | N/A | Turno en `worker_form` es `<select class="form-select">` nativo, no Select2 en modal. |
| §11 | Fechas | ✅ | `schedule_start_date` y `hiring_date` son `<input type="date">`. Comparación contractual normaliza a `Y-m-d`. UI de historial `d/m/Y H:i`. |
| §12 | Solo FontAwesome | ⚠️ | Tab Turnos, historial, generate y plantillas usan `fas`/`far`. **Tabs Departamentos/Puestos de `catalogs/index.php` siguen con `data-lucide`** (legado; el prompt pidió no migrarlos). |
| §13 | Breadcrumb | ✅ | `WorkerContractController` y `CatalogController` usan `App\Libraries\Breadcrumb` + `viewData['breadcrumb']`. |
| §14 | Extend layout maestro | ⚠️ | Vistas `extend($layout)` con `title` / `main` / `pageFooterScripts`. El doc nombra sección `scripts`; el layout real usa `pageFooterScripts` (convención del ERP). |
| §15 | viewData / outputData | ⚠️ | HTML: `setViewSuccess` + `renderLayout`. Mutaciones AJAX de turnos/manual: `setOutputSuccess/Error` + `csrf` en JSON. Alta trabajador: éxito HTTP 201 aunque falle el contrato (`contract_warning` en payload; el front de `worker_form` no lo lee y muestra toast `success`). |
| §17 | notifyShow() | ⚠️ | Catálogos/generate usan `notifyShow`. `worker_form` también. Hay `confirm()` nativo al borrar turno (no es `alert()`). |
| §18 | Uploads organizados | ✅ | PDF persistido en `FCPATH . 'uploads/hr/contracts/{profile_id}/'` con ruta relativa. Logos de plantilla en `uploads/hr/contracts/logos/`. Documentos de expediente (preexistente) en `WRITEPATH` (excepción privada §18). |
| §19 | Soft deletes + allowedFields + validationRules | ✅ | `WorkerContractModel`, `HrWorkerScheduleModel`, `HrShiftModel`: soft deletes, `$allowedFields`, reglas en modelo. `HrShiftModel` ganó `$validationRules` en el fix. |
| §20 | Catálogos estáticos | N/A | Turnos son tabla `hr_cat_shifts` (catálogo operativo, no constante). |
| §21 | Sistema de Alertas | N/A | Este workstream no añade detectores. |

---

#### Deuda Técnica y Pendientes

- [ ] **`assignSchedule` sin transacción:** soft-close + insert no atómicos. Riesgo residual medio documentado en re-auditoría.
- [ ] **Sin UNIQUE de un solo `active` en MySQL:** enforcement en código (`FOR UPDATE` + archive). Aceptado a propósito (MySQL sin UNIQUE parcial nativo).
- [ ] **`WorkerController::create`/`update` sin `isAJAX()`** (§1).
- [ ] **Fetch de `worker_form` / `save-manual`:** CSRF en body, no en header (§2.C).
- [ ] **Alta: `contract_warning` como `success=true`:** el usuario puede ver toast verde con texto de aviso; el JS no ramifica por `response.contract_warning`.
- [ ] **Comando `hr:verify-contracts`:** auxiliar de auditoría; decidir si se queda en prod u se elimina.
- [ ] **Lucide en tabs dept/puestos** del catálogo HR (fuera del alcance del fix de turnos).
- [ ] **`get_contracts` sin named route** (`Routes.php` línea de `get_contracts/(:num)`).
- [ ] **Catálogos HR** (dept/job/shift) solo bajo `hr.access`, sin `hr.edit`.
- [ ] **Fuera de alcance (correcto no tocar):** vacaciones dual (`hr_vacation_requests` vs `hr_vacations`), enum incidencias `rechazada` vs `rechazado`, `HrContractModel` legado, plantillas por sucursal, documentos legales Organization, `location_id` NULL en workers y fiscal/logo vacío en sucursales.
- [ ] **UI E2E en navegador** de alta/edición/fork no ejercida en la re-auditoría (verificación por código + SQL + spark).

---

#### Notas para el Siguiente Agente

- Re-auditoría **2026-09-18: Go con observaciones menores**. No reabrir F1 (DECIMAL) ni F2 (9 actives) salvo que el schema haya cambiado: el backfill ya dejó cadena lineal, no el árbol histórico real pre-migración.
- Logo PDF: sucursal → plantilla `header_logo` → empresa. Hoy sucursales sin `logo_path` y workers sin `location_id`; el fallback a plantilla/empresa es el camino real.
- Un active por `profile_id` es regla de negocio; no crear UNIQUE `(profile_id, status)` sin índice funcional.
- `change_source`: `auto` alta, `fork` cambio de datos / fork desde historial, `manual` generación sin padre.
- No ejecutar el `down()` de `000001` en prod: droppea columnas de versionado.
- Prompt de corrección original cubría F1–F15; no mezclar con Task 002 incidencias ni Task 001 documentos de sucursal.
- Registry §2: filas **HR-v** y **HR-f** corresponden a este reporte; el orquestador puede marcar “reporte presente”.

---

### REPORTE-011: FIX-A — WorkerIncidenceController (§9 transacciones + §21 AlertService)

**Tarea ID:** 005 / FIX-A (también cierra deuda de 002)
**Archivo de plan:** `agentic/005_refactor_db_transactions.md` + FIX-A en `agentic/AUDITORIA_ARQUITECTURA.md`
**Agente / Modelo:** Cursor Grok 4.6
**Fecha de ejecución:** 2026-09-18
**Sesión de chat:** Refactor MVC incidencias HR — transacciones al modelo y AlertService

---

#### Archivos Creados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `app/Models/HR/HrIncidenceModel.php` | Modificado | `recordIncidence()` y `updateIncidenceStatus()` encapsulan `$this->db->transStart()` / `transComplete()`. Notificación via `AlertService::dispatch()` + `assignToUsers()`. |
| `app/Controllers/HR/WorkerIncidenceController.php` | Modificado | Sin `\Config\Database::connect()` ni transacciones. Delega al modelo. `logActivity` en create/update. CSRF en JSON. |

#### Migraciones Ejecutadas

| Archivo de Migración | Acción | Tablas Afectadas | ¿Corrió OK? |
|----------------------|--------|------------------|-------------|
| N/A | — | — | N/A — sin cambios de esquema |

#### 🔒 Sección Obligatoria — Permisos (§7) y Bitácora (§8)

**§7 — Mapa de permisos implementados:**

| Ruta / Acción | Tipo | Filtro en Routes.php | Guard can() en Vista |
|---------------|------|----------------------|----------------------|
| `hr.worker.incidence.add` | POST | `permission:hr.edit` | N/A (AJAX; UI previa no tocada) |
| `hr.worker.incidence.update` | POST | `permission:hr.edit` | N/A (AJAX; UI previa no tocada) |

**§8 — Mapa de entradas en bitácora:**

| Acción de negocio | Tipo logActivity | Descripción registrada |
|-------------------|-----------------|------------------------|
| Registrar incidencia | `'create_incidence'` | `'Registró incidencia {tipo} para profile_id: {id} (fecha: {fecha})'` |
| Actualizar estatus | `'update_incidence'` | `'Actualizó estatus de incidencia ID: {id} a: {status}'` |

---

#### Cumplimiento de Estándares Técnicos

| § | Estándar | ¿Cumplido? | Evidencia / Notas |
|---|----------|------------|-------------------|
| §1 | Guard AJAX en endpoints | ⚠️ | Ambos métodos públicos tienen `isAJAX()`. Responden 403 JSON (legado), no `PageNotFoundException`. |
| §2 | CSRF renovación DataTables | N/A | Sin DataTables en este controlador. |
| §2 | CSRF en header Fetch | N/A | No se tocó el frontend. |
| §3 | Rutas con alias | ✅ | `hr.worker.incidence.add` / `hr.worker.incidence.update` (preexistentes). |
| §5 | DataTableTrait + column_order | N/A | Sin DataTable server-side. |
| §6 | Guard DataTable isDataTable | N/A | Sin DataTables. |
| **§7** | **Filtros de permiso Shield** | **✅** | **Rutas de mutación con `permission:hr.edit` (preexistente).** |
| **§8** | **Bitácora de actividad** | **✅** | **`logActivity` en create y update de incidencia (antes ausente).** |
| §9 | Transacciones en Modelo | ✅ | `$this->db->transStart()` solo en `HrIncidenceModel`. Cero `Database::connect()` en el controlador. |
| §10 | Select2 dropdownParent | N/A | Sin cambios de UI. |
| §12 | Solo FontAwesome | ✅ | Iconos de alerta `fa-exclamation-circle` / `fa-info-circle`. |
| §13 | Breadcrumb | N/A | Endpoints AJAX, sin vista HTML. |
| §14 | Extend layout maestro | N/A | Sin vistas nuevas. |
| §15 | viewData / outputData | ✅ | `setOutputSuccess/Error` + `csrf_hash()` en JSON. |
| §17 | notifyShow() | N/A | Sin cambios de frontend. |
| §18 | Uploads organizados | N/A | Sin uploads. |
| §19 | Soft deletes + allowedFields + validationRules | ⚠️ | Soft deletes y `$allowedFields` OK. `$validationRules` sigue ausente (legado, no añadido en este fix). |
| §21 | AlertService | ✅ | Reemplazado `SysNotificationModel` por `AlertService::dispatch()` + `assignToUsers()` al `user_id` del perfil. |

#### Deuda Técnica y Pendientes

- [ ] Guard AJAX §1: los métodos públicos responden 403 JSON en vez de `PageNotFoundException`.
- [ ] `HrIncidenceModel` sin `$validationRules` (§19).
- [ ] Task 002 (justificaciones, pre-nómina, PDF finiquito) sigue incompleta; este reporte solo cierra FIX-A.
- [ ] UI E2E de alta/cambio de estatus de incidencia no ejercida en navegador (verificación por código + `php -l`).

#### Notas para el Siguiente Agente

- La alerta se asigna al `user_id` vinculado del perfil, no a todos los usuarios con `hr.access`/`hr.edit`. `dispatch()` va con `requiredPermission: null` y luego `assignToUsers()`.
- `SysNotificationModel` ya no se usa desde incidencias; el modelo de tabla `sys_notifications` permanece por si hay datos legado.
- Registry §2: filas **005** y **002** corresponden a este FIX-A; el orquestador puede marcar “FIX-A resuelto”.

---


## Apéndice A: Contexto del Sistema de Alertas

El sistema tiene **dos generaciones** de alertas que NO deben confundirse:

### Generación 1 — Legacy CHISA (CI3, documentado en `SISTEMA_ALERTA_NOTIFICACIONES.MD`)
- Sistema global sin filtrado por usuario.
- Sin persistencia (solo consultas en tiempo real).
- Polling cada 2 minutos con jQuery `$.get`.
- Sin "marcar como leído".
- **Estado:** Documentado como referencia histórica. NO debe usarse en el ERP CI4.

### Generación 2 — CI4 con `AlertService` (documentado en `DOCUMENTACION_TECNICA.md` §21 y `004_sistema_alertas_notificaciones.md`)
- **Alertas almacenadas:** `sys_alerts` + `sys_alert_user` con estado leída/no leída.
- **Alertas automáticas (CHISA-style):** `_detect*()` en `AlertController`, sin persistencia.
- Filtrado por permisos Shield por alerta.
- Polling cada 60 segundos.
- Historial completo en `alerts_history`.
- Respuesta JSON unificada con `is_auto` flag.
- **Estado:** Este es el sistema que DEBE implementarse y mantenerse.

---

## Apéndice B: Arquitectura de Módulo de Proveedores

El módulo de proveedores evolucionó en múltiples tasks. Mapa de dependencias:

```
Task 003 (Modelos/Controladores/Vistas base)
    └── Task 006 (PO Documentos + Email + Recurrentes)
            └── Task 007 (Fixes: CSRF, column_order, category_id nullable)
                    └── Migración 2026-08-18-120500 (fix indexes)
```

**Tablas del módulo de proveedores:**
- `fin_suppliers` (existente)
- `fin_supplier_contacts` (Task 003)
- `fin_purchase_orders` (Task 003)
- `fin_purchase_order_items` (Task 003)
- `catalog_product_suppliers` (Task 003)
- `fin_purchase_order_documents` (Task 006, migración -120000)
- `fin_purchase_order_email_logs` (Task 006, migración -120100)
- `fin_po_email_log_documents` (Task 006, migración -120100, pivot)
- `fin_recurring_expenses` (existente + ALTER Task 006, migración -120200)
- `fin_expenses` (existente + ALTER Task 006, migración -120300)
- `fin_expense_documents` (Task 006, migración -120400)
- Indexes fix (Task 007, migración -120500)

**Uploads privados del módulo:**
- OC: `WRITEPATH/uploads/financial/po/{po_id}/`
- Gastos/Recurrentes: `WRITEPATH/uploads/financial/expenses/{id}/`

---

## Apéndice C: Arquitectura RRHH — Contratos

```
hr_contracts (existente + ALTER versionado)
    ├── parent_contract_id (self-FK, fork tree)
    ├── version (INT, backfill 1..N por profile_id)
    ├── change_source ENUM('auto','manual','fork')
    └── status ENUM('active','archived','draft')

Migraciones RRHH:
    2026-08-18-000001_HrContractVersioning.php      (ALTER inicial)
    2026-08-18-000002_HrContractVersioningFix.php   (backfill + MODIFY NOT NULL)
```

**Reglas de negocio clave:**
1. Solo 1 fila `status='active'` por `profile_id`.
2. Cualquier cambio en datos del trabajador genera fork automático (`change_source='fork'`).
3. Logo en PDF: `org_branches.logo_path` → plantilla `header_logo` → empresa `org_company_profile.logo_path`.

---

*Documento creado por el agente auditor de arquitectura. Última actualización: 2026-09-18.*

---

## Apéndice D: Análisis de `pendientes.md` vs. Estado Real del Sistema

> **Fecha de análisis:** 2026-09-18 | **Auditor:** Agente Orquestador

### Resumen ejecutivo

| Módulo | Ítem de `pendientes.md` | Estado | Plan/Acción |
|--------|------------------------|--------|-------------|
| **Administrador** | Alta/edición usuarios admins | ✅ IMPLEMENTADO | `UserController.php` con CRUD completo |
| **Administrador** | Asignación de roles | ✅ IMPLEMENTADO | `RoleController.php` + Shield |
| **Administrador** | Permisos independientes | ✅ IMPLEMENTADO | `UserController::permissions()` + `permissions_save()` |
| **Administrador** | Bitácora: búsqueda avanzada GLOBAL | ❌ FALTA | Solo existe vista por usuario. **→ Task 009** |
| **Administrador** | Edición empresa/sucursales (datos + logo) | ✅ IMPLEMENTADO | `OrganizationController.php` completo |
| **Administrador** | Documentos legales por sucursal (CSF, Comprobante, Acta) | ❌ FALTA | Solo existe `logo_path`. **→ Task 001** |
| **Administrador** | Sistema de permisos Shield (filtro + sidebar) | ✅ IMPLEMENTADO | Rutas con `permission:` y sidebar con `can()` |
| **RR.HH** | Alta/edición/eliminación trabajadores | ✅ IMPLEMENTADO | `WorkerController.php` |
| **RR.HH** | Vinculación trabajador ↔ usuario admin | ✅ IMPLEMENTADO | `hr_profiles.user_id` FK a `users.id` |
| **RR.HH** | Datos legales México (NSS, IMSS, INFONAVIT, vacaciones, horarios) | ✅ IMPLEMENTADO | Campos en `HrProfileModel` + `HrVacationModel` + `HrWorkerScheduleModel` |
| **RR.HH** | Alertas por documentos faltantes (INE, domicilio, etc.) | ✅ IMPLEMENTADO | `_detectWorkersMissingNss/Rfc/Curp/BankData()` en `AlertController` |
| **RR.HH** | Departamentos como catálogo desplegable | ✅ IMPLEMENTADO | `HrDepartmentModel` + `CatalogController` |
| **RR.HH** | Sistema de contratos con plantillas y árbol histórico | ✅ IMPLEMENTADO | `WorkerContractController` + versioning (Task 2026-08-18) |
| **RR.HH** | **Nóminas (Payroll)** | ❌ NO EXISTE | Módulo completo por crear. **→ Task 011** |
| **Proveedores** | Alta/edición proveedores con datos de contacto | ✅ IMPLEMENTADO | `SupplierController` + `SupplierContactController` |
| **Proveedores** | Categorías propias con CRUD + softdelete + alertas | ⚠️ INCOMPLETO | `supplier_type` es enum hardcodeado. **→ Task 010** |
| **Proveedores** | CRM datos, órdenes de compra, servicios recurrentes | ✅ IMPLEMENTADO | `PurchaseOrderController` + `RecurringExpenseController` |
| **Proveedores** | Documentos asociados (contratos, acuerdos, PDFs) | ✅ IMPLEMENTADO | `PurchaseOrderDocumentController` + `fin_expense_documents` |
| **Proveedores** | Entrada de productos físicos + stock | ✅ IMPLEMENTADO | `InventoryController` + `catalog_product_stock` + `inventory_movements` |
| **Proveedores** | Alertas y notificaciones para el módulo | ✅ IMPLEMENTADO | `_detectSuppliersMissingTaxId()` + alertas recurrentes |

### Contrastación con planes existentes

| Plan | Descripción | Estado vs. `pendientes.md` |
|------|-------------|---------------------------|
| `001_branch_legal_documents.md` | Subir CSF, comprobante de domicilio y Acta Constitutiva a sucursales | ❌ **No implementado.** Solicitado en `pendientes.md` (Administrador → documentos por sucursal). Ejecutar Task 001. |
| `004_crm_clients.md` | Módulo CRM/Clientes con contactos y perfiles de facturación | ⚠️ **No está en `pendientes.md`**, pero el plan existe. Es un módulo independiente de futura implementación cuando el usuario lo priorice. |

### Acciones inmediatas tomadas por el auditor

1. ✅ **Eliminados** los 3 controladores legacy sin rutas activas:
   - `app/Controllers/Pelicula.php`
   - `app/Controllers/Cliente.php`
   - `app/Controllers/Login/Login.php`

2. ✅ **Creados** nuevos planes de implementación:
   - `agentic/009_admin_global_audit_log.md` — Bitácora global de administrador
   - `agentic/010_supplier_categories_crud.md` — Categorías de proveedores con soft-delete
   - `agentic/011_hr_payroll_nominas.md` — Sistema de nóminas completo

### Tareas para agentes en paralelo

| Prioridad | Task | Archivo de plan | Modelo recomendado | Criticidad |
|-----------|------|-----------------|--------------------|------------|
| 🔴 ALTA | **Task 001** — Branch Legal Docs | `001_branch_legal_documents.md` | Composer 2.5 | Media — funcionalidad solicitada por pendientes.md |
| 🔴 ALTA | **Task 011** — Nóminas (Payroll) | `011_hr_payroll_nominas.md` | Composer 2.5 (High) | Alta — módulo nuevo más complejo del backlog |
| 🟡 MEDIA | **Task 010** — Categorías Proveedores | `010_supplier_categories_crud.md` | Composer 2.5 | Media — refactor con migración de datos existentes |
| 🟡 MEDIA | **Task 009** — Bitácora Global Admin | `009_admin_global_audit_log.md` | Composer 2.5 | Baja — vista de lectura, sin nuevas tablas |
| 🔵 BAJA | **Task 004** — CRM/Clientes | `004_crm_clients.md` | Composer 2.5 | Baja — no en pendientes.md, priorizar tras los anteriores |

---

### REPORTE-012: Documentos legales de sucursales (CSF, domicilio, acta)

**Tarea ID:** 001
**Archivo de plan:** `agentic/001_branch_legal_documents.md`
**Agente / Modelo:** Cursor Grok 4.6
**Fecha de ejecución:** 2026-09-18
**Sesión de chat:** Task 001 — upload de documentos legales por sucursal

---

#### Archivos Creados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `app/Database/Migrations/Config/2026-09-18-140000_AddLegalDocumentPathsToOrgBranches.php` | Nuevo | ALTER `org_branches`: `csf_path`, `address_proof_path`, `incorporation_act_path` VARCHAR(255) NULL |
| `app/Models/Organization/OrgBranchModel.php` | Modificado | `$allowedFields` + persistencia de los 3 archivos en `saveBranch()` vía `store_public_upload()` (§9/§18) |
| `app/Controllers/Organization/OrganizationController.php` | Modificado | `saveBranch()` orquesta archivos + `logActivity('upload_branch_docs')` por archivo; `get_ajax()` expone URLs |
| `app/Views/Organization/branch_form.php` | Modificado | 3 file inputs + badges de descarga + `fetch()` CSRF + `notifyShow()` |
| `app/Views/Organization/branches_index.php` | Modificado | Misma UI en el modal vivo (formulario realmente usado) y badges en el offcanvas |
| `app/Config/Routes.php` | Sin cambio | Ruta `organization.branch.save` ya existía con alias y permiso |

#### Migraciones Ejecutadas

| Archivo de Migración | Acción | Tablas Afectadas | ¿Corrió OK? |
|----------------------|--------|------------------|-------------|
| `2026-09-18-140000_AddLegalDocumentPathsToOrgBranches.php` | ALTER | `org_branches` | Sí — batch 28, columnas verificadas con `spark db:table` |

#### 🔒 Sección Obligatoria — Permisos (§7) y Bitácora (§8)

**§7 — Mapa de permisos implementados:**

| Ruta / Acción | Tipo | Filtro en Routes.php | Guard can() en Vista |
|---------------|------|----------------------|----------------------|
| `organization.branch.save` (`POST nat/organization/branch/save`) | POST | Grupo `permission:admin.manage-organization` | N/A — formulario ya dentro del módulo protegido |
| `organization.branch.get_ajax` | GET | Mismo grupo `permission:admin.manage-organization` | N/A — usado por el modal/offcanvas autenticado |

**§8 — Mapa de entradas en bitácora:**

| Acción de negocio | Tipo logActivity | Descripción registrada |
|-------------------|-----------------|------------------------|
| Crear / editar sucursal | `'save_branch'` | `'Creó'/'Actualizó' la sucursal ID: {id}` (ya existía) |
| Subir CSF | `'upload_branch_docs'` | `'Subió CSF ({nombre original}) para sucursal ID: {id}'` |
| Subir comprobante de domicilio | `'upload_branch_docs'` | `'Subió Comprobante de Domicilio ({nombre original}) para sucursal ID: {id}'` |
| Subir acta constitutiva | `'upload_branch_docs'` | `'Subió Acta Constitutiva ({nombre original}) para sucursal ID: {id}'` |

---

#### Cumplimiento de Estándares Técnicos

| § | Estándar | ¿Cumplido? | Evidencia / Notas |
|---|----------|------------|-------------------|
| §1 | Guard AJAX en endpoints | ✅ | `saveBranch()` llama `$this->assertAjaxPost()` (404 si no es AJAX POST). Fetch envía `X-Requested-With: XMLHttpRequest`. |
| §2 | CSRF renovación DataTables | ✅ | Sin cambio: `xhr.dt` + `refreshCsrfFromHeaders` en `branches_index.php`. |
| §2 | CSRF en header Fetch | ✅ | `branch_form.php` y `branches_index.php` inyectan `<?= csrf_header() ?>` y actualizan `#csrf_token` desde header + `res.csrf`. |
| §3 | Rutas con alias | ✅ | Se reutiliza `route_to('organization.branch.save')`. No se crearon rutas nuevas. |
| §5 | DataTableTrait + column_order | N/A | Endpoint de upload; DataTables de sucursales no se tocó. |
| §6 | Guard DataTable isDataTable | N/A | Sin tabla nueva. |
| **§7** | **Filtros de permiso Shield** | **✅** | **Grupo `organization` con `permission:admin.manage-organization`.** |
| **§8** | **Bitácora de actividad** | **✅** | **`upload_branch_docs` por cada archivo subido (nombre + ID) + `save_branch`.** |
| §9 | Transacciones en Modelo | ✅ | Los 3 archivos se persisten en `OrgBranchModel::saveBranch()` junto con los datos; transacción `$this->db->transStart/Complete` sin `Database::connect()` en el controlador. |
| §10 | Select2 dropdownParent | N/A | Sin Select2. |
| §12 | Solo FontAwesome | ✅ | `fas fa-file-pdf`, `fas fa-upload`, `fas fa-check-circle`, `fas fa-folder-open`. |
| §13 | Breadcrumb | N/A | No hay vista de página nueva. |
| §14 | Extend layout maestro | ✅ | Se modifican vistas existentes que ya hacen `$this->extend($layout)`. |
| §15 | viewData / outputData | ✅ | `setOutputSuccess()` / `setOutputError()` + `jsonWithCsrf($this->outputData)`. |
| §17 | notifyShow() | ✅ | `branch_form.php` usa `notifyShow()`; `branches_index.php` usa wrapper `notify()` → `notifyShow()`. Cero `alert()` en estas vistas. |
| §18 | Uploads organizados | ✅ | Helper `store_public_upload()`: `FCPATH . 'uploads/organization/'`, `getRandomName()`, ruta relativa en BD, elimina archivo anterior. |
| §19 | Soft deletes + allowedFields + validationRules | ✅ | Soft delete ya activo. Campos añadidos a `$allowedFields`. Rutas de archivo las genera el servidor; no se añadieron rules de input de usuario. |
| §20 | Catálogos estáticos | N/A | No aplica. |
| §21 | AlertService | N/A | No aplica a este módulo. |

#### Deuda Técnica y Pendientes

- [ ] `branch_form.php` **no está ruteado**: `createBranch()`/`editBranch()` redirigen al listado (`?open=create|edit`). La UI real es el modal de `branches_index.php`; por eso se actualizó también (fuera del listado explícito del plan, necesario para que la función exista en producción).
- [ ] E2E en navegador no ejercido: `https://sandbox.especialistashosting.com/nat/organization/branches` responde **401 Basic Auth** nginx (`Area de desarrollo`) antes del login Shield. Verificación por migración en disco, `php -l` y grep de estándares.
- [ ] `store_public_upload()` mueve el archivo **antes** de `transStart()` (mismo patrón que el logo). Si el UPDATE falla, puede quedar un archivo huérfano en `uploads/organization/`.

#### Notas para el Siguiente Agente

- Nombres de input: `csf`, `address_proof`, `incorporation_act`. Columnas: `csf_path`, `address_proof_path`, `incorporation_act_path`.
- El helper ya crea `uploads/organization/` si no existe. En web, FCPATH es `public_html/` (no `public/` de spark).
- `get_ajax` ahora devuelve `csf_url`, `address_proof_url`, `incorporation_act_url` para badges `target="_blank"`.
- Registry §2: fila **001** corresponde a este REPORTE-012; el orquestador puede auditar y marcar Go/No-Go.

---

### REPORTE-013: Bitácora Global de Administrador

**Tarea ID:** 009
**Archivo de plan:** `agentic/009_admin_global_audit_log.md`
**Agente / Modelo:** Cursor Grok 4.6
**Fecha de ejecución:** 2026-09-18
**Sesión de chat:** Task 009 — vista global de bitácora con filtros y DataTables

---

#### Archivos Creados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `app/Views/Users/global_activity.php` | Nuevo | Vista de bitácora global: CSRF §2, filtros date/Select2, DataTable Guard + `xhr.dt` |
| `app/Models/Users/UserActivityLogsModel.php` | Modificado | `$column_order` / `$column_search`, `getGlobalActivity()`, `_get_datatables_query()` con JOIN `users` en `scope=global` |
| `app/Controllers/Users/UserController.php` | Modificado | `auditLog()` + `auditLog_ajax()` + helpers `assertAjaxPost()`, `jsonWithCsrf()`, `assertPermission()` |
| `app/Config/Routes.php` | Modificado | GET `nat/admin/audit-log` (`admin.audit_log`) y POST `nat/admin/audit-log/ajax` (`admin.audit_log_ajax`) |
| `app/Config/AuthGroups.php` | Modificado | Permiso Shield `admin.audit-log` — cubierto por `admin.*` en grupos superadmin/admin |
| `app/Views/Partials/loggedin_sidebar.php` | Modificado | Ítem "Bitácora Global" con `fas fa-clipboard-list`, `url_is('nat/admin/audit-log*')` y `can('admin.audit-log')` |

#### Migraciones Ejecutadas

| Archivo de Migración | Acción | Tablas Afectadas | ¿Corrió OK? |
|----------------------|--------|------------------|-------------|
| *(ninguna)* | OMITIDA | `users_activity_logs` | N/A — `php spark db:table users_activity_logs --metadata` confirma `ip_address` VARCHAR(45) NULL ya existente. La tabla real es `users_activity_logs` (no `user_activity_logs`). Columnas: `id`, `users_id`, `type`, `description`, `ip_address`, `user_agent`, `created_at`. |

#### 🔒 Sección Obligatoria — Permisos (§7) y Bitácora (§8)

**§7 — Mapa de permisos implementados:**

| Ruta / Acción | Tipo | Filtro en Routes.php | Guard can() en Vista |
|---------------|------|----------------------|----------------------|
| `admin.audit_log` (`GET nat/admin/audit-log`) | GET | `permission:admin.audit-log` (+ grupo `session`) | Sidebar: `auth()->user()->can('admin.audit-log')` |
| `admin.audit_log_ajax` (`POST nat/admin/audit-log/ajax`) | POST | `permission:admin.audit-log` (+ grupo `session`) | N/A — endpoint AJAX; también `assertPermission('admin.audit-log')` en controlador |

**§8 — Mapa de entradas en bitácora:**

| Acción de negocio | Tipo logActivity | Descripción registrada |
|-------------------|-----------------|------------------------|
| *(ninguna)* | N/A | Vista de **solo lectura**. No hay CREATE/UPDATE/DELETE. §8 no aplica. |

---

#### Cumplimiento de Estándares Técnicos

| § | Estándar | ¿Cumplido? | Evidencia / Notas |
|---|----------|------------|-------------------|
| §1 | Guard AJAX en endpoints | ✅ | `auditLog_ajax()` llama `$this->assertAjaxPost()` (404 si no es AJAX POST). DataTable envía `X-Requested-With: XMLHttpRequest`. |
| §2 | CSRF renovación DataTables | ✅ | Hidden `#csrf_token` al inicio de `<main>`. `ajax.data` inyecta el token. `$('#audit-log-table').on('xhr.dt', …)` actualiza el hash desde `csrf_header()`. |
| §2 | CSRF en header Fetch | N/A | No hay `fetch()` de mutación en esta vista. |
| §3 | Rutas con alias | ✅ | `'as' => 'admin.audit_log'` y `'as' => 'admin.audit_log_ajax'`. Vista y sidebar usan `route_to()`. |
| §5 | DataTableTrait + column_order | ✅ | Trait ya existía. Se definieron `$column_order` y `$column_search`. `_get_datatables_query()` hace JOIN `users` y aplica filtros en `scope=global`. |
| §6 | Guard DataTable isDataTable | ✅ | `if ($.fn.dataTable.isDataTable('#audit-log-table')) return;` |
| **§7** | **Filtros de permiso Shield** | **✅** | **Rutas con `permission:admin.audit-log`. Permiso en `AuthGroups::$permissions`. Grupos `admin`/`superadmin` lo heredan via `admin.*`. Sidebar con `can('admin.audit-log')`.** |
| **§8** | **Bitácora de actividad** | **✅** | **N/A funcional — módulo de lectura. No hay mutaciones que registrar.** |
| §9 | Transacciones en Modelo | N/A | Sin mutaciones ni `Database::connect()` en el controlador. `getGlobalActivity()` usa `$this->db` del modelo. |
| §10 | Select2 dropdownParent | ✅ | `$('#filter_user').select2({ theme: 'bootstrap-5', dropdownParent: $('#filtros-card'), allowClear: true })`. Usuarios se cargan en `viewData` (no se creó `user.select_ajax`; 7 usuarios, búsqueda local Select2). |
| §11 | Fechas | ✅ | Filtros `<input type="date">`. Columna Fecha: `date('d/m/Y H:i', strtotime($createdRaw))`. Backend ISO. |
| §12 | Solo FontAwesome | ✅ | `fas fa-clipboard-list`, `fas fa-filter`, `fas fa-times`, `fas fa-file-csv`. Sin Lucide en esta vista. |
| §13 | Breadcrumb | ✅ | `Breadcrumb` fresco (sin merge con "Usuarios"): Inicio → Administrador → Bitácora Global. |
| §14 | Extend layout maestro | ✅ | `<?php $this->extend($layout); ?>` + secciones `title`, `main`. JS en `pageFooterScripts` porque el layout real **no** renderiza `scripts` (solo `pageFooterScripts`). |
| §15 | viewData / outputData | ✅ | `auditLog()`: `$this->viewData['response']` + `renderLayout()`. `auditLog_ajax()`: `setOutputSuccess()` + `jsonWithCsrf($this->outputData)` con claves DataTables (`draw`, `recordsTotal`, `recordsFiltered`, `data`). |
| §17 | notifyShow() | N/A | Sin mutaciones / sin feedback de guardado. |
| §18 | Uploads organizados | N/A | Sin uploads. |
| §19 | Soft deletes + allowedFields + validationRules | ✅ | Logs son append-only (`$useSoftDeletes = false`, ya existente). `$allowedFields` definidos. Sin validationRules nuevas: no hay writes en esta tarea. |
| §20 | Catálogos estáticos | N/A | No aplica. |
| §21 | AlertService | N/A | No aplica. |

#### Deuda Técnica y Pendientes

- [ ] Exportar CSV/Excel solo exporta la **página visible** de DataTables (botón cliente). `getGlobalActivity($filters)` está listo para un export server-side de todos los filtros; no se añadió ruta extra porque el plan no la pedía.
- [ ] Select2 de usuario no usa AJAX `user.select_ajax` (esa ruta no existía). Se pobló el `<select>` desde `usersProvider` y Select2 filtra en cliente. Suficiente con el volumen actual (~7 usuarios).
- [ ] E2E en navegador no ejercido: `https://sandbox.especialistashosting.com/nat/admin/audit-log` responde **401 Basic Auth** nginx (`Area de desarrollo`) antes del login Shield. Verificado con `php -l`, `php spark routes` y `php spark filter:check`.
- [ ] El layout `user_loggedin_layout.php` ya incluye `#csrf_token`; la vista añade otro por §2 (IDs duplicados). `getElementById` toma el del layout; ambos arrancan con el mismo hash.

#### Notas para el Siguiente Agente

- Tabla real: **`users_activity_logs`**. FK: **`users_id`**. Acción: columna **`type`** (no `action`). `ip_address` ya existía — no crear migración.
- DataTables global envía `scope=global` + `filter_user` / `filter_action` / `filter_date_from` / `filter_date_to`. El modo por usuario (`UserController::activity_ajax`) no se tocó.
- Permiso `admin.audit-log` vive en `Config\AuthGroups` (fuente Shield). No hay seeder de permisos; `admin.*` en la matrix cubre superadmin y admin.
- `getGlobalActivity()` hace JOIN `users` y devuelve array asociativo (`type` alias `action`) para exports futuros.
- Registry §2: fila **009** corresponde a este REPORTE-013.

---

### REPORTE-014: Categorías de Proveedores — CRUD + refactor supplier_type → category_id

**Tarea ID:** 010
**Archivo de plan:** `agentic/010_supplier_categories_crud.md`
**Agente / Modelo:** Cursor Grok 4.6
**Fecha de ejecución:** 2026-09-18
**Sesión de chat:** Task 010 — CRUD categorías de proveedores (soft-delete + migración destructiva)

---

#### Archivos Creados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `app/Database/Migrations/Financial/2026-09-18-000001_CreateFinSupplierCategories.php` | Nuevo | Tabla `fin_supplier_categories` + seed de 4 categorías |
| `app/Database/Migrations/Financial/2026-09-18-000002_AlterFinSuppliersAddCategoryId.php` | Nuevo | `category_id`, mapeo string→FK, FK `SET NULL`, DROP `supplier_type`. `down()` en orden inverso |
| `app/Models/Financial/FinSupplierCategoryModel.php` | Nuevo | Soft-delete, `DataTableTrait`, `$column_order`/`$column_search`, `getForSelect()`, `hasSuppliers()`, `restore()` |
| `app/Controllers/Financial/SupplierCategoryController.php` | Nuevo | CRUD AJAX: `index`, `list_ajax`, `store`, `update`, `delete`, `restore`. `assertAjaxPost()` en todos los POST |
| `app/Views/Financial/Suppliers/categories_index.php` | Nuevo | DataTable Guard §6, CSRF §2, modal alta/edición, modal confirmación con advertencia `hasSuppliers`, botón Restaurar |
| `app/Models/Financial/FinSupplierModel.php` | Modificado | `category_id` en `$allowedFields` y validación `permit_empty\|is_natural_no_zero`. JOIN `fsc.name AS category_name` en `_get_datatables_query()`. `findWithCategory()` |
| `app/Controllers/Financial/BaseFinancialController.php` | Modificado | Instancia `FinSupplierCategoryModel`. Helpers `assertAjaxPost()` y `jsonWithCsrf()` |
| `app/Controllers/Financial/SupplierController.php` | Modificado | `getForSelect()` a la vista; listado/offcanvas/get_ajax usan `category_name`; `_extractSupplierData()` lee `category_id` |
| `app/Config/Routes.php` | Modificado | Grupo `nat/suppliers/categories` con alias `suppliers.categories.*` |
| `app/Views/Partials/loggedin_sidebar.php` | Modificado | Ítem "Categorías" con `url_is('nat/suppliers/categories*')` y `fas fa-tags` |
| `app/Views/Financial/Suppliers/suppliers_index.php` | Modificado | Select2 de categoría en modales de alta/edición; filtro por `category_id`; badge de categoría en listado/offcanvas |
| `app/Database/Seeds/SupplierSeeder.php` | Modificado | Usa `category_id` resuelto por nombre de categoría |

#### Migraciones Ejecutadas

| Archivo de Migración | Acción | Tablas Afectadas | ¿Corrió OK? |
|----------------------|--------|------------------|-------------|
| `2026-09-18-000001_CreateFinSupplierCategories.php` | CREATE + seed | `fin_supplier_categories` (4 filas: Infraestructura, Hardware, Servicios, Administrativo) | Sí |
| `2026-09-18-000002_AlterFinSuppliersAddCategoryId.php` | ALTER + data migrate + DROP | `fin_suppliers`: añadió `category_id` (INT NULL), mapeó 8 proveedores (1→Infraestructura, 2→Hardware, 3→Servicios, 4→Administrativo), FK `fk_fin_suppliers_category_id` ON DELETE SET NULL, eliminó `supplier_type` | Sí |
| `2026-09-18-140000/140100/140200` (HR Payroll, Task 011) | CREATE | `hr_payroll_*` | Sí — **efecto colateral** de `php spark migrate`. Esas migraciones ya estaban en disco (otro agente). No las autoricé ni las edité. |

Orden respetado: 000001 → 000002. Verificado con `php spark db:table fin_suppliers --metadata` (`supplier_type` ya no existe; `category_id` sí).

#### 🔒 Sección Obligatoria — Permisos (§7) y Bitácora (§8)

**§7 — Mapa de permisos implementados:**

| Ruta / Acción | Tipo | Filtro en Routes.php | Guard can() en Vista |
|---------------|------|----------------------|----------------------|
| `suppliers.categories.index` (`GET nat/suppliers/categories`) | GET | `permission:catalog.access` (+ grupo `session`) | Sidebar: `can('catalog.access')` (bloque Proveedores) |
| `suppliers.categories.list_ajax` | POST | `permission:catalog.access` | N/A — AJAX |
| `suppliers.categories.store` | POST | `permission:catalog.manage-suppliers` | Botón "Nueva Categoría": `can('catalog.manage-suppliers')` |
| `suppliers.categories.update` | POST | `permission:catalog.manage-suppliers` | Botón editar en DataTable: `can('catalog.manage-suppliers')` |
| `suppliers.categories.delete` | POST | `permission:catalog.manage-suppliers` | Botón eliminar en DataTable: `can('catalog.manage-suppliers')` |
| `suppliers.categories.restore` | POST | `permission:catalog.manage-suppliers` | Botón restaurar en DataTable: `can('catalog.manage-suppliers')` |

Verificado con `php spark filter:check` y `php spark routes`.

**§8 — Mapa de entradas en bitácora:**

| Acción de negocio | Tipo logActivity | Descripción registrada |
|-------------------|-----------------|------------------------|
| Crear categoría | `'create_supplier_category'` | `'Creó categoría de proveedor: {nombre} (ID: {id})'` |
| Editar categoría | `'update_supplier_category'` | `'Editó categoría de proveedor: {nombre} (ID: {id})'` |
| Eliminar categoría (soft) | `'delete_supplier_category'` | `'Eliminó (soft) categoría de proveedor: {nombre} (ID: {id})'` |
| Restaurar categoría | `'restore_supplier_category'` | `'Restauró categoría de proveedor: {nombre} (ID: {id})'` |

---

#### Cumplimiento de Estándares Técnicos

| § | Estándar | ¿Cumplido? | Evidencia / Notas |
|---|----------|------------|-------------------|
| §1 | Guard AJAX en endpoints | ✅ | `list_ajax/store/update/delete/restore` llaman `$this->assertAjaxPost()` (404 si no es AJAX POST). Fetch envía `X-Requested-With: XMLHttpRequest`. |
| §2 | CSRF renovación DataTables | ✅ | Hidden `#csrf_token` al inicio de `<main>`. `ajax.data` inyecta el token. `$('#categories-table').on('xhr.dt', …)` actualiza el hash. |
| §2 | CSRF en header Fetch | ✅ | Store/update/delete/restore inyectan `csrf_header()` en el header y actualizan `#csrf_token` desde la respuesta (header + body `csrf`). |
| §3 | Rutas con alias | ✅ | `'as' => 'suppliers.categories.*'`. Vistas y sidebar usan `route_to()`. 0 `site_url()` en la vista nueva. |
| §4 | Sidebar activo | ✅ | `url_is('nat/suppliers/categories*') ? 'active' : ''`. El ítem "Proveedores" se excluye de `categories*` para no marcar ambos. |
| §5/§16 | DataTableTrait + column_order | ✅ | `$column_order = [null, 'name', 'description', 'active', null]` y `$column_search = ['name', 'description']` + `$datatableConfig` (el trait lee esta última). `get_datatables()` del trait y `get_datatables_total()`. JOIN de proveedores está en `FinSupplierModel::_get_datatables_query()` (equivalente a `getListData()` del plan). |
| §6 | Guard DataTable isDataTable | ✅ | `if ($.fn.dataTable.isDataTable('#categories-table')) return;` |
| **§7** | **Filtros de permiso Shield** | **✅** | **Ver tabla arriba. Botones de escritura ocultos sin `catalog.manage-suppliers`.** |
| **§8** | **Bitácora de actividad** | **✅** | **Ver tabla arriba. Nombre e ID en las 4 mutaciones.** |
| §9 | Transacciones en Modelo | ✅ | N/A funcional — operaciones de tabla única. 0 `Database::connect()` / `transStart` en el controlador. |
| §10 | Select2 dropdownParent | ✅ | Select2 de categoría en alta/edición de proveedor: `dropdownParent: $('#modalCreateSupplier')` y `dropdownParent: $('#modalEditSupplier')`. El plan pedía `$('#supplier-modal')`; esos IDs no existen (hay dos modales). Se usaron los IDs reales para que el z-index/foco funcionen. |
| §11 | Fechas | N/A | La entidad no maneja fechas de negocio. Timestamps de modelo en ISO. |
| §12 | Solo FontAwesome | ✅ | `fas fa-tags`, `fas fa-edit`, `fas fa-trash-alt`, `fas fa-undo`. Sin Lucide en DataTables/AJAX. |
| §13 | Breadcrumb | ✅ | `['Inicio' => route_to('dashboard.index'), 'Proveedores' => route_to('suppliers.index'), 'Categorías' => '']` |
| §14 | Extend layout maestro | ✅ | `extend($layout)` + `title` + `main`. JS en `pageFooterScripts` porque el layout **no** renderiza `scripts`. |
| §15 | viewData / outputData | ✅ | `index()`: `renderLayout()` + `$this->viewData`. AJAX: `setOutputSuccess()` / `setOutputError()` + `jsonWithCsrf($this->outputData)`. |
| §17 | notifyShow() | ✅ | JS usa `notifyShow(res.message, 'success'\|'danger')`. 0 `alert()` / `confirm()` en `categories_index.php` (confirmación vía modal Bootstrap). |
| §18 | Uploads organizados | N/A | Sin uploads. |
| §19 | Soft deletes + allowedFields + validationRules | ✅ | `$useSoftDeletes = true`, `$protectFields = true`, `$allowedFields = ['name', 'description', 'active']`. Validación `name` unique en el modelo. |
| §20 | Catálogos estáticos | N/A | Override explícito del plan: el usuario pidió tabla DB + softdelete para históricos. |
| §21 | AlertService | ✅ | No se usa `AlertService`. Delete bloqueado con `hasSuppliers()` → `setOutputError()`. El modal de confirmación muestra alerta visual roja y oculta el botón Eliminar si hay proveedores vinculados. |

#### Deuda Técnica y Pendientes

- [ ] E2E en navegador no ejercido: `https://sandbox.especialistashosting.com/nat/suppliers/categories` responde **401 Basic Auth** nginx (`Area de desarrollo`) antes del login Shield. Verificado con `php -l`, `php spark migrate`, `php spark db:table`, `php spark routes` y `php spark filter:check`.
- [ ] §10: `dropdownParent` apunta a `#modalCreateSupplier` / `#modalEditSupplier` (IDs reales), no a `#supplier-modal` (ID que el plan asumía y que no existe en la UI actual de dos modales).
- [ ] `php spark migrate` aplicó además las migraciones HR Payroll `2026-09-18-140000/140100/140200` que ya estaban en disco (Task 011, otro agente). No se revirtieron.
- [ ] El layout ya incluye `#csrf_token`; la vista añade otro por §2 (IDs duplicados, mismo patrón que proveedores). `getElementById` toma el del layout.
- [ ] `FinSupplierModel::getSupplierTypes()` se conservó como wrapper de `getForSelect()` por compatibilidad. El enum hardcodeado se eliminó.

#### Notas para el Siguiente Agente

- Columna `fin_suppliers.supplier_type` **ya no existe**. Usar `category_id` (FK nullable a `fin_supplier_categories.id`, ON DELETE SET NULL).
- El delete de categoría está bloqueado si hay proveedores no-eliminados con esa `category_id`. Soft-delete de categoría no dispara el SET NULL (no es DELETE físico).
- Seed inicial: id 1 Infraestructura, 2 Hardware, 3 Servicios, 4 Administrativo. Los 8 proveedores seed mapeados.
- Registry §2: fila **010** corresponde a este REPORTE-014.

---

### REPORTE-015: Sistema de Nóminas (Payroll)

**Tarea ID:** 011
**Archivo de plan:** `agentic/011_hr_payroll_nominas.md`
**Agente / Modelo:** Cursor Grok 4.6
**Fecha de ejecución:** 2026-09-18
**Sesión de chat:** Task 011 — módulo de nóminas ligado a sueldos de RR.HH.

---

#### Archivos Creados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `app/Database/Migrations/HR/2026-09-18-140000_CreateHrPayrollPeriods.php` | Nuevo | Tabla `hr_payroll_periods` con `disableForeignKeyChecks()` |
| `app/Database/Migrations/HR/2026-09-18-140100_CreateHrPayrollEntries.php` | Nuevo | Tabla `hr_payroll_entries` (FK periodo + perfil) |
| `app/Database/Migrations/HR/2026-09-18-140200_CreateHrPayrollSchedules.php` | Nuevo | Tabla `hr_payroll_schedules` (planeador) |
| `app/Models/HR/HrPayrollPeriodModel.php` | Nuevo | DataTableTrait, `PERIOD_TYPES`, `createPeriodWithEntries()` en transacción §9, `getDashboardStats()`, `getWithDetails()` |
| `app/Models/HR/HrPayrollEntryModel.php` | Nuevo | Soft-delete, `PAYMENT_METHODS`, `getByPeriod()`, `calculateTotals()`, `updateEntryFields()`, `markEntryPaid()` |
| `app/Models/HR/HrPayrollScheduleModel.php` | Nuevo | `getUpcoming()`, `getAllWithBranch()` |
| `app/Controllers/HR/PayrollController.php` | Nuevo | Dashboard, CRUD periodos/entradas, recibo, calendario. 0 `Database::connect()` / `transStart` |
| `app/Controllers/HR/BaseHrController.php` | Modificado | `assertAjaxPost()`, `jsonWithCsrf()`, `UserActivityLogsModel` |
| `app/Views/HR/Payroll/payroll_index.php` | Nuevo | 4 cards + DataTable Guard + modal crear periodo (Select2) + modal calendario |
| `app/Views/HR/Payroll/payroll_show.php` | Nuevo | Detalle del periodo, DataTable de entradas, modales edición/pago |
| `app/Views/HR/Payroll/payroll_receipt.php` | Nuevo | Recibo de impresión (layout sin sidebar) |
| `app/Views/HR/Payroll/payroll_schedule.php` | Nuevo | Vista completa del planeador |
| `app/Views/HR/Payroll/payroll_schedule_modal.php` | Nuevo | Partial CRUD de calendarios |
| `app/Views/Layouts/print_layout.php` | Nuevo | Layout sin sidebar/topbar; secciones `title`/`main`/`scripts` |
| `app/Views/Layouts/user_loggedin_layout.php` | Modificado | Se añadió `renderSection('scripts')` tras `pageFooterScripts` para cumplir §14 |
| `app/Config/Routes.php` | Modificado | Grupo `nat/hr/payroll` con alias `hr.payroll.*` |
| `app/Config/AuthGroups.php` | Modificado | Permiso `hr.manage-payroll` (cubierto por `hr.*` en superadmin/admin/hr) |
| `app/Views/Partials/loggedin_sidebar.php` | Modificado | Ítem Nóminas con `url_is('nat/hr/payroll*')` y `route_to('hr.payroll.index')` |
| `app/Controllers/System/AlertController.php` | Modificado | `_detectPendingPayrolls()` con `_buildAutoAlert()` + `_safeDetect()` y permiso `hr.manage-payroll` |

#### Migraciones Ejecutadas

| Archivo de Migración | Acción | Tablas Afectadas | ¿Corrió OK? |
|----------------------|--------|------------------|-------------|
| `2026-09-18-140000_CreateHrPayrollPeriods.php` | CREATE | `hr_payroll_periods` | Sí — batch 29. `php spark db:table` lista la tabla |
| `2026-09-18-140100_CreateHrPayrollEntries.php` | CREATE | `hr_payroll_entries` | Sí — batch 29 |
| `2026-09-18-140200_CreateHrPayrollSchedules.php` | CREATE | `hr_payroll_schedules` | Sí — batch 29 |

Orden respetado: periods → entries → schedules. Las tres usan `disableForeignKeyChecks()`.

> Nota: el mismo batch 29 también contiene migraciones de Task 010 (`fin_supplier_categories`). Timestamp `2026-09-18-140000` coincide con Task 001 (`AddLegalDocumentPathsToOrgBranches`); CI4 las distingue por nombre de clase. Ambas ya corridas.

#### 🔒 Sección Obligatoria — Permisos (§7) y Bitácora (§8)

**§7 — Mapa de permisos implementados:**

| Ruta / Acción | Tipo | Filtro en Routes.php | Guard can() en Vista |
|---------------|------|----------------------|----------------------|
| `hr.payroll.index` | GET | `permission:hr.access` (grupo hr + grupo payroll) | Sidebar: bloque `hr.access`; `url_is('nat/hr/payroll*')` |
| `hr.payroll.list_ajax` | POST | `permission:hr.access` | N/A — AJAX |
| `hr.payroll.create` | GET | `permission:hr.manage-payroll` | Botón "Nueva Nómina": `can('hr.manage-payroll')` |
| `hr.payroll.store` | POST | `permission:hr.manage-payroll` | Modal de alta solo si `can('hr.manage-payroll')` |
| `hr.payroll.show` | GET | `permission:hr.access` | N/A — lectura |
| `hr.payroll.approve` | POST | `permission:hr.manage-payroll` | Botón Aprobar: `can('hr.manage-payroll')` |
| `hr.payroll.cancel` | POST | `permission:hr.manage-payroll` | Botón Cancelar: `can('hr.manage-payroll')` |
| `hr.payroll.entry.update` | POST | `permission:hr.manage-payroll` | Botón editar entrada: `can('hr.manage-payroll')` |
| `hr.payroll.entry.paid` | POST | `permission:hr.manage-payroll` | Botón pagar: `can('hr.manage-payroll')` |
| `hr.payroll.period.paid` | POST | `permission:hr.manage-payroll` | Botón "Marcar todas como pagadas": `can('hr.manage-payroll')` |
| `hr.payroll.receipt` | GET | `permission:hr.access` | N/A — lectura |
| `hr.payroll.schedule` | GET | `permission:hr.access` | N/A — lectura |
| `hr.payroll.schedule_ajax` | POST | `permission:hr.access` | N/A — AJAX |
| `hr.payroll.schedule.store` | POST | `permission:hr.manage-payroll` | Formulario calendario: `can('hr.manage-payroll')` |
| `hr.payroll.schedule.update` | POST | `permission:hr.manage-payroll` | Botón editar calendario |
| `hr.payroll.schedule.delete` | POST | `permission:hr.manage-payroll` | Botón eliminar calendario |

**§8 — Mapa de entradas en bitácora:**

| Acción de negocio | Tipo logActivity | Descripción registrada |
|-------------------|-----------------|------------------------|
| Crear periodo | `'create_payroll_period'` | `'Creó período de nómina: {name} (ID: {id})'` |
| Aprobar periodo | `'approve_payroll_period'` | `'Aprobó período de nómina: {name} (ID: {id})'` |
| Cancelar periodo | `'cancel_payroll_period'` | `'Canceló período de nómina: {name} (ID: {id})'` |
| Editar entrada | `'update_payroll_entry'` | `'Actualizó entrada ID: {entryId} Trabajador: {workerName}'` |
| Pagar entrada | `'payroll_entry_paid'` | `'Marcó como pagada entrada ID: {entryId}'` |
| Pagar periodo completo | `'payroll_period_paid'` | `'Marcó todas las entradas del período ID: {periodId}'` |
| Crear calendario | `'create_payroll_schedule'` | `'Creó calendario de nómina: {name} (ID: {id})'` |
| Editar calendario | `'update_payroll_schedule'` | `'Actualizó calendario de nómina: {name} (ID: {id})'` |
| Eliminar calendario | `'delete_payroll_schedule'` | `'Eliminó calendario de nómina: {name} (ID: {id})'` |

---

#### Cumplimiento de Estándares Técnicos

| § | Estándar | ¿Cumplido? | Evidencia / Notas |
|---|----------|------------|-------------------|
| §1 | Guard AJAX en endpoints | ✅ | `list_ajax`, `store`, `approve`, `cancel`, `updateEntry`, `markPaid`, `markPeriodPaid`, `schedule_ajax`, `storeSchedule`, `updateSchedule`, `deleteSchedule` llaman `$this->assertAjaxPost()`. GET (`index`, `create`, `show`, `receipt`, `schedule`) sin guard. Fetch envía `X-Requested-With: XMLHttpRequest`. |
| §2 | CSRF renovación DataTables | ✅ | Hidden `#csrf_token` al inicio de `<main>` en index/show/receipt/schedule. `$('#payroll-table').on('xhr.dt', …)` actualiza el hash. |
| §2 | CSRF en header Fetch | ✅ | Mutaciones inyectan `<?= csrf_header() ?>` y capturan el nuevo token de la respuesta. |
| §3 | Rutas con alias | ✅ | Todas `'as' => 'hr.payroll.*'`. Vistas usan `route_to()`. 0 `site_url()` en vistas nuevas. Verificado con `php spark routes`. |
| §4 | Sidebar activo | ✅ | `url_is('nat/hr/payroll*')` dentro del bloque `hr.access`. Link con `route_to('hr.payroll.index')`. |
| §5/§16 | DataTableTrait + column_order | ✅ | `HrPayrollPeriodModel` usa el trait, `$column_order`/`$column_search` y `$datatableConfig`. `_get_datatables_query()` con JOIN sucursal + subquery de trabajadores. Show usa DataTable client-side. |
| §6 | Guard DataTable isDataTable | ✅ | `if ($.fn.dataTable.isDataTable('#payroll-table')) return;` e idem `#payroll-entries-table`. |
| **§7** | **Filtros de permiso Shield** | **✅** | **Ver tabla arriba. Permiso nuevo `hr.manage-payroll` en `AuthGroups`. Lectura `hr.access`; escritura `hr.manage-payroll`.** |
| **§8** | **Bitácora de actividad** | **✅** | **Ver tabla arriba. Todas las mutaciones (periodo, entrada, calendario) tienen `logActivity`.** |
| §9 | Transacciones en Modelo | ✅ | `createPeriodWithEntries()`, `updateEntryFields()`, `markEntryPaid()`, `markAllEntriesPaid()` usan `$this->db->transStart/Complete`. 0 `Database::connect()` / `transStart` en `PayrollController`. |
| §10 | Select2 dropdownParent | ✅ | `#period-branch` en modal crear: `dropdownParent: $('#create-period-modal')`. |
| §11 | Fechas | ✅ | Formularios `<input type="date">` (inicio/fin/pago/calendario). DataTable muestra `d/m/Y`. DB ISO `YYYY-MM-DD`. |
| §12 | Solo FontAwesome | ✅ | `fa-money-check-alt`, `fa-check`, `fa-times`, `fa-receipt`, `fa-calendar-alt`, `fa-dollar-sign`. 0 Lucide en vistas de nómina. |
| §13 | Breadcrumb | ✅ | Index: Inicio / RR.HH / Nóminas. Show: … / Nóminas / Período #X. Receipt: … / Recibo. Instancia `Breadcrumb([])` para no mezclar con "Recursos Humanos" del base. |
| §14 | Extend layout maestro | ✅ | `extend($layout)` + `title`/`main`/`scripts`. Recibo usa `Layouts/print_layout`. Se añadió `renderSection('scripts')` al layout autenticado. |
| §15 | viewData / outputData | ✅ | GET: `viewData` + `renderLayout()`. POST-AJAX: `setOutputSuccess()`/`setOutputError()` + `jsonWithCsrf($this->outputData)` (list_ajax usa el mismo helper con payload DataTables). |
| §17 | notifyShow() | ✅ | Feedback AJAX con `notifyShow(res.message, 'success'\|'danger')`. 0 `alert()` / `console.log()` en vistas de nómina. Confirmaciones destructivas usan `window.confirm` (mismo patrón que OC). |
| §18 | Uploads organizados | N/A | Recibos son vistas HTML; no hay uploads. |
| §19 | Soft deletes + allowedFields + validationRules | ✅ | Period y Entry: `$useSoftDeletes = true`, `$protectFields = true`, `$allowedFields` y `$validationRules` en el modelo. Schedule sin soft-delete (el plan no define `deleted_at`). |
| §20 | Catálogos estáticos | ✅ | `HrPayrollPeriodModel::PERIOD_TYPES` y `HrPayrollEntryModel::PAYMENT_METHODS` (+ `STATUSES` / `PAYMENT_STATUSES`). Sin tablas extra. |
| §21 | Alertas automáticas | ✅ | `_detectPendingPayrolls()` usa `_buildAutoAlert()`, integrado en `_detectAll()` con `_safeDetect()` y `can('hr.manage-payroll')`. |

#### Deuda Técnica y Pendientes

- [ ] ISR no se calcula con tablas SAT; se pre-llena con `hr_employment_data.isr_retention` y es editable a mano (el plan lo declara así).
- [ ] Horas extra se almacenan pero no se convierten a importe (no hay tarifa por hora en el esquema). El bruto = salario del periodo + bonos + prestaciones.
- [ ] `create()` GET redirige a `index?open=create` (abre el modal), igual que sucursales. No hay vista `payroll_create.php` separada.
- [ ] Rutas extra de calendario (`schedule/store|update|delete`) no estaban en el listado mínimo del plan; se añadieron porque el modal pide CRUD con `hr.manage-payroll`.
- [ ] Salario **no** está en `hr_profiles`: vive en `hr_employment_data` (`current_salary`, `daily_salary`, `imss_fee`, `infonavit_contribution`). No se creó campo `salary`. Las entradas automáticas leen esos campos vía JOIN.
- [ ] Timestamp de migración `2026-09-18-140000` colisiona con Task 001 (otra clase). CI4 las corrió ambas; no renombrar el archivo ya migrado.
- [ ] E2E en navegador no ejercido: el host responde **401 Basic Auth** nginx (`Area de desarrollo`) antes del login Shield. Verificado con `php -l`, `php spark migrate`, `php spark routes` y `php spark db:table`.

#### Notas para el Siguiente Agente

- Permiso de escritura: **`hr.manage-payroll`** (nuevo). Ya existía `hr.payroll` en AuthGroups; no se reutilizó porque el plan pidió el nombre nuevo. Grupos con `hr.*` cubren ambos.
- Al generar un periodo se toman trabajadores `hr_employment_data.status = 'active'`. Bruto: `daily_salary * worked_days` si hay SDI; si no, prorrateo de `current_salary` (semanal/4, quincenal/2, mensual entero). IMSS/INFONAVIT/ISR se copian del expediente laboral.
- Recibo: logo de sucursal (`org_branches.logo_path`) con fallback a `org_company_profile.logo_path`. Layout `Layouts/print_layout`.
- Detector de alertas: entradas `pending` cuyo `hr_payroll_periods.end_date` es anterior a hoy − 5 días.
- Registry §2: fila **011** corresponde a este REPORTE-015.

---

### REPORTE-016: Núcleo del sistema de plugins

**Tarea ID:** PLUGIN-000
**Archivo de plan:** `agentic/plugin_000_plugin_system_core.md` (v1.2)
**Agente / Modelo:** Cursor Grok 4.7
**Fecha de ejecución:** 2026-10-16
**Sesión de chat:** Núcleo de plugins, sin los plugins 001–006

#### Archivos creados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `app/Libraries/PluginSystem/*.php` | Nuevo | Contrato, manager, respaldos, hooks y market |
| `app/Controllers/PluginSystem/PluginBaseController.php` | Nuevo | Base AJAX, CSRF y bitácora para plugins |
| `app/Controllers/Admin/PluginMarketController.php` | Nuevo | Market |
| `app/Views/Admin/plugins_market.php` | Nuevo | UI del market |
| `app/Views/PluginSystem/sidebar_section.php` | Nuevo | Ítems de sidebar de plugins |
| `app/Database/Migrations/System/2026-09-18-20000{0..3}_*.php` | Nuevo | Cuatro tablas `sys_*` |
| `app/Commands/PluginDoctor.php` | Nuevo | `plugin:doctor` |
| `app/Commands/PluginAction.php` | Nuevo | Ciclo de vida por CLI |

#### Migraciones ejecutadas

| Archivo de Migración | Acción | Tablas Afectadas | ¿Corrió OK? |
|----------------------|--------|------------------|-------------|
| `2026-09-18-200000_CreateSysPlugins.php` | CREATE | `sys_plugins` | Sí |
| `2026-09-18-200001_CreateSysPluginPermissions.php` | CREATE | `sys_plugin_permissions` | Sí |
| `2026-09-18-200002_CreateSysPluginSettings.php` | CREATE | `sys_plugin_settings` | Sí |
| `2026-09-18-200003_CreateSysPluginBackups.php` | CREATE | `sys_plugin_backups` | Sí |

#### 🔒 Sección Obligatoria — Permisos (§7) y Bitácora (§8)

**§7 — Mapa de permisos implementados:**

| Ruta / Acción | Tipo | Filtro en Routes.php | Guard can() en Vista |
|---------------|------|----------------------|----------------------|
| `admin.plugins.index` | GET | `permission:admin.manage-plugins` (grupo `nat` con `session`) | Sidebar: `can('admin.manage-plugins')` |
| `admin.plugins.settings` | GET | `permission:admin.manage-plugins` | N/A — la pantalla ya exige el permiso |
| `admin.plugins.install` | POST | `permission:admin.manage-plugins` | Botón Instalar solo si el manifiesto es instalable |
| `admin.plugins.uninstall` | POST | `permission:admin.manage-plugins` | Botón solo con el plugin inactive |
| `admin.plugins.activate` | POST | `permission:admin.manage-plugins` | Botón Activar |
| `admin.plugins.deactivate` | POST | `permission:admin.manage-plugins` | Botón Desactivar |
| `admin.plugins.restore` | POST | `permission:admin.manage-plugins` | Botón Restaurar |
| `admin.plugins.purge` | POST | `permission:admin.manage-plugins` | Botón Purgar, deshabilitado hasta escribir ELIMINAR |
| `admin.plugins.update` | POST | `permission:admin.manage-plugins` | Botón Actualizar |
| `admin.plugins.settings.save` | POST | `permission:admin.manage-plugins` | Formulario de ajustes |

**§8 — Mapa de entradas en bitácora:**

| Acción de negocio | Tipo logActivity | Descripción registrada |
|-------------------|-----------------|------------------------|
| Instalar | `plugin_install` | `Instaló el plugin '{slug}'.` |
| Desinstalar | `plugin_uninstall` | `Desinstaló el plugin '{slug}'. Datos archivados.` |
| Activar | `plugin_activate` | `Activó el plugin '{slug}'.` |
| Desactivar | `plugin_deactivate` | `Desactivó el plugin '{slug}'.` |
| Restaurar | `plugin_restore` | `Restauró el respaldo de plugin #{id}.` |
| Purgar | `plugin_purge` | `Purgó definitivamente el respaldo de plugin #{id}.` |
| Actualizar | `plugin_update` | `Actualizó el plugin '{slug}'.` |
| Guardar ajustes | `plugin_settings` | `Actualizó la configuración del plugin '{slug}'.` Sin valores. |

#### Cumplimiento de Estándares Técnicos

| § | Estándar | ¿Cumplido? | Evidencia / Notas |
|---|----------|------------|-------------------|
| §1 | Guard AJAX en endpoints | ✅ | `assertAjaxPost()` en todos los POST del Market |
| §2 | CSRF en header Fetch | ✅ | Cabecera `csrf_header()` y renovación desde la respuesta. No hay DataTables |
| §3 | Rutas con alias | ✅ | `admin.plugins.*`. La vista usa `route_to()` |
| §4 | Sidebar activo | ✅ | `url_is('nat/admin/plugins*')` |
| §5/§6 | DataTables | ✅ | No aplica |
| **§7** | **Filtros de permiso Shield** | **✅** | **`admin.manage-plugins` en cada ruta. Los grupos `admin` y `superadmin` lo cubren con `admin.*`** |
| **§8** | **Bitácora de actividad** | **✅** | **Tabla de arriba. CLI incluido** |
| §9 | Transacciones en Modelo | ✅ | El controlador no llama `db_connect()`. El catálogo de permisos vive en `PluginManager` |
| §12 | Solo FontAwesome | ✅ | `fas` en el market y en el partial |
| §13 | Breadcrumb | ✅ | Inicio > Administrador > Plugins |
| §14 | Extend layout maestro | ✅ | `plugins_market.php` extiende `$layout` |
| §15 | viewData / outputData | ✅ | `renderLayout()` y `jsonWithCsrf()` |
| §17 | notifyShow() | ✅ | Sin `alert()` ni `confirm()` |
| §19 | Soft deletes | ✅ | Tablas de registro, sin soft-delete, como indica la spec |
| §21 | Alertas | ✅ | El core solo agrega los detectores de plugins al final de `_detectAll()` |

#### Deuda Técnica y Pendientes

- [ ] La UI no se recorrió en el navegador: el host responde 401 Basic Auth antes de Shield.
- [ ] No hay subida ZIP ni comparación de `min_erp_version`.
- [ ] Los hooks reservados y los tabs de offcanvas siguen en PLUGIN-000b. Los plugins 001–006 no se codificaron.
- [ ] Las asignaciones huérfanas en `auth_permissions_users` tras desinstalar siguen siendo inertes.

#### Notas para el Siguiente Agente

- La spec que manda es `plugin_000` v1.2, sección 0b. §4 y §5 de la arquitectura son historia.
- Un plugin nuevo declara permisos con un prefijo propio (`servers.access`). Un prefijo que ya tenga comodín en `AuthGroups.matrix` se rechaza al instalar.
- `php spark plugin:doctor` y `php spark plugin:action install <slug>` son la vía de prueba mientras el 401 siga delante del login.


