<?php $this->extend('Layouts/user_loggedin_layout'); ?>

<?php $this->section('title'); echo esc($headTitle ?? 'Órdenes de Compra'); $this->endSection(); ?>

<?php
$response = $response ?? [];
$canManage = auth()->user()->can('catalog.manage-suppliers');
$canDocs   = auth()->user()->can('catalog.po-documents');
$canEmail  = auth()->user()->can('catalog.po-send-email');
?>

<?php $this->section('main') ?>
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

<main class="content">
    <div class="container-fluid p-0">
        <div class="row mb-2 mb-xl-3">
            <div class="col-auto d-none d-sm-block">
                <h1 class="h3 mb-3">
                    <i class="fas fa-fw fa-file-invoice-dollar me-2 text-primary"></i><?= esc($pageTitle ?? 'Órdenes de Compra') ?>
                </h1>
            </div>
            <div class="col-auto ms-auto text-end mt-n1">
                <nav aria-label="breadcrumb"><?= $breadcrumb ?? '' ?></nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Listado de Órdenes de Compra</h5>
                            <?php if ($canManage): ?>
                            <button type="button" class="btn btn-success btn-sm" id="btn-new-po">
                                <i class="fas fa-plus me-1"></i>Nueva OC
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <select id="filter-status" class="form-select form-select-sm">
                                    <option value="">— Todos los estatus —</option>
                                    <?php foreach ($response['statuses'] ?? [] as $key => $st): ?>
                                    <option value="<?= esc($key) ?>"><?= esc($st['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="filter-supplier" class="form-select form-select-sm">
                                    <option value="">— Todos los proveedores —</option>
                                    <?php foreach ($response['suppliers'] ?? [] as $s): ?>
                                    <option value="<?= (int) $s->id ?>"><?= esc($s->commercial_name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="filter-branch" class="form-select form-select-sm">
                                    <option value="">— Todas las sucursales —</option>
                                    <?php foreach ($response['branches'] ?? [] as $b): ?>
                                    <option value="<?= (int) $b->id ?>"><?= esc($b->commercial_name ?? $b->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" id="filter-deleted">
                                    <label class="form-check-label small" for="filter-deleted">Papelera (eliminadas)</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-clear-filters">
                                    <i class="fas fa-times me-1"></i>Limpiar filtros
                                </button>
                            </div>
                        </div>
                        <table id="datatables-purchase-orders" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>PO#</th>
                                    <th>Proveedor</th>
                                    <th>Sucursal</th>
                                    <th>Estatus</th>
                                    <th>Total</th>
                                    <th>Moneda</th>
                                    <th>Emisión</th>
                                    <th>Entrega</th>
                                    <th class="text-center" style="width:160px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
$po_mode = 'create';
$supplier_readonly = false;
echo view('Financial/PurchaseOrders/partials/po_form_modal', [
    'response' => $response,
    'po_mode' => $po_mode,
    'supplier_readonly' => $supplier_readonly,
]);
?>

<!-- Offcanvas detalle OC -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasPoDetail" style="width:720px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title"><i class="fas fa-file-invoice-dollar me-2"></i><span id="oc-po-title">Orden de Compra</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="nav nav-tabs px-3 pt-2" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-po-general">General</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-po-inbound">Facturas proveedor</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-po-outbound">Comprobantes</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-po-emails">Correos</button></li>
        </ul>
        <div class="tab-content p-3">
            <div class="tab-pane fade show active" id="tab-po-general">
                <div id="oc-po-general-content"><div class="text-center py-4 text-muted">Cargando…</div></div>
            </div>
            <div class="tab-pane fade" id="tab-po-inbound">
                <?php if ($canDocs): ?>
                <form id="form-upload-inbound" class="mb-3 border rounded p-3 bg-light">
                    <input type="hidden" name="doc_channel" value="inbound_archive">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <select name="doc_type" class="form-select form-select-sm" required>
                                <option value="supplier_invoice_pdf">Factura PDF</option>
                                <option value="supplier_invoice_xml">XML CFDI</option>
                                <option value="supplier_invoice_image">Imagen</option>
                            </select>
                        </div>
                        <div class="col-md-5"><input type="file" name="document_file[]" class="form-control form-control-sm" required multiple accept=".pdf,.xml,.jpg,.jpeg,.png,.webp"></div>
                        <div class="col-md-3"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Folio / UUID"></div>
                        <div class="col-12"><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-upload me-1"></i>Subir factura</button></div>
                    </div>
                </form>
                <?php endif; ?>
                <div id="oc-po-inbound-list"></div>
            </div>
            <div class="tab-pane fade" id="tab-po-outbound">
                <?php if ($canDocs): ?>
                <form id="form-upload-outbound" class="mb-3 border rounded p-3 bg-light">
                    <input type="hidden" name="doc_channel" value="outbound">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <select name="doc_type" class="form-select form-select-sm" required>
                                <option value="payment_proof">Comprobante de pago</option>
                                <option value="clarification">Aclaración</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-5"><input type="file" name="document_file[]" class="form-control form-control-sm" required multiple accept=".pdf,.jpg,.jpeg,.png,.webp"></div>
                        <div class="col-md-3"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notas"></div>
                        <div class="col-12"><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-upload me-1"></i>Subir comprobante</button></div>
                    </div>
                </form>
                <?php endif; ?>
                <div id="oc-po-outbound-list"></div>
            </div>
            <div class="tab-pane fade" id="tab-po-emails">
                <?php if ($canEmail): ?>
                <div class="d-flex gap-2 mb-3">
                    <button class="btn btn-outline-primary btn-sm btn-compose-email" data-kind="send_po"><i class="fas fa-paper-plane me-1"></i>Enviar OC</button>
                    <button class="btn btn-outline-info btn-sm btn-compose-email" data-kind="reply_with_attachments"><i class="fas fa-reply me-1"></i>Respuesta con adjuntos</button>
                </div>
                <?php endif; ?>
                <div id="oc-po-email-logs"></div>
            </div>
        </div>
    </div>
</div>

<?php if ($canEmail): ?>
<div class="modal fade" id="modalPoEmail" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-envelope me-2"></i>Enviar correo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-po-email">
                    <input type="hidden" name="email_kind" id="email-kind" value="send_po">
                    <div class="mb-2"><label class="form-label">Para</label><input type="text" name="to_emails" id="email-to" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">CC</label><input type="text" name="cc_emails" id="email-cc" class="form-control"></div>
                    <div class="mb-2"><label class="form-label">Asunto</label><input type="text" name="subject" id="email-subject" class="form-control" required></div>
                    <div class="mb-2">
                        <label class="form-label">Cuerpo</label>
                        <textarea name="body_html" id="email-body" class="form-control" rows="8"></textarea>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="attached_po_pdf" id="email-attach-pdf" value="1" checked>
                        <label class="form-check-label" for="email-attach-pdf">Adjuntar PDF de la OC generado</label>
                    </div>
                    <div id="email-outbound-docs" class="mb-2"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-send-po-email"><i class="fas fa-paper-plane me-1"></i>Enviar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $this->endSection() ?>

<?php $this->section('pageFooterScripts') ?>
<?php if ($canEmail): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js"></script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    initPurchaseOrdersDataTable();
    bindPurchaseOrderActions();
    bindPoFormModal();
    bindPoOffcanvas();
    <?php if ($canEmail): ?>bindPoEmailModal();<?php endif; ?>
    var openPo = new URLSearchParams(window.location.search).get('open');
    var openTab = new URLSearchParams(window.location.search).get('tab');
    if (openPo) openPoOffcanvas(openPo, openTab);
});

let _poItemIdx = 0;
let _activePoId = null;
const CAN_MANAGE = <?= $canManage ? 'true' : 'false' ?>;
const CAN_DOCS   = <?= $canDocs ? 'true' : 'false' ?>;
const CAN_EMAIL  = <?= $canEmail ? 'true' : 'false' ?>;

function getCsrfToken() { return document.getElementById('csrf_token').value; }
function refreshCsrf(r) {
    if (!r) return;
    var header = '<?= csrf_header() ?>';
    var t = null;
    if (r.headers && typeof r.headers.get === 'function') {
        t = r.headers.get(header);
    } else if (typeof r.getResponseHeader === 'function') {
        t = r.getResponseHeader(header);
    }
    if (t) {
        var el = document.getElementById('csrf_token');
        if (el) el.value = t;
    }
}

function initPurchaseOrdersDataTable() {
    if ($.fn.dataTable.isDataTable('#datatables-purchase-orders')) return;
    window.poTable = $('#datatables-purchase-orders').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: {
            url: '<?= route_to('suppliers.po.list_ajax') ?>', type: 'POST',
            data: function (d) {
                d['<?= csrf_token() ?>'] = getCsrfToken();
                d.filter_status = document.getElementById('filter-status').value;
                d.filter_supplier_id = document.getElementById('filter-supplier').value;
                d.filter_branch_id = document.getElementById('filter-branch').value;
                d.filter_deleted = document.getElementById('filter-deleted').checked ? 1 : '';
            }
        },
        columns: [
            { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 },
            { data: 5 }, { data: 6 }, { data: 7 },
            { data: 8, orderable: false, searchable: false, className: 'text-center' }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
    });
    $('#datatables-purchase-orders').on('xhr.dt', function (e, s, j, xhr) { refreshCsrf(xhr); });
    ['filter-status','filter-supplier','filter-branch','filter-deleted'].forEach(function(id) {
        document.getElementById(id).addEventListener('change', function() { window.poTable.ajax.reload(); });
    });
    document.getElementById('btn-clear-filters').addEventListener('click', function() {
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-supplier').value = '';
        document.getElementById('filter-branch').value = '';
        document.getElementById('filter-deleted').checked = false;
        window.poTable.ajax.reload();
    });
}

function bindPurchaseOrderActions() {
    var table = document.getElementById('datatables-purchase-orders');
    table.addEventListener('click', function(e) {
        var btnView = e.target.closest('.btn-view-po');
        if (btnView) { e.preventDefault(); openPoOffcanvas(btnView.getAttribute('data-id')); return; }
        var btnEdit = e.target.closest('.btn-edit-po');
        if (btnEdit) { e.preventDefault(); loadPoForEdit(btnEdit.getAttribute('data-id')); return; }
        var btnStatus = e.target.closest('.btn-update-po-status');
        if (btnStatus) {
            e.preventDefault();
            if (!confirm('¿Cambiar estatus?')) return;
            postPoAction('<?= route_to('suppliers.po.update_status', 0) ?>'.replace('/0', '/' + btnStatus.dataset.id), { status: btnStatus.dataset.status });
        }
        var btnDelete = e.target.closest('.btn-delete-po');
        if (btnDelete) {
            e.preventDefault();
            if (!confirm('¿Eliminar esta orden?')) return;
            postPoAction('<?= route_to('suppliers.po.delete', 0) ?>'.replace('/0', '/' + btnDelete.dataset.id), {});
        }
        var btnRestore = e.target.closest('.btn-restore-po');
        if (btnRestore) {
            e.preventDefault();
            if (!confirm('¿Restaurar esta orden?')) return;
            postPoAction('<?= route_to('suppliers.po.restore', 0) ?>'.replace('/0', '/' + btnRestore.dataset.id), {});
        }
    });
    var btnNew = document.getElementById('btn-new-po');
    if (btnNew) btnNew.addEventListener('click', openNewPoModal);
}

function postPoAction(url, extra) {
    var fd = new FormData();
    Object.keys(extra).forEach(function(k) { fd.append(k, extra[k]); });
    fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', '<?= csrf_header() ?>': getCsrfToken() }, body: fd })
    .then(function(r) { refreshCsrf(r); return r.json(); })
    .then(function(data) {
        notifyShow(data.message, data.success ? 'success' : 'danger');
        if (data.success) window.poTable.ajax.reload(null, false);
    });
}

function bindPoFormModal() {
    $('#modalGeneratePO').on('shown.bs.modal', function () {
        ['#po-currency','#po-supplier-select'].forEach(function(sel) {
            var el = document.querySelector(sel);
            if (el && !$(sel).hasClass('select2-hidden-accessible')) {
                $(sel).select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#modalGeneratePO'), allowClear: !!el.id.match(/supplier/) });
            }
        });
    });
    document.getElementById('btn-gen-po-number').addEventListener('click', fetchPoNumber);
    document.getElementById('btn-add-po-item').addEventListener('click', function() { addPoItem(_poItemIdx++); });
    document.getElementById('po-items-tbody').addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove-po-item')) { e.target.closest('.po-item-row').remove(); recalcPoTotals(); }
    });
    document.getElementById('po-items-tbody').addEventListener('input', function(e) {
        if (e.target.classList.contains('po-qty') || e.target.classList.contains('po-price')) recalcPoTotals();
    });
    document.getElementById('btn-submit-generate-po').addEventListener('click', submitPoForm);
    var supplierSelect = document.getElementById('po-supplier-select');
    if (supplierSelect) supplierSelect.addEventListener('change', function() {
        document.getElementById('po-supplier-id').value = this.value;
    });
}

