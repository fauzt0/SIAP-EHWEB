<?php $this->extend($layout); ?>
<?php $this->section('title'); echo esc($headTitle); $this->endSection(); ?>
<?php $this->section('main'); ?>
<style>
#oc-followup-tree { border-left: 2px solid #e9ecef; margin-left: 10px; }
#oc-followup-tree .fu-item { position: relative; padding-left: 28px; margin-bottom: 1rem; }
#oc-followup-tree .fu-dot { position: absolute; left: -8px; top: 6px; width: 14px; height: 14px; border-radius: 50%; background: #3f80ea; border: 2px solid #fff; }
</style>
<main class="content">
  <div class="container-fluid p-0">
    <div class="row mb-2 mb-xl-3">
      <div class="col-auto d-none d-sm-block">
        <h1 class="h3 mb-3"><i class="fas fa-fw fa-address-book me-2 text-primary"></i><?= esc($pageTitle) ?></h1>
      </div>
      <div class="col-auto ms-auto text-end mt-n1">
        <nav aria-label="breadcrumb"><?= $breadcrumb ?? '' ?></nav>
      </div>
    </div>

    <div class="row mb-3">
      <?php
      $cards = [
        ['label' => 'Total', 'key' => 'total', 'icon' => 'fa-users', 'color' => 'primary'],
        ['label' => 'Leads', 'key' => 'leads', 'icon' => 'fa-user-plus', 'color' => 'info'],
        ['label' => 'Prospectos', 'key' => 'prospects', 'icon' => 'fa-filter', 'color' => 'warning'],
        ['label' => 'Activos', 'key' => 'active', 'icon' => 'fa-handshake', 'color' => 'success'],
      ];
      foreach ($cards as $card): ?>
      <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="card">
          <div class="card-body">
            <div class="float-end"><i class="fas <?= $card['icon'] ?> fa-2x text-<?= $card['color'] ?>"></i></div>
            <h5 class="card-title mb-0"><?= $card['label'] ?></h5>
            <h3 class="mt-2 mb-0"><?= (int) ($response['stats'][$card['key']] ?? 0) ?></h3>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Directorio</h5>
        <div class="d-flex gap-2">
          <select id="filter-status" class="form-select form-select-sm" style="width:auto">
            <option value="">Todos los estatus</option>
            <option value="lead">Lead</option>
            <option value="prospect">Prospecto</option>
            <option value="active">Activo</option>
            <option value="inactive">Inactivo</option>
            <option value="churned">Baja</option>
          </select>
          <?php if (!empty($response['canManage'])): ?>
          <button class="btn btn-primary btn-sm" id="btn-new-client"><i class="fas fa-plus me-1"></i>Alta</button>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-body">
        <table id="tbl-clients" class="table table-striped w-100">
          <thead>
            <tr>
              <th>Código</th><th>Empresa</th><th>RFC</th><th>Estatus</th><th>Email</th><th>Contactos</th><th>Activo</th><th></th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</main>

