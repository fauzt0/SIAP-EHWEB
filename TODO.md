# Panel de Tareas (TODO)

Seguimiento de pendientes y próximos hitos. Referencia técnica: [DOCUMENTACION_TECNICA.md](DOCUMENTACION_TECNICA.md). Handoff del agente anterior: [ESTATUS_ENTREGA.md](ESTATUS_ENTREGA.md). Esquema organización: [SCHEMA_ORGANIZACION.md](SCHEMA_ORGANIZACION.md).

**Última revisión:** 2026-05-18 (alineado con estado del repositorio `main`).

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

- [ ] **[DOCUMENTACIÓN LEGAL]** Subida y almacenamiento de archivos por sucursal (CSF, comprobante de domicilio, acta constitutiva) en `branch_form.php` — hoy hay placeholder deshabilitado; definir tabla pivote o rutas en `uploads/organization/`

---

## ❌ Pendientes de Recursos Humanos (mejoras)

- [ ] **[RRHH] Incidencias avanzadas:** retardos, faltas por enfermedad, permisos con flujo completo
- [ ] Carga de justificaciones y recetas médicas vinculadas a incidencias
- [ ] Reporte mensual para prenómina
- [ ] Generación de formatos PDF de baja (renuncia/finiquito) — revisar qué cubre ya `WorkerSettlementController`

---

## 🚀 Roadmap ERP (después de Fase 2 catálogo)

Orden sugerido según dependencias de negocio:

- [ ] **[PROVEEDORES]** Alta de proveedores, datos fiscales/bancarios, tabla `catalog_product_suppliers` (costo base)
- [ ] **[CLIENTES / CRM]** Compradores, contactos técnicos, perfiles de facturación
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
- [ ] Revisar controladores legado que aún usen `$db->transStart()` directo (mover a modelos, ver §9 documentación)
