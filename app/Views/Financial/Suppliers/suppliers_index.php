<?php $this->extend($layout); ?>

<?php $this->section('title'); echo esc($headTitle); $this->endSection(); ?>

<?php $this->section('main') ?>

<style>
/* ── Corrección: superposición en timeline de bitácora de actividad ── */
/* El CSS del tema define :before para línea y punto, pero .timeline-item
   carece de padding-left para separar el contenido. Además, .timeline-point
   no tiene estilos de posición. */
#oc-activity-timeline .timeline-item {
    position: relative;
    padding-left: 32px;
    margin-bottom: 1rem;
}
#oc-activity-timeline .timeline-item:last-child {
    margin-bottom: 0;
}
/* Ocultamos el :before del theme porque usamos .timeline-point como dot */
#oc-activity-timeline .timeline-item:before {
    display: none;
}
#oc-activity-timeline .timeline-point {
    position: absolute;
    left: 0;
    top: 4px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    z-index: 2;
    border: 3px solid #3f80ea;
}
#oc-activity-timeline .timeline-content {
    position: relative;
    min-height: 24px;
}
</style>

<?php /* §2 — Token CSRF global para DataTables y Fetch API */ ?>
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

<main class="content">
    <div class="container-fluid p-0">

        <?php /* Encabezado + Breadcrumb */ ?>
        <div class="row mb-2 mb-xl-3">
            <div class="col-auto d-none d-sm-block">
                <h1 class="h3 mb-3">
                    <i class="fas fa-fw fa-truck me-2 text-primary"></i><?= esc($pageTitle) ?>
                </h1>
            </div>
            <div class="col-auto ms-auto text-end mt-n1">
                <nav aria-label="breadcrumb">
                    <?= isset($breadcrumb) ? $breadcrumb : '' ?>
                </nav>
            </div>
        </div>

        <?php /* Stats Cards — §12 fas icons only */ ?>
        <div class="row mb-3">
        <?php
        $statCards = [
            ['label' => 'Total Proveedores', 'key' => 'total',         'color' => 'primary',   'icon' => 'fa-truck',        'sub' => 'Registrados en el sistema'],
            ['label' => 'Activos',           'key' => 'active',        'color' => 'success',   'icon' => 'fa-check-circle', 'sub' => 'Proveedores habilitados'],
            ['label' => 'Inactivos',         'key' => 'inactive',      'color' => 'danger',    'icon' => 'fa-times-circle', 'sub' => 'Proveedores deshabilitados'],
            ['label' => 'Productos Asoc.',   'key' => 'with_products', 'color' => 'info',      'icon' => 'fa-boxes',        'sub' => 'Vínculos catálogo-proveedor'],
        ];
        $total = $response['stats']['total'] ?? 0;
        foreach ($statCards as $card):
            $val     = $response['stats'][$card['key']] ?? 0;
            $percent = ($card['key'] !== 'total' && $total > 0) ? round(($val / $total) * 100) : 100;
        ?>
        <div class="col-12 col-sm-6 col-md-3 d-flex mb-3">
            <div class="card flex-fill">
                <div class="card-header pb-0">
                    <h5 class="card-title mb-0 mt-1"><?= $card['label'] ?></h5>
                </div>
                <div class="card-body my-0 pt-0">
                    <div class="d-flex align-items-center mb-3 mt-2">
                        <div class="flex-grow-1">
                            <h3 class="mb-0 fw-light"><?= number_format($val) ?></h3>
                        </div>
                        <div class="ms-auto">
                            <div class="stat text-<?= $card['color'] ?>">
                                <i class="fas <?= $card['icon'] ?> align-middle"></i>
                            </div>
                        </div>
                    </div>
                    <div class="progress progress-sm shadow-sm mb-1">
                        <div class="progress-bar bg-<?= $card['color'] ?>" role="progressbar"
                             style="width: <?= min(100, max(3, $percent)) ?>%"></div>
                    </div>
                    <small class="text-muted"><?= $card['sub'] ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>

        <?php /* DataTable Card */ ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        <!-- Fila de búsqueda y filtros (estilo unificado con productos/categorías/usuarios) -->
                        <div class="row mb-3">
                            <div class="col-md-4 mb-2 mb-md-0">
                                <div class="input-group input-group-search">
                                    <input type="text" class="form-control" id="filter-search"
                                           placeholder="Buscar proveedor…">
                                    <button class="btn" type="button">
                                        <i class="fas fa-search align-middle"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-8 mb-2 mb-md-0">
                                <div class="d-flex gap-2 flex-wrap align-items-center justify-content-md-end">
                                    <select class="form-select" id="filter-supplier-type" style="max-width:180px;">
                                        <option value="">Todos los tipos</option>
                                        <?php foreach ($response['supplier_types'] ?? [] as $key => $label): ?>
                                            <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select class="form-select" id="filter-status" style="max-width:180px;">
                                        <option value="">Todos los estatus</option>
                                        <option value="1">Activos</option>
                                        <option value="0">Inactivos</option>
                                        <option value="deleted">Eliminados (Papelera)</option>
                                    </select>
                                    <button class="btn btn-outline-secondary" id="btn-clear-filters" title="Limpiar filtros">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php if (auth()->user()->can('catalog.manage-suppliers')): ?>
                                    <button type="button" class="btn btn-primary btn-lg"
                                            data-bs-toggle="modal" data-bs-target="#modalCreateSupplier">
                                        <i class="fas fa-plus"></i> Nuevo Proveedor
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla DataTable -->
                        <table id="datatables-suppliers" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Proveedor</th>
                                    <th>RFC / Fiscal</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th class="text-center">Contactos</th>
                                    <th class="text-center">OC</th>
                                    <th class="text-center">Estatus</th>
                                    <th class="text-center" style="width:130px;">Acciones</th>
                                    <th style="width:40px;"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>

    </div>
</main>
<?php $this->endSection() ?>

