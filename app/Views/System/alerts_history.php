<?php $this->extend($layout); ?>

<?php $this->section('title'); echo esc($headTitle); $this->endSection(); ?>

<?php $this->section('main') ?>

<?php /* Token CSRF global para DataTables y Fetch API */ ?>
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

<main class="content">
    <div class="container-fluid p-0">

        <?php /* Encabezado + Breadcrumb */ ?>
        <div class="row mb-2 mb-xl-3">
            <div class="col-auto d-none d-sm-block">
                <h1 class="h3 mb-3">
                    <i class="fas fa-fw fa-bell me-2 text-warning"></i><?= esc($pageTitle) ?>
                </h1>
            </div>
            <div class="col-auto ms-auto text-end mt-n1">
                <nav aria-label="breadcrumb">
                    <?= $breadcrumb ?? '' ?>
                </nav>
            </div>
        </div>

        
        
        

        <?php /* Tabla de historial */ ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-fw fa-history me-1 text-muted"></i>Historial de Notificaciones
                </h5>
            </div>
            <div class="card-body">

            <!-- Botón "Marcar todo como leído" — solo aplica a notificaciones almacenadas  -->
            <div class="row mb-2">
                <div class="col-12 d-flex flex-wrap align-items-end">
                    <button type="button" class="btn btn-primary" id="btnMarkAllRead"
                        title="Solo marca como leídas las notificaciones almacenadas">
                        <i class="fas fa-check-double fa-fw"></i> Marcar notificaciones como leídas
                    </button>
                    <p class="text-muted">
                        Las alertas <span class="badge bg-info text-white">En vivo</span> no se pueden marcar como leídas;
                        desaparecen cuando se corrige la condición en el sistema.
                    </p>
                </div>
            </div>



                <table id="alertsTable" class="table table-striped table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th width="40px"></th>
                            <th>Título</th>
                            <th>Mensaje</th>
                            <th>Módulo</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th width="50px">Acción</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

    </div>
</main>

<?php $this->endSection(); ?>

<?php $this->section('pageFooterScripts'); ?>
<script>
/**
 * DataTable del historial de notificaciones.
 * Carga todas las alertas del usuario autenticado vía AJAX.
 */
let alertsTable = null;

document.addEventListener('DOMContentLoaded', function () {
    initAlertsHistoryTable();
    bindMarkAllReadButton();
});

function initAlertsHistoryTable() {
    if ($.fn.dataTable.isDataTable('#alertsTable')) return;

    alertsTable = $('#alertsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: '<?= route_to('alerts.history_ajax') ?>',
        type: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        data: function (d) {
            d['<?= csrf_token() ?>'] = document.getElementById('csrf_token').value;
        }
    },
    columns: [
        {
            data: 'icon',
            render: function (data, type, row) {
                const cls = row.type === 'warning' ? 'text-warning' :
                    row.type === 'danger' ? 'text-danger' :
                    row.type === 'success' ? 'text-success' : 'text-primary';
                return '<i class="fas fa-fw ' + (data || 'fa-bell') + ' ' + cls + '"></i>';
            }
        },
        { data: 'title' },
        {
            data: 'message',
            render: function (data) {
                if (!data) return '';
                return data.length > 80 ? data.substring(0, 80) + '…' : data;
            }
        },
        {
            data: 'module',
            render: function (data) {
                if (!data) return '-';
                return data.charAt(0).toUpperCase() + data.slice(1);
            }
        },
        {
            data: 'is_auto',
            orderable: false,
            render: function (data) {
                return data == 1
                    ? '<span class="badge bg-info text-dark" title="Detectada en tiempo real según datos del sistema">'
                        + '<i class="fas fa-broadcast-tower me-1"></i>En vivo</span>'
                    : '<span class="badge bg-light text-dark border" title="Notificación almacenada en el sistema">'
                        + '<i class="fas fa-inbox me-1"></i>Notificación</span>';
            }
        },
        {
            data: 'created_at',
            render: function (data, type, row) {
                if (row.is_auto == 1) {
                    return '<span class="text-muted" title="Se actualiza en cada consulta">'
                        + '<i class="fas fa-sync-alt fa-fw me-1"></i>Tiempo real</span>';
                }
                if (!data) return '';
                const d = new Date(data.replace(' ', 'T') + 'Z');
                return d.toLocaleDateString('es-MX', {
                    day: '2-digit', month: '2-digit', year: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                });
            }
        },
        {
            data: 'is_read',
            orderable: false,
            render: function (data, type, row) {
                if (row.is_auto == 1) {
                    return '<span class="badge bg-warning text-dark" title="Visible mientras la condición persista en el sistema">'
                        + '<i class="fas fa-exclamation-circle me-1"></i>Activa</span>';
                }
                return data == 1
                    ? '<span class="badge bg-secondary"><i class="fas fa-check me-1"></i>Leída</span>'
                    : '<span class="badge bg-primary"><i class="fas fa-circle me-1"></i>Nueva</span>';
            }
        },
        {
            data: 'target_url',
            orderable: false,
            render: function (data, type, row) {
                if (!data) return '';
                const title = row.is_auto == 1 ? 'Ir a corregir la condición' : 'Ir a la sección';
                return '<a href="' + data + '" class="btn btn-sm btn-outline-primary" title="' + title + '">'
                    + '<i class="fas fa-external-link-alt"></i></a>';
            }
        }
    ],
    order: [[5, 'desc']],
    language: {
        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json'
    },
    pageLength: 10,
    responsive: true,
    autoWidth: true,
    });

    $('#alertsTable').on('xhr.dt', function (e, settings, json, xhr) {
        if (xhr && xhr.getResponseHeader('<?= csrf_header() ?>')) {
            document.getElementById('csrf_token').value = xhr.getResponseHeader('<?= csrf_header() ?>');
        }
        if (json && json.csrf) {
            document.getElementById('csrf_token').value = json.csrf;
        }
    });
}

function bindMarkAllReadButton() {
    document.getElementById('btnMarkAllRead').addEventListener('click', function () {
    const currentToken = document.getElementById('csrf_token').value;

    fetch('<?= route_to('alerts.mark_all_read') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_header() ?>': currentToken
        }
    })
    .then(response => {
        const newToken = response.headers.get('<?= csrf_header() ?>');
        if (newToken) {
            document.getElementById('csrf_token').value = newToken;
        }
        return response.json();
    })
    .then(data => {
        if (data.success && alertsTable) {
            alertsTable.ajax.reload(null, false);
            if (typeof notifyShow === 'function') {
                notifyShow(data.message, 'success');
            }
        } else {
            if (typeof notifyShow === 'function') {
                notifyShow(data.message || 'Error al marcar alertas', 'danger');
            }
        }
    })
    .catch(err => {
        console.error('Error al marcar todo como leído:', err);
        if (typeof notifyShow === 'function') {
            notifyShow('Error de conexión al marcar alertas', 'danger');
        }
    });
    });
}
</script>
<?php $this->endSection(); ?>
