<?php $this->extend($layout); ?>

<?php $this->section('title');
echo $pageTitle;
$this->endSection(); ?>

<?php $this->section('pageStyles'); ?>
<?php $this->endSection(); ?>
<?php $this->section('pageHeaderScripts'); ?>
<?php $this->endSection(); ?>

<?php $this->section('main') ?>
<!-- Token CSRF global para peticiones AJAX de DataTables (mismo patrón que user_form_modal.php) -->
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

            <div class="row mb-3">
              <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <button type="button" class="btn btn-light btn-lg me-2"><i data-lucide="download"></i> Export</button>
                    <a href="<?= route_to('hr.contracts.templates.index') ?>" class="btn btn-light btn-lg me-2">
                      <i data-lucide="file-text"></i> Plantillas
                    </a>
                    <?php if (auth()->user()->can('hr.create')): ?>
                      <a href="<?= route_to('hr.worker.new') ?>" class="btn btn-primary btn-lg" id="btn-nuevo-ingreso">
                        <i data-lucide="user-plus"></i> Nuevo Ingreso
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>

            <!-- Fila de filtros -->
            <div class="row mb-3">
              <div class="col-md-4 mb-2 mb-md-0">
                <div class="input-group input-group-search">
                  <input type="text" class="form-control" id="hr-workers-search"
                    placeholder="Buscar por nombre, CURP, RFC, No. Empleado…">
                  <button class="btn" type="button">
                    <i class="align-middle" data-lucide="search"></i>
                  </button>
                </div>
              </div>
              <div class="col-md-8 mb-2 mb-md-0">
                <div class="d-flex gap-2 flex-wrap align-items-center">

                  <!-- Filtro por Departamento -->
                  <select class="form-select" id="filter-department" style="max-width: 180px;">
                    <option value="">Todos los Dptos.</option>
                    <?php foreach ($response['departments'] ?? [] as $dept): ?>
                      <option value="<?= $dept->id ?>"><?= esc($dept->name) ?></option>
                    <?php endforeach; ?>
                  </select>

                  <!-- Filtro por Sucursal -->
                  <select class="form-select" id="filter-location" style="max-width: 180px;">
                    <option value="">Todas las Sucursales</option>
                    <?php foreach ($response['locations'] ?? [] as $loc): ?>
                      <option value="<?= $loc->id ?>"><?= esc($loc->name) ?></option>
                    <?php endforeach; ?>
                  </select>

                  <!-- Filtro por Estatus -->
                  <select class="form-select" id="filter-status" style="max-width: 150px;">
                    <option value="active">Activos</option>
                    <option value="terminated">Bajas</option>
                    <option value="on_leave">En Permiso</option>
                    <option value="suspended">Suspendidos</option>
                    <option value="">Todos</option>
                  </select>

                  <!-- Limpiar filtros -->
                  <button class="btn btn-outline-secondary" id="btn-clear-filters" title="Limpiar filtros">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- Tabla DataTable -->
            <table id="datatables-workers" class="table table-striped w-100">
              <thead>
                <tr>
                  <th class="text-start" style="width: 50px;">#</th>
                  <th>Trabajador</th>
                  <th>Puesto</th>
                  <th>Departamento</th>
                  <th>Sucursal</th>
                  <th>Ingreso</th>
                  <th>Estatus</th>
                  <th style="width: 120px;">Acciones</th>
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
<?php $this->endSection() ?>

<?php $this->section('pageFooterScripts'); ?>
<?= $this->include('HR/Partials/worker_offcanvas') ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
  inicializarWorkersDataTable();
});