<?php $this->section('pageFooterScripts') ?>

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Alta de Proveedor  (#modalCreateSupplier)
     §10 — Select2 con dropdownParent para z-index correcto
══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalCreateSupplier" tabindex="-1"
     aria-labelledby="modalCreateSupplierLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border-top:4px solid #0d6efd;">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCreateSupplierLabel">
                    <i class="fas fa-plus-circle me-2 text-primary"></i>Dar de Alta Proveedor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="form-create-supplier" novalidate>
                    <h6 class="text-muted text-uppercase fw-bold border-bottom pb-2 mb-3">
                        <i class="fas fa-info-circle me-1"></i>Datos Generales
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Tipo de Proveedor <span class="text-danger">*</span></label>
                            <select name="supplier_type" id="create-supplier-type" class="form-select" required>
                                <option value="">— Seleccione —</option>
                                <?php foreach ($response['supplier_types'] ?? [] as $key => $label): ?>
                                    <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nombre Comercial <span class="text-danger">*</span></label>
                            <input type="text" name="commercial_name" class="form-control"
                                   placeholder="Nombre que identifica al proveedor" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Razón Social</label>
                            <input type="text" name="legal_name" class="form-control" placeholder="Nombre legal / razón social">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">RFC / ID Fiscal</label>
                            <input type="text" name="tax_id" class="form-control" placeholder="RFC, NIT, EIN…">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Persona de Contacto</label>
                            <input type="text" name="contact_person" class="form-control" placeholder="Nombre del interlocutor">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Email de Contacto</label>
                            <input type="email" name="contact_email" class="form-control" placeholder="correo@proveedor.com">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="contact_phone" class="form-control" placeholder="+52 (33) 0000-0000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sitio Web</label>
                            <input type="url" name="website" class="form-control" placeholder="https://www.proveedor.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estatus</label>
                            <select name="status" class="form-select">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="address" class="form-control" placeholder="Dirección completa">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Datos Bancarios</label>
                            <textarea name="bank_details" class="form-control" rows="2"
                                      placeholder="Banco, No. de cuenta, CLABE, etc."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notas Internas</label>
                            <textarea name="notes" class="form-control" rows="2"
                                      placeholder="Observaciones, condiciones de pago, etc."></textarea>
                        </div>
                    </div>

                    <h6 class="text-muted text-uppercase fw-bold border-bottom pb-2 mb-3">
                        <i class="fas fa-address-book me-1"></i>Contactos Adicionales
                        <button type="button" class="btn btn-outline-secondary btn-sm ms-2"
                                id="btn-add-create-contact">
                            <i class="fas fa-plus"></i> Agregar
                        </button>
                    </h6>
                    <div id="create-contacts-container"></div>

                    <template id="tpl-contact-row">
                        <div class="contact-row card card-body py-2 mb-2 bg-light">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-3">
                                    <input type="text" name="contacts[__idx__][contact_name]"
                                           class="form-control form-control-sm" placeholder="Nombre *" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="contacts[__idx__][job_title]"
                                           class="form-control form-control-sm" placeholder="Cargo / Puesto">
                                </div>
                                <div class="col-md-2">
                                    <input type="email" name="contacts[__idx__][email]"
                                           class="form-control form-control-sm" placeholder="Email">
                                </div>
                                <div class="col-md-2">
                                    <input type="text" name="contacts[__idx__][phone]"
                                           class="form-control form-control-sm" placeholder="Teléfono">
                                </div>
                                <div class="col-md-1 d-flex align-items-center justify-content-center">
                                    <div class="form-check m-0">
                                        <input type="checkbox" name="contacts[__idx__][is_primary]"
                                               class="form-check-input" title="Contacto principal">
                                        <label class="form-check-label small">Ppal.</label>
                                    </div>
                                </div>
                                <div class="col-md-1 text-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-contact-row"
                                            title="Eliminar fila">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-submit-create-supplier">
                    <i class="fas fa-save me-1"></i>Guardar Proveedor
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Editar Proveedor  (#modalEditSupplier)
══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalEditSupplier" tabindex="-1"
     aria-labelledby="modalEditSupplierLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border-top:4px solid #ffc107;">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditSupplierLabel">
                    <i class="fas fa-edit me-2 text-warning"></i>Editar Proveedor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="edit-supplier-loading" class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <div class="mt-2 text-muted small">Cargando datos del proveedor…</div>
                </div>
                <form id="form-edit-supplier" novalidate style="display:none;">
                    <input type="hidden" id="edit-supplier-id">
                    <h6 class="text-muted text-uppercase fw-bold border-bottom pb-2 mb-3">
                        <i class="fas fa-info-circle me-1"></i>Datos Generales
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Tipo de Proveedor <span class="text-danger">*</span></label>
                            <select name="supplier_type" id="edit-supplier-type" class="form-select" required>
                                <option value="">— Seleccione —</option>
                                <?php foreach ($response['supplier_types'] ?? [] as $key => $label): ?>
                                    <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nombre Comercial <span class="text-danger">*</span></label>
                            <input type="text" name="commercial_name" id="edit-commercial-name"
                                   class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Razón Social</label>
                            <input type="text" name="legal_name" id="edit-legal-name" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">RFC / ID Fiscal</label>
                            <input type="text" name="tax_id" id="edit-tax-id" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Persona de Contacto</label>
                            <input type="text" name="contact_person" id="edit-contact-person" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Email de Contacto</label>
                            <input type="email" name="contact_email" id="edit-contact-email" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="contact_phone" id="edit-contact-phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sitio Web</label>
                            <input type="url" name="website" id="edit-website" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estatus</label>
                            <select name="status" id="edit-status" class="form-select">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="address" id="edit-address" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Datos Bancarios</label>
                            <textarea name="bank_details" id="edit-bank-details" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notas Internas</label>
                            <textarea name="notes" id="edit-notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="btn-submit-edit-supplier">
                    <i class="fas fa-save me-1"></i>Actualizar Proveedor
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     OFFCANVAS: Perfil 360° del Proveedor  (#offcanvasSupplierProfile)
     Tabs: General | Contactos | Productos & Servicios | Órdenes de Compra
══════════════════════════════════════════════════════════════════════════ -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSupplierProfile"
     aria-labelledby="offcanvasSupplierProfileLabel"
     style="width:min(720px,96vw);">

    <div class="offcanvas-header border-bottom">
        <div>
            <h5 class="offcanvas-title" id="offcanvasSupplierProfileLabel">
                <i class="fas fa-truck me-2 text-primary"></i>
                <span id="oc-supplier-name" class="fw-semibold">Proveedor</span>
                <span id="oc-supplier-type-badge" class="ms-2"></span>
            </h5>
            <small class="text-muted" id="oc-supplier-tax-id">—</small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <?php if (auth()->user()->can('catalog.manage-suppliers')): ?>
            <button type="button" class="btn btn-outline-warning btn-sm" id="oc-btn-edit" title="Editar proveedor">
                <i class="fas fa-edit"></i>
            </button>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
        </div>
    </div>

    <div id="oc-loading" class="text-center py-5 d-none">
        <div class="spinner-border text-primary"></div>
        <div class="mt-2 text-muted small">Cargando perfil…</div>
    </div>

    <div id="oc-body" class="offcanvas-body p-0">
        <ul class="nav nav-tabs nav-fill border-bottom px-3 pt-2 bg-light sticky-top"
            id="supplierTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-general" data-bs-toggle="tab"
                        data-bs-target="#tabpanel-general" type="button" role="tab">
                    <i class="fas fa-fw fa-info-circle me-1"></i>General
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-contacts" data-bs-toggle="tab"
                        data-bs-target="#tabpanel-contacts" type="button" role="tab">
                    <i class="fas fa-fw fa-address-card me-1"></i>Contactos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-products" data-bs-toggle="tab"
                        data-bs-target="#tabpanel-products" type="button" role="tab">
                    <i class="fas fa-fw fa-box me-1"></i>Productos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-orders" data-bs-toggle="tab"
                        data-bs-target="#tabpanel-orders" type="button" role="tab">
                    <i class="fas fa-fw fa-file-invoice-dollar me-1"></i>OC
                </button>
            </li>
        </ul>

        <div class="tab-content" id="supplierTabsContent">

            <!-- ── TAB 1: Información General & Bitácora ──────────────── -->
            <div class="tab-pane fade show active" id="tabpanel-general" role="tabpanel">
                <!-- Placeholder inicial mientras no hay proveedor seleccionado -->
                <div id="oc-general-placeholder" class="text-center text-muted py-5 px-3">
                    <i class="fas fa-info-circle fa-3x mb-3 d-block text-secondary opacity-50"></i>
                    <p class="fst-italic mb-0">Seleccione un proveedor para ver su perfil.</p>
                </div>

                <!-- Tabla de información general (oculta por defecto, se muestra vía JS) -->
                <div id="oc-general-info" style="display:none;">
                    <div class="px-4 py-3">
                        <table class="table table-sm table-borderless align-middle mb-0">
                            <tbody>
                                <tr class="bg-light">
                                    <td class="text-muted ps-0" style="width:38%"><i class="fas fa-building fa-fw me-1"></i>Nombre Comercial</td>
                                    <td class="fw-semibold" id="oc-g-commercial-name">—</td>
                                </tr>
                                <tr>
                                    <td class="text-muted ps-0"><i class="fas fa-file-signature fa-fw me-1"></i>Razón Social</td>
                                    <td id="oc-g-legal-name">—</td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-muted ps-0"><i class="fas fa-receipt fa-fw me-1"></i>RFC / ID Fiscal</td>
                                    <td id="oc-g-tax-id" class="font-monospace">—</td>
                                </tr>
                                <tr>
                                    <td class="text-muted ps-0"><i class="fas fa-check-circle fa-fw me-1"></i>Estatus</td>
                                    <td id="oc-g-status">—</td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-muted ps-0"><i class="fas fa-globe fa-fw me-1"></i>Sitio Web</td>
                                    <td id="oc-g-website">—</td>
                                </tr>
                                <tr>
                                    <td class="text-muted ps-0"><i class="fas fa-user-tie fa-fw me-1"></i>Persona de Contacto</td>
                                    <td id="oc-g-contact-person">—</td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-muted ps-0"><i class="fas fa-envelope fa-fw me-1"></i>Email de Contacto</td>
                                    <td id="oc-g-contact-email">—</td>
                                </tr>
                                <tr>
                                    <td class="text-muted ps-0"><i class="fas fa-phone fa-fw me-1"></i>Teléfono</td>
                                    <td id="oc-g-contact-phone">—</td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-muted ps-0"><i class="fas fa-map-marker-alt fa-fw me-1"></i>Dirección</td>
                                    <td id="oc-g-address">—</td>
                                </tr>
                                <tr>
                                    <td class="text-muted ps-0"><i class="fas fa-university fa-fw me-1"></i>Datos Bancarios</td>
                                    <td id="oc-g-bank-details" class="font-monospace small">—</td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-muted ps-0"><i class="fas fa-sticky-note fa-fw me-1"></i>Notas Internas</td>
                                    <td id="oc-g-notes" class="fst-italic text-muted">—</td>
                                </tr>
                                <tr>
                                    <td class="text-muted ps-0"><i class="fas fa-calendar-plus fa-fw me-1"></i>Registrado</td>
                                    <td id="oc-g-created-at">—</td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-muted ps-0"><i class="fas fa-calendar-alt fa-fw me-1"></i>Última Actualización</td>
                                    <td id="oc-g-updated-at">—</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <hr class="my-0">
                <div class="px-4 py-3">
                    <h6 class="text-muted fw-bold mb-2">
                        <i class="fas fa-history me-1"></i>Bitácora de Actividad
                    </h6>
                    <div id="oc-activity-timeline">
                        <p class="text-muted small fst-italic">Sin actividad registrada.</p>
                    </div>
                </div>
            </div>

            <!-- ── TAB 2: Contactos ───────────────────────────────────── -->
            <div class="tab-pane fade p-3" id="tabpanel-contacts" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0"><i class="fas fa-users me-1"></i>Contactos del Proveedor</h6>
                    <?php if (auth()->user()->can('catalog.manage-suppliers')): ?>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-open-add-contact">
                        <i class="fas fa-plus me-1"></i>Agregar Contacto
                    </button>
                    <?php endif; ?>
                </div>
                <div id="oc-contacts-list">
                    <div class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm me-2"></div>Cargando…
                    </div>
                </div>
            </div>

            <!-- ── TAB 3: Productos & Servicios ──────────────────────── -->
            <div class="tab-pane fade p-3" id="tabpanel-products" role="tabpanel">
                <div class="accordion" id="accordionProdServ">

                    <!-- Productos del Catálogo (Revendibles) -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button py-2" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#collapseResellable">
                                <i class="fas fa-fw fa-boxes me-2 text-primary"></i>
                                Productos del Catálogo (Revendibles)
                            </button>
                        </h2>
                        <div id="collapseResellable" class="accordion-collapse collapse show"
                             data-bs-parent="#accordionProdServ">
                            <div class="accordion-body pt-2">
                                <?php if (auth()->user()->can('catalog.manage-suppliers')): ?>
                                <div class="text-end mb-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                            id="btn-open-link-product">
                                        <i class="fas fa-link me-1"></i>Asociar Producto
                                    </button>
                                </div>
                                <?php endif; ?>
                                <div id="oc-products-list">
                                    <div class="text-center py-3 text-muted">
                                        <div class="spinner-border spinner-border-sm me-2"></div>Cargando…
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Servicios Internos Recurrentes -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed py-2" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#collapseServices">
                                <i class="fas fa-fw fa-bolt me-2 text-warning"></i>
                                Servicios Internos Recurrentes (Luz, Tel., Basura…)
                            </button>
                        </h2>
                        <div id="collapseServices" class="accordion-collapse collapse"
                             data-bs-parent="#accordionProdServ">
                            <div class="accordion-body pt-2">
                                <?php if (auth()->user()->can('catalog.manage-suppliers')): ?>
                                <div class="text-end mb-2">
                                    <button type="button" class="btn btn-outline-warning btn-sm"
                                            id="btn-open-add-service">
                                        <i class="fas fa-plus me-1"></i>Agregar Gasto Recurrente
                                    </button>
                                </div>
                                <?php endif; ?>
                                <div id="oc-services-list">
                                    <div class="text-center py-3 text-muted">
                                        <div class="spinner-border spinner-border-sm me-2"></div>Cargando…
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ── TAB 4: Órdenes de Compra ───────────────────────────── -->
            <div class="tab-pane fade p-3" id="tabpanel-orders" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">
                        <i class="fas fa-file-invoice-dollar me-1"></i>Historial de Órdenes de Compra
                    </h6>
                    <?php if (auth()->user()->can('catalog.manage-suppliers')): ?>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-open-generate-po">
                        <i class="fas fa-plus me-1"></i>Generar OC
                    </button>
                    <?php endif; ?>
                </div>
                <div id="oc-orders-list">
                    <div class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm me-2"></div>Cargando…
                    </div>
                </div>
            </div>

        </div><!-- /tab-content -->
    </div><!-- /oc-body -->
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Agregar Contacto  (#modalAddContact)
     z-index > 1055 para quedar encima del Offcanvas
══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalAddContact" tabindex="-1"
     aria-labelledby="modalAddContactLabel" aria-hidden="true"
     data-bs-backdrop="static" style="z-index:1065;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAddContactLabel">
                    <i class="fas fa-user-plus me-2 text-primary"></i>Agregar Contacto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="form-add-contact" novalidate>
                    <input type="hidden" id="add-contact-supplier-id">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre del Contacto <span class="text-danger">*</span></label>
                            <input type="text" name="contact_name" class="form-control"
                                   placeholder="Nombre completo" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cargo / Puesto</label>
                            <input type="text" name="job_title" class="form-control"
                                   placeholder="Ej. Director Comercial, Soporte…">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                   placeholder="correo@ejemplo.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="phone" class="form-control" placeholder="+52 …">
                        </div>
                        <div class="col-md-6 d-flex align-items-end pb-1">
                            <div class="form-check">
                                <input type="checkbox" name="is_primary" id="add-contact-is-primary"
                                       class="form-check-input">
                                <label class="form-check-label" for="add-contact-is-primary">
                                    Contacto Principal
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-submit-add-contact">
                    <i class="fas fa-save me-1"></i>Guardar Contacto
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Editar Contacto  (#modalEditContact)
══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalEditContact" tabindex="-1"
     aria-labelledby="modalEditContactLabel" aria-hidden="true"
     data-bs-backdrop="static" style="z-index:1065;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditContactLabel">
                    <i class="fas fa-user-edit me-2 text-warning"></i>Editar Contacto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="edit-contact-loading" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary"></div>
                </div>
                <form id="form-edit-contact" novalidate style="display:none;">
                    <input type="hidden" id="edit-contact-id">
                    <input type="hidden" id="edit-contact-supplier-id">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre del Contacto <span class="text-danger">*</span></label>
                            <input type="text" name="contact_name" id="ec-name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cargo / Puesto</label>
                            <input type="text" name="job_title" id="ec-job-title" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="ec-email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="phone" id="ec-phone" class="form-control">
                        </div>
                        <div class="col-md-6 d-flex align-items-end pb-1">
                            <div class="form-check">
                                <input type="checkbox" name="is_primary" id="ec-is-primary"
                                       class="form-check-input">
                                <label class="form-check-label" for="ec-is-primary">Contacto Principal</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="btn-submit-edit-contact">
                    <i class="fas fa-save me-1"></i>Actualizar Contacto
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Asociar Producto del Catálogo  (#modalAssociateProduct)
     §10 — Select2 con dropdownParent para z-index correcto
══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalAssociateProduct" tabindex="-1"
     aria-labelledby="modalAssociateProductLabel" aria-hidden="true"
     data-bs-backdrop="static" style="z-index:1065;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAssociateProductLabel">
                    <i class="fas fa-link me-2 text-primary"></i>Asociar Producto del Catálogo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="form-link-product" novalidate>
                    <input type="hidden" id="link-product-supplier-id">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Producto del Catálogo <span class="text-danger">*</span></label>
                            <select id="link-catalog-product-id" name="catalog_product_id"
                                    class="form-select" required>
                                <option value="">— Seleccione un producto —</option>
                            </select>
                            <div class="form-text">Solo se listan productos activos del catálogo.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Costo de Compra</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="purchase_cost" class="form-control"
                                       min="0" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Moneda</label>
                            <select name="purchase_currency" id="link-purchase-currency" class="form-select">
                                <option value="MXN">MXN — Peso Mexicano</option>
                                <option value="USD">USD — Dólar</option>
                                <option value="EUR">EUR — Euro</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Vencimiento con Proveedor</label>
                            <input type="date" name="supplier_due_date" class="form-control">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="is_auto_renew"
                                       id="link-auto-renew" class="form-check-input">
                                <label class="form-check-label" for="link-auto-renew">
                                    <i class="fas fa-sync-alt me-1 text-success"></i>Renovación Automática
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-submit-link-product">
                    <i class="fas fa-link me-1"></i>Asociar Producto
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Agregar Gasto Recurrente  (#modalAddRecurringService)
     §10 — Select2 con dropdownParent para z-index correcto
══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalAddRecurringService" tabindex="-1"
     aria-labelledby="modalAddRecurringServiceLabel" aria-hidden="true"
     data-bs-backdrop="static" style="z-index:1065;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAddRecurringServiceLabel">
                    <i class="fas fa-bolt me-2 text-warning"></i>Agregar Gasto Recurrente / Servicio
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="form-add-service" novalidate>
                    <input type="hidden" id="add-service-supplier-id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre del Servicio <span class="text-danger">*</span></label>
                            <input type="text" name="description" class="form-control"
                                   placeholder="Ej. Energía Eléctrica, Internet…" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Servicio</label>
                            <select name="service_type" id="add-service-type" class="form-select">
                                <?php
                                $serviceTypeLabels = \App\Models\Financial\FinRecurringExpenseModel::getServiceTypeOptions();
                                foreach ($response['recurring_service_types'] ?? [] as $groupLabel => $typeKeys):
                                ?>
                                <optgroup label="<?= esc($groupLabel) ?>">
                                    <?php foreach ($typeKeys as $typeKey): ?>
                                    <option value="<?= esc($typeKey) ?>">
                                        <?= esc($serviceTypeLabels[$typeKey] ?? $typeKey) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Monto Estimado <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="amount" class="form-control"
                                       min="0.01" step="0.01" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Moneda</label>
                            <select name="currency" id="add-service-currency" class="form-select">
                                <option value="MXN">MXN</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>                        
                        <div class="col-md-6">
                            <label class="form-label">Frecuencia <span class="text-danger">*</span></label>
                            <select name="frequency" id="add-service-frequency" class="form-select" required>
                                <option value="weekly">Semanal</option>
                                <option value="monthly">Mensual</option>
                                <option value="quarterly">Trimestral</option>
                                <option value="semiannual">Semestral</option>
                                <option value="yearly">Anual</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="billing-day-field">
                            <label class="form-label">Día de Pago (del mes) <span class="text-danger">*</span></label>
                            <input type="number" name="billing_day" class="form-control" min="1" max="31" placeholder="1–31" value="1" required>
                        </div>
                        <div class="col-md-6 d-flex align-items-end pb-1">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="add-service-is-active"
                                       class="form-check-input" value="1" checked>
                                <label class="form-check-label" for="add-service-is-active">Servicio activo</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="btn-submit-add-service">
                    <i class="fas fa-save me-1"></i>Guardar Servicio
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Generar Orden de Compra  (#modalGeneratePO)
══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalGeneratePO" tabindex="-1"
     aria-labelledby="modalGeneratePOLabel" aria-hidden="true"
     data-bs-backdrop="static" style="z-index:1065;">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border-top:4px solid #198754;">
            <div class="modal-header">
                <h5 class="modal-title" id="modalGeneratePOLabel">
                    <i class="fas fa-file-invoice-dollar me-2 text-success"></i>Generar Orden de Compra
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="form-generate-po" novalidate>
                    <input type="hidden" name="fin_supplier_id" id="po-supplier-id">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Proveedor</label>
                            <input type="text" id="po-supplier-name" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sucursal / Unidad receptora <span class="text-danger">*</span></label>
                            <select name="org_branch_id" id="po-branch-id" class="form-select" required
                                    data-default="<?= (int) ($response['default_branch_id'] ?? 0) ?>">
                                <option value="">— Seleccione —</option>
                                <?php foreach ($response['branches'] ?? [] as $branch): ?>
                                <option value="<?= (int) $branch->id ?>"
                                    <?= ((int) ($response['default_branch_id'] ?? 0) === (int) $branch->id) ? 'selected' : '' ?>>
                                    <?= esc($branch->commercial_name ?? $branch->name) ?>
                                    (<?= esc($branch->branch_code) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Define quién recibe la mercancía y a qué RFC se asocia el gasto.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Número de OC <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="po_number" id="po-number"
                                       class="form-control" placeholder="Auto-generado" required>
                                <button type="button" class="btn btn-outline-secondary"
                                        id="btn-gen-po-number" title="Generar número">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estatus Inicial</label>
                            <select name="status" class="form-select">
                                <option value="draft">
                                    <i class="fas fa-file-alt"></i> Borrador
                                </option>
                                <option value="sent">Enviada al Proveedor</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Moneda <span class="text-danger">*</span></label>
                            <select name="currency" id="po-currency" class="form-select">
                                <option value="MXN">MXN — Peso Mexicano</option>
                                <option value="USD">USD — Dólar Estadounidense</option>
                                <option value="EUR">EUR — Euro</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha de Emisión</label>
                            <input type="date" name="issue_date" id="po-issue-date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha Estimada de Entrega</label>
                            <input type="date" name="delivery_date" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notas / Condiciones de la OC</label>
                            <textarea name="notes" class="form-control" rows="2"
                                      placeholder="Términos de pago, condiciones de entrega, observaciones…"></textarea>
                        </div>
                    </div>

                    <h6 class="text-muted text-uppercase fw-bold border-bottom pb-2 mb-3">
                        <i class="fas fa-list-ol me-1"></i>Artículos / Líneas de la Orden
                        <button type="button" class="btn btn-outline-success btn-sm ms-2"
                                id="btn-add-po-item">
                            <i class="fas fa-plus"></i> Agregar Línea
                        </button>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" id="po-items-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:38%">
                                        Descripción <span class="text-danger">*</span>
                                    </th>
                                    <th style="width:12%">Cantidad</th>
                                    <th style="width:17%">Precio Unitario</th>
                                    <th style="width:17%">Total Línea</th>
                                    <th style="width:6%"></th>
                                </tr>
                            </thead>
                            <tbody id="po-items-tbody"></tbody>
                            <tfoot>
                                <tr class="table-secondary fw-bold">
                                    <td colspan="3" class="text-end pe-3">Total General:</td>
                                    <td id="po-grand-total" class="font-monospace">$0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <template id="tpl-po-item-row">
                        <tr class="po-item-row">
                            <td>
                                <input type="text" name="items[__idx__][description]"
                                       class="form-control form-control-sm"
                                       placeholder="Descripción del artículo o servicio" required>
                            </td>
                            <td>
                                <input type="number" name="items[__idx__][quantity]"
                                       class="form-control form-control-sm po-qty"
                                       min="1" value="1">
                            </td>
                            <td>
                                <input type="number" name="items[__idx__][unit_price]"
                                       class="form-control form-control-sm po-price"
                                       min="0" step="0.01" value="0.00">
                            </td>
                            <td class="po-line-total align-middle text-end font-monospace">$0.00</td>
                            <td class="text-center">
                                <button type="button"
                                        class="btn btn-outline-danger btn-sm btn-remove-po-item"
                                        title="Eliminar línea">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-submit-generate-po">
                    <i class="fas fa-save me-1"></i>Crear Orden de Compra
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     JAVASCRIPT — Suppliers Module
     §1  Fetch API con X-Requested-With
     §2  CSRF guard (DataTables xhr.dt + renovación en cada Fetch)
     §6  DataTables guard: isDataTable check
     §10 Select2 dropdownParent en todos los modales
     §17 notifyShow para feedback al usuario
══════════════════════════════════════════════════════════════════════════ -->
<script>
/* ──────────────────────────────────────────────────────────────────────────
   ESTADO GLOBAL DEL MÓDULO
────────────────────────────────────────────────────────────────────────── */
let _supplierId    = null;   // ID del proveedor activo en el offcanvas
let _poItemIdx     = 0;      // Índice para las filas de artículos de OC
let _createCtxIdx  = 0;      // Índice para filas de contactos en modal alta

/* ──────────────────────────────────────────────────────────────────────────
   DOM READY
────────────────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    initSuppliersDataTable();   // §6
    bindCreateModal();
    bindEditModal();
    bindOffcanvas();
    bindContactModals();
    bindProductModal();
    bindServiceModal();
    bindPoModal();
});

/* ──────────────────────────────────────────────────────────────────────────
   §6 — DataTables Guard + Inicialización Server-Side
────────────────────────────────────────────────────────────────────────── */
function initSuppliersDataTable() {
    if ($.fn.dataTable.isDataTable('#datatables-suppliers')) return;

    window.suppliersTable = $('#datatables-suppliers').DataTable({
        processing: true,
        serverSide: true,
        responsive: {
            details: {
                type: 'column',
                target: -1
            }
        },
        dom: "<'row'<'col-sm-12 col-md-6'><'col-sm-12 col-md-6 text-end'l>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row mt-3'<'col-sm-12 col-md-5'i>" +
             "<'col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center'p>>",
        ajax: {
            url: '<?= route_to('suppliers.list_ajax') ?>',
            type: 'POST',
            data: function (d) {
                /* §2 — Inyectar token CSRF actual */
                d['<?= csrf_token() ?>'] = document.getElementById('csrf_token').value;
                d.filter_supplier_type   = document.getElementById('filter-supplier-type').value;
                d.filter_status          = document.getElementById('filter-status').value;
            }
        },
        columns: [
            { data: 0, orderable: false, searchable: false }, // Tipo badge
            { data: 1 },                                       // Nombre / Razón Social
            { data: 2 },                                       // RFC
            { data: 3 },                                       // Email
            { data: 4 },                                       // Teléfono
            { data: 5, orderable: false },                     // # Contactos
            { data: 6, orderable: false },                     // # OC
            { data: 7, orderable: false, searchable: false },  // Estatus
            { data: 8, orderable: false, searchable: false },  // Acciones
            { data: null, defaultContent: '', orderable: false, searchable: false, className: 'dtr-control' }
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json' },
        pageLength: 15,
        order: [[1, 'asc']]
    });

    /* §2 — Escuchar evento xhr.dt para renovar el token CSRF */
    $('#datatables-suppliers').on('xhr.dt', function (e, settings, json, xhr) {
        refreshCsrfFromHeaders(xhr);
    });

    /* Filtros por tipo y estatus */
    ['filter-supplier-type', 'filter-status'].forEach(function (id) {
        document.getElementById(id).addEventListener('change', function () {
            if (window.suppliersTable) window.suppliersTable.draw();
        });
    });

    /* Buscador general con debounce (300ms) */
    var searchInput = document.getElementById('filter-search');
    if (searchInput) {
        var searchTimer;
        searchInput.addEventListener('keyup', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                if (window.suppliersTable) {
                    window.suppliersTable.search(searchInput.value).draw();
                }
            }, 300);
        });
    }

    document.getElementById('btn-clear-filters').addEventListener('click', function () {
        document.getElementById('filter-supplier-type').value = '';
        document.getElementById('filter-status').value = '';
        if (searchInput) searchInput.value = '';
        if (window.suppliersTable) window.suppliersTable.search('').draw();
    });


}

/* ──────────────────────────────────────────────────────────────────────────
   DELEGACIÓN DE EVENTOS — Botones generados dinámicamente por DataTables
────────────────────────────────────────────────────────────────────────── */
document.addEventListener('click', function (e) {

    /* VER → abrir Offcanvas */
    var btnShow = e.target.closest('.btn-show-supplier');
    if (btnShow) { openOffcanvas(btnShow.dataset.id); return; }

    /* EDITAR → Modal de edición */
    var btnEdit = e.target.closest('.btn-edit-supplier');
    if (btnEdit) { loadEditModal(btnEdit.dataset.id); return; }

    /* TOGGLE ESTATUS */
    var btnToggle = e.target.closest('.btn-toggle-supplier');
    if (btnToggle) { toggleStatus(btnToggle.dataset.id); return; }

    /* ELIMINAR (soft delete) */
    var btnDelete = e.target.closest('.btn-delete-supplier');
    if (btnDelete) { deleteSupplier(btnDelete.dataset.id); return; }

    /* RESTAURAR (papelera) */
    var btnRestore = e.target.closest('.btn-restore-supplier');
    if (btnRestore) { restoreSupplier(btnRestore.dataset.id); return; }

    /* EDITAR CONTACTO (desde Offcanvas) */
    var btnEditContact = e.target.closest('.btn-edit-contact');
    if (btnEditContact) { loadEditContactModal(btnEditContact.dataset.id); return; }

    /* MARCAR CONTACTO PRINCIPAL */
    var btnSetPrimary = e.target.closest('.btn-set-primary-contact');
    if (btnSetPrimary) { setPrimaryContact(btnSetPrimary.dataset.id); return; }

    /* ELIMINAR CONTACTO */
    var btnDelContact = e.target.closest('.btn-delete-contact');
    if (btnDelContact) { deleteContact(btnDelContact.dataset.id); return; }

    /* DESVINCULAR PRODUCTO */
    var btnUnlink = e.target.closest('.btn-unlink-product');
    if (btnUnlink) { unlinkProduct(btnUnlink.dataset.supplierId, btnUnlink.dataset.linkId); return; }
});

/* ──────────────────────────────────────────────────────────────────────────
   OFFCANVAS — Perfil 360° del Proveedor
────────────────────────────────────────────────────────────────────────── */
function openOffcanvas(supplierId) {
    loadSupplierProfile(supplierId);
}

/** Alias documentado en §6 — carga el perfil 360° del proveedor en el Offcanvas */
function loadSupplierProfile(supplierId) {
    _supplierId = supplierId;

    /* Mostrar loading y ocultar contenido */
    document.getElementById('oc-loading').classList.remove('d-none');
    document.getElementById('oc-body').classList.add('d-none');

    /* Activar pestaña General al abrir */
    bootstrap.Tab.getOrCreateInstance(document.getElementById('tab-general')).show();

    bootstrap.Offcanvas.getOrCreateInstance(
        document.getElementById('offcanvasSupplierProfile')
    ).show();

    /* §1 Fetch API, §2 CSRF */
    fetch('<?= route_to('suppliers.get_ajax', 0) ?>'.replace('/0', '/' + supplierId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) {
        var nt = r.headers.get('<?= csrf_header() ?>');
        if (nt) document.getElementById('csrf_token').value = nt;
        return r.json();
    })
    .then(function (data) {
        document.getElementById('oc-loading').classList.add('d-none');
        if (!data.success) {
            notifyShow(data.message || 'Error al cargar el proveedor.', 'danger');
            return;
        }
        document.getElementById('oc-body').classList.remove('d-none');
        renderOffcanvasGeneral(data.response.supplier);
        loadActivityTimeline(supplierId);
    })
    .catch(function (err) {
        console.error(err);
        notifyShow('Error de conexión al cargar el proveedor.', 'danger');
    });
}

function renderOffcanvasGeneral(s) {
    var typeBadges = {
        'infrastructure': '<span class="badge badge-subtle-info"><i class="fas fa-server me-1"></i>Infraestructura</span>',
        'hardware':       '<span class="badge badge-subtle-secondary"><i class="fas fa-hdd me-1"></i>Hardware</span>',
        'services':       '<span class="badge badge-subtle-primary"><i class="fas fa-cloud me-1"></i>Servicios</span>',
        'administrative': '<span class="badge badge-subtle-warning"><i class="fas fa-briefcase me-1"></i>Administrativo</span>'
    };

    /* ── Cabecera del offcanvas ── */
    document.getElementById('oc-supplier-name').textContent = s.commercial_name || '—';
    document.getElementById('oc-supplier-type-badge').innerHTML = typeBadges[s.supplier_type] || '';
    document.getElementById('oc-supplier-tax-id').textContent =
        s.tax_id ? 'RFC: ' + s.tax_id : (s.legal_name || '—');

    /* Botón editar en cabecera */
    var ocEdit = document.getElementById('oc-btn-edit');
    if (ocEdit) ocEdit.dataset.supplierId = s.id;

    /* ── Mostrar tabla de datos, ocultar placeholder ── */
    document.getElementById('oc-general-placeholder').style.display = 'none';
    document.getElementById('oc-general-info').style.display = 'block';

    /* ── Poblar cada campo de la tabla ── */
    setText('oc-g-commercial-name', s.commercial_name);
    setText('oc-g-legal-name', s.legal_name);
    setText('oc-g-tax-id', s.tax_id);

    /* Estatus como badge */
    var statusHtml = s.status == 1
        ? '<span class="badge badge-subtle-success"><i class="fas fa-check-circle me-1"></i>Activo</span>'
        : '<span class="badge badge-subtle-danger"><i class="fas fa-times-circle me-1"></i>Inactivo</span>';
    document.getElementById('oc-g-status').innerHTML = statusHtml;

    /* Sitio Web como enlace */
    var websiteEl = document.getElementById('oc-g-website');
    if (s.website) {
        websiteEl.innerHTML = '<a href="' + esc(s.website) + '" target="_blank" rel="noopener">' + esc(s.website) + '</a>';
    } else {
        websiteEl.textContent = '—';
    }

    setText('oc-g-contact-person', s.contact_person);

    /* Email como enlace mailto */
    var emailEl = document.getElementById('oc-g-contact-email');
    if (s.contact_email) {
        emailEl.innerHTML = '<a href="mailto:' + esc(s.contact_email) + '">' + esc(s.contact_email) + '</a>';
    } else {
        emailEl.textContent = '—';
    }

    setText('oc-g-contact-phone', s.contact_phone);
    setText('oc-g-address', s.address);
    setText('oc-g-bank-details', s.bank_details);

    /* Notas internas */
    var notesEl = document.getElementById('oc-g-notes');
    if (s.notes) {
        notesEl.textContent = s.notes;
        notesEl.className = 'fst-italic text-muted';
    } else {
        notesEl.textContent = '—';
        notesEl.className = '';
    }

    setText('oc-g-created-at', s.created_at ? fmtDate(s.created_at) : '—');
    setText('oc-g-updated-at', s.updated_at ? fmtDate(s.updated_at) : '—');
}

/**
 * setText(id, value)
 * Helper para asignar textContent a un elemento con valor por defecto '—'
 */
function setText(id, value) {
    var el = document.getElementById(id);
    if (el) el.textContent = value || '—';
}

/** Carga la bitácora de actividad del proveedor en #oc-activity-timeline (§10.3) */
function loadActivityTimeline(supplierId) {
    var container = document.getElementById('oc-activity-timeline');
    container.innerHTML = '<div class="text-center py-3 text-muted">' +
        '<div class="spinner-border spinner-border-sm me-2"></div>Cargando bitácora…</div>';

    fetch('<?= route_to('suppliers.activity', 0) ?>'.replace('/0', '/' + supplierId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) {
        refreshCsrfFromHeaders(r);
        return r.json();
    })
    .then(function (data) {
        if (!data.success) {
            container.innerHTML = '<p class="text-muted small fst-italic">No se pudo cargar la bitácora.</p>';
            return;
        }
        var logs = (data.response && data.response.logs) ? data.response.logs : [];
        if (logs.length === 0) {
            container.innerHTML = '<p class="text-muted small fst-italic">' +
                '<i class="fas fa-history me-1"></i>Sin actividad registrada.</p>';
            return;
        }
        var html = '<div class="timeline timeline-sm mt-1">';
        logs.forEach(function (log) {
            var typeLabel = (log.type || 'actividad').replace(/_/g, ' ');
            typeLabel = typeLabel.charAt(0).toUpperCase() + typeLabel.slice(1);
            html += '<div class="timeline-item">' +
                '<div class="timeline-point bg-primary"></div>' +
                '<div class="timeline-content">' +
                '<div class="d-flex justify-content-between align-items-start gap-2">' +
                '<strong class="small">' + esc(typeLabel) + '</strong>' +
                '<span class="text-muted text-sm text-nowrap">' + fmtDateTime(log.created_at) + '</span>' +
                '</div>' +
                '<p class="mb-0 small">' + esc(log.description || '') + '</p>' +
                (log.ip_address
                    ? '<small class="text-muted">IP: ' + esc(log.ip_address) + '</small>'
                    : '') +
                '</div></div>';
        });
        html += '</div>';
        container.innerHTML = html;
    })
    .catch(function (err) {
        console.error(err);
        container.innerHTML = '<p class="text-muted small fst-italic">Error al cargar la bitácora.</p>';
    });
}

function bindOffcanvas() {
    /* Tabs lazy-load */
    document.getElementById('tab-contacts').addEventListener('shown.bs.tab', function () {
        if (_supplierId) loadContactsTab(_supplierId);
    });
    document.getElementById('tab-products').addEventListener('shown.bs.tab', function () {
        if (_supplierId) {
            loadProductsTab(_supplierId);
            loadServicesTab(_supplierId);
        }
    });
    document.getElementById('tab-orders').addEventListener('shown.bs.tab', function () {
        if (_supplierId) loadOrdersTab(_supplierId);
    });

    /* Botón editar en cabecera del offcanvas */
    var ocBtnEdit = document.getElementById('oc-btn-edit');
    if (ocBtnEdit) {
        ocBtnEdit.addEventListener('click', function () {
            if (_supplierId) loadEditModal(_supplierId);
        });
    }

    /* Botón Agregar Contacto */
    var btnOpenContact = document.getElementById('btn-open-add-contact');
    if (btnOpenContact) {
        btnOpenContact.addEventListener('click', function () {
            document.getElementById('add-contact-supplier-id').value = _supplierId;
            document.getElementById('form-add-contact').reset();
            new bootstrap.Modal(document.getElementById('modalAddContact')).show();
        });
    }

    /* Botón Asociar Producto */
    var btnLinkProd = document.getElementById('btn-open-link-product');
    if (btnLinkProd) {
        btnLinkProd.addEventListener('click', function () {
            openLinkProductModal(_supplierId);
        });
    }

    /* Botón Agregar Servicio Recurrente */
    var btnSvc = document.getElementById('btn-open-add-service');
    if (btnSvc) {
        btnSvc.addEventListener('click', function () {
            document.getElementById('add-service-supplier-id').value = _supplierId;
            document.getElementById('form-add-service').reset();
            document.getElementById('add-service-is-active').checked = true;
            new bootstrap.Modal(document.getElementById('modalAddRecurringService')).show();
        });
    }

    /* Botón Generar OC */
    var btnPo = document.getElementById('btn-open-generate-po');
    if (btnPo) {
        btnPo.addEventListener('click', function () {
            openGeneratePoModal(_supplierId);
        });
    }
}

/* ── Carga de contenido de tabs (lazy) ────────────────────────────────── */
function loadContactsTab(supplierId) {
    var container = document.getElementById('oc-contacts-list');
    container.innerHTML = '<div class="text-center py-4 text-muted">' +
        '<div class="spinner-border spinner-border-sm me-2"></div>Cargando contactos…</div>';

    fetch('<?= site_url('nat/suppliers/') ?>' + supplierId + '/contacts', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) {
        refreshCsrfFromHeaders(r);
        return r.json();
    })
    .then(function (data) {
        if (!data.success) {
            notifyShow(data.message || 'Error al cargar contactos.', 'danger');
            container.innerHTML = '<p class="text-muted text-center py-4 fst-italic">No se pudieron cargar los contactos.</p>';
            return;
        }
        var contacts = data.response.contacts || [];
        if (contacts.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-4 fst-italic">' +
                '<i class="fas fa-address-card me-2"></i>Sin contactos registrados.</p>';
            return;
        }
        var canManage = <?= auth()->user()->can('catalog.manage-suppliers') ? 'true' : 'false' ?>;
        var html = '<div class="list-group list-group-flush">';
        contacts.forEach(function (c) {
            var pBadge  = c.is_primary
                ? '<span class="badge badge-subtle-success ms-2"><i class="fas fa-star me-1"></i>Principal</span>'
                : '';
            var actions = canManage
                ? '<div class="d-flex gap-1 mt-2">' +
                  '<button class="btn btn-outline-warning btn-sm btn-edit-contact" data-id="' + c.id + '" title="Editar">' +
                  '<i class="fas fa-edit"></i></button>' +
                  '<button class="btn btn-outline-secondary btn-sm btn-set-primary-contact" data-id="' + c.id + '" title="Marcar como principal">' +
                  '<i class="fas fa-star"></i></button>' +
                  '<button class="btn btn-outline-danger btn-sm btn-delete-contact" data-id="' + c.id + '" title="Eliminar">' +
                  '<i class="fas fa-trash"></i></button></div>'
                : '';
            html += '<div class="list-group-item px-0">' +
                '<div class="fw-semibold">' + esc(c.contact_name) + pBadge + '</div>' +
                (c.job_title ? '<small class="text-muted">' + esc(c.job_title) + '</small>' : '') +
                '<div class="mt-1 small text-muted d-flex flex-wrap gap-3">' +
                (c.email ? '<span><i class="fas fa-envelope me-1"></i><a href="mailto:' + esc(c.email) + '">' + esc(c.email) + '</a></span>' : '') +
                (c.phone ? '<span><i class="fas fa-phone me-1"></i>' + esc(c.phone) + '</span>' : '') +
                '</div>' + actions + '</div>';
        });
        html += '</div>';
        container.innerHTML = html;
    })
    .catch(function (err) {
        console.error(err);
        notifyShow('Error de conexión al cargar contactos.', 'danger');
        container.innerHTML = '<p class="text-muted text-center py-4 fst-italic">No se pudieron cargar los contactos.</p>';
    });
}

function loadProductsTab(supplierId) {
    var container = document.getElementById('oc-products-list');
    container.innerHTML = '<div class="text-center py-4 text-muted">' +
        '<div class="spinner-border spinner-border-sm me-2"></div>Cargando productos…</div>';

    fetch('<?= site_url('nat/suppliers/') ?>' + supplierId + '/products', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) {
        refreshCsrfFromHeaders(r);
        return r.json();
    })
    .then(function (data) {
        if (!data.success) {
            notifyShow(data.message || 'Error al cargar productos.', 'danger');
            container.innerHTML = '<p class="text-muted text-center py-4 fst-italic">No se pudieron cargar los productos.</p>';
            return;
        }
        var products = data.response.products || [];
        if (products.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-4 fst-italic">' +
                '<i class="fas fa-box-open me-2"></i>Sin productos asociados a este proveedor.</p>';
            return;
        }
        var canManage = <?= auth()->user()->can('catalog.manage-suppliers') ? 'true' : 'false' ?>;
        var html = '<div class="table-responsive"><table class="table table-sm table-hover mb-0">' +
            '<thead class="table-light"><tr><th>SKU</th><th>Producto</th><th>Costo</th>' +
            '<th>Vencimiento</th><th></th></tr></thead><tbody>';
        products.forEach(function (p) {
            var dueDate  = p.supplier_due_date ? fmtDate(p.supplier_due_date) : '—';
            var cost     = p.purchase_cost
                ? '$' + parseFloat(p.purchase_cost).toFixed(2) + ' ' + esc(p.purchase_currency || '')
                : '—';
            var renewBadge = p.is_auto_renew
                ? '<span class="badge badge-subtle-success ms-1" title="Renovación automática">' +
                  '<i class="fas fa-sync-alt"></i></span>'
                : '';
            var unlinkBtn = canManage
                ? '<button class="btn btn-outline-danger btn-sm btn-unlink-product" ' +
                  'data-link-id="' + p.id + '" data-supplier-id="' + supplierId + '" ' +
                  'title="Desvincular"><i class="fas fa-unlink"></i></button>'
                : '';
            html += '<tr><td class="font-monospace small">' + esc(p.product_sku || '—') + '</td>' +
                '<td>' + esc(p.product_name || '—') + renewBadge + '</td>' +
                '<td>' + cost + '</td><td>' + dueDate + '</td><td>' + unlinkBtn + '</td></tr>';
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;
    })
    .catch(function (err) {
        console.error(err);
        notifyShow('Error de conexión al cargar productos.', 'danger');
        container.innerHTML = '<p class="text-muted text-center py-4 fst-italic">No se pudieron cargar los productos.</p>';
    });
}

function loadServicesTab(supplierId) {
    var container = document.getElementById('oc-services-list');
    container.innerHTML = '<div class="text-center py-3 text-muted">' +
        '<div class="spinner-border spinner-border-sm me-2"></div>Cargando servicios…</div>';

    fetch('<?= route_to('suppliers.services.list', 0) ?>'.replace('/0', '/' + supplierId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) {
        refreshCsrfFromHeaders(r);
        return r.json();
    })
    .then(function (data) {
        if (!data.success) {
            notifyShow(data.message || 'Error al cargar servicios.', 'danger');
            container.innerHTML = '<p class="text-muted text-center py-4 fst-italic">No se pudieron cargar los servicios.</p>';
            return;
        }
        var services = (data.response && data.response.services) ? data.response.services : [];
        if (services.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-4 fst-italic">' +
                '<i class="fas fa-bolt me-2"></i>Sin servicios recurrentes registrados.</p>';
            return;
        }
        var freqMap = {
            weekly:     'Semanal',
            monthly:    'Mensual',
            quarterly:  'Trimestral',
            semiannual: 'Semestral',
            yearly:     'Anual'
        };
        var serviceTypeMap = <?= json_encode(
            \App\Models\Financial\FinRecurringExpenseModel::getServiceTypeOptions(),
            JSON_UNESCAPED_UNICODE
        ) ?>;
        var html = '<div class="table-responsive"><table class="table table-sm table-hover mb-0">' +
            '<thead class="table-light"><tr><th>Servicio</th><th>Tipo</th><th>Monto</th>' +
            '<th>Día pago</th><th>Frecuencia</th><th>Estatus</th></tr></thead><tbody>';
        services.forEach(function (svc) {
            var amount = svc.amount
                ? '$' + parseFloat(svc.amount).toFixed(2) + ' ' + esc(svc.currency || 'MXN')
                : '—';
            var freq   = freqMap[svc.frequency] || esc(svc.frequency || '—');
            var typeLbl = serviceTypeMap[svc.service_type] || esc(svc.service_type || '—');
            var status = parseInt(svc.is_active)
                ? '<span class="badge badge-subtle-success"><i class="fas fa-check me-1"></i>Activo</span>'
                : '<span class="badge badge-subtle-secondary"><i class="fas fa-pause me-1"></i>Inactivo</span>';
            html += '<tr>' +
                '<td>' + esc(svc.description || '—') + '</td>' +
                '<td><span class="small text-muted">' + typeLbl + '</span></td>' +
                '<td class="font-monospace">' + amount + '</td>' +
                '<td class="text-center">' + esc(String(svc.billing_day || '—')) + '</td>' +
                '<td>' + freq + '</td>' +
                '<td>' + status + '</td></tr>';
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;
    })
    .catch(function (err) {
        console.error(err);
        notifyShow('Error de conexión al cargar servicios.', 'danger');
        container.innerHTML = '<p class="text-muted text-center py-4 fst-italic">No se pudieron cargar los servicios.</p>';
    });
}

function loadOrdersTab(supplierId) {
    var container = document.getElementById('oc-orders-list');
    container.innerHTML = '<div class="text-center py-4 text-muted">' +
        '<div class="spinner-border spinner-border-sm me-2"></div>Cargando órdenes…</div>';

    fetch('<?= site_url('nat/suppliers/') ?>' + supplierId + '/purchase-orders', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) {
        refreshCsrfFromHeaders(r);
        return r.json();
    })
    .then(function (data) {
        if (!data.success) {
            notifyShow(data.message || 'Error al cargar órdenes de compra.', 'danger');
            container.innerHTML = '<p class="text-muted text-center py-4 fst-italic">No se pudieron cargar las órdenes.</p>';
            return;
        }
        var orders = data.response.orders || [];
        if (orders.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-4 fst-italic">' +
                '<i class="fas fa-file-invoice me-2"></i>Sin órdenes de compra registradas.</p>';
            return;
        }
        var statusMap = {
            draft:     '<span class="badge badge-subtle-secondary">Borrador</span>',
            sent:      '<span class="badge badge-subtle-info">Enviada</span>',
            approved:  '<span class="badge badge-subtle-success">Aprobada</span>',
            completed: '<span class="badge badge-subtle-primary">Completada</span>',
            cancelled: '<span class="badge badge-subtle-danger">Cancelada</span>'
        };
        var dlBase = '<?= site_url('nat/suppliers/purchase-orders/download/') ?>';
        var html = '<div class="table-responsive"><table class="table table-sm table-hover mb-0">' +
            '<thead class="table-light"><tr><th>OC #</th><th>Estatus</th><th>Total</th>' +
            '<th>Emisión</th><th>Acciones</th></tr></thead><tbody>';
        orders.forEach(function (o) {
            var badge  = statusMap[o.status] ||
                '<span class="badge bg-secondary">' + esc(o.status) + '</span>';
            var total  = o.total_amount
                ? '$' + parseFloat(o.total_amount).toFixed(2) + ' ' + esc(o.currency || '')
                : '—';
            html += '<tr>' +
                '<td class="font-monospace fw-semibold">' + esc(o.po_number) + '</td>' +
                '<td>' + badge + '</td>' +
                '<td class="font-monospace">' + total + '</td>' +
                '<td>' + fmtDate(o.issue_date) + '</td>' +
                '<td><div class="d-flex gap-1">' +
                '<a href="' + dlBase + o.id + '?action=view" target="_blank" rel="noopener" ' +
                'class="btn btn-outline-info btn-sm" title="Previsualizar PDF">' +
                '<i class="fas fa-eye"></i></a>' +
                '<a href="' + dlBase + o.id + '" class="btn btn-outline-secondary btn-sm" ' +
                'title="Descargar PDF"><i class="fas fa-download"></i></a>' +
                '</div></td></tr>';
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;
    })
    .catch(function (err) {
        console.error(err);
        notifyShow('Error de conexión al cargar órdenes de compra.', 'danger');
        container.innerHTML = '<p class="text-muted text-center py-4 fst-italic">No se pudieron cargar las órdenes.</p>';
    });
}

/* ──────────────────────────────────────────────────────────────────────────
   MODAL CREAR PROVEEDOR
────────────────────────────────────────────────────────────────────────── */
function bindCreateModal() {
    /* §10 — Select2 con dropdownParent al mostrarse el modal */
    $('#modalCreateSupplier').on('shown.bs.modal', function () {
        if (!$('#create-supplier-type').hasClass('select2-hidden-accessible')) {
            $('#create-supplier-type').select2({
                theme: 'bootstrap-5',
                placeholder: '— Seleccione un tipo —',
                allowClear: false,
                width: '100%',
                dropdownParent: $('#modalCreateSupplier')
            });
        }
    });

    /* Contactos dinámicos — agregar fila */
    document.getElementById('btn-add-create-contact').addEventListener('click', function () {
        var tpl   = document.getElementById('tpl-contact-row');
        var clone = tpl.content.cloneNode(true);
        clone.querySelectorAll('[name]').forEach(function (el) {
            el.name = el.name.replace(/__idx__/g, _createCtxIdx);
        });
        document.getElementById('create-contacts-container').appendChild(clone);
        _createCtxIdx++;
    });

    /* Contactos dinámicos — eliminar fila */
    document.getElementById('create-contacts-container').addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-remove-contact-row');
        if (btn) btn.closest('.contact-row').remove();
    });

    /* Reset al cerrar */
    document.getElementById('modalCreateSupplier').addEventListener('hidden.bs.modal', function () {
        document.getElementById('form-create-supplier').reset();
        document.getElementById('create-contacts-container').innerHTML = '';
        _createCtxIdx = 0;
        if ($('#create-supplier-type').hasClass('select2-hidden-accessible')) {
            $('#create-supplier-type').val(null).trigger('change');
        }
    });

    document.getElementById('btn-submit-create-supplier').addEventListener('click', function () {
        submitCreate();
    });
}

function submitCreate() {
    var form = document.getElementById('form-create-supplier');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    var btn      = document.getElementById('btn-submit-create-supplier');
    var token    = document.getElementById('csrf_token').value;
    var formData = new FormData(form);

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

    /* §1 — Fetch API, §2 — CSRF en cabecera */
    fetch('<?= route_to('suppliers.store') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_header() ?>': token
        },
        body: formData
    })
    .then(function (r) {
        var nt = r.headers.get('<?= csrf_header() ?>');
        if (nt) document.getElementById('csrf_token').value = nt;
        return r.json();
    })
    .then(function (data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Guardar Proveedor';
        if (data.success) {
            /* §17 — notifyShow */
            notifyShow(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalCreateSupplier')).hide();
            if (window.suppliersTable) window.suppliersTable.ajax.reload(null, false);
        } else {
            var errs = data.errors ? Object.values(data.errors).join('<br>') : '';
            notifyShow((data.message || 'Error al guardar.') + (errs ? '<br>' + errs : ''), 'danger');
        }
    })
    .catch(function (err) {
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Guardar Proveedor';
        notifyShow('Error de conexión con el servidor.', 'danger');
    });
}

/* ──────────────────────────────────────────────────────────────────────────
   MODAL EDITAR PROVEEDOR
────────────────────────────────────────────────────────────────────────── */
function bindEditModal() {
    /* §10 — Select2 con dropdownParent */
    $('#modalEditSupplier').on('shown.bs.modal', function () {
        if (!$('#edit-supplier-type').hasClass('select2-hidden-accessible')) {
            $('#edit-supplier-type').select2({
                theme: 'bootstrap-5',
                placeholder: '— Seleccione un tipo —',
                allowClear: false,
                width: '100%',
                dropdownParent: $('#modalEditSupplier')
            });
        }
    });

    /* Reset al cerrar */
    document.getElementById('modalEditSupplier').addEventListener('hidden.bs.modal', function () {
        document.getElementById('form-edit-supplier').style.display    = 'none';
        document.getElementById('edit-supplier-loading').style.display = 'block';
    });

    document.getElementById('btn-submit-edit-supplier').addEventListener('click', function () {
        submitEdit();
    });
}

function loadEditModal(supplierId) {
    document.getElementById('form-edit-supplier').style.display    = 'none';
    document.getElementById('edit-supplier-loading').style.display = 'block';

    new bootstrap.Modal(document.getElementById('modalEditSupplier')).show();

    fetch('<?= route_to('suppliers.get_ajax', 0) ?>'.replace('/0', '/' + supplierId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) {
        var nt = r.headers.get('<?= csrf_header() ?>');
        if (nt) document.getElementById('csrf_token').value = nt;
        return r.json();
    })
    .then(function (data) {
        document.getElementById('edit-supplier-loading').style.display = 'none';
        if (!data.success) {
            notifyShow(data.message || 'Error al cargar datos.', 'danger');
            bootstrap.Modal.getInstance(document.getElementById('modalEditSupplier')).hide();
            return;
        }
        var s = data.response.supplier;
        document.getElementById('edit-supplier-id').value      = s.id;
        document.getElementById('edit-commercial-name').value  = s.commercial_name || '';
        document.getElementById('edit-legal-name').value       = s.legal_name      || '';
        document.getElementById('edit-tax-id').value           = s.tax_id          || '';
        document.getElementById('edit-contact-person').value   = s.contact_person  || '';
        document.getElementById('edit-contact-email').value    = s.contact_email   || '';
        document.getElementById('edit-contact-phone').value    = s.contact_phone   || '';
        document.getElementById('edit-website').value          = s.website         || '';
        document.getElementById('edit-address').value          = s.address         || '';
        document.getElementById('edit-bank-details').value     = s.bank_details    || '';
        document.getElementById('edit-notes').value            = s.notes           || '';
        document.getElementById('edit-status').value           = s.status;

        /* Sync Select2 */
        if ($('#edit-supplier-type').hasClass('select2-hidden-accessible')) {
            $('#edit-supplier-type').val(s.supplier_type).trigger('change');
        } else {
            document.getElementById('edit-supplier-type').value = s.supplier_type || '';
        }
        document.getElementById('form-edit-supplier').style.display = 'block';
    })
    .catch(function (err) {
        console.error(err);
        notifyShow('Error de conexión al cargar el proveedor.', 'danger');
    });
}

function submitEdit() {
    var form = document.getElementById('form-edit-supplier');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    var btn      = document.getElementById('btn-submit-edit-supplier');
    var suppId   = document.getElementById('edit-supplier-id').value;
    var token    = document.getElementById('csrf_token').value;
    var formData = new FormData(form);

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Actualizando…';

    fetch('<?= route_to('suppliers.update', 0) ?>'.replace('/0', '/' + suppId), {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_header() ?>': token
        },
        body: formData
    })
    .then(function (r) {
        var nt = r.headers.get('<?= csrf_header() ?>');
        if (nt) document.getElementById('csrf_token').value = nt;
        return r.json();
    })
    .then(function (data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Actualizar Proveedor';
        if (data.success) {
            notifyShow(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalEditSupplier')).hide();
            if (window.suppliersTable) window.suppliersTable.ajax.reload(null, false);
            /* Recargar offcanvas si el proveedor editado es el activo */
            if (_supplierId == suppId) openOffcanvas(suppId);
        } else {
            var errs = data.errors ? Object.values(data.errors).join('<br>') : '';
            notifyShow((data.message || 'Error al actualizar.') + (errs ? '<br>' + errs : ''), 'danger');
        }
    })
    .catch(function (err) {
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Actualizar Proveedor';
        notifyShow('Error de conexión con el servidor.', 'danger');
    });
}

/* ──────────────────────────────────────────────────────────────────────────
   CRUD — Eliminar y Toggle de Estatus
────────────────────────────────────────────────────────────────────────── */
function deleteSupplier(supplierId) {
    if (!confirm('¿Eliminar este proveedor? La acción puede revertirse.')) return;
    csrfFetch('<?= route_to('suppliers.delete', 0) ?>'.replace('/0', '/' + supplierId))
        .then(function (data) {
            if (data.success) {
                notifyShow(data.message, 'success');
                if (window.suppliersTable) window.suppliersTable.ajax.reload(null, false);
            } else {
                notifyShow(data.message || 'Error al eliminar.', 'danger');
            }
        });
}

function restoreSupplier(supplierId) {
    if (!confirm('¿Restaurar este proveedor?')) return;
    csrfFetch('<?= route_to('suppliers.restore', 0) ?>'.replace('/0', '/' + supplierId))
        .then(function (data) {
            if (data.success) {
                notifyShow(data.message, 'success');
                if (window.suppliersTable) window.suppliersTable.ajax.reload(null, false);
            } else {
                notifyShow(data.message || 'Error al restaurar.', 'danger');
            }
        });
}

function toggleStatus(supplierId) {
    csrfFetch('<?= route_to('suppliers.toggle_status', 0) ?>'.replace('/0', '/' + supplierId))
        .then(function (data) {
            if (data.success) {
                notifyShow(data.message, 'success');
                if (window.suppliersTable) window.suppliersTable.ajax.reload(null, false);
            } else {
                notifyShow(data.message || 'Error al cambiar estatus.', 'danger');
            }
        });
}

/* ──────────────────────────────────────────────────────────────────────────
   CONTACTOS
────────────────────────────────────────────────────────────────────────── */
function bindContactModals() {
    document.getElementById('btn-submit-add-contact').addEventListener('click', function () {
        submitAddContact();
    });
    document.getElementById('btn-submit-edit-contact').addEventListener('click', function () {
        submitEditContact();
    });
    document.getElementById('modalAddContact').addEventListener('hidden.bs.modal', function () {
        document.getElementById('form-add-contact').reset();
    });
    document.getElementById('modalEditContact').addEventListener('hidden.bs.modal', function () {
        document.getElementById('form-edit-contact').style.display    = 'none';
        document.getElementById('edit-contact-loading').style.display = 'block';
    });
}

function submitAddContact() {
    var form = document.getElementById('form-add-contact');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    var btn    = document.getElementById('btn-submit-add-contact');
    var suppId = document.getElementById('add-contact-supplier-id').value;
    var token  = document.getElementById('csrf_token').value;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

    fetch('<?= site_url('nat/suppliers/') ?>' + suppId + '/contacts/store', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_header() ?>': token
        },
        body: new FormData(form)
    })
    .then(function (r) {
        var nt = r.headers.get('<?= csrf_header() ?>');
        if (nt) document.getElementById('csrf_token').value = nt;
        return r.json();
    })
    .then(function (data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Guardar Contacto';
        if (data.success) {
            notifyShow(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalAddContact')).hide();
            loadContactsTab(suppId);
        } else {
            notifyShow(data.message || 'Error al guardar contacto.', 'danger');
        }
    })
    .catch(function (err) {
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Guardar Contacto';
        notifyShow('Error de conexión.', 'danger');
    });
}

function loadEditContactModal(contactId) {
    document.getElementById('form-edit-contact').style.display    = 'none';
    document.getElementById('edit-contact-loading').style.display = 'block';
    new bootstrap.Modal(document.getElementById('modalEditContact')).show();

    fetch('<?= route_to('suppliers.contacts.get_ajax', 0) ?>'.replace('/0', '/' + contactId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) {
        refreshCsrfFromHeaders(r);
        return r.json();
    })
    .then(function (data) {
        document.getElementById('edit-contact-loading').style.display = 'none';
        if (!data.success) { notifyShow(data.message, 'danger'); return; }
        var c = data.response.contact;
        document.getElementById('edit-contact-id').value          = c.id;
        document.getElementById('edit-contact-supplier-id').value = c.fin_supplier_id;
        document.getElementById('ec-name').value                   = c.contact_name || '';
        document.getElementById('ec-job-title').value              = c.job_title     || '';
        document.getElementById('ec-email').value                  = c.email         || '';
        document.getElementById('ec-phone').value                  = c.phone         || '';
        document.getElementById('ec-is-primary').checked           = !!parseInt(c.is_primary);
        document.getElementById('form-edit-contact').style.display = 'block';
    })
    .catch(function (err) {
        console.error(err);
        notifyShow('Error de conexión.', 'danger');
    });
}

function submitEditContact() {
    var form = document.getElementById('form-edit-contact');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    var btn       = document.getElementById('btn-submit-edit-contact');
    var contactId = document.getElementById('edit-contact-id').value;
    var suppId    = document.getElementById('edit-contact-supplier-id').value;
    var token     = document.getElementById('csrf_token').value;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

    fetch('<?= route_to('suppliers.contacts.update', 0) ?>'.replace('/0', '/' + contactId), {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_header() ?>': token
        },
        body: new FormData(form)
    })
    .then(function (r) {
        var nt = r.headers.get('<?= csrf_header() ?>');
        if (nt) document.getElementById('csrf_token').value = nt;
        return r.json();
    })
    .then(function (data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Actualizar Contacto';
        if (data.success) {
            notifyShow(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalEditContact')).hide();
            loadContactsTab(suppId);
        } else {
            notifyShow(data.message || 'Error al actualizar contacto.', 'danger');
        }
    })
    .catch(function (err) {
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Actualizar Contacto';
        notifyShow('Error de conexión.', 'danger');
    });
}

function deleteContact(contactId) {
    if (!confirm('¿Eliminar este contacto?')) return;
    var suppId = _supplierId;
    csrfFetch('<?= route_to('suppliers.contacts.delete', 0) ?>'.replace('/0', '/' + contactId))
        .then(function (data) {
            if (data.success) {
                notifyShow(data.message, 'success');
                if (suppId) loadContactsTab(suppId);
            } else {
                notifyShow(data.message || 'Error al eliminar.', 'danger');
            }
        });
}

function setPrimaryContact(contactId) {
    var suppId = _supplierId;
    csrfFetch('<?= route_to('suppliers.contacts.set_primary', 0) ?>'.replace('/0', '/' + contactId))
        .then(function (data) {
            if (data.success) {
                notifyShow(data.message, 'success');
                if (suppId) loadContactsTab(suppId);
            } else {
                notifyShow(data.message || 'Error.', 'danger');
            }
        });
}

/* ──────────────────────────────────────────────────────────────────────────
   PRODUCTOS DEL CATÁLOGO — Vincular / Desvincular
────────────────────────────────────────────────────────────────────────── */
function bindProductModal() {
    /* §10 — Select2 con dropdownParent para el select de producto */
    $('#modalAssociateProduct').on('show.bs.modal', function () {
        loadCatalogProductOptions();
    });

    $('#modalAssociateProduct').on('hidden.bs.modal', function () {
        document.getElementById('form-link-product').reset();
        if ($('#link-catalog-product-id').hasClass('select2-hidden-accessible')) {
            $('#link-catalog-product-id').val(null).trigger('change');
        }
    });

    document.getElementById('btn-submit-link-product').addEventListener('click', function () {
        submitLinkProduct();
    });
}

function loadCatalogProductOptions() {
    var sel = document.getElementById('link-catalog-product-id');
    /* Solo cargar una vez */
    if (sel.options.length > 1) {
        /* Re-inicializar Select2 si aún no está activo */
        initLinkProductSelect2();
        return;
    }

    var token = document.getElementById('csrf_token').value;
    var fd    = new FormData();
    fd.append('draw',              '1');
    fd.append('start',             '0');
    fd.append('length',            '500');
    fd.append('search[value]',     '');
    fd.append('search[regex]',     'false');
    fd.append('<?= csrf_token() ?>', token);

    fetch('<?= route_to('catalog.products_ajax') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_header() ?>': token
        },
        body: fd
    })
    .then(function (r) {
        var nt = r.headers.get('<?= csrf_header() ?>');
        if (nt) document.getElementById('csrf_token').value = nt;
        return r.json();
    })
    .then(function (data) {
        (data.data || []).forEach(function (row) {
            /* El ID está en el atributo data-id del último elemento de acciones */
            var actionsHtml = row[row.length - 1] || '';
            var idMatch     = actionsHtml.match(/data-id=['"']?(\d+)['"']?/);
            if (!idMatch) return;

            /* El nombre está en la 2ª columna; quitar HTML */
            var tmp = document.createElement('div');
            tmp.innerHTML = row[1] || '';
            var name = (tmp.textContent || tmp.innerText || '').trim().split('\n')[0].trim();
            if (!name) return;

            var opt    = document.createElement('option');
            opt.value  = idMatch[1];
            opt.text   = name;
            sel.appendChild(opt);
        });
        initLinkProductSelect2();
    })
    .catch(function (err) {
        console.error('Error cargando productos:', err);
        notifyShow('Error al cargar el catálogo de productos.', 'danger');
    });
}

function initLinkProductSelect2() {
    if (!$('#link-catalog-product-id').hasClass('select2-hidden-accessible')) {
        $('#link-catalog-product-id').select2({
            theme: 'bootstrap-5',
            placeholder: '— Buscar o seleccionar producto —',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#modalAssociateProduct')
        });
    }
    if (!$('#link-purchase-currency').hasClass('select2-hidden-accessible')) {
        $('#link-purchase-currency').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('#modalAssociateProduct')
        });
    }
}

function openLinkProductModal(supplierId) {
    document.getElementById('link-product-supplier-id').value = supplierId;
    document.getElementById('form-link-product').reset();
    if ($('#link-catalog-product-id').hasClass('select2-hidden-accessible')) {
        $('#link-catalog-product-id').val(null).trigger('change');
    }
    new bootstrap.Modal(document.getElementById('modalAssociateProduct')).show();
}

function submitLinkProduct() {
    var form = document.getElementById('form-link-product');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    /* Validación Select2 */
    var productId = document.getElementById('link-catalog-product-id').value;
    if (!productId) {
        notifyShow('Seleccione un producto del catálogo.', 'warning');
        return;
    }

    var btn    = document.getElementById('btn-submit-link-product');
    var suppId = document.getElementById('link-product-supplier-id').value;
    var token  = document.getElementById('csrf_token').value;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

    fetch('<?= site_url('nat/suppliers/') ?>' + suppId + '/products/link', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_header() ?>': token
        },
        body: new FormData(form)
    })
    .then(function (r) {
        var nt = r.headers.get('<?= csrf_header() ?>');
        if (nt) document.getElementById('csrf_token').value = nt;
        return r.json();
    })
    .then(function (data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-link me-1"></i>Asociar Producto';
        if (data.success) {
            notifyShow(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalAssociateProduct')).hide();
            loadProductsTab(suppId);
        } else {
            notifyShow(data.message || 'Error al asociar el producto.', 'danger');
        }
    })
    .catch(function (err) {
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-link me-1"></i>Asociar Producto';
        notifyShow('Error de conexión.', 'danger');
    });
}

function unlinkProduct(supplierId, linkId) {
    if (!confirm('¿Desvincular este producto del proveedor?')) return;
    csrfFetch('<?= site_url('nat/suppliers/') ?>' + supplierId + '/products/unlink/' + linkId)
        .then(function (data) {
            if (data.success) {
                notifyShow(data.message, 'success');
                loadProductsTab(supplierId);
            } else {
                notifyShow(data.message || 'Error al desvincular.', 'danger');
            }
        });
}

/* ──────────────────────────────────────────────────────────────────────────
   SERVICIOS RECURRENTES — Alta desde Offcanvas
────────────────────────────────────────────────────────────────────────── */
function bindServiceModal() {
    /* §10 — Select2 con dropdownParent */
    $('#modalAddRecurringService').on('shown.bs.modal', function () {
        ['#add-service-type', '#add-service-currency', '#add-service-frequency'].forEach(function (sel) {
            if (!$(sel).hasClass('select2-hidden-accessible')) {
                $(sel).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $('#modalAddRecurringService')
                });
            }
        });
        /* Bind cambio de frecuencia para mostrar/ocultar día de pago */
        toggleBillingDayField();
        $('#add-service-frequency').on('change', toggleBillingDayField);
    });

    document.getElementById('modalAddRecurringService').addEventListener('hidden.bs.modal', function () {
        document.getElementById('form-add-service').reset();
        ['#add-service-type', '#add-service-currency', '#add-service-frequency'].forEach(function (sel) {
            if ($(sel).hasClass('select2-hidden-accessible')) {
                $(sel).val($(sel).find('option:first').val()).trigger('change');
            }
        });
        document.getElementById('add-service-is-active').checked = true;
        /* Restaurar campo día de pago */
        var billingDayGroup = document.getElementById('billing-day-field');
        if (billingDayGroup) billingDayGroup.style.display = '';
    });

    document.getElementById('btn-submit-add-service').addEventListener('click', function () {
        submitAddService();
    });
}

