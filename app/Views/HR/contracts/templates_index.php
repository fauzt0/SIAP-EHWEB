<?php $this->extend($layout); ?>

<?php $this->section('main') ?>
<!-- Token CSRF global (Requerido por estándar) -->
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

<main class="content">
    <div class="container-fluid p-0">
        <div class="row mb-3">
            <div class="col-12">
                <?= $breadcrumb ?>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Plantillas de Contratos</h5>
                        <?php if (auth()->user()->can('hr.create')): ?>
                            <a href="<?= route_to('hr.contracts.templates.new') ?>" class="btn btn-primary">
                                <i class="align-middle me-1" data-lucide="plus"></i> Nueva Plantilla
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="datatables-templates" class="table table-striped w-100">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Modelo Base</th>
                                        <th>Estado</th>
                                        <th>Última Edición</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Cargado vía AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php $this->endSection() ?>

<?php $this->section('pageFooterScripts'); ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Inicializar DataTable
        const table = $('#datatables-templates').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            dom: "<'row'<'col-sm-12 col-md-6'><'col-sm-12 col-md-6 text-end'l>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center'p>>",
            ajax: {
                url: '<?= route_to('hr.contracts.templates.listAjax') ?>',
                type: 'POST',
                data: function(d) {
                    d['<?= csrf_token() ?>'] = document.getElementById('csrf_token').value;
                }
            },
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json'
            },
            pageLength: 10,
            order: [[4, 'desc']] // Ordenar por fecha de edición
        });

        // Actualizar CSRF tras cada llamada AJAX
        $('#datatables-templates').on('xhr.dt', function(e, settings, json, xhr) {
            const newCsrf = xhr.getResponseHeader('<?= csrf_header() ?>');
            if (newCsrf) {
                document.getElementById('csrf_token').value = newCsrf;
            }
        });

        // Manejar eliminación
        document.addEventListener('click', function(e) {
            const btnDelete = e.target.closest('.btn-delete-template');
            if (btnDelete) {
                const id = btnDelete.dataset.id;
                if (!confirm('¿Estás seguro de eliminar esta plantilla?')) return;

                const formData = new FormData();
                formData.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);

                fetch('<?= base_url('nat/hr/contracts/templates/delete') ?>/' + id, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    const newCsrf = response.headers.get('<?= csrf_header() ?>');
                    if (newCsrf) document.getElementById('csrf_token').value = newCsrf;
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        notifyShow(data.message, 'success');
                        table.ajax.reload(null, false);
                    } else {
                        notifyShow(data.message || 'Error al eliminar', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    notifyShow('Error en la petición', 'danger');
                });
            }
        });
    });
</script>
<?php $this->endSection(); ?>
