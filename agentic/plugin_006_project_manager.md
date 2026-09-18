# Plugin 006 — Administrador de Proyectos, Tareas y Calendarización

> **Slug:** `project-manager` | **Prefijo DB:** `plg_proj_` | **Versión:** 1.0.0
> **Referencia:** `ARQUITECTURA_PLUGIN_SYSTEM.md`
> **Ruta base:** `/nat/plugins/projects/`

---

## 1. Alcance y Objetivo

Sistema de gestión de proyectos y tareas integrado al ERP:
- Proyectos con hitos (milestones), fases y tareas anidadas
- Tablero Kanban + Vista de Lista + Vista de Calendario (FullCalendar)
- Asignación de tareas a usuarios del ERP (Shield CI4)
- Seguimiento de tiempo (time tracking) por tarea
- Vinculación a clientes CRM, cotizaciones aprobadas, trabajadores HR
- Comentarios en tareas con mención de usuarios (`@usuario`)
- Reportes de avance: % completado, horas estimadas vs reales, carga por persona
- Alertas de tareas vencidas y hitos próximos

---

## 2. Base de Datos

### `plg_proj_projects` — Proyectos

```sql
id                INT AI PK
name              VARCHAR(255)
code              VARCHAR(30) UNIQUE   -- "PROJ-2026-001"
description       TEXT NULL
status            ENUM('planning','active','paused','completed','cancelled') DEFAULT 'planning'
priority          ENUM('low','medium','high','critical') DEFAULT 'medium'
branch_id         INT NULL FK org_branches.id
client_id         INT NULL             -- FK futura a crm_clients.id
quotation_id      INT NULL FK plg_quot_quotations.id  -- si viene de cotización
manager_id        INT FK users.id
start_date        DATE NULL
due_date          DATE NULL
completed_at      DATETIME NULL
color             CHAR(7) DEFAULT '#0d6efd'
budget            DECIMAL(14,2) NULL
notes             TEXT NULL
created_by        INT FK users.id
created_at, updated_at, deleted_at
```

### `plg_proj_milestones` — Hitos del proyecto

```sql
id                INT AI PK
project_id        INT FK plg_proj_projects.id (CASCADE)
name              VARCHAR(255)
due_date          DATE NULL
status            ENUM('pending','completed') DEFAULT 'pending'
completed_at      DATETIME NULL
sort_order        TINYINT DEFAULT 0
```

### `plg_proj_tasks` — Tareas (soporta anidamiento un nivel)

```sql
id                INT AI PK
project_id        INT FK plg_proj_projects.id (CASCADE)
milestone_id      INT NULL FK plg_proj_milestones.id
parent_id         INT NULL FK self.id  -- subtarea
name              VARCHAR(500)
description       TEXT NULL
status            ENUM('todo','in_progress','review','done','cancelled') DEFAULT 'todo'
priority          ENUM('low','medium','high','critical') DEFAULT 'medium'
assigned_to       INT NULL FK users.id
estimated_hours   DECIMAL(6,2) NULL
actual_hours      DECIMAL(6,2) DEFAULT 0  -- suma de time_logs
start_date        DATE NULL
due_date          DATE NULL
completed_at      DATETIME NULL
sort_order        INT DEFAULT 0
tags              VARCHAR(500) NULL
created_by        INT FK users.id
created_at, updated_at, deleted_at
```

### `plg_proj_task_members` — Múltiples asignados por tarea

```sql
id          INT AI PK
task_id     INT FK plg_proj_tasks.id (CASCADE)
user_id     INT FK users.id
role        ENUM('assignee','reviewer','observer') DEFAULT 'assignee'
```

### `plg_proj_time_logs` — Registro de tiempo por tarea

```sql
id                INT AI PK
task_id           INT FK plg_proj_tasks.id (CASCADE)
user_id           INT FK users.id
description       VARCHAR(500) NULL
hours             DECIMAL(5,2)
logged_date       DATE
billable          TINYINT(1) DEFAULT 1
created_at, updated_at
```

### `plg_proj_comments` — Comentarios en tareas

```sql
id                INT AI PK
task_id           INT FK plg_proj_tasks.id (CASCADE)
user_id           INT FK users.id
content           TEXT
mentions          JSON NULL            -- [{"user_id":3,"username":"jdoe"}]
edited_at         DATETIME NULL
created_at, updated_at, deleted_at
```

### `plg_proj_attachments` — Archivos adjuntos

```sql
id                INT AI PK
task_id           INT FK plg_proj_tasks.id (CASCADE)
user_id           INT FK users.id
original_name     VARCHAR(255)
stored_path       VARCHAR(500)         -- WRITEPATH/uploads/plugins/project-manager/
file_size         INT
mime_type         VARCHAR(100)
created_at
```

---

## 3. Modelos Principales

### `ProjectModel`
- `DataTableTrait`
- `$useSoftDeletes = true`
- `generateCode(int $year)` → `PROJ-{YEAR}-{XXXX}`
- `getDashboardStats(int $userId)` → proyectos activos, tareas vencidas, horas del mes
- `getWithStats(int $id)` → proyecto + count de tareas por status + % avance
- **Transacción §9:** `createWithMilestones(array $data, array $milestones): int|false`

