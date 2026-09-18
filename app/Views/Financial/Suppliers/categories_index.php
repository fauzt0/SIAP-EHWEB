<?php $this->extend($layout); ?>

<?php $this->section('title'); echo esc($headTitle); $this->endSection(); ?>

<?php $this->section('main') ?>

<?php /* §2 — Token CSRF global para DataTables y Fetch API */ ?>
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

<main class="content">
    <div class="container-fluid p-0">

        <div class="row mb-2 mb-xl-3">
            <div class="col-auto d-none d-sm-block">
                <h1 class="h3 mb-3">
                    <i class="fas fa-fw fa-tags me-2 text-primary"></i><?= esc($pageTitle) ?>
                </h1>
            </div>
            <div class="col-auto ms-auto text-end mt-n1">
                <nav aria-label="breadcrumb">
                    <?= isset($breadcrumb) ? $breadcrumb : '' ?>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        <div class="row mb-3">
                            <div class="col-md-4 mb-2 mb-md-0">
                                <div class="input-group input-group-search">
                                    <input type="text" class="form-control" id="filter-search"
                                           placeholder="Buscar categoría…">
                                    <button class="btn" type="button">
                                        <i class="fas fa-search align-middle"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-8 mb-2 mb-md-0">
                                <div class="d-flex gap-2 flex-wrap align-items-center justify-content-md-end">
                                    <select class="form-select" id="filter-status" style="max-width:180px;">
                                        <option value="">Todos los estatus</option>
                                        <option value="1">Activas</option>
                                        <option value="0">Inactivas</option>
                                        <option value="deleted">Eliminadas (Papelera)</option>
                                    </select>
                                    <button class="btn btn-outline-secondary" id="btn-clear-filters" title="Limpiar filtros">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php if (auth()->user()->can('catalog.manage-suppliers')): ?>
                                    <button type="button" class="btn btn-primary btn-lg" id="btn-nueva-categoria">
                                        <i class="fas fa-plus"></i> Nueva Categoría
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <table id="categories-table" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Categoría</th>
                                    <th>Descripción</th>
                                    <th class="text-center">Proveedores</th>
                                    <th class="text-center">Estatus</th>
                                    <th class="text-center" style="width:120px;">Acciones</th>
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

<?php if (auth()->user()->can('catalog.manage-suppliers')): ?>
<!-- Modal alta/edición de categoría -->
<div class="modal fade" id="modal-category" tabindex="-1" aria-labelledby="modal-category-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-category-title">
                    <i class="fas fa-tags me-2 text-primary"></i><span id="modal-title-text">Nueva Categoría</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="form-category">
                <input type="hidden" name="id" id="cat-id" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="cat-name"
                               maxlength="100" required placeholder="Ej: Infraestructura">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripción</label>
                        <textarea class="form-control" name="description" id="cat-description" rows="3"
                                  placeholder="Descripción breve de la categoría…"></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold">Estatus</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="active" id="cat-active" value="1" checked>
                            <label class="form-check-label" for="cat-active">Activa</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-category">
                        <i class="fas fa-save me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal confirmación eliminar / restaurar (§21 — advertencia visual si hay proveedores) -->
<div class="modal fade" id="modal-category-confirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title" id="confirm-title">
                    <i class="fas fa-exclamation-triangle me-2 text-warning"></i>Confirmar
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="confirm-warning" class="alert alert-danger d-none" role="alert">
                    <i class="fas fa-ban me-1"></i>
                    <strong>No se puede eliminar.</strong>
                    Esta categoría tiene <span id="confirm-suppliers-count">0</span> proveedor(es) vinculado(s).
                    Reasigne esos proveedores antes de eliminar la categoría.
                </div>
                <p id="confirm-message" class="mb-0">¿Desea continuar?</p>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-confirm-action">
                    <i class="fas fa-trash-alt me-1"></i>Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $this->endSection() ?>

<?php $this->section('pageFooterScripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    initCategoriesDataTable();
    bindCategoryActions();
});

