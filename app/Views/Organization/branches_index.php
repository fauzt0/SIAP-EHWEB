<?php $this->extend($layout); ?>

<?php $this->section('title'); ?>
<?= esc($headTitle) ?>
<?php $this->endSection(); ?>

<?php $this->section('main'); ?>
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

<main class="content">
    <div class="container-fluid p-0">

        <div class="row mb-3 align-items-center">
            <div class="col-auto">
                <h1 class="h3 mb-0"><?= esc($pageTitle) ?></h1>
            </div>
            <div class="col-auto ms-auto text-end">
                <a href="<?= base_url('nat/organization/branch/new') ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nueva Sucursal
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <table id="datatables-branches" class="table table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Logo</th>
                                    <th>Nombre</th>
                                    <th>Código</th>
                                    <th>Teléfono</th>
                                    <th>Correo</th>
                                    <th>Estatus</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    let table = $('#datatables-branches').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "<?= base_url('nat/organization/branches/list_ajax') ?>",
            type: "POST",
            data: function ( d ) {
                d['<?= csrf_token() ?>'] = document.getElementById('csrf_token').value;
            }
        },
        columnDefs: [
            { targets: 0, orderable: false, searchable: false },
            { targets: 5, orderable: false, searchable: false },
            { targets: 6, orderable: false, searchable: false }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json'
        }
    });

    $('#datatables-branches').on('xhr.dt', function ( e, settings, json, xhr ) {
        if(xhr && xhr.getResponseHeader('<?= csrf_header() ?>')) {
            document.getElementById('csrf_token').value = xhr.getResponseHeader('<?= csrf_header() ?>');
        } else if (json && json.csrf) {
            document.getElementById('csrf_token').value = json.csrf;
        }
    });
});

window.toggleBranchStatus = function(id, status) {
    if(confirm('¿Estás seguro de cambiar el estatus de esta sucursal?')) {
        let csrf = document.getElementById('csrf_token').value;
        $.post('<?= base_url("nat/organization/branch/toggle_status/") ?>' + id, {
            status: status,
            '<?= csrf_token() ?>': csrf
        }, function(res) {
            if(res.csrf) document.getElementById('csrf_token').value = res.csrf;
            if(res.success) {
                $('#datatables-branches').DataTable().ajax.reload(null, false);
                if(typeof tools !== 'undefined' && tools.showAlert) tools.showAlert('success', 'Éxito', res.message);
            }
        });
    }
}

window.deleteBranch = function(id) {
    if(confirm('¿Estás seguro de ELIMINAR esta sucursal? Esta acción puede tener efectos en productos o usuarios asociados.')) {
        let csrf = document.getElementById('csrf_token').value;
        $.post('<?= base_url("nat/organization/branch/delete/") ?>' + id, {
            '<?= csrf_token() ?>': csrf
        }, function(res) {
            if(res.csrf) document.getElementById('csrf_token').value = res.csrf;
            if(res.success) {
                $('#datatables-branches').DataTable().ajax.reload(null, false);
                if(typeof tools !== 'undefined' && tools.showAlert) tools.showAlert('success', 'Éxito', res.message);
            }
        });
    }
}
</script>
<?php $this->endSection(); ?>
