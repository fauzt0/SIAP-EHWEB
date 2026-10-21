<?php $this->extend('Layouts/user_loggedin_layout'); ?>

<?php $this->section('title'); echo esc($headTitle ?? 'Servicios Recurrentes'); $this->endSection(); ?>

<?php
$response = $response ?? [];
$stats = $response['stats'] ?? [];
$canManage = auth()->user()->can('catalog.recurring-manage');
?>

<?php $this->section('main') ?>
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

<main class="content">
    <div class="container-fluid p-0">
        <div class="row mb-2 mb-xl-3">
            <div class="col-auto"><h1 class="h3 mb-3"><i class="fas fa-fw fa-calendar-alt me-2 text-warning"></i><?= esc($pageTitle ?? 'Servicios Recurrentes') ?></h1></div>
            <div class="col-auto ms-auto text-end mt-n1"><nav><?= $breadcrumb ?? '' ?></nav></div>
        </div>

        <?php
        if (! function_exists('recurring_stat_money')) {
            function recurring_stat_money(array $stats, string $amountKey, string $mapKey): string
            {
                $map = $stats[$mapKey] ?? [];
                if (! is_array($map) || $map === []) {
                    return '$' . number_format((float) ($stats[$amountKey] ?? 0), 2);
                }
                $parts = [];
                foreach ($map as $currency => $amount) {
                    $parts[] = $currency . ' ' . number_format((float) $amount, 2);
                }

                return implode(' · ', $parts);
            }
        }
        ?>
        <div class="row mb-3">
            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Pendientes este mes</div><div class="h4 mb-0 text-warning" id="stat-pending"><?= esc(recurring_stat_money($stats, 'pending_this_month_amount', 'pending_this_month_by_currency')) ?></div><div class="small text-muted" id="stat-pending-count"><?= (int) ($stats['pending_this_month'] ?? 0) ?> vencimiento(s)</div></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Vencidos</div><div class="h4 mb-0 text-danger" id="stat-overdue"><?= esc(recurring_stat_money($stats, 'overdue_amount', 'overdue_by_currency')) ?></div><div class="small text-muted" id="stat-overdue-count"><?= (int) ($stats['overdue'] ?? 0) ?> vencimiento(s)</div></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Pagados este mes</div><div class="h4 mb-0 text-success" id="stat-paid"><?= esc(recurring_stat_money($stats, 'paid_this_month_amount', 'paid_this_month_by_currency')) ?></div><div class="small text-muted" id="stat-paid-count"><?= (int) ($stats['paid_this_month'] ?? 0) ?> vencimiento(s)</div></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Por vencer (aviso)</div><div class="h4 mb-0 text-info" id="stat-due-soon"><?= esc(recurring_stat_money($stats, 'due_soon_amount', 'due_soon_by_currency')) ?></div><div class="small text-muted" id="stat-due-soon-count"><?= (int) ($stats['due_soon'] ?? 0) ?> vencimiento(s)</div></div></div></div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-calendar">Calendario</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-expenses" id="btn-tab-expenses">Vencimientos</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-templates" id="btn-tab-templates">Plantillas</button></li>
                </ul>
                <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary btn-sm" href="<?= route_to('suppliers.reports.index') ?>">
                    <i class="fas fa-chart-bar me-1"></i>Reportes
                </a>
                <?php if ($canManage): ?>
                <button type="button" class="btn btn-warning btn-sm" id="btn-generate-recurring">
                    <i class="fas fa-sync-alt me-1"></i>Generar vencimientos
                </button>
                <?php endif; ?>
                </div>
            </div>
            <div class="card-body tab-content">
                <div class="tab-pane fade show active" id="tab-calendar">
                    <div id="recurring-calendar"></div>
                </div>
                <div class="tab-pane fade" id="tab-expenses">
                    <div class="row g-2 mb-3">
                        <div class="col-md-2">
                            <select id="filter-exp-status" class="form-select form-select-sm">
                                <option value="">— Estatus —</option>
                                <option value="pending">Pendiente</option>
                                <option value="partial">Parcial</option>
                                <option value="paid">Pagado</option>
                                <option value="cancelled">Cancelado</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="filter-exp-supplier" class="form-select form-select-sm">
                                <option value="">— Proveedor —</option>
                                <?php foreach ($response['suppliers'] ?? [] as $s): ?>
                                <option value="<?= (int) $s->id ?>"><?= esc($s->commercial_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="filter-exp-branch" class="form-select form-select-sm">
                                <option value="">— Sucursal —</option>
                                <?php foreach ($response['branches'] ?? [] as $b): ?>
                                <option value="<?= (int) $b->id ?>"><?= esc($b->commercial_name ?? $b->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="filter-exp-type" class="form-select form-select-sm">
                                <option value="">— Tipo —</option>
                                <?php foreach ($response['service_types'] ?? [] as $typeKey => $typeLabel): ?>
                                <option value="<?= esc($typeKey) ?>"><?= esc($typeLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="month" id="filter-exp-month" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-clear-exp-filters"><i class="fas fa-times me-1"></i>Limpiar</button>
                        </div>
                    </div>
                    <table id="datatables-recurring-expenses" class="table table-striped w-100">
                        <thead>
                            <tr>
                                <th>Vencimiento</th><th>Proveedor</th><th>Servicio</th><th>Sucursal</th>
                                <th>Periodo</th><th>Total</th><th>Pagado</th><th>Estatus</th><th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="tab-pane fade" id="tab-templates">
                    <?php if ($canManage): ?>
                    <div class="text-end mb-2">
                        <button type="button" class="btn btn-warning btn-sm" id="btn-new-template">
                            <i class="fas fa-plus me-1"></i>Nueva plantilla
                        </button>
                    </div>
                    <?php endif; ?>
                    <table id="datatables-recurring-templates" class="table table-striped w-100">
                        <thead>
                            <tr>
                                <th>Servicio</th><th>Proveedor</th><th>Sucursal</th><th>Tipo</th>
                                <th>Monto</th><th>Frecuencia</th><th>Próximo</th><th>Activo</th><th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Offcanvas detalle vencimiento -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasExpenseDetail" style="width:640px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title"><i class="fas fa-receipt me-2"></i><span id="expense-detail-title">Vencimiento</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div id="expense-detail-header" class="mb-3"></div>
        <?php if ($canManage): ?>
        <form id="form-upload-expense-doc" class="mb-3 border rounded p-3 bg-light">
            <div class="row g-2">
                <div class="col-md-4">
                    <select name="doc_type" class="form-select form-select-sm">
                        <option value="payment_proof">Comprobante de pago</option>
                        <option value="invoice">Factura</option>
                        <option value="other">Otro</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="file" name="document_file[]" class="form-control form-control-sm" required multiple accept=".pdf,.jpg,.jpeg,.png,.webp">
                </div>
                <div class="col-md-3">
                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Notas">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-upload me-1"></i>Subir comprobante</button>
                </div>
            </div>
        </form>
        <?php endif; ?>
        <div id="expense-documents-list"></div>
    </div>
</div>

<?php if ($canManage): ?>
<div class="modal fade" id="modalNewTemplate" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-template-title"><i class="fas fa-bolt me-2 text-warning"></i>Nueva plantilla recurrente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-new-template" novalidate>
                    <input type="hidden" name="template_id" id="tpl-id" value="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Proveedor <span class="text-danger">*</span></label>
                            <select name="supplier_id" id="tpl-supplier-id" class="form-select" required>
                                <option value="">— Seleccione —</option>
                                <?php foreach ($response['suppliers'] ?? [] as $s): ?>
                                <option value="<?= (int) $s->id ?>"><?= esc($s->commercial_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sucursal</label>
                            <select name="org_branch_id" class="form-select">
                                <option value="">— Matriz —</option>
                                <?php foreach ($response['branches'] ?? [] as $branch): ?>
                                <option value="<?= (int) $branch->id ?>" <?= ((int)($response['default_branch_id'] ?? 0) === (int)$branch->id) ? 'selected' : '' ?>>
                                    <?= esc($branch->commercial_name ?? $branch->name) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre del servicio <span class="text-danger">*</span></label>
                            <input type="text" name="description" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de servicio</label>
                            <select name="service_type" id="tpl-service-type" class="form-select">
                                <?php
                                $labels = $response['service_types'] ?? [];
                                foreach ($response['service_type_groups'] ?? [] as $groupLabel => $typeKeys):
                                ?>
                                <optgroup label="<?= esc($groupLabel) ?>">
                                    <?php foreach ($typeKeys as $typeKey): ?>
                                    <option value="<?= esc($typeKey) ?>"><?= esc($labels[$typeKey] ?? $typeKey) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Monto <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" min="0.01" step="0.01" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Moneda</label>
                            <select name="currency" class="form-select"><option value="MXN">MXN</option><option value="USD">USD</option></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Frecuencia</label>
                            <select name="frequency" id="tpl-frequency" class="form-select">
                                <option value="weekly">Semanal</option>
                                <option value="monthly" selected>Mensual</option>
                                <option value="quarterly">Trimestral</option>
                                <option value="semiannual">Semestral</option>
                                <option value="yearly">Anual</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="tpl-billing-day-field">
                            <label class="form-label">Día de pago (mes)</label>
                            <input type="number" name="billing_day" class="form-control" min="1" max="31" value="1">
                        </div>
                        <div class="col-md-4 d-none" id="tpl-billing-weekday-field">
                            <label class="form-label">Día de la semana</label>
                            <select name="billing_weekday" class="form-select">
                                <option value="1">Lunes</option><option value="2">Martes</option>
                                <option value="3">Miércoles</option><option value="4">Jueves</option>
                                <option value="5">Viernes</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Inicio</label>
                            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fin (opcional)</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Avisar (días antes)</label>
                            <input type="number" name="notify_days_before" class="form-control" value="3" min="0">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" value="1" id="tpl-is-active" checked>
                                <label class="form-check-label" for="tpl-is-active">Plantilla activa</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="btn-submit-template"><i class="fas fa-save me-1"></i>Guardar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="modalMarkPaid" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Registrar pago</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="pay-expense-id">
                <label class="form-label">Monto pagado</label>
                <input type="number" id="pay-amount" class="form-control" min="0" step="0.01">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-confirm-pay">Guardar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalReschedule" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Reprogramar vencimiento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="reschedule-expense-id">
                <label class="form-label">Nueva fecha</label>
                <input type="date" id="reschedule-due-date" class="form-control" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-reschedule">Guardar</button>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection() ?>

<?php $this->section('pageFooterScripts') ?>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initRecurringCalendar();
    initExpensesTable();
    document.getElementById('btn-tab-expenses').addEventListener('shown.bs.tab', function() {
        if (!$.fn.dataTable.isDataTable('#datatables-recurring-expenses')) initExpensesTable();
        else window.expensesTable.columns.adjust();
    });
    document.getElementById('btn-tab-templates').addEventListener('shown.bs.tab', function() {
        if (!$.fn.dataTable.isDataTable('#datatables-recurring-templates')) initTemplatesTable();
    });
    <?php if ($canManage): ?>
    document.getElementById('btn-generate-recurring').addEventListener('click', generateRecurring);
    document.getElementById('btn-confirm-pay').addEventListener('click', confirmMarkPaid);
    document.getElementById('btn-confirm-reschedule').addEventListener('click', confirmReschedule);
    document.getElementById('btn-new-template').addEventListener('click', function() {
        resetTemplateModal();
        new bootstrap.Modal(document.getElementById('modalNewTemplate')).show();
    });
    document.getElementById('btn-submit-template').addEventListener('click', submitTemplate);
    $('#modalNewTemplate').on('shown.bs.modal', function() {
        if (!$('#tpl-supplier-id').hasClass('select2-hidden-accessible')) {
            $('#tpl-supplier-id').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#modalNewTemplate'), allowClear: true });
        }
    });
    document.getElementById('tpl-frequency').addEventListener('change', toggleTplBillingFields);
    <?php endif; ?>
    var expenseDocForm = document.getElementById('form-upload-expense-doc');
    if (expenseDocForm) {
        expenseDocForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!_activeExpenseId) return;
            uploadExpenseDoc(_activeExpenseId, new FormData(this));
        });
    }
});

