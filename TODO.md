# Panel de Tareas (TODO)

Este archivo se utiliza exclusivamente para el seguimiento de tareas pendientes, correcciones y próximos hitos del proyecto. Toda la referencia técnica ha sido trasladada a [DOCUMENTACION_TECNICA.md]
---

## 🏢 En Desarrollo: Módulo de Organización (Perfil de Compañía)

- [ ] **[DOCUMENTACIÓN LEGAL]** Desarrollar lógica de subida y almacenamiento de archivos (constancia de situación fiscal, comprobante de domicilio, acta constitutiva) para cada sucursal en el formulario `branch_form.php`.

## 🛠️ En Desarrollo: Módulo de Productos y Servicios (Fase 1)

- [ ] **[CATÁLOGO] Estructura Base:**
    - [ ] Migración de `catalog_product_images`.
    - [ ] Implementación de Modelos CI4 (Products, Categories, Plans, Attributes, Stock, Movements).
    - [ ] CRUD de Categorías (Vista jerárquica y lógica de slugs).
- [ ] **[PRODUCTOS] Gestión Maestra:**
    - [ ] Listado de Productos (DataTables Server-Side).
    - [ ] Formulario dinámico de Producto (Físico vs Servicio).
    - [ ] Galería de imágenes con Glassmorphism UI.

## ❌ Pendientes de Recursos Humanos

- [ ] **[RRHH] Gestión Avanzada de Incidencias:**
    - [ ] Registro de retardos, faltas por enfermedad y permisos.
    - [ ] Carga de justificaciones y recetas médicas.
    - [ ] Reporte mensual para prenómina.
- [ ] Generación de formatos PDF de baja (Renuncia/Finiquito).

## 🚀 Próximos Hitos

- [ ] **[PROVEEDORES]** Módulo de Proveedores: Registras a las empresas que te suministran servicios, capturas sus datos fiscales/bancarios y completas la tabla puente catalog_product_suppliers. Esto define tu costo base.

- [ ] **[CLIENTES]** Módulo de Clientes / CRM: Das de alta a los compradores, asignas los contactos técnicos y defines sus perfiles de facturación.

- [ ] **[VENTAS]** Módulo de Ventas, Renovaciones y POS: Ejecutas la transacción. Al tener los Proveedores (costos) y los Clientes (compradores) previamente configurados, el sistema puede generar la sales_order y calcular la utilidad neta en tiempo real.

- [ ] **[CONTABILIDAD]** Módulo de Contabilidad: Entra en acción para administrar los saldos. Toma los ingresos generados en Ventas y los cruza con los egresos fijos registrados en Proveedores.

- [ ] **[NOTIFICACIONES]**  Módulo de Notificaciones: La capa final. Con todos los módulos operativos, configuras los disparadores (triggers) para enviar alertas por WhatsApp o correo sobre vencimientos, tickets o pagos.

- [ ] **[ACTIVACIONES]** Módulo de Activaciones / Aprovisionamiento: El motor técnico. Se encarga de la ejecución (crear cuentas en WHM, desplegar VPS, etc.) una vez que el Catálogo ha procesado la orden comercial. Mantiene la infraestructura separada de la oferta comercial.
- [ ] Implementar módulo de Reportes base.

---

## 🔔 Nota sobre Sistema de Alertas (Notificaciones)
El sistema de alertas debe integrarse con el menú de "Notifications" en el topbar y considerarse en el desarrollo de Inventario y Ventas:
- **Disparadores (Triggers):**
    - **Stock Bajo:** Cuando `catalog_product_stock.stock < catalog_product_stock.min_alert`.
    - **Vencimientos:** Alertas automáticas para renovación de planes/suscripciones.
    - **Bitácora:** Los movimientos críticos de inventario deben generar notificaciones para administradores.
- **Implementación:** Los modelos mutativos deben disparar eventos o llamadas al futuro `NotificationService` para registrar la alerta en la DB y mostrarla en tiempo real vía AJAX/WebSockets.

---

## ⚙️ Refactorizaciones Pendientes

- [ ] **[CONTRATOS]** Optimización de editor de plantillas y versionado.
- [ ] **[USUARIOS]** Sección de "Mi Perfil" para empleados.
- [ ] **[UI]** Estandarizar tablas a `table-striped`.
- [ ] **[TÉCNICO]** Corregir dependencia de token CSRF en `main_users.php`.