<div class="offcanvas offcanvas-end" tabindex="-1" id="oc-client" style="width:480px">
  <div class="offcanvas-header bg-primary text-white">
    <div>
      <h5 class="offcanvas-title mb-0" id="oc-client-name">Cliente</h5>
      <small id="oc-client-code" class="opacity-75"></small>
    </div>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <ul class="nav nav-tabs nav-justified mb-3" role="tablist">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#oc-tab-datos" type="button"><i class="fas fa-id-card me-1"></i>Datos</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#oc-tab-fiscal" type="button"><i class="fas fa-file-invoice me-1"></i>Fiscal</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#oc-tab-seg" type="button"><i class="fas fa-comments me-1"></i>Seguimiento</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#oc-tab-ventas" type="button"><i class="fas fa-cash-register me-1"></i>Ventas</button></li>
    </ul>
    <div class="tab-content">
      <div class="tab-pane fade show active" id="oc-tab-datos">
        <dl class="row small mb-0" id="oc-datos"></dl>
        <h6 class="mt-3">Contactos</h6>
        <div id="oc-contacts"></div>
        <div id="oc-plugin-tabs" class="mt-3"></div>
      </div>
      <div class="tab-pane fade" id="oc-tab-fiscal"><div id="oc-billing"></div></div>
      <div class="tab-pane fade" id="oc-tab-seg">
        <div class="row g-2 mb-2">
          <div class="col"><input type="date" class="form-control form-control-sm" id="fu-from"></div>
          <div class="col"><input type="date" class="form-control form-control-sm" id="fu-to"></div>
          <div class="col-auto"><button class="btn btn-sm btn-outline-primary" id="btn-fu-filter"><i class="fas fa-search"></i></button></div>
        </div>
        <?php if (!empty($response['canManage'])): ?>
        <button class="btn btn-sm btn-outline-primary w-100 mb-3" id="btn-new-followup"><i class="fas fa-plus me-1"></i>Nueva revisión</button>
        <?php endif; ?>
        <div id="oc-followup-tree"></div>
      </div>
      <div class="tab-pane fade" id="oc-tab-ventas">
        <h6>Cotizaciones</h6><div id="oc-quotes"></div>
        <h6 class="mt-3">Órdenes</h6><div id="oc-orders"></div>
        <h6 class="mt-3">Servicios</h6><div id="oc-services"></div>
        <h6 class="mt-3">Recibos</h6><div id="oc-receipts"></div>
        <h6 class="mt-3">Comprobantes</h6><div id="oc-proofs"></div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-client" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" id="form-client">
      <div class="modal-header">
        <h5 class="modal-title">Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="client-id">
        <ul class="nav nav-tabs mb-3">
          <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#f-gen" type="button">General</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#f-con" type="button">Contactos</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#f-bill" type="button">Facturación</button></li>
        </ul>
        <div class="tab-content">
          <div class="tab-pane fade show active" id="f-gen">
            <div class="row g-2">
              <div class="col-md-8"><label class="form-label">Empresa *</label><input class="form-control" name="company_name" required></div>
              <div class="col-md-4"><label class="form-label">Estatus</label>
                <select class="form-select" name="status">
                  <option value="lead">Lead</option>
                  <option value="prospect">Prospecto</option>
                  <option value="active">Activo</option>
                  <option value="inactive">Inactivo</option>
                  <option value="churned">Baja</option>
                </select>
              </div>
              <div class="col-md-6"><label class="form-label">Nombre comercial</label><input class="form-control" name="trade_name"></div>
              <div class="col-md-6"><label class="form-label">RFC</label><input class="form-control" name="rfc_tax_id"></div>
              <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email"></div>
              <div class="col-md-6"><label class="form-label">Teléfono</label><input class="form-control" name="phone"></div>
              <div class="col-md-6"><label class="form-label">Sitio web</label><input class="form-control" name="website"></div>
              <div class="col-md-6"><label class="form-label">Origen</label><input class="form-control" name="source"></div>
              <div class="col-12"><label class="form-label">Notas</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
              <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="active" id="client-active" checked><label class="form-check-label" for="client-active">Activo</label></div></div>
            </div>
          </div>
          <div class="tab-pane fade" id="f-con">
            <div id="contacts-rows"></div>
            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btn-add-contact"><i class="fas fa-plus me-1"></i>Contacto</button>
          </div>
          <div class="tab-pane fade" id="f-bill">
            <div id="billing-rows"></div>
            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btn-add-billing"><i class="fas fa-plus me-1"></i>Perfil fiscal</button>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modal-followup" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="form-followup">
      <div class="modal-header"><h5 class="modal-title">Revisión de seguimiento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="parent_followup_id" id="fu-parent">
        <div class="mb-2"><label class="form-label">Título *</label><input class="form-control" name="title" required></div>
        <div class="mb-2"><label class="form-label">Tipo</label>
          <select class="form-select" name="followup_type">
            <option value="comment">Comentario</option>
            <option value="call">Llamada</option>
            <option value="meeting">Reunión</option>
            <option value="email">Email</option>
            <option value="status_change">Cambio de estatus</option>
            <option value="service">Servicio</option>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Estatus del cliente</label>
          <select class="form-select" name="client_status" id="fu-status">
            <option value="lead">Lead</option>
            <option value="prospect">Prospecto</option>
            <option value="active">Activo</option>
            <option value="inactive">Inactivo</option>
            <option value="churned">Baja</option>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Comentario</label><textarea class="form-control" name="comment" rows="3"></textarea></div>
        <div class="mb-2"><label class="form-label">Servicios involucrados</label>
          <select class="form-select" id="fu-products" multiple>
            <?php foreach ($response['products'] ?? [] as $p): ?>
              <option value="<?= (int) $p->id ?>"><?= esc($p->commercial_name) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary" type="submit">Registrar revisión</button></div>
    </form>
  </div>
</div>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
const csrfName = '<?= csrf_token() ?>';
function csrfVal(){ return document.getElementById('csrf_token').value; }
function csrfHdr(h){ const n = h.get('<?= csrf_header() ?>'); if(n) document.getElementById('csrf_token').value = n; }
function ajaxHeaders(){ return { 'X-Requested-With':'XMLHttpRequest', '<?= csrf_header() ?>': csrfVal() }; }
let currentClientId = 0;
let followupCache = [];

