<?php $this->extend($layout); ?>

<?php $this->section('title');
echo $pageTitle;
$this->endSection(); ?>

<?php $this->section('pageStyles'); ?>
<style>
    .contract-tree { list-style: none; padding-left: 0; }
    .contract-tree ul { list-style: none; padding-left: 1.5rem; border-left: 2px solid #dee2e6; margin-left: 0.5rem; }
    .contract-tree-node { margin-bottom: 0.75rem; }
    .contract-tree-card { border: 1px solid #e9ecef; border-radius: 0.5rem; padding: 1rem; background: #fff; }
    .contract-tree-card.active-version { border-color: #198754; background: #f8fff9; }
    .status-active { background-color: #d1e7dd; color: #0f5132; padding: 0.35em 0.65em; font-size: 0.85rem; }
    .status-historical { background-color: #f8f9fa; color: #6c757d; border: 1px solid #dee2e6; padding: 0.35em 0.65em; font-size: 0.85rem; }
    #previewModal .modal-dialog { max-width: 90%; height: 90vh; }
    #previewModal .modal-content { height: 100%; }
    #previewModal .modal-body { padding: 0; height: calc(100% - 110px); }
    #preview-iframe { width: 100%; height: 100%; border: none; }
</style>
<?php $this->endSection(); ?>

<?php
$contracts     = $response['contracts'] ?? [];
$contractTree  = $response['contract_tree'] ?? [];
$profileId     = $response['profile_id'];
$workerName    = $response['worker_name'];

$renderTreeNode = static function ($node, $profileId) use (&$renderTreeNode) {
    $c = $node['contract'];
    $isActive = $c->status === 'active';
    $displayType = $c->contract_type_name ?: ($c->template_name ?: 'Personalizado');
    ob_start();
    ?>
    <li class="contract-tree-node">
        <div class="contract-tree-card <?= $isActive ? 'active-version' : '' ?>">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <span class="badge bg-primary me-1">v<?= (int)($c->version ?? 1) ?></span>
                    <?php if ($isActive): ?>
                        <span class="badge status-active"><i class="fas fa-check-circle me-1"></i>Vigente</span>
                    <?php else: ?>
                        <span class="badge status-historical">Histórico</span>
                    <?php endif; ?>
                    <?php if (!empty($c->change_source)): ?>
                        <span class="badge bg-light text-dark border"><?= esc(ucfirst($c->change_source)) ?></span>
                    <?php endif; ?>
                    <div class="fw-semibold text-primary mt-1"><?= esc($displayType) ?></div>
                    <div class="text-muted small"><?= esc($c->reason ?: 'Sin comentario') ?></div>
                    <div class="small text-muted mt-1">
                        <i class="far fa-calendar-alt me-1"></i><?= date('d/m/Y H:i', strtotime($c->created_at)) ?>
                    </div>
                </div>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-info btn-preview" data-id="<?= $c->id ?>" title="Ver"><i class="fas fa-eye"></i></button>
                    <a href="<?= route_to('hr.contracts.generate_edit', $profileId, $c->id) ?>" class="btn btn-warning text-dark" title="Fork / Editar"><i class="fas fa-code-branch"></i></a>
                    <a href="<?= route_to('hr.contracts.download', $c->id) ?>?action=download" class="btn btn-outline-secondary" title="PDF"><i class="fas fa-download"></i></a>
                </div>
            </div>
        </div>
        <?php if (!empty($node['children'])): ?>
            <ul>
                <?php foreach ($node['children'] as $child): ?>
                    <?= $renderTreeNode($child, $profileId) ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </li>
    <?php
    return ob_get_clean();
};
?>

<?php $this->section('main') ?>
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

<main class="content">
    <div class="container-fluid p-0">
        <div class="row mb-2 mb-xl-3 align-items-center">
            <div class="col-auto d-none d-sm-block">
                <h1 class="h3 mb-0"><?= esc($pageTitle) ?></h1>
                <p class="text-muted mb-0">Historial y árbol de versiones para: <strong><?= esc($workerName) ?></strong></p>
            </div>
            <div class="col-auto ms-auto text-end">
                <nav aria-label="breadcrumb" class="d-inline-block"><?= isset($breadcrumb) ? $breadcrumb : '' ?></nav>
                <div class="ms-2 d-inline-block">
                    <a href="<?= route_to('hr.contracts.generate', $profileId) ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Nuevo Contrato
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0"><i class="fas fa-sitemap me-2 text-primary"></i>Árbol de Contratos</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($contractTree)): ?>
                            <p class="text-muted text-center py-4 mb-0">No hay contratos registrados.</p>
                        <?php else: ?>
                            <ul class="contract-tree">
                                <?php foreach ($contractTree as $root): ?>
                                    <?= $renderTreeNode($root, $profileId) ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-white pt-4">
                        <div class="row align-items-center">
                            <div class="col-md-6"><h5 class="card-title mb-0">Listado de Versiones</h5></div>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                                    <input type="text" id="contract-search" class="form-control border-start-0 ps-0" placeholder="Buscar...">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="table-contracts">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Versión</th>
                                        <th>Tipo</th>
                                        <th>Motivo</th>
                                        <th>Fecha</th>
                                        <th>Estatus</th>
                                        <th class="text-end pe-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($contracts)): ?>
                                        <tr><td colspan="6" class="text-center py-5 text-muted">Sin registros.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($contracts as $c): ?>
                                            <tr>
                                                <td class="ps-4"><span class="badge bg-light text-dark border fw-bold">v<?= (int)($c->version ?? 1) ?></span></td>
                                                <td><span class="fw-semibold text-primary"><?= esc($c->contract_type_name ?: $c->template_name ?: 'Personalizado') ?></span></td>
                                                <td><div class="text-truncate" style="max-width:200px;" title="<?= esc($c->reason) ?>"><?= esc($c->reason ?: '—') ?></div></td>
                                                <td><?= date('d/m/Y H:i', strtotime($c->created_at)) ?></td>
                                                <td>
                                                    <?php if ($c->status === 'active'): ?>
                                                        <span class="badge status-active">Vigente</span>
                                                    <?php else: ?>
                                                        <span class="badge status-historical">Histórico</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-info btn-preview" data-id="<?= $c->id ?>"><i class="fas fa-eye"></i></button>
                                                        <a href="<?= route_to('hr.contracts.generate_edit', $profileId, $c->id) ?>" class="btn btn-warning text-dark"><i class="fas fa-code-branch"></i></a>
                                                        <a href="<?= route_to('hr.contracts.download', $c->id) ?>" class="btn btn-outline-secondary"><i class="fas fa-download"></i></a>
                                                    </div>
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
</main>

<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title text-white"><i class="fas fa-file-pdf me-2"></i>Detalle del Contrato</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light"><iframe id="preview-iframe" src="about:blank"></iframe></div>
            <div class="modal-footer bg-white border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a id="btn-download-modal" href="#" class="btn btn-primary"><i class="fas fa-download me-1"></i>Descargar PDF</a>
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
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const term = this.value.toLowerCase();
            for (let i = 0; i < rows.length; i++) {
                rows[i].style.display = rows[i].textContent.toLowerCase().includes(term) ? '' : 'none';
            }
        });
    }
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    const previewIframe = document.getElementById('preview-iframe');
    const btnDownloadModal = document.getElementById('btn-download-modal');
    document.querySelectorAll('.btn-preview').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            previewIframe.src = '<?= route_to('hr.contracts.download', 0) ?>'.replace('/0', '/' + id) + '?action=view';
            btnDownloadModal.href = '<?= route_to('hr.contracts.download', 0) ?>'.replace('/0', '/' + id) + '?action=download';
            previewModal.show();
        });
    });
    document.getElementById('previewModal').addEventListener('hidden.bs.modal', function () {
        previewIframe.src = 'about:blank';
    });
});
</script>
<?php $this->endSection(); ?>
