<?php $this->extend($layout); ?>

<?php $this->section('title'); ?>
<?= esc($headTitle ?? $pageTitle ?? 'Bitácora Global') ?>
<?php $this->endSection(); ?>

<?php $this->section('main'); ?>
<main class="content">
  <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

  <div class="container-fluid p-0">
    <div class="row mb-2 mb-xl-3">
      <div class="col-auto d-none d-sm-block">
        <h1 class="h3 mb-3">
          <i class="fas fa-fw fa-clipboard-list me-2 text-primary"></i><?= esc($pageTitle ?? 'Bitácora Global') ?>
        </h1>
      </div>
      <div class="col-auto ms-auto text-end mt-n1">
        <nav aria-label="breadcrumb">
          <?= $breadcrumb ?? '' ?>
        </nav>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card" id="filtros-card">
          <div class="card-header pb-0 border-bottom">
            <h5 class="card-title mb-0">
              <i class="fas fa-fw fa-filter me-1 text-muted"></i>Filtros de bitácora
            </h5>

            <div class="row mt-3 mb-3 g-2">
              <div class="col-lg-3 col-md-6">
                <label class="form-label text-muted mb-1" for="filter_user">Usuario</label>
                <select class="form-select" id="filter_user" name="filter_user">
                  <option value="">Todos los usuarios</option>
                  <?php foreach (($response['users'] ?? []) as $u): ?>
                    <?php
                      $label = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                      $label = $label !== '' ? $label . ' (' . $u->username . ')' : $u->username;
                    ?>
                    <option value="<?= (int) $u->id ?>"><?= esc($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-lg-3 col-md-6">
                <label class="form-label text-muted mb-1" for="filter_action">Acción</label>
                <input type="text" class="form-control" id="filter_action" name="filter_action"
                       list="activity-types-list" placeholder="Ej. login, update_user…" autocomplete="off">
                <datalist id="activity-types-list">
                  <?php foreach (($response['activityTypes'] ?? []) as $type): ?>
                    <option value="<?= esc($type) ?>"></option>
                  <?php endforeach; ?>
                </datalist>
              </div>

              <div class="col-lg-2 col-md-6">
                <label class="form-label text-muted mb-1" for="filter_date_from">Desde</label>
                <input type="date" class="form-control" id="filter_date_from" name="filter_date_from">
              </div>

              <div class="col-lg-2 col-md-6">
                <label class="form-label text-muted mb-1" for="filter_date_to">Hasta</label>
                <input type="date" class="form-control" id="filter_date_to" name="filter_date_to">
              </div>

              <div class="col-lg-2 col-md-12 d-flex align-items-end gap-2">
                <button type="button" class="btn btn-primary" id="btn-filter-audit">
                  <i class="fas fa-fw fa-filter me-1"></i> Filtrar
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btn-reset-audit" title="Limpiar filtros">
                  <i class="fas fa-fw fa-times"></i>
                </button>
              </div>
            </div>
          </div>

          <div class="card-body">
            <div class="d-flex justify-content-end mb-2">
              <button type="button" class="btn btn-outline-success btn-sm" id="btn-export-csv">
                <i class="fas fa-fw fa-file-csv me-1"></i> Exportar CSV (página)
              </button>
            </div>
            <table id="audit-log-table" class="table table-striped table-hover" style="width:100%">
              <thead>
                <tr>
                  <th style="width: 6%">ID</th>
                  <th style="width: 18%">Usuario</th>
                  <th style="width: 14%">Acción</th>
                  <th style="width: 36%">Descripción</th>
                  <th style="width: 12%">IP</th>
                  <th style="width: 14%">Fecha</th>
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
<?php $this->endSection(); ?>

<?php $this->section('pageFooterScripts'); ?>
<script>
$(document).ready(function () {
  if ($.fn.dataTable.isDataTable('#audit-log-table')) return;

  $('#filter_user').select2({
    theme: 'bootstrap-5',
    placeholder: 'Todos los usuarios',
    allowClear: true,
    width: '100%',
    dropdownParent: $('#filtros-card')
  });

  window.auditLogTable = $('#audit-log-table').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    order: [[5, 'desc']],
    ajax: {
      url: '<?= route_to('admin.audit_log_ajax') ?>',
      type: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      data: function (d) {
        d['<?= csrf_token() ?>'] = document.getElementById('csrf_token').value;
        d.filter_user      = $('#filter_user').val();
        d.filter_action    = $('#filter_action').val();
        d.filter_date_from = $('#filter_date_from').val();
        d.filter_date_to   = $('#filter_date_to').val();
      }
    },
    columns: [
      { data: 0 },
      { data: 1 },
      { data: 2 },
      { data: 3 },
      { data: 4 },
      { data: 5 }
    ],
    language: {
      url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json'
    }
  });

  $('#audit-log-table').on('xhr.dt', function (e, settings, json, xhr) {
    if (xhr && xhr.getResponseHeader('<?= csrf_header() ?>')) {
      document.getElementById('csrf_token').value = xhr.getResponseHeader('<?= csrf_header() ?>');
    }
  });

  $('#btn-filter-audit').on('click', function () {
    window.auditLogTable.draw();
  });

  $('#btn-reset-audit').on('click', function () {
    $('#filter_user').val(null).trigger('change');
    $('#filter_action').val('');
    $('#filter_date_from').val('');
    $('#filter_date_to').val('');
    window.auditLogTable.draw();
  });

  $('#btn-export-csv').on('click', function () {
    var headers = ['ID', 'Usuario', 'Acción', 'Descripción', 'IP', 'Fecha'];
    var lines = [headers.join(',')];
    window.auditLogTable.rows({ search: 'applied' }).every(function () {
      var row = this.data();
      var cells = row.map(function (cell) {
        var text = $('<div>').html(cell).text().replace(/\s+/g, ' ').trim();
        if (text.indexOf('"') !== -1 || text.indexOf(',') !== -1) {
          text = '"' + text.replace(/"/g, '""') + '"';
        }
        return text;
      });
      lines.push(cells.join(','));
    });
    var blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'bitacora-global.csv';
    link.click();
  });
});
</script>
<?php $this->endSection(); ?>
