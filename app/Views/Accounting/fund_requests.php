<?php $this->extend($layout); ?>
<?php $this->section('title'); echo esc($headTitle); $this->endSection(); ?>
<?php $this->section('main'); ?>
<main class="content">
  <div class="container-fluid p-0">
    <div class="row mb-3">
      <div class="col"><h1 class="h3"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i><?= esc($pageTitle) ?></h1></div>
      <div class="col-auto"><?php if (auth()->user()->can('finance.transactions')): ?>
        <button class="btn btn-primary btn-sm" id="btn-new-folio"><i class="fas fa-plus me-1"></i>Nueva solicitud</button>
      <?php endif; ?></div>
    </div>
    <div class="card"><div class="card-body">
      <table id="tbl-folios" class="table table-striped w-100">
        <thead><tr><th>Folio</th><th>Concepto</th><th>Cuenta</th><th>Monto</th><th>Estatus</th><th>Fecha</th><th></th></tr></thead>
      </table>
    </div></div>
  </div>
</main>
<div class="modal fade" id="modal-folio" tabindex="-1">
  <div class="modal-dialog"><form class="modal-content" id="form-folio">
    <div class="modal-header"><h5 class="modal-title">Solicitud de fondos</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <label class="form-label">Concepto del gasto</label>
      <textarea class="form-control mb-2" name="concept" required minlength="3"></textarea>
      <div class="row g-2">
        <div class="col-md-6"><label class="form-label">Monto</label><input type="number" step="0.01" min="0.01" class="form-control" name="amount_paid" required></div>
        <div class="col-md-6"><label class="form-label">Cuenta</label>
          <select class="form-select" name="account_id" required>
            <?php foreach ($response['accounts'] ?? [] as $a): ?>
              <option value="<?= (int)$a->id ?>"><?= esc($a->name) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Generar folio</button></div>
  </form></div>
</div>
<?php $this->endSection(); ?>
<?php $this->section('scripts'); ?>
<script>
const csrfName = '<?= csrf_token() ?>';
function csrfVal(){ return document.getElementById('csrf_token').value; }
function csrfHdr(h){ const n=h.get('<?= csrf_header() ?>'); if(n) document.getElementById('csrf_token').value=n; }
function ajaxHeaders(){ return {'X-Requested-With':'XMLHttpRequest','<?= csrf_header() ?>': csrfVal()}; }
if (!$.fn.dataTable.isDataTable('#tbl-folios')) {
  window.tblFolios = $('#tbl-folios').DataTable({
    processing:true, serverSide:true,
    ajax:{ url:'<?= route_to('accounting.funds.ajax') ?>', type:'POST', data:d=>{ d[csrfName]=csrfVal(); } }
  });
  $('#tbl-folios').on('xhr.dt', (e,s,j,xhr)=>{ if(xhr) csrfHdr(xhr); });
}
document.getElementById('btn-new-folio')?.addEventListener('click', ()=>{
  document.getElementById('form-folio').reset();
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-folio')).show();
});
document.getElementById('form-folio').addEventListener('submit', async ev=>{
  ev.preventDefault();
  const r = await fetch('<?= route_to('accounting.funds.store') ?>', {method:'POST', headers: ajaxHeaders(), body: new FormData(ev.target)});
  csrfHdr(r.headers);
  const j = await r.json();
  if(j.success){ bootstrap.Modal.getInstance(document.getElementById('modal-folio')).hide(); tblFolios.ajax.reload(); notifyShow(j.message, 'success'); }
  else notifyShow(j.message, 'danger');
});
document.addEventListener('click', async e=>{
  const btn = e.target.closest('.btn-approve,.btn-reject,.btn-execute');
  if(!btn) return;
  const id = btn.dataset.id;
  let url = '';
  if(btn.classList.contains('btn-approve')) url = '<?= site_url('nat/accounting/funds/approve') ?>/' + id;
  if(btn.classList.contains('btn-reject')) url = '<?= site_url('nat/accounting/funds/reject') ?>/' + id;
  if(btn.classList.contains('btn-execute')) url = '<?= site_url('nat/accounting/funds/execute') ?>/' + id;
  const fd = new FormData(); fd.append(csrfName, csrfVal());
  const r = await fetch(url, {method:'POST', headers: ajaxHeaders(), body: fd});
  csrfHdr(r.headers);
  const j = await r.json();
  notifyShow(j.message, j.success ? 'success' : 'danger');
  if(j.success) tblFolios.ajax.reload();
});
</script>
<?php $this->endSection(); ?>
