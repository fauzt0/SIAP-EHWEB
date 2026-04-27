# Panel de Tareas (TODO)

Este archivo se utiliza exclusivamente para el seguimiento de tareas pendientes, correcciones y próximos hitos del proyecto. Toda la referencia técnica ha sido trasladada a [DOCUMENTACION_TECNICA.md]
---

## ❌ Tareas por hacer


## Módulo de Recursos Humanos
- [x] Preparación de las tablas de la base de datos. Migraciones creadas y ejecutadas para el módulo de RRHH.
- [x] Preparación para módulo de Recursos humanos. Controladores y modelos creados con la arquitectura estándar.
- [x] Vista principal del módulo de Recursos humanos. Listado de trabajadores con DataTables (server-side), filtros avanzados por estatus, departamento y sucursal.
- [ ] Vista de perfil del trabajador (offcanvas). Botón de ver perfil que abre un offcanvas con datos completos del trabajador, botones de acción (Editar, Nuevo Contrato, Calcular Finiquito), cards de vacaciones, incidencias, horario laboral e historial de contratos.
- [x] Alta de empleados. Formulario de 5 pestañas (Personales, Laborales, Nómina/Prestaciones, Bancarios, Emergencias) con selector de usuario del sistema, regímenes SAT, jefe directo con Select2 y número de empleado autogenerado.
- [x] Edición de empleado. El formulario de alta también funciona para edición con datos precargados.
- [x] Eliminar trabajador (soft delete) con botón en listado.
- [ ] Diseño del offcanvas de perfil: botones "Nuevo Contrato", "Calcular Finiquito", cards informativos de vacaciones, incidencias, horario laboral y árbol cronológico de contratos.
- [x] **[HR/CATÁLOGOS] Gestión de Departamentos y Puestos:** Implementar interfaz sencilla para el CRUD de catálogos de RRHH.


## Módulo de Contratos (interno en recursos humanos)
Una vez implementado el módulo de recursos humanos, se agregará la funcionalidad de contratos, el cual consiste en que cada trabajador tendrá un contrato que se descargará en pdf. Este se genera de manera automática al dar de alta al trabajador o cuando se realice alguna modificación en sus datos, por lo que se deberá mantener un historico de cambios de contrato, la opción para descargarlo y un pequeño buscador de contratos. Se adjuntan ejemplos visuales del sistema previamente relacionado (Chisa ERP), como referencia visual.

- [x] *Importante*: PDF. Implementación de un generador de pdf escalable, moderno y flexible mediante la libreria `PdfLibrary` (basada en Mpdf).
- [x] Contratos Vista general. Botón en el listado de trabajadores con acceso a la gestión de plantillas y listado DataTables (server-side).
- [x] Contratos Plantilla alta y editar. Formulario con editor Quill, variables dinámicas {{nombre_variable}}, carga de logotipo de cabecera y bloque de firmas.
- [x] Modelos base. Selector de modelos base (Legal LFT México, etc.) que cargan texto predefinido en el editor.
- [x] Contratos. Generación automática de contrato al alta/edición de trabajador con histórico de versiones inmutables (content_snapshot).
- [x] En el offcanvas del trabajador, historial cronológico de contratos con opción de descarga en PDF profesional.
- [x] Estandarización de PDF con diseño corporativo, logotipo y estilos premium.













## 🚀 Hitos Próximos
- [ ] Módulo de proveedores.
- [ ] Módulo de productos y servicios.
- [ ] Módulo de contabilidad. 
- [ ] Implementar módulo de Reportes base
- [ ] Configurar respaldos automáticos de base de datos
- [ ] Optimización de assets para producción

---

## 🛠️ Correcciones y Mejoras Pendientes

- [ ] Revisar tiempos de respuesta en DataTables con >10k registros
- [ ] Mejorar validaciones de seguridad en carga de archivos masivos
- [ ] **[TÉCNICO/CSRF] Corregir dependencia frágil del #csrf_token en `main_users.php`**
  - **Problema:** El input `<input id="csrf_token">` que DataTables usa para las peticiones POST proviene del modal `user_form_modal.php`. Si un usuario no tiene el permiso `users.create`, el modal no se renderiza y el token no existe en el DOM → falla silenciosa con 403 en filtros y paginación.
  - **Solución:** Agregar el input global directamente al inicio de la sección `main` en `app/Views/Users/main_users.php`, siguiendo el estándar de la Sección 2 de `DOCUMENTACION_TECNICA.md`:
    ```html
    <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">
    ```
  - **Nota:** Una vez agregado, el input del modal puede mantenerse para compatibilidad o eliminarse para evitar duplicados de ID (ambos apuntan al mismo elemento en el DOM).
- [x] **[TÉCNICO/MVC] Refactorizar transacciones de BD en `WorkerController`**
  - **Problema:** Los métodos `create()` y `update()` instancian directamente `\Config\Database::connect()` y manejan lógica de transacciones multi-tabla (`$db->transStart()`). Esto viola la separación de responsabilidades del patrón MVC.
  - **Solución:** Mover la lógica de transacción a métodos especializados dentro de `HrProfileModel` (ej. `createWorkerWithEmployment()`), manteniendo el controlador agnóstico de la capa de infraestructura de datos. (Ver Sección 9 de `DOCUMENTACION_TECNICA.md`).
- [ ] **[TÉCNICO/MVC] Validar y refactorizar transacciones de BD en `UserController`**
  - **Problema:** Revisar si el controlador de usuarios tiene transacciones o conexiones a base de datos manuales (similar a lo que ocurría en WorkerController).
  - **Solución:** De ser así, trasladar la lógica pertinente al `UserModel` y alinear el código con la Sección 9 de la Documentación Técnica.
- [ ] **[HR/CATÁLOGOS] Integración de sucursales (Branches)**
  - **Problema:** El selector de sucursales en el alta de empleados debe alimentarse de `org_branches`, pero actualmente no existe un módulo para gestionar estas sucursales (modulo de configuración de empresa pendiente).
  - **Solución:** Implementar el listado de sucursales dentro de un futuro módulo de "Configuración de Empresa" y asegurar que el alta de trabajadores consuma estos datos dinámicamente.
- [ ] **[UI/ESTÁNDAR] Estandarizar diseño de tablas (table-striped)**
  - **Tarea:** Asegurar que todas las tablas del sistema (incluyendo Administración de Usuarios y futuros módulos) utilicen la clase `table-striped` para mejorar la legibilidad.



---

## ✅ Tareas Completadas Recientemente

- [x] Arquitectura de Catálogos, Sales & Billing (Omnicanalidad, Parcialidades, SoftDeletes)
- [x] Estructuración de base de datos para Contabilidad y Proveedores.
- [x] Estructuración de proyectos
- [x] Sistema de gestión de usuarios y sesiones con roles (Shield)
- [x] Corrección de iconos de contacto en Offcanvas
- [x] Implementación de edición de avatar para administradores
- [x] Reorganización de documentación técnica
- [x] Revisión y validación de migraciones del módulo Sales (tablas: sales_orders, sales_order_items, billing_payments, customer_services, service_renewal_logs, customer_wallet_transactions)
- [x] Diseño profesional de tablas Sales: soporte omnicanal, parcialidades, renovaciones automáticas/manuales, pagos/tickets y facturación CFDI
