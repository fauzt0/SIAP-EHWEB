<?php $this->extend($layout); ?>

<?php $this->section('title'); ?>
<?= esc($headTitle ?? 'Nóminas') ?>
<?php $this->endSection(); ?>

<?php
$response    = $response ?? [];
$stats       = $response['stats'] ?? [];
$branches    = $response['branches'] ?? [];
$periodTypes = $response['period_types'] ?? [];
$statuses    = $response['statuses'] ?? [];
$canManage   = auth()->user()->can('hr.manage-payroll');
?>

<?php $this->section('main'); ?>
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

<main class="content">
    <div class="container-fluid p-0">
        <div class="row mb-2 mb-xl-3">
            <div class="col-auto d-none d-sm-block">
                <h1 class="h3 mb-3">
                    <i class="fas fa-fw fa-money-check-alt me-2 text-primary"></i><?= esc($pageTitle ?? 'Nóminas') ?>
                </h1>
            </div>
            <div class="col-auto ms-auto text-end mt-n1">
                <nav aria-label="breadcrumb"><?= $breadcrumb ?? '' ?></nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-sm-6 col-md-3 d-flex">
                <div class="card flex-fill">
                    <div class="card-header pb-0">
                        <h5 class="card-title mb-0 mt-1">Nóminas del mes</h5>
                    </div>
                    <div class="card-body my-0 pt-0">
                        <div class="d-flex align-items-center mb-3 mt-2">
                            <div class="flex-grow-1">
                                <h3 class="mb-0 fw-light"><?= number_format((int) ($stats['periods_this_month'] ?? 0)) ?></h3>
                            </div>
                            <div class="ms-auto">
                                <div class="stat text-primary"><i class="fas fa-money-check-alt"></i></div>
                            </div>
                        </div>
                        <small class="text-muted">Periodos con inicio en el mes actual</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 d-flex">
                <div class="card flex-fill">
                    <div class="card-header pb-0">
                        <h5 class="card-title mb-0 mt-1">Pagos pendientes</h5>
                    </div>
                    <div class="card-body my-0 pt-0">
                        <div class="d-flex align-items-center mb-3 mt-2">
                            <div class="flex-grow-1">
                                <h3 class="mb-0 fw-light"><?= number_format((int) ($stats['pending_payments'] ?? 0)) ?></h3>
                            </div>
                            <div class="ms-auto">
                                <div class="stat text-warning"><i class="fas fa-clock"></i></div>
                            </div>
                        </div>
                        <small class="text-muted">Entradas con estatus pendiente</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 d-flex">
                <div class="card flex-fill">
                    <div class="card-header pb-0">
                        <h5 class="card-title mb-0 mt-1">Pagadas en el mes</h5>
                    </div>
                    <div class="card-body my-0 pt-0">
                        <div class="d-flex align-items-center mb-3 mt-2">
                            <div class="flex-grow-1">
                                <h3 class="mb-0 fw-light"><?= number_format((int) ($stats['paid_this_month'] ?? 0)) ?></h3>
                            </div>
                            <div class="ms-auto">
                                <div class="stat text-success"><i class="fas fa-check"></i></div>
                            </div>
                        </div>
                        <small class="text-muted">Entradas pagadas este mes</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 d-flex">
                <div class="card flex-fill">
                    <div class="card-header pb-0">
                        <h5 class="card-title mb-0 mt-1">Monto pendiente</h5>
                    </div>
                    <div class="card-body my-0 pt-0">
                        <div class="d-flex align-items-center mb-3 mt-2">
                            <div class="flex-grow-1">
                                <h3 class="mb-0 fw-light">$<?= number_format((float) ($stats['pending_amount'] ?? 0), 2) ?></h3>
                            </div>
                            <div class="ms-auto">
                                <div class="stat text-info"><i class="fas fa-dollar-sign"></i></div>
                            </div>
                        </div>
                        <small class="text-muted">Suma neta de pagos pendientes</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Periodos de nómina</h5>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btn-open-schedule">
                                    <i class="fas fa-calendar-alt me-1"></i>Ver Calendario
                                </button>
                                <?php if ($canManage): ?>
                                <button type="button" class="btn btn-success btn-sm" id="btn-new-payroll">
                                    <i class="fas fa-plus me-1"></i>Nueva Nómina
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <select id="filter-status" class="form-select form-select-sm">
                                    <option value="">— Todos los estatus —</option>
                                    <?php foreach ($statuses as $key => $label): ?>
                                    <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="filter-branch" class="form-select form-select-sm">
                                    <option value="">— Todas las sucursales —</option>
                                    <?php foreach ($branches as $b): ?>
                                    <option value="<?= (int) $b->id ?>"><?= esc($b->commercial_name ?? $b->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <table id="payroll-table" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Período</th>
                                    <th>Trabajadores</th>
                                    <th>Total Bruto</th>
                                    <th>Total Neto</th>
                                    <th>Estatus</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php if ($canManage): ?>
<div class="modal fade" id="create-period-modal" tabindex="-1" aria-labelledby="create-period-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="create-period-modal-label">
                    <i class="fas fa-money-check-alt me-2 text-success"></i>Nueva nómina
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="form-create-period">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="period-name" class="form-control" required maxlength="100" placeholder="Ej. Quincena 1 septiembre">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de periodo <span class="text-danger">*</span></label>
                        <select name="period_type" id="period-type" class="form-select" required>
                            <?php foreach ($periodTypes as $key => $label): ?>
                            <option value="<?= esc($key) ?>" <?= $key === 'biweekly' ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha inicio <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="period-start" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha fin <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="period-end" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sucursal</label>
                        <select name="branch_id" id="period-branch" class="form-select">
                            <option value="">— Todas las sucursales —</option>
                            <?php foreach ($branches as $b): ?>
                            <option value="<?= (int) $b->id ?>"><?= esc($b->commercial_name ?? $b->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Se generarán entradas para trabajadores activos de la sucursal (o de todas).</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Días trabajados</label>
                        <input type="number" name="worked_days" id="period-worked-days" class="form-control" min="1" max="31" placeholder="Auto según tipo">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-submit-period">
                    <i class="fas fa-save me-1"></i>Crear periodo
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?= view('HR/Payroll/payroll_schedule_modal', ['response' => $response]) ?>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    initPayrollTable();
    bindPayrollIndexActions();
    <?php if ($canManage): ?>
    initCreatePeriodModal();
    <?php endif; ?>
    bindScheduleModal();
    var open = new URLSearchParams(window.location.search).get('open');
    if (open === 'create' && document.getElementById('create-period-modal')) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('create-period-modal')).show();
    }
    if (open === 'schedule') {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('payroll-schedule-modal')).show();
    }
});