function initCategoriesDataTable() {
    /* §6 — DataTables Guard */
    if ($.fn.dataTable.isDataTable('#categories-table')) return;

    window.categoriesTable = $('#categories-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        dom: "<'row'<'col-sm-12'tr>>" +
             "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center'p>>",
        ajax: {
            url: '<?= route_to('suppliers.categories.list_ajax') ?>',
            type: 'POST',
            data: function (d) {
                d['<?= csrf_token() ?>'] = document.getElementById('csrf_token').value;
                d.filter_status = document.getElementById('filter-status').value;
            }
        },
        columns: [
            { data: 0 },
            { data: 1, orderable: false },
            { data: 2, orderable: false, searchable: false, className: 'text-center' },
            { data: 3, orderable: false, searchable: false, className: 'text-center' },
            { data: 4, orderable: false, searchable: false, className: 'text-center' }
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json' },
        pageLength: 15,
        order: [[0, 'asc']]
    });

    /* §2 — Renovar CSRF tras cada petición DataTables */
    $('#categories-table').on('xhr.dt', function (e, settings, json, xhr) {
        if (xhr && xhr.getResponseHeader('<?= csrf_header() ?>')) {
            document.getElementById('csrf_token').value = xhr.getResponseHeader('<?= csrf_header() ?>');
        }
    });

    var searchInput = document.getElementById('filter-search');
    if (searchInput) {
        var searchTimer;
        searchInput.addEventListener('keyup', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                if (window.categoriesTable) {
                    window.categoriesTable.search(searchInput.value).draw();
                }
            }, 300);
        });
    }

    document.getElementById('filter-status').addEventListener('change', function () {
        if (window.categoriesTable) window.categoriesTable.draw();
    });

    document.getElementById('btn-clear-filters').addEventListener('click', function () {
        document.getElementById('filter-status').value = '';
        if (searchInput) searchInput.value = '';
        if (window.categoriesTable) window.categoriesTable.search('').draw();
    });
}

