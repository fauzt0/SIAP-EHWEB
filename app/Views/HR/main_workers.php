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

    <!-- Fila de Estadísticas -->
    <div class="row">
      <!-- Card 1: Total Empleados -->
      <div class="col-12 col-sm-6 col-md-3 d-flex">
        <div class="card flex-fill">
          <div class="card-header pb-0">
            <h5 class="card-title mb-0 mt-1">Total Empleados</h5>
          </div>
          <div class="card-body my-0 pt-0">
            <div class="d-flex align-items-center mb-3 mt-2">
              <div class="flex-grow-1">
                <h3 class="mb-0 fw-light"><?= number_format($response['stats']['total_employees'] ?? 0) ?></h3>
              </div>
              <div class="ms-auto">
                <div class="stat text-primary">
                  <i class="align-middle" data-lucide="users"></i>
                </div>
              </div>
            </div>
            <div class="progress progress-sm shadow-sm mb-1">
              <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
            </div>
            <small class="text-muted">Activos: <?= $response['stats']['total_active'] ?? 0 ?> | Inactivos: <?= $response['stats']['total_inactive'] ?? 0 ?></small>
          </div>
        </div>
      </div>

      <!-- Card 2: Nuevos Ingresos (30 días) -->
      <div class="col-12 col-sm-6 col-md-3 d-flex">
        <div class="card flex-fill">
          <div class="card-header pb-0">
            <h5 class="card-title mb-0 mt-1">Nuevos (30d)</h5>
          </div>
          <div class="card-body my-0 pt-0">
            <div class="d-flex align-items-center mb-3 mt-2">
              <div class="flex-grow-1">
                <h3 class="mb-0 fw-light"><?= number_format($response['stats']['new_hires'] ?? 0) ?></h3>
              </div>
              <div class="ms-auto">
                <div class="stat text-success">
                  <i class="align-middle" data-lucide="user-plus"></i>
                </div>
              </div>
            </div>
            <div class="progress progress-sm shadow-sm mb-1">
              <?php $hiresPerc = ($response['stats']['total_active'] > 0) ? ($response['stats']['new_hires'] / $response['stats']['total_active']) * 100 : 0; ?>
              <div class="progress-bar bg-success" role="progressbar" style="width: <?= min(100, max(5, $hiresPerc)) ?>%"></div>
            </div>
            <small class="text-muted">Nuevos ingresos últimos 30 días</small>
          </div>
        </div>
      </div>

      <!-- Card 3: Nómina Mensual -->
      <div class="col-12 col-sm-6 col-md-3 d-flex">
        <div class="card flex-fill">
          <div class="card-header pb-0">
            <h5 class="card-title mb-0 mt-1">Nómina Mensual</h5>
          </div>
          <div class="card-body my-0 pt-0">
            <div class="d-flex align-items-center mb-3 mt-2">
              <div class="flex-grow-1">
                <h3 class="mb-0 fw-light">$<?= number_format($response['stats']['total_payroll'] ?? 0, 2) ?></h3>
              </div>
              <div class="ms-auto">
                <div class="stat text-info">
                  <i class="align-middle" data-lucide="dollar-sign"></i>
                </div>
              </div>
            </div>
            <div class="progress progress-sm shadow-sm mb-1">
              <div class="progress-bar bg-info" role="progressbar" style="width: 100%"></div>
            </div>
            <small class="text-muted">Salarios base mensuales</small>
          </div>
        </div>
      </div>

      <!-- Card 4: Incidencias Pendientes -->
      <div class="col-12 col-sm-6 col-md-3 d-flex">
        <div class="card flex-fill">
          <div class="card-header pb-0">
            <h5 class="card-title mb-0 mt-1">Incidencias</h5>
          </div>
          <div class="card-body my-0 pt-0">
            <div class="d-flex align-items-center mb-3 mt-2">
              <div class="flex-grow-1">
                <h3 class="mb-0 fw-light"><?= number_format($response['stats']['pending_incid'] ?? 0) ?></h3>
              </div>
              <div class="ms-auto">
                <div class="stat text-danger">
                  <i class="align-middle" data-lucide="alert-triangle"></i>
                </div>
              </div>
            </div>
            <div class="progress progress-sm shadow-sm mb-1">
              <div class="progress-bar bg-danger" role="progressbar" style="width: <?= ($response['stats']['pending_incid'] > 0) ? '100' : '0' ?>%"></div>
            </div>
            <small class="text-muted">Pendientes por revisar</small>
          </div>
        </div>
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
                    <button type="button" class="btn btn-light btn-lg me-2"><i data-lucide="download"></i>
                      Export</button>
                    <a href="<?= route_to('hr.contracts.templates.index') ?>" class="btn btn-light btn-lg me-2">
                      <i data-lucide="file-text"></i> Plantillas
                    </a>
                    <button type="button" class="btn btn-warning btn-lg me-2 position-relative" onclick="openGlobalVacationsModal()">
                      <i data-lucide="umbrella"></i> Vacaciones
                      <?php if (!empty($response['stats']['pending_vac'])): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                          <?= $response['stats']['pending_vac'] ?>
                          <span class="visually-hidden">solicitudes pendientes</span>
                        </span>
                      <?php endif; ?>
                    </button>
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
          d.location_id = $('#filter-location').val();
          d.status = $('#filter-status').val();
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

  // Modal Global de Vacaciones
  function openGlobalVacationsModal() {
      const modal = new bootstrap.Modal(document.getElementById('modalGlobalVacations'));
      modal.show();
      loadGlobalVacations();
  }

  function loadGlobalVacations() {
      const container = document.getElementById('global-vacations-tbody');
      container.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary mb-2"></div><br>Cargando solicitudes...</td></tr>';

      fetch('<?= route_to('hr.worker.vacations.global') ?>', {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(r => r.json())
      .then(data => {
          if (!data.success) {
              container.innerHTML = `<tr><td colspan="6" class="text-danger text-center">${data.message || 'Error al cargar.'}</td></tr>`;
              return;
          }

          const vacations = data.response;
          if (!vacations || vacations.length === 0) {
              container.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay solicitudes registradas.</td></tr>';
              return;
          }

          let html = '';
          vacations.forEach(v => {
              const dateReq = new Date(v.created_at).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute:'2-digit' });
              const startObj = new Date(v.start_date);
              const endObj = new Date(v.end_date);
              const period = `${startObj.toLocaleDateString('es-MX')} al ${endObj.toLocaleDateString('es-MX')}`;
              
              let statusBadge = '';
              let actions = '';
              
              const today = new Date();
              today.setHours(0,0,0,0);
              const canDelete = startObj > today;

              if (v.status === 'pendiente') {
                  statusBadge = '<span class="badge bg-warning text-dark">Pendiente</span>';
                  actions = `
                      <button class="btn btn-sm btn-success me-1" onclick="updateGlobalVacationStatus(${v.id}, 'aprobado')" title="Aprobar"><i class="fas fa-check"></i></button>
                      <button class="btn btn-sm btn-danger me-1" onclick="updateGlobalVacationStatus(${v.id}, 'rechazado')" title="Rechazar"><i class="fas fa-times"></i></button>
                  `;
              } else if (v.status === 'aprobado') {
                  statusBadge = '<span class="badge bg-success">Aprobada</span>';
              } else {
                  statusBadge = '<span class="badge bg-danger">Rechazada</span>';
              }

              if (canDelete) {
                  actions += `<button class="btn btn-sm btn-outline-danger" onclick="deleteGlobalVacation(${v.id})" title="Eliminar definitivamente"><i class="fas fa-trash-alt"></i></button>`;
              }

              html += `
                  <tr data-status="${v.status}">
                      <td><div class="fw-bold">${v.first_name} ${v.last_name}</div><small class="text-muted">#${v.employee_number}</small></td>
                      <td>${dateReq}</td>
                      <td>${period}</td>
                      <td>${v.total_days}</td>
                      <td>${v.notes || '-'}</td>
                      <td>${statusBadge} <div class="mt-1">${actions}</div></td>
                  </tr>
              `;
          });
          container.innerHTML = html;
          filterGlobalVacations('pendiente'); // Por defecto mostrar pendientes
      })
      .catch(err => {
          console.error(err);
          container.innerHTML = '<tr><td colspan="6" class="text-danger text-center">Error de conexión.</td></tr>';
      });
  }

  function filterGlobalVacations(status) {
      const rows = document.querySelectorAll('#global-vacations-tbody tr[data-status]');
      rows.forEach(row => {
          if (status === 'todas' || row.dataset.status === status) {
              row.style.display = '';
          } else {
              row.style.display = 'none';
          }
      });
      // Actualizar estilo de los botones de filtro
      document.querySelectorAll('.btn-filter-vac').forEach(btn => {
          if(btn.dataset.status === status) {
              btn.classList.add('active', 'fw-bold');
              btn.classList.remove('text-muted');
          } else {
              btn.classList.remove('active', 'fw-bold');
              btn.classList.add('text-muted');
          }
      });
  }

  function updateGlobalVacationStatus(id, newStatus) {
      if(!confirm(`¿Deseas marcar esta solicitud como ${newStatus.toUpperCase()}?`)) return;

      const csrfInput = document.getElementById('csrf_token');
      const formData = new FormData();
      formData.append('status', newStatus);
      if (csrfInput) formData.append('<?= csrf_token() ?>', csrfInput.value);

      fetch('<?= route_to('hr.worker.vacations.update_status', 0) ?>'.replace('/0', '/' + id), {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: formData
      })
      .then(r => {
          const c = r.headers.get('<?= csrf_header() ?>');
          if (c && csrfInput) csrfInput.value = c;
          return r.json();
      })
      .then(data => {
          if (data.success) {
              if (typeof notifyShow === 'function') notifyShow(data.message, 'success');
              loadGlobalVacations();
              // Si la tabla principal de empleados debe actualizarse por algún contador
              if (window.workersDataTable) window.workersDataTable.ajax.reload(null, false);
          } else {
              if (typeof notifyShow === 'function') notifyShow(data.message || 'Error', 'danger');
          }
      })
      .catch(err => console.error(err));
  }
  function deleteGlobalVacation(id) {
      if(!confirm('¿Estás seguro de eliminar permanentemente esta solicitud? Los días serán devueltos al balance del trabajador.')) return;

      const csrfInput = document.getElementById('csrf_token');
      const formData = new FormData();
      if (csrfInput) formData.append('<?= csrf_token() ?>', csrfInput.value);

      fetch('<?= route_to('hr.worker.vacations.delete', 0) ?>'.replace('/0', '/' + id), {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: formData
      })
      .then(r => {
          const c = r.headers.get('<?= csrf_header() ?>');
          if (c && csrfInput) csrfInput.value = c;
          return r.json();
      })
      .then(data => {
          if (data.success) {
              if (typeof notifyShow === 'function') notifyShow(data.message, 'success');
              loadGlobalVacations();
              if (window.workersDataTable) window.workersDataTable.ajax.reload(null, false);
          } else {
              if (typeof notifyShow === 'function') notifyShow(data.message || 'Error', 'danger');
          }
      })
      .catch(err => console.error(err));
  }
