# Panel de Tareas (TODO)

Seguimiento de pendientes y próximos hitos. Referencia técnica: [DOCUMENTACION_TECNICA.md](DOCUMENTACION_TECNICA.md). Handoff del agente anterior: [ESTATUS_ENTREGA.md](ESTATUS_ENTREGA.md). Esquema organización: [SCHEMA_ORGANIZACION.md](SCHEMA_ORGANIZACION.md).

**Última revisión:** 2026-10-12 (parches de arquitectura del sistema de plugins aplicados; ver `agentic/AUDITORIA_PLUGIN_SYSTEM.md`).

---

## ✅ Completado (no requiere acción inmediata)

### Organización y configuración
- [x] Perfil de compañía matriz (`org_company_profile`) — `Organization/profile.php`
- [x] CRUD de sucursales / unidades de negocio (`org_branches`) — DataTables, logos, permisos `admin.manage-organization`
- [x] Documentación técnica ampliada (ViewData, OutputData, DataTableTrait, uploads, etc.)
- [x] Diccionario `SCHEMA_ORGANIZACION.md`

### Catálogo — Fase 1 (marcas y productos base)
- [x] Migración y modelo `catalog_brands`; seeder de marcas
- [x] Columna `brand_id` en `catalog_products` + selector en `product_form.php`
- [x] Migración `catalog_product_images`
- [x] Modelos: Products, Categories, Plans, Attributes, Stock, Movements, Images, Relations
- [x] CRUD de categorías (jerárquico)
- [x] Listado de productos (DataTables server-side) + filtros
- [x] Formulario dinámico producto (físico / servicio / digital)
- [x] Galería de imágenes en formulario de producto
- [x] Módulo de inventario por sucursal (`catalog_product_stock` + movimientos)

### Otros módulos con base operativa
- [x] Usuarios, roles y permisos (Shield)
- [x] RRHH: trabajadores, documentos, vacaciones, contratos/plantillas, incidencias básicas, finiquito/baja (parcial según permisos)

---

## ✅ Completado — Catálogo Fase 2 (producto ↔ unidad de negocio)

- [x] Migración `business_unit_id` (FK `org_branches.id`) + backfill productos existentes
- [x] `CatalogProductModel`, `CatalogProductController`, `product_form.php`, `products_index.php`
- [x] **UI Unificada:** Campo "Unidad de Negocio" habilitado para todos los tipos (Servicio, Físico, Digital) en Alta y Edición.
- [x] Filtro por unidad de negocio en listado
- [x] `PLAN_ORGANIZACION_CATALOGO.md` actualizado

## 🔥 Prioridad actual — Siguiente hito

Ver **Roadmap ERP** (Proveedores → CRM → Ventas).

---

## 🏢 Organización — Pendiente (no bloquea Fase 2)

- [x] **[DOCUMENTACIÓN LEGAL]** Subida y almacenamiento de archivos por sucursal (CSF, comprobante de domicilio, acta constitutiva) — migración ejecutada batch 28, `OrgBranchModel::saveBranch()` con `store_public_upload()`, logActivity por archivo. *(Task 001 — 2026-09-18)*
- [x] **[BITÁCORA GLOBAL]** Vista de auditoría global para administradores (`/nat/admin/audit-log`) con filtros avanzados (usuario, acción, rango de fechas), DataTable server-side, permiso `admin.audit-log`. *(Task 009 — 2026-09-18)*

---

## ❌ Pendientes de Recursos Humanos (mejoras)

- [x] **[NÓMINAS]** Sistema completo de nóminas: periodos, entradas por trabajador, recibo de impresión, planeador de calendario, 4 cards de dashboard, detector de alertas automáticas. Permiso nuevo `hr.manage-payroll`. *(Task 011 — 2026-09-18)*
- [ ] **[RRHH] Incidencias avanzadas:** retardos, faltas por enfermedad, permisos con flujo completo
- [ ] Carga de justificaciones y recetas médicas vinculadas a incidencias
- [ ] **[NÓMINAS v2]** Cálculo automático de ISR con tablas SAT (hoy editable manualmente)
- [ ] **[NÓMINAS v2]** Conversión de horas extra a importe (añadir `hourly_rate` a `hr_employment_data`)
- [ ] Generación de formatos PDF de baja (renuncia/finiquito) — revisar qué cubre ya `WorkerSettlementController`

