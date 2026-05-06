# Panel de Tareas (TODO)

Este archivo se utiliza exclusivamente para el seguimiento de tareas pendientes, correcciones y próximos hitos del proyecto. Toda la referencia técnica ha sido trasladada a [DOCUMENTACION_TECNICA.md]
---

## ❌ Tareas por hacer

- [ ] Generación de formatos PDF de baja: Carta de renuncia y Recibo de finiquito/liquidación. Este a diferencia del contrato, no se podra editar, solo genera el documento sin almacenarlo en base de datos o ruta interna del sistema. 
- [ ] **[RRHH] Gestión Avanzada de Incidencias:**
    - [ ] Implementar registro de retardos, faltas por enfermedad y permisos especiales.
    - [ ] Sistema de justificaciones (carga de archivos/recetas médicas).
    - [ ] Flujo de aprobación/rechazo por parte del administrador.
    - [ ] Reporte mensual de incidencias por trabajador para prenómina.

## 🛠️ Mejoras y Refactorización

- [ ] **[CONTRATOS] Optimización del Sistema de Plantillas:**
    - [ ] Mejorar el editor de plantillas para soportar bloques condicionales.
    - [ ] Implementar previsualización en tiempo real con datos de prueba.
    - [ ] Versionado de plantillas base.

- [ ] **[USUARIOS] Sección de Mi perfil:** Acceso directo a solicitud de vacaciones, alta de incidencias, acceso a historial de contratos, datos del usuario y formulario para actualizar ciertos datos (email, teléfono, datos del perfil). Los datos sensibles o laborales no se podrán actualizar, solo estarán disponibles para consulta.

- [ ] **[UI/ESTÁNDAR] Estandarizar diseño de tablas (table-striped):** Asegurar que todas las tablas del sistema utilicen la clase `table-striped`.

- [ ] **[TÉCNICO/CSRF] Corregir dependencia frágil del #csrf_token en `main_users.php`** (Ver detalles en TODO anterior).

---

## 🚀 Hitos Próximos
- [ ] Módulo de notificaciones
- [ ] Módulo de proveedores.
- [ ] Módulo de productos y servicios.
- [ ] Módulo de contabilidad. 
- [ ] Módulo de Provisionamiento/Servicios Activos
- [ ] Implementar módulo de Reportes base.