/**
 * toggleBillingDayField
 *
 * Muestra u oculta el campo "Día de Pago" según la frecuencia seleccionada.
 * Si la frecuencia es "weekly" (semanal), el día de pago no aplica y se oculta.
 * Para las demás frecuencias (mensual, trimestral, semestral, anual), el campo
 * se muestra y se marca como requerido.
 */
function toggleBillingDayField() {
    var freq        = document.getElementById('add-service-frequency').value;
    var billingRow  = document.getElementById('billing-day-field');
    var billingInput = billingRow ? billingRow.querySelector('[name="billing_day"]') : null;

    if (!billingRow) return;

    if (freq === 'weekly') {
        billingRow.style.display = 'none';
        if (billingInput) {
            billingInput.removeAttribute('required');
            billingInput.disabled = true;   /* Excluir de FormData cuando está oculto */
        }
    } else {
        billingRow.style.display = '';
        if (billingInput) {
            billingInput.setAttribute('required', 'required');
            billingInput.disabled = false;  /* Re-incluir en FormData */
        }
    }
}

function submitAddService() {
    var form = document.getElementById('form-add-service');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    var btn    = document.getElementById('btn-submit-add-service');
    var suppId = document.getElementById('add-service-supplier-id').value || _supplierId;
    if (!suppId) {
        notifyShow('No hay proveedor seleccionado.', 'warning');
        return;
    }

    var token    = document.getElementById('csrf_token').value;
    var formData = new FormData(form);
    if (!document.getElementById('add-service-is-active').checked) {
        formData.set('is_active', '0');
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

    fetch('<?= route_to('suppliers.services.store', 0) ?>'.replace('/0', '/' + suppId), {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_header() ?>': token
        },
        body: formData
    })
    .then(function (r) {
        var nt = r.headers.get('<?= csrf_header() ?>');
        if (nt) document.getElementById('csrf_token').value = nt;
        return r.json();
    })
    .then(function (data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Guardar Servicio';
        if (data.success) {
            notifyShow(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalAddRecurringService')).hide();
            loadServicesTab(suppId);
            loadActivityTimeline(suppId);
        } else {
            var errs = data.errors ? Object.values(data.errors).join('<br>') : '';
            notifyShow((data.message || 'Error al guardar servicio.') + (errs ? '<br>' + errs : ''), 'danger');
        }
    })
    .catch(function (err) {
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Guardar Servicio';
        notifyShow('Error de conexión.', 'danger');
    });
}

/* ──────────────────────────────────────────────────────────────────────────
   ÓRDENES DE COMPRA — Generar OC
────────────────────────────────────────────────────────────────────────── */
function bindPoModal() {
    /* §10 — Select2 en selects del modal de OC */
    $('#modalGeneratePO').on('shown.bs.modal', function () {
        ['#po-currency'].forEach(function (sel) {
            if (!$(sel).hasClass('select2-hidden-accessible')) {
                $(sel).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $('#modalGeneratePO')
                });
            }
        });
    });
    document.getElementById('btn-gen-po-number').addEventListener('click', fetchPoNumber);

    document.getElementById('btn-add-po-item').addEventListener('click', function () {
        addPoItem(_poItemIdx++);
    });

    document.getElementById('po-items-tbody').addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-remove-po-item');
        if (btn) { btn.closest('.po-item-row').remove(); recalcPoTotals(); }
    });

    document.getElementById('po-items-tbody').addEventListener('input', function (e) {
        if (e.target.classList.contains('po-qty') ||
            e.target.classList.contains('po-price')) recalcPoTotals();
    });

    document.getElementById('btn-submit-generate-po').addEventListener('click', submitGeneratePo);

    document.getElementById('modalGeneratePO').addEventListener('hidden.bs.modal', function () {
        document.getElementById('form-generate-po').reset();
        document.getElementById('po-items-tbody').innerHTML = '';
        document.getElementById('po-grand-total').textContent = '$0.00';
        _poItemIdx = 0;
    });
}