let _activeExpenseId = null;
const CAN_MANAGE = <?= $canManage ? 'true' : 'false' ?>;

function getCsrf() { return document.getElementById('csrf_token').value; }
function refreshCsrf(r) {
    if (!r) return;
    var header = '<?= csrf_header() ?>';
    var t = null;
    if (r.headers && typeof r.headers.get === 'function') {
        t = r.headers.get(header);
    } else if (typeof r.getResponseHeader === 'function') {
        t = r.getResponseHeader(header);
    }
    if (t) {
        var el = document.getElementById('csrf_token');
        if (el) el.value = t;
    }
}
function esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function initRecurringCalendar() {
    var calEl = document.getElementById('recurring-calendar');
    window.recurringCal = new FullCalendar.Calendar(calEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        height: 520,
        events: function(info, success, failure) {
            fetch('<?= route_to('suppliers.recurring.calendar_ajax') ?>?start=' + info.startStr + '&end=' + info.endStr, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function(r) { refreshCsrf(r); return r.json(); })
            .then(function(data) { success((data.response && data.response.events) || []); })
            .catch(failure);
        },
        eventClick: function(info) {
            info.jsEvent.preventDefault();
            if (info.event.id) openExpenseOffcanvas(info.event.id);
        }
    });
    window.recurringCal.render();
}

