<?php $this->extend($layout); ?>
<?php $this->section('title'); echo esc($headTitle); $this->endSection(); ?>
<?php $this->section('main'); ?>
<main class="content">
  <div class="container-fluid p-0">
    <div class="row mb-3">
      <div class="col"><h1 class="h3"><i class="fas fa-file-signature me-2 text-primary"></i><?= esc($pageTitle) ?></h1></div>
      <div class="col-auto"><?php if (auth()->user()->can('sales.create')): ?><button class="btn btn-primary btn-sm" id="btn-new-quote"><i class="fas fa-plus me-1"></i>Nueva</button><?php endif; ?></div>
    </div>
    <div class="card"><div class="card-body">
      <table id="tbl-quotes" class="table table-striped w-100">
        <thead><tr><th>Folio</th><th>Cliente</th><th>Estatus</th><th>Total</th><th>Vigencia</th><th></th></tr></thead>
      </table>
    </div></div>
  </div>
</main>
<div class="modal fade" id="modal-quote" tabindex="-1">
  <div class="modal-dialog modal-lg"><form class="modal-content" id="form-quote">
    <div class="modal-header"><h5 class="modal-title" id="quote-modal-title">Cotización</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input type="hidden" name="id" id="quote-id">
      <div class="row g-2 mb-2">
        <div class="col-md-6"><label class="form-label">Cliente</label>
          <select class="form-select" name="client_id" required>
            <option value="">—</option>
            <?php foreach ($response['clients'] ?? [] as $c): ?>
              <option value="<?= (int)$c->id ?>"><?= esc($c->company_name) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3"><label class="form-label">Sucursal</label>
          <select class="form-select" name="branch_id">
            <option value="">Matriz</option>
            <?php foreach ($response['branches'] ?? [] as $b): ?>
              <option value="<?= (int)$b->id ?>"><?= esc($b->name) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3"><label class="form-label">Vigencia</label><input type="date" class="form-control" name="valid_until"></div>
        <div class="col-md-4"><label class="form-label">Estatus</label>
          <select class="form-select" name="status">
            <option value="draft">Borrador</option>
            <option value="sent">Enviada</option>
            <option value="approved">Aprobada</option>
            <option value="rejected">Rechazada</option>
            <option value="expired">Vencida</option>
          </select>
        </div>
      </div>
      <table class="table table-sm" id="quote-items">
        <thead><tr><th>Producto</th><th>Ciclo</th><th>Precio</th><th>Cant.</th><th></th></tr></thead>
        <tbody></tbody>
      </table>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-qitem">Renglón</button>
      <textarea class="form-control mt-2" name="notes" placeholder="Notas"></textarea>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Guardar</button></div>
  </form></div>
</div>
<div class="offcanvas offcanvas-end" tabindex="-1" id="oc-quote" style="width:420px">
  <div class="offcanvas-header"><h5 class="offcanvas-title">Cotización</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
  <div class="offcanvas-body" id="oc-quote-body"></div>