if (!$.fn.dataTable.isDataTable('#tbl-clients')) {
window.tblClients = $('#tbl-clients').DataTable({
  processing: true, serverSide: true, searching: true,
  ajax: {
    url: '<?= route_to('crm.clients.ajax') ?>',
    type: 'POST',
    data: function(d){
      d[csrfName] = csrfVal();
      d.filter_status = document.getElementById('filter-status').value;
    }
  }
});
$('#tbl-clients').on('xhr.dt', function(e,s,j,xhr){ if(xhr) csrfHdr(xhr); });
}
document.getElementById('filter-status').addEventListener('change', () => tblClients.ajax.reload());

document.getElementById('btn-new-client')?.addEventListener('click', () => {
  document.getElementById('form-client').reset();
  document.getElementById('client-id').value = '';
  document.getElementById('contacts-rows').innerHTML = '';
  document.getElementById('billing-rows').innerHTML = '';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-client')).show();
});

function addContactRow(c={}){
  const wrap = document.getElementById('contacts-rows');
  const div = document.createElement('div');
  div.className = 'row g-2 mb-2 contact-row';
  div.innerHTML = `<div class="col-md-3"><select class="form-select form-select-sm" name="ctype"><option value="buyer">Compras</option><option value="technical">Técnico</option><option value="billing">Facturación</option><option value="admin">Admin</option></select></div>
    <div class="col-md-3"><input class="form-control form-control-sm" name="cname" placeholder="Nombre" value="${c.name||''}"></div>
    <div class="col-md-3"><input class="form-control form-control-sm" name="cemail" placeholder="Email" value="${c.email||''}"></div>
    <div class="col-md-2"><input class="form-control form-control-sm" name="cphone" placeholder="Tel" value="${c.phone||''}"></div>
    <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger btn-rm">&times;</button></div>`;
  wrap.appendChild(div);
  if(c.contact_type) div.querySelector('[name=ctype]').value = c.contact_type;
  div.querySelector('.btn-rm').onclick = () => div.remove();
}
function addBillingRow(b={}){
  const wrap = document.getElementById('billing-rows');
  const div = document.createElement('div');
  div.className = 'border rounded p-2 mb-2 billing-row';
  div.innerHTML = `<div class="row g-2">
    <div class="col-md-6"><input class="form-control form-control-sm" name="blegal" placeholder="Razón social" value="${b.legal_name||''}"></div>
    <div class="col-md-6"><input class="form-control form-control-sm" name="brfc" placeholder="RFC" value="${b.rfc_tax_id||''}"></div>
    <div class="col-md-4"><input class="form-control form-control-sm" name="breg" placeholder="Régimen" value="${b.tax_regime||''}"></div>
    <div class="col-md-4"><input class="form-control form-control-sm" name="bcp" placeholder="C.P." value="${b.postal_code||''}"></div>
    <div class="col-md-4"><input class="form-control form-control-sm" name="bcfdi" placeholder="Uso CFDI" value="${b.cfdi_use||''}"></div>
    <div class="col-12"><input class="form-control form-control-sm" name="baddr" placeholder="Dirección" value="${b.address||''}"></div>
    <div class="col-12 text-end"><button type="button" class="btn btn-sm btn-outline-danger btn-rm">Quitar</button></div></div>`;
  wrap.appendChild(div);
  div.querySelector('.btn-rm').onclick = () => div.remove();
}
document.getElementById('btn-add-contact').onclick = () => addContactRow();
document.getElementById('btn-add-billing').onclick = () => addBillingRow();

function collectContacts(){
  return [...document.querySelectorAll('.contact-row')].map(r => ({
    contact_type: r.querySelector('[name=ctype]').value,
    name: r.querySelector('[name=cname]').value,
    email: r.querySelector('[name=cemail]').value,
    phone: r.querySelector('[name=cphone]').value
  })).filter(x => x.name);
}
function collectBilling(){
  return [...document.querySelectorAll('.billing-row')].map(r => ({
    legal_name: r.querySelector('[name=blegal]').value,
    rfc_tax_id: r.querySelector('[name=brfc]').value,
    tax_regime: r.querySelector('[name=breg]').value,
    postal_code: r.querySelector('[name=bcp]').value,
    cfdi_use: r.querySelector('[name=bcfdi]').value,
    address: r.querySelector('[name=baddr]').value
  })).filter(x => x.legal_name && x.rfc_tax_id);
}