---

## 🚀 Roadmap ERP (después de Fase 2 catálogo)

Orden sugerido según dependencias de negocio:

- [x] **[PROVEEDORES]** Módulo integral de proveedores
  - [x] Fase 1: BD y Modelos — migración ejecutada (Batch 18). Los 4 modelos completos y verificados.
  - [x] Fase 2: Controladores y Rutas — 33 rutas nombradas, 3 controladores, Shield, auditoría.
  - [x] Fase 3 (Vista principal): `suppliers_index.php` funcional — DataTable SSR, CSRF, Offcanvas 4 tabs, todos los modales.
  - [x] **[CATEGORÍAS]** Tabla `fin_supplier_categories` con soft-delete y CRUD propio (`SupplierCategoryController`). Refactor `supplier_type` enum → FK `category_id`. Guard `hasSuppliers()` en delete. Select2 con `dropdownParent` correcto en modales. *(Task 010 — 2026-09-18)*
  - [ ] **[PENDIENTE — ALTA]** `PurchaseOrderController::download(int $id)` + ruta `purchase-orders/download/(:num)` → PDF con `PdfLibrary` (vista previa + descarga). Ver §9 en `003_catalog_suppliers_part3_views.md`.
  - [ ] **[PENDIENTE — MEDIA]** Timeline de actividad: endpoint AJAX `suppliers/(:num)/activity` → filtra `UserActivityLogsModel` por acciones del proveedor y rellena `#oc-activity-timeline`.
  - [ ] **[PENDIENTE — MEDIA]** Gastos recurrentes: `fin_recurring_expenses` ya existe (Batch 6, FK a `fin_suppliers`). Crear `FinRecurringExpenseModel` + endpoints para listar/vincular al proveedor. El UI modal ya existe en la vista.
  - [ ] **[PENDIENTE — BAJA]** Vista standalone de Órdenes de Compra: `Financial/PurchaseOrders/purchase_orders_index.php` (el controlador ya tiene el método `index()`).
  - [ ] **[POLISH — OPCIONAL]** Split de la vista en partials (`Partials/supplier_modals.php`, `supplier_offcanvas.php`). Cursor pointer en filas del DataTable. Búsqueda con debounce.
- [ ] **[CLIENTES / CRM]** Compradores, contactos técnicos, perfiles de facturación — plan detallado en `agentic/004_crm_clients.md`
- [ ] **[VENTAS / POS]** Órdenes de venta, renovaciones, utilidad neta (requiere proveedores + clientes)
- [ ] **[CONTABILIDAD]** Cruce ingresos (ventas) vs egresos (proveedores)
- [ ] **[NOTIFICACIONES]** Triggers (stock bajo, vencimientos, bitácora crítica) + topbar
- [ ] **[ACTIVACIONES]** Aprovisionamiento (WHM, VPS, etc.) desacoplado del catálogo comercial
- [ ] Módulo de reportes base
- [ ] Vista “Stock crítico” por sucursal y marca (ver `PLAN_ORGANIZACION_CATALOGO.md`)
- [ ] **Hito 5:** Descuentos en catálogo (cuando catálogo + unidad de negocio estén estables)

---

## 🔔 Nota: Sistema de alertas (futuro)

Integrar con menú Notifications del topbar cuando existan Inventario + Ventas operativos:

- **Stock bajo:** `catalog_product_stock.stock < min_alert`
- **Vencimientos:** renovación de planes/suscripciones
- **Bitácora:** movimientos críticos de inventario
- **Implementación:** `NotificationService` desde modelos mutativos (DB + AJAX/WebSockets)

---

## ⚙️ Refactorizaciones y deuda técnica

