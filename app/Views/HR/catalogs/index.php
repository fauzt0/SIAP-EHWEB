<?php $this->extend($layout); ?>

<?php $this->section('title');
echo $pageTitle;
$this->endSection(); ?>

<?php $this->section('main') ?>
<!-- Token CSRF global (Requerido por estándar de la documentación) -->
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
                <div class="card-header pb-0">
                    <h5 class="card-title mb-0">Gestión de Catálogos de RRHH</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" data-bs-toggle="tab" href="#tab-departments" role="tab">
                                <i class="align-middle me-1" data-lucide="layers"></i> Departamentos
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-jobs" role="tab">
                                <i class="align-middle me-1" data-lucide="briefcase"></i> Puestos / Cargos
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content mt-3">
                        <!-- Tab: Departamentos -->
                        <div class="tab-pane fade show active" id="tab-departments" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Listado de Departamentos</h6>
                                <button class="btn btn-primary btn-sm" onclick="openCatalogModal('department')">
                                    <i class="align-middle me-1" data-lucide="plus"></i> Nuevo Departamento
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Descripción</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($departments)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">No hay departamentos registrados.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($departments as $dept): ?>
                                                <tr>
                                                    <td class="fw-bold"><?= esc($dept->name) ?></td>
                                                    <td><?= esc($dept->description ?: 'Sin descripción') ?></td>
                                                    <td class="text-end">
                                                        <button class="btn btn-sm btn-light" onclick="editCatalog('department', <?= $dept->id ?>, '<?= esc($dept->name, 'js') ?>', '<?= esc($dept->description, 'js') ?>')">
                                                            <i class="align-middle" data-lucide="edit-2"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-light text-danger" onclick="deleteCatalog('department', <?= $dept->id ?>)">
                                                            <i class="align-middle" data-lucide="trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab: Puestos -->
                        <div class="tab-pane fade" id="tab-jobs" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Listado de Puestos / Cargos</h6>
                                <button class="btn btn-primary btn-sm" onclick="openCatalogModal('job')">
                                    <i class="align-middle me-1" data-lucide="plus"></i> Nuevo Puesto
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Descripción</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($jobs)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">No hay puestos registrados.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($jobs as $job): ?>
                                                <tr>
                                                    <td class="fw-bold"><?= esc($job->name) ?></td>
                                                    <td><?= esc($job->description ?: 'Sin descripción') ?></td>
                                                    <td class="text-end">
                                                        <button class="btn btn-sm btn-light" onclick="editCatalog('job', <?= $job->id ?>, '<?= esc($job->name, 'js') ?>', '<?= esc($job->description, 'js') ?>')">
                                                            <i class="align-middle" data-lucide="edit-2"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-light text-danger" onclick="deleteCatalog('job', <?= $job->id ?>)">
                                                            <i class="align-middle" data-lucide="trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Genérico para Catálogos -->
<div class="modal fade" id="catalogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="catalogModalTitle">Nuevo Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="catalogForm">
                <input type="hidden" id="catalogId" name="id">
                <input type="hidden" id="catalogType" name="type">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="catalogName" name="name" required placeholder="Ej: Recursos Humanos">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" id="catalogDescription" name="description" rows="3" placeholder="Opcional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveCatalog">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
</main>
<?php $this->endSection(); ?>

<?php $this->section('pageFooterScripts'); ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const catalogModal = new bootstrap.Modal(document.getElementById('catalogModal'));
        const catalogForm = document.getElementById('catalogForm');

        // Helper para actualizar CSRF desde headers
        function updateCsrfToken(response) {
            const newCsrf = response.headers.get('<?= csrf_header() ?>');
            if (newCsrf) {
                document.getElementById('csrf_token').value = newCsrf;
            }
            return response;
        }

        // Abrir modal para nuevo
        window.openCatalogModal = function(type) {
            document.getElementById('catalogId').value = '';
            document.getElementById('catalogType').value = type;
            document.getElementById('catalogName').value = '';
            document.getElementById('catalogDescription').value = '';
            
            const title = type === 'department' ? 'Nuevo Departamento' : 'Nuevo Puesto / Cargo';
            document.getElementById('catalogModalTitle').innerText = title;
            
            catalogModal.show();
        };

        // Abrir modal para editar
        window.editCatalog = function(type, id, name, description) {
            document.getElementById('catalogId').value = id;
            document.getElementById('catalogType').value = type;
            document.getElementById('catalogName').value = name;
            document.getElementById('catalogDescription').value = description;
            
            const title = type === 'department' ? 'Editar Departamento' : 'Editar Puesto / Cargo';
            document.getElementById('catalogModalTitle').innerText = title;
            
            catalogModal.show();
        };

        // Guardar via AJAX
        catalogForm.onsubmit = function(e) {
            e.preventDefault();
            const type = document.getElementById('catalogType').value;
            const url = type === 'department' ? '<?= route_to('hr.catalogs.department.save') ?>' : '<?= route_to('hr.catalogs.job.save') ?>';
            
            const formData = new FormData(this);
            // Incluir el token CSRF global
            formData.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);
            
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(updateCsrfToken)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    notifyShow(data.message, 'success');
                    catalogModal.hide();
                    location.reload(); 
                } else {
                    notifyShow(data.message || 'Error desconocido', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                notifyShow('Error en la petición', 'danger');
            });
        };

        // Eliminar via AJAX
        window.deleteCatalog = function(type, id) {
            if (!confirm('¿Estás seguro de eliminar este registro?')) return;
            
            let url = type === 'department' 
                ? '<?= route_to('hr.catalogs.department.delete', 0) ?>' 
                : '<?= route_to('hr.catalogs.job.delete', 0) ?>';
            url = url.replace('/0', '/' + id);

            let formData = new FormData();
            // Incluir el token CSRF global
            formData.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(updateCsrfToken)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    notifyShow(data.message, 'success');
                    location.reload();
                } else {
                    notifyShow(data.message || 'Error desconocido', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                notifyShow('Error en la petición', 'danger');
            });
        };
    });
</script>
<?php $this->endSection(); ?>