function initExpensesTable() {
    if ($.fn.dataTable.isDataTable('#datatables-recurring-expenses')) return;
    window.expensesTable = $('#datatables-recurring-expenses').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: '<?= route_to('suppliers.recurring.list_ajax') ?>', type: 'POST',
            data: function(d) {
                d['<?= csrf_token() ?>'] = getCsrf();
                d.tab = 'expenses';
                d.filter_recurring_only = 1;
                d.filter_status = document.getElementById('filter-exp-status').value;
                d.filter_supplier_id = document.getElementById('filter-exp-supplier').value;
                d.filter_branch_id = document.getElementById('filter-exp-branch').value;
                d.filter_service_type = document.getElementById('filter-exp-type').value;
                d.filter_month = document.getElementById('filter-exp-month').value;
            }
        },
        columns: Array.from({length: 9}, function(_, i) { return { data: i, orderable: i < 8 }; }),
        columnDefs: [{ targets: 8, orderable: false, searchable: false }],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
    });
    $('#datatables-recurring-expenses').on('xhr.dt', function(e,s,j,xhr) { refreshCsrf(xhr); });
    $('#datatables-recurring-expenses').on('click', '.btn-mark-paid', function() {
        document.getElementById('pay-expense-id').value = this.dataset.id;
        document.getElementById('pay-amount').value = this.dataset.total;
        new bootstrap.Modal(document.getElementById('modalMarkPaid')).show();
    });
    $('#datatables-recurring-expenses').on('click', '.btn-view-expense', function() {
        openExpenseOffcanvas(this.dataset.id);
    });
    $('#datatables-recurring-expenses').on('click', '.btn-cancel-expense', function() {
        if (!confirm('¿Cancelar este periodo? No se eliminará la plantilla.')) return;
        postExpenseAction('<?= route_to('suppliers.recurring.cancel', 0) ?>'.replace('/0', '/' + this.dataset.id), {});
    });
    $('#datatables-recurring-expenses').on('click', '.btn-reschedule-expense', function() {
        document.getElementById('reschedule-expense-id').value = this.dataset.id;
        document.getElementById('reschedule-due-date').value = this.dataset.due || '';
        new bootstrap.Modal(document.getElementById('modalReschedule')).show();
    });
    ['filter-exp-status','filter-exp-supplier','filter-exp-branch','filter-exp-type','filter-exp-month'].forEach(function(id) {
        document.getElementById(id).addEventListener('change', function() { window.expensesTable.ajax.reload(); });
    });
    document.getElementById('btn-clear-exp-filters').addEventListener('click', function() {
        document.getElementById('filter-exp-status').value = '';
        document.getElementById('filter-exp-supplier').value = '';
        document.getElementById('filter-exp-branch').value = '';
        document.getElementById('filter-exp-type').value = '';
        document.getElementById('filter-exp-month').value = '';
        window.expensesTable.ajax.reload();
    });
}

