<?php $this->extend($layout); ?>

<?php $this->section('title');
echo $pageTitle;
$this->endSection(); ?>

<?php $this->section('pageStyles'); ?>
<style>
    .contract-version-badge {
        font-size: 0.9rem;
        padding: 0.5em 0.8em;
    }
    .status-active { background-color: #d1e7dd; color: #0f5132; padding: 0.5em 0.8em; font-size: 0.85rem; }
    .status-historical { background-color: #f8f9fa; color: #6c757d; border: 1px solid #dee2e6; padding: 0.5em 0.8em; font-size: 0.85rem; }
    
    #table-contracts th, #table-contracts td {
        padding-top: 1.25rem;
        padding-bottom: 1.25rem;
    }
    
    #previewModal .modal-dialog {
        max-width: 90%;
        height: 90vh;
    }
    #previewModal .modal-content {
        height: 100%;
    }
    #previewModal .modal-body {
        padding: 0;
        height: calc(100% - 110px);
    }
    #preview-iframe {
        width: 100%;
        height: 100%;
        border: none;
    }
</style>
<?php $this->endSection(); ?>

<?php $this->section('main') ?>
<?php
$contracts = $response['contracts'] ?? [];
$profileId = $response['profile_id'];
$workerName = $response['worker_name'];
?>

<main class="content">
    <div class="container-fluid p-0">

        <!-- Encabezado + Breadcrumb -->
        <div class="row mb-2 mb-xl-3 align-items-center">
            <div class="col-auto d-none d-sm-block">
                <h1 class="h3 mb-0"><?= esc($pageTitle) ?></h1>
                <p class="text-muted mb-0">Historial completo para: <strong><?= esc($workerName) ?></strong></p>
            </div>
            <div class="col-auto ms-auto text-end">
                <nav aria-label="breadcrumb" class="d-inline-block">
                    <?= isset($breadcrumb) ? $breadcrumb : '' ?>
                </nav>
                <div class="ms-2 d-inline-block">
                    <a href="<?= route_to('hr.contracts.generate', $profileId) ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Nuevo Contrato
                    </a>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-header bg-white pt-4">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="card-title mb-0">Listado de Versiones</h5>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="contract-search" class="form-control border-start-0 ps-0" placeholder="Buscar por motivo, tipo o fecha...">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="table-contracts">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 80px;">Versión</th>
                                <th>Tipo de Contrato</th>
                                <th>Motivo / Comentario</th>
                                <th style="width: 160px;">Fecha de Registro</th>
                                <th>Estatus</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($contracts)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-file-contract fa-2x mb-2 d-block opacity-25"></i>
                                        No hay registros de contratos para este empleado.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $total = count($contracts);
                                foreach ($contracts as $index => $c): 
                                    $versionNum = $total - $index;
                                    $isFirst = ($index === 0);
                                ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="badge bg-light text-dark border fw-bold"><?= $versionNum ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                                $displayType = 'Personalizado';
                                                if (!empty($c->contract_type_name)) {
                                                    $displayType = $c->contract_type_name;
                                                } else if (!empty($c->template_name)) {
                                                    $displayType = $c->template_name;
                                                } else if (strpos($c->reason, ' - ') !== false) {
                                                    $parts = explode(' - ', $c->reason, 2);
                                                    $displayType = $parts[0];
                                                }
                                            ?>
                                            <span class="fw-semibold text-primary"><?= esc($displayType) ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                                $displayReason = $c->reason;
                                                if (strpos($c->reason, ' - ') !== false) {
                                                    $parts = explode(' - ', $c->reason, 2);
                                                    $displayReason = $parts[1];
                                                }
                                            ?>
                                            <div class="text-truncate" style="max-width: 300px;" title="<?= esc($displayReason) ?>">
                                                <?= esc($displayReason ?: 'Sin comentario') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="text-dark"><i class="far fa-calendar-alt me-1 text-muted"></i><?= date('d/m/Y', strtotime($c->created_at)) ?></span>
                                                <small class="text-muted" style="font-size: 0.75rem;"><i class="far fa-clock me-1"></i><?= date('H:i', strtotime($c->created_at)) ?> hrs</small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($isFirst && $c->status === 'active'): ?>
                                                <span class="badge status-active"><i class="fas fa-check-circle me-1"></i>Vigente</span>
                                            <?php else: ?>
                                                <span class="badge status-historical">Histórico</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <button class="btn btn-info btn-preview" data-id="<?= $c->id ?>" title="Ver Previsualización">
                                                    <i class="fas fa-eye me-1"></i> Ver
                                                </button>
                                                <a href="<?= route_to('hr.contracts.generate_edit', $profileId, $c->id) ?>" class="btn btn-warning text-dark" title="Editar a partir de esta versión">
                                                    <i class="fas fa-edit me-1"></i>
                                                </a>
                                                <a href="<?= route_to('hr.contracts.download', $c->id) ?>?action=download" class="btn btn-outline-secondary" title="Descargar PDF">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white py-3">
                <p class="small text-muted mb-0">Mostrando <?= count($contracts) ?> registros de contrato.</p>
            </div>
        </div>

    </div>
</main>

<!-- Modal para Previsualización -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title text-white"><i class="fas fa-file-pdf me-2"></i>Detalle del Contrato</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <iframe id="preview-iframe" src="about:blank"></iframe>
            </div>
            <div class="modal-footer bg-white border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a id="btn-download-modal" href="#" class="btn btn-primary">
                    <i class="fas fa-download me-1"></i>Descargar PDF
                </a>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('pageFooterScripts'); ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('contract-search');
    const table = document.getElementById('table-contracts');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    // Filtrado en tiempo real
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const term = this.value.toLowerCase();
            for (let i = 0; i < rows.length; i++) {
                const text = rows[i].textContent.toLowerCase();
                rows[i].style.display = text.includes(term) ? '' : 'none';
            }
        });
    }

    // Manejo de Modal de Previsualización
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    const previewIframe = document.getElementById('preview-iframe');
    const btnDownloadModal = document.getElementById('btn-download-modal');

    document.querySelectorAll('.btn-preview').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const viewUrl = '<?= base_url('nat/hr/contracts/download') ?>/' + id + '?action=view';
            const downloadUrl = '<?= base_url('nat/hr/contracts/download') ?>/' + id + '?action=download';
            
            previewIframe.src = viewUrl;
            btnDownloadModal.href = downloadUrl;
            previewModal.show();
        });
    });

    // Limpiar iframe al cerrar modal
    document.getElementById('previewModal').addEventListener('hidden.bs.modal', function () {
        previewIframe.src = 'about:blank';
    });
});
</script>
<?php $this->endSection(); ?>