function openGeneratePoModal(supplierId) {
    var supplierName = document.getElementById('oc-supplier-name').textContent || '';
    document.getElementById('po-supplier-id').value    = supplierId;
    document.getElementById('po-supplier-name').value  = supplierName;
    var branchSelect = document.getElementById('po-branch-id');
    if (branchSelect && branchSelect.dataset.default) {
        branchSelect.value = branchSelect.dataset.default;
    } else if (branchSelect && branchSelect.options.length === 2) {
        branchSelect.selectedIndex = 1;
    }
    document.getElementById('po-issue-date').value     = new Date().toISOString().split('T')[0];
    document.getElementById('po-items-tbody').innerHTML = '';
    _poItemIdx = 0;

    new bootstrap.Modal(document.getElementById('modalGeneratePO')).show();
    fetchPoNumber();
    addPoItem(_poItemIdx++); /* Primera línea vacía */
}

function fetchPoNumber() {
    fetch('<?= route_to('suppliers.po.generate_number') ?>', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) {
        refreshCsrfFromHeaders(r);
        return r.json();
    })
    .then(function (data) {
        if (data.success && data.response && data.response.po_number) {
            document.getElementById('po-number').value = data.response.po_number;
        } else if (!data.success) {
            notifyShow(data.message || 'Error al generar número de OC.', 'danger');
        }
    })
    .catch(function (err) {
        console.error('Error al generar número de OC:', err);
        notifyShow('Error de conexión al generar número de OC.', 'danger');
    });
}

