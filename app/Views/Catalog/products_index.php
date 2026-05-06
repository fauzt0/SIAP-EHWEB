<?php $this->extend($layout); ?>

<?php $this->section('title'); echo esc($pageTitle); $this->endSection(); ?>

<?php $this->section('main') ?>
{{-- Token CSRF global — Sección 2 DOCUMENTACION_TECNICA.md --}}
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

<main class="content">
    <div class="container-fluid p-0">

        {{-- Encabezado + Breadcrumb --}}
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

        {{-- Cards de Estadísticas --}}
        <div class="row g-3 mb-4">
            <?php
            $cards = [
                ['label' => 'Total Productos', 'key' => 'total',    'color' => 'primary',   'icon' => 'fa-cubes'],
                ['label' => 'Servicios',        'key' => 'services', 'color' => 'info',      'icon' => 'fa-cloud'],
                ['label' => 'Físicos',          'key' => 'physical', 'color' => 'warning',   'icon' => 'fa-box'],
                ['label' => 'Digitales',        'key' => 'digital',  'color' => 'secondary', 'icon' => 'fa-key'],
            ];
            foreach ($cards as $card): ?>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="flex-shrink-0 rounded-3 p-3 bg-<?= $card['color'] ?> bg-opacity-10">
                            <i class="fas <?= $card['icon'] ?> fa-lg text-<?= $card['color'] ?>"></i>
                        </div>
                        <div>
                            <div class="h4 mb-0 fw-bold"><?= number_format($response['stats'][$card['key']] ?? 0) ?></div>
                            <small class="text-muted"><?= $card['label'] ?></small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        {{-- Tabla principal --}}
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-boxes me-2 text-primary"></i>Catálogo de Productos y Servicios
                                </h5>
                            </div>
                            <div class="col-auto">
                                <a href="<?= route_to('catalog.products.create') ?>" class="btn btn-primary shadow-sm">
                                    <i class="fas fa-plus me-1"></i> Nuevo Producto
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">

                        {{-- Filtros --}}
                        <div class="row g-2 mb-4">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control bg-light border-start-0"
                                           id="product-search" placeholder="Buscar por nombre, SKU...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" id="filter-type">
                                    <option value="">Todos los tipos</option>
                                    <option value="service">Servicio</option>
                                    <option value="physical">Físico</option>
                                    <option value="digital">Digital</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="filter-category">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($response['categories'] as $cat): ?>
                                    <option value="<?= $cat->id ?>"><?= esc($cat->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" id="filter-active">
                                    <option value="1">Activos</option>
                                    <option value="0">Inactivos</option>
                                    <option value="">Todos</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button class="btn btn-outline-secondary w-100" id="btn-clear-filters" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        {{-- DataTable --}}
                        <div class="table-responsive">
                            <table id="datatables-products" class="table table-striped align-middle w-100">
                                <thead class="table-light text-uppercase small fw-bold">
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

    </div>
</main>
<?php $this->endSection() ?>

<?php $this->section('pageFooterScripts'); ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    initProductsTable();
});

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

    // Filtros de selección
    ['filter-type', 'filter-category', 'filter-active'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => {
            if (window.productsTable) window.productsTable.draw();
        });
    });

    // Limpiar filtros
    document.getElementById('btn-clear-filters').addEventListener('click', function () {
        document.getElementById('filter-type').value     = '';
        document.getElementById('filter-category').value = '';
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
});
</script>
<?php $this->endSection(); ?>
