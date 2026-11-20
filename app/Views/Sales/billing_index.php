<?php $this->extend($layout); ?>
<?php $this->section('title'); echo esc($headTitle); $this->endSection(); ?>
<?php $this->section('main'); ?>
<main class="content">
  <div class="container-fluid p-0">
    <div class="row mb-3">
      <div class="col">
        <h1 class="h3"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i><?= esc($pageTitle) ?></h1>
        <p class="text-muted mb-0">Recibo interno y factura en borrador. El timbrado CFDI se conectará después.</p>
      </div>
      <?php if (auth()->user()->can('sales.billing')): ?>
      <div class="col-auto"><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-issue"><i class="fas fa-plus me-1"></i>Emitir recibo</button></div>
      <?php endif; ?>
    </div>
    <div class="card"><div class="card-body">
      <table id="tbl-receipts" class="table table-striped w-100">
        <thead><tr><th>Recibo</th><th>Cliente</th><th>Orden</th><th>Total</th><th>Recibo</th><th>Orden</th><th></th></tr></thead>
      </table>
    </div></div>
  </div>
</main>

<div class="modal fade" id="modal-issue" tabindex="-1">
  <div class="modal-dialog"><form class="modal-content" id="form-issue">
    <div class="modal-header"><h5 class="modal-title">Emitir recibo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <label class="form-label">Orden</label>
      <select class="form-select" name="order_id" required>
        <option value="">—</option>
        <?php foreach ($response['orders'] ?? [] as $o): ?>
          <option value="<?= (int) $o->id ?>"><?= esc(($o->order_number ?: '#'.$o->id).' · '.($o->company_name ?? '').' · $'.$o->total_amount) ?></option>
        <?php endforeach; ?>
      </select>
      <label class="form-label mt-2">Notas</label>
      <textarea class="form-control" name="notes" rows="2"></textarea>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Emitir</button></div>
  </form></div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="oc-receipt" style="width:460px">
  <div class="offcanvas-header bg-primary text-white">
    <h5 class="offcanvas-title" id="oc-receipt-title">Recibo</h5>
    <button class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body" id="oc-receipt-body"></div>
</div>

<div class="modal fade" id="modal-pay" tabindex="-1">
  <div class="modal-dialog"><form class="modal-content" id="form-pay">
    <div class="modal-header"><h5 class="modal-title">Registrar cobro</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input type="hidden" name="order_id" id="pay-order">
      <input type="hidden" name="receipt_id" id="pay-receipt">
      <label class="form-label">Importe</label>
      <input class="form-control" name="amount_paid" type="number" step="0.01" required>
      <label class="form-label mt-2">Método</label>
      <select class="form-select" name="payment_method">
        <option value="transfer">Transferencia</option>
        <option value="cash">Efectivo</option>
        <option value="pos_terminal">Terminal</option>
        <option value="mercadopago">Mercado Pago</option>
      </select>
      <label class="form-label mt-2">Referencia</label>
      <input class="form-control" name="transaction_id">
    </div>
    <div class="modal-footer"><button class="btn btn-success">Guardar cobro</button></div>
  </form></div>
</div>

<div class="modal fade" id="modal-proof" tabindex="-1">
  <div class="modal-dialog"><form class="modal-content" id="form-proof" enctype="multipart/form-data">
    <div class="modal-header"><h5 class="modal-title">Comprobante del cliente</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input type="hidden" name="client_id" id="proof-client">
      <input type="hidden" name="order_id" id="proof-order">
      <input type="hidden" name="receipt_id" id="proof-receipt">
      <input class="form-control" type="file" name="proof_file" accept="application/pdf,image/*" required>
      <textarea class="form-control mt-2" name="notes" placeholder="Notas"></textarea>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Cargar</button></div>
  </form></div>
