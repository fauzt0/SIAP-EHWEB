<?php $this->extend($layout); ?>
<?php $this->section('title'); echo esc($headTitle); $this->endSection(); ?>
<?php $this->section('main'); ?>
<main class="content">
  <div class="container-fluid p-0">
    <div class="row mb-3">
      <div class="col"><h1 class="h3"><i class="fas fa-receipt me-2 text-primary"></i><?= esc($pageTitle) ?></h1></div>
      <div class="col-auto"><?php if (auth()->user()->can('finance.transactions')): ?>
        <button class="btn btn-primary btn-sm" id="btn-new-exp">Nuevo gasto</button>
      <?php endif; ?></div>
    </div>
    <div class="card"><div class="card-body">
      <table id="tbl-exp" class="table table-striped w-100">
        <thead><tr><th>Vence</th><th>Concepto</th><th>Periodo</th><th>Total</th><th>Pagado</th><th>Estatus</th><th></th></tr></thead>
      </table>
    </div></div>
  </div>
</main>
<div class="modal fade" id="modal-exp" tabindex="-1">
  <div class="modal-dialog"><form class="modal-content" id="form-exp">
    <div class="modal-header"><h5 class="modal-title">Gasto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <label class="form-label">Categoría</label>
      <select class="form-select mb-2" name="category_id" required>
        <?php foreach (['fixed'=>'Fijos','variable'=>'Variables'] as $k=>$lbl): ?>
          <optgroup label="<?= esc($lbl) ?>">
          <?php foreach (($response['categories'][$k] ?? []) as $c): ?>
            <option value="<?= (int)$c->id ?>"><?= esc($c->name) ?></option>
          <?php endforeach; ?>
          </optgroup>
        <?php endforeach; ?>
      </select>
      <label class="form-label">Monto</label>
      <input type="number" step="0.01" min="0" class="form-control mb-2" name="total_amount" required>
      <label class="form-label">Fecha de vencimiento</label>
      <input type="date" class="form-control mb-2" name="due_date" value="<?= date('Y-m-d') ?>">
      <label class="form-label">Notas / concepto</label>
      <textarea class="form-control" name="notes"></textarea>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Guardar</button></div>
  </form></div>
</div>
<div class="modal fade" id="modal-pay-exp" tabindex="-1">
  <div class="modal-dialog"><form class="modal-content" id="form-pay-exp">
    <div class="modal-header"><h5 class="modal-title">Pagar gasto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input type="hidden" id="pay-exp-id">
      <label class="form-label">Monto</label>
      <input type="number" step="0.01" class="form-control mb-2" name="paid_amount" id="pay-exp-amount" required>
      <label class="form-label">Cuenta</label>
      <select class="form-select" name="account_id">
        <?php foreach ($response['accounts'] ?? [] as $a): ?>
          <option value="<?= (int)$a->id ?>"><?= esc($a->name) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Registrar pago</button></div>
  </form></div>
</div>
<?php $this->endSection(); ?>
<?php $this->section('scripts'); ?>
<script>
const csrfName = '<?= csrf_token() ?>';
function csrfVal(){ return document.getElementById('csrf_token').value; }
function csrfHdr(h){ const n=h.get('<?= csrf_header() ?>'); if(n) document.getElementById('csrf_token').value=n; }
function ajaxHeaders(){ return {'X-Requested-With':'XMLHttpRequest','<?= csrf_header() ?>': csrfVal()}; }
if (!$.fn.dataTable.isDataTable('#tbl-exp')) {
  window.tblExp = $('#tbl-exp').DataTable({
    processing:true, serverSide:true,
    ajax:{ url:'<?= route_to('accounting.expenses.ajax') ?>', type:'POST', data:d=>{ d[csrfName]=csrfVal(); } }
  });
  $('#tbl-exp').on('xhr.dt', (e,s,j,xhr)=>{ if(xhr) csrfHdr(xhr); });
}
document.getElementById('btn-new-exp')?.addEventListener('click', ()=>{
  document.getElementById('form-exp').reset();
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-exp')).show();
});
document.getElementById('form-exp').addEventListener('submit', async ev=>{
  ev.preventDefault();
  const r = await fetch('<?= route_to('accounting.expenses.store') ?>', {method:'POST', headers: ajaxHeaders(), body: new FormData(ev.target)});
  csrfHdr(r.headers); const j = await r.json();
  if(j.success){ bootstrap.Modal.getInstance(document.getElementById('modal-exp')).hide(); tblExp.ajax.reload(); notifyShow(j.message,'success'); }
  else notifyShow(j.message,'danger');
});
document.addEventListener('click', e=>{
  const b = e.target.closest('.btn-pay-exp');
  if(!b) return;
  document.getElementById('pay-exp-id').value = b.dataset.id;
  document.getElementById('pay-exp-amount').value = b.dataset.amount;
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-pay-exp')).show();
});
document.getElementById('form-pay-exp').addEventListener('submit', async ev=>{
  ev.preventDefault();
  const id = document.getElementById('pay-exp-id').value;
  const r = await fetch('<?= site_url('nat/accounting/expenses/mark_paid') ?>/' + id, {method:'POST', headers: ajaxHeaders(), body: new FormData(ev.target)});
  csrfHdr(r.headers); const j = await r.json();
  if(j.success){ bootstrap.Modal.getInstance(document.getElementById('modal-pay-exp')).hide(); tblExp.ajax.reload(); notifyShow(j.message,'success'); }
  else notifyShow(j.message,'danger');
});
</script>
<?php $this->endSection(); ?>
