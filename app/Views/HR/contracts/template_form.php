<?php $this->extend($layout); ?>

<?php $this->section('pageStyles'); ?>
<style>
    .variable-badge {
        cursor: pointer;
        transition: all 0.2s;
    }

    .variable-badge:hover {
        background-color: #3b7ddd !important;
        color: #fff !important;
        transform: scale(1.05);
    }
</style>
<?php $this->endSection(); ?>

<?php $this->section('main') ?>
<input type="hidden" id="csrf_token" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

<main class="content">
    <div class="container-fluid p-0">
        <div class="row mb-3">
            <div class="col-12">
                <?= $breadcrumb ?>
            </div>
        </div>

        <form id="form-template" action="<?= route_to('hr.contracts.templates.save') ?>" method="POST"
            enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $template ? $template->id : '' ?>">

            <div class="row">
                <!-- Columna Principal: Editor -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Contenido Legal del Contrato</h5>
                        </div>
                        <div class="card-body">
                            <!-- Barra de Herramientas Variables -->
                            <div class="mb-3 p-3 bg-light border rounded">
                                <label class="form-label d-block text-muted small fw-bold text-uppercase mb-2">Insertar
                                    Variables Dinámicas</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge bg-secondary variable-badge"
                                        data-var="{{nombre_trabajador}}">Nombre Completo</span>
                                    <span class="badge bg-secondary variable-badge" data-var="{{puesto}}">Puesto</span>
                                    <span class="badge bg-secondary variable-badge"
                                        data-var="{{departamento}}">Departamento</span>
                                    <span class="badge bg-secondary variable-badge" data-var="{{sueldo_mensual}}">Sueldo
                                        Mensual</span>
                                    <span class="badge bg-secondary variable-badge" data-var="{{sueldo_diario}}">Sueldo
                                        Diario</span>
                                    <span class="badge bg-secondary variable-badge" data-var="{{fecha_ingreso}}">Fecha
                                        Ingreso</span>
                                    <span class="badge bg-secondary variable-badge" data-var="{{rfc}}">RFC</span>
                                    <span class="badge bg-secondary variable-badge" data-var="{{curp}}">CURP</span>
                                    <span class="badge bg-secondary variable-badge"
                                        data-var="{{domicilio}}">Domicilio</span>
                                    <span class="badge bg-secondary variable-badge"
                                        data-var="{{nacionalidad}}">Nacionalidad</span>
                                </div>
                                <div class="mt-2 pt-2 border-top">
                                    <button type="button" class="btn btn-sm btn-outline-dark"
                                        id="btn-insert-signatures">
                                        <i class="align-middle me-1" data-lucide="pen-tool"></i> Insertar Bloque de
                                        Firmas
                                    </button>
                                </div>
                            </div>

                            <!-- Editor TinyMCE -->
                            <textarea id="editor-container" style="height: 500px;"></textarea>
                            <input type="hidden" name="content" id="template_content">
                        </div>
                    </div>
                </div>

                <!-- Columna Lateral: Configuración -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Detalles de la Plantilla</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Nombre de la Plantilla <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control"
                                    placeholder="Ej: Contrato de Planta LFT"
                                    value="<?= $template ? esc($template->name) : '' ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Modelo Base</label>
                                <select name="base_model" id="base_model" class="form-select">
                                    <option value="lft" <?= ($template && $template->base_model == 'lft') ? 'selected' : '' ?>>Legal LFT México</option>
                                    <option value="modern" <?= ($template && $template->base_model == 'modern') ? 'selected' : '' ?>>Moderno</option>
                                    <option value="classic" <?= ($template && $template->base_model == 'classic') ? 'selected' : '' ?>>Clásico</option>
                                    <option value="corporate" <?= ($template && $template->base_model == 'corporate') ? 'selected' : '' ?>>Corporativo</option>
                                </select>
                                <div class="form-text">Carga un texto predefinido según el estilo legal.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Descripción Breve</label>
                                <textarea name="description" class="form-control"
                                    rows="2"><?= $template ? esc($template->description) : '' ?></textarea>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_default" id="is_default"
                                        <?= ($template && $template->is_default) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_default">Establecer como
                                        predeterminada</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Logotipo de Cabecera (Opcional)</label>
                                <?php if ($template && !empty($template->header_logo)): ?>
                                    <div class="mb-2">
                                        <img src="<?= base_url($template->header_logo) ?>" alt="Logo actual"
                                            style="max-height: 50px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="header_logo" class="form-control" accept="image/*">
                                <div class="form-text">Imagen que aparecerá en la parte superior del contrato PDF.</div>
                            </div>

                            <hr>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg" id="btn-save-template">
                                    <i class="align-middle me-1" data-lucide="save"></i> Guardar Plantilla
                                </button>
                                <a href="<?= route_to('hr.contracts.templates.index') ?>"
                                    class="btn btn-outline-secondary">Cancelar</a>
                            </div>
                        </div>
                    </div>

                    <!-- Card de Ayuda -->
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <h6><i class="align-middle me-1 text-info" data-lucide="help-circle"></i> Ayuda</h6>
                            <p class="small text-muted mb-0">
                                Utiliza las variables dinámicas para que el sistema rellene automáticamente los datos
                                del trabajador al generar el PDF.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>
<?php $this->endSection() ?>

<?php $this->section('pageFooterScripts'); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const initialContent = `<?= $template ? str_replace('`', '\`', $template->content) : '' ?>`;
        
        tinymce.init({
            selector: '#editor-container',
            height: 500,
            menubar: 'file edit view insert format tools table help',
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
            toolbar: 'undo redo | blocks | ' +
            'bold italic underline strikethrough | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | table | help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
            language: 'es_MX',
            setup: function (editor) {
                editor.on('init', function () {
                    if (initialContent) {
                        editor.setContent(initialContent);
                    }
                });
            }
        });

        // Manejar inserción de variables
        document.querySelectorAll('.variable-badge').forEach(badge => {
            badge.addEventListener('click', function () {
                if (tinymce.activeEditor) {
                    tinymce.activeEditor.insertContent(this.dataset.var);
                }
            });
        });

        // Insertar bloque de firmas
        document.getElementById('btn-insert-signatures').addEventListener('click', function () {
            const signatureHtml = `
            <br><br>
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <tr>
                    <td style="text-align: center; padding: 20px; width: 50%;">
                        <div style="width: 80%; border-bottom: 1px solid #000; margin: 40px auto 10px auto;"></div>
                        <strong>EL PATRÓN</strong><br>
                        {{nombre_empresa}}
                    </td>
                    <td style="text-align: center; padding: 20px; width: 50%;">
                        <div style="width: 80%; border-bottom: 1px solid #000; margin: 40px auto 10px auto;"></div>
                        <strong>EL TRABAJADOR</strong><br>
                        {{nombre_trabajador}}
                    </td>
                </tr>
            </table>
            <br>
            `;
            if (tinymce.activeEditor) {
                tinymce.activeEditor.insertContent(signatureHtml);
            }
        });

        // Manejar envío del formulario
        const form = document.getElementById('form-template');
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            if (tinymce.activeEditor) {
                document.getElementById('template_content').value = tinymce.activeEditor.getContent();
            }

            const btnSave = document.getElementById('btn-save-template');
            btnSave.disabled = true;
            btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

            const formData = new FormData(this);
            formData.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    const newCsrf = response.headers.get('<?= csrf_header() ?>');
                    if (newCsrf) document.getElementById('csrf_token').value = newCsrf;
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        notifyShow(data.message, 'success');
                        if (data.redirect) {
                            setTimeout(() => window.location.href = data.redirect, 1000);
                        }
                    } else {
                        notifyShow(data.message || 'Error al guardar', 'danger');
                        btnSave.disabled = false;
                        btnSave.innerHTML = '<i class="align-middle me-1" data-lucide="save"></i> Guardar Plantilla';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    notifyShow('Error en la petición', 'danger');
                    btnSave.disabled = false;
                    btnSave.innerHTML = '<i class="align-middle me-1" data-lucide="save"></i> Guardar Plantilla';
                });
        });

        // Cargar modelos base legales al cambiar el select
        document.getElementById('base_model').addEventListener('change', function () {
            const type = this.value;
            if (!type) return;

            if (tinymce.activeEditor && tinymce.activeEditor.getContent().length > 15 && !confirm('¿Deseas reemplazar el contenido actual con el modelo base seleccionado?')) {
                return;
            }

            fetch('<?= route_to('hr.contracts.templates.getBaseModel') ?>?type=' + type)
                .then(r => r.json())
                .then(data => {
                    if (data.content && tinymce.activeEditor) {
                        tinymce.activeEditor.setContent(data.content);
                        notifyShow('Modelo base cargado', 'default');
                    }
                });
        });
    });
</script>
<?php $this->endSection(); ?>