function addPoItem(idx) {
    var tpl   = document.getElementById('tpl-po-item-row');
    var clone = tpl.content.cloneNode(true);
    clone.querySelectorAll('[name]').forEach(function (el) {
        el.name = el.name.replace(/__idx__/g, idx);
    });
    document.getElementById('po-items-tbody').appendChild(clone);
}

function recalcPoTotals() {
    var grandTotal = 0;
    document.querySelectorAll('#po-items-tbody .po-item-row').forEach(function (row) {
        var qty   = parseFloat(row.querySelector('.po-qty').value)   || 0;
        var price = parseFloat(row.querySelector('.po-price').value) || 0;
        var total = qty * price;
        row.querySelector('.po-line-total').textContent = '$' + total.toFixed(2);
        grandTotal += total;
    });
    document.getElementById('po-grand-total').textContent = '$' + grandTotal.toFixed(2);
}

function submitGeneratePo() {
    var form  = document.getElementById('form-generate-po');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    var items = document.querySelectorAll('#po-items-tbody .po-item-row');
    if (items.length === 0) {
        notifyShow('Agregue al menos una línea de artículo a la orden.', 'warning');
        return;
    }

    var btn   = document.getElementById('btn-submit-generate-po');
    var token = document.getElementById('csrf_token').value;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Creando OC…';

    fetch('<?= route_to('suppliers.po.store') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_header() ?>': token
        },
        body: new FormData(form)
    })
    .then(function (r) {
        var nt = r.headers.get('<?= csrf_header() ?>');
        if (nt) document.getElementById('csrf_token').value = nt;
        return r.json();
    })
    .then(function (data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Crear Orden de Compra';
        if (data.success) {
            notifyShow(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalGeneratePO')).hide();
            if (_supplierId) loadOrdersTab(_supplierId);
        } else {
            var errs = data.errors ? Object.values(data.errors).join('<br>') : '';
            notifyShow((data.message || 'Error al crear la OC.') + (errs ? '<br>' + errs : ''), 'danger');
        }
    })
    .catch(function (err) {
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>Crear Orden de Compra';
        notifyShow('Error de conexión.', 'danger');
    });
}