document.getElementById('form-client').addEventListener('submit', async (ev) => {
  ev.preventDefault();
  const fd = new FormData(ev.target);
  fd.append('contacts_json', JSON.stringify(collectContacts()));
  fd.append('billing_json', JSON.stringify(collectBilling()));
  fd.append('active', document.getElementById('client-active').checked ? '1' : '');
  const id = document.getElementById('client-id').value;
  const url = id ? '<?= site_url('nat/crm/clients/update') ?>/' + id : '<?= route_to('crm.clients.store') ?>';
  const r = await fetch(url, { method:'POST', headers: ajaxHeaders(), body: fd });
  csrfHdr(r.headers);
  const j = await r.json();
  if(j.success){ bootstrap.Modal.getInstance(document.getElementById('modal-client')).hide(); tblClients.ajax.reload(); notifyShow(j.message, 'success'); }
  else notifyShow(j.message || 'Error', 'danger');
});

document.addEventListener('click', async (e) => {
  const show = e.target.closest('.btn-show-client');
  const edit = e.target.closest('.btn-edit-client');
  const del = e.target.closest('.btn-delete-client');
  if(show){ await openClient(show.dataset.id); }
  if(edit){
    const j = await loadClient(edit.dataset.id);
    if(!j) return;
    const c = j.response.client;
    const f = document.getElementById('form-client');
    f.reset();
    document.getElementById('client-id').value = c.id;
    f.company_name.value = c.company_name || '';
    f.trade_name.value = c.trade_name || '';
    f.rfc_tax_id.value = c.rfc_tax_id || '';
    f.email.value = c.email || '';
    f.phone.value = c.phone || '';
    f.website.value = c.website || '';
    f.status.value = c.status;
    f.source.value = c.source || '';
    f.notes.value = c.notes || '';
    document.getElementById('client-active').checked = Number(c.active) === 1;
    document.getElementById('contacts-rows').innerHTML = '';
    (j.response.contacts||[]).forEach(addContactRow);
    document.getElementById('billing-rows').innerHTML = '';
    (j.response.billing||[]).forEach(addBillingRow);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-client')).show();
  }
  if(del && confirm('¿Eliminar cliente?')){
    const r = await fetch('<?= site_url('nat/crm/clients/delete') ?>/' + del.dataset.id, { method:'POST', headers: ajaxHeaders(), body: new URLSearchParams({[csrfName]: csrfVal()}) });
    csrfHdr(r.headers);
    const j = await r.json();
    notifyShow(j.message, j.success ? 'success' : 'danger');
    tblClients.ajax.reload();
  }
});

async function loadClient(id){
  const r = await fetch('<?= site_url('nat/crm/clients/show_ajax') ?>/' + id, { headers: {'X-Requested-With':'XMLHttpRequest'} });
  const j = await r.json();
  if(!j.success){ notifyShow(j.message, 'danger'); return null; }
  return j;
}

async function openClient(id){
  const j = await loadClient(id);
  if(!j) return;
  currentClientId = id;
  renderOffcanvas(j.response);
  bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('oc-client')).show();
}

