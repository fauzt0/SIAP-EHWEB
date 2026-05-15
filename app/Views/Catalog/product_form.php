<?php $this->extend($layout); ?>

<?php $this->section('title'); echo esc($pageTitle); $this->endSection(); ?>

<?php $this->section('pageStyles'); ?>
<style>
/* ── Galería de imágenes Glassmorphism ── */
.img-gallery-drop {
    border: 2px dashed #c9d1d9;
    border-radius: 12px;
    background: rgba(248, 249, 250, 0.6);
    backdrop-filter: blur(4px);
    transition: border-color .2s, background .2s;
    cursor: pointer;
    min-height: 140px;
}
.img-gallery-drop:hover, .img-gallery-drop.dragover {
    border-color: #0d6efd;
    background: rgba(13, 110, 253, 0.05);
}
.img-thumb-card {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,.1);
    transition: transform .2s;
}
.img-thumb-card:hover { transform: translateY(-2px); }
.img-thumb-card img   { width: 100%; height: 110px; object-fit: cover; }
.img-thumb-overlay {
    position: absolute; inset: 0;
    background: rgba(0,0,0,.45);
    opacity: 0; transition: opacity .2s;
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.img-thumb-card:hover .img-thumb-overlay { opacity: 1; }
.badge-main-img {
    position: absolute; top: 6px; left: 6px;
    font-size: .65rem;
}

/* ── Atributos dinámicos ── */
.attr-row { animation: fadeInDown .2s ease; }
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}
/* ── Switch de tipo de producto ── */
.type-tab { cursor: pointer; transition: all .2s; border-bottom: 3px solid transparent; }
.type-tab.active { border-bottom-color: #0d6efd; color: #0d6efd !important; font-weight: 600; }
</style>
<?php $this->endSection(); ?>

<?php $this->section('main') ?>
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

<main class="content">
    <div class="container-fluid p-0">

        <div class="row mb-3 align-items-center">
            <div class="col-auto d-none d-sm-block">
                <h1 class="h3 mb-0"><?= esc($pageTitle) ?></h1>
            </div>
            <div class="col-auto ms-auto text-end">
                <nav aria-label="breadcrumb"><?= isset($breadcrumb) ? $breadcrumb : '' ?></nav>
            </div>
        </div>

        <form id="product-form" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="id" id="product-id" value="<?= isset($response['product']) && $response['product'] ? $response['product']->id : '' ?>">

        <div class="row g-4">

            <?php /* ── COLUMNA PRINCIPAL ── */ ?>
            <div class="col-lg-8">

                <?php /* Datos Generales */ ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Información General</h5>
                    </div>
                    <div class="card-body p-4">

                        <?php /* Selector de Tipo */ ?>
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase text-muted">Tipo de Producto</label>
                            <div class="d-flex gap-4 border-bottom pb-2" id="type-tabs">
                                <span class="type-tab text-muted pb-1 <?= (empty($response['product']) || $response['product']->product_type === 'service') ? 'active' : '' ?>"
                                      data-type="service">
                                    <i class="fas fa-cloud me-1"></i>Servicio
                                </span>
                                <span class="type-tab text-muted pb-1 <?= (!empty($response['product']) && $response['product']->product_type === 'physical') ? 'active' : '' ?>"
                                      data-type="physical">
                                    <i class="fas fa-box me-1"></i>Producto Físico
                                </span>
                                <span class="type-tab text-muted pb-1 <?= (!empty($response['product']) && $response['product']->product_type === 'digital') ? 'active' : '' ?>"
                                      data-type="digital">
                                    <i class="fas fa-key me-1"></i>Digital / Licencia
                                </span>
                            </div>
                            <input type="hidden" name="product_type" id="product_type"
                                   value="<?= !empty($response['product']) ? esc($response['product']->product_type) : 'service' ?>">
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nombre Comercial <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" name="commercial_name" id="commercial_name" required
                                       value="<?= !empty($response['product']) ? esc($response['product']->commercial_name) : '' ?>"
                                       placeholder="Ej: Hosting Básico Pro">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nombre Interno</label>
                                <input type="text" class="form-control form-control-lg" name="internal_name" id="internal_name"
                                       value="<?= !empty($response['product']) ? esc($response['product']->internal_name) : '' ?>"
                                       placeholder="Nombre para uso interno">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">SKU <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace text-uppercase" name="sku" id="sku" required
                                           value="<?= !empty($response['product']) ? esc($response['product']->sku) : '' ?>"
                                           placeholder="HOST-001" <?= $response['isEdit'] ? 'readonly' : '' ?>>
                                    <?php if (!$response['isEdit']): ?>
                                    <button class="btn btn-outline-secondary" type="button" onclick="generateSku()" title="Generar Automático">
                                        <i class="fas fa-random"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="form-text">Código único de identificación. <?= $response['isEdit'] ? 'No modificable en edición.' : '' ?></div>
                            </div>
                            <?php /* Código de barras: solo para Físicos */ ?>
                            <div class="col-md-4 section-physical" style="display: <?= (!empty($response['product']) && $response['product']->product_type === 'physical') ? '' : 'none' ?>">
                                <label class="form-label fw-semibold">Código de Barras</label>
                                <input type="text" class="form-control font-monospace" name="barcode"
                                       value="<?= !empty($response['product']) ? esc($response['product']->barcode ?? '') : '' ?>"
                                       placeholder="EAN/UPC">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Categoría</label>
                                <select class="form-select" name="category_id" id="category_id">
                                    <option value="">Sin categoría</option>
                                    <?php foreach ($response['categories'] as $cat): ?>
                                    <option value="<?= $cat->id ?>" data-icon="<?= esc($cat->icon ?? 'fas fa-folder') ?>" <?= (!empty($response['product']) && $response['product']->category_id == $cat->id) ? 'selected' : '' ?>>
                                        <?= esc($cat->display_name ?? $cat->name) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Marca</label>
                                <select class="form-select" name="brand_id" id="brand_id">
                                    <option value="">Sin marca</option>
                                    <?php foreach ($response['brands'] as $brand): ?>
                                    <option value="<?= $brand->id ?>" <?= (!empty($response['product']) && $response['product']->brand_id == $brand->id) ? 'selected' : '' ?>>
                                        <?= esc($brand->name) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Descripción Corta</label>
                                <input type="text" class="form-control" name="description_short"
                                       value="<?= !empty($response['product']) ? esc($response['product']->description_short ?? '') : '' ?>"
                                       placeholder="Resumen visible en listados y propuestas (máx. 255 caracteres)" maxlength="255">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Descripción Larga</label>
                                <textarea class="form-control" name="description_long" rows="4"
                                          placeholder="Descripción detallada del producto o servicio..."><?= !empty($response['product']) ? esc($response['product']->description_long ?? '') : '' ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <?php /* Atributos Dinámicos (EAV) */ ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0"><i class="fas fa-list-ul me-2 text-info"></i>Especificaciones Técnicas</h5>
                        <button type="button" class="btn btn-sm btn-outline-info" id="btn-add-attr">
                            <i class="fas fa-plus me-1"></i>Añadir
                        </button>
                    </div>
                    <div class="card-body p-4">
                        <div id="attributes-container">
                            <?php if (!empty($response['attributes'])): ?>
                                <?php foreach ($response['attributes'] as $i => $attr): ?>
                                <div class="attr-row row g-2 mb-2 align-items-center">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control form-control-sm" name="attributes[<?= $i ?>][attr_key]"
                                               value="<?= esc($attr->attr_key) ?>" placeholder="Característica (ej: RAM)">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" class="form-control form-control-sm" name="attributes[<?= $i ?>][attr_value]"
                                               value="<?= esc($attr->attr_value) ?>" placeholder="Valor (ej: 4 GB)">
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="checkbox" name="attributes[<?= $i ?>][is_highlight]"
                                                   <?= $attr->is_highlight ? 'checked' : '' ?>>
                                            <label class="form-check-label small text-muted">Destacar</label>
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-attr w-100">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted small fst-italic mb-0" id="no-attrs-msg">
                                    Sin especificaciones. Haz clic en "Añadir" para agregar características como RAM, Disco, Paneles, etc.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div><?php /* /col-lg-8 */ ?>

            <?php /* ── COLUMNA LATERAL ── */ ?>
            <div class="col-lg-4">

                <?php /* Publicación / Estado */ ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0"><i class="fas fa-paper-plane me-2 text-success"></i>Publicación</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" name="active" id="active"
                                   <?= (empty($response['product']) || $response['product']->active) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="active">Producto Activo</label>
                            <div class="form-text">Solo los productos activos son visibles al vender.</div>
                        </div>
                        <hr>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" id="btn-submit">
                                <i class="fas fa-save me-1"></i>
                                <?= $response['isEdit'] ? 'Guardar Cambios' : 'Crear Producto' ?>
                            </button>
                            <a href="<?= route_to('catalog.products') ?>" class="btn btn-light">
                                <i class="fas fa-arrow-left me-1"></i>Volver al Listado
                            </a>
                        </div>
                    </div>
                </div>

                <?php /* Galería de Imágenes */ ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0"><i class="fas fa-images me-2 text-warning"></i>Galería</h5>
                    </div>
                    <div class="card-body p-3">

                        <?php /* Imágenes existentes */ ?>
                        <div class="row g-2 mb-3" id="existing-images">
                            <?php if (!empty($response['images'])): ?>
                                <?php foreach ($response['images'] as $img): ?>
                                <div class="col-6" id="img-card-<?= $img->id ?>">
                                    <div class="img-thumb-card">
                                        <img src="<?= base_url('uploads/catalog/' . $img->path) ?>"
                                             alt="Imagen de producto">
                                        <?php if ($img->is_main): ?>
                                        <span class="badge bg-success badge-main-img"><i class="fas fa-star me-1"></i>Portada</span>
                                        <?php endif; ?>
                                        <div class="img-thumb-overlay">
                                            <?php if (!$img->is_main): ?>
                                            <button type="button" class="btn btn-sm btn-light btn-set-main"
                                                    data-id="<?= $img->id ?>" title="Usar como portada">
                                                <i class="fas fa-star text-warning"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-danger btn-delete-img"
                                                    data-id="<?= $img->id ?>" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <?php /* Drop zone para nuevas imágenes */ ?>
                        <div class="img-gallery-drop d-flex flex-column align-items-center justify-content-center p-3"
                             id="img-dropzone">
                            <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                            <p class="text-muted small mb-1">Arrastra imágenes aquí o</p>
                            <label for="product_images" class="btn btn-sm btn-outline-secondary mb-0">
                                <i class="fas fa-folder-open me-1"></i>Seleccionar archivos
                            </label>
                            <input type="file" name="product_images[]" id="product_images"
                                   accept="image/*" multiple class="d-none">
                            <p class="text-muted" style="font-size:.7rem;" class="mt-1">JPG, PNG o WEBP — Máx. 2MB c/u</p>
                        </div>

                        <?php /* Preview de imágenes a subir */ ?>
                        <div class="row g-2 mt-2" id="preview-images"></div>

                    </div>
                </div>

            </div><?php /* /col-lg-4 */ ?>
            <?php if ($response['isEdit']): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <ul class="nav nav-tabs card-header-tabs" id="plans-relations-tabs">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#tab-plans">
                                    <i class="fas fa-tags me-1 text-primary"></i>Planes de Precios
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-relations">
                                    <i class="fas fa-link me-1 text-info"></i>Relaciones / Bundles
                                </a>
                            </li>
                            <?php if ($response['product']->product_type === 'physical'): ?>
                            <li class="nav-item" id="tab-inventory-nav">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-inventory">
                                    <i class="fas fa-boxes me-1 text-success"></i>Inventario
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="card-body p-4">
                        <div class="tab-content">

                            <div class="tab-pane fade show active" id="tab-plans">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <p class="text-muted small mb-0">Ciclos de cobro y precios para este producto.</p>
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-manage-plans"
                                            data-id="<?= $response['product']->id ?>"
                                            data-name="<?= esc($response['product']->commercial_name) ?>">
                                        <i class="fas fa-plus me-1"></i>Gestionar Planes
                                    </button>
                                </div>
                                <div id="inline-plans-list">
                                    <div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i></div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tab-relations">
                                <p class="text-muted small mb-3">
                                    <i class="fas fa-info-circle me-1 text-info"></i>
                                    Los <strong>gifts/bundles</strong> con período gratuito &gt; 0 aplican gratis solo ese tiempo. Después se cobran como servicio adicional.
                                </p>
                                <div id="relation-form" class="border rounded p-3 bg-light mb-3">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold small">Producto Relacionado</label>
                                            <select class="form-select form-select-sm" id="related-product-id" name="related_product_id">
                                                <option value="">Selecciona un producto...</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold small">Tipo</label>
                                            <select class="form-select form-select-sm" id="relation-type" name="relation_type">
                                                <option value="gift">Gift (Regalo)</option>
                                                <option value="bundle">Bundle (Paquete)</option>
                                                <option value="upsell">Upsell (Mejora)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2" id="duration-group">
                                            <label class="form-label fw-semibold small">
                                                Per. Gratuito <i class="fas fa-info-circle text-muted" title="Meses gratis. 0 = Permanente."></i>
                                            </label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" class="form-control" name="duration_months" id="duration-months" min="0" value="0">
                                                <span class="input-group-text">mes(es)</span>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold small">Override $</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" name="override_price" id="override-price" min="0" step="0.01" value="0.00">
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" id="btn-add-relation" class="btn btn-primary btn-sm w-100" title="Agregar relación">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div id="relations-list">
                                    <div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i></div>
                                </div>
                            </div>

                            <?php if ($response['product']->product_type === 'physical'): ?>
                            <div class="tab-pane fade" id="tab-inventory">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <p class="text-muted small mb-0">
                                        <i class="fas fa-info-circle me-1 text-info"></i>
                                        Existencias actuales por sucursal. Solo aplica para productos físicos.
                                    </p>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="btn-open-movement">
                                        <i class="fas fa-plus me-1"></i>Registrar Movimiento
                                    </button>
                                </div>
                                <div id="stock-table-container">
                                    <div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i></div>
                                </div>
                                <div class="mt-3">
                                    <h6 class="text-muted fw-semibold"><i class="fas fa-history me-1"></i>Últimos Movimientos (Kardex)</h6>
                                    <div id="kardex-container">
                                        <div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i></div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div><?php /* /row */ ?>
        </form>

    </div>
</main>
<?php $this->endSection() ?>

<?php $this->section('pageFooterScripts'); ?>
<script>
// ─────────────────────────────────────────────────────────────
// Variables de rutas (usando route_to para no hardcodear URLs)
// Sección 3 DOCUMENTACION_TECNICA.md
// ─────────────────────────────────────────────────────────────
const ROUTES = {
    store:        '<?= route_to('catalog.products.store') ?>',
    update:       '<?= base_url('nat/catalog/products/update/') ?>',
    deleteImage:  '<?= base_url('nat/catalog/products/images/delete/') ?>',
    setMain:      '<?= base_url('nat/catalog/products/images/set_main/') ?>',
};
const IS_EDIT     = <?= $response['isEdit'] ? 'true' : 'false' ?>;
const PRODUCT_ID  = <?= !empty($response['product']->id) ? $response['product']->id : 'null' ?>;

document.addEventListener('DOMContentLoaded', function () {
    initTypeTabs();
    initAttributeBuilder();
    initImageGallery();
    initFormSubmit();
    initCategorySelect();
});

function initCategorySelect() {
    function formatCategory(state) {
        if (!state.id) return state.text;
        const icon = state.element.getAttribute('data-icon') || 'fas fa-folder';
        return $('<span><i class="' + icon + ' text-muted me-2"></i>' + state.text + '</span>');
    }

    $('#category_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Seleccione una categoría',
        allowClear: true,
        templateResult: formatCategory,
        templateSelection: formatCategory
    });
}

