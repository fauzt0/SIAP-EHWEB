<?php $this->extend($layout); ?>

<?php $this->section('title'); ?>
<?= esc($headTitle) ?>
<?php $this->endSection(); ?>

<?php
$company   = $response['company'] ?? null;
$stats     = $response['stats'] ?? ['total' => 0, 'active' => 0, 'inactive' => 0, 'main' => 0];
$hasCompany = ! empty($company);
?>

<?php $this->section('main'); ?>
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

<main class="content">
    <div class="container-fluid p-0">

        <div class="row mb-2 mb-xl-3">
            <div class="col-auto d-none d-sm-block">
                <h1 class="h3 mb-3">
                    <i class="fas fa-fw fa-store me-2 text-primary"></i><?= esc($pageTitle) ?>
                </h1>
            </div>
            <div class="col-auto ms-auto text-end mt-n1">
                <nav aria-label="breadcrumb"><?= $breadcrumb ?? '' ?></nav>
            </div>
        </div>

        <?php if (! $hasCompany): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Configure primero el <a href="<?= route_to('organization.profile') ?>">perfil de la compañía matriz</a> antes de dar de alta sucursales.
        </div>
        <?php endif; ?>

        <div class="row mb-3">
            <?php
            $statCards = [
                ['label' => 'Total Sucursales', 'key' => 'total',    'color' => 'primary', 'icon' => 'fa-store',        'sub' => 'Unidades registradas'],
                ['label' => 'Activas',          'key' => 'active',   'color' => 'success', 'icon' => 'fa-check-circle', 'sub' => 'Operando actualmente'],
                ['label' => 'Inactivas',        'key' => 'inactive', 'color' => 'danger',  'icon' => 'fa-times-circle', 'sub' => 'Fuera de operación'],
                ['label' => 'Principal',        'key' => 'main',     'color' => 'info',    'icon' => 'fa-star',         'sub' => 'Marcadas como matriz'],
            ];
            $totalAll = (int) ($stats['total'] ?? 0);
            foreach ($statCards as $card):
                $val     = (int) ($stats[$card['key']] ?? 0);
                $percent = ($card['key'] !== 'total' && $totalAll > 0) ? round(($val / $totalAll) * 100) : ($card['key'] === 'total' ? 100 : 0);
            ?>
            <div class="col-12 col-sm-6 col-xl-3 d-flex mb-3">
                <div class="card flex-fill">
                    <div class="card-header pb-0">
                        <h5 class="card-title mb-0 mt-1"><?= $card['label'] ?></h5>
                    </div>
                    <div class="card-body my-0 pt-0">
                        <div class="d-flex align-items-center mb-3 mt-2">
                            <div class="flex-grow-1">
                                <h3 class="mb-0 fw-light"><?= number_format($val) ?></h3>
                            </div>
                            <div class="ms-auto">
                                <div class="stat text-<?= $card['color'] ?>">
                                    <i class="fas <?= $card['icon'] ?> align-middle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="progress progress-sm shadow-sm mb-1">
                            <div class="progress-bar bg-<?= $card['color'] ?>" style="width: <?= min(100, max(3, $percent)) ?>%"></div>
                        </div>
                        <small class="text-muted"><?= $card['sub'] ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4 mb-2 mb-md-0">
                                <div class="input-group input-group-search">
                                    <input type="text" class="form-control" id="filter-search" placeholder="Buscar sucursal…">
                                    <button class="btn" type="button"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="d-flex gap-2 flex-wrap justify-content-md-end">
                                    <select class="form-select" id="filter-active" style="max-width:180px;">
                                        <option value="">Todos los estatus</option>
                                        <option value="1">Activas</option>
                                        <option value="0">Inactivas</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" id="btn-clear-filters" title="Limpiar filtros">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php if ($hasCompany): ?>
                                    <button type="button" class="btn btn-primary btn-lg" id="btn-new-branch">
                                        <i class="fas fa-plus"></i> Nueva Sucursal
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <table id="datatables-branches" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th style="width:50px;">Logo</th>
                                    <th>Unidad / RFC</th>
                                    <th>Código</th>
                                    <th>Teléfono</th>
                                    <th>Correo</th>
                                    <th class="text-center">Estatus</th>
                                    <th class="text-center" style="width:150px;">Acciones</th>
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