function bindCategoryActions() {
    var form = document.getElementById('form-category');
    if (!form) return;

    var modalEl      = document.getElementById('modal-category');
    var modal        = new bootstrap.Modal(modalEl);
    var confirmEl    = document.getElementById('modal-category-confirm');
    var confirmModal = new bootstrap.Modal(confirmEl);
    var pendingAction = null;

    var btnNueva = document.getElementById('btn-nueva-categoria');
    if (btnNueva) {
        btnNueva.addEventListener('click', function () {
            form.reset();
            document.getElementById('cat-id').value = '';
            document.getElementById('cat-active').checked = true;
            document.getElementById('modal-title-text').textContent = 'Nueva Categoría';
            modal.show();
        });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!form.checkValidity()) { form.reportValidity(); return; }

        var id       = document.getElementById('cat-id').value;
        var token    = document.getElementById('csrf_token').value;
        var formData = new FormData(form);
        formData.set('active', document.getElementById('cat-active').checked ? '1' : '0');

        var url = id
            ? '<?= route_to('suppliers.categories.update', 0) ?>'.replace('/0', '/' + id)
            : '<?= route_to('suppliers.categories.store') ?>';

        var btnSave = document.getElementById('btn-save-category');
        var original = btnSave.innerHTML;
        btnSave.disabled = true;
        btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                '<?= csrf_header() ?>': token
            },
            body: formData
        })
        .then(function (r) {
            var nt = r.headers.get('<?= csrf_header() ?>');
            if (nt) document.getElementById('csrf_token').value = nt;
            return r.json();
        })
        .then(function (res) {
            if (res.csrf) document.getElementById('csrf_token').value = res.csrf;
            if (res.success) {
                notifyShow(res.message, 'success');
                modal.hide();
                if (window.categoriesTable) window.categoriesTable.ajax.reload(null, false);
            } else {
                var errs = res.errors ? Object.values(res.errors).join('<br>') : '';
                notifyShow((res.message || 'Error al guardar.') + (errs ? '<br>' + errs : ''), 'danger');
            }
        })
        .catch(function (err) {
            console.error(err);
            notifyShow('Error de conexión con el servidor.', 'danger');
        })
        .finally(function () {
            btnSave.disabled = false;
            btnSave.innerHTML = original;
        });
    });

    document.addEventListener('click', function (e) {
        var btnEdit = e.target.closest('.btn-edit-category');
        if (btnEdit) {
            document.getElementById('cat-id').value          = btnEdit.dataset.id || '';
            document.getElementById('cat-name').value        = btnEdit.dataset.name || '';
            document.getElementById('cat-description').value = btnEdit.dataset.description || '';
            document.getElementById('cat-active').checked    = btnEdit.dataset.active === '1';
            document.getElementById('modal-title-text').textContent = 'Editar Categoría';
            modal.show();
            return;
        }

        var btnDelete = e.target.closest('.btn-delete-category');
        if (btnDelete) {
            var hasSuppliers = btnDelete.dataset.hasSuppliers === '1';
            var count        = btnDelete.dataset.suppliersCount || '0';
            var name         = btnDelete.dataset.name || '';

            document.getElementById('confirm-title').innerHTML =
                '<i class="fas fa-exclamation-triangle me-2 text-warning"></i>Eliminar categoría';
            document.getElementById('confirm-message').textContent =
                '¿Eliminar la categoría «' + name + '»? La acción puede revertirse desde la papelera.';
            document.getElementById('confirm-suppliers-count').textContent = count;

            var warning = document.getElementById('confirm-warning');
            var btnOk   = document.getElementById('btn-confirm-action');

            if (hasSuppliers) {
                warning.classList.remove('d-none');
                btnOk.classList.add('d-none');
                pendingAction = null;
            } else {
                warning.classList.add('d-none');
                btnOk.classList.remove('d-none');
                btnOk.className = 'btn btn-danger';
                btnOk.innerHTML = '<i class="fas fa-trash-alt me-1"></i>Eliminar';
                pendingAction = {
                    url: '<?= route_to('suppliers.categories.delete', 0) ?>'.replace('/0', '/' + btnDelete.dataset.id)
                };
            }
            confirmModal.show();
            return;
        }

        var btnRestore = e.target.closest('.btn-restore-category');
        if (btnRestore) {
            document.getElementById('confirm-title').innerHTML =
                '<i class="fas fa-undo me-2 text-success"></i>Restaurar categoría';
            document.getElementById('confirm-message').textContent =
                '¿Restaurar la categoría «' + (btnRestore.dataset.name || '') + '»?';
            document.getElementById('confirm-warning').classList.add('d-none');

            var btnOk = document.getElementById('btn-confirm-action');
            btnOk.classList.remove('d-none');
            btnOk.className = 'btn btn-success';
            btnOk.innerHTML = '<i class="fas fa-undo me-1"></i>Restaurar';
            pendingAction = {
                url: '<?= route_to('suppliers.categories.restore', 0) ?>'.replace('/0', '/' + btnRestore.dataset.id)
            };
            confirmModal.show();
        }
    });

    document.getElementById('btn-confirm-action').addEventListener('click', function () {
        if (!pendingAction) return;

        var btn   = this;
        var token = document.getElementById('csrf_token').value;
        btn.disabled = true;

        fetch(pendingAction.url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                '<?= csrf_header() ?>': token
            }
        })
        .then(function (r) {
            var nt = r.headers.get('<?= csrf_header() ?>');
            if (nt) document.getElementById('csrf_token').value = nt;
            return r.json();
        })
        .then(function (res) {
            if (res.csrf) document.getElementById('csrf_token').value = res.csrf;
            if (res.success) {
                notifyShow(res.message, 'success');
                confirmModal.hide();
                if (window.categoriesTable) window.categoriesTable.ajax.reload(null, false);
            } else {
                notifyShow(res.message || 'No se pudo completar la acción.', 'danger');
            }
        })
        .catch(function (err) {
            console.error(err);
            notifyShow('Error de conexión con el servidor.', 'danger');
        })
        .finally(function () {
            btn.disabled = false;
            pendingAction = null;
        });
    });
}
</script>
<?php $this->endSection(); ?>