function getCsrfToken() {
    return document.getElementById('csrf_token').value;
}
function refreshCsrf(r) {
    if (!r) return;
    var header = '<?= csrf_header() ?>';
    var t = null;
    if (r.headers && typeof r.headers.get === 'function') t = r.headers.get(header);
    else if (typeof r.getResponseHeader === 'function') t = r.getResponseHeader(header);
    if (t) {
        var el = document.getElementById('csrf_token');
        if (el) el.value = t;
    }
}

function initPayrollTable() {
    if ($.fn.dataTable.isDataTable('#payroll-table')) return;
    window.payrollTable = $('#payroll-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '<?= route_to('hr.payroll.list_ajax') ?>',
            type: 'POST',
            data: function (d) {
                d['<?= csrf_token() ?>'] = getCsrfToken();
                d.filter_status = document.getElementById('filter-status').value;
                d.filter_branch_id = document.getElementById('filter-branch').value;
            }
        },
        columns: [
            { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 },
            { data: 4 }, { data: 5 }, { data: 6 },
            { data: 7, orderable: false, searchable: false, className: 'text-center' }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
    });
    $('#payroll-table').on('xhr.dt', function (e, s, j, xhr) { refreshCsrf(xhr); });
    document.getElementById('filter-status').addEventListener('change', function () { window.payrollTable.ajax.reload(); });
    document.getElementById('filter-branch').addEventListener('change', function () { window.payrollTable.ajax.reload(); });
}

function bindPayrollIndexActions() {
    var table = document.getElementById('payroll-table');
    table.addEventListener('click', function (e) {
        var btnApprove = e.target.closest('.btn-approve-period');
        if (btnApprove) {
            e.preventDefault();
            postPayroll('<?= route_to('hr.payroll.approve', 0) ?>'.replace('/0', '/' + btnApprove.dataset.id), {});
            return;
        }
        var btnCancel = e.target.closest('.btn-cancel-period');
        if (btnCancel) {
            e.preventDefault();
            if (!window.confirm('¿Cancelar este periodo de nómina?')) return;
            postPayroll('<?= route_to('hr.payroll.cancel', 0) ?>'.replace('/0', '/' + btnCancel.dataset.id), {});
        }
    });
    var btnCal = document.getElementById('btn-open-schedule');
    if (btnCal) {
        btnCal.addEventListener('click', function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('payroll-schedule-modal')).show();
        });
    }
    var btnNew = document.getElementById('btn-new-payroll');
    if (btnNew) {
        btnNew.addEventListener('click', function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('create-period-modal')).show();
        });
    }
}