<!-- Modal Alta / Edición -->
<div class="modal fade" id="modalBranch" tabindex="-1" aria-labelledby="modalBranchLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-top:4px solid #3f80ea;">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBranchLabel">
                    <i class="fas fa-store me-2 text-primary"></i><span id="modal-branch-title">Nueva Sucursal</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="form-branch" enctype="multipart/form-data" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="id" id="branch-id">
                    <input type="hidden" name="company_id" id="branch-company-id" value="<?= $hasCompany ? (int) $company->id : '' ?>">

                    <div class="row g-3">
                        <div class="col-12">
                            <p class="small text-muted mb-0">
                                <i class="fas fa-info-circle me-1"></i>
                                Cada sucursal es una unidad que puede emitir facturas y recibir compras con su propio RFC y datos fiscales.
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre interno <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="branch-name" required
                                   placeholder="Ej: Sucursal Norte">
                            <small class="text-muted">Uso operativo en el sistema (listados, inventario).</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Código <span class="text-danger">*</span></label>
                            <input type="text" class="form-control font-monospace" name="branch_code" id="branch-code" required maxlength="10">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre comercial <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="commercial_name" id="branch-commercial-name" required
                                   placeholder="Como aparece en facturas">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">RFC / Tax ID</label>
                            <input type="text" class="form-control font-monospace" name="tax_id" id="branch-tax-id" maxlength="20">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Razón social</label>
                            <input type="text" class="form-control" name="legal_name" id="branch-legal-name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono de contacto</label>
                            <input type="text" class="form-control" name="phone" id="branch-phone">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo de contacto</label>
                            <input type="email" class="form-control" name="email" id="branch-email">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Sitio web</label>
                            <input type="url" class="form-control" name="website_url" id="branch-website" placeholder="https://">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Dirección fiscal / operativa</label>
                            <textarea class="form-control" name="address" id="branch-address" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Impresora POS</label>
                            <input type="text" class="form-control" name="pos_printer_name" id="branch-printer" placeholder="Ej: EPSON TM-T88V">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Logo</label>
                            <input type="file" class="form-control" name="logo" id="branch-logo" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" name="is_main" id="branch-is-main" value="1">
                                <label class="form-check-label" for="branch-is-main">Sucursal principal</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" name="active" id="branch-active" value="1" checked>
                                <label class="form-check-label" for="branch-active">Sucursal activa</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-branch">
                        <i class="fas fa-save me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Offcanvas detalle -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasBranch" aria-labelledby="offcanvasBranchLabel" style="width:420px;">
    <div class="offcanvas-header border-bottom">
        <div>
            <h5 class="offcanvas-title" id="offcanvasBranchLabel">Detalle de Sucursal</h5>
            <small class="text-muted" id="oc-branch-code"></small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body">
        <div id="oc-loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2 mb-0">Cargando…</p>
        </div>
        <div id="oc-content" class="d-none">
            <div class="text-center mb-4">
                <img id="oc-logo" src="" alt="Logo" class="rounded img-fluid border" style="max-height:120px;object-fit:contain;">
            </div>
            <h4 class="mb-1" id="oc-name"></h4>
            <div class="mb-3" id="oc-badges"></div>
            <dl class="row small mb-0">
                <dt class="col-sm-4">Nombre comercial</dt>
                <dd class="col-sm-8" id="oc-commercial">—</dd>
                <dt class="col-sm-4">RFC</dt>
                <dd class="col-sm-8 font-monospace" id="oc-tax-id">—</dd>
                <dt class="col-sm-4">Razón social</dt>
                <dd class="col-sm-8" id="oc-legal">—</dd>
                <dt class="col-sm-4">Teléfono</dt>
                <dd class="col-sm-8" id="oc-phone">—</dd>
                <dt class="col-sm-4">Correo</dt>
                <dd class="col-sm-8" id="oc-email">—</dd>
                <dt class="col-sm-4">Dirección</dt>
                <dd class="col-sm-8" id="oc-address">—</dd>
                <dt class="col-sm-4">Impresora POS</dt>
                <dd class="col-sm-8" id="oc-printer">—</dd>
                <dt class="col-sm-4">Alta</dt>
                <dd class="col-sm-8" id="oc-created">—</dd>
            </dl>
            <div class="d-grid gap-2 mt-4">
                <button type="button" class="btn btn-primary" id="oc-btn-edit">
                    <i class="fas fa-edit me-1"></i>Editar sucursal
                </button>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('pageFooterScripts'); ?>
