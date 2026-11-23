<?php $this->extend($layout); ?>
<?php $this->section('title'); echo esc($headTitle); $this->endSection(); ?>
<?php $this->section('main'); ?>
<main class="content">
  <div class="container-fluid p-0">
    <div class="row mb-3">
      <div class="col"><h1 class="h3"><i class="fas fa-file-invoice me-2 text-primary"></i><?= esc($pageTitle) ?></h1></div>
      <div class="col-auto"><?php if (auth()->user()->can('finance.transactions')): ?>
        <button class="btn btn-primary btn-sm" id="btn-new-sinv">Factura de proveedor</button>
      <?php endif; ?></div>
    </div>
    <ul class="nav nav-tabs mb-3" role="tablist">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-prov">Proveedores</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sales">Ventas (emitir)</button></li>
    </ul>
    <div class="tab-content">
      <div class="tab-pane fade show active" id="tab-prov">
        <div class="card"><div class="card-body">
          <table id="tbl-sinv" class="table table-striped w-100">
            <thead><tr><th>Folio</th><th>Proveedor</th><th>Factura / OC</th><th>Fecha</th><th>Total</th><th>Pagado</th><th>Estatus</th><th></th></tr></thead>
          </table>
        </div></div>
      </div>
      <div class="tab-pane fade" id="tab-sales">
        <div class="card"><div class="card-body">
          <table id="tbl-vinv" class="table table-striped w-100">
            <thead><tr><th>Número</th><th>Cliente</th><th>Orden</th><th>Total</th><th>Estatus</th><th>Fecha</th><th></th></tr></thead>
          </table>
        </div></div>
      </div>
    </div>
  </div>
</main>
<div class="modal fade" id="modal-sinv" tabindex="-1">
  <div class="modal-dialog"><form class="modal-content" id="form-sinv">
    <div class="modal-header"><h5 class="modal-title">Factura de proveedor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <label class="form-label">Proveedor</label>
      <select class="form-select mb-2" name="supplier_id" required>
        <option value="">—</option>
        <?php foreach ($response['suppliers'] ?? [] as $s): $s=(object)$s; ?>
          <option value="<?= (int)$s->id ?>"><?= esc($s->commercial_name) ?></option>
        <?php endforeach; ?>
      </select>
      <label class="form-label">Orden de compra (opcional)</label>
      <select class="form-select mb-2" name="purchase_order_id">
        <option value="">—</option>
        <?php foreach ($response['orders'] ?? [] as $o): $o=(object)$o; ?>
          <option value="<?= (int)$o->id ?>"><?= esc($o->po_number) ?> (<?= number_format((float)$o->total_amount,2) ?>)</option>
        <?php endforeach; ?>
      </select>
      <div class="row g-2">
        <div class="col-md-6"><label class="form-label">Número de factura</label><input class="form-control" name="invoice_number"></div>
        <div class="col-md-6"><label class="form-label">Total</label><input type="number" step="0.01" class="form-control" name="total" required></div>
        <div class="col-md-6"><label class="form-label">Fecha</label><input type="date" class="form-control" name="invoice_date" value="<?= date('Y-m-d') ?>"></div>
        <div class="col-md-6"><label class="form-label">Vence</label><input type="date" class="form-control" name="due_date"></div>
      </div>
      <label class="form-label mt-2">Cuenta de pago</label>
      <select class="form-select" name="account_id">
        <?php foreach ($response['accounts'] ?? [] as $a): ?>
          <option value="<?= (int)$a->id ?>"><?= esc($a->name) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Guardar</button></div>
  </form></div>
</div>
<div class="modal fade" id="modal-pay-inv" tabindex="-1">
  <div class="modal-dialog"><form class="modal-content" id="form-pay-inv">
    <div class="modal-header"><h5 class="modal-title">Pagar factura</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input type="hidden" id="pay-inv-id">
      <label class="form-label">Monto</label>
      <input type="number" step="0.01" class="form-control mb-2" name="paid_amount" id="pay-inv-amount" required>
      <label class="form-label">Cuenta</label>
      <select class="form-select" name="account_id">
        <?php foreach ($response['accounts'] ?? [] as $a): ?>
          <option value="<?= (int)$a->id ?>"><?= esc($a->name) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Pagar</button></div>
  </form></div>
