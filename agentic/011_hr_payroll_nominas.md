# Task 011: Sistema de Nóminas (Payroll)

## Objetivo
Implementar un sistema de nóminas completo ligado al sueldo del trabajador y al módulo de Recursos Humanos.
El sistema debe generar nóminas (manual o automática), permitir ver montos totales, pagos pendientes,
métodos de pago y diferentes indicadores informativos. Incluye recibo de nómina con logotipo, datos del
trabajador, deducciones, bonos y prestaciones.

Referencias visuales: `/agentic/nomina/nomina0.PNG` ... `nomina5.PNG`.

---

## Base de datos — Migraciones

### Tabla 1: `hr_payroll_periods` — Periodos de nómina
```
id, name (VARCHAR 100), period_type (ENUM: weekly|biweekly|monthly), start_date, end_date,
status (ENUM: draft|approved|paid|cancelled, default draft),
branch_id (FK org_branches.id, NULL), total_gross, total_deductions, total_net,
created_by (FK users.id), created_at, updated_at, deleted_at
```

### Tabla 2: `hr_payroll_entries` — Nómina por trabajador
```
id, period_id (FK hr_payroll_periods.id), hr_profile_id (FK hr_profiles.id),
base_salary (DECIMAL 12,2), worked_days (TINYINT), overtime_hours (DECIMAL 5,2 default 0),
gross_amount (DECIMAL 12,2), imss_employee (DECIMAL 10,2 default 0),
infonavit (DECIMAL 10,2 default 0), isr (DECIMAL 10,2 default 0),
other_deductions (DECIMAL 10,2 default 0), bonuses (DECIMAL 10,2 default 0),
benefits (DECIMAL 10,2 default 0), net_amount (DECIMAL 12,2),
payment_method (ENUM: bank_transfer|cash|check|other, default bank_transfer),
payment_status (ENUM: pending|paid|partial, default pending),
payment_date (DATE, NULL), payment_reference (VARCHAR 100, NULL),
notes (TEXT, NULL), created_at, updated_at, deleted_at
```

### Tabla 3: `hr_payroll_schedules` — Calendario de nóminas (planeador)
```
id, name (VARCHAR 100), period_type (ENUM: weekly|biweekly|monthly),
next_payment_date (DATE), recurrence_day (TINYINT, NULL — día del mes o semana),
branch_id (FK org_branches.id, NULL), active (TINYINT default 1),
created_at, updated_at
```

⚠️ FK ordering: `hr_profiles` y `org_branches` deben existir antes. Usar `disableForeignKeyChecks()`.

---

## Modelos

### `app/Models/HR/HrPayrollPeriodModel.php`
- `$table = 'hr_payroll_periods'`, `$useSoftDeletes = true`
- `$allowedFields = ['name', 'period_type', 'start_date', 'end_date', 'status', 'branch_id', 'total_gross', 'total_deductions', 'total_net', 'created_by']`
- Método `getDashboardStats(): array` → total nóminas, pagos pendientes, pagadas en el mes, total pendiente del mes
- Método `getWithDetails(int $id): array` → incluye entries con datos de trabajador

### `app/Models/HR/HrPayrollEntryModel.php`
- `$table = 'hr_payroll_entries'`, `$useSoftDeletes = true`
- `$allowedFields = [todos los campos de entrada]`
- Método `getByPeriod(int $periodId): array` → todas las entradas de un periodo con datos del trabajador
- Método `calculateTotals(int $periodId): array` → suma gross, deductions, net

### `app/Models/HR/HrPayrollScheduleModel.php`
- `$table = 'hr_payroll_schedules'`
- `$allowedFields = ['name', 'period_type', 'next_payment_date', 'recurrence_day', 'branch_id', 'active']`
- Método `getUpcoming(int $days = 30): array` → calendarios próximos

---

## Controladores

### `app/Controllers/HR/PayrollController.php`
Extiende `BaseHrController`. Métodos:

