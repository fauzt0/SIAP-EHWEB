<?php $this->extend($layout); ?>
<?php $this->section('title'); echo esc($headTitle); $this->endSection(); ?>
<?php $this->section('main'); ?>
<main class="content">
  <div class="container-fluid p-0">
    <div class="row mb-3">
      <div class="col"><h1 class="h3"><?= esc($pageTitle) ?></h1></div>
      <div class="col-auto"><button class="btn btn-primary btn-sm" id="btn-new-acc">Nueva cuenta</button></div>
    </div>
    <div class="card"><div class="card-body">
      <table id="tbl-acc" class="table table-striped w-100">
        <thead><tr><th>Nombre</th><th>Tipo</th><th>Moneda</th><th>Saldo</th><th>Estatus</th></tr></thead>
      </table>
    </div></div>
  </div>
</main>
<div class="modal fade" id="modal-acc" tabindex="-1">
  <div class="modal-dialog"><form class="modal-content" id="form-acc">
    <div class="modal-header"><h5 class="modal-title">Cuenta</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input class="form-control mb-2" name="name" placeholder="Nombre" required>
      <select class="form-select mb-2" name="type">
        <option value="cash">Caja</option>
        <option value="bank">Banco</option>
        <option value="digital">Digital</option>
      </select>
      <input class="form-control mb-2" name="currency" value="MXN" maxlength="3">
      <input type="number" step="0.01" class="form-control" name="balance" value="0" placeholder="Saldo inicial">
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Guardar</button></div>
  </form></div>
</div>
<?php $this->endSection(); ?>
<?php $this->section('scripts'); ?>
<script>
const csrfName = '<?= csrf_token() ?>';
function csrfVal(){ return document.getElementById('csrf_token').value; }
function csrfHdr(h){ const n=h.get('<?= csrf_header() ?>'); if(n) document.getElementById('csrf_token').value=n; }
function ajaxHeaders(){ return {'X-Requested-With':'XMLHttpRequest','<?= csrf_header() ?>': csrfVal()}; }
if (!$.fn.dataTable.isDataTable('#tbl-acc')) {
  window.tblAcc = $('#tbl-acc').DataTable({
    processing:true, serverSide:true,
    ajax:{ url:'<?= route_to('accounting.accounts.ajax') ?>', type:'POST', data:d=>{ d[csrfName]=csrfVal(); } }
  });
  $('#tbl-acc').on('xhr.dt', (e,s,j,xhr)=>{ if(xhr) csrfHdr(xhr); });
}
document.getElementById('btn-new-acc').onclick=()=>{ document.getElementById('form-acc').reset(); bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-acc')).show(); };
document.getElementById('form-acc').addEventListener('submit', async ev=>{
  ev.preventDefault();
  const r = await fetch('<?= route_to('accounting.accounts.store') ?>', {method:'POST', headers: ajaxHeaders(), body: new FormData(ev.target)});
  csrfHdr(r.headers); const j = await r.json();
  if(j.success){ bootstrap.Modal.getInstance(document.getElementById('modal-acc')).hide(); tblAcc.ajax.reload(); notifyShow(j.message,'success'); }
  else notifyShow(j.message,'danger');
});
</script>
<?php $this->endSection(); ?>
