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


## Módulo de Contratos (interno en recursos humanos)
Una vez implementado el módulo de recursos humanos, se agregará la funcionalidad de contratos, el cual consiste en que cada trabajador tendrá un contrato que se descargará en pdf. Este se genera de manera automática al dar de alta al trabajador o cuando se realice alguna modificación en sus datos, por lo que se deberá mantener un historico de cambios de contrato, la opción para descargarlo y un pequeño buscador de contratos. Se adjuntan ejemplos visuales del sistema previamente relacionado (Chisa ERP), como referencia visual.

- [ ] *Importante*: PDF. Debido a que este módulo y varios módulos posteriores van a requerir generar archivos en pdf, se buscará la implementación de un generador de pdf escalable, moderno y flexible, por lo que se deeberá crear una libreria para la generación de documentos en pdf.
- [ ] Contratos Vista general. Se tendrá un botón en la parte superior del listado de trabajadores (modulo padre creado previamente) con acceso a una nueva ruta ![alt text](image-12.png), donde se tendrá un formulario para el alta de nuevo contrato (plantillas). En esta nueva ruta aparecerá un buscador de datatables con los datos de la plantillas de contratos previamente creados ![alt text](image-13.png), descripción y fecha de última edición asi como el botón para editar o eliminar dicha plantilla (softdelete). 
- [ ] Contratos Plantilla alta y editar. Para crear una nueva plantilla se tendrá un botón en la parte superior derecha del listado general de contratos (vista general de contratos) que abrirá una nueva ruta con un formulario, editor de texto enriquecido y opción para insertar variables en formato {{nombre_variable}} asi como campos de datos en general como nombre de la plantilla, logo de encabezado, domicilio fiscal y descripción y la opción de editar bloque de firmas para las firmas del contrato en el editor de texto enriquecido. ![alt text](image-29.png)
- [] Modelos base. El formulario para el contrato tendrá en la parte superior un selector con modelos base (modelos con texto ya cargados no editables) que permiten tener una plantilla con bases legales de contratos en mexico ![alt text](image-14.png) y que al ser agregados, se cargan en el editor de texto enriquecido. Los modelos son: Legal LFT Mexico ![alt text](image-15.png), clasico ![alt text](image-16.png), moderno ![alt text](image-17.png), corporativo ![alt text](image-17.png). Se podrá almacenar la plantilla de contrato como una plantilla base para futuros contratos en el area de trabajadores. Al guardar esta plantilla se mostrará el listado de plantillas creadas. La opción para editar las plantillas previamente creadas abrira una nueva ruta con los mismos campos que el alta de plantilla pero con los datos de la plantilla ya precargados.
- [ ] Contratos. Cada que se realiza un alta de trabajadores o edición de datos del trabajador, se deberá generar un nuevo contrato de manera automática utilizando el modelo base default (legal LFT mexico) con los datos actualizados asi como un historico de cambios que permacerá visible tanto en el offcanvas del trabajador ![alt text](image-20.png) como en el botón para dar de alta nuevos contratos (listado de contratos realizados anteriormente) ![alt text](image-22.png) donde aparecerá el formulario para crear un nuevo contrato (basado en las plantillas que se crearon en el punto anterior) ![alt text](image-21.png) mediante selectores y que se asignará al trabajador. En el listado de contratos realizados anteriormente se mostrará también el historico de contratos del trabajador, el cual consiste en una tabla sencilla de datatables sin serverside rendering, con datos generales del contrato y botón de ver por modal con el contrato (una vista previa con la estructura de secciones, titulos y espacio de firmas, con los datos del trabajador, etc)  y la opción para descargarlo en pdf ![alt text](image-23.png). Al crear o guardar el nuevo contrato seleccionando el tipo de contrato (tiempo determinado, prueba 3 meses, capacitacion inicial , etc ) ![alt text](image-24.png) y plantilla ![alt text](image-25.png) asi como el motivo. Se agregará este contrato al historico del usuario. En caso de que los datos del usuario se actualicen, se agregará el nuevo contrato usando la plantilla default o la ultima plantilla utilizada para este usuario (la de default en caso de no haber creado ninguna antes).
- [] En el offcanvas del trabajador, en el historico de contratos, se deberá mostrar de manera cronologica los contratos con la opción para ver (modal) ![alt text](image-26.png) ![alt text](image-27.png) y descargar pdf.
- [] En todas las secciones que permita la descarga de contratos PDF, deberán ser estandarizadas, es decir, debe ser el mismo pdf generado y a mostrar, siendo el mismo caso en el modal que muestre una vista previa. ![alt text](image-28.png)













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
