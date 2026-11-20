<?php $this->extend($layout); ?>
<?php $this->section('title'); echo esc($headTitle); $this->endSection(); ?>
<?php $this->section('main'); ?>
<main class="content">
  <div class="container-fluid p-0">
    <div class="row mb-3">
      <div class="col"><h1 class="h3"><i class="fas fa-cash-register me-2 text-primary"></i><?= esc($pageTitle) ?></h1></div>
      <div class="col-auto"><?php if (auth()->user()->can('sales.create')): ?><button class="btn btn-primary btn-sm" id="btn-new-order"><i class="fas fa-plus me-1"></i>Nueva orden</button><?php endif; ?></div>
    </div>
    <div class="card"><div class="card-body">
      <table id="tbl-orders" class="table table-striped w-100">
        <thead><tr><th>Folio</th><th>Cliente</th><th>Estatus</th><th>Canal</th><th>Total</th><th>Fecha</th><th></th></tr></thead>
      </table>
    </div></div>
  </div>
</main>
<div class="modal fade" id="modal-order" tabindex="-1">
  <div class="modal-dialog modal-lg"><form class="modal-content" id="form-order">
    <div class="modal-header"><h5 class="modal-title">Orden de venta</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <div class="mb-2"><label class="form-label">Cliente</label>
        <select class="form-select" name="client_id" required>
          <option value="">—</option>
          <?php foreach ($response['clients'] ?? [] as $c): ?>
            <option value="<?= (int)$c->id ?>"><?= esc($c->company_name) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <table class="table table-sm" id="order-items">
        <thead><tr><th>Producto</th><th>Ciclo</th><th>Precio</th><th>Cant.</th><th></th></tr></thead>
        <tbody></tbody>
      </table>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-oitem">Renglón</button>
      <textarea class="form-control mt-2" name="notes" placeholder="Notas"></textarea>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Guardar</button></div>
  </form></div>
</div>
<div class="offcanvas offcanvas-end" tabindex="-1" id="oc-order" style="width:420px">
  <div class="offcanvas-header"><h5 class="offcanvas-title">Orden</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
  <div class="offcanvas-body" id="oc-order-body"></div>
</div>
<?php $this->endSection(); ?>
<?php $this->section('scripts'); ?>
<script>
const products = <?= json_encode($response['products'] ?? []) ?>;
const csrfName = '<?= csrf_token() ?>';
function csrfVal(){ return document.getElementById('csrf_token').value; }
function csrfHdr(h){ const n=h.get('<?= csrf_header() ?>'); if(n) document.getElementById('csrf_token').value=n; }
function ajaxHeaders(){ return {'X-Requested-With':'XMLHttpRequest','<?= csrf_header() ?>': csrfVal()}; }
window.tblOrders = $('#tbl-orders').DataTable({
  processing:true, serverSide:true,
  ajax:{ url:'<?= route_to('sales.orders.ajax') ?>', type:'POST', data:d=>{ d[csrfName]=csrfVal(); } }
});
$('#tbl-orders').on('xhr.dt', (e,s,j,xhr)=>{ if(xhr) csrfHdr(xhr); });
function addOItem(){
  const opts = products.map(p=>`<option value="${p.id}" data-name="${p.commercial_name}">${p.commercial_name}</option>`).join('');
  const tr = document.createElement('tr');
  tr.innerHTML = `<td><select class="form-select form-select-sm psel">${opts}</select></td>
    <td><select class="form-select form-select-sm cyc"><option value="one_time">Única</option><option value="monthly">Mensual</option><option value="semiannual">Semestral</option><option value="annual">Anual</option></select></td>
    <td><input type="number" step="0.01" class="form-control form-control-sm price" value="0"></td>
    <td><input type="number" class="form-control form-control-sm qty" value="1"></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger rm">&times;</button></td>`;
  document.querySelector('#order-items tbody').appendChild(tr);
  tr.querySelector('.rm').onclick=()=>tr.remove();
}
document.getElementById('btn-add-oitem').onclick = addOItem;
document.getElementById('btn-new-order')?.addEventListener('click', ()=>{
  document.getElementById('form-order').reset();
  document.querySelector('#order-items tbody').innerHTML='';
  addOItem();
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-order')).show();
});
document.getElementById('form-order').addEventListener('submit', async ev=>{
  ev.preventDefault();
  const items = [...document.querySelectorAll('#order-items tbody tr')].map(tr=>{
    const sel = tr.querySelector('.psel');
    return { product_id: sel.value, item_name: sel.selectedOptions[0].dataset.name, billing_cycle: tr.querySelector('.cyc').value, unit_price: tr.querySelector('.price').value, quantity: tr.querySelector('.qty').value, period_start: new Date().toISOString().slice(0,10) };
  });
  const fd = new FormData(ev.target);
  fd.append('items_json', JSON.stringify(items));
  const r = await fetch('<?= route_to('sales.orders.store') ?>', {method:'POST', headers: ajaxHeaders(), body: fd});
  csrfHdr(r.headers);
  const j = await r.json();
  if(j.success){ bootstrap.Modal.getInstance(document.getElementById('modal-order')).hide(); tblOrders.ajax.reload(); notifyShow(j.message, 'success'); }
  else notifyShow(j.message, 'danger');
});
document.addEventListener('click', async e=>{
  const show = e.target.closest('.btn-show-order');
  if(!show) return;
  const r = await fetch('<?= site_url('nat/sales/orders/show_ajax') ?>/'+show.dataset.id,{headers:{'X-Requested-With':'XMLHttpRequest'}});
  const j = await r.json();
  if(!j.success) return;
  const o = j.response.order;
  document.getElementById('oc-order-body').innerHTML = `<p class="fw-bold">${o.order_number||('#'+o.id)}</p>` +
    (o.items||[]).map(i=>`<div class="small">${i.item_name} · ${i.billing_cycle||'—'} · $${i.unit_price}</div>`).join('') +
    '<h6 class="mt-3">Servicios</h6>' + (o.services||[]).map(s=>`<div class="small">${s.reference_name} · ${s.billing_cycle} · ${s.next_due_date||''}</div>`).join('') +
    (j.response.plugin_tabs||[]).map(t=>`<div class="alert alert-light mt-2">${t.title||''}${t.html||''}</div>`).join('');
  bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('oc-order')).show();
});
</script>
<?php $this->endSection(); ?>
