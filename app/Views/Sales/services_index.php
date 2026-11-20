<?php $this->extend($layout); ?>
<?php $this->section('title'); echo esc($headTitle); $this->endSection(); ?>
<?php $this->section('main'); ?>
<main class="content">
  <div class="container-fluid p-0">
    <h1 class="h3 mb-3"><i class="fas fa-sync me-2 text-primary"></i><?= esc($pageTitle) ?></h1>
    <div class="card"><div class="card-body">
      <table id="tbl-services" class="table table-striped w-100">
        <thead><tr><th>Servicio</th><th>Cliente</th><th>Ciclo</th><th>Estatus</th><th>Próximo cobro</th><th>Importe</th></tr></thead>
      </table>
    </div></div>
  </div>
</main>
<?php $this->endSection(); ?>
<?php $this->section('scripts'); ?>
<script>
const csrfName = '<?= csrf_token() ?>';
function csrfVal(){ return document.getElementById('csrf_token').value; }
function csrfHdr(h){ const n=h.get('<?= csrf_header() ?>'); if(n) document.getElementById('csrf_token').value=n; }
$('#tbl-services').DataTable({
  processing:true, serverSide:true,
  ajax:{ url:'<?= route_to('sales.services.ajax') ?>', type:'POST', data:d=>{ d[csrfName]=csrfVal(); } }
});
$('#tbl-services').on('xhr.dt', (e,s,j,xhr)=>{ if(xhr) csrfHdr(xhr); });
</script>
<?php $this->endSection(); ?>
