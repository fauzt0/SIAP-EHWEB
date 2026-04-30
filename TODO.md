# Panel de Tareas (TODO)

Este archivo se utiliza exclusivamente para el seguimiento de tareas pendientes, correcciones y próximos hitos del proyecto. Toda la referencia técnica ha sido trasladada a [DOCUMENTACION_TECNICA.md]
---

## ❌ Tareas por hacer


## Módulo de Recursos Humanos (Fase 2)
![Imagen de referencia](image.png) Imagen de referencia. Se debe mantener el diseñop actual de las cards que ya existe en el offcanvas, pero debe contener la informacion de referencia.

- [ ] **Vista de perfil del trabajador (offcanvas):** Finalizar el diseño de cards informativos para vacaciones, incidencias y horario laboral asi como sus funcionalidades:
- [ ] **Horarios Laborales:**
    - [ ] Implementar gestión de turnos y asignación de horarios por trabajador.![alt text](image-3.png). 
    ![alt text](image-4.png.).Crea el archivo .md con la descripción de las tablas de turnos y crea las migraciones correspondientes para crear las tablas en la base de datos.     
    - [ ] Configuración de días de descanso y jornadas especiales.
- [ ] **Gestión de Vacaciones e Incidencias:**
    - [ ] Módulo de control de asistencia y registro de incidencias (faltas, retardos, permisos).
    ![listado_incidencias_usuario](image-5.png), ![alta_nueva_incidencia](image-6.png).
    - [ ] Acceso directo al control de de asistencias e incidencias desde el dashboar general de recursos humanos "nat/hr/workers". El acceso será un botón con un número que indique el número de incidencias no atendidas o revisadas. El botón desplegará un modal con el listado de indicendias y los metodos de aprobación, rechazo, etc (dejo a tu consideracion las opciones de incidencias. Los que "pendiente" es que no se han revisado, los que estan palomeados es que ya se revisaron y aprobado su justificante, los que estan tachados es que ya se aprobaron y se desconto el dia, los que tienen una x es que se rechazaron..) El listado tendrá un pequeño filtro, de preferencia debe ser server side rendering (utiliza los metodos y arquitectura que ya hemos empleado en el sistema).
    - [ ] Sistema de cálculo y control de vacaciones según antigüedad (Ley Federal del Trabajo). En caso no tener la antiguedad suficiente, no se puede crear la solicitud y se envia la alerta antes de abrir el modal.![alt text](image-10.png) ![alt text](image-11.png)
    - [ ] Card informativo en Offcanvas de vacaciones del trabajador: Días totales vs Días disfrutados. Botón de acceso directo para solicitud de vacaciones. ![alt text](image-7.png).
    - [ ] Modal de solicitud de vacaciones con listados de historial. ![alt text](image-8.png) ![alt text](image-9.png)
    - [ ] Modal general con alertas de solicitudes de vacaciones, aprovaciones, rechazados, etc. Contralores y metodos del backend y del modelo para aprobar, rechazar, filtrar y notificar a los usuarios. * Esto en el listado general de trabajadores.
    ![alt text](image-1.png) 
- [ ] **Gestión de Finiquitos y Liquidaciones:**
    - [x] Calculadora automática de finiquito (aguinaldo, vacaciones y prima vacacional proporcional). ![alt text](image-13.png)
    - [x] Calculadora de liquidación (indemnización 3 meses, 20 días por año, prima de antigüedad).
    - [ ] Generación de formatos PDF de baja: Carta de renuncia y Recibo de finiquito/liquidación. Este a diferencia del contrato, no se podra editar, solo genera el documento sin almacenarlo en base de datos o ruta interna del sistema. 
    
- [x] Cards informativos en la parte superior del listado de trabajadores "/nat/hr/workers", con datos "total de empleados", "nuevos (30d)", "nómina mensual", "incidencias (pendientes, vacaciones, etc)".![alt text](image-12.png). Implementar algun sistema de consulta rapida o cache, etc, para prevenir consuiltas constantes a la base de datos para obtener estos datos. (cache de codeigniter o algo similar).
    

## 🛠️ Mejoras y Refactorización
- [x] **[ARQUITECTURA] Refactorización de Servicios:** Migrar la lógica de `app/Services/ContractService.php` directamente al `WorkerController` (o controlador especializado) para seguir los estándares nativos de CI4 y eliminar la carpeta personalizada `/Services`.
- [ ] **[CONTRATOS] Optimización del Sistema de Plantillas:**
    - [ ] Mejorar el editor de plantillas para soportar bloques condicionales.
    - [ ] Implementar previsualización en tiempo real con datos de prueba.
    - [ ] Versionado de plantillas base.
    - [ ] Sección de Mi perfil con acceso directo a solicitud de vacaciones, alta de incidencias, acceso a historial de contratos, datos del usuario y formulario para actualizar ciertos datos (email, teléfono, datos del perfil). Los datos sensibles o laborales no se podrán actualizar, solo estarán disponibles para consulta.


- [ ] **[UI/ESTÁNDAR] Estandarizar diseño de tablas (table-striped):** Asegurar que todas las tablas del sistema utilicen la clase `table-striped`.
- [ ] **[TÉCNICO/CSRF] Corregir dependencia frágil del #csrf_token en `main_users.php`** (Ver detalles en TODO anterior).

---

## 🚀 Hitos Próximos
- [ ] Módulo de notificaciones
- [ ] Módulo de proveedores.
- [ ] Módulo de productos y servicios.
- [ ] Módulo de contabilidad. 
- [ ] Implementar módulo de Reportes base.

## Módulo de notificaciones.
- [ ] Implementar base de datos para notificaciones (tabla `sys_notifications`).
- [ ] Implementar métodos del backend para el envío de notificaciones (Servicio de Notificaciones).
- [ ] Implementar componentes del frontend para visualizar alertas en tiempo real.
- [ ] **Integración de Vacaciones:** Extender el sistema para notificar solicitudes y respuestas (aprobado/rechazado) a los trabajadores involucrados.
- [ ] **Notificaciones vía Email:** Configurar el envío automático de correos electrónicos para avisar a RRHH de nuevas solicitudes y a los empleados de sus resoluciones.
- [ ] **Alertas de Caducidad:** Generar una alerta en el sistema 2 meses antes de que un periodo vacacional expire, notificando al administrador que es un derecho del trabajador y debe ser programado.
- [ ] **Acceso Usuario:** Habilitar en el perfil de usuario (vista trabajador) el acceso a sus propias solicitudes de vacaciones e historial.
- [ ] **Integración Contable:** Vincular el módulo de sueldos y finiquitos con las tablas de contabilidad existentes para automatizar el registro de egresos por nómina.
- [ ] Alertas preventivas para usuarios con datos críticos faltantes (NSS, Contratos vencidos, etc.).


---

## ✅ Tareas Completadas Recientemente

### Recursos Humanos & Contratos (Fase 1)
- [x] **Gestión Documental AJAX:** Subida inmediata de archivos, reemplazo con limpieza física y persistencia de pestañas sin recarga.
- [x] **Historial de Contratos:** Generación automática de versiones inmutables (`content_snapshot`) en alta y edición.
- [x] **PDF Corporativo:** Implementación de `PdfLibrary` con diseño premium, logotipos y firmas.
- [x] **Expediente Digital:** CRUD completo de documentos con filtros de búsqueda y soporte de Soft Delete.
- [x] **Catálogos RRHH:** Gestión de Departamentos, Puestos, Tipos de Contrato y Plantillas.
- [x] **Alta/Edición de Empleados:** Formulario multi-pestaña con validaciones LFT y autogeneración de número de empleado.
- [x] **Arquitectura MVC:** Refactorización de transacciones a los modelos (`HrProfileModel`).

### Otros
- [x] Sistema de gestión de usuarios y sesiones (Shield).
- [x] Refactorización de avatares y auditoría de actividades.
- [x] Estructuración de base de datos para Ventas, Facturación y Clientes.