<script>
const csrfHeader     = '<?= csrf_header() ?>';
const csrfField      = '<?= csrf_token() ?>';
const branchGetUrl   = '<?= route_to('organization.branch.get_ajax', 0) ?>';
const branchSaveUrl  = '<?= route_to('organization.branch.save') ?>';
const branchToggleUrl = '<?= route_to('organization.branch.toggle_status', 0) ?>';
const branchDeleteUrl = '<?= route_to('organization.branch.delete', 0) ?>';
const hasCompany     = <?= $hasCompany ? 'true' : 'false' ?>;

let _activeBranchId = null;
let branchesTable   = null;
const branchModal   = () => bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBranch'));
const branchOffcanvas = () => bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('offcanvasBranch'));

document.addEventListener('DOMContentLoaded', function () {
    initBranchesDataTable();
    bindBranchForm();
    bindBranchDelegation();
    handleDeepLink();
    showSessionFlash();
});

function showSessionFlash() {
    <?php if (session()->getFlashdata('success')): ?>
    notify(<?= json_encode(session()->getFlashdata('success')) ?>, 'success');
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
    notify(<?= json_encode(session()->getFlashdata('error')) ?>, 'danger');
    <?php endif; ?>
    const params = new URLSearchParams(window.location.search);
    if (params.get('saved') === '1') {
        notify('Sucursal guardada correctamente.', 'success');
        window.history.replaceState({}, '', '<?= route_to('organization.branches') ?>');
    }
}

function handleDeepLink() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('open') === 'create' && hasCompany) {
        openBranchModal();
        window.history.replaceState({}, '', '<?= route_to('organization.branches') ?>');
    }
    if (params.get('open') === 'edit' && params.get('id')) {
        loadBranchModal(params.get('id'));
        window.history.replaceState({}, '', '<?= route_to('organization.branches') ?>');
    }
}

/**
 * Renueva el token CSRF desde headers (DataTables XHR o Fetch Response). §2
 */
function refreshCsrfFromHeaders(response) {
    if (!response) return;
    const newToken = typeof response.getResponseHeader === 'function'
        ? response.getResponseHeader(csrfHeader)
        : response.headers?.get(csrfHeader);
    const input = document.getElementById('csrf_token');
    if (newToken && input) input.value = newToken;
}

function applyCsrfFromJson(data) {
    if (data && data.csrf) document.getElementById('csrf_token').value = data.csrf;
}

function notify(msg, type) {
    if (typeof notifyShow === 'function') notifyShow(msg, type);
    else console.warn(msg);
}

function initBranchesDataTable() {
    if ($.fn.dataTable.isDataTable('#datatables-branches')) return;

    branchesTable = $('#datatables-branches').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        dom: "<'row'<'col-sm-12'tr>>" +
             "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end'p>>",
        ajax: {
            url: '<?= route_to('organization.branches_ajax') ?>',
            type: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data: function (d) {
                d[csrfField] = document.getElementById('csrf_token').value;
                d.filter_active = document.getElementById('filter-active').value;
            }
        },
        columns: [
            { data: 0, orderable: false, searchable: false },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5, orderable: false, searchable: false },
            { data: 6, orderable: false, searchable: false }
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json' },
        pageLength: 15,
        order: [[1, 'asc']]
    });

    $('#datatables-branches').on('xhr.dt', function (e, settings, json, xhr) {
        refreshCsrfFromHeaders(xhr);
        if (json && json.csrf) document.getElementById('csrf_token').value = json.csrf;
    });

    let searchTimer;
    document.getElementById('filter-search').addEventListener('keyup', function () {
        clearTimeout(searchTimer);
        const val = this.value;
        searchTimer = setTimeout(() => branchesTable.search(val).draw(), 300);
    });

    document.getElementById('filter-active').addEventListener('change', () => branchesTable.draw());
    document.getElementById('btn-clear-filters').addEventListener('click', function () {
        document.getElementById('filter-search').value = '';
        document.getElementById('filter-active').value = '';
        branchesTable.search('').draw();
    });

    document.getElementById('btn-new-branch')?.addEventListener('click', openBranchModal);
}