function initTemplatesTable() {
    if ($.fn.dataTable.isDataTable('#datatables-recurring-templates')) return;
    window.templatesTable = $('#datatables-recurring-templates').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: '<?= route_to('suppliers.recurring.list_ajax') ?>', type: 'POST',
            data: function(d) {
                d['<?= csrf_token() ?>'] = getCsrf();
                d.tab = 'templates';
                d.filter_supplier_id = document.getElementById('filter-exp-supplier').value;
                d.filter_branch_id = document.getElementById('filter-exp-branch').value;
                d.filter_service_type = document.getElementById('filter-exp-type').value;
            }
        },
        columns: Array.from({length: 9}, function(_, i) { return { data: i, orderable: i < 8 }; }),
        columnDefs: [{ targets: 8, orderable: false, searchable: false }],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
    });
    $('#datatables-recurring-templates').on('xhr.dt', function(e,s,j,xhr) { refreshCsrf(xhr); });
    $('#datatables-recurring-templates').on('click', '.btn-edit-template', function() {
        loadTemplateForEdit(this.dataset.id);
    });
    $('#datatables-recurring-templates').on('click', '.btn-toggle-template', function() {
        toggleTemplate(this.dataset.id, this.dataset.active === '1' ? '0' : '1');
    });
}

function openExpenseOffcanvas(expenseId) {
    _activeExpenseId = expenseId;
    fetch('<?= route_to('suppliers.recurring.documents.list', 0) ?>'.replace('/0', '/' + expenseId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function(r) { refreshCsrf(r); return r.json(); })
    .then(function(data) {
        if (!data.success) { notifyShow(data.message || 'Error al cargar.', 'danger'); return; }
        var exp = data.response.expense;
        var statusLabels = { pending: 'Pendiente', partial: 'Parcial', paid: 'Pagado', cancelled: 'Cancelado' };
        document.getElementById('expense-detail-title').textContent = (exp.template_description || 'Vencimiento') + ' — ' + (exp.period_label || exp.id);
        document.getElementById('expense-detail-header').innerHTML =
            '<dl class="row small mb-0">' +
            '<dt class="col-4">Proveedor</dt><dd class="col-8">' + esc(exp.supplier_name || '—') + '</dd>' +
            '<dt class="col-4">Servicio</dt><dd class="col-8">' + esc(exp.template_description || '—') + '</dd>' +
            '<dt class="col-4">Sucursal</dt><dd class="col-8">' + esc(exp.branch_name || '—') + '</dd>' +
            '<dt class="col-4">Vencimiento</dt><dd class="col-8">' + (exp.due_date ? formatDate(exp.due_date) : '—') + '</dd>' +
            '<dt class="col-4">Total</dt><dd class="col-8">' + parseFloat(exp.total_amount).toFixed(2) + ' ' + esc(exp.currency || 'MXN') + '</dd>' +
            '<dt class="col-4">Pagado</dt><dd class="col-8">' + parseFloat(exp.paid_amount || 0).toFixed(2) + '</dd>' +
            '<dt class="col-4">Estatus</dt><dd class="col-8">' + esc(statusLabels[exp.status] || exp.status) + '</dd>' +
            '</dl>';
        renderExpenseDocuments(data.response.documents || []);
        new bootstrap.Offcanvas(document.getElementById('offcanvasExpenseDetail')).show();
    });
}

function formatDate(iso) {
    if (!iso) return '—';
    var p = iso.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
}

function renderExpenseDocuments(docs) {
    var el = document.getElementById('expense-documents-list');
    if (!docs.length) { el.innerHTML = '<p class="text-muted fst-italic">Sin comprobantes.</p>'; return; }
    var html = '<ul class="list-group list-group-flush">';
    docs.forEach(function(d) {
        html += '<li class="list-group-item d-flex justify-content-between align-items-center px-0">' +
            '<span><i class="fas fa-file me-2"></i>' + esc(d.original_name) + '</span>' +
            '<div class="d-flex gap-1">' +
            '<a class="btn btn-outline-info btn-sm" href="<?= route_to('suppliers.recurring.documents.download', 0) ?>'.replace('/0','/'+d.id) + '?action=view" target="_blank"><i class="fas fa-eye"></i></a>' +
            (CAN_MANAGE ? '<button type="button" class="btn btn-outline-danger btn-sm btn-del-exp-doc" data-id="'+d.id+'"><i class="fas fa-trash"></i></button>' : '') +
            '</div></li>';
    });
    html += '</ul>';
    el.innerHTML = html;
    el.querySelectorAll('.btn-del-exp-doc').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('¿Eliminar comprobante?')) return;
            fetch('<?= route_to('suppliers.recurring.documents.delete', 0) ?>'.replace('/0','/'+btn.dataset.id), {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', '<?= csrf_header() ?>': getCsrf() }
            }).then(function(r) { refreshCsrf(r); return r.json(); })
            .then(function(data) {
                notifyShow(data.message, data.success ? 'success' : 'danger');
                if (data.success && _activeExpenseId) openExpenseOffcanvas(_activeExpenseId);
            });
        });
    });
}