function inicializarWorkersDataTable() {
  if ($.fn.dataTable.isDataTable('#datatables-workers')) return;

    window.workersDataTable = $('#datatables-workers').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    dom: "<'row'<'col-sm-12 col-md-6'><'col-sm-12 col-md-6 text-end'l>>" +
         "<'row'<'col-sm-12'tr>>" +
         "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center'p>>",
    ajax: {
      url: '<?= route_to('hr.workers_ajax') ?>',
      type: 'POST',
      data: function (d) {
        d['<?= csrf_token() ?>'] = document.getElementById('csrf_token') ? document.getElementById('csrf_token').value : '';
        d.department_id = $('#filter-department').val();
        d.location_id   = $('#filter-location').val();
        d.status        = $('#filter-status').val();
      }
    },
    columns: [
      { data: 0, orderable: false, searchable: false }, // Avatar
      { data: 1 },                                       // Nombre + Nº Empleado
      { data: 2 },                                       // Puesto
      { data: 3 },                                       // Departamento
      { data: 4 },                                       // Sucursal
      { data: 5 },                                       // Fecha Ingreso
      { data: 6, orderable: false, searchable: false },  // Estatus Badge
      { data: 7, orderable: false, searchable: false }   // Acciones
    ],
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json'
    },
    pageLength: 10, // Cambiado a 10 para coincidir con el dropdown por defecto
    order: [[1, 'asc']]
  });

  // Actualizar CSRF tras cada llamada AJAX de DataTables
  $('#datatables-workers').on('xhr.dt', function (e, settings, json, xhr) {
    if (xhr && xhr.getResponseHeader('<?= csrf_header() ?>')) {
      const newCsrfHash = xhr.getResponseHeader('<?= csrf_header() ?>');
      if (document.getElementById('csrf_token')) {
        document.getElementById('csrf_token').value = newCsrfHash;
      }
    }
  });

  // Búsqueda con debounce
  let searchTimeout;
  $('#hr-workers-search').on('keyup', function () {
    const val = this.value;
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function () {
      if (window.workersDataTable) window.workersDataTable.search(val).draw();
    }, 500);
  });

  // Cambios en filtros de selección
  $('#filter-department, #filter-location, #filter-status').on('change', function () {
    if (window.workersDataTable) window.workersDataTable.draw();
  });

  // Limpiar filtros
  document.getElementById('btn-clear-filters').addEventListener('click', function () {
    $('#filter-department').val('');
    $('#filter-location').val('');
    $('#filter-status').val('active');
    $('#hr-workers-search').val('');
    if (window.workersDataTable) window.workersDataTable.search('').draw();
  });
}

// Delegación de eventos para botones generados por DataTables
document.addEventListener('click', function (e) {

  // Botón VER → abrir Offcanvas de perfil
  const btnView = e.target.closest('.btn-view-worker');
  if (btnView) {
    loadWorkerOffcanvas(btnView.dataset.userId);
    return;
  }

  // Botón ELIMINAR → soft delete con confirmación
  const btnDelete = e.target.closest('.btn-delete-worker');
  if (btnDelete) {
    const userId = btnDelete.dataset.userId;
    if (!confirm('¿Está seguro de eliminar a este trabajador? Esta acción puede ser revertida.')) return;

    fetch('<?= base_url('nat/hr/worker/delete/') ?>' + userId, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: '<?= csrf_token() ?>=' + (document.getElementById('csrf_token') ? document.getElementById('csrf_token').value : '')
    })
    .then(r => {
      const newCsrf = r.headers.get('<?= csrf_header() ?>');
      if (newCsrf && document.getElementById('csrf_token')) document.getElementById('csrf_token').value = newCsrf;
      return r.json();
    })
    .then(data => {
      if (data.success) {
        notifyShow(data.message, 'success');
        if (window.workersDataTable) window.workersDataTable.ajax.reload(null, false);
      } else {
        notifyShow(data.message || 'Error al eliminar', 'danger');
      }
    })
    .catch(err => {
      console.error(err);
      notifyShow('Error de conexión con el servidor', 'danger');
    });
    return;
  }
});

// Re-inicializar iconos Lucide tras cada redraw de DataTables
$('#datatables-workers').on('draw.dt', function () {
  if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>
<?php $this->endSection(); ?>