**Dashboard / Listado de períodos:**
- `index(): string` — dashboard con 4 cards (total nóminas mes, pendientes, pagadas, monto pendiente) + DataTable de períodos
- `list_ajax(): ResponseInterface` — server-side DT de períodos

**Período:**
- `create(): ResponseInterface|string` — formulario de nuevo período
- `show(int $id): string` — detalle de un período con todas sus entradas (trabajadores)
- `store(): ResponseInterface` — guardar período, **transacción en modelo** (§9): crea el período + genera entries automáticas para todos los trabajadores activos de la sucursal con su salario base
- `approve(int $id): ResponseInterface` — cambiar status a `approved` (logActivity)
- `cancel(int $id): ResponseInterface` — cancelar período

**Entradas individuales:**
- `updateEntry(int $entryId): ResponseInterface` — editar bonos, deducciones, horas extra, método de pago de un trabajador individual (logActivity `update_payroll_entry`)
- `markPaid(int $entryId): ResponseInterface` — marcar entrada como pagada (logActivity `payroll_entry_paid`)
- `markPeriodPaid(int $periodId): ResponseInterface` — marcar todas las entradas del período como pagadas de golpe

**Recibo:**
- `receipt(int $entryId): string` — vista del recibo de nómina (para imprimir/PDF):
  - Logotipo de sucursal (`org_branches.logo_path`)
  - Datos del trabajador: nombre, puesto, departamento, periodo
  - Tabla: días trabajados, salario base, horas extra, bonos, prestaciones
  - Tabla deducciones: IMSS, INFONAVIT, ISR, otras
  - Total neto a recibir
  - Estatus del pago
  - Referencia de pago

**Calendario:**
- `schedule(): string` — vista modal de calendarización con `FullCalendar` o tabla de próximos pagos
- `schedule_ajax(): ResponseInterface` — datos para el calendario

Permisos requeridos:
- Lectura: `hr.access`
- Escritura (crear/aprobar/pagar): `hr.manage-payroll` (nuevo permiso)

### Transacciones (§9)
En `HrPayrollPeriodModel`:
```php
public function createPeriodWithEntries(array $periodData, array $workers): int|false
{
    $this->db->transStart();
    $periodId = $this->insert($periodData);
    foreach ($workers as $worker) {
        $this->entryModel->insert([
            'period_id'      => $periodId,
            'hr_profile_id'  => $worker['id'],
            'base_salary'    => $worker['salary'],
            'worked_days'    => $periodData['worked_days'] ?? 15,
            'gross_amount'   => $worker['salary'],
            'net_amount'     => $worker['salary'],  // Se ajusta manualmente
            'payment_status' => 'pending',
        ]);
    }
    $this->db->transComplete();
    return $this->db->transStatus() ? $periodId : false;
}
```

---

