<?php $this->extend($layout); ?>
<?php $this->section('title'); echo esc($headTitle); $this->endSection(); ?>
<?php $this->section('main'); ?>
<style>
.crm-col { min-width: 220px; background:#f8f9fa; border-radius:8px; padding:8px; }
.crm-card { cursor:grab; }
</style>
<main class="content">
  <div class="container-fluid p-0">
    <div class="row mb-3">
      <div class="col"><h1 class="h3"><i class="fas fa-funnel-dollar me-2 text-primary"></i><?= esc($pageTitle) ?></h1></div>
      <div class="col-auto"><?php if (auth()->user()->can('crm.manage')): ?>
        <button class="btn btn-primary btn-sm" id="btn-new-opp">Nueva oportunidad</button>
      <?php endif; ?></div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">Pipeline abierto</div><div class="h4 mb-0">$<?= number_format((float)($response['forecast']['pipeline_value']??0),2) ?></div></div></div></div>
      <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">Pronóstico ponderado</div><div class="h4 mb-0">$<?= number_format((float)($response['forecast']['weighted']??0),2) ?></div></div></div></div>
      <div class="col-md-4">
        <label class="form-label">Embudo</label>
        <select id="filter-pipeline" class="form-select">
          <?php foreach ($response['pipelines'] ?? [] as $p): ?>
            <option value="<?= (int)$p->id ?>"><?= esc($p->name) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="card mb-3"><div class="card-body overflow-auto">
      <div class="d-flex gap-3" id="opp-board" style="min-height:280px"></div>
    </div></div>
    <div class="card"><div class="card-body">
      <table id="tbl-opp" class="table table-striped w-100">
        <thead><tr><th>Folio</th><th>Título</th><th>Cliente</th><th>Embudo</th><th>Etapa</th><th>Valor</th><th>%</th><th>Cierre</th><th></th></tr></thead>
      </table>
    </div></div>
  </div>
</main>
<div class="modal fade" id="modal-opp" tabindex="-1">
  <div class="modal-dialog"><form class="modal-content" id="form-opp">
    <div class="modal-header"><h5 class="modal-title">Oportunidad</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input class="form-control mb-2" name="title" placeholder="Título del trato" required>
      <select class="form-select mb-2" name="client_id" required>
        <option value="">Cliente</option>
        <?php foreach ($response['clients'] ?? [] as $c): ?>
          <option value="<?= (int)$c->id ?>"><?= esc($c->company_name) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-select mb-2" name="pipeline_id">
        <?php foreach ($response['pipelines'] ?? [] as $p): ?>
          <option value="<?= (int)$p->id ?>"><?= esc($p->name) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="number" step="0.01" class="form-control mb-2" name="expected_value" placeholder="Valor esperado">
      <input type="date" class="form-control" name="close_date">
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Guardar</button></div>
  </form></div>
</div>
<div class="modal fade" id="modal-lost" tabindex="-1">
  <div class="modal-dialog"><form class="modal-content" id="form-lost">
    <div class="modal-header"><h5 class="modal-title">Motivo de pérdida</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input type="hidden" id="lost-opp-id">
      <input type="hidden" id="lost-stage-id">
      <select class="form-select mb-2" name="lost_reason_id" required>
        <option value="">Seleccione motivo</option>
        <?php foreach ($response['reasons'] ?? [] as $r): ?>
          <option value="<?= (int)$r->id ?>"><?= esc($r->name) ?></option>
        <?php endforeach; ?>
      </select>
      <textarea class="form-control" name="lost_notes" placeholder="Notas"></textarea>
    </div>
    <div class="modal-footer"><button class="btn btn-danger">Marcar perdido</button></div>
  </form></div>
</div>
<?php $this->endSection(); ?>
<?php $this->section('scripts'); ?>
<script>
const csrfName = '<?= csrf_token() ?>';
const lostStageIds = <?= json_encode(array_values(array_map(fn($s)=>(int)$s->id, array_filter($response['stages']??[], fn($s)=>(int)$s->is_lost===1)))) ?>;
function csrfVal(){ return document.getElementById('csrf_token').value; }
function csrfHdr(h){ const n=h.get('<?= csrf_header() ?>'); if(n) document.getElementById('csrf_token').value=n; }
function ajaxHeaders(){ return {'X-Requested-With':'XMLHttpRequest','<?= csrf_header() ?>': csrfVal()}; }
if (!$.fn.dataTable.isDataTable('#tbl-opp')) {
  window.tblOpp = $('#tbl-opp').DataTable({
    processing:true, serverSide:true,
    ajax:{ url:'<?= route_to('crm.opportunities.ajax') ?>', type:'POST', data:d=>{ d[csrfName]=csrfVal(); d.filter_pipeline=document.getElementById('filter-pipeline').value; } }
  });
  $('#tbl-opp').on('xhr.dt', (e,s,j,xhr)=>{ if(xhr) csrfHdr(xhr); });
}
async function loadBoard(){
  const pid = document.getElementById('filter-pipeline').value;
  const r = await fetch('<?= route_to('crm.opportunities.board') ?>?pipeline_id='+pid, {headers:{'X-Requested-With':'XMLHttpRequest'}});
  const j = await r.json();
  const stages = j.response?.stages || [];
  const cols = j.response?.columns || {};
  document.getElementById('opp-board').innerHTML = stages.map(st=>{
    const cards = (cols[st.id]||cols[String(st.id)]||[]).map(o=>`
      <div class="card crm-card mb-2" draggable="true" data-id="${o.id}" data-lost="${st.is_lost}">
        <div class="card-body p-2"><strong>${o.title||''}</strong><div class="small text-muted">${o.company_name||''}</div>
        <div class="small">$${Number(o.expected_value||0).toFixed(2)} · ${o.probability||0}%</div></div>
      </div>`).join('');
    return `<div class="crm-col flex-fill" data-stage="${st.id}" data-lost="${st.is_lost}" ondragover="event.preventDefault()" ondrop="dropOpp(event, ${st.id}, ${st.is_lost})">
      <div class="fw-semibold mb-2">${st.name} <span class="badge bg-light text-dark">${st.win_probability}%</span></div>${cards}</div>`;
  }).join('');
}
document.getElementById('filter-pipeline').addEventListener('change', ()=>{ tblOpp.ajax.reload(); loadBoard(); });
loadBoard();
window.dropOpp = async function(ev, stageId, isLost){
  ev.preventDefault();
  const id = ev.dataTransfer.getData('id');
  if(!id) return;
  if(Number(isLost)===1){
    document.getElementById('lost-opp-id').value = id;
    document.getElementById('lost-stage-id').value = stageId;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-lost')).show();
    return;
  }
  await moveOpp(id, stageId, {});
};
document.addEventListener('dragstart', e=>{
  const card = e.target.closest('.crm-card');
  if(card) e.dataTransfer.setData('id', card.dataset.id);
});
async function moveOpp(id, stageId, extra){
  const fd = new FormData();
  fd.append(csrfName, csrfVal());
  fd.append('stage_id', stageId);
  Object.keys(extra).forEach(k=>fd.append(k, extra[k]));
  const r = await fetch('<?= site_url('nat/crm/opportunities/move') ?>/' + id, {method:'POST', headers: ajaxHeaders(), body: fd});
  csrfHdr(r.headers); const j = await r.json();
  notifyShow(j.message, j.success?'success':'danger');
  if(j.success){ loadBoard(); tblOpp.ajax.reload(); }
}
document.getElementById('btn-new-opp')?.addEventListener('click', ()=>{
  document.getElementById('form-opp').reset();
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-opp')).show();
});
document.getElementById('form-opp').addEventListener('submit', async ev=>{
  ev.preventDefault();
  const r = await fetch('<?= route_to('crm.opportunities.store') ?>', {method:'POST', headers: ajaxHeaders(), body: new FormData(ev.target)});
  csrfHdr(r.headers); const j = await r.json();
  if(j.success){ bootstrap.Modal.getInstance(document.getElementById('modal-opp')).hide(); loadBoard(); tblOpp.ajax.reload(); notifyShow(j.message,'success'); }
  else notifyShow(j.message,'danger');
});
document.getElementById('form-lost').addEventListener('submit', async ev=>{
  ev.preventDefault();
  const id = document.getElementById('lost-opp-id').value;
  const stageId = document.getElementById('lost-stage-id').value;
  const extra = { lost_reason_id: ev.target.lost_reason_id.value, lost_notes: ev.target.lost_notes.value };
  bootstrap.Modal.getInstance(document.getElementById('modal-lost')).hide();
  await moveOpp(id, stageId, extra);
});
</script>
<?php $this->endSection(); ?>