/* ──────────────────────────────────────────────────────────────────────────
   UTILIDADES
────────────────────────────────────────────────────────────────────────── */

/**
 * Renueva el token CSRF global desde los headers de una respuesta Fetch/XHR.
 * §2 — Usado por DataTables (xhr.dt) y por todas las llamadas Fetch.
 */
function refreshCsrfFromHeaders(response) {
    if (!response) return;
    var headerName = '<?= csrf_header() ?>';
    var newToken   = typeof response.getResponseHeader === 'function'
        ? response.getResponseHeader(headerName)
        : response.headers.get(headerName);
    if (newToken && document.getElementById('csrf_token')) {
        document.getElementById('csrf_token').value = newToken;
    }
}

/**
 * Wrapper para Fetch POST simple con CSRF (sin body extra).
 * §1 X-Requested-With, §2 token en cabecera y renovación.
 */
function csrfFetch(url) {
    var token = document.getElementById('csrf_token').value;
    return fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_header() ?>': token
        }
    })
    .then(function (r) {
        refreshCsrfFromHeaders(r);
        return r.json();
    })
    .catch(function (err) {
        console.error(err);
        notifyShow('Error de conexión con el servidor.', 'danger');
        return { success: false, message: 'Error de conexión.' };
    });
}

/** Escapa HTML para prevenir XSS en contenido dinámico */
function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/** Formatea fecha ISO/datetime a DD/MM/YYYY §11 */
function fmtDate(dateStr) {
    if (!dateStr) return '—';
    var d = new Date(String(dateStr).replace(' ', 'T'));
    if (isNaN(d.getTime())) return String(dateStr);
    return d.toLocaleDateString('es-MX', {
        day: '2-digit', month: '2-digit', year: 'numeric'
    });
}

/** Formatea datetime para la bitácora (fecha + hora) */
function fmtDateTime(dateStr) {
    if (!dateStr) return '—';
    var d = new Date(String(dateStr).replace(' ', 'T'));
    if (isNaN(d.getTime())) return String(dateStr);
    return d.toLocaleString('es-MX', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}
</script>

<?php $this->endSection() ?>
