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
                    <?= isset($breadcrumb) ? $breadcrumb : '' ?>
                </nav>
            </div>
        </div>

        <?php /* Botón "Marcar todo como leído" */ ?>
        <div class="row mb-3">
            <div class="col-12">
                <button type="button" class="btn btn-outline-primary" id="btnMarkAllRead">
                    <i class="fas fa-check-double fa-fw"></i> Marcar todo como leído
                </button>
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
                <table id="alertsTable" class="table table-striped table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th width="40px"></th>
                            <th>Título</th>
                            <th>Mensaje</th>
                            <th>Módulo</th>
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

<?php $this->section('scripts'); ?>
<script>
/**
 * DataTable del historial de notificaciones.
 * Carga todas las alertas del usuario autenticado vía AJAX.
 */
if ($.fn.dataTable.isDataTable('#alertsTable')) return;

const alertsTable = $('#alertsTable').DataTable({
    ajax: {
        url: '<?= route_to('alerts.history_ajax') ?>',
        type: 'POST',
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
            data: 'created_at',
            render: function (data) {
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
            render: function (data) {
                return data == 1
                    ? '<span class="badge bg-secondary"><i class="fas fa-check me-1"></i>Leída</span>'
                    : '<span class="badge bg-primary"><i class="fas fa-circle me-1"></i>Nueva</span>';
            }
        },
        {
            data: 'target_url',
            render: function (data) {
                if (!data) return '';
                return '<a href="' + data + '" class="btn btn-sm btn-outline-primary" title="Ir a la sección">'
                    + '<i class="fas fa-external-link-alt"></i></a>';
            }
        }
    ],
    order: [[4, 'desc']],
    language: {
        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json'
    },
    pageLength: 10,
    responsive: true,
    autoWidth: true,
});

// Actualizar token CSRF en cada recarga de DataTable
$('#alertsTable').on('xhr.dt', function (e, settings, json, xhr) {
    if (xhr && xhr.getResponseHeader('<?= csrf_header() ?>')) {
        document.getElementById('csrf_token').value = xhr.getResponseHeader('<?= csrf_header() ?>');
    }
    if (json && json.csrf) {
        document.getElementById('csrf_token').value = json.csrf;
    }
});

// Botón "Marcar todo como leído"
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
        if (data.success) {
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
</script>
<?php $this->endSection(); ?>
