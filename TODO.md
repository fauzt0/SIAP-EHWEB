# Panel de Tareas (TODO)

Seguimiento de pendientes y próximos hitos.

| Referencia | Enlace |
|------------|--------|
| Estándares | [DOCUMENTACION_TECNICA.md](DOCUMENTACION_TECNICA.md) |
| Índice agentic (vivos / archivo) | [agentic/INDICE.md](agentic/INDICE.md) |
| Handoff histórico | [ESTATUS_ENTREGA.md](ESTATUS_ENTREGA.md) (solo contexto) |
| Organización — diccionario | [SCHEMA_ORGANIZACION.md](SCHEMA_ORGANIZACION.md) |
| Cumplimiento §1–§21 | [agentic/AUDITORIA_CUMPLIMIENTO_DOC_TECNICA.md](agentic/AUDITORIA_CUMPLIMIENTO_DOC_TECNICA.md) |
| Reglas Cursor | [.cursor/rules/](.cursor/rules/) |

**Última revisión:** 2026-11-19 (America/Mexico_City). Specs cerradas movidas a `agentic/archivo/planes-cerrados/`.

---

## ✅ Completado (no requiere acción inmediata)

### Organización y configuración
- [x] Perfil de compañía matriz (`org_company_profile`) — `Organization/profile.php`
- [x] CRUD de sucursales (`org_branches`) — DataTables, logos, permisos `admin.manage-organization`
- [x] Documentos legales por sucursal (CSF, comprobante, acta) — `OrgBranchModel`
- [x] `DOCUMENTACION_TECNICA.md`, `SCHEMA_ORGANIZACION.md`
- [x] Project rules `.cursor/rules/` + auditoría cumplimiento doc técnica

### Catálogo
- [x] Marcas, productos, categorías, planes, inventario, `business_unit_id` (Fase 2)
- [x] `PLAN_ORGANIZACION_CATALOGO.md` hitos 4.5–4.6

### Otros módulos operativos
- [x] Usuarios, roles y permisos (Shield)
- [x] RRHH: trabajadores, documentos, vacaciones, contratos/plantillas, incidencias básicas, finiquito/baja, **nóminas v1** (Task 011)
- [x] **Proveedores:** CRUD, categorías (010), OC, documentos, email estructura, gastos recurrentes (006–008 archivados)
- [x] **Alertas** — core §21 (`AlertController`, topbar, historial)
- [x] **Bitácora global** admin (009) — `admin.audit-log`
- [x] Refactor transacciones a modelos (005)

---

## 🔥 Prioridad actual

1. **CRM / clientes** — spec viva: `agentic/004_crm_clients.md`
2. **RRHH incidencias avanzadas** — spec viva: `agentic/002_hr_advanced_incidents.md`
3. **Roadmap:** ventas/POS y contabilidad (dependen de clientes + catálogo estable)

---

## ❌ Pendientes de Recursos Humanos

- [ ] **[RRHH] Incidencias avanzadas:** retardos, faltas, permisos con flujo completo (`002_hr_advanced_incidents.md`)
- [ ] Carga de justificaciones/recetas en `WRITEPATH/uploads/hr/justifications/`
- [ ] Reporte pre-nómina mensual por incidencias (si no se cubre en el mismo task)
- [ ] **[NÓMINAS v2]** Cálculo automático ISR (tablas SAT)
- [ ] **[NÓMINAS v2]** Horas extra → importe (`hourly_rate` en `hr_employment_data`)
- [ ] PDF de baja (renuncia/finiquito) — revisar cobertura de `WorkerSettlementController`

---

## 🚀 Roadmap ERP

Orden sugerido:

- [ ] **[CLIENTES / CRM]** `agentic/004_crm_clients.md`
- [ ] **[VENTAS / POS]** Órdenes de venta, renovaciones (proveedores + clientes)
- [ ] **[CONTABILIDAD]** Ingresos vs egresos
- [ ] Módulo de reportes base
- [ ] Vista **Stock crítico** por sucursal y marca (`PLAN_ORGANIZACION_CATALOGO.md` §4)
- [ ] **Hito 5:** Descuentos en catálogo
- [ ] **[ACTIVACIONES]** Aprovisionamiento hosting/VPS desacoplado del catálogo

### Proveedores — polish opcional (no bloquea roadmap)

- [x] OC automáticas desde recurrentes + origen Manual/Recurrente (2026-11-19)
- [x] Alertas de módulo (vencidos, OC pendientes, sin factura) + canal email `queued_until_smtp`
- [x] Dashboard de recurrentes con gráficas rápidas
- [ ] Split vista en partials (`supplier_modals`, `supplier_offcanvas`)
- [ ] Cursor pointer en filas DataTable, debounce en búsqueda
- [ ] Timeline actividad en offcanvas (endpoint `activity` existe; verificar UX completa)

---

## ⚙️ Refactorizaciones y deuda técnica

- [ ] **[CONTRATOS]** Editor de plantillas y versionado
- [ ] **[USUARIOS]** “Mi perfil” para empleados
- [ ] **[UI]** Estandarizar tablas a `table-striped`
- [ ] **[TÉCNICO]** Dependencia CSRF en `main_users.php`
- [ ] **[SEGURIDAD]** Uploads sensibles fuera de `public_html` vía controlador (productos/logos según política)
- [ ] **[UI/UX]** Tablas DataTables responsive
- [ ] **[§12]** Lucide → FontAwesome en partials globales (ver `AUDITORIA_CUMPLIMIENTO_DOC_TECNICA.md`)
- [ ] **[§3]** Alias `as` faltantes: `user/list`, `get_contracts/(:num)` en `Routes.php`

---

## 🐛 Deuda técnica (sprint 2026-09-18, sigue vigente)

Ver `agentic/AUDITORIA_ARQUITECTURA.md` Apéndice D.

- [ ] **[DT-1]** CSRF: `#csrf_token` duplicado layout vs vistas
- [ ] **[DT-2]** ISR nóminas manual
- [ ] **[DT-3]** Horas extra sin importe
- [ ] **[DT-4]** Export bitácora solo página visible (falta ruta server-side)
- [ ] **[DT-5]** E2E en sandbox (401 Basic Auth nginx)

---

## 🔌 Sistema de Plugins

> Specs en `agentic/plugin_*.md` y `ARQUITECTURA_PLUGIN_SYSTEM.md` — **no depurados en esta revisión**. Estado en commits de `feat/plugin-system-architecture`.

### Plugin System Core
- [x] PLUGIN-000 núcleo (Manager, mercado, doctor, backups)
- [ ] PLUGIN-000b tabs offcanvas + `dashboard.widgets`
- [ ] PLUGIN-001 … PLUGIN-006 (ver lista anterior en historial de git del TODO o `agentic/avance.md`)

*(Detalle de checkboxes históricos conservado en commits previos de este archivo si se necesita trazabilidad.)*

---

## 🔔 Notificaciones futuras (inventario + ventas)

Triggers adicionales (stock bajo masivo, renovaciones comerciales) cuando Inventario + Ventas estén operativos. Base actual: §21 + detectores en `AlertController`.