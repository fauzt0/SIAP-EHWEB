<?php $this->extend($layout); ?>

<?php $this->section('title'); ?>
<?= esc($headTitle ?? 'Periodo de nómina') ?>
<?php $this->endSection(); ?>

<?php
$response        = $response ?? [];
$period          = $response['period'] ?? [];
$entries         = $period['entries'] ?? [];
$paymentMethods  = $response['payment_methods'] ?? [];
$paymentStatuses = $response['payment_statuses'] ?? [];
$periodTypes     = $response['period_types'] ?? [];
$statuses        = $response['statuses'] ?? [];
$canManage       = auth()->user()->can('hr.manage-payroll');
$status          = $period['status'] ?? 'draft';
$statusColor     = \App\Models\HR\HrPayrollPeriodModel::STATUS_COLORS[$status] ?? 'secondary';
$statusLabel     = $statuses[$status] ?? $status;
$typeLabel       = $periodTypes[$period['period_type'] ?? ''] ?? ($period['period_type'] ?? '');
$editable        = $canManage && in_array($status, ['draft', 'approved'], true);
$canPay          = $canManage && in_array($status, ['draft', 'approved'], true);
?>

<?php $this->section('main'); ?>
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

<main class="content">
    <div class="container-fluid p-0">
        <div class="row mb-2 mb-xl-3">
            <div class="col-auto d-none d-sm-block">
                <h1 class="h3 mb-3">
                    <i class="fas fa-fw fa-money-check-alt me-2 text-primary"></i><?= esc($period['name'] ?? 'Periodo') ?>
                    <span class="badge bg-<?= esc($statusColor) ?> ms-2"><?= esc($statusLabel) ?></span>
                </h1>
            </div>
            <div class="col-auto ms-auto text-end mt-n1">
                <nav aria-label="breadcrumb"><?= $breadcrumb ?? '' ?></nav>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <div class="text-muted small">Tipo</div>
                        <div class="fw-semibold"><?= esc($typeLabel) ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Periodo</div>
                        <div class="fw-semibold">
                            <?= ! empty($period['start_date']) ? date('d/m/Y', strtotime($period['start_date'])) : '—' ?>
                            —
                            <?= ! empty($period['end_date']) ? date('d/m/Y', strtotime($period['end_date'])) : '—' ?>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small">Sucursal</div>
                        <div class="fw-semibold"><?= esc($period['branch_commercial_name'] ?? $period['branch_name'] ?? 'Todas') ?></div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small">Bruto / Neto</div>
                        <div class="fw-semibold">
                            $<?= number_format((float) ($period['total_gross'] ?? 0), 2) ?>
                            /
                            $<?= number_format((float) ($period['total_net'] ?? 0), 2) ?>
                        </div>
                    </div>
                    <div class="col-md-2 text-md-end">
                        <a href="<?= route_to('hr.payroll.index') ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </a>
                    </div>
                </div>
                <?php if ($canManage): ?>
                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <?php if ($status === 'draft'): ?>
                    <button type="button" class="btn btn-success btn-sm" id="btn-approve">
                        <i class="fas fa-check me-1"></i>Aprobar
                    </button>
                    <?php endif; ?>
                    <?php if (in_array($status, ['draft', 'approved'], true)): ?>
                    <button type="button" class="btn btn-outline-danger btn-sm" id="btn-cancel">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <?php endif; ?>
                    <?php if ($canPay): ?>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-mark-all-paid">
                        <i class="fas fa-dollar-sign me-1"></i>Marcar todas como pagadas
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-users me-2"></i>Entradas por trabajador</h5>
            </div>
            <div class="card-body">
                <table id="payroll-entries-table" class="table table-striped table-hover w-100">
                    <thead>
                        <tr>
                            <th>Trabajador</th>
                            <th>Puesto</th>
                            <th>Días</th>
                            <th>Salario Base</th>
                            <th>Bonos</th>
                            <th>Deducciones</th>
                            <th>Neto</th>
                            <th>Método Pago</th>
                            <th>Estatus</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $e): ?>
                        <?php
                            $ded = (float) ($e['imss_employee'] ?? 0) + (float) ($e['infonavit'] ?? 0) + (float) ($e['isr'] ?? 0) + (float) ($e['other_deductions'] ?? 0);
                            $payStatus = $e['payment_status'] ?? 'pending';
                            $payColor  = $payStatus === 'paid' ? 'success' : ($payStatus === 'partial' ? 'info' : 'warning');
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= esc($e['worker_name'] ?? '—') ?></div>
                                <div class="small text-muted"><?= esc($e['employee_number'] ?? '') ?></div>
                            </td>
                            <td><?= esc($e['job_name'] ?? '—') ?></td>
                            <td><?= (int) ($e['worked_days'] ?? 0) ?></td>
                            <td>$<?= number_format((float) ($e['base_salary'] ?? 0), 2) ?></td>
                            <td>$<?= number_format((float) ($e['bonuses'] ?? 0), 2) ?></td>
                            <td>$<?= number_format($ded, 2) ?></td>
                            <td class="fw-semibold">$<?= number_format((float) ($e['net_amount'] ?? 0), 2) ?></td>
                            <td><?= esc($paymentMethods[$e['payment_method'] ?? ''] ?? ($e['payment_method'] ?? '—')) ?></td>
                            <td><span class="badge bg-<?= $payColor ?>"><?= esc($paymentStatuses[$payStatus] ?? $payStatus) ?></span></td>
                            <td class="text-nowrap">
                                <a href="<?= route_to('hr.payroll.receipt', (int) $e['id']) ?>" class="btn btn-outline-secondary btn-sm" title="Recibo" target="_blank">
                                    <i class="fas fa-receipt"></i>
                                </a>
                                <?php if ($editable): ?>
                                <button type="button" class="btn btn-outline-primary btn-sm btn-edit-entry"
                                    data-id="<?= (int) $e['id'] ?>"
                                    data-worker="<?= esc($e['worker_name'] ?? '', 'attr') ?>"
                                    data-worked-days="<?= (int) ($e['worked_days'] ?? 0) ?>"
                                    data-overtime="<?= esc((string) ($e['overtime_hours'] ?? 0), 'attr') ?>"
                                    data-bonuses="<?= esc((string) ($e['bonuses'] ?? 0), 'attr') ?>"
                                    data-benefits="<?= esc((string) ($e['benefits'] ?? 0), 'attr') ?>"
                                    data-imss="<?= esc((string) ($e['imss_employee'] ?? 0), 'attr') ?>"
                                    data-infonavit="<?= esc((string) ($e['infonavit'] ?? 0), 'attr') ?>"
                                    data-isr="<?= esc((string) ($e['isr'] ?? 0), 'attr') ?>"
                                    data-other="<?= esc((string) ($e['other_deductions'] ?? 0), 'attr') ?>"
                                    data-method="<?= esc($e['payment_method'] ?? 'bank_transfer', 'attr') ?>"
                                    data-notes="<?= esc($e['notes'] ?? '', 'attr') ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($canPay && $payStatus !== 'paid'): ?>
                                <button type="button" class="btn btn-outline-success btn-sm btn-mark-paid"
                                    data-id="<?= (int) $e['id'] ?>"
                                    data-worker="<?= esc($e['worker_name'] ?? '', 'attr') ?>">
                                    <i class="fas fa-dollar-sign"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php if ($editable): ?>