### `TaskModel`
- `DataTableTrait`
- `$useSoftDeletes = true`
- `getKanbanData(int $projectId)` → tareas agrupadas por status
- `getCalendarData(int $userId, string $month)` → tareas con due_date en el mes dado
- `getByAssignee(int $userId, array $filters)` → mis tareas con filtros
- `updateActualHours(int $taskId)` → suma los time_logs y actualiza `actual_hours`

---

## 4. Controladores y Rutas

### `ProjectController`

| Método | Alias | Permiso |
|--------|-------|---------|
| `index()` | `projects.index` | `projects.access` |
| `list_ajax()` | `projects.list_ajax` | `projects.access` |
| `show(id)` | `projects.show` | `projects.access` |
| `store()` | `projects.store` | `projects.manage` |
| `update(id)` | `projects.update` | `projects.manage` |
| `delete(id)` | `projects.delete` | `projects.delete` |
| `kanban(id)` | `projects.kanban` | `projects.access` |
| `calendar()` | `projects.calendar` | `projects.access` |
| `calendar_ajax()` | `projects.calendar_ajax` | `projects.access` |
| `report(id)` | `projects.report` | `projects.access` |

### `TaskController`

| Método | Alias | Permiso |
|--------|-------|---------|
| `store()` | `tasks.store` | `projects.manage` |
| `update(id)` | `tasks.update` | `tasks.update` |
| `delete(id)` | `tasks.delete` | `projects.manage` |
| `update_status(id)` | `tasks.status` | `tasks.update` |
| `get_ajax(id)` | `tasks.get` | `projects.access` |
| `log_time()` | `tasks.log_time` | `projects.access` |
| `add_comment()` | `tasks.comment` | `projects.access` |
| `upload_attachment()` | `tasks.attach` | `projects.access` |

---

## 5. Vistas

### `projects_index.php`
- **4 cards:** Proyectos activos, Tareas vencidas hoy, Horas esta semana, Proyectos completados mes
- DataTable de proyectos con columnas: Código, Nombre, Cliente, Responsable, Avance (progress bar), Fecha límite, Estado
- Botón "Nuevo Proyecto" con modal
- Filtros: estado, responsable, cliente, rango de fechas

### `project_show.php` — Vista de proyecto con tabs
1. **Resumen:** datos generales + progress bar + stats de tareas
2. **Kanban:** tablero de tareas arrastrable (Drag & Drop con SortableJS) con columnas: Todo, En Progreso, Revisión, Completado
3. **Lista:** DataTable de todas las tareas con filtros y búsqueda
4. **Calendario:** FullCalendar mensual con tareas como eventos
5. **Hitos:** timeline de milestones
6. **Tiempo:** tabla de time_logs del proyecto + gráfica de horas por usuario
7. **Archivos:** listado de adjuntos de todas las tareas del proyecto

### `task_modal.php` — Modal de tarea (create/edit)
- Campos: nombre, descripción (textarea rico — no CKEditor, sino simple), milestone, asignado (Select2 múltiple), prioridad, estimado, fechas, tags
- Comentarios inline si es edición
- Botón de subir archivo
- Timer de tiempo ("Iniciar / Detener") para registro rápido

### `calendar_view.php` — Calendario global
- FullCalendar v6 con vista mensual/semanal/diaria
- Mis tareas y las del equipo (filtro por usuario)
- Hitos como marcadores en el calendario
- Drag & drop de fechas de tareas

### `project_report.php` — Reporte de avance
- % completado general
- Tabla: tarea, asignado, horas estimadas, reales, estado
- Gráfica de barras: horas por usuario (Chart.js o simple CSS)
- Exportar a PDF (vía `PdfLibrary`)

---

## 6. Integración con ERP Base

### Con Cotizaciones (Plugin 002)
Cuando una cotización se aprueba y convierte:
- Hook `quotation.converted` → opción para crear proyecto automáticamente
- El proyecto hereda: nombre del cliente, monto (como presupuesto), datos de contacto

### Con CRM
- `project.client_id` → vinculado al cliente CRM
- En la ficha del cliente CRM: tab "Proyectos" lista los proyectos asociados

### Con HR (trabajadores)
- `task.assigned_to` → cualquier usuario Shield (incluye trabajadores con acceso)
- Futura integración: `hr_profiles` para mostrar disponibilidad de trabajadores en asignaciones

### Con Sidebar
El plugin añade:
- "Proyectos" con sub-ítems: Mis tareas, Todos los proyectos, Calendario
- Badge con count de tareas vencidas si `plugin_can('projects.access')`

---

## 7. Kanban con Drag & Drop