</script>

<!-- Modal Global Vacaciones -->
<div class="modal fade" id="modalGlobalVacations" tabindex="-1" aria-labelledby="modalGlobalVacationsLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-top: 5px solid #fd7e14;"> <!-- Color warning de bootstrap -->
      <div class="modal-header">
        <h5 class="modal-title" id="modalGlobalVacationsLabel"><i class="fas fa-umbrella-beach me-2 text-warning"></i> Solicitudes de Vacaciones</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-0">
        <div class="bg-light px-3 py-2 border-bottom d-flex gap-3">
            <button class="btn btn-link btn-filter-vac text-decoration-none px-0" data-status="pendiente" onclick="filterGlobalVacations('pendiente')"><i class="fas fa-clock me-1"></i>Pendientes</button>
            <button class="btn btn-link btn-filter-vac text-decoration-none px-0" data-status="aprobado" onclick="filterGlobalVacations('aprobado')"><i class="fas fa-check me-1 text-success"></i>Aprobadas</button>
            <button class="btn btn-link btn-filter-vac text-decoration-none px-0" data-status="rechazado" onclick="filterGlobalVacations('rechazado')"><i class="fas fa-times me-1 text-danger"></i>Rechazadas</button>
            <button class="btn btn-link btn-filter-vac text-decoration-none px-0" data-status="todas" onclick="filterGlobalVacations('todas')"><i class="fas fa-list me-1"></i>Todas</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 text-sm">
                <thead class="table-light">
                    <tr>
                        <th>Empleado</th>
                        <th>Fecha Solicitud</th>
                        <th>Período Solicitado</th>
                        <th>Días</th>
                        <th>Observaciones</th>
                        <th style="width: 150px;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="global-vacations-tbody">
                    <!-- JS -->
                </tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<?php $this->endSection(); ?>