</div>
<?php $this->endSection(); ?>
<?php $this->section('scripts'); ?>
<script>
const products = <?= json_encode($response['products'] ?? []) ?>;
const csrfName = '<?= csrf_token() ?>';
function csrfVal(){ return document.getElementById('csrf_token').value; }
function csrfHdr(h){ const n=h.get('<?= csrf_header() ?>'); if(n) document.getElementById('csrf_token').value=n; }
function ajaxHeaders(){ return {'X-Requested-With':'XMLHttpRequest','<?= csrf_header() ?>': csrfVal()}; }
if (!$.fn.dataTable.isDataTable('#tbl-quotes')) {
window.tblQuotes = $('#tbl-quotes').DataTable({
  processing:true, serverSide:true,
  ajax:{ url:'<?= route_to('sales.quotes.ajax') ?>', type:'POST', data:d=>{ d[csrfName]=csrfVal(); } }
});
$('#tbl-quotes').on('xhr.dt', (e,s,j,xhr)=>{ if(xhr) csrfHdr(xhr); });
}
function addQItem(item={}){
  const opts = products.map(p=>`<option value="${p.id}" data-name="${p.commercial_name}" ${String(item.product_id||'')===String(p.id)?'selected':''}>${p.commercial_name}</option>`).join('');
  const tr = document.createElement('tr');
  tr.innerHTML = `<td><select class="form-select form-select-sm psel">${opts}</select></td>
    <td><select class="form-select form-select-sm cyc">
      ${['one_time','monthly','semiannual','annual'].map(c=>`<option value="${c}" ${(item.billing_cycle||'')===c?'selected':''}>${c}</option>`).join('')}
    </select></td>
    <td><input type="number" step="0.01" class="form-control form-control-sm price" value="${item.unit_price||0}"></td>
    <td><input type="number" class="form-control form-control-sm qty" value="${item.quantity||1}"></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger rm">&times;</button></td>`;
  document.querySelector('#quote-items tbody').appendChild(tr);
  tr.querySelector('.rm').onclick=()=>tr.remove();
}
document.getElementById('btn-add-qitem').onclick = ()=>addQItem();
document.getElementById('btn-new-quote')?.addEventListener('click', ()=>{
  document.getElementById('form-quote').reset();
  document.getElementById('quote-id').value='';
  document.getElementById('quote-modal-title').textContent='Nueva cotización';
  document.querySelector('#quote-items tbody').innerHTML='';
  addQItem();
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-quote')).show();
});
document.getElementById('form-quote').addEventListener('submit', async ev=>{
  ev.preventDefault();
  const items = [...document.querySelectorAll('#quote-items tbody tr')].map(tr=>{
    const sel = tr.querySelector('.psel');
    return { product_id: sel.value, item_name: sel.selectedOptions[0].dataset.name, billing_cycle: tr.querySelector('.cyc').value, unit_price: tr.querySelector('.price').value, quantity: tr.querySelector('.qty').value };
  });
  const fd = new FormData(ev.target);
  fd.append('items_json', JSON.stringify(items));
  const id = document.getElementById('quote-id').value;
  const url = id ? '<?= site_url('nat/sales/quotes/update') ?>/' + id : '<?= route_to('sales.quotes.store') ?>';
  const r = await fetch(url, {method:'POST', headers: ajaxHeaders(), body: fd});
  csrfHdr(r.headers);
  const j = await r.json();
  if(j.success){ bootstrap.Modal.getInstance(document.getElementById('modal-quote')).hide(); tblQuotes.ajax.reload(); notifyShow(j.message, 'success'); }
  else notifyShow(j.message, 'danger');
});
document.addEventListener('click', async e=>{
  const show = e.target.closest('.btn-show-quote');
  const conv = e.target.closest('.btn-convert-quote');
  const edit = e.target.closest('.btn-edit-quote');
  if(show){
    const r = await fetch('<?= site_url('nat/sales/quotes/show_ajax') ?>/'+show.dataset.id,{headers:{'X-Requested-With':'XMLHttpRequest'}});
    const j = await r.json();
    if(!j.success) return;
    const q = j.response.quote;
    const pdf = j.response.pdf || ('<?= site_url('nat/sales/documents/quote') ?>/'+q.id);
    const html = j.response.pdf_html || (pdf+'/html');
    document.getElementById('oc-quote-body').innerHTML = `<p class="fw-bold">${q.quote_number} · ${q.status}</p>`
      + (q.items||[]).map(i=>`<div class="small">${i.item_name} · ${i.billing_cycle} · $${i.unit_price} x ${i.quantity}</div>`).join('')
      + `<p class="mt-2">Total $${Number(q.total_amount).toFixed(2)}</p>`
      + `<div class="d-flex gap-2"><a class="btn btn-sm btn-outline-secondary" target="_blank" href="${html}">Vista previa</a><a class="btn btn-sm btn-outline-danger" target="_blank" href="${pdf}">PDF</a><a class="btn btn-sm btn-outline-dark" target="_blank" href="${pdf}?action=download">Descargar</a></div>`
      + (j.response.plugin_tabs||[]).map(t=>`<div class="alert alert-light mt-2">${t.title||''}${t.html||''}</div>`).join('');
    bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('oc-quote')).show();
  }
  if(edit){
    const r = await fetch('<?= site_url('nat/sales/quotes/show_ajax') ?>/'+edit.dataset.id,{headers:{'X-Requested-With':'XMLHttpRequest'}});
    const j = await r.json(); if(!j.success) return;
    const q = j.response.quote;
    const f = document.getElementById('form-quote');
    f.reset();
    document.getElementById('quote-id').value = q.id;
    document.getElementById('quote-modal-title').textContent = 'Actualizar ' + q.quote_number;
    f.client_id.value = q.client_id;
    if(f.branch_id) f.branch_id.value = q.branch_id || '';
    f.valid_until.value = q.valid_until || '';
    f.status.value = ['converted'].includes(q.status) ? 'draft' : q.status;
    f.notes.value = q.notes || '';
    document.querySelector('#quote-items tbody').innerHTML='';
    (q.items||[]).forEach(addQItem);
    if(!(q.items||[]).length) addQItem();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-quote')).show();
  }
  if(conv && confirm('¿Convertir esta cotización a venta? Se emitirá recibo/prefactura.')){
    const r = await fetch('<?= site_url('nat/sales/quotes/convert') ?>/'+conv.dataset.id,{method:'POST', headers: ajaxHeaders(), body: new URLSearchParams({[csrfName]:csrfVal()})});
    csrfHdr(r.headers);
    const j = await r.json();
    notifyShow(j.message, j.success?'success':'danger');
    tblQuotes.ajax.reload();
  }
});
</script>
<?php $this->endSection(); ?>
