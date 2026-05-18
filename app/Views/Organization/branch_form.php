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
                <a href="<?= base_url('nat/organization/branches') ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al listado
                </a>
            </div>
        </div>

        <form id="branch-form" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $response['isEdit'] ? esc($response['branch']->id) : '' ?>">
            <input type="hidden" name="company_id" value="<?= !empty($response['company']) ? $response['company']->id : 1 ?>">

            <div class="row">
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0">Información Básica</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label" for="name">Nombre de la Sucursal <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                        value="<?= $response['isEdit'] ? esc($response['branch']->name) : '' ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="branch_code">Código <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="branch_code" name="branch_code" 
                                        value="<?= $response['isEdit'] ? esc($response['branch']->branch_code) : '' ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="phone">Teléfono</label>
                                    <input type="text" class="form-control" id="phone" name="phone" 
                                        value="<?= $response['isEdit'] ? esc($response['branch']->phone) : '' ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="email">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                        value="<?= $response['isEdit'] ? esc($response['branch']->email) : '' ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="address">Dirección</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?= $response['isEdit'] ? esc($response['branch']->address) : '' ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0">Documentación Legal</h5>
                        </div>
                        <div class="card-body text-center py-5">
                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">Carga de Documentación</h6>
                            <p class="small text-muted mb-3">Aquí podrás cargar constancias de situación fiscal, comprobantes de domicilio y actas constitutivas asociadas a esta sucursal.</p>
                            <button type="button" class="btn btn-outline-primary" disabled title="Módulo en desarrollo">
                                <i class="fas fa-upload"></i> Subir Documentos (Próximamente)
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0">Logo de la Sucursal</h5>
                        </div>
                        <div class="card-body text-center">
                            <?php $logo = ($response['isEdit'] && $response['branch']->logo_path) ? base_url($response['branch']->logo_path) : base_url('bootstrap/img/avatars/avatar.jpg'); ?>
                            <img alt="Logo Sucursal" src="<?= $logo ?>" class="rounded mx-auto d-block mt-2 mb-3 img-fluid" style="max-height: 150px; object-fit: contain;" id="logo-preview">
                            <div>
                                <label for="logo" class="btn btn-outline-primary"><i class="fas fa-image"></i> Seleccionar Logo</label>
                                <input type="file" id="logo" name="logo" class="d-none" accept="image/*">
                            </div>
                            <small class="text-muted d-block mt-2">Visible en facturas y cotizaciones emitidas por esta sucursal.</small>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0">Configuración Adicional</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="is_main" name="is_main" value="1" <?= ($response['isEdit'] && $response['branch']->is_main) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_main">Es la Sucursal Principal</label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="active" name="active" value="1" <?= (!$response['isEdit'] || $response['branch']->active) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="active">Sucursal Activa</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="pos_printer_name">Impresora POS (Tickets)</label>
                                <input type="text" class="form-control" id="pos_printer_name" name="pos_printer_name" 
                                    value="<?= $response['isEdit'] ? esc($response['branch']->pos_printer_name) : '' ?>" placeholder="Ej: EPSON TM-T88V">
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg" id="btn-save">
                            <i class="fas fa-save me-2"></i>Guardar Sucursal
                        </button>
                    </div>
                </div>
            </div>
        </form>

    </div>
</main>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
document.getElementById('logo').addEventListener('change', function(e) {
    if(this.files && this.files[0]) {
        let reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('logo-preview').src = e.target.result;
        }
        reader.readAsDataURL(this.files[0]);
    }
});

$('#branch-form').on('submit', function(e) {
    e.preventDefault();
    let formData = new FormData(this);
    let csrfToken = document.getElementById('csrf_token').value;
    formData.append('<?= csrf_token() ?>', csrfToken);

    let btn = $('#btn-save');
    let originalHtml = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...').prop('disabled', true);

    $.ajax({
        url: '<?= base_url("nat/organization/branch/save") ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            btn.html(originalHtml).prop('disabled', false);
            if(res.csrf) document.getElementById('csrf_token').value = res.csrf;
            if(res.success) {
                if(typeof notifyShow === 'function') {
                    notifyShow(res.message, 'success');
                } else {
                    alert(res.message);
                }
                setTimeout(() => window.location.href = '<?= base_url("nat/organization/branches") ?>', 1500);
            } else {
                if(typeof notifyShow === 'function') {
                    notifyShow(res.message, 'danger');
                } else {
                    alert(res.message);
                }
            }
        },
        error: function(xhr) {
            btn.html(originalHtml).prop('disabled', false);
            let res = xhr.responseJSON;
            if(res && res.csrf) document.getElementById('csrf_token').value = res.csrf;
            if(typeof notifyShow === 'function') {
                notifyShow(res && res.message ? res.message : 'Error de servidor', 'danger');
            } else {
                alert(res && res.message ? res.message : 'Error de servidor');
            }
        }
    });
});
</script>
<?php $this->endSection(); ?>