## Rutas — `app/Config/Routes.php`
Dentro del grupo `nat/hr`:
```php
$routes->group('payroll', ['filter' => 'permission:hr.access'], function ($routes) {
    $routes->get('/', 'HR\PayrollController::index', ['as' => 'hr.payroll.index']);
    $routes->post('list_ajax', 'HR\PayrollController::list_ajax', ['as' => 'hr.payroll.list_ajax']);
    $routes->get('create', 'HR\PayrollController::create', ['as' => 'hr.payroll.create', 'filter' => 'permission:hr.manage-payroll']);
    $routes->post('store', 'HR\PayrollController::store', ['as' => 'hr.payroll.store', 'filter' => 'permission:hr.manage-payroll']);
    $routes->get('show/(:num)', 'HR\PayrollController::show/$1', ['as' => 'hr.payroll.show']);
    $routes->post('approve/(:num)', 'HR\PayrollController::approve/$1', ['as' => 'hr.payroll.approve', 'filter' => 'permission:hr.manage-payroll']);
    $routes->post('cancel/(:num)', 'HR\PayrollController::cancel/$1', ['as' => 'hr.payroll.cancel', 'filter' => 'permission:hr.manage-payroll']);
    $routes->post('entry/update/(:num)', 'HR\PayrollController::updateEntry/$1', ['as' => 'hr.payroll.entry.update', 'filter' => 'permission:hr.manage-payroll']);
    $routes->post('entry/mark-paid/(:num)', 'HR\PayrollController::markPaid/$1', ['as' => 'hr.payroll.entry.paid', 'filter' => 'permission:hr.manage-payroll']);
    $routes->post('period/mark-paid/(:num)', 'HR\PayrollController::markPeriodPaid/$1', ['as' => 'hr.payroll.period.paid', 'filter' => 'permission:hr.manage-payroll']);
    $routes->get('receipt/(:num)', 'HR\PayrollController::receipt/$1', ['as' => 'hr.payroll.receipt']);
    $routes->get('schedule', 'HR\PayrollController::schedule', ['as' => 'hr.payroll.schedule']);
    $routes->post('schedule_ajax', 'HR\PayrollController::schedule_ajax', ['as' => 'hr.payroll.schedule_ajax']);
});
```

---

## Vistas

### `app/Views/HR/Payroll/payroll_index.php`
- 4 Cards informativas (§10/Bootstrap cards):
  - Total nóminas del mes (número de períodos)
  - Pagos pendientes (entradas en status `pending`)
  - Nóminas pagadas en el mes
  - Monto total pendiente del mes (suma de `net_amount` donde `payment_status = pending`)
- DataTable de períodos con columnas: Nombre, Tipo, Período, Trabajadores, Total Bruto, Total Neto, Estatus, Acciones
- Botón "Nueva Nómina" (con permiso `hr.manage-payroll`)
- Botón "Ver Calendario" → modal con planeador de nóminas

### `app/Views/HR/Payroll/payroll_show.php`
- Encabezado del período con badge de estatus
- Botones de aprobación / cancelación (según permisos)
- DataTable de entradas (trabajadores) con columnas: Trabajador, Puesto, Días, Salario Base, Bonos, Deducciones, Neto, Método Pago, Estatus, Acciones
- Modal de edición de entrada individual (editar bonos, deducciones, método de pago)
- Botón "Marcar todas como pagadas"
- Link a recibo individual

### `app/Views/HR/Payroll/payroll_receipt.php`
- Vista de impresión (sin sidebar)
- Logotipo de la empresa/sucursal en la esquina superior
- Datos de la empresa y del trabajador
- Tabla de conceptos (salario base, horas extra, bonos, prestaciones)
- Tabla de deducciones (IMSS empleado, INFONAVIT, ISR, otras)
- Total neto destacado
- Estatus del pago con fecha y referencia si está pagado
- Botón imprimir (`window.print()`)

### `app/Views/HR/Payroll/payroll_schedule_modal.php`
- Modal con tabla de `hr_payroll_schedules`: nombre, tipo, próxima fecha, sucursal
- CRUD simple de calendarios (solo admins con `hr.manage-payroll`)

---

## Alertas del sistema
En `app/Controllers/System/AlertController.php`, dentro de `_detectHrAlerts()` (o similar):
```php
$alerts = array_merge($alerts, $this->_safeDetect('_detectPendingPayrolls'));
```

Añadir método:
```php
private function _detectPendingPayrolls(): array
{
    // Detectar entradas de nómina con payment_status = 'pending' y payment_date < NOW() - 5 días
    $entries = $this->db->table('hr_payroll_entries hpe')
        ->join('hr_payroll_periods hpp', 'hpp.id = hpe.period_id')
        ->where('hpe.payment_status', 'pending')
        ->where('hpp.end_date <', date('Y-m-d', strtotime('-5 days')))
        ->whereNull('hpe.deleted_at')
        ->whereNull('hpp.deleted_at')
        ->countAllResults();

    if ($entries === 0) return [];

    return [AlertService::build(
        title: 'Nóminas pendientes de pago',
        message: "$entries pago(s) de nómina están pendientes.",
        level: 'warning',
        permission: 'hr.manage-payroll',
        module: 'hr.payroll',
        action_url: route_to('hr.payroll.index')
    )];
}
```

