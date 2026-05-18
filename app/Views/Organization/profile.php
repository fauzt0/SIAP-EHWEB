<?php $this->extend($layout); ?>

<?php $this->section('title'); ?>
<?= esc($headTitle) ?>
<?php $this->endSection(); ?>

<?php $this->section('main'); ?>
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

<main class="content">
    <div class="container-fluid p-0">

        <h1 class="h3 mb-3"><?= esc($pageTitle) ?></h1>

        <div class="row">
            <div class="col-md-8 col-xl-9">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Información de la Matriz</h5>
                    </div>
                    <div class="card-body">
                        <form id="profile-form" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?= !empty($response['company']) ? esc($response['company']->id) : '' ?>">
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label" for="commercial_name">Nombre Comercial <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="commercial_name" name="commercial_name" 
                                            value="<?= !empty($response['company']) ? esc($response['company']->commercial_name) : '' ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="legal_name">Razón Social</label>
                                        <input type="text" class="form-control" id="legal_name" name="legal_name" 
                                            value="<?= !empty($response['company']) ? esc($response['company']->legal_name) : '' ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="tax_id">RFC / Tax ID</label>
                                        <input type="text" class="form-control" id="tax_id" name="tax_id" 
                                            value="<?= !empty($response['company']) ? esc($response['company']->tax_id) : '' ?>">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="primary_email">Correo Principal <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="primary_email" name="primary_email" 
                                                value="<?= !empty($response['company']) ? esc($response['company']->primary_email) : '' ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="primary_phone">Teléfono Principal <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="primary_phone" name="primary_phone" 
                                                value="<?= !empty($response['company']) ? esc($response['company']->primary_phone) : '' ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="website_url">Sitio Web</label>
                                        <input type="url" class="form-control" id="website_url" name="website_url" 
                                            value="<?= !empty($response['company']) ? esc($response['company']->website_url) : '' ?>" placeholder="https://">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <?php $logo = !empty($response['company']) && $response['company']->logo_path ? base_url($response['company']->logo_path) : base_url('assets/img/default-company.png'); ?>
                                        <img alt="Logo" src="<?= $logo ?>" class="rounded mx-auto d-block mt-2 mb-2 img-fluid" style="max-height: 150px; object-fit: contain;" id="logo-preview">
                                        <div class="mt-2">
                                            <label for="logo" class="btn btn-primary"><i class="fas fa-upload"></i> Cambiar Logo</label>
                                            <input type="file" id="logo" name="logo" class="d-none" accept="image/*">
                                        </div>
                                        <small class="text-muted d-block mt-2">Formatos permitidos: JPG, PNG, WEBP. Max: 2MB.</small>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <!-- Espacio para botones adicionales como documentación legal -->
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg" id="btn-save">
                                    <i class="fas fa-save me-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-xl-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Información</h5>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">
                            El Perfil de la Compañía representa la entidad matriz. La información introducida aquí aparecerá en facturas y documentos oficiales globales.
                        </p>
                        <hr>
                        <strong>Soporte Técnico</strong><br>
                        Para modificaciones avanzadas o configuración del sistema de facturación electrónica, contacta al administrador del sistema.
                    </div>
                </div>
            </div>
        </div>

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

$('#profile-form').on('submit', function(e) {
    e.preventDefault();
    let formData = new FormData(this);
    let csrfToken = document.getElementById('csrf_token').value;
    formData.append('<?= csrf_token() ?>', csrfToken);

    let btn = $('#btn-save');
    let originalHtml = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...').prop('disabled', true);

    $.ajax({
        url: '<?= base_url("nat/organization/profile/update") ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            btn.html(originalHtml).prop('disabled', false);
            if(res.csrf) document.getElementById('csrf_token').value = res.csrf;
            if(res.success) {
                if(typeof tools !== 'undefined' && tools.showAlert) {
                    tools.showAlert('success', '¡Éxito!', res.message);
                } else {
                    alert(res.message);
                }
            } else {
                if(typeof tools !== 'undefined' && tools.showAlert) {
                    tools.showAlert('error', 'Error', res.message);
                } else {
                    alert(res.message);
                }
            }
        },
        error: function(xhr) {
            btn.html(originalHtml).prop('disabled', false);
            let res = xhr.responseJSON;
            if(res && res.csrf) document.getElementById('csrf_token').value = res.csrf;
            if(typeof tools !== 'undefined' && tools.showAlert) {
                tools.showAlert('error', 'Error', res ? res.message : 'Error de servidor');
            } else {
                alert(res ? res.message : 'Error de servidor');
            }
        }
    });
});
</script>
<?php $this->endSection(); ?>
