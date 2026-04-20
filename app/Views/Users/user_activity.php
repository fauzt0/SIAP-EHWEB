<?= $this->extend('Layouts/user_loggedin_layout') ?>

<?= $this->section('title') ?>
  Actividad de <?= esc($response['username']) ?>
<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
  <style>
    /* Forzar alineación a la derecha de la paginación */
    div.dataTables_wrapper div.dataTables_paginate {
      display: flex;
      justify-content: flex-end;
    }
    div.dataTables_wrapper div.dataTables_paginate ul.pagination {
      margin: 2px 0;
      white-space: nowrap;
      justify-content: flex-end;
    }
  </style>
<?= $this->endSection() ?>

<?= $this->section('main') ?>

<main class="content">
  <div class="container-fluid p-0">
    <!-- Breadcrumb y título -->
    <div class="row mb-2 mb-xl-3">
      <div class="col-auto d-none d-sm-block">
        <h3><strong>Actividad</strong> <?= esc($response['first_name'] . ' ' . $response['last_name']) ?></h3>
      </div>
      <div class="col-auto ms-auto text-end mt-n1">
        <nav aria-label="breadcrumb">
          <?= $breadcrumb ?>
        </nav>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header pb-0 border-bottom">
            <h5 class="card-title mb-0">Listado Completo de Actividad</h5>
            
            <div class="row mt-3 mb-3">
              <!-- Filtro de Fechas con daterangepicker -->
              <div class="col-lg-4 col-md-5 mb-2">
                <label class="form-label text-muted mb-1">Rango de Fechas</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="align-middle" data-lucide="calendar"></i></span>
                  <input type="text" class="form-control" name="daterange" id="filter-daterange" placeholder="Selecciona fechas..." />
                </div>
                <input type="hidden" id="start_date">
                <input type="hidden" id="end_date">
              </div>

              <!-- Filtro de Tipo/Módulo -->
              <div class="col-lg-3 col-md-4 mb-2">
                <label class="form-label text-muted mb-1">Acción / Tipo</label>
                <select class="form-select" id="filter-type">
                  <option value="">Todas las acciones</option>
                  <?php if(!empty($activityTypes)): ?>
                    <?php foreach($activityTypes as $type): ?>
                      <option value="<?= esc($type) ?>"><?= esc(strtoupper($type)) ?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>

              <div class="col-lg-5 col-md-3 mb-2 d-flex align-items-end justify-content-end">
                  <button type="button" class="btn btn-primary" id="btn-filter-activity">
                    <i class="align-middle me-1" data-lucide="filter"></i> Filtrar
                  </button>
                  <button type="button" class="btn btn-secondary ms-2" id="btn-reset-filters">
                    <i class="align-middle me-1" data-lucide="refresh-cw"></i> Limpiar
                  </button>
              </div>
            </div>
          </div>
          
          <div class="card-body">
            <table id="datatables-activity" class="table table-striped table-hover" style="width:100%">
              <thead>
                <tr>
                  <th style="width: 5%">#</th>
                  <th style="width: 15%">Acción</th>
                  <th style="width: 50%">Descripción</th>
                  <th style="width: 15%">Dirección IP</th>
                  <th style="width: 15%">Fecha y Hora</th>
                </tr>
              </thead>
              <tbody>
                <!-- Datos cargados via AJAX -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?= $this->endSection() ?>

<?= $this->section('pageFooterScripts') ?>
<script>
  $(document).ready(function() {
    
    // 1. Inicializar DateRangePicker
    let daterangepicker = $('input[name="daterange"]').daterangepicker({
      autoUpdateInput: false,
      locale: {
        format: 'YYYY-MM-DD',
        cancelLabel: 'Limpiar',
        applyLabel: 'Aplicar',
        customRangeLabel: 'Personalizado',
        daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi','Sa'],
        monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
        firstDay: 1
      }
    });

    $('input[name="daterange"]').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        $('#start_date').val(picker.startDate.format('YYYY-MM-DD'));
        $('#end_date').val(picker.endDate.format('YYYY-MM-DD'));
    });

    $('input[name="daterange"]').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        $('#start_date').val('');
        $('#end_date').val('');
    });

    // 2. Inicializar DataTables
    if ($.fn.dataTable.isDataTable('#datatables-activity')) return;
    var tableActivity = $("#datatables-activity").DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      order: [[4, "desc"]], // Ordenar por fecha_y_hora por defecto
      dom: "<'row'<'col-sm-12 col-md-6'><'col-sm-12 col-md-6 text-end'l>>" +
           "<'row'<'col-sm-12'tr>>" +
           "<'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i><'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
      ajax: {
        url: '<?= route_to('user.activity_ajax', $response['userId']) ?>',
        type: 'POST',
        data: function (d) {
          d.<?= csrf_token() ?> = '<?= csrf_hash() ?>'; // Token de seguridad
          // Adjuntamos valores de filtros extendidos
          d.type = $('#filter-type').val();
          d.start_date = $('#start_date').val();
          d.end_date = $('#end_date').val();
        }
      },
      columnDefs: [
        { "orderable": false, "targets": [0] },
      ],
      language: {
        "url": "https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json"
      }
    });

    // 3. Botones de Filtrar y Limpiar
    $('#btn-filter-activity').on('click', function() {
      tableActivity.draw();
    });

    $('#btn-reset-filters').on('click', function() {
      // Limpiar picker
      $('input[name="daterange"]').val('');
      $('#start_date').val('');
      $('#end_date').val('');
      // Limpiar select
      $('#filter-type').val('');
      // Recargar
      tableActivity.draw();
    });

  });
</script>
<?= $this->endSection() ?>