</div>
<?php $this->endSection(); ?>
<?php $this->section('scripts'); ?>
<script>
const csrfName = '<?= csrf_token() ?>';
function csrfVal(){ return document.getElementById('csrf_token').value; }
function csrfHdr(h){ const n=h.get('<?= csrf_header() ?>'); if(n) document.getElementById('csrf_token').value=n; }
function ajaxHeaders(){ return {'X-Requested-With':'XMLHttpRequest','<?= csrf_header() ?>': csrfVal()}; }
if (!$.fn.dataTable.isDataTable('#tbl-receipts')) {
  window.tblReceipts = $('#tbl-receipts').DataTable({
    processing:true, serverSide:true, searching:false,
    ajax:{ url:'<?= route_to('sales.billing.ajax') ?>', type:'POST', data:d=>{ d[csrfName]=csrfVal(); } }
  });
  $('#tbl-receipts').on('xhr.dt', (e,s,j,xhr)=>{ if(xhr) csrfHdr(xhr); });
}
let currentReceipt = null;
document.getElementById('form-issue').addEventListener('submit', async ev=>{
  ev.preventDefault();
  const r = await fetch('<?= route_to('sales.billing.issue') ?>', {method:'POST', headers: ajaxHeaders(), body: new FormData(ev.target)});
  csrfHdr(r.headers);
  const j = await r.json();
  notifyShow(j.message, j.success?'success':'danger');
  if(j.success){ bootstrap.Modal.getInstance(document.getElementById('modal-issue')).hide(); tblReceipts.ajax.reload(); }
});
document.addEventListener('click', async e=>{
  const btn = e.target.closest('.btn-show-receipt');
  if(!btn) return;
  const r = await fetch('<?= site_url('nat/sales/billing/show_ajax') ?>/'+btn.dataset.id, {headers:{'X-Requested-With':'XMLHttpRequest'}});
  const j = await r.json();
  if(!j.success){ notifyShow(j.message,'danger'); return; }
  currentReceipt = j.response;
  const rec = j.response.receipt;
  document.getElementById('oc-receipt-title').textContent = rec.receipt_number;
  const items = (rec.items||[]).map(i=>`<div class="small">${i.item_name} · ${i.billing_cycle||''} · $${i.unit_price} · ${i.activation_status||''}</div>`).join('');
  const pays = (j.response.payments||[]).map(p=>`<div class="small">${p.payment_date||''} · ${p.payment_method} · $${p.amount_paid}</div>`).join('') || '<p class="text-muted small">Sin cobros</p>';
  const proofs = (j.response.proofs||[]).map(p=>`<div class="small"><a href="<?= site_url('nat/sales/billing/proof') ?>/${p.id}">${p.original_name}</a></div>`).join('') || '<p class="text-muted small">Sin comprobantes</p>';
  document.getElementById('oc-receipt-body').innerHTML = `
    <p class="mb-1"><strong>${rec.company_name||''}</strong> · ${rec.client_code||''}</p>
    <p class="small">Orden ${rec.order_number||rec.order_id} · Factura borrador #${rec.invoice_id||'—'}</p>
    <p>Total $${Number(rec.total_amount).toFixed(2)} · Pagado $${Number(j.response.paid).toFixed(2)} · Saldo $${Number(j.response.balance).toFixed(2)}</p>
    <h6>Partidas</h6>${items}
    <h6 class="mt-3">Cobros</h6>${pays}
    <h6 class="mt-3">Comprobantes</h6>${proofs}
    <div class="d-flex gap-2 mt-3">
      <?php if (auth()->user()->can('sales.collections')): ?>
      <button class="btn btn-sm btn-success" id="btn-open-pay">Cobro</button>
      <button class="btn btn-sm btn-outline-primary" id="btn-open-proof">Comprobante</button>
      <?php endif; ?>
    </div>`;
  bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('oc-receipt')).show();
});
document.addEventListener('click', e=>{
  if(e.target.id==='btn-open-pay' && currentReceipt){
    document.getElementById('pay-order').value = currentReceipt.receipt.order_id;
    document.getElementById('pay-receipt').value = currentReceipt.receipt.id;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-pay')).show();
  }
  if(e.target.id==='btn-open-proof' && currentReceipt){
    document.getElementById('proof-client').value = currentReceipt.receipt.client_id;
    document.getElementById('proof-order').value = currentReceipt.receipt.order_id;
    document.getElementById('proof-receipt').value = currentReceipt.receipt.id;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-proof')).show();
  }
});
document.getElementById('form-pay').addEventListener('submit', async ev=>{
  ev.preventDefault();
  const r = await fetch('<?= route_to('sales.billing.pay') ?>', {method:'POST', headers: ajaxHeaders(), body: new FormData(ev.target)});
  csrfHdr(r.headers);
  const j = await r.json();
  notifyShow(j.message, j.success?'success':'danger');
  if(j.success) bootstrap.Modal.getInstance(document.getElementById('modal-pay')).hide();
});
document.getElementById('form-proof').addEventListener('submit', async ev=>{
  ev.preventDefault();
  const r = await fetch('<?= route_to('sales.billing.proof') ?>', {method:'POST', headers: ajaxHeaders(), body: new FormData(ev.target)});
  csrfHdr(r.headers);
  const j = await r.json();
  notifyShow(j.message, j.success?'success':'danger');
  if(j.success) bootstrap.Modal.getInstance(document.getElementById('modal-proof')).hide();
});
</script>
<?php $this->endSection(); ?>