function uploadExpenseDoc(expenseId, fd) {
    fetch('<?= route_to('suppliers.recurring.documents.upload', 0) ?>'.replace('/0', '/' + expenseId), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', '<?= csrf_header() ?>': getCsrf() },
        body: fd
    }).then(function(r) { refreshCsrf(r); return r.json(); })
    .then(function(data) {
        notifyShow(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            document.getElementById('form-upload-expense-doc').reset();
            openExpenseOffcanvas(expenseId);
        }
    });
}

function toggleTplBillingFields() {
    var weekly = document.getElementById('tpl-frequency').value === 'weekly';
    document.getElementById('tpl-billing-day-field').classList.toggle('d-none', weekly);
    document.getElementById('tpl-billing-weekday-field').classList.toggle('d-none', !weekly);
}

<?php if ($canManage): ?>
function submitTemplate() {
    var form = document.getElementById('form-new-template');
    if (!form.checkValidity()) { form.reportValidity(); return; }
    var fd = new FormData(form);
    if (!document.getElementById('tpl-is-active').checked) fd.set('is_active', '0');
    var tplId = document.getElementById('tpl-id').value;
    var url = tplId
        ? '<?= route_to('suppliers.recurring.templates.update', 0) ?>'.replace('/0', '/' + tplId)
        : '<?= route_to('suppliers.recurring.templates.store') ?>';
    fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', '<?= csrf_header() ?>': getCsrf() },
        body: fd
    }).then(function(r) { refreshCsrf(r); return r.json(); })
    .then(function(data) {
        notifyShow(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalNewTemplate')).hide();
            resetTemplateModal();
            if (window.templatesTable) window.templatesTable.ajax.reload(null, false);
        }
    });
}