- [ ] **[CONTRATOS]** Optimización del editor de plantillas y versionado
- [ ] **[USUarios]** Sección “Mi perfil” para empleados
- [ ] **[UI]** Estandarizar tablas a `table-striped`
- [ ] **[TÉCNICO]** Corregir dependencia de token CSRF en `main_users.php`
- [x] Revisar controladores legado con `$db->transStart()` directo — auditados y movidos a modelos (§9): `WorkerIncidenceController`, `WorkerController`, `HrWorkerScheduleModel`. *(Sprint 2026-09-18)*
- [x] **[LEGACY]** Eliminados controladores sin rutas activas: `Pelicula.php`, `Cliente.php`, `Login/Login.php`. Eliminados modelos huérfanos: `PeliculaModel`, `CategoriaModel`, `ClienteModel`, `UsuarioModel`. *(Sprint 2026-09-18)*
- [ ] **[SEGURIDAD]** Mover el almacenamiento de imágenes y archivos cargados (productos, logotipos, etc.) fuera de `public_html` (ej. a `WRITEPATH . 'uploads/'`) y servirlos a través de un controlador seguro con validación de permisos.
- [ ] **[UI/UX]** Corregir la vista responsive de todas las tablas DataTables del sistema

---

## 🐛 Deuda técnica detectada en sprint 2026-09-18

> No bloqueante. Detectada en auditoría arquitectónica. Ver `agentic/AUDITORIA_ARQUITECTURA.md` Apéndice D para contexto completo.

- [ ] **[DT-1 — CSRF IDs duplicados]** `user_loggedin_layout.php` (línea 37) y las vistas nuevas (payroll, categorías, bitácora global) ambas renderizan `<input id="csrf_token">`. `getElementById` toma el del layout (correcto). Solución futura: eliminar el input del layout y dejarlo solo en el `<main>` de cada vista.
- [ ] **[DT-2 — ISR nóminas manual]** `hr_payroll_entries.isr` se pre-llena desde `hr_employment_data.isr_retention` y es editable a mano. No hay cálculo automático con tablas SAT. Requiere tabla de tarifas ISR + lógica de cálculo en `HrPayrollEntryModel`.
- [ ] **[DT-3 — Horas extra sin importe]** `hr_payroll_entries.overtime_hours` se almacena pero no se convierte a monto salarial. Falta campo `hourly_rate` en `hr_employment_data`. Añadir el campo + fórmula en `createPeriodWithEntries()`.
- [ ] **[DT-4 — Export bitácora client-side]** Botón CSV en `global_activity.php` exporta solo la página visible del DataTable (client-side). Para exportar todos los registros filtrados se requiere una ruta server-side adicional (`admin.audit_log_export`).
- [ ] **[DT-5 — E2E en sandbox sin verificar]** Las 4 nuevas rutas (docs legales, bitácora global, categorías proveedores, nóminas) no se verificaron en navegador — el host devuelve **401 Basic Auth** nginx antes del login Shield. Verificadas via `php spark routes`, `php spark db:table`, `php spark filter:check` y `php -l`.

---

## 🔌 Sistema de Plugins — Arquitectura corregida v1.1 (2026-10-12)

> Arquitectura auditada contra el código real y parcheada. Implementación pendiente **por otro agente** (el orquestador no codifica).
> Spec de implementación vigente: `agentic/plugin_000_plugin_system_core.md` (**v1.1**, autocontenida).
> Marco conceptual: `agentic/ARQUITECTURA_PLUGIN_SYSTEM.md` (v1.1; §4 y §5 quedaron como referencia histórica).
> Auditoría y justificación de cada cambio: `agentic/AUDITORIA_PLUGIN_SYSTEM.md` (commit `7e1dbe5`).