function openBranchModal() {
    const form = document.getElementById('form-branch');
    form.reset();
    document.getElementById('branch-id').value = '';
    document.getElementById('branch-active').checked = true;
    document.getElementById('modal-branch-title').textContent = 'Nueva Sucursal';
    const nameEl = document.getElementById('branch-name');
    const commercialEl = document.getElementById('branch-commercial-name');
    if (!nameEl._syncCommercial) {
        nameEl._syncCommercial = true;
        nameEl.addEventListener('input', function () {
            if (!document.getElementById('branch-id').value && commercialEl.value === '') {
                commercialEl.value = nameEl.value;
            }
        });
    }
    branchModal().show();
}

function loadBranchModal(id) {
    fetch(branchGetUrl.replace(/\/0$/, '/' + id), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => { refreshCsrfFromHeaders(r); return r.json(); })
    .then(data => {
        applyCsrfFromJson(data);
        if (!data.success) {
            notify(data.message || 'No se pudo cargar la sucursal.', 'danger');
            return;
        }
        fillBranchForm(data.response);
        document.getElementById('modal-branch-title').textContent = 'Editar Sucursal';
        branchModal().show();
    })
    .catch(() => notify('Error de comunicación con el servidor.', 'danger'));
}

function fillBranchForm(b) {
    document.getElementById('branch-id').value = b.id || '';
    document.getElementById('branch-company-id').value = b.company_id || '';
    document.getElementById('branch-name').value = b.name || '';
    document.getElementById('branch-commercial-name').value = b.commercial_name || b.name || '';
    document.getElementById('branch-legal-name').value = b.legal_name || '';
    document.getElementById('branch-tax-id').value = b.tax_id || '';
    document.getElementById('branch-code').value = b.branch_code || '';
    document.getElementById('branch-phone').value = b.phone || '';
    document.getElementById('branch-website').value = b.website_url || '';
    document.getElementById('branch-email').value = b.email || '';
    document.getElementById('branch-address').value = b.address || '';
    document.getElementById('branch-printer').value = b.pos_printer_name || '';
    document.getElementById('branch-is-main').checked = b.is_main == 1;
    document.getElementById('branch-active').checked = b.active == 1;
}

function bindBranchForm() {
    document.getElementById('form-branch').addEventListener('submit', function (e) {
        e.preventDefault();
        if (!hasCompany) {
            notify('Configure primero el perfil de la compañía.', 'warning');
            return;
        }

        const formData = new FormData(this);
        if (!document.getElementById('branch-is-main').checked) formData.set('is_main', '0');
        if (!document.getElementById('branch-active').checked) formData.set('active', '0');

        const csrf = document.getElementById('csrf_token').value;
        const btn  = document.getElementById('btn-save-branch');
        const html = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando…';

        fetch(branchSaveUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                [csrfHeader]: csrf
            },
            body: formData
        })
        .then(r => { refreshCsrfFromHeaders(r); return r.json(); })
        .then(data => {
            applyCsrfFromJson(data);
            if (data.success) {
                notify(data.message, 'success');
                branchModal().hide();
                branchesTable.ajax.reload(null, false);
            } else {
                let msg = data.message || 'Error al guardar.';
                if (data.errors && typeof data.errors === 'object') {
                    msg += ' ' + Object.values(data.errors).flat().join(' ');
                }
                notify(msg, 'danger');
            }
        })
        .catch(() => notify('Error de comunicación con el servidor.', 'danger'))
        .finally(() => { btn.disabled = false; btn.innerHTML = html; });
    });
}

