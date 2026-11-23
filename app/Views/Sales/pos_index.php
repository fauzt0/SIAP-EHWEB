<?php $this->extend($layout); ?>
<?php $this->section('title'); echo esc($headTitle); $this->endSection(); ?>
<?php $this->section('main'); ?>
<main class="content">
  <div class="container-fluid p-0">
    <div class="row mb-3">
      <div class="col"><h1 class="h3"><i class="fas fa-store me-2 text-primary"></i><?= esc($pageTitle) ?></h1>
        <p class="text-muted mb-0">Venta directa de mostrador o cotización con estatus. El PDF usa el logo de la sucursal o de la empresa.</p>
      </div>
    </div>
    <div class="row g-3">
      <div class="col-lg-7">
        <div class="card">
          <div class="card-header"><input type="search" class="form-control" id="pos-q" placeholder="Buscar producto o SKU"></div>
          <div class="card-body" id="pos-products" style="max-height:520px;overflow:auto"></div>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="card">
          <div class="card-body">
            <div class="row g-2 mb-2">
              <div class="col-8">
                <label class="form-label">Cliente</label>
                <select class="form-select" id="pos-client">
                  <option value="">—</option>
                  <?php foreach ($response['clients'] ?? [] as $c): ?>
                    <option value="<?= (int)$c->id ?>"><?= esc($c->company_name) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-4">
                <label class="form-label">Sucursal</label>
                <select class="form-select" id="pos-branch">
                  <option value="">Matriz</option>
                  <?php foreach ($response['branches'] ?? [] as $b): ?>
                    <option value="<?= (int)$b->id ?>"><?= esc($b->name) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="pos-walkin">
                  <label class="form-check-label" for="pos-walkin">Venta de mostrador (público general)</label>
                </div>
              </div>
            </div>
            <table class="table table-sm" id="pos-cart">
              <thead><tr><th>Producto</th><th>Cant.</th><th>Precio</th><th></th></tr></thead>
              <tbody></tbody>
            </table>
            <div class="d-flex justify-content-between fw-bold mb-2"><span>Total (sin IVA)</span><span id="pos-sub">$0.00</span></div>
            <div class="d-flex justify-content-between mb-3"><span>Total + IVA 16%</span><span id="pos-tot">$0.00</span></div>
            <textarea class="form-control mb-2" id="pos-notes" rows="2" placeholder="Notas"></textarea>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label">Documento</label>
                <select class="form-select" id="pos-mode">
                  <option value="sale">Venta</option>
                  <option value="quote">Cotización</option>
                </select>
              </div>
              <div class="col-6" id="pos-quote-fields">
                <label class="form-label">Estatus cotización</label>
                <select class="form-select" id="pos-qstatus">
                  <option value="draft">Borrador</option>
                  <option value="sent">Enviada</option>
                </select>
              </div>
            </div>
            <?php if (!empty($response['canCreate'])): ?>
            <button class="btn btn-primary w-100" id="btn-pos-pay"><i class="fas fa-check me-1"></i>Cobrar / emitir</button>
            <?php endif; ?>
            <div id="pos-result" class="mt-3"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