function openNewPoModal() {
    document.getElementById('form-generate-po').reset();
    document.getElementById('po-id').value = '';
    document.getElementById('po-items-tbody').innerHTML = '';
    document.getElementById('po-modal-title-text').textContent = 'Nueva Orden de Compra';
    document.getElementById('po-submit-text').textContent = 'Crear Orden de Compra';
    document.getElementById('po-issue-date').value = new Date().toISOString().split('T')[0];
    _poItemIdx = 0; addPoItem(_poItemIdx++);
    fetchPoNumber();
    new bootstrap.Modal(document.getElementById('modalGeneratePO')).show();
}

function loadPoForEdit(id) {
    fetch('<?= route_to('suppliers.po.get_ajax', 0) ?>'.replace('/0', '/' + id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function(r) { refreshCsrf(r); return r.json(); })
    .then(function(data) {
        if (!data.success) { notifyShow(data.message, 'danger'); return; }
        var o = data.response.order;
        document.getElementById('po-id').value = o.id;
        document.getElementById('po-supplier-id').value = o.fin_supplier_id;
        var sel = document.getElementById('po-supplier-select');
        if (sel) { sel.value = o.fin_supplier_id; $(sel).trigger('change'); }
        document.getElementById('po-branch-id').value = o.org_branch_id;
        document.getElementById('po-number').value = o.po_number;
        document.getElementById('po-status').value = o.status;
        document.getElementById('po-currency').value = o.currency || 'MXN';
        document.getElementById('po-issue-date').value = o.issue_date || '';
        document.getElementById('po-delivery-date').value = o.delivery_date || '';
        document.getElementById('po-notes').value = o.notes || '';
        document.getElementById('po-items-tbody').innerHTML = '';
        _poItemIdx = 0;
        (o.items || []).forEach(function(item) {
            addPoItem(_poItemIdx);
            var row = document.querySelector('#po-items-tbody .po-item-row:last-child');
            row.querySelector('[name$="[description]"]').value = item.description || '';
            row.querySelector('.po-qty').value = item.quantity || 1;
            row.querySelector('.po-price').value = item.unit_price || 0;
            _poItemIdx++;
        });
        recalcPoTotals();
        document.getElementById('po-modal-title-text').textContent = 'Editar Orden de Compra';
        document.getElementById('po-submit-text').textContent = 'Guardar cambios';
        new bootstrap.Modal(document.getElementById('modalGeneratePO')).show();
    });
}

function fetchPoNumber() {
    fetch('<?= route_to('suppliers.po.generate_number') ?>', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function(r) { refreshCsrf(r); return r.json(); })
    .then(function(data) { if (data.success && data.response) document.getElementById('po-number').value = data.response.po_number; });
}

function addPoItem(idx) {
    var tpl = document.getElementById('tpl-po-item-row').content.cloneNode(true);
    tpl.querySelectorAll('[name]').forEach(function(el) { el.name = el.name.replace(/__idx__/g, idx); });
    document.getElementById('po-items-tbody').appendChild(tpl);
}

function recalcPoTotals() {
    var grand = 0;
    document.querySelectorAll('#po-items-tbody .po-item-row').forEach(function(row) {
        var t = (parseFloat(row.querySelector('.po-qty').value)||0) * (parseFloat(row.querySelector('.po-price').value)||0);
        row.querySelector('.po-line-total').textContent = '$' + t.toFixed(2);
        grand += t;
    });
    document.getElementById('po-grand-total').textContent = '$' + grand.toFixed(2);
}

function submitPoForm() {
    var form = document.getElementById('form-generate-po');
    if (!form.checkValidity()) { form.reportValidity(); return; }
    if (!document.querySelectorAll('#po-items-tbody .po-item-row').length) {
        notifyShow('Agregue al menos una línea.', 'warning'); return;
    }
    var poId = document.getElementById('po-id').value;
    var url = poId ? '<?= route_to('suppliers.po.update', 0) ?>'.replace('/0', '/' + poId) : '<?= route_to('suppliers.po.store') ?>';
    var sel = document.getElementById('po-supplier-select');
    if (sel && sel.value) document.getElementById('po-supplier-id').value = sel.value;
    fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', '<?= csrf_header() ?>': getCsrfToken() }, body: new FormData(form) })
    .then(function(r) { refreshCsrf(r); return r.json(); })
    .then(function(data) {
        notifyShow(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalGeneratePO')).hide();
            window.poTable.ajax.reload(null, false);
        }
    });
}

function bindPoOffcanvas() {
    <?php if ($canDocs): ?>
    document.getElementById('form-upload-inbound').addEventListener('submit', function(e) {
        e.preventDefault(); uploadPoDoc(_activePoId, new FormData(this));
    });
    document.getElementById('form-upload-outbound').addEventListener('submit', function(e) {
        e.preventDefault(); uploadPoDoc(_activePoId, new FormData(this));
    });
    <?php endif; ?>
}

function openPoOffcanvas(id, tab) {
    _activePoId = id;
    fetch('<?= route_to('suppliers.po.show', 0) ?>'.replace('/0', '/' + id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function(r) { refreshCsrf(r); return r.json(); })
    .then(function(data) {
        if (!data.success) return;
        var o = data.response.order;
        document.getElementById('oc-po-title').textContent = 'OC ' + o.po_number;
        var html = '<dl class="row small mb-0">' +
            '<dt class="col-4">Proveedor</dt><dd class="col-8">' + esc(o.supplier_name) + '</dd>' +
            '<dt class="col-4">Sucursal</dt><dd class="col-8">' + esc(o.branch_name || o.branch_commercial_name) + '</dd>' +
            '<dt class="col-4">Total</dt><dd class="col-8">' + parseFloat(o.total_amount).toFixed(2) + ' ' + esc(o.currency) + '</dd>' +
            '<dt class="col-4">Notas</dt><dd class="col-8">' + esc(o.notes || '—') + '</dd></dl>';
        if (o.items && o.items.length) {
            html += '<table class="table table-sm mt-3"><thead><tr><th>Descripción</th><th>Cant.</th><th>Total</th></tr></thead><tbody>';
            o.items.forEach(function(it) {
                html += '<tr><td>' + esc(it.description) + '</td><td>' + it.quantity + '</td><td>' + parseFloat(it.total_price).toFixed(2) + '</td></tr>';
            });
            html += '</tbody></table>';
        }
        document.getElementById('oc-po-general-content').innerHTML = html;
        loadPoDocuments(id, 'inbound_archive', 'oc-po-inbound-list');
        loadPoDocuments(id, 'outbound', 'oc-po-outbound-list');
        loadPoEmailLogs(id);
        new bootstrap.Offcanvas(document.getElementById('offcanvasPoDetail')).show();
        if (tab) {
            var tabBtn = document.querySelector('[data-bs-target="#tab-po-' + tab + '"]');
            if (tabBtn) tabBtn.click();
        }
    });
}

function loadPoDocuments(poId, channel, containerId) {
    fetch('<?= route_to('suppliers.po.documents.list', 0) ?>'.replace('/0', '/' + poId) + '?channel=' + channel, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function(r) { refreshCsrf(r); return r.json(); })
    .then(function(data) {
        var el = document.getElementById(containerId);
        if (!data.success || !data.response.documents.length) {
            el.innerHTML = '<p class="text-muted fst-italic">Sin documentos.</p>'; return;
        }
        var html = '<ul class="list-group list-group-flush">';
        data.response.documents.forEach(function(d) {
            html += '<li class="list-group-item d-flex justify-content-between align-items-center px-0">' +
                '<span><i class="fas fa-file me-2"></i>' + esc(d.original_name) + '</span>' +
                '<div class="d-flex gap-1">' +
                '<a class="btn btn-outline-info btn-sm" href="<?= route_to('suppliers.po.documents.download', 0) ?>'.replace('/0','/'+d.id) + '?action=view" target="_blank"><i class="fas fa-eye"></i></a>' +
                (CAN_DOCS ? '<button class="btn btn-outline-danger btn-sm btn-del-po-doc" data-id="'+d.id+'"><i class="fas fa-trash"></i></button>' : '') +
                '</div></li>';
        });
        html += '</ul>';
        el.innerHTML = html;
        el.querySelectorAll('.btn-del-po-doc').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!confirm('¿Eliminar documento?')) return;
                postPoAction('<?= route_to('suppliers.po.documents.delete', 0) ?>'.replace('/0','/'+btn.dataset.id), {});
                setTimeout(function() { loadPoDocuments(poId, channel, containerId); }, 400);
            });
        });
    });
}

