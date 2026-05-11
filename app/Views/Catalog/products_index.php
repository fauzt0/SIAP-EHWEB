<?php $this->extend($layout); ?>

<?php $this->section('title'); echo esc($pageTitle); $this->endSection(); ?>

<?php $this->section('main') ?>
<?php /* Token CSRF global — Sección 2 DOCUMENTACION_TECNICA.md */ ?>
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

<main class="content">
    <div class="container-fluid p-0">

        <?php /* Encabezado + Breadcrumb */ ?>
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

        <?php /* Cards de Estadísticas — Estilo Unificado con HR/Usuarios */ ?>
        <div class="row mb-3">
            <?php
            $cards = [
                ['label' => 'Total Productos', 'key' => 'total',    'color' => 'primary',   'icon' => 'fa-cubes'],
                ['label' => 'Servicios',        'key' => 'services', 'color' => 'info',      'icon' => 'fa-cloud'],
                ['label' => 'Físicos',          'key' => 'physical', 'color' => 'warning',   'icon' => 'fa-box'],
                ['label' => 'Digitales',        'key' => 'digital',  'color' => 'secondary', 'icon' => 'fa-key'],
            ];
            $total = $response['stats']['total'] ?? 0;
            foreach ($cards as $card): 
                $val = $response['stats'][$card['key']] ?? 0;
                // Si es la tarjeta de total, va al 100%. Si no, se calcula la proporción.
                $percent = 100;
                if ($card['key'] !== 'total' && $total > 0) {
                    $percent = round(($val / $total) * 100);
                }
            ?>
            <div class="col-12 col-sm-6 col-md-3 d-flex">
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
                            <div class="progress-bar bg-<?= $card['color'] ?>" role="progressbar" style="width: <?= $percent ?>%"></div>
                        </div>
                        <small class="text-muted">Total registrados</small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
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
                                        <a href="<?= route_to('catalog.products.create') ?>" class="btn btn-primary btn-lg">
                                            <i class="fas fa-plus"></i> Nuevo Producto
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fila de filtros -->
                        <div class="row mb-3">
                            <div class="col-md-4 mb-2 mb-md-0">
                                <div class="input-group input-group-search">
                                    <input type="text" class="form-control" id="product-search" placeholder="Buscar por nombre, SKU...">
                                    <button class="btn" type="button">
                                        <i class="fas fa-search align-middle"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-8 mb-2 mb-md-0">
                                <div class="d-flex gap-2 flex-wrap align-items-center justify-content-md-end">
                                    <select class="form-select" id="filter-type" style="max-width: 180px;">
                                        <option value="">Todos los tipos</option>
                                        <option value="service">Servicio</option>
                                        <option value="physical">Físico</option>
                                        <option value="digital">Digital</option>
                                    </select>

                                    <select class="form-select" id="filter-category" style="max-width: 180px;">
                                        <option value="">Todas las categorías</option>
                                        <?php foreach ($response['categories'] as $cat): ?>
                                        <option value="<?= $cat->id ?>" data-icon="<?= esc($cat->icon ?? 'fas fa-folder') ?>"><?= esc($cat->display_name ?? $cat->name) ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <select class="form-select" id="filter-active" style="max-width: 150px;">
                                        <option value="1">Activos</option>
                                        <option value="0">Inactivos</option>
                                        <option value="deleted">Eliminados (Papelera)</option>
                                        <option value="">Todos</option>
                                    </select>

                                    <button class="btn btn-outline-secondary" id="btn-clear-filters" title="Limpiar filtros">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla DataTable -->
                        <table id="datatables-products" class="table table-striped w-100">
                            <thead>
                                <tr>
                                    <th style="width:60px;">Imagen</th>
                                    <th>Producto / SKU</th>
                                    <th>Categoría</th>
                                    <th>Tipo</th>
                                    <th>Planes</th>
                                    <th>Estatus</th>
                                    <th style="width:120px;">Acciones</th>
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

<?= $this->include('Catalog/Partials/offcanvas_plans') ?>
<?php $this->endSection() ?>

<?php $this->section('pageFooterScripts'); ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    initProductsTable();
    initFiltersSelect2();
});

function initFiltersSelect2() {
    function formatCategory(state) {
        if (!state.id) return state.text;
        const icon = state.element.getAttribute('data-icon') || 'fas fa-folder';
        return $('<span><i class="' + icon + ' text-muted me-2"></i>' + state.text + '</span>');
    }

    $('#filter-category').select2({
        theme: 'bootstrap-5',
        placeholder: 'Todas las categorías',
        allowClear: true,
        templateResult: formatCategory,
        templateSelection: formatCategory,
        dropdownParent: $('#filter-category').parent()
    });

    // Actualizar DataTable cuando se usa Select2
    $('#filter-category').on('change', function() {
        if (window.productsTable) window.productsTable.draw();
    });
}