function resetTemplateModal() {
    var form = document.getElementById('form-new-template');
    form.reset();
    document.getElementById('tpl-id').value = '';
    document.getElementById('tpl-is-active').checked = true;
    document.getElementById('modal-template-title').innerHTML = '<i class="fas fa-bolt me-2 text-warning"></i>Nueva plantilla recurrente';
    toggleTplBillingFields();
    var sel = document.getElementById('tpl-supplier-id');
    if (sel) { sel.value = ''; $(sel).trigger('change'); }
}

function loadTemplateForEdit(id) {
    fetch('<?= route_to('suppliers.recurring.templates.get', 0) ?>'.replace('/0', '/' + id), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function(r) { refreshCsrf(r); return r.json(); })
    .then(function(data) {
        if (!data.success) { notifyShow(data.message || 'Error al cargar.', 'danger'); return; }
        var t = data.response.template;
        document.getElementById('tpl-id').value = t.id;
        document.getElementById('tpl-supplier-id').value = t.supplier_id || '';
        $(document.getElementById('tpl-supplier-id')).trigger('change');
        document.querySelector('#form-new-template [name="org_branch_id"]').value = t.org_branch_id || '';
        document.querySelector('#form-new-template [name="description"]').value = t.description || '';
        document.getElementById('tpl-service-type').value = t.service_type || 'other';
        document.querySelector('#form-new-template [name="amount"]').value = t.amount || '';
        document.querySelector('#form-new-template [name="currency"]').value = t.currency || 'MXN';
        document.getElementById('tpl-frequency').value = t.frequency || 'monthly';
        document.querySelector('#form-new-template [name="billing_day"]').value = t.billing_day || 1;
        document.querySelector('#form-new-template [name="billing_weekday"]').value = t.billing_weekday || 1;
        document.querySelector('#form-new-template [name="start_date"]').value = t.start_date || '';
        document.querySelector('#form-new-template [name="end_date"]').value = t.end_date || '';
        document.querySelector('#form-new-template [name="notify_days_before"]').value = t.notify_days_before || 0;
        document.getElementById('tpl-is-active').checked = parseInt(t.is_active, 10) === 1;
        toggleTplBillingFields();
        document.getElementById('modal-template-title').innerHTML = '<i class="fas fa-edit me-2 text-warning"></i>Editar plantilla recurrente';
        new bootstrap.Modal(document.getElementById('modalNewTemplate')).show();
    });
}

