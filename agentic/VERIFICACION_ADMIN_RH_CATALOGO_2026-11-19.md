# Verificación Admin, RH, Catálogo y alertas

Fecha: 2026-11-19 (CDMX). Rama: `cursor/agente-nube-841d`. Alcance: **sin plugins ni CRM**.

## Administrador

| Área | Estado | Notas |
|------|--------|--------|
| Usuarios CRUD + permisos | OK | Rutas `nat/user/*`, filtros `admin.manage-users`, `users.*` |
| Roles / grupos Shield | OK | `Users\RoleController`, permisos en `AuthGroups.php` |
| Bitácora global | OK | `admin.audit_log`, `admin.audit-log` + DataTables AJAX (Task 009) |
| Mercado de plugins | OK | `nat/admin/plugins/*` — **no probado en esta pasada** (fuera de alcance) |
| Organización (sucursales) | OK | Grupo `organization`, `admin.manage-organization` |

**Gap menor:** no hay detectores automáticos de “admin” en topbar (solo operativos RH/Finanzas/Catálogo); las alertas almacenadas vía `AlertService::dispatch()` sí respetan `required_permission`.

## Sistema de alertas

| Pieza | Estado | Notas |
|-------|--------|--------|
| Topbar AJAX | OK | `get_unread_ajax`, fusiona almacenadas + `_detect*` |
| Permisos almacenadas | OK | `getUnreadByUser` filtra `required_permission`; contador alineado en `getUnreadCount` |
| Permisos automáticas | OK (mejorado) | Detectores RH por `hr.view`/`hr.edit`; proveedores solo `catalog.manage-suppliers` / `purchasing.suppliers`; inventario `catalog.manage` o `catalog.access` |
| Plugins | Cableado | `PluginManager::runAlertDetectors($user)` — no evaluado plugin a plugin |
| RH documentación | OK (nuevo) | Faltantes de expediente, contrato activo, horario vigente, datos laborales |

## Recursos Humanos

| Funcionalidad | Estado | Rutas / código |
|---------------|--------|----------------|
| Expediente (perfil + fiscal) | OK | `WorkerController`, `hr_profiles` |
| Datos laborales | OK | `hr_employment_data`, modelo `HrEmploymentModel` |
| Contrato digital + PDF | OK | `WorkerContractController`, `ContractService`, TinyMCE |
| Historial / versiones / forks | OK | `contractHistory`, `WorkerContractModel::getContractTree` |
| Horarios | OK | `hr_worker_schedules` + catálogo turnos |
| Documentos seguros | OK | `WorkerDocumentController` (WRITEPATH) |
| Vacaciones / incidencias / finiquito | OK | Controladores dedicados + permisos `hr.edit` |
| Nóminas | OK | `PayrollController` + alerta `hr.manage-payroll` |

**Sugerencia VPS:** revisar trabajadores `active` sin contrato o sin documentos tras migrar datos legacy.

## Catálogo / Productos

| Funcionalidad | Estado | Notas |
|---------------|--------|--------|
| Productos CRUD + DataTables | OK | `CatalogProductController`, rutas con `as` |
| Categorías, planes, relaciones | OK | `CatalogPlanController` |
| Inventario / kardex / min alert | OK | `InventoryController`; alerta stock bajo |
| Marcas y sucursal (BU) | OK | `brand_id`, `business_unit_id` en modelo |
| Puente proveedores | OK | `CatalogProductSupplierModel`, enlace desde proveedores |
| Compatibilidad plugins | Parcial | Núcleo estable; hooks `catalog.product.show.tabs` **documentados pero no cableados** en v1 (`ARQUITECTURA_PLUGIN_SYSTEM.md`) — correcto no extender CRM/plugins en esta tarea |

## Regla de agente

Añadida `.cursor/rules/04-agente-modelos-y-subagentes.mdc`: sin subagentes ni modelos pesados por defecto; Composer/Grok.