function initProductsTable() {
    // Guard: Sección 6 DOCUMENTACION_TECNICA.md
    if ($.fn.dataTable.isDataTable('#datatables-products')) return;

    window.productsTable = $('#datatables-products').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        dom: "<'row'<'col-sm-12 col-md-6'><'col-sm-12 col-md-6 text-end'l>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end'p>>",
        ajax: {
            url: '<?= route_to('catalog.products_ajax') ?>',
            type: 'POST',
            data: function (d) {
                // CSRF — Sección 2 DOCUMENTACION_TECNICA.md
                d['<?= csrf_token() ?>']  = document.getElementById('csrf_token').value;
                d.filter_type             = document.getElementById('filter-type').value;
                d.filter_category         = document.getElementById('filter-category').value;
                d.filter_active           = document.getElementById('filter-active').value;
            }
        },
        columns: [
            { data: 0, orderable: false, searchable: false }, // Imagen
            { data: 1 },                                       // Nombre / SKU
            { data: 2 },                                       // Categoría
            { data: 3 },                                       // Tipo
            { data: 4, orderable: false, searchable: false },  // Planes
            { data: 5, orderable: false, searchable: false },  // Estatus
            { data: 6, orderable: false, searchable: false }   // Acciones
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json' },
        pageLength: 15,
        order: [[1, 'asc']]
    });

    // Renovar CSRF tras cada petición — Sección 2 DOCUMENTACION_TECNICA.md
    $('#datatables-products').on('xhr.dt', function (e, settings, json, xhr) {
        const newToken = xhr && xhr.getResponseHeader('<?= csrf_header() ?>');
        if (newToken) document.getElementById('csrf_token').value = newToken;
    });

    // Búsqueda con debounce
    let searchTimeout;
    document.getElementById('product-search').addEventListener('keyup', function () {
        const val = this.value;
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (window.productsTable) window.productsTable.search(val).draw();
        }, 400);
    });

    // Filtros de selección (type y active)
    ['filter-type', 'filter-active'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => {
            if (window.productsTable) window.productsTable.draw();
        });
    });

    // Limpiar filtros
    document.getElementById('btn-clear-filters').addEventListener('click', function () {
        document.getElementById('filter-type').value     = '';
        $('#filter-category').val('').trigger('change');
        document.getElementById('filter-active').value   = '1';
        document.getElementById('product-search').value  = '';
        if (window.productsTable) window.productsTable.search('').draw();
    });
}

// Delegación de eventos — botones generados dinámicamente por DataTables
document.addEventListener('click', function (e) {
    // Eliminar producto
    const btnDelete = e.target.closest('.btn-delete-product');
    if (btnDelete) {
        if (!confirm('¿Eliminar este producto? Esta acción puede revertirse.')) return;

        const formData = new FormData();
        formData.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);

        fetch('<?= base_url('nat/catalog/products/delete/') ?>' + btnDelete.dataset.id, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(r => {
            const newToken = r.headers.get('<?= csrf_header() ?>');
            if (newToken) document.getElementById('csrf_token').value = newToken;
            return r.json();
        })
        .then(data => {
            if (data.success) {
                notifyShow(data.message, 'success');
                if (window.productsTable) window.productsTable.ajax.reload(null, false);
            } else {
                notifyShow(data.message || 'Error al eliminar', 'danger');
            }
        })
        .catch(() => notifyShow('Error de conexión', 'danger'));
    }

    // Restaurar producto
    const btnRestore = e.target.closest('.btn-restore-product');
    if (btnRestore) {
        if (!confirm('¿Restaurar este producto?')) return;

        const formData = new FormData();
        formData.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);

        fetch('<?= base_url('nat/catalog/products/restore/') ?>' + btnRestore.dataset.id, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(r => {
            const newToken = r.headers.get('<?= csrf_header() ?>');
            if (newToken) document.getElementById('csrf_token').value = newToken;
            return r.json();
        })
        .then(data => {
            if (data.success) {
                notifyShow(data.message, 'success');
                if (window.productsTable) window.productsTable.ajax.reload(null, false);
            } else {
                notifyShow(data.message || 'Error al restaurar', 'danger');
            }
        })
        .catch(() => notifyShow('Error de conexión', 'danger'));
    }

    // Activar/Inactivar producto (Toggle Status)
    const btnToggle = e.target.closest('.btn-toggle-product');
    if (btnToggle) {
        const formData = new FormData();
        formData.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);

        fetch('<?= base_url('nat/catalog/products/toggle_status/') ?>' + btnToggle.dataset.id, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(r => {
            const newToken = r.headers.get('<?= csrf_header() ?>');
            if (newToken) document.getElementById('csrf_token').value = newToken;
            return r.json();
        })
        .then(data => {
            if (data.success) {
                notifyShow(data.message, 'success');
                if (window.productsTable) window.productsTable.ajax.reload(null, false);
            } else {
                notifyShow(data.message || 'Error al cambiar estatus', 'danger');
            }
        })
        .catch(() => notifyShow('Error de conexión', 'danger'));
    }
});
</script>
<?php $this->endSection(); ?>