function toggleTemplate(id, nextActive) {
    var fd = new FormData();
    fd.append('is_active', nextActive);
    fetch('<?= route_to('suppliers.recurring.templates.toggle', 0) ?>'.replace('/0', '/' + id), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', '<?= csrf_header() ?>': getCsrf() },
        body: fd
    }).then(function(r) { refreshCsrf(r); return r.json(); })
    .then(function(data) {
        notifyShow(data.message, data.success ? 'success' : 'danger');
        if (data.success && window.templatesTable) window.templatesTable.ajax.reload(null, false);
    });
}

function postExpenseAction(url, extra) {
    var fd = new FormData();
    Object.keys(extra || {}).forEach(function(k) { fd.append(k, extra[k]); });
    fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', '<?= csrf_header() ?>': getCsrf() },
        body: fd
    }).then(function(r) { refreshCsrf(r); return r.json(); })
    .then(function(data) {
        notifyShow(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            applyStats(data.response && data.response.stats);
            if (window.expensesTable) window.expensesTable.ajax.reload(null, false);
            if (window.recurringCal) window.recurringCal.refetchEvents();
        }
    });
}

function confirmReschedule() {
    var id = document.getElementById('reschedule-expense-id').value;
    postExpenseAction('<?= route_to('suppliers.recurring.reschedule', 0) ?>'.replace('/0', '/' + id), {
        due_date: document.getElementById('reschedule-due-date').value
    });
    var modal = bootstrap.Modal.getInstance(document.getElementById('modalReschedule'));
    if (modal) modal.hide();
}

function applyStats(stats) {
    if (!stats) return;
    function moneyMap(map, single) {
        var keys = map && typeof map === 'object' ? Object.keys(map) : [];
        function amount(n) { return (parseFloat(n) || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); }
        if (keys.length > 0) {
            return keys.map(function(code) { return code + ' ' + amount(map[code]); }).join(' · ');
        }
        return '$' + amount(single);
    }
    document.getElementById('stat-pending').textContent = moneyMap(stats.pending_this_month_by_currency, stats.pending_this_month_amount);
    document.getElementById('stat-pending-count').textContent = (stats.pending_this_month || 0) + ' vencimiento(s)';
    document.getElementById('stat-overdue').textContent = moneyMap(stats.overdue_by_currency, stats.overdue_amount);
    document.getElementById('stat-overdue-count').textContent = (stats.overdue || 0) + ' vencimiento(s)';
    document.getElementById('stat-paid').textContent = moneyMap(stats.paid_this_month_by_currency, stats.paid_this_month_amount);
    document.getElementById('stat-paid-count').textContent = (stats.paid_this_month || 0) + ' vencimiento(s)';
    document.getElementById('stat-due-soon').textContent = moneyMap(stats.due_soon_by_currency, stats.due_soon_amount);
    document.getElementById('stat-due-soon-count').textContent = (stats.due_soon || 0) + ' vencimiento(s)';
}

function generateRecurring() {
    fetch('<?= route_to('suppliers.recurring.generate') ?>', {
        method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', '<?= csrf_header() ?>': getCsrf() }
    }).then(function(r) { refreshCsrf(r); return r.json(); }).then(function(data) {
        notifyShow(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            applyStats(data.response && data.response.stats);
            if (window.recurringCal) window.recurringCal.refetchEvents();
            if (window.expensesTable) window.expensesTable.ajax.reload();
        }
    });
}
function confirmMarkPaid() {
    var id = document.getElementById('pay-expense-id').value;
    var fd = new FormData();
    fd.append('paid_amount', document.getElementById('pay-amount').value);
    fetch('<?= route_to('suppliers.recurring.pay', 0) ?>'.replace('/0', '/' + id), {
        method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', '<?= csrf_header() ?>': getCsrf() }, body: fd
    }).then(function(r) { refreshCsrf(r); return r.json(); }).then(function(data) {
        notifyShow(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            applyStats(data.response && data.response.stats);
            bootstrap.Modal.getInstance(document.getElementById('modalMarkPaid')).hide();
            if (window.expensesTable) window.expensesTable.ajax.reload();
            if (window.recurringCal) window.recurringCal.refetchEvents();
        }
    });
}
<?php endif; ?>
</script>
<?php $this->endSection() ?>
