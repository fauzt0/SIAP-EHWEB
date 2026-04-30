# Plan de Implementación: Recursos Humanos - Fase 2

Este plan detalla la ejecución de las funcionalidades avanzadas de RRHH: Gestión de Horarios, Vacaciones, Incidencias, Finiquitos y Notificaciones.

## 1. Arquitectura de Base de Datos (Nuevas Tablas)

### Horarios y Turnos
- `hr_cat_shifts`: Definición de turnos (Nombre, Hora Entrada, Hora Salida, Tolerancia, Días laborables).
- `hr_worker_schedules`: Asignación de turnos a trabajadores (Vínculo profile_id -> shift_id).

### Vacaciones e Incidencias
- `hr_incidences`: Registro de incidencias (Faltas, retardos, incapacidades, permisos).
- `hr_vacation_requests`: Solicitudes de vacaciones (Fecha inicio, Fecha fin, Estatus: pendiente/aprobado/rechazado).
- `hr_vacation_balances`: Control de días disponibles por antigüedad (LFT).

### Notificaciones
- `sys_notifications`: Tabla general de notificaciones del sistema (user_id, título, mensaje, leída: boolean, url_destino).

## 2. Desarrollo Backend (Controladores y Modelos)

### WorkerController (Nuevos Métodos)
- `getIncidencesAjax()`: Listado SSR para el modal de incidencias.
- `approveIncidence()` / `rejectIncidence()`: Gestión de estatus.
- `getVacationHistoryAjax()`: Historial para el modal de vacaciones.
- `requestVacationAjax()`: Validación de antigüedad y creación de solicitud.

### ContractService -> SettlementService [NUEVO]
- Lógica para cálculo de finiquitos y liquidaciones.
- Generación de PDF efímero (sin guardado en disco).

## 3. Interfaz de Usuario (UI/UX)

### Dashboard General (`nat/hr/workers`)
- **Cards de Resumen:** Implementar caché de 10-15 min para contadores (Total, Nuevos, Nómina, Incidencias).
- **Botón de Incidencias:** Badge dinámico con el conteo de registros "pendientes".

### Offcanvas de Perfil
- Implementar las cards informativas visuales (Estilo Glassmorphism/Moderno).
- Integración de botones de acción rápida para solicitar vacaciones o reportar incidencia.

### Sección "Mi Perfil"
- Nueva vista para que el usuario logueado gestione sus propios datos permitidos.

## 4. Cronograma de Ejecución

1. **Sprint 1:** Base de Datos y Migraciones (Horarios e Incidencias).
2. **Sprint 2:** Gestión de Incidencias y Notificaciones.
3. **Sprint 3:** Sistema de Vacaciones (LFT) y Dashboard Stats (Caché).
4. **Sprint 4:** Finiquitos y Liquidaciones (PDFs).
5. **Sprint 5:** Sección "Mi Perfil" y Pulido UI.

## 5. Lineamientos Técnicos
- **Layouts:** Usar `Layouts/user_loggedin_layout`.
- **Outputs:** Todas las respuestas AJAX vía `$this->setOutputSuccess()` / `$this->setOutputError()`.
- **Vistas:** Pasar datos vía `$this->viewData`.
- **Seguridad:** Tokens CSRF en cada `fetch` y validación de permisos `hr.manage`.
