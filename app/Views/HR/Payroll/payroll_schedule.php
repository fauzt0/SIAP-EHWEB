<?php $this->extend($layout); ?>

<?php $this->section('title'); ?>
<?= esc($headTitle ?? 'Calendario de nóminas') ?>
<?php $this->endSection(); ?>

<?php
$response    = $response ?? [];
$periodTypes = $response['period_types'] ?? [];
$canManage   = auth()->user()->can('hr.manage-payroll');
?>

<?php $this->section('main'); ?>
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

<main class="content">
    <div class="container-fluid p-0">
        <div class="row mb-2 mb-xl-3">
            <div class="col-auto d-none d-sm-block">
                <h1 class="h3 mb-3">
                    <i class="fas fa-fw fa-calendar-alt me-2 text-primary"></i><?= esc($pageTitle ?? 'Calendario de Nóminas') ?>
                </h1>
            </div>
            <div class="col-auto ms-auto text-end mt-n1">
                <nav aria-label="breadcrumb"><?= $breadcrumb ?? '' ?></nav>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Planeador de pagos</h5>
                <a href="<?= route_to('hr.payroll.index') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Volver a nóminas
                </a>
            </div>
            <div class="card-body">
                <?= view('HR/Payroll/payroll_schedule_modal', ['response' => $response]) ?>
                <p class="text-muted mb-3">Use el formulario para programar fechas de pago recurrentes por sucursal.</p>
                <button type="button" class="btn btn-primary btn-sm" id="btn-open-schedule-page">
                    <i class="fas fa-calendar-alt me-1"></i>Abrir planeador
                </button>
            </div>
        </div>
    </div>
</main>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('payroll-schedule-modal');
    if (modalEl) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        document.getElementById('btn-open-schedule-page').addEventListener('click', function () {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    }
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

(function bindSchedulePage() {
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
            fetch(url, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', '<?= csrf_header() ?>': getCsrfToken() },
                body: new FormData(form)
            })
            .then(function (r) { refreshCsrf(r); return r.json(); })
            .then(function (res) {
                if (typeof notifyShow === 'function') notifyShow(res.message, res.success ? 'success' : 'danger');
                if (res.success) window.location.reload();
            });
        });
        var resetBtn = document.getElementById('btn-reset-schedule');
        if (resetBtn) resetBtn.addEventListener('click', function () {
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
                headers: { 'X-Requested-With': 'XMLHttpRequest', '<?= csrf_header() ?>': getCsrfToken() },
                body: new FormData()
            })
            .then(function (r) { refreshCsrf(r); return r.json(); })
            .then(function (res) {
                if (typeof notifyShow === 'function') notifyShow(res.message, res.success ? 'success' : 'danger');
                if (res.success) window.location.reload();
            });
        }
    });
})();
</script>
<?php $this->endSection(); ?>
