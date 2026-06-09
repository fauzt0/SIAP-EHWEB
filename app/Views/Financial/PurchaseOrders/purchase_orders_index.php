<?php $this->extend('Layouts/user_loggedin_layout'); ?>

<?php $this->section('title'); echo esc($headTitle ?? 'Órdenes de Compra'); $this->endSection(); ?>

<?php $this->section('main') ?>
<?php /* §2 — Token CSRF global para DataTables y Fetch API */ ?>
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

<main class="content">
    <div class="container-fluid p-0">

        <?php /* Encabezado + Breadcrumb */ ?>
        <div class="row mb-2 mb-xl-3">
            <div class="col-auto d-none d-sm-block">
                <h1 class="h3 mb-3">
                    <i class="fas fa-fw fa-file-invoice-dollar me-2 text-primary"></i><?= esc($pageTitle ?? 'Órdenes de Compra') ?>
                </h1>
            </div>
            <div class="col-auto ms-auto text-end mt-n1">
                <nav aria-label="breadcrumb">
                    <?= isset($breadcrumb) ? $breadcrumb : '' ?>
                </nav>
            </div>
        </div>

        <?php /* DataTable Card */ ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>Listado de Órdenes de Compra
                            </h5>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        <table id="datatables-purchase-orders" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>PO#</th>
                                    <th>Proveedor</th>
                                    <th>Estatus</th>
                                    <th>Total</th>
                                    <th>Moneda</th>
                                    <th>Emisión</th>
                                    <th>Entrega</th>
                                    <th class="text-center" style="width:130px;">Acciones</th>
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
<?php $this->endSection() ?>

<?php $this->section('pageFooterScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    initPurchaseOrdersDataTable();
    bindPurchaseOrderActions();
});

/* ──────────────────────────────────────────────────────────────────────────
   §6 — DataTables Guard + Inicialización Server-Side
────────────────────────────────────────────────────────────────────────── */
function initPurchaseOrdersDataTable() {
    if ($.fn.dataTable.isDataTable('#datatables-purchase-orders')) return;

    window.poTable = $('#datatables-purchase-orders').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        dom: "<'row'<'col-sm-12 col-md-6'><'col-sm-12 col-md-6 text-end'l>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row mt-3'<'col-sm-12 col-md-5'i>" +
             "<'col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center'p>>",
        ajax: {
            url: '<?= route_to('suppliers.po.list_ajax') ?>',
            type: 'POST',
            data: function (d) {
                /* §2 — Inyectar token CSRF actual */
                d['<?= csrf_token() ?>'] = document.getElementById('csrf_token').value;
            }
        },
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6 },
            { data: 7, orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });

    /* §2 — Escuchar evento xhr.dt para renovar el token CSRF */
    $('#datatables-purchase-orders').on('xhr.dt', function (e, settings, json, xhr) {
        if (xhr && xhr.getResponseHeader('<?= csrf_header() ?>')) {
            document.getElementById('csrf_token').value = xhr.getResponseHeader('<?= csrf_header() ?>');
        }
    });
}

function bindPurchaseOrderActions() {
    var table = document.getElementById('datatables-purchase-orders');
    if (!table) return;

    table.addEventListener('click', function(e) {
        var btnStatus = e.target.closest('.btn-update-po-status');
        if (btnStatus) {
            e.preventDefault();
            var poId = btnStatus.getAttribute('data-id');
            var status = btnStatus.getAttribute('data-status');
            
            if (confirm('¿Está seguro de cambiar el estatus de esta orden a ' + status + '?')) {
                var fd = new FormData();
                fd.append('status', status);
                fd.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);

                fetch('<?= site_url('nat/suppliers/purchase-orders/update_status/') ?>' + poId, {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => {
                    var nt = r.headers.get('<?= csrf_header() ?>');
                    if (nt) document.getElementById('csrf_token').value = nt;
                    return r.json();
                })
                .then(data => {
                    if (data.success) {
                        notifyShow(data.message, 'success');
                        window.poTable.ajax.reload(null, false);
                    } else {
                        notifyShow(data.message || 'Error al actualizar estatus.', 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    notifyShow('Error de conexión.', 'danger');
                });
            }
        }

        var btnDelete = e.target.closest('.btn-delete-po');
        if (btnDelete) {
            e.preventDefault();
            var poId = btnDelete.getAttribute('data-id');
            
            if (confirm('¿Está seguro de eliminar esta orden de compra?')) {
                var fd = new FormData();
                fd.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);

                fetch('<?= site_url('nat/suppliers/purchase-orders/delete/') ?>' + poId, {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => {
                    var nt = r.headers.get('<?= csrf_header() ?>');
                    if (nt) document.getElementById('csrf_token').value = nt;
                    return r.json();
                })
                .then(data => {
                    if (data.success) {
                        notifyShow(data.message, 'success');
                        window.poTable.ajax.reload(null, false);
                    } else {
                        notifyShow(data.message || 'Error al eliminar la orden.', 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    notifyShow('Error de conexión.', 'danger');
                });
            }
        }
    });
}
</script>
<?php $this->endSection() ?>