function uploadPoDoc(poId, fd) {
    fetch('<?= route_to('suppliers.po.documents.upload', 0) ?>'.replace('/0', '/' + poId), {
        method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', '<?= csrf_header() ?>': getCsrfToken() }, body: fd
    }).then(function(r) { refreshCsrf(r); return r.json(); }).then(function(data) {
        notifyShow(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            loadPoDocuments(poId, 'inbound_archive', 'oc-po-inbound-list');
            loadPoDocuments(poId, 'outbound', 'oc-po-outbound-list');
        }
    });
}

function loadPoEmailLogs(poId) {
    fetch('<?= route_to('suppliers.po.email.compose', 0) ?>'.replace('/0', '/' + poId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function(r) { refreshCsrf(r); return r.json(); })
    .then(function(data) {
        var el = document.getElementById('oc-po-email-logs');
        var logs = (data.response && data.response.email_logs) || [];
        if (!logs.length) { el.innerHTML = '<p class="text-muted fst-italic">Sin correos enviados.</p>'; return; }
        var html = '<ul class="list-group list-group-flush">';
        logs.forEach(function(l) {
            html += '<li class="list-group-item px-0"><div class="fw-semibold">' + esc(l.subject) + '</div>' +
                '<small class="text-muted">' + esc(l.email_kind) + ' · ' + esc(l.smtp_status) + ' · ' + esc(l.sent_at || '') + '</small></li>';
        });
        html += '</ul>';
        el.innerHTML = html;
    });
}

<?php if ($canEmail): ?>
function bindPoEmailModal() {
    document.querySelectorAll('.btn-compose-email').forEach(function(btn) {
        btn.addEventListener('click', function() { composePoEmail(_activePoId, btn.dataset.kind); });
    });
    document.getElementById('btn-send-po-email').addEventListener('click', sendPoEmail);
}
function composePoEmail(poId, kind) {
    fetch('<?= route_to('suppliers.po.email.compose', 0) ?>'.replace('/0', '/' + poId) + '?kind=' + kind, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function(r) { refreshCsrf(r); return r.json(); })
    .then(function(data) {
        if (!data.success) return;
        var r = data.response;
        document.getElementById('email-kind').value = kind;
        document.getElementById('email-to').value = r.to_emails;
        document.getElementById('email-subject').value = r.subject;
        document.getElementById('email-attach-pdf').checked = kind === 'send_po';
        var docsHtml = '';
        (r.outbound_docs || []).forEach(function(d) {
            docsHtml += '<div class="form-check"><input class="form-check-input" type="checkbox" name="document_ids[]" value="'+d.id+'" id="doc-'+d.id+'"><label class="form-check-label" for="doc-'+d.id+'">'+esc(d.original_name)+'</label></div>';
        });
        document.getElementById('email-outbound-docs').innerHTML = docsHtml || '<small class="text-muted">Sin comprobantes outbound.</small>';
        new bootstrap.Modal(document.getElementById('modalPoEmail')).show();
        if (typeof tinymce !== 'undefined' && !tinymce.get('email-body')) {
            tinymce.init({ selector: '#email-body', height: 280, menubar: false, plugins: 'lists link', toolbar: 'bold italic | bullist numlist | link', license_key: 'gpl' });
        }
        if (tinymce.get('email-body')) tinymce.get('email-body').setContent(r.body_html);
        else document.getElementById('email-body').value = r.body_html;
    });
}
function sendPoEmail() {
    var fd = new FormData(document.getElementById('form-po-email'));
    if (tinymce.get('email-body')) fd.set('body_html', tinymce.get('email-body').getContent());
    fetch('<?= route_to('suppliers.po.email.send', 0) ?>'.replace('/0', '/' + _activePoId), {
        method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', '<?= csrf_header() ?>': getCsrfToken() }, body: fd
    }).then(function(r) { refreshCsrf(r); return r.json(); }).then(function(data) {
        notifyShow(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalPoEmail')).hide();
            loadPoEmailLogs(_activePoId);
        }
    });
}
<?php endif; ?>

function esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
</script>
<?php $this->endSection() ?>