```javascript
// En project_show.php — Kanban con SortableJS
// SortableJS desde CDN (liviano, sin jQuery)
import Sortable from 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/modular/sortable.esm.js';

document.querySelectorAll('.kanban-column').forEach(column => {
    new Sortable(column, {
        group: 'tasks',
        animation: 150,
        onEnd: function(evt) {
            const taskId  = evt.item.dataset.taskId;
            const newStatus = evt.to.dataset.status;
            // POST via fetch con CSRF header (§2)
            fetch(route_to('tasks.status'), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': document.getElementById('csrf_token').value,
                },
                body: JSON.stringify({ task_id: taskId, status: newStatus }),
            }).then(r => r.json()).then(res => {
                if (!res.success) notifyShow(res.message, 'danger');
            });
        }
    });
});
```

---

## 8. Permisos

```json
"permissions": {
  "projects.access": "Ver proyectos y tareas",
  "projects.manage": "Crear y editar proyectos",
  "projects.delete": "Eliminar proyectos",
  "tasks.update":    "Actualizar estatus y datos de tareas asignadas"
}
```

---

## 9. Alertas Automáticas

| Detector | Condición | Tipo |
|----------|-----------|------|
| `_detectOverdueTasks` | Tareas con `due_date < hoy` en status no completado | `danger` |
| `_detectUpcomingMilestones` | Hitos en los próximos 3 días | `warning` |
| `_detectUnassignedTasks` | Tareas sin asignado hace más de 24h | `info` |
| `_detectProjectsAtRisk` | Proyectos con < 30% avance y < 7 días para vencer | `warning` |

---

## 10. Cumplimiento §1–§21

| § | Aplicación en este plugin |
|---|--------------------------|
| §1 | `assertAjaxPost()` en store, update, delete (project y task), update_status, log_time, add_comment, upload_attachment, calendar_ajax |
| §2 | CSRF hidden en todas las vistas. `xhr.dt` en DataTable. Fetch con header CSRF en Drag&Drop Kanban + `jsonWithCsrf()` |
| §3 | Alias `projects.*` y `tasks.*`. Solo `route_to()` en vistas |
| §4 | `url_is('nat/plugins/projects*')` en sidebar |
| §5/§16 | `DataTableTrait` en `ProjectModel` y `TaskModel`. `$column_order` y `$column_search` definidos |
| §6 | `isDataTable` guard en `projects_index.php` y vista lista de tareas |
| §7 | `permission:projects.*` / `permission:tasks.*` en todas las rutas. `plugin_can()` en botones de vistas |
| §8 | `logActivity` en project_create, project_update, project_delete, task_create, task_update, task_delete, task_status_changed, time_logged |
| §9 | `ProjectModel::createWithMilestones()` con `transStart/Complete`. Nunca transacciones en controladores |
| §10 | Select2 con `dropdownParent` en modales de asignación de usuarios |
| §11 | `<input type="date">` para start_date, due_date. `d/m/Y` en DataTable. ISO en DB |
| §12 | FontAwesome: `fa-project-diagram`, `fa-tasks`, `fa-calendar-alt`, `fa-clock`, `fa-flag`. Sin Lucide en DataTables |
| §13 | Breadcrumbs en todas las vistas de página completa |
| §14 | Vistas extienden `$layout` con secciones `title`, `main`, `scripts` |
| §15 | `renderLayout()` para GET. `setOutputSuccess()`/`setOutputError()` + `jsonWithCsrf()` para POST |
| §17 | `notifyShow()` para feedback. Drag&Drop usa feedback visual (clase CSS), no `alert()` |
| §18 | Adjuntos en `WRITEPATH/uploads/plugins/project-manager/{task_id}/`. Ruta relativa en DB |
| §19 | `useSoftDeletes = true` en `ProjectModel` y `TaskModel`. `$protectFields = true`. `$allowedFields` explícito |
| §20 | Estados de proyecto/tarea y prioridades como constantes en los modelos |
| §21 | Detectores usan array con estructura estándar del ERP. `_safeDetect()` en `_detectAll()` |

---

## Prompt para el agente implementador

```
Actúa como un desarrollador experto en CodeIgniter 4. Lee y ejecuta exactamente el plan:
@agentic/plugin_006_project_manager.md

Lee también:
- @DOCUMENTACION_TECNICA.md (§1–§21 obligatorios)
- @agentic/ARQUITECTURA_PLUGIN_SYSTEM.md
- @agentic/plugin_000_plugin_system_core.md (prerequisito ya instalado)

Prefix de tablas: plg_proj_
Namespace: Plugins\ProjectManager\

Importante:
- El Kanban usa SortableJS (CDN liviano). El Drag & Drop de columnas envía un POST a tasks.status
  con CSRF en el header HTTP (§2). Si el backend falla, revertir el elemento al estado anterior visualmente.
- FullCalendar v6 se carga desde CDN. Los eventos son GET vía calendar_ajax() (no POST).
- El reporte de avance puede exportarse a PDF usando PdfLibrary del ERP base.
- SortableJS y FullCalendar NO son FontAwesome — está permitido cargarlos desde CDN
  porque son librerías de comportamiento, no de íconos.

Al terminar, añade tu reporte al final de @agentic/AUDITORIA_ARQUITECTURA.md.
```