<?php $this->endSection(); ?>
<?php $this->section('scripts'); ?>
<script>
const csrfName = '<?= csrf_token() ?>';
function csrfVal(){ return document.getElementById('csrf_token').value; }
function csrfHdr(h){ const n=h.get('<?= csrf_header() ?>'); if(n) document.getElementById('csrf_token').value=n; }
function ajaxHeaders(){ return {'X-Requested-With':'XMLHttpRequest','<?= csrf_header() ?>': csrfVal()}; }
let cart = [];
function money(n){ return '$' + (Number(n)||0).toFixed(2); }
function renderCart(){
  const tb = document.querySelector('#pos-cart tbody');
  tb.innerHTML = cart.map((i,idx)=>`<tr>
    <td><div class="small fw-semibold">${i.item_name}</div><div class="text-muted small">${i.billing_cycle||''}</div></td>
    <td><input type="number" min="1" class="form-control form-control-sm qty" data-i="${idx}" value="${i.quantity}"></td>
    <td><input type="number" step="0.01" class="form-control form-control-sm price" data-i="${idx}" value="${i.unit_price}"></td>
    <td><button class="btn btn-sm btn-outline-danger rm" data-i="${idx}">&times;</button></td>
  </tr>`).join('');
  const sub = cart.reduce((s,i)=>s + Number(i.unit_price)*Number(i.quantity), 0);
  document.getElementById('pos-sub').textContent = money(sub);
  document.getElementById('pos-tot').textContent = money(sub * 1.16);
}
async function loadProducts(q=''){
  const r = await fetch('<?= route_to('sales.pos.products') ?>?q=' + encodeURIComponent(q), {headers:{'X-Requested-With':'XMLHttpRequest'}});
  const j = await r.json();
  const list = (j.response && j.response.products) || [];
  document.getElementById('pos-products').innerHTML = list.map(p=>`<button type="button" class="btn btn-light border w-100 text-start mb-1 pos-add"
    data-id="${p.id}" data-plan="${p.plan_id||''}" data-name="${p.commercial_name} ${p.plan_name?('· '+p.plan_name):''}" data-cycle="${p.billing_cycle||'one_time'}" data-price="${p.sale_price||0}">
    <div class="fw-semibold">${p.commercial_name}</div>
    <div class="small text-muted">${p.sku||''} · ${p.plan_name||''} · ${p.billing_cycle||''} · ${money(p.sale_price||0)}</div>
  </button>`).join('') || '<p class="text-muted">Sin productos</p>';
}
loadProducts();
let t=null;
document.getElementById('pos-q').addEventListener('input', ev=>{
  clearTimeout(t); t=setTimeout(()=>loadProducts(ev.target.value), 250);
});
document.getElementById('pos-products').addEventListener('click', e=>{
  const b = e.target.closest('.pos-add'); if(!b) return;
  cart.push({ product_id:b.dataset.id, plan_id:b.dataset.plan||null, item_name:b.dataset.name, billing_cycle:b.dataset.cycle, unit_price:b.dataset.price, quantity:1 });
  renderCart();
});
document.querySelector('#pos-cart').addEventListener('input', e=>{
  const i = e.target.dataset.i; if(i===undefined) return;
  if(e.target.classList.contains('qty')) cart[i].quantity = e.target.value;
  if(e.target.classList.contains('price')) cart[i].unit_price = e.target.value;
  renderCart();
});
document.querySelector('#pos-cart').addEventListener('click', e=>{
  const b = e.target.closest('.rm'); if(!b) return;
  cart.splice(Number(b.dataset.i),1); renderCart();
});
document.getElementById('btn-pos-pay')?.addEventListener('click', async ()=>{
  if(!cart.length){ notifyShow('Agregue productos', 'danger'); return; }
  const fd = new FormData();
  fd.append(csrfName, csrfVal());
  fd.append('mode', document.getElementById('pos-mode').value);
  fd.append('client_id', document.getElementById('pos-client').value);
  fd.append('branch_id', document.getElementById('pos-branch').value);
  fd.append('walk_in', document.getElementById('pos-walkin').checked ? '1' : '');
  fd.append('notes', document.getElementById('pos-notes').value);
  fd.append('quote_status', document.getElementById('pos-qstatus').value);
  fd.append('items_json', JSON.stringify(cart));
  const r = await fetch('<?= route_to('sales.pos.checkout') ?>', {method:'POST', headers: ajaxHeaders(), body: fd});
  csrfHdr(r.headers);
  const j = await r.json();
  notifyShow(j.message, j.success?'success':'danger');
  if(!j.success) return;
  cart = []; renderCart();
  const d = j.response || {};
  let html = `<div class="alert alert-success"><strong>${d.number||''}</strong>`;
  if(d.pdf) html += `<div class="mt-2"><a class="btn btn-sm btn-outline-danger" target="_blank" href="${d.pdf}">PDF</a> <a class="btn btn-sm btn-outline-secondary" target="_blank" href="${d.pdf}/html">Vista previa</a></div>`;
  if(d.receipt_pdf) html += `<div class="mt-1"><a class="btn btn-sm btn-outline-dark" target="_blank" href="${d.receipt_pdf}">Recibo PDF</a></div>`;
  html += '</div>';
  document.getElementById('pos-result').innerHTML = html;
});
</script>
<?php $this->endSection(); ?>
