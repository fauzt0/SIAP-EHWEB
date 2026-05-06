<?php
/**
 * Partial: Offcanvas de Gestión de Planes
 *
 * Se incluye en products_index.php.
 * Se dispara desde el botón "Gestionar Planes" del DataTable.
 *
 * Sección 2 DOCUMENTACION_TECNICA.md: CSRF renovado en cada respuesta.
 * Sección 10: Select2 con dropdownParent para z-index correcto en offcanvas.
 * Sección 12: Solo FontAwesome (fas), no Lucide en contenido dinámico.
 */
?>

<!-- ═══════════════════════════════════════════════════════════
     OFFCANVAS DE PLANES
     ═══════════════════════════════════════════════════════════ -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas-plans"
     aria-labelledby="offcanvasPlansLabel" style="width: 580px;">
    <div class="offcanvas-header border-bottom bg-dark text-white py-3">
        <h5 class="offcanvas-title" id="offcanvasPlansLabel">
            <i class="fas fa-tags me-2"></i>Planes de Precios
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body p-0">
        <!-- Info del producto seleccionado -->
        <div class="px-4 py-3 bg-light border-bottom" id="plans-product-info">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 p-2 rounded-3">
                    <i class="fas fa-box fa-lg text-primary" id="plans-product-icon"></i>
                </div>
                <div>
                    <div class="fw-bold" id="plans-product-name">Cargando...</div>
                    <small class="text-muted font-monospace" id="plans-product-sku"></small>
                </div>
            </div>
        </div>

        <!-- Formulario de plan nuevo/edición -->
        <div class="p-4 border-bottom bg-white">
            <h6 class="fw-bold mb-3" id="plan-form-title">
                <i class="fas fa-plus-circle me-1 text-primary"></i>Agregar Plan
            </h6>
            <form id="plan-form">
                <input type="hidden" id="plan-id">
                <input type="hidden" id="plan-product-id">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Nombre del Plan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="plan-name" name="plan_name"
                               placeholder="Ej: Plan Básico Mensual">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Ciclo de Cobro <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="plan-billing-cycle" name="billing_cycle">
                            <option value="monthly">Mensual</option>
                            <option value="yearly">Anual</option>
                            <option value="one_time">Pago Único</option>
                            <option value="custom">Personalizado</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">
                            Precio de Venta <span class="text-danger">*</span>
                            <i class="fas fa-info-circle text-muted ms-1"
                               title="Precio de la primera compra / compra única"></i>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="plan-sale-price"
                                   name="sale_price" min="0" step="0.01" value="0.00">
                        </div>
                    </div>
                    <div class="col-md-4" id="renewal-price-group">
                        <label class="form-label fw-semibold small">
                            Precio de Renovación
                            <i class="fas fa-info-circle text-muted ms-1"
                               title="Precio en cobros futuros (2do pago en adelante). Si es igual al precio de venta, déjalo igual."></i>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="plan-renewal-price"
                                   name="renewal_price" min="0" step="0.01" value="0.00">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">
                            Setup Fee
                            <i class="fas fa-info-circle text-muted ms-1"
                               title="Cargo único de instalación/configuración. Se cobra solo en la primera compra."></i>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="plan-setup-fee"
                                   name="setup_fee" min="0" step="0.01" value="0.00">
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="plan-is-active"
                                   name="is_active" checked>
                            <label class="form-check-label small" for="plan-is-active">Plan Activo</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary btn-sm" id="btn-save-plan">
                        <i class="fas fa-save me-1"></i>Guardar Plan
                    </button>
                    <button type="button" class="btn btn-light btn-sm" id="btn-cancel-plan">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                </div>
            </form>
        </div>

        <!-- Listado de planes del producto -->
        <div class="p-4">
            <h6 class="fw-bold mb-3"><i class="fas fa-list me-1 text-secondary"></i>Planes Configurados</h6>
            <div id="plans-list">
                <div class="text-center text-muted py-4">
                    <i class="fas fa-spinner fa-spin fa-lg mb-2 d-block"></i>Cargando planes...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     SCRIPTS DEL OFFCANVAS
     ═══════════════════════════════════════════════════════════ -->