function generateSku() {
    const randomNum = Math.floor(1000 + Math.random() * 9000);
    document.getElementById('sku').value = 'PRD-' + randomNum;
}

// ── Selector de tipo de producto ──────────────────────────────
function initTypeTabs() {
    const tabs = document.querySelectorAll('.type-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('product_type').value = this.dataset.type;

            // Mostrar/ocultar secciones según tipo
            const isPhysical = this.dataset.type === 'physical';
            document.querySelectorAll('.section-physical').forEach(el => {
                el.style.display = isPhysical ? '' : 'none';
            });
        });
    });
}

// ── Builder de Atributos Dinámicos (EAV) ─────────────────────
let attrIndex = <?= !empty($response['attributes']) ? count($response['attributes']) : 0 ?>;

function initAttributeBuilder() {
    document.getElementById('btn-add-attr').addEventListener('click', addAttrRow);
    document.addEventListener('click', function (e) {
        if (e.target.closest('.btn-remove-attr')) {
            e.target.closest('.attr-row').remove();
            checkNoAttrs();
        }
    });
}

function addAttrRow() {
    const noMsg = document.getElementById('no-attrs-msg');
    if (noMsg) noMsg.remove();

    const div = document.createElement('div');
    div.className = 'attr-row row g-2 mb-2 align-items-center';
    div.innerHTML = `
        <div class="col-md-4">
            <input type="text" class="form-control form-control-sm"
                   name="attributes[${attrIndex}][attr_key]" placeholder="Característica (ej: RAM)">
        </div>
        <div class="col-md-5">
            <input type="text" class="form-control form-control-sm"
                   name="attributes[${attrIndex}][attr_value]" placeholder="Valor (ej: 4 GB)">
        </div>
        <div class="col-md-2 text-center">
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="checkbox"
                       name="attributes[${attrIndex}][is_highlight]">
                <label class="form-check-label small text-muted">Destacar</label>
            </div>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-attr w-100">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
    document.getElementById('attributes-container').appendChild(div);
    attrIndex++;
}

function checkNoAttrs() {
    const container = document.getElementById('attributes-container');
    if (container.querySelectorAll('.attr-row').length === 0) {
        container.innerHTML = `<p class="text-muted small fst-italic mb-0" id="no-attrs-msg">
            Sin especificaciones. Haz clic en "Añadir" para agregar características.</p>`;
    }
}

// ── Galería de imágenes ───────────────────────────────────────
function initImageGallery() {
    const dropzone = document.getElementById('img-dropzone');
    const fileInput = document.getElementById('product_images');

    // Drag & Drop
    dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('dragover'); });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
    dropzone.addEventListener('drop', e => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        fileInput.files = e.dataTransfer.files;
        showPreviews(fileInput.files);
    });

    // Click en input
    fileInput.addEventListener('change', () => showPreviews(fileInput.files));

    // Delegación: Eliminar imagen existente
    document.addEventListener('click', function (e) {
        const btnDel = e.target.closest('.btn-delete-img');
        if (btnDel) {
            if (!confirm('¿Eliminar esta imagen?')) return;
            sendImageRequest(ROUTES.deleteImage + btnDel.dataset.id, btnDel.dataset.id, 'delete');
        }

        // Marcar como principal
        const btnMain = e.target.closest('.btn-set-main');
        if (btnMain) {
            sendImageRequest(ROUTES.setMain + btnMain.dataset.id, btnMain.dataset.id, 'set_main');
        }
    });
}

function showPreviews(files) {
    const container = document.getElementById('preview-images');
    container.innerHTML = '';
    Array.from(files).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = e => {
            const col = document.createElement('div');
            col.className = 'col-6';
            col.innerHTML = `
                <div class="img-thumb-card">
                    <img src="${e.target.result}" alt="${file.name}">
                    <div class="img-thumb-overlay">
                        <span class="text-white small fw-bold"><i class="fas fa-upload me-1"></i>Nueva</span>
                    </div>
                </div>`;
            container.appendChild(col);
        };
        reader.readAsDataURL(file);
    });
}

function sendImageRequest(url, imageId, action) {
    const formData = new FormData();
    formData.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);

    fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => {
        const newToken = r.headers.get('<?= csrf_header() ?>');
        if (newToken) document.getElementById('csrf_token').value = newToken;
        return r.json();
    })
    .then(data => {
        if (data.success) {
            notifyShow(data.message, 'success');
            if (action === 'delete') {
                document.getElementById('img-card-' + imageId)?.remove();
            } else if (action === 'set_main') {
                // Recargar para actualizar badges de portada
                window.location.reload();
            }
        } else {
            notifyShow(data.message || 'Error', 'danger');
        }
    })
    .catch(() => notifyShow('Error de conexión', 'danger'));
}

// ── Envío del formulario principal ───────────────────────────
function initFormSubmit() {
    document.getElementById('product-form').addEventListener('submit', function (e) {
        e.preventDefault();

        const btn = document.getElementById('btn-submit');
        const origHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

        const formData = new FormData(this);
        formData.set('<?= csrf_token() ?>', document.getElementById('csrf_token').value);

        // Ajustar active (checkbox)
        if (!document.getElementById('active').checked) formData.set('active', '0');

        const url    = IS_EDIT ? ROUTES.update + PRODUCT_ID : ROUTES.store;

        fetch(url, {
            method: 'POST',
            headers: { 
                'X-Requested-With': 'XMLHttpRequest',
                '<?= csrf_header() ?>': document.getElementById('csrf_token').value
            },
            body: formData
        })
        .then(r => {
            const newToken = r.headers.get('<?= csrf_header() ?>');
            if (newToken) document.getElementById('csrf_token').value = newToken;
            return r.json();
        })
        .then(data => {
            if (data.success) {
                notifyShow(data.message, 'success');
                // Si es creación, redirigir al form de edición
                if (!IS_EDIT && data.response?.redirect) {
                    setTimeout(() => window.location.href = data.response.redirect, 800);
                }
            } else {
                const errors = data.error || data.errors || {};
                const msg = typeof errors === 'object'
                    ? Object.values(errors).join('<br>')
                    : (data.message || 'Error al guardar');
                notifyShow(msg, 'danger');
            }
        })
        .catch(() => notifyShow('Error de conexión', 'danger'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        });
    });
}
</script>

<?php if ($response['isEdit']): ?>
<script>
const PRODUCT_ID_EDIT  = <?= $response['product']->id ?>;
const RELATIONS_BASE   = '<?= base_url('nat/catalog/products/') ?>';
const RELATIONS_DELETE = '<?= route_to('catalog.relations.delete', 0) ?>'.replace('/0', '/');

// ── Carga inline de planes en el tab ────────────────────
(function loadInlinePlans() {
    fetch(RELATIONS_BASE + PRODUCT_ID_EDIT + '/plans', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        const container = document.getElementById('inline-plans-list');
        if (!data.success || !data.response.plans.length) {
            container.innerHTML = '<p class="text-muted fst-italic small">Sin planes. Haz clic en "Gestionar Planes".</p>';
            return;
        }
        const cycleMap = { monthly: 'Mensual', yearly: 'Anual', one_time: 'Pago Único', custom: 'Personalizado' };
        container.innerHTML = `<div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr><th>Plan</th><th>Ciclo</th><th>Venta</th><th>Renovación</th><th>Setup Fee</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    ${data.response.plans.map(p => `
                    <tr>
                        <td class="fw-semibold">${p.plan_name}</td>
                        <td><span class="badge badge-subtle-secondary">${cycleMap[p.billing_cycle] || p.billing_cycle}</span></td>
                        <td class="fw-bold">$${parseFloat(p.sale_price).toFixed(2)}</td>
                        <td class="text-muted">${p.billing_cycle !== 'one_time' ? '$' + parseFloat(p.renewal_price).toFixed(2) : '—'}</td>
                        <td class="text-muted">${parseFloat(p.setup_fee) > 0 ? '$' + parseFloat(p.setup_fee).toFixed(2) : '—'}</td>
                        <td><span class="badge badge-subtle-${p.is_active ? 'success' : 'secondary'}">${p.is_active ? 'Activo' : 'Inactivo'}</span></td>
                    </tr>`).join('')}
                </tbody>
            </table></div>`;
    })
    .catch(() => {});
})();

// ── Select2 para búsqueda de producto relacionado ─────────────
// Sección 10 DOCUMENTACION_TECNICA.md: dropdownParent para z-index
document.querySelector('[href="#tab-relations"]')?.addEventListener('shown.bs.tab', function () {
    if (typeof $ !== 'undefined' && $('#related-product-id').data('select2') === undefined) {
        $('#related-product-id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Buscar producto...',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#related-product-id').parent(),
            ajax: {
                url: '<?= base_url('nat/catalog/products/list_ajax') ?>',
                type: 'POST',
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return {
                        '<?= csrf_token() ?>': document.getElementById('csrf_token').value,
                        search: { value: params.term || '' },
                        start: 0, length: 20, draw: 1, filter_active: '1'
                    };
                },
                processResults: function (data) {
                    if (!data.data) return { results: [] };
                    return {
                        results: data.data.map(function (row) {
                            // row[1] = HTML con nombre + SKU
                            const nameTmp = document.createElement('div');
                            nameTmp.innerHTML = row[1];
                            const name = nameTmp.querySelector('.fw-semibold')?.textContent?.trim() || 'Producto';
                            const sku  = nameTmp.querySelector('.font-monospace')?.textContent?.trim() || '';

                            // El ID numérico está en data-id de los botones de acción (row[6])
                            const actTmp = document.createElement('div');
                            actTmp.innerHTML = row[6] || '';
                            const productId = parseInt(actTmp.querySelector('[data-id]')?.getAttribute('data-id') || '0');

                            // Excluir el producto actual y entradas sin ID válido
                            if (!productId || productId === PRODUCT_ID_EDIT) return null;
                            return { id: productId, text: name + (sku ? ' [' + sku + ']' : '') };
                        }).filter(Boolean)
                    };
                }
            }
        });
        loadRelations();
    }
});

// ── Cargar relaciones ─────────────────────────────────────────
function loadRelations() {
    const container = document.getElementById('relations-list');
    fetch(RELATIONS_BASE + PRODUCT_ID_EDIT + '/relations', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        cache: 'no-store'
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success || !data.response.length) {
            container.innerHTML = '<p class="text-muted fst-italic small">Sin relaciones configuradas.</p>';
            return;
        }
        const typeMap = { gift: ['Regalo', 'success'], bundle: ['Bundle', 'primary'], upsell: ['Upsell', 'warning'] };
        container.innerHTML = data.response.map(rel => {
            const [label, color] = typeMap[rel.relation_type] || ['N/A', 'secondary'];
            const period = parseInt(rel.duration_months) === 0 ? 'Permanente' : rel.duration_months + ' mes(es) gratis';
            const price  = parseFloat(rel.override_price) > 0 ? '$' + parseFloat(rel.override_price).toFixed(2) : 'Precio original';
            return `
            <div class="d-flex align-items-center justify-content-between border rounded p-2 mb-2 bg-white">
                <div class="d-flex gap-3 align-items-center">
                    <span class="badge badge-subtle-${color}">${label}</span>
                    <div>
                        <div class="fw-semibold small">${rel.related_name || 'Producto'}</div>
                        <small class="text-muted">${rel.related_sku} · <strong>${period}</strong> · ${price}</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-relation" data-id="${rel.id}" title="Eliminar">
                    <i class="fas fa-times"></i>
                </button>
            </div>`;
        }).join('');
    })
    .catch(() => {});
}

// ── Agregar relación ──────────────────────────────────────────
// El panel de relaciones es un <div> (no un <form>) para evitar
// la anidación de formularios que HTML no permite. El botón +
// tiene id="btn-add-relation" y type="button".
// Un producto puede tener múltiples relaciones con distintos
// productos o tipos (el modelo previene duplicados del mismo par).
function submitRelation() {
    const select2Val = typeof $ !== 'undefined'
        ? $('#related-product-id').val()
        : document.getElementById('related-product-id').value;

    if (!select2Val) { notifyShow('Selecciona un producto relacionado.', 'warning'); return; }

    const formData = new FormData();
    formData.set('<?= csrf_token() ?>', document.getElementById('csrf_token').value);
    formData.set('related_product_id', select2Val);
    formData.set('relation_type',   document.getElementById('relation-type').value);
    formData.set('duration_months', document.getElementById('duration-months').value);
    formData.set('override_price',  document.getElementById('override-price').value);

    fetch(RELATIONS_BASE + PRODUCT_ID_EDIT + '/relations/store', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => {
        const t = r.headers.get('<?= csrf_header() ?>');
        if (t) document.getElementById('csrf_token').value = t;
        return r.json();
    })
    .then(data => {
        if (data.success) {
            notifyShow(data.message, 'success');
            loadRelations();
            // Resetear campos para permitir agregar otra relación
            if (typeof $ !== 'undefined') $('#related-product-id').val(null).trigger('change');
            document.getElementById('duration-months').value = '0';
            document.getElementById('override-price').value  = '0.00';
            document.getElementById('relation-type').value   = 'gift';
            document.getElementById('duration-group').style.display = '';
        } else {
            let errorMsg = data.message || 'Error al crear la relación';
            if (data.errors && typeof data.errors === 'object') {
                errorMsg += ': ' + Object.values(data.errors).join(', ');
            } else if (data.errors) {
                errorMsg += ': ' + data.errors;
            }
            notifyShow(errorMsg, 'danger');
        }
    })
    .catch(() => notifyShow('Error de conexión', 'danger'));
}

document.getElementById('btn-add-relation')?.addEventListener('click', submitRelation);

// ── Eliminar relación ─────────────────────────────────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-delete-relation');
    if (!btn) return;
    e.preventDefault();
    if (!confirm('¿Eliminar esta relación?')) return;

    const formData = new FormData();
    formData.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);

    fetch(RELATIONS_DELETE + btn.dataset.id, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => {
        const t = r.headers.get('<?= csrf_header() ?>');
        if (t) document.getElementById('csrf_token').value = t;
        return r.json();
    })
    .then(data => {
        if (data.success) { notifyShow(data.message, 'success'); loadRelations(); }
        else notifyShow(data.message || 'Error', 'danger');
    })
    .catch(() => notifyShow('Error de conexión', 'danger'));
});

// ── Ocultar duration_months para upsell ──────────────────────
document.getElementById('relation-type')?.addEventListener('change', function () {
    const isUpsell = this.value === 'upsell';
    document.getElementById('duration-group').style.display = isUpsell ? 'none' : '';
    if (isUpsell) document.getElementById('duration-months').value = '0';
});

<?php if ($response['isEdit'] && $response['product']->product_type === 'physical'): ?>
// ── Inventario: carga de stock y Kardex ──────────────────────
// Se activa al mostrar la pestaña de Inventario (Sección 1 DOCUMENTACION_TECNICA.md)
const INVENTORY_PRODUCT_ID = <?= $response['product']->id ?>;

function loadStock() {
    fetch('<?= route_to('catalog.inventory.stock', $response['product']->id) ?>', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        const container = document.getElementById('stock-table-container');
        if (!data.success || !data.response.length) {
            container.innerHTML = '<p class="text-muted small fst-italic">Sin registros de stock. Registra una entrada para inicializar.</p>';
            return;
        }
        let rows = '';
        data.response.forEach(s => {
            const alertClass = (parseFloat(s.stock) <= parseFloat(s.min_alert) && parseFloat(s.min_alert) > 0)
                ? 'table-danger' : '';
            rows += `<tr class="${alertClass}">
                <td>${s.branch_name || 'Sucursal #' + s.org_branches_id}</td>
                <td class="fw-semibold">${parseFloat(s.stock).toFixed(2)}</td>
                <td>
                    <div class="input-group input-group-sm" style="max-width:120px">
                        <input type="number" class="form-control min-alert-input" data-stock-id="${s.id}"
                               data-branch-id="${s.org_branches_id}" value="${parseFloat(s.min_alert).toFixed(2)}" min="0" step="1">
                        <button class="btn btn-outline-secondary btn-save-alert" type="button"
                                data-branch-id="${s.org_branches_id}" title="Guardar alerta"><i class="fas fa-save"></i></button>
                    </div>
                </td>
            </tr>`;
        });
        container.innerHTML = `<table class="table table-sm table-hover table-striped mb-0">
            <thead class="table-light"><tr>
                <th>Sucursal</th><th>Existencia</th><th>Alerta Mínima</th>
            </tr></thead><tbody>${rows}</tbody>
        </table>`;
        // Guardar alerta mínima
        container.querySelectorAll('.btn-save-alert').forEach(btn => {
            btn.addEventListener('click', function () {
                const branchId = this.dataset.branchId;
                const input    = container.querySelector(`.min-alert-input[data-branch-id="${branchId}"]`);
                const fd       = new FormData();
                fd.append('org_branch_id', branchId);
                fd.append('min_alert', input.value);
                fd.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);
                fetch('<?= route_to('catalog.inventory.min_alert', $response['product']->id) ?>', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        '<?= csrf_header() ?>': document.getElementById('csrf_token').value,
                    },
                    body: fd
                })
                .then(r => { const t = r.headers.get('<?= csrf_header() ?>'); if (t) document.getElementById('csrf_token').value = t; return r.json(); })
                .then(d => {
                    notifyShow(d.message, d.success ? 'success' : 'danger');
                    if (d.success) loadStock();
                })
                .catch(() => notifyShow('Error de conexión', 'danger'));
            });
        });
    });
}

function loadKardex() {
    fetch('<?= route_to('catalog.inventory.kardex', $response['product']->id) ?>', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        const container = document.getElementById('kardex-container');
        if (!data.success || !data.response.length) {
            container.innerHTML = '<p class="text-muted small fst-italic">Sin movimientos registrados.</p>';
            return;
        }
        const typeMap = { entry: '<span class="badge badge-subtle-success">Entrada</span>', exit: '<span class="badge badge-subtle-danger">Salida</span>', transfer: '<span class="badge badge-subtle-info">Transferencia</span>' };
        let rows = '';
        data.response.forEach(m => {
            const dest = m.target_branch_name ? ` → ${m.target_branch_name}` : '';
            rows += `<tr>
                <td class="text-muted small">${m.created_at}</td>
                <td>${typeMap[m.type] || m.type}</td>
                <td class="fw-semibold">${parseFloat(m.quantity).toFixed(2)}</td>
                <td>${m.branch_name || '-'}${dest}</td>
                <td class="text-muted small">${m.notes || '-'}</td>
            </tr>`;
        });
        container.innerHTML = `<div class="table-responsive" style="max-height:250px;overflow-y:auto">
            <table class="table table-sm table-hover table-striped mb-0">
                <thead class="table-light"><tr>
                    <th>Fecha</th><th>Tipo</th><th>Cantidad</th><th>Sucursal</th><th>Notas</th>
                </tr></thead><tbody>${rows}</tbody>
            </table></div>`;
    });
}

// Carga automática al abrir la tab de inventario
document.querySelector('[href="#tab-inventory"]')?.addEventListener('shown.bs.tab', function () {
    loadStock();
    loadKardex();
});

// Botón "Registrar Movimiento" abre el modal
document.getElementById('btn-open-movement')?.addEventListener('click', function () {
    const modal = new bootstrap.Modal(document.getElementById('modal-movement'));
    modal.show();
});

// Envío del formulario de movimiento
document.getElementById('btn-submit-movement')?.addEventListener('click', function () {
    const form = document.getElementById('form-movement');
    const fd   = new FormData(form);
    fd.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);

    fetch('<?= route_to('catalog.inventory.movement', $response['product']->id) ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_header() ?>': document.getElementById('csrf_token').value,
        },
        body: fd
    })
    .then(r => { const t = r.headers.get('<?= csrf_header() ?>'); if (t) document.getElementById('csrf_token').value = t; return r.json(); })
    .then(data => {
        if (data.success) {
            notifyShow(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('modal-movement')).hide();
            form.reset();
            loadStock();
            loadKardex();
        } else {
            notifyShow(data.message || 'Error al registrar el movimiento', 'danger');
        }
    })
    .catch(() => notifyShow('Error de conexión', 'danger'));
});

// Mostrar/Ocultar campo de sucursal destino en transferencias
document.getElementById('movement-type')?.addEventListener('change', function () {
    const targetGroup = document.getElementById('target-branch-group');
    targetGroup.style.display = this.value === 'transfer' ? '' : 'none';
});
<?php endif; ?>
</script>

<?= $this->include('Catalog/Partials/offcanvas_plans') ?>
<?php if ($response['isEdit'] && $response['product']->product_type === 'physical'): ?>
<!-- Modal: Registro de Movimiento de Inventario -->
<div class="modal fade" id="modal-movement" tabindex="-1" aria-labelledby="modal-movement-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-movement-label">
                    <i class="fas fa-boxes me-2 text-success"></i>Registrar Movimiento de Inventario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-movement" novalidate>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de Movimiento <span class="text-danger">*</span></label>
                        <select class="form-select" name="type" id="movement-type" required>
                            <option value="entry">Entrada (Compra / Ajuste +)</option>
                            <option value="exit">Salida (Venta / Ajuste -)</option>
                            <option value="transfer">Transferencia entre Sucursales</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sucursal Origen <span class="text-danger">*</span></label>
                        <select class="form-select" name="org_branch_id" id="movement-branch" required>
                            <option value="">Selecciona una sucursal...</option>
                            <?php foreach ($response['branches'] as $branch): ?>
                            <option value="<?= $branch->id ?>"><?= esc($branch->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3" id="target-branch-group" style="display:none">
                        <label class="form-label fw-semibold">Sucursal Destino <span class="text-danger">*</span></label>
                        <select class="form-select" name="target_org_branch_id" id="movement-target-branch">
                            <option value="">Selecciona una sucursal...</option>
                            <?php foreach ($response['branches'] as $branch): ?>
                            <option value="<?= $branch->id ?>"><?= esc($branch->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="quantity" min="0.01" step="0.01" placeholder="0.00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notas / Referencia</label>
                        <input type="text" class="form-control" name="notes" placeholder="Ej: Factura #123, Ajuste por merma...">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-submit-movement">
                    <i class="fas fa-check me-1"></i>Registrar
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>
<?php $this->endSection(); ?>