### Plugin System Core (prerequisito — implementar PRIMERO)
- [ ] **[PLUGIN-000]** Infraestructura: `PluginManager`, `PluginInterface`, `BasePlugin`, `PluginBackupService`, `PluginHookRegistry`, `PluginMarket` + `PluginBaseController` — `app/Libraries/PluginSystem/`.
- [ ] **[PLUGIN-000]** 4 migraciones: `sys_plugins` (sin columna `config`), `sys_plugin_permissions`, `sys_plugin_settings` (fuente única de la configuración), `sys_plugin_backups` (con `class_dir`).
- [ ] **[PLUGIN-000]** **7 touch points** al ERP base: `Config/Autoload.php`, `Events.php`, `Routes.php`, `loggedin_sidebar.php`, `user_loggedin_layout.php`, `AlertController.php`, `UserController.php` (catálogo de permisos).
- [ ] **[PLUGIN-000]** `PluginMarketController` + vista `plugins_market.php` con flujo Desactivar → Desinstalar → Restaurar → Purgar.
- [ ] **[PLUGIN-000]** Respaldo de datos en desinstalación: `RENAME TABLE` atómico a `_bak_plg_*` + dump SQL en `WRITEPATH/plugins/backups/`, registro **antes** de renombrar. Purga solo tras confirmación textual ELIMINAR y sin exigir los archivos del plugin.
- [ ] **[PLUGIN-000]** Comando `php spark plugin:doctor` + criterios de aceptación CLI (el sandbox no permite verificar en navegador por el 401 Basic Auth, DT-5).

### Plugins individuales (orden corregido 2026-10-12)

> **Dependencias:** tras PLUGIN-000, las specs 001–006 deben revisarse contra el contrato de hooks v1 (solo `alert.detect` y `layout.footer`). Hoy 001, 003, 005 y 006 registran hooks reservados sin punto de dispatch.
> **PLUGIN-000b (nuevo, bloquea a 001 y 006):** definir el mecanismo real de tabs en offcanvas (inyección JSON/partial por plugin) para `supplier.show.tabs` / `catalog.product.show.tabs`, y el dispatch de `dashboard.widgets`.
> **Paralelizable sin 000b:** 002, 003, 004 y 005 (tras revisar sus specs al contrato v1), cada uno en su rama/worktree.

- [ ] **[PLUGIN-000b]** Mecanismo de tabs en offcanvas + `dashboard.widgets` (prerequisito de 001 y 006). Requiere decisión de diseño previa.
- [ ] **[PLUGIN-001]** Administrador de Servidores — DirectAdmin API, SSH web, monitor, KB+IA, daemon futuro. Plan: `agentic/plugin_001_server_admin.md` (revisar hooks; depende de 000b)
- [ ] **[PLUGIN-002]** Cotizaciones Avanzadas — builder multi-ítem, PDF, versioning, conversión a OC. Plan: `agentic/plugin_002_advanced_quotations.md`
- [ ] **[PLUGIN-003]** Firma Digital — flujo OTP email, PDF sellado, rutas públicas sin Shield. Plan: `agentic/plugin_003_digital_signature.md` (usa `quotation.show.actions`, hook reservado)
- [ ] **[PLUGIN-004]** WordPress Admin — REST API multi-site, plugins WP, usuarios, posts. Plan: `agentic/plugin_004_wordpress_admin.md`
- [ ] **[PLUGIN-005]** Chatbot CRM IA — WhatsApp + Telegram + webchat, Gemini/DeepSeek, handoff humano. Plan: `agentic/plugin_005_chatbot_crm.md` (usa `chatbot.contact.identified`, hook reservado)
- [ ] **[PLUGIN-006]** Administrador de Proyectos — Kanban+Lista+Calendario (FullCalendar), subtareas, time tracking. Plan: `agentic/plugin_006_project_manager.md` (revisar hooks; depende de 000b)

### Decisiones arquitectónicas clave (documentadas en `agentic/ARQUITECTURA_PLUGIN_SYSTEM.md`)
- Plugins en `plugins/{Directory}/` (PascalCase) con namespace `Plugins\{Directory}\`; `directory` es obligatorio en el manifiesto y distinto del `slug`
- Tablas con prefijo `plg_{slug}_` — nunca tocan tablas del ERP
- **Hooks v1 (contrato cerrado 2026-10-12):** solo `alert.detect` y `layout.footer` están cableados; el resto del catálogo queda reservado y sin punto de dispatch (ver §6)
- Rutas en `/nat/plugins/{route_prefix}/`
- Permisos: declarados en el manifiesto, registrados en `sys_plugin_permissions` e integrados al catálogo de Shield (sin filtro propio)
- Autoload por `Config\Autoload::$psr4` (sin `composer dump-autoload`) y migraciones con namespace exacto registrado en runtime
- Desinstalación en 3 etapas: Desactivar → Desinstalar (backup) → Purgar (irreversible)