<script>
const PLAN_ROUTES = {
    getPlans:    '<?= base_url('nat/catalog/products/') ?>',
    storePlan:   '<?= base_url('nat/catalog/products/') ?>',
    updatePlan:  '<?= route_to('catalog.plans.update', 0) ?>'.replace('/0', '/'),
    deletePlan:  '<?= route_to('catalog.plans.delete', 0) ?>'.replace('/0', '/'),
    togglePlan:  '<?= route_to('catalog.plans.toggle', 0) ?>'.replace('/0', '/'),
};

const cycleLabels = {
    monthly:  'Mensual',
    yearly:   'Anual',
    one_time: 'Pago Único',
    custom:   'Personalizado'
};

let currentProductId = null;

// ── Apertura del offcanvas desde el DataTable ──────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-manage-plans');
    if (!btn) return;

    currentProductId = btn.dataset.id;

    document.getElementById('plans-product-name').textContent = btn.dataset.name;
    document.getElementById('plans-product-sku').textContent  = '';
    document.getElementById('plan-product-id').value          = currentProductId;

    resetPlanForm();
    loadPlans(currentProductId);

    const offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvas-plans'));
    offcanvas.show();
});

// ── Cargar planes del producto ────────────────────────────
function loadPlans(productId) {
    const container = document.getElementById('plans-list');
    container.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin fa-lg"></i></div>';

    fetch(PLAN_ROUTES.getPlans + productId + '/plans', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { container.innerHTML = '<p class="text-danger">' + (data.message || 'Error') + '</p>'; return; }

        const { product, plans } = data.response;

        // Actualizar info del producto
        document.getElementById('plans-product-sku').textContent = product.sku;
        const icon = { service: 'fa-cloud', physical: 'fa-box', digital: 'fa-key' }[product.product_type] || 'fa-tag';
        document.getElementById('plans-product-icon').className  = 'fas ' + icon + ' fa-lg text-primary';

        if (!plans.length) {
            container.innerHTML = '<p class="text-muted fst-italic small">Sin planes configurados. Agrega el primero arriba.</p>';
            return;
        }

        container.innerHTML = plans.map(plan => `
            <div class="card border-0 shadow-sm mb-2" id="plan-card-${plan.id}">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-bold small">${plan.plan_name}</span>
                                <span class="badge badge-subtle-${plan.is_active ? 'success' : 'secondary'} ms-1">
                                    ${plan.is_active ? 'Activo' : 'Inactivo'}
                                </span>
                                <span class="badge badge-subtle-info">${cycleLabels[plan.billing_cycle] || plan.billing_cycle}</span>
                            </div>
                            <div class="d-flex gap-3 text-muted small">
                                <span><i class="fas fa-tag me-1"></i>Venta: <strong class="text-dark">$${parseFloat(plan.sale_price).toFixed(2)}</strong></span>
                                ${plan.billing_cycle !== 'one_time' ? `<span><i class="fas fa-sync me-1"></i>Renovación: <strong class="text-dark">$${parseFloat(plan.renewal_price).toFixed(2)}</strong></span>` : ''}
                                ${parseFloat(plan.setup_fee) > 0 ? `<span><i class="fas fa-wrench me-1"></i>Setup: <strong class="text-warning">$${parseFloat(plan.setup_fee).toFixed(2)}</strong></span>` : ''}
                            </div>
                        </div>
                        <div class="d-flex gap-1 align-items-center ms-2">
                            <button class="btn btn-xs btn-outline-warning btn-edit-plan" data-plan='${JSON.stringify(plan)}' title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-xs btn-outline-secondary btn-toggle-plan" data-id="${plan.id}" title="${plan.is_active ? 'Desactivar' : 'Activar'}">
                                <i class="fas ${plan.is_active ? 'fa-eye-slash' : 'fa-eye'}"></i>
                            </button>
                            <button class="btn btn-xs btn-outline-danger btn-delete-plan" data-id="${plan.id}" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    })
    .catch(() => { container.innerHTML = '<p class="text-danger">Error de conexión.</p>'; });
}

// ── Guardar Plan (crear o editar) ─────────────────────────
document.getElementById('plan-form').addEventListener('submit', function (e) {
    e.preventDefault();

    const planId    = document.getElementById('plan-id').value;
    const productId = document.getElementById('plan-product-id').value;
    const btn       = document.getElementById('btn-save-plan');
    const origHtml  = btn.innerHTML;
    btn.disabled    = true;
    btn.innerHTML   = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

    const formData = new FormData(this);
    formData.set('<?= csrf_token() ?>', document.getElementById('csrf_token').value);
    formData.set('is_active', document.getElementById('plan-is-active').checked ? '1' : '0');

    const url = planId
        ? PLAN_ROUTES.updatePlan + planId
        : PLAN_ROUTES.storePlan + productId + '/plans/store';

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
            resetPlanForm();
            loadPlans(productId);
            if (window.productsTable) window.productsTable.ajax.reload(null, false);
        } else {
            const msg = typeof data.error === 'object'
                ? Object.values(data.error).join('<br>')
                : (data.message || 'Error al guardar');
            notifyShow(msg, 'danger');
        }
    })
    .catch(() => notifyShow('Error de conexión', 'danger'))
    .finally(() => { btn.disabled = false; btn.innerHTML = origHtml; });
});

// ── Delegación de eventos en la lista de planes ───────────
document.addEventListener('click', function (e) {
    // Editar plan
    const btnEdit = e.target.closest('.btn-edit-plan');
    if (btnEdit) {
        const plan = JSON.parse(btnEdit.dataset.plan);
        document.getElementById('plan-id').value            = plan.id;
        document.getElementById('plan-name').value          = plan.plan_name;
        document.getElementById('plan-billing-cycle').value = plan.billing_cycle;
        document.getElementById('plan-sale-price').value    = plan.sale_price;
        document.getElementById('plan-renewal-price').value = plan.renewal_price;
        document.getElementById('plan-setup-fee').value     = plan.setup_fee;
        document.getElementById('plan-is-active').checked   = plan.is_active == 1;
        document.getElementById('plan-form-title').innerHTML = '<i class="fas fa-edit me-1 text-warning"></i>Editar Plan';
        toggleRenewalField(plan.billing_cycle);
        document.getElementById('plan-name').scrollIntoView({ behavior: 'smooth' });
        return;
    }

    // Toggle activo/inactivo
    const btnToggle = e.target.closest('.btn-toggle-plan');
    if (btnToggle) {
        sendPlanRequest(PLAN_ROUTES.togglePlan + btnToggle.dataset.id, null, 'toggle');
        return;
    }

    // Eliminar plan
    const btnDelete = e.target.closest('.btn-delete-plan');
    if (btnDelete) {
        if (!confirm('¿Eliminar este plan?')) return;
        sendPlanRequest(PLAN_ROUTES.deletePlan + btnDelete.dataset.id, btnDelete.dataset.id, 'delete');
        return;
    }
});

// Cancelar edición
document.getElementById('btn-cancel-plan').addEventListener('click', resetPlanForm);

function resetPlanForm() {
    document.getElementById('plan-form').reset();
    document.getElementById('plan-id').value = '';
    document.getElementById('plan-form-title').innerHTML = '<i class="fas fa-plus-circle me-1 text-primary"></i>Agregar Plan';
    document.getElementById('plan-is-active').checked = true;
    toggleRenewalField('monthly');
}

function toggleRenewalField(cycle) {
    const group = document.getElementById('renewal-price-group');
    group.style.display = cycle === 'one_time' ? 'none' : '';
    if (cycle === 'one_time') document.getElementById('plan-renewal-price').value = '0.00';
}

document.getElementById('plan-billing-cycle').addEventListener('change', function () {
    toggleRenewalField(this.value);
});

function sendPlanRequest(url, itemId, action) {
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
            loadPlans(currentProductId);
            if (window.productsTable) window.productsTable.ajax.reload(null, false);
        } else {
            notifyShow(data.message || 'Error', 'danger');
        }
    })
    .catch(() => notifyShow('Error de conexión', 'danger'));
}
</script>