</div>
<?php $this->endSection(); ?>
<?php $this->section('scripts'); ?>
<script>
const csrfName = '<?= csrf_token() ?>';
function csrfVal(){ return document.getElementById('csrf_token').value; }
function csrfHdr(h){ const n=h.get('<?= csrf_header() ?>'); if(n) document.getElementById('csrf_token').value=n; }
function ajaxHeaders(){ return {'X-Requested-With':'XMLHttpRequest','<?= csrf_header() ?>': csrfVal()}; }
if (!$.fn.dataTable.isDataTable('#tbl-sinv')) {
  window.tblSinv = $('#tbl-sinv').DataTable({
    processing:true, serverSide:true,
    ajax:{ url:'<?= route_to('accounting.invoices.supplier_ajax') ?>', type:'POST', data:d=>{ d[csrfName]=csrfVal(); } }
  });
  $('#tbl-sinv').on('xhr.dt', (e,s,j,xhr)=>{ if(xhr) csrfHdr(xhr); });
}
if (!$.fn.dataTable.isDataTable('#tbl-vinv')) {
  window.tblVinv = $('#tbl-vinv').DataTable({
    processing:true, serverSide:true,
    ajax:{ url:'<?= route_to('accounting.invoices.sales_ajax') ?>', type:'POST', data:d=>{ d[csrfName]=csrfVal(); } }
  });
  $('#tbl-vinv').on('xhr.dt', (e,s,j,xhr)=>{ if(xhr) csrfHdr(xhr); });
}
document.getElementById('btn-new-sinv')?.addEventListener('click', ()=>{
  document.getElementById('form-sinv').reset();
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-sinv')).show();
});
document.getElementById('form-sinv').addEventListener('submit', async ev=>{
  ev.preventDefault();
  const r = await fetch('<?= route_to('accounting.invoices.supplier.store') ?>', {method:'POST', headers: ajaxHeaders(), body: new FormData(ev.target)});
  csrfHdr(r.headers); const j = await r.json();
  if(j.success){ bootstrap.Modal.getInstance(document.getElementById('modal-sinv')).hide(); tblSinv.ajax.reload(); notifyShow(j.message,'success'); }
  else notifyShow(j.message,'danger');
});
document.addEventListener('click', async e=>{
  const pay = e.target.closest('.btn-pay-inv');
  if(pay){
    document.getElementById('pay-inv-id').value = pay.dataset.id;
    document.getElementById('pay-inv-amount').value = pay.dataset.due;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-pay-inv')).show();
    return;
  }
  const issue = e.target.closest('.btn-issue-sale');
  if(!issue) return;
  const fd = new FormData(); fd.append(csrfName, csrfVal());
  const r = await fetch('<?= site_url('nat/accounting/invoices/sales/issue') ?>/' + issue.dataset.id, {method:'POST', headers: ajaxHeaders(), body: fd});
  csrfHdr(r.headers); const j = await r.json();
  notifyShow(j.message, j.success?'success':'danger');
  if(j.success) tblVinv.ajax.reload();
});
document.getElementById('form-pay-inv').addEventListener('submit', async ev=>{
  ev.preventDefault();
  const id = document.getElementById('pay-inv-id').value;
  const r = await fetch('<?= site_url('nat/accounting/invoices/supplier/pay') ?>/' + id, {method:'POST', headers: ajaxHeaders(), body: new FormData(ev.target)});
  csrfHdr(r.headers); const j = await r.json();
  if(j.success){ bootstrap.Modal.getInstance(document.getElementById('modal-pay-inv')).hide(); tblSinv.ajax.reload(); notifyShow(j.message,'success'); }
  else notifyShow(j.message,'danger');
});
</script>
<?php $this->endSection(); ?>
