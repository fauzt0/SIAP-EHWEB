<?php $this->extend($layout); ?>
<?php $this->section('title'); echo esc($headTitle); $this->endSection(); ?>
<?php $this->section('main'); ?>
<main class="content">
  <div class="container-fluid p-0">
    <div class="row mb-3">
      <div class="col"><h1 class="h3"><i class="fas fa-balance-scale me-2 text-primary"></i><?= esc($pageTitle) ?></h1></div>
    </div>
    <?php $f = $response['folios'] ?? []; $e = $response['expenses'] ?? []; ?>
    <div class="row g-3 mb-3">
      <div class="col-md-3"><div class="card"><div class="card-body">
        <div class="text-muted small">Fondos solicitados</div>
        <div class="h4 mb-0"><?= number_format((float)($f['requested_amount'] ?? 0), 2) ?></div>
        <div class="small"><?= (int)($f['requested_count'] ?? 0) ?> folios</div>
      </div></div></div>
      <div class="col-md-3"><div class="card"><div class="card-body">
        <div class="text-muted small">Fondos aprobados</div>
        <div class="h4 mb-0"><?= number_format((float)($f['approved_amount'] ?? 0), 2) ?></div>
      </div></div></div>
      <div class="col-md-3"><div class="card"><div class="card-body">
        <div class="text-muted small">Gastos vencidos</div>
        <div class="h4 mb-0"><?= number_format((float)($e['overdue_amount'] ?? 0), 2) ?></div>
      </div></div></div>
      <div class="col-md-3"><div class="card"><div class="card-body">
        <div class="text-muted small">CxP proveedores</div>
        <div class="h4 mb-0"><?= number_format((float)($response['open_supplier'] ?? 0), 2) ?></div>
      </div></div></div>
    </div>
    <div class="card mb-3"><div class="card-body">
      <h5 class="card-title">Cuentas</h5>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>Cuenta</th><th>Tipo</th><th>Saldo</th></tr></thead>
        <tbody>
        <?php foreach ($response['accounts'] ?? [] as $a): ?>
          <tr><td><?= esc($a->name) ?></td><td><?= esc($a->type) ?></td><td><?= number_format((float)$a->balance, 2) ?> <?= esc($a->currency) ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($response['accounts'])): ?><tr><td colspan="3" class="text-muted">Sin cuentas. Cree Caja chica o Banco en Cuentas de tesorería.</td></tr><?php endif; ?>
        </tbody>
      </table></div>
    </div></div>
    <div class="card"><div class="card-body">
      <h5 class="card-title">Libro mayor</h5>
      <table id="tbl-ledger" class="table table-striped w-100">
        <thead><tr><th>Fecha</th><th>Cuenta</th><th>Origen</th><th>Tipo</th><th>Monto</th><th>Saldo</th><th>Notas</th></tr></thead>
      </table>
    </div></div>
  </div>
</main>
<?php $this->endSection(); ?>
<?php $this->section('scripts'); ?>
<script>
const csrfName = '<?= csrf_token() ?>';
function csrfVal(){ return document.getElementById('csrf_token').value; }
if (!$.fn.dataTable.isDataTable('#tbl-ledger')) {
  $('#tbl-ledger').DataTable({
    processing:true, serverSide:true, order:[[0,'desc']],
    ajax:{ url:'<?= route_to('accounting.ledger_ajax') ?>', type:'POST', data:d=>{ d[csrfName]=csrfVal(); } }
  });
  $('#tbl-ledger').on('xhr.dt', (e,s,j,xhr)=>{ if(xhr){ const n=xhr.getResponseHeader('<?= csrf_header() ?>'); if(n) document.getElementById('csrf_token').value=n; } });
}
</script>
<?php $this->endSection(); ?>