<div class="modal fade" id="edit-entry-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar entrada — <span id="edit-entry-worker"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-edit-entry">
                    <input type="hidden" id="edit-entry-id" value="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Días trabajados</label>
                            <input type="number" name="worked_days" id="edit-worked-days" class="form-control" min="0" max="31">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Horas extra</label>
                            <input type="number" step="0.01" name="overtime_hours" id="edit-overtime" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Método de pago</label>
                            <select name="payment_method" id="edit-method" class="form-select">
                                <?php foreach ($paymentMethods as $key => $label): ?>
                                <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bonos</label>
                            <input type="number" step="0.01" name="bonuses" id="edit-bonuses" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Prestaciones</label>
                            <input type="number" step="0.01" name="benefits" id="edit-benefits" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">IMSS empleado</label>
                            <input type="number" step="0.01" name="imss_employee" id="edit-imss" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">INFONAVIT</label>
                            <input type="number" step="0.01" name="infonavit" id="edit-infonavit" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ISR</label>
                            <input type="number" step="0.01" name="isr" id="edit-isr" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Otras deducciones</label>
                            <input type="number" step="0.01" name="other_deductions" id="edit-other" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notas</label>
                            <textarea name="notes" id="edit-notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btn-save-entry"><i class="fas fa-save me-1"></i>Guardar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($canPay): ?>
<div class="modal fade" id="mark-paid-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-dollar-sign me-2"></i>Registrar pago — <span id="paid-worker-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-mark-paid">
                    <input type="hidden" id="paid-entry-id" value="">
                    <input type="hidden" id="paid-mode" value="entry">
                    <div class="mb-3">
                        <label class="form-label">Fecha de pago</label>
                        <input type="date" name="payment_date" id="paid-date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Referencia de pago</label>
                        <input type="text" name="payment_reference" id="paid-reference" class="form-control" maxlength="100">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-confirm-paid">Confirmar pago</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    initEntriesTable();
    bindShowActions();
});

