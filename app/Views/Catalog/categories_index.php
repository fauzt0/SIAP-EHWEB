<?php $this->extend($layout); ?>

<?php $this->section('title');
echo $pageTitle;
$this->endSection(); ?>

<?php $this->section('main') ?>
<!-- Token CSRF global para peticiones AJAX -->
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

<main class="content">
    <div class="container-fluid p-0">

        <!-- Encabezado + Breadcrumb -->
        <div class="row mb-2 mb-xl-3">
            <div class="col-auto d-none d-sm-block">
                <h1 class="h3 mb-3"><?= esc($pageTitle) ?></h1>
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

                        <!-- Botones superiores -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <button type="button" class="btn btn-light btn-lg me-2"><i class="fas fa-download"></i> Export</button>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-primary btn-lg" id="btn-nueva-categoria">
                                            <i class="fas fa-plus"></i> Nueva Categoría
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fila de filtros -->
                        <div class="row mb-3">
                            <div class="col-md-4 mb-2 mb-md-0">
                                <div class="input-group input-group-search">
                                    <input type="text" class="form-control" id="category-search" placeholder="Buscar categoría...">
                                    <button class="btn" type="button">
                                        <i class="fas fa-search align-middle"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-8 mb-2 mb-md-0">
                                <div class="d-flex gap-2 flex-wrap align-items-center justify-content-md-end">
                                    <select class="form-select" id="filter-active" style="max-width: 150px;">
                                        <option value="1">Activos</option>
                                        <option value="0">Inactivos</option>
                                        <option value="">Todos</option>
                                    </select>
                                    <button class="btn btn-outline-secondary" id="btn-clear-filters" title="Limpiar filtros">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla DataTable -->
                        <table id="datatables-categories" class="table table-striped w-100">
                            <thead>
                                <tr>
                                    <th>Categoría</th>
                                    <th>Slug</th>
                                    <th>Dependencia</th>
                                    <th>Estatus</th>
                                    <th style="width: 100px;">Acciones</th>
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