function openBranchOffcanvas(id) {
    _activeBranchId = id;
    document.getElementById('oc-loading').classList.remove('d-none');
    document.getElementById('oc-content').classList.add('d-none');
    branchOffcanvas().show();

    fetch(branchGetUrl.replace(/\/0$/, '/' + id), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => { refreshCsrfFromHeaders(r); return r.json(); })
    .then(data => {
        applyCsrfFromJson(data);
        document.getElementById('oc-loading').classList.add('d-none');
        if (!data.success) {
            notify(data.message || 'Error al cargar.', 'danger');
            branchOffcanvas().hide();
            return;
        }
        const b = data.response;
        document.getElementById('oc-content').classList.remove('d-none');
        document.getElementById('oc-name').textContent = b.name;
        document.getElementById('oc-commercial').textContent = b.commercial_name || b.name || '—';
        document.getElementById('oc-tax-id').textContent = b.tax_id || '—';
        document.getElementById('oc-legal').textContent = b.legal_name || '—';
        document.getElementById('oc-branch-code').textContent = b.branch_code;
        document.getElementById('oc-logo').src = b.logo_url;
        document.getElementById('oc-phone').textContent = b.phone || '—';
        document.getElementById('oc-email').textContent = b.email || '—';
        document.getElementById('oc-address').textContent = b.address || '—';
        document.getElementById('oc-printer').textContent = b.pos_printer_name || '—';
        document.getElementById('oc-created').textContent = b.created_at
            ? new Date(b.created_at).toLocaleDateString('es-MX') : '—';

        let badges = b.active
            ? '<span class="badge badge-subtle-success">Activa</span>'
            : '<span class="badge badge-subtle-danger">Inactiva</span>';
        if (b.is_main == 1) badges += ' <span class="badge badge-subtle-primary">Principal</span>';
        document.getElementById('oc-badges').innerHTML = badges;
    })
    .catch(() => {
        document.getElementById('oc-loading').classList.add('d-none');
        notify('Error de comunicación con el servidor.', 'danger');
        branchOffcanvas().hide();
    });
}

document.getElementById('oc-btn-edit').addEventListener('click', function () {
    if (_activeBranchId) {
        branchOffcanvas().hide();
        loadBranchModal(_activeBranchId);
    }
});

function bindBranchDelegation() {
    document.addEventListener('click', function (e) {
        const show = e.target.closest('.btn-show-branch');
        if (show) { openBranchOffcanvas(show.dataset.id); return; }

        const edit = e.target.closest('.btn-edit-branch');
        if (edit) { loadBranchModal(edit.dataset.id); return; }

        const toggle = e.target.closest('.btn-toggle-branch');
        if (toggle) {
            toggleBranchStatus(toggle.dataset.id, toggle.dataset.status);
            return;
        }

        const del = e.target.closest('.btn-delete-branch');
        if (del) { deleteBranch(del.dataset.id); }
    });
}

function orgPost(url, payload) {
    const csrf = document.getElementById('csrf_token').value;
    payload[csrfField] = csrf;
    return fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded',
            [csrfHeader]: csrf
        },
        body: new URLSearchParams(payload).toString()
    }).then(r => { refreshCsrfFromHeaders(r); return r.json(); });
}

function toggleBranchStatus(id, status) {
    if (!confirm('¿Cambiar el estatus de esta sucursal?')) return;
    orgPost(branchToggleUrl.replace(/\/0$/, '/' + id), { status: status })
        .then(res => {
            applyCsrfFromJson(res);
            if (res.success) {
                notify(res.message, 'success');
                branchesTable.ajax.reload(null, false);
            } else {
                notify(res.message || 'Error', 'danger');
            }
        })
        .catch(() => notify('Error de comunicación con el servidor.', 'danger'));
}

function deleteBranch(id) {
    if (!confirm('¿Eliminar esta sucursal? Puede afectar productos o usuarios vinculados.')) return;
    orgPost(branchDeleteUrl.replace(/\/0$/, '/' + id), {})
        .then(res => {
            applyCsrfFromJson(res);
            if (res.success) {
                notify(res.message, 'success');
                branchesTable.ajax.reload(null, false);
            } else {
                notify(res.message || 'Error', 'danger');
            }
        })
        .catch(() => notify('Error de comunicación con el servidor.', 'danger'));
}
</script>
<?php $this->endSection(); ?>