function postPayroll(url, extra) {
    var fd = new FormData();
    Object.keys(extra).forEach(function (k) { fd.append(k, extra[k]); });
    fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_header() ?>': getCsrfToken()
        },
        body: fd
    })
    .then(function (r) { refreshCsrf(r); return r.json(); })
    .then(function (res) {
        if (typeof notifyShow === 'function') {
            notifyShow(res.message, res.success ? 'success' : 'danger');
        }
        if (res.success && window.payrollTable) window.payrollTable.ajax.reload(null, false);
    });
}

<?php if ($canManage): ?>
function initCreatePeriodModal() {
    var $branch = $('#period-branch');
    if ($branch.length && !$branch.hasClass('select2-hidden-accessible')) {
        $branch.select2({
            theme: 'bootstrap-5',
            placeholder: 'Seleccione una sucursal',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#create-period-modal')
        });
    }
    document.getElementById('btn-submit-period').addEventListener('click', function () {
        var form = document.getElementById('form-create-period');
        if (!form.reportValidity()) return;
        var fd = new FormData(form);
        fetch('<?= route_to('hr.payroll.store') ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                '<?= csrf_header() ?>': getCsrfToken()
            },
            body: fd
        })
        .then(function (r) { refreshCsrf(r); return r.json(); })
        .then(function (res) {
            if (typeof notifyShow === 'function') {
                notifyShow(res.message, res.success ? 'success' : 'danger');
            }
            if (res.success && res.response && res.response.redirect_to) {
                window.location.href = res.response.redirect_to;
            }
        });
    });
}
<?php endif; ?>

function bindScheduleModal() {
    var modal = document.getElementById('payroll-schedule-modal');
    if (!modal) return;

    var form = document.getElementById('form-payroll-schedule');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var id = document.getElementById('schedule-id').value;
            var url = id
                ? '<?= route_to('hr.payroll.schedule.update', 0) ?>'.replace('/0', '/' + id)
                : '<?= route_to('hr.payroll.schedule.store') ?>';
            var fd = new FormData(form);
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': getCsrfToken()
                },
                body: fd
            })
            .then(function (r) { refreshCsrf(r); return r.json(); })
            .then(function (res) {
                if (typeof notifyShow === 'function') {
                    notifyShow(res.message, res.success ? 'success' : 'danger');
                }
                if (res.success) window.location.reload();
            });
        });
        document.getElementById('btn-reset-schedule').addEventListener('click', function () {
            form.reset();
            document.getElementById('schedule-id').value = '';
        });
    }

    modal.addEventListener('click', function (e) {
        var editBtn = e.target.closest('.btn-edit-schedule');
        if (editBtn) {
            document.getElementById('schedule-id').value = editBtn.dataset.id;
            document.getElementById('schedule-name').value = editBtn.dataset.name || '';
            document.getElementById('schedule-period-type').value = editBtn.dataset.periodType || '';
            document.getElementById('schedule-next-date').value = editBtn.dataset.next || '';
            document.getElementById('schedule-recurrence-day').value = editBtn.dataset.recurrence || '';
            document.getElementById('schedule-branch-id').value = editBtn.dataset.branch || '';
            document.getElementById('schedule-active').value = editBtn.dataset.active || '1';
            return;
        }
        var delBtn = e.target.closest('.btn-delete-schedule');
        if (delBtn) {
            if (!window.confirm('¿Eliminar este calendario?')) return;
            fetch('<?= route_to('hr.payroll.schedule.delete', 0) ?>'.replace('/0', '/' + delBtn.dataset.id), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': getCsrfToken()
                },
                body: new FormData()
            })
            .then(function (r) { refreshCsrf(r); return r.json(); })
            .then(function (res) {
                if (typeof notifyShow === 'function') {
                    notifyShow(res.message, res.success ? 'success' : 'danger');
                }
                if (res.success) window.location.reload();
            });
        }
    });
}
</script>
<?php $this->endSection(); ?>