<!-- Modal de Categoría (Add/Edit) -->
<div class="modal fade" id="modal-category" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title text-white"><i class="fas fa-tag me-2"></i><span id="modal-title-text" class="text-white">Nueva Categoría</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-category">
                <input type="hidden" name="id" id="cat-id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre de la Categoría <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" name="name" id="cat-name" required placeholder="Ej: Hosting Compartido">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Slug (URL)</label>
                        <input type="text" class="form-control bg-light" name="slug" id="cat-slug" placeholder="hosting-compartido">
                        <small class="text-muted">Se genera automáticamente si se deja vacío.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Categoría Superior (Padre)</label>
                        <select class="form-select" name="parent_id" id="cat-parent">
                            <option value="">-- Ninguna (Nivel Principal) --</option>
                            <?php foreach ($response['parent_categories'] as $parent): ?>
                                <option value="<?= $parent->id ?>"><?= esc($parent->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Icono (FontAwesome)</label>
                                <div class="input-group">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="icon-preview">
                                        <i class="fas fa-folder"></i>
                                    </button>
                                    <ul class="dropdown-menu icon-selector-dropdown" style="max-height: 200px; overflow-y: auto; width: 250px;">
                                        <li><div class="p-2 d-flex flex-wrap gap-2 justify-content-center" id="icon-list-container">
                                            <!-- Iconos cargados vía JS -->
                                        </div></li>
                                    </ul>
                                    <input type="text" class="form-control" name="icon" id="cat-icon" value="fas fa-folder" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Estatus</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="active" id="cat-active" checked>
                                    <label class="form-check-label" for="cat-active">Activo</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-bold">Descripción</label>
                        <textarea class="form-control" name="description" id="cat-description" rows="3" placeholder="Descripción breve de la categoría..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary shadow-sm" id="btn-save-category">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $this->endSection() ?>

<?php $this->section('pageFooterScripts'); ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        inicializarCategoriesDataTable();
        setupCategoryActions();
    });

    function inicializarCategoriesDataTable() {
        if ($.fn.dataTable.isDataTable('#datatables-categories')) return;

        window.categoriesDataTable = $('#datatables-categories').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            dom: "<'row'<'col-sm-12'tr>>" +
                 "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center'p>>",
            ajax: {
                url: '<?= route_to('catalog.categories_ajax') ?>',
                type: 'POST',
                data: function(d) {
                    d['<?= csrf_token() ?>'] = document.getElementById('csrf_token').value;
                    d['active'] = document.getElementById('filter-active').value;
                }
            },
            columns: [
                { data: 0 }, // Categoría (Nombre + Icono)
                { data: 1 }, // Slug
                { data: 2 }, // Dependencia (Padre)
                { data: 3 }, // Estatus Badge
                { data: 4, orderable: false, searchable: false } // Acciones
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json'
            },
            pageLength: 10,
            order: [[0, 'asc']]
        });

        // Actualizar CSRF tras cada llamada AJAX
        $('#datatables-categories').on('xhr.dt', function(e, settings, json, xhr) {
            if (xhr && xhr.getResponseHeader('<?= csrf_header() ?>')) {
                document.getElementById('csrf_token').value = xhr.getResponseHeader('<?= csrf_header() ?>');
            }
        });

        // Búsqueda con debounce
        let searchTimeout;
        $('#category-search').on('keyup', function() {
            const val = this.value;
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                if (window.categoriesDataTable) window.categoriesDataTable.search(val).draw();
            }, 400);
        });

        // Eventos de filtros
        $('#filter-active').on('change', function() {
            if (window.categoriesDataTable) window.categoriesDataTable.ajax.reload();
        });

        $('#btn-clear-filters').on('click', function() {
            $('#filter-active').val('1');
            $('#category-search').val('');
            if (window.categoriesDataTable) {
                window.categoriesDataTable.search('');
                window.categoriesDataTable.ajax.reload();
            }
        });
    }

    function setupCategoryActions() {
        const modalEl = document.getElementById('modal-category');
        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('form-category');

        // Lista de iconos comunes de FontAwesome para el selector (Uso General ERP)
        const commonIcons = [
            // General & Folder
            'fas fa-folder', 'fas fa-folder-open', 'fas fa-box', 'fas fa-boxes', 'fas fa-tags', 'fas fa-tag', 'fas fa-bookmark',
            // Comercio & Ventas
            'fas fa-shopping-cart', 'fas fa-shopping-bag', 'fas fa-shopping-basket', 'fas fa-store', 'fas fa-store-alt', 'fas fa-cash-register',
            // Tecnología
            'fas fa-laptop', 'fas fa-mobile-alt', 'fas fa-tablet-alt', 'fas fa-desktop', 'fas fa-server', 'fas fa-cloud', 'fas fa-database', 
            'fas fa-code', 'fas fa-microchip', 'fas fa-wifi', 'fas fa-bolt', 'fas fa-hdd', 'fas fa-print', 'fas fa-mouse',
            // Herramientas & Configuración
            'fas fa-tools', 'fas fa-cogs', 'fas fa-cog', 'fas fa-wrench', 'fas fa-hammer', 'fas fa-screwdriver', 'fas fa-paint-brush', 
            'fas fa-ruler-combined', 'fas fa-briefcase', 'fas fa-archive',
            // Usuarios & Comunicación
            'fas fa-user', 'fas fa-users', 'fas fa-user-tie', 'fas fa-user-graduate', 'fas fa-id-card', 'fas fa-envelope', 'fas fa-phone', 
            'fas fa-headset', 'fas fa-comments', 'fas fa-bell',
            // Finanzas
            'fas fa-dollar-sign', 'fas fa-euro-sign', 'fas fa-credit-card', 'fas fa-wallet', 'fas fa-piggy-bank', 'fas fa-university', 
            'fas fa-balance-scale', 'fas fa-calculator', 'fas fa-percentage', 'fas fa-chart-line', 'fas fa-chart-bar', 'fas fa-chart-pie',
            // Logística & Transporte
            'fas fa-truck', 'fas fa-shipping-fast', 'fas fa-warehouse', 'fas fa-route', 'fas fa-barcode', 'fas fa-qrcode', 'fas fa-map-marker-alt', 
            'fas fa-globe', 'fas fa-car', 'fas fa-bus', 'fas fa-plane', 'fas fa-ship',
            // Oficina & Documentos
            'fas fa-file-alt', 'fas fa-file-invoice', 'fas fa-file-pdf', 'fas fa-file-excel', 'fas fa-copy', 'fas fa-paste', 'fas fa-calendar-alt', 
            'fas fa-clock', 'fas fa-history', 'fas fa-tasks',
            // Salud
            'fas fa-heartbeat', 'fas fa-stethoscope', 'fas fa-first-aid', 'fas fa-pills', 'fas fa-hospital', 'fas fa-user-md',
            // Industria & Educación
            'fas fa-factory', 'fas fa-industry', 'fas fa-flask', 'fas fa-atom', 'fas fa-book', 'fas fa-graduation-cap', 'fas fa-school', 
            'fas fa-lightbulb', 'fas fa-rocket',
            // Hogar & Estilo de Vida
            'fas fa-home', 'fas fa-building', 'fas fa-couch', 'fas fa-bed', 'fas fa-utensils', 'fas fa-coffee', 'fas fa-glass-martini', 
            'fas fa-gift', 'fas fa-heart', 'fas fa-star', 'fas fa-leaf', 'fas fa-fire', 'fas fa-tint', 'fas fa-camera', 'fas fa-video',
            // Otros
            'fas fa-key', 'fas fa-lock', 'fas fa-shield-alt', 'fas fa-search', 'fas fa-info-circle', 'fas fa-check-circle', 
            'fas fa-exclamation-triangle', 'fas fa-question-circle', 'fas fa-trash-alt'
        ];

        const iconContainer = document.getElementById('icon-list-container');
        commonIcons.forEach(icon => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm btn-light border p-2';
            btn.title = icon;
            btn.innerHTML = `<i class="${icon}"></i>`;
            btn.onclick = function() {
                document.getElementById('cat-icon').value = icon;
                document.getElementById('icon-preview').innerHTML = `<i class="${icon}"></i>`;
            };
            iconContainer.appendChild(btn);
        });

        // Botón Nueva Categoría
        document.getElementById('btn-nueva-categoria').addEventListener('click', function() {
            form.reset();
            document.getElementById('cat-id').value = '';
            document.getElementById('modal-title-text').innerText = 'Nueva Categoría';
            document.getElementById('icon-preview').innerHTML = '<i class="fas fa-folder"></i>';
            modal.show();
        });

        // Guardar Categoría
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);
            
            // Ajuste para el checkbox de active
            if(!document.getElementById('cat-active').checked) {
                formData.set('active', '0');
            } else {
                formData.set('active', '1');
            }

            const btnSave = document.getElementById('btn-save-category');
            const originalHtml = btnSave.innerHTML;
            btnSave.disabled = true;
            btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

            fetch('<?= route_to('catalog.categories.save') ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(r => {
                const newCsrf = r.headers.get('<?= csrf_header() ?>');
                if (newCsrf) document.getElementById('csrf_token').value = newCsrf;
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    notifyShow(data.message, 'success');
                    modal.hide();
                    if (window.categoriesDataTable) window.categoriesDataTable.ajax.reload(null, false);
                } else {
                    notifyShow(data.message || 'Error al guardar', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                notifyShow('Error de conexión con el servidor', 'danger');
            })
            .finally(() => {
                btnSave.disabled = false;
                btnSave.innerHTML = originalHtml;
            });
        });

        // Delegación de eventos para Editar y Eliminar
        document.addEventListener('click', function(e) {
            // Editar
            const btnEdit = e.target.closest('.btn-edit-category');
            if (btnEdit) {
                const id = btnEdit.dataset.id;
                fetch('<?= base_url('nat/catalog/categories/get_ajax/') ?>' + id, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const cat = data.response;
                        document.getElementById('cat-id').value = cat.id;
                        document.getElementById('cat-name').value = cat.name;
                        document.getElementById('cat-slug').value = cat.slug;
                        document.getElementById('cat-parent').value = cat.parent_id || '';
                        document.getElementById('cat-icon').value = cat.icon || 'fas fa-folder';
                        document.getElementById('cat-description').value = cat.description || '';
                        document.getElementById('cat-active').checked = (cat.active == 1);
                        document.getElementById('icon-preview').innerHTML = `<i class="${cat.icon || 'fas fa-folder'}"></i>`;
                        
                        document.getElementById('modal-title-text').innerText = 'Editar Categoría';
                        modal.show();
                    } else {
                        notifyShow(data.message, 'danger');
                    }
                });
                return;
            }

            // Eliminar
            const btnDelete = e.target.closest('.btn-delete-category');
            if (btnDelete) {
                const id = btnDelete.dataset.id;
                if (!confirm('¿Estás seguro de eliminar esta categoría?')) return;

                const formData = new FormData();
                formData.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);

                fetch('<?= base_url('nat/catalog/categories/delete/') ?>' + id, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(r => {
                    const newCsrf = r.headers.get('<?= csrf_header() ?>');
                    if (newCsrf) document.getElementById('csrf_token').value = newCsrf;
                    return r.json();
                })
                .then(data => {
                    if (data.success) {
                        notifyShow(data.message, 'success');
                        if (window.categoriesDataTable) window.categoriesDataTable.ajax.reload(null, false);
                    } else {
                        notifyShow(data.message, 'danger');
                    }
                });
                return;
            }

            // Suspender / Restaurar
            const btnToggle = e.target.closest('.btn-toggle-status');
            if (btnToggle) {
                const id = btnToggle.dataset.id;
                const status = btnToggle.dataset.status;
                const actionText = status == '1' ? 'restaurar/activar' : 'suspender/desactivar';
                
                if (!confirm(`¿Estás seguro de ${actionText} esta categoría?`)) return;

                const formData = new FormData();
                formData.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);
                formData.append('status', status);

                fetch('<?= base_url('nat/catalog/categories/toggle_status/') ?>' + id, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(r => {
                    const newCsrf = r.headers.get('<?= csrf_header() ?>');
                    if (newCsrf) document.getElementById('csrf_token').value = newCsrf;
                    return r.json();
                })
                .then(data => {
                    if (data.success) {
                        notifyShow(data.message, 'success');
                        if (window.categoriesDataTable) window.categoriesDataTable.ajax.reload(null, false);
                    } else {
                        notifyShow(data.message, 'danger');
                    }
                });
                return;
            }
        });
    }
</script>
<?php $this->endSection(); ?>