function getCsrfToken() { return document.getElementById('csrf_token').value; }
function refreshCsrf(r) {
    if (!r) return;
    var header = '<?= csrf_header() ?>';
    var t = (r.headers && r.headers.get) ? r.headers.get(header) : (r.getResponseHeader ? r.getResponseHeader(header) : null);
    if (t) {
        var el = document.getElementById('csrf_token');
        if (el) el.value = t;
    }
}

function initEntriesTable() {
    if ($.fn.dataTable.isDataTable('#payroll-entries-table')) return;
    $('#payroll-entries-table').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[0, 'asc']],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
        columnDefs: [{ orderable: false, targets: -1 }]
    });
}

function postShow(url, extra, reload) {
    var fd = new FormData();
    Object.keys(extra || {}).forEach(function (k) { fd.append(k, extra[k]); });
    return fetch(url, {
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
        if (res.success && reload !== false) window.location.reload();
        return res;
    });
}

function bindShowActions() {
    var approve = document.getElementById('btn-approve');
    if (approve) {
        approve.addEventListener('click', function () {
            postShow('<?= route_to('hr.payroll.approve', (int) ($period['id'] ?? 0)) ?>', {});
        });
    }
    var cancel = document.getElementById('btn-cancel');
    if (cancel) {
        cancel.addEventListener('click', function () {
            if (!window.confirm('¿Cancelar este periodo?')) return;
            postShow('<?= route_to('hr.payroll.cancel', (int) ($period['id'] ?? 0)) ?>', {});
        });
    }
    var markAll = document.getElementById('btn-mark-all-paid');
    if (markAll) {
        markAll.addEventListener('click', function () {
            document.getElementById('paid-mode').value = 'period';
            document.getElementById('paid-entry-id').value = '';
            document.getElementById('paid-worker-name').textContent = 'todas las entradas';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('mark-paid-modal')).show();
        });
    }

    document.getElementById('payroll-entries-table').addEventListener('click', function (e) {
        var editBtn = e.target.closest('.btn-edit-entry');
        if (editBtn) {
            document.getElementById('edit-entry-id').value = editBtn.dataset.id;
            document.getElementById('edit-entry-worker').textContent = editBtn.dataset.worker || '';
            document.getElementById('edit-worked-days').value = editBtn.dataset.workedDays || 0;
            document.getElementById('edit-overtime').value = editBtn.dataset.overtime || 0;
            document.getElementById('edit-bonuses').value = editBtn.dataset.bonuses || 0;
            document.getElementById('edit-benefits').value = editBtn.dataset.benefits || 0;
            document.getElementById('edit-imss').value = editBtn.dataset.imss || 0;
            document.getElementById('edit-infonavit').value = editBtn.dataset.infonavit || 0;
            document.getElementById('edit-isr').value = editBtn.dataset.isr || 0;
            document.getElementById('edit-other').value = editBtn.dataset.other || 0;
            document.getElementById('edit-method').value = editBtn.dataset.method || 'bank_transfer';
            document.getElementById('edit-notes').value = editBtn.dataset.notes || '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('edit-entry-modal')).show();
            return;
        }
        var payBtn = e.target.closest('.btn-mark-paid');
        if (payBtn) {
            document.getElementById('paid-mode').value = 'entry';
            document.getElementById('paid-entry-id').value = payBtn.dataset.id;
            document.getElementById('paid-worker-name').textContent = payBtn.dataset.worker || '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('mark-paid-modal')).show();
        }
    });

    var saveEntry = document.getElementById('btn-save-entry');
    if (saveEntry) {
        saveEntry.addEventListener('click', function () {
            var id = document.getElementById('edit-entry-id').value;
            var form = document.getElementById('form-edit-entry');
            var fd = new FormData(form);
            var extra = {};
            fd.forEach(function (v, k) { extra[k] = v; });
            postShow('<?= route_to('hr.payroll.entry.update', 0) ?>'.replace('/0', '/' + id), extra);
        });
    }

    var confirmPaid = document.getElementById('btn-confirm-paid');
    if (confirmPaid) {
        confirmPaid.addEventListener('click', function () {
            var mode = document.getElementById('paid-mode').value;
            var extra = {
                payment_date: document.getElementById('paid-date').value,
                payment_reference: document.getElementById('paid-reference').value
            };
            var url;
            if (mode === 'period') {
                url = '<?= route_to('hr.payroll.period.paid', (int) ($period['id'] ?? 0)) ?>';
            } else {
                url = '<?= route_to('hr.payroll.entry.paid', 0) ?>'.replace('/0', '/' + document.getElementById('paid-entry-id').value);
            }
            postShow(url, extra);
        });
    }
}
</script>
<?php $this->endSection(); ?>
