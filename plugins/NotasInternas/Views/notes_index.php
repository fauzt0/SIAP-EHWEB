<?php $this->extend($layout); ?>

<?php $this->section('title'); ?>
<?= esc($pageTitle ?? 'Notas internas') ?>
<?php $this->endSection(); ?>

<?php $this->section('pageStyles'); ?>
<style>
    .ni-composer, .ni-card {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .12);
    }
    .ni-composer {
        background: #fff9c4;
        max-width: 42rem;
    }
    .ni-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 1rem;
    }
    .ni-card {
        min-height: 140px;
        background: #fff9c4;
    }
    .ni-body[contenteditable="true"]:empty:before {
        content: attr(data-placeholder);
        color: #6c757d;
    }
</style>
<?php $this->endSection(); ?>

<?php $this->section('main'); ?>
<div class="container-fluid p-0">
    <div class="row mb-3">
        <div class="col-12">
            <?= $breadcrumb ?? '' ?>
            <h1 class="h3 mb-1"><i class="fas fa-sticky-note me-2"></i><?= esc($pageTitle ?? 'Notas internas') ?></h1>
            <p class="text-muted mb-0">Tus notas personales. El listado y el guardado se conectan en el siguiente paso.</p>
        </div>
    </div>

    <div class="card ni-composer mb-4 mx-auto">
        <div class="card-body">
            <input type="text" class="form-control border-0 bg-transparent fw-semibold px-0 mb-2" placeholder="Título" disabled>
            <div class="ni-body form-control border-0 bg-transparent px-0" contenteditable="false" data-placeholder="Escribe una nota…"></div>
            <div class="d-flex justify-content-between align-items-center mt-2 text-muted small">
                <span><i class="fas fa-lock me-1"></i>Composer deshabilitado hasta el CRUD AJAX</span>
                <button type="button" class="btn btn-sm btn-secondary" disabled>Guardar</button>
            </div>
        </div>
    </div>

    <div id="ni-grid" class="ni-grid">
        <div class="card ni-card">
            <div class="card-body">
                <h5 class="card-title">Ejemplo</h5>
                <p class="card-text mb-2">Cuando exista el listado, aquí aparecerán tus notas.</p>
                <p class="card-text"><small class="text-muted">Vista estática (paso E)</small></p>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>
