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

            {{-- ── COLUMNA PRINCIPAL ── --}}
            <div class="col-lg-8">

                {{-- Datos Generales --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Información General</h5>
                    </div>
                    <div class="card-body p-4">

                        {{-- Selector de Tipo --}}
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase text-muted">Tipo de Producto</label>
                            <div class="d-flex gap-4 border-bottom pb-2" id="type-tabs">
                                <span class="type-tab text-muted pb-1 <?= (!$response['isEdit'] || $response['product']->product_type === 'service') ? 'active' : '' ?>"
                                      data-type="service">
                                    <i class="fas fa-cloud me-1"></i>Servicio
                                </span>
                                <span class="type-tab text-muted pb-1 <?= ($response['isEdit'] && $response['product']->product_type === 'physical') ? 'active' : '' ?>"
                                      data-type="physical">
                                    <i class="fas fa-box me-1"></i>Producto Físico
                                </span>
                                <span class="type-tab text-muted pb-1 <?= ($response['isEdit'] && $response['product']->product_type === 'digital') ? 'active' : '' ?>"
                                      data-type="digital">
                                    <i class="fas fa-key me-1"></i>Digital / Licencia
                                </span>
                            </div>
                            <input type="hidden" name="product_type" id="product_type"
                                   value="<?= $response['isEdit'] ? esc($response['product']->product_type) : 'service' ?>">
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nombre Comercial <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" name="commercial_name" id="commercial_name" required
                                       value="<?= $response['isEdit'] ? esc($response['product']->commercial_name) : '' ?>"
                                       placeholder="Ej: Hosting Básico Pro">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nombre Interno</label>
                                <input type="text" class="form-control form-control-lg" name="internal_name" id="internal_name"
                                       value="<?= $response['isEdit'] ? esc($response['product']->internal_name) : '' ?>"
                                       placeholder="Nombre para uso interno">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">SKU <span class="text-danger">*</span></label>
                                <input type="text" class="form-control font-monospace text-uppercase" name="sku" id="sku" required
                                       value="<?= $response['isEdit'] ? esc($response['product']->sku) : '' ?>"
                                       placeholder="HOST-001">
                                <div class="form-text">Código único de identificación</div>
                            </div>
                            {{-- Código de barras: solo para Físicos --}}
                            <div class="col-md-4 section-physical" style="display: <?= ($response['isEdit'] && $response['product']->product_type === 'physical') ? '' : 'none' ?>">
                                <label class="form-label fw-semibold">Código de Barras</label>
                                <input type="text" class="form-control font-monospace" name="barcode"
                                       value="<?= $response['isEdit'] ? esc($response['product']->barcode ?? '') : '' ?>"
                                       placeholder="EAN/UPC">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Categoría</label>
                                <select class="form-select" name="category_id" id="category_id">
                                    <option value="">Sin categoría</option>
                                    <?php foreach ($response['categories'] as $cat): ?>
                                    <option value="<?= $cat->id ?>" <?= ($response['isEdit'] && $response['product']->category_id == $cat->id) ? 'selected' : '' ?>>
                                        <?= esc($cat->name) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Descripción Corta</label>
                                <input type="text" class="form-control" name="description_short"
                                       value="<?= $response['isEdit'] ? esc($response['product']->description_short ?? '') : '' ?>"
                                       placeholder="Resumen visible en listados y propuestas (máx. 255 caracteres)" maxlength="255">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Descripción Larga</label>
                                <textarea class="form-control" name="description_long" rows="4"
                                          placeholder="Descripción detallada del producto o servicio..."><?= $response['isEdit'] ? esc($response['product']->description_long ?? '') : '' ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Atributos Dinámicos (EAV) --}}
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

            </div>{{-- /col-lg-8 --}}

            {{-- ── COLUMNA LATERAL ── --}}
            <div class="col-lg-4">

                {{-- Publicación / Estado --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0"><i class="fas fa-toggle-on me-2 text-success"></i>Publicación</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" name="active" id="active"
                                   <?= (!$response['isEdit'] || $response['product']->active) ? 'checked' : '' ?>>
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

                {{-- Galería de Imágenes --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0"><i class="fas fa-images me-2 text-warning"></i>Galería</h5>
                    </div>
                    <div class="card-body p-3">

                        {{-- Imágenes existentes --}}
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

                        {{-- Drop zone para nuevas imágenes --}}
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

                        {{-- Preview de imágenes a subir --}}
                        <div class="row g-2 mt-2" id="preview-images"></div>

                    </div>
                </div>

            </div>{{-- /col-lg-4 --}}
        </div>{{-- /row --}}
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
const PRODUCT_ID  = <?= $response['product'] ? $response['product']->id : 'null' ?>;

document.addEventListener('DOMContentLoaded', function () {
    initTypeTabs();
    initAttributeBuilder();
    initImageGallery();
    initFormSubmit();
});

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
<?php $this->endSection(); ?>
