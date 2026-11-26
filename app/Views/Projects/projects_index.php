<?php $this->extend($layout); ?>
<?php $this->section('title'); echo esc($headTitle); $this->endSection(); ?>
<?php $this->section('main'); ?>
<main class="content">
  <div class="container-fluid p-0">
    <div class="row mb-3">
      <div class="col"><h1 class="h3"><i class="fas fa-tasks me-2 text-primary"></i><?= esc($pageTitle) ?></h1></div>
      <div class="col-auto"><?php if (auth()->user()->can('projects.manage')): ?>
        <button class="btn btn-primary btn-sm" id="btn-new-prj">Nuevo proyecto</button>
      <?php endif; ?></div>
    </div>
    <div class="card"><div class="card-body">
      <table id="tbl-prj" class="table table-striped w-100">
        <thead><tr><th>Código</th><th>Nombre</th><th>Cliente</th><th>Estatus</th><th>Avance</th><th>Presupuesto</th><th>Entrega</th><th></th></tr></thead>
      </table>
    </div></div>
  </div>
</main>
<div class="modal fade" id="modal-prj" tabindex="-1">
  <div class="modal-dialog"><form class="modal-content" id="form-prj">
    <div class="modal-header"><h5 class="modal-title">Proyecto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input class="form-control mb-2" name="name" placeholder="Nombre" required>
      <select class="form-select mb-2" name="client_id" required>
        <option value="">Cliente</option>
        <?php foreach ($response['clients'] ?? [] as $c): ?>
          <option value="<?= (int)$c->id ?>"><?= esc($c->company_name) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="number" class="form-control mb-2" name="quote_id" placeholder="ID cotización (opcional, copia partidas)">
      <input type="number" step="0.01" class="form-control mb-2" name="budget" placeholder="Presupuesto">
      <input type="date" class="form-control" name="due_date">
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Guardar</button></div>
  </form></div>
</div>
<div class="offcanvas offcanvas-end" tabindex="-1" id="oc-prj" style="width:480px">
  <div class="offcanvas-header"><h5 class="offcanvas-title">Proyecto</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
  <div class="offcanvas-body" id="oc-prj-body"></div>
</div>
<?php $this->endSection(); ?>
<?php $this->section('scripts'); ?>
<script>
const csrfName = '<?= csrf_token() ?>';
const statuses = <?= json_encode($response['statuses'] ?? []) ?>;
function csrfVal(){ return document.getElementById('csrf_token').value; }
function csrfHdr(h){ const n=h.get('<?= csrf_header() ?>'); if(n) document.getElementById('csrf_token').value=n; }
function ajaxHeaders(){ return {'X-Requested-With':'XMLHttpRequest','<?= csrf_header() ?>': csrfVal()}; }
if (!$.fn.dataTable.isDataTable('#tbl-prj')) {
  window.tblPrj = $('#tbl-prj').DataTable({
    processing:true, serverSide:true,
    ajax:{ url:'<?= route_to('projects.ajax') ?>', type:'POST', data:d=>{ d[csrfName]=csrfVal(); } }
  });
  $('#tbl-prj').on('xhr.dt', (e,s,j,xhr)=>{ if(xhr) csrfHdr(xhr); });
}
document.getElementById('btn-new-prj')?.addEventListener('click', ()=>{
  document.getElementById('form-prj').reset();
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-prj')).show();
});
document.getElementById('form-prj').addEventListener('submit', async ev=>{
  ev.preventDefault();
  const r = await fetch('<?= route_to('projects.store') ?>', {method:'POST', headers: ajaxHeaders(), body: new FormData(ev.target)});
  csrfHdr(r.headers); const j = await r.json();
  if(j.success){ bootstrap.Modal.getInstance(document.getElementById('modal-prj')).hide(); tblPrj.ajax.reload(); notifyShow(j.message,'success'); }
  else notifyShow(j.message,'danger');
});
document.addEventListener('click', async e=>{
  const btn = e.target.closest('.btn-show-prj');
  if(!btn) return;
  const r = await fetch('<?= site_url('nat/projects/show_ajax') ?>/' + btn.dataset.id, {headers:{'X-Requested-With':'XMLHttpRequest'}});
  const j = await r.json();
  if(!j.success){ notifyShow(j.message,'danger'); return; }
  const p = j.response.project;
  const items = (j.response.items||[]).map(i=>`<li>${i.item_name} × ${i.quantity} @ $${Number(i.unit_price).toFixed(2)} <small class="text-muted">(catálogo $${Number(i.catalog_price).toFixed(2)})</small></li>`).join('') || '<li class="text-muted">Sin partidas</li>';
  const opts = Object.keys(statuses).map(k=>`<option value="${k}" ${p.status===k?'selected':''}>${statuses[k]}</option>`).join('');
  document.getElementById('oc-prj-body').innerHTML = `
    <h5>${p.code} — ${p.name}</h5>
    <p class="text-muted">Avance ${p.progress}%</p>
    <ul>${items}</ul>
    <form id="form-prj-st">
      <select class="form-select mb-2" name="status">${opts}</select>
      <input type="range" class="form-range" name="progress" min="0" max="100" value="${p.progress}">
      <button class="btn btn-primary btn-sm">Actualizar avance</button>
    </form>`;
  document.getElementById('form-prj-st').onsubmit = async ev=>{
    ev.preventDefault();
    const r2 = await fetch('<?= site_url('nat/projects/status') ?>/' + p.id, {method:'POST', headers: ajaxHeaders(), body: new FormData(ev.target)});
    csrfHdr(r2.headers); const j2 = await r2.json();
    notifyShow(j2.message, j2.success?'success':'danger');
    if(j2.success) tblPrj.ajax.reload();
  };
  bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('oc-prj')).show();
});
</script>
<?php $this->endSection(); ?>