function renderOffcanvas(d){
  const c = d.client;
  document.getElementById('oc-client-name').textContent = c.company_name;
  document.getElementById('oc-client-code').textContent = c.client_code + ' · ' + c.status;
  document.getElementById('fu-status').value = c.status;
  document.getElementById('oc-datos').innerHTML = `
    <dt class="col-4">Email</dt><dd class="col-8">${c.email||'—'}</dd>
    <dt class="col-4">Teléfono</dt><dd class="col-8">${c.phone||'—'}</dd>
    <dt class="col-4">Web</dt><dd class="col-8">${c.website||'—'}</dd>
    <dt class="col-4">RFC</dt><dd class="col-8">${c.rfc_tax_id||'—'}</dd>
    <dt class="col-4">Origen</dt><dd class="col-8">${c.source||'—'}</dd>`;
  document.getElementById('oc-contacts').innerHTML = (d.contacts||[]).map(x => `<div class="border rounded p-2 mb-1 small"><strong>${x.name}</strong> · ${x.contact_type}<br>${x.email||''} ${x.phone||''}</div>`).join('') || '<p class="text-muted small">Sin contactos</p>';
  document.getElementById('oc-billing').innerHTML = (d.billing||[]).map(x => `<div class="border rounded p-2 mb-2 small"><strong>${x.legal_name}</strong><br>RFC ${x.rfc_tax_id} · ${x.tax_regime||''}<br>${x.address||''}</div>`).join('') || '<p class="text-muted">Sin perfiles fiscales</p>';
  followupCache = d.followups || [];
  renderFollowups(followupCache);
  document.getElementById('oc-quotes').innerHTML = (d.quotes||[]).map(x => `<div class="small">${x.quote_number} · ${x.status} · $${x.total_amount}</div>`).join('') || '<p class="text-muted small">Sin cotizaciones</p>';
  document.getElementById('oc-orders').innerHTML = (d.orders||[]).map(x => `<div class="small">${x.order_number||('#'+x.id)} · ${x.status} · $${x.total_amount}</div>`).join('') || '<p class="text-muted small">Sin órdenes</p>';
  document.getElementById('oc-services').innerHTML = (d.services||[]).map(x => `<div class="small">${x.reference_name||'Servicio'} · ${x.billing_cycle} · ${x.activation_status||''} · vence ${x.next_due_date||'—'}</div>`).join('') || '<p class="text-muted small">Sin servicios</p>';
  document.getElementById('oc-receipts').innerHTML = (d.receipts||[]).map(x => `<div class="small">${x.receipt_number} · $${x.total_amount} · ${x.status}</div>`).join('') || '<p class="text-muted small">Sin recibos</p>';
  document.getElementById('oc-proofs').innerHTML = (d.proofs||[]).map(x => `<div class="small"><a href="<?= site_url('nat/sales/billing/proof') ?>/${x.id}">${x.original_name}</a></div>`).join('') || '<p class="text-muted small">Sin comprobantes</p>';
  const pluginHtml = (d.plugin_tabs||[]).map(t => `<div class="alert alert-light border small"><strong>${t.title||t.hook||'Plugin'}</strong><div>${t.html||''}</div></div>`).join('');
  document.getElementById('oc-plugin-tabs').innerHTML = pluginHtml;
}

function renderFollowups(list){
  const from = document.getElementById('fu-from').value;
  const to = document.getElementById('fu-to').value;
  const filtered = list.filter(f => {
    const d = (f.created_at||'').slice(0,10);
    if(from && d < from) return false;
    if(to && d > to) return false;
    return true;
  });
  const html = filtered.map(f => {
    const svcs = (f.services||[]).map(s => s.product_name || ('#'+s.product_id)).join(', ');
    const vigente = f.status === 'active' ? '<span class="badge bg-success">Vigente</span>' : '';
    return `<div class="fu-item">
      <span class="fu-dot"></span>
      <div class="fw-semibold">${f.title||''} ${vigente}</div>
      <div class="small text-muted">${f.followup_type} · v${f.version} · ${(f.created_at||'').slice(0,10)}</div>
      <div class="small">${f.comment||''}</div>
      ${svcs ? `<div class="small"><i class="fas fa-cubes me-1"></i>${svcs}</div>` : ''}
      <button type="button" class="btn btn-sm btn-outline-primary mt-1 btn-fork" data-id="${f.id}"><i class="fas fa-code-branch me-1"></i>Revisión</button>
    </div>`;
  }).join('');
  document.getElementById('oc-followup-tree').innerHTML = html || '<p class="text-muted small">Sin seguimiento</p>';
}
document.getElementById('btn-fu-filter').onclick = () => renderFollowups(followupCache);
document.getElementById('btn-new-followup')?.addEventListener('click', () => {
  document.getElementById('form-followup').reset();
  document.getElementById('fu-parent').value = '';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-followup')).show();
});
document.addEventListener('click', (e) => {
  const fork = e.target.closest('.btn-fork');
  if(!fork) return;
  document.getElementById('fu-parent').value = fork.dataset.id;
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-followup')).show();
});
document.getElementById('form-followup').addEventListener('submit', async (ev) => {
  ev.preventDefault();
  const fd = new FormData(ev.target);
  const pids = [...document.getElementById('fu-products').selectedOptions].map(o => ({product_id: o.value}));
  fd.append('services_json', JSON.stringify(pids));
  const r = await fetch('<?= site_url('nat/crm/clients') ?>/' + currentClientId + '/followups', { method:'POST', headers: ajaxHeaders(), body: fd });
  csrfHdr(r.headers);
  const j = await r.json();
  if(j.success){
    bootstrap.Modal.getInstance(document.getElementById('modal-followup')).hide();
    followupCache = j.response.followups || [];
    renderFollowups(followupCache);
    tblClients.ajax.reload(null, false);
    notifyShow(j.message, 'success');
  } else notifyShow(j.message, 'danger');
});
</script>
<?php $this->endSection(); ?>