---

## Sidebar — `app/Views/Partials/loggedin_sidebar.php`
Dentro del bloque `if (auth()->user()->can('hr.access'))`, añadir:
```php
<li class="sidebar-item <?= url_is('nat/hr/payroll*') ? 'active' : '' ?>">
  <a class="sidebar-link" href="<?= route_to('hr.payroll.index') ?>">
    <i class="fas fa-fw fa-money-check-alt me-1"></i> Nóminas
  </a>
</li>
```

---

## Cumplimiento TOTAL con DOCUMENTACION_TECNICA.md

| § | Estándar | Aplicación en esta tarea |
|---|----------|--------------------------|
| §1 | AJAX guard | `list_ajax()`, `store()`, `approve()`, `cancel()`, `updateEntry()`, `markPaid()`, `markPeriodPaid()`, `schedule_ajax()` — todos llaman `$this->assertAjaxPost()`. `index()`, `create()`, `show()`, `receipt()`, `schedule()` son GET normales sin guard AJAX |
| §2 | CSRF | `<input hidden csrf>` al inicio del `<main>` en CADA vista (`payroll_index.php`, `payroll_show.php`, `payroll_receipt.php`). DataTable escucha `xhr.dt` para renovar token. Fetch del modal entry usa token en header HTTP + captura nuevo token de respuesta |
| §3 | Rutas con alias | Todas las rutas con `'as' => 'hr.payroll.*'`. Vistas solo usan `route_to()`, nunca `site_url()` ni `base_url()` hardcodeado |
| §4 | Sidebar activo | `url_is('nat/hr/payroll*') ? 'active' : ''` en la nueva `<li>` del sidebar dentro del bloque `hr.access` |
| §5/§16 | DataTableTrait | `HrPayrollPeriodModel` usa `use \App\Traits\DataTableTrait`. Definir `$column_order` y `$column_search`. En `payroll_show.php` la tabla de entradas también usa DataTable (puede ser client-side si < 500 filas, o server-side con su propio endpoint) |
| §6 | DataTables Guard | `if ($.fn.dataTable.isDataTable('#payroll-table')) return;` en `payroll_index.php`. Idem para tabla de entradas en `payroll_show.php` |
| §7 | Permisos Shield | `'filter' => 'permission:hr.access'` en rutas de lectura. `'filter' => 'permission:hr.manage-payroll'` en store, approve, cancel, updateEntry, markPaid. Botones de acción en vista con `<?php if (auth()->user()->can('hr.manage-payroll')): ?>` |
| §8 | Bitácora | `logActivity('create_payroll_period', 'Creó período de nómina: ' . $name . ' (ID: ' . $id . ')')`, `logActivity('approve_payroll_period', ...)`, `logActivity('cancel_payroll_period', ...)`, `logActivity('update_payroll_entry', 'Actualizó entrada ID: ' . $entryId . ' Trabajador: ' . $workerName)`, `logActivity('payroll_entry_paid', 'Marcó como pagada entrada ID: ' . $entryId)`, `logActivity('payroll_period_paid', 'Marcó todas las entradas del período ID: ' . $periodId)` |
| §9 | Transacciones en modelo | `HrPayrollPeriodModel::createPeriodWithEntries()` usa `$this->db->transStart()` / `$this->db->transComplete()`. El controlador solo llama `$this->periodModel->createPeriodWithEntries($data, $workers)`. **Nunca** `\Config\Database::connect()` en `PayrollController` |
| §10 | Select2 | En formulario de creación de período, el selector de sucursal usa Select2. Si está en modal: `dropdownParent: $('#create-period-modal')` obligatorio |
| §11 | Fechas | `start_date` y `end_date` en formulario usan `<input type="date">` (HTML5 nativo). `payment_date` en modal de pago también usa `<input type="date">`. DB almacena en `YYYY-MM-DD`. La UI muestra en formato `d/m/Y` usando `date('d/m/Y', strtotime($row))` |
| §12 | FontAwesome | `fas fa-money-check-alt` (nóminas), `fas fa-check` (aprobar), `fas fa-times` (cancelar), `fas fa-receipt` (recibo), `fas fa-calendar-alt` (calendario), `fas fa-dollar-sign` (pago). Evitar Lucide icons en columnas de DataTable |
| §13 | Breadcrumbs | `payroll_index.php`: `['Inicio' => route_to('dashboard.index'), 'RR.HH' => route_to('hr.workers'), 'Nóminas' => '']`. `payroll_show.php`: `['Inicio' => ..., 'RR.HH' => ..., 'Nóminas' => route_to('hr.payroll.index'), 'Período #X' => '']`. `receipt()`: `['Inicio' => ..., 'RR.HH' => ..., 'Nóminas' => route_to('hr.payroll.index'), 'Recibo' => '']` |
| §14 | Vistas/Layouts | Todas las vistas extienden `<?= $this->extend($layout) ?>` con secciones `title`, `main`, `scripts`. `payroll_receipt.php` puede usar un layout simplificado `Layouts/print_layout` (o crear uno nuevo sin sidebar para impresión) |
| §15 | viewData/outputData | Métodos GET: `$this->viewData['response'] = [...]` → `renderLayout(...)`. Métodos POST-AJAX: `$this->setOutputSuccess()` / `$this->setOutputError()` → `$this->jsonWithCsrf($this->outputData)` |
| §17 | notifyShow | Todo feedback tras operaciones AJAX usa `notifyShow(res.message, 'success')` o `notifyShow(res.message, 'danger')`. **Nunca** `alert()` ni `console.log()` en producción |
| §18 | Uploads | No aplica (los recibos se generan como vistas HTML, no se suben archivos) |
| §19 | Soft Delete | `$useSoftDeletes = true` en `HrPayrollPeriodModel` y `HrPayrollEntryModel`. `$protectFields = true`. `$allowedFields` explícito en ambos modelos |
| §20 | Catálogos estáticos | `period_type` (weekly/biweekly/monthly) y `payment_method` pueden ser constantes en el modelo, no tablas DB. Definir como `const PERIOD_TYPES = ['weekly' => 'Semanal', 'biweekly' => 'Quincenal', 'monthly' => 'Mensual']` |
| §21 | Sistema de alertas — Detector | `_detectPendingPayrolls()` debe usar `$this->_buildAutoAlert([...])` (no construir `stdClass` manual). Integrarlo en `_detectAll()` con `if ($user->can('hr.manage-payroll')) { $alerts = array_merge($alerts, $this->_safeDetect('_detectPendingPayrolls')); }` |

---

## Permiso nuevo a registrar
- `hr.manage-payroll` → "Crear, aprobar y gestionar nóminas"

---

## Modelo recomendado
**`Composer 2.5`** (modo High) — La complejidad de las vistas (4 cards + datatables + recibo de impresión + modal de calendario) justifica el modelo más capaz.

## Notas importantes
- `hr_profiles` ya contiene: `current_salary`, `daily_salary`, `imss_fee`, `infonavit_contribution`, `benefits_infonavit` → **NO se requiere migración adicional para estos campos**.
- En `HrPayrollEntryModel`, usar `hr_profiles.current_salary` como salario base al generar entradas automáticas.
- Pre-poblar `imss_employee` con `hr_profiles.imss_fee` e `infonavit` con `hr_profiles.infonavit_contribution` al crear las entradas del período.
- Los importes de ISR son capturables manualmente en esta versión inicial; una versión futura puede calcularlos automáticamente con tablas SAT.
