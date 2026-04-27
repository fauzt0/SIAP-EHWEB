<?php $this->extend($layout); ?>

<?php $this->section('title');
echo $pageTitle;
$this->endSection(); ?>

<?php $this->section('pageStyles'); ?>
<style>
    /* Estilos para el contenedor del editor */
    #editor-container {
        border-radius: 0.375rem;
    }
</style>
<?php $this->endSection(); ?>

<?php $this->section('main') ?>
<?php
$workerName   = esc($response['worker_name']);
$profileId    = $response['profile_id'];
$templates    = $response['contract_templates'] ?? [];
$contractTypes = $response['contract_types'] ?? [];
$editContent  = $response['edit_content'] ?? '';
$editType     = $response['edit_type'] ?? '';
?>

<input type="hidden" id="csrf_token" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

<main class="content">
  <div class="container-fluid p-0">

    <!-- Encabezado + Breadcrumb -->
    <div class="row mb-2 mb-xl-3">
      <div class="col-auto d-none d-sm-block">
        <h1 class="h3 mb-0"><?= esc($pageTitle) ?></h1>
        <p class="text-muted mb-3">Empleado: <strong><?= $workerName ?></strong></p>
      </div>
      <div class="col-auto ms-auto text-end mt-n1">
        <nav aria-label="breadcrumb">
          <?= isset($breadcrumb) ? $breadcrumb : '' ?>
        </nav>
      </div>
    </div>

    <div class="row">
      <div class="col-12">

        <!-- Botones de Acciones Rápidas -->
        <div class="d-flex justify-content-end gap-2 mb-3">
          <button class="btn btn-outline-secondary" id="btn-load-lft">
            <i class="fas fa-gavel me-1"></i>Cargar Contrato LFT
          </button>
          <button class="btn btn-danger" id="btn-preview-pdf" disabled>
            <i class="fas fa-file-pdf me-1"></i>PDF
          </button>
        </div>

        <div class="card shadow-sm mb-4">
          <div class="card-body">

            <!-- Fila Superior: Selectores -->
            <div class="row g-3 mb-4 border-bottom pb-4">
              <div class="col-md-4">
                <label class="form-label fw-semibold">Tipo de Contrato</label>
                <select id="contract_type" class="form-select">
                  <option value="">Seleccione...</option>
                  <?php foreach ($contractTypes as $ct): ?>
                    <option value="<?= $ct->id ?>"><?= esc($ct->name) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Seleccionar Plantilla</label>
                <select id="template_id" class="form-select">
                  <option value="">Seleccione una plantilla...</option>
                  <?php foreach ($templates as $t): ?>
                    <option value="<?= $t->id ?>"><?= esc($t->name) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Motivo (Opcional)</label>
                <input type="text" id="reason" class="form-control" placeholder="Ej. Renovación anual">
              </div>
            </div>

            <!-- Editor TinyMCE -->
            <div class="mb-4">
              <label class="form-label fw-semibold">Contenido del Contrato (Editable)</label>
              <div>
                <textarea id="editor-container" style="height: 600px;"></textarea>
              </div>
            </div>

            <!-- Footer: Switch y Botones -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center bg-light border rounded p-3">
              <div class="form-check form-switch mb-3 mb-md-0">
                <input class="form-check-input" type="checkbox" id="save_as_template">
                <label class="form-check-label fw-semibold" for="save_as_template">
                  Guardar estos cambios como una nueva plantilla global
                </label>
              </div>
              <div class="d-flex gap-2">
                <a href="<?= route_to('hr.workers') ?>" class="btn btn-secondary">Cancelar</a>
                <button class="btn btn-success fw-bold" id="btn-save-contract">
                  <i class="fas fa-save me-1"></i>Generar y Guardar Contrato
                </button>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>

  </div>
</main>

<!-- Formulario Oculto para Preview PDF -->
<form id="form-preview" action="<?= route_to('hr.contracts.preview_raw') ?>" method="POST" target="_blank" style="display:none;">
  <?= csrf_field() ?>
  <input type="hidden" name="content" id="preview_content">
</form>
<?php $this->endSection(); ?>

<?php $this->section('pageFooterScripts'); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    tinymce.init({
        selector: '#editor-container',
        height: 600,
        menubar: 'file edit view insert format tools table help',
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
        toolbar: 'undo redo | blocks | ' +
        'bold italic underline strikethrough | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'removeformat | table | help',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
        language: 'es_MX', // Opcional si el archivo de idioma no está, caerá a inglés
        setup: function (editor) {
            editor.on('init', function () {
                <?php if (!empty($editContent)): ?>
                editor.setContent(<?= json_encode($editContent) ?>);
                document.getElementById('btn-preview-pdf').disabled = false;
                <?php else: ?>
                editor.setContent('<p class="text-muted fst-italic">← Selecciona una plantilla para cargar el contenido del contrato.</p>');
                <?php endif; ?>
            });
        }
    });

    const profileId = <?= (int)$profileId ?>;
    const initialType = <?= json_encode($editType) ?>;
    
    if (initialType) {
        // Pre-seleccionar tipo de contrato si existe en las opciones
        const sel = document.getElementById('contract_type');
        for (let i = 0; i < sel.options.length; i++) {
            if (sel.options[i].value === initialType) {
                sel.selectedIndex = i;
                break;
            }
        }
    }

    function getCsrf() {
        const el = document.getElementById('csrf_token');
        return el ? el.value : '';
    }

    function updateCsrf(resp) {
        // Obsoleto si no se manda en cabeceras. Mantenemos por compatibilidad.
        const c = resp.headers ? resp.headers.get('<?= csrf_header() ?>') : null;
        if (c) {
            document.querySelectorAll('input[name="<?= csrf_token() ?>"]').forEach(el => el.value = c);
        }
    }

    function renderTemplate(templateId) {
        if (!tinymce.activeEditor) return;
        
        tinymce.activeEditor.setContent('<p class="text-muted"><em>Cargando plantilla...</em></p>');
        tinymce.activeEditor.mode.set('readonly');
        document.getElementById('btn-preview-pdf').disabled = true;

        const fd = new FormData();
        fd.append('profile_id', profileId);
        fd.append('template_id', templateId);
        fd.append('<?= csrf_token() ?>', getCsrf());

        fetch('<?= route_to('hr.contracts.render_template') ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.csrf) {
                document.querySelectorAll('input[name="<?= csrf_token() ?>"]').forEach(el => el.value = data.csrf);
            }
            if (data.success) {
                tinymce.activeEditor.setContent(data.html);
                document.getElementById('btn-preview-pdf').disabled = false;
                if (templateId === 'lft') {
                    tinymce.activeEditor.mode.set('readonly');
                } else {
                    tinymce.activeEditor.mode.set('design');
                }
                if (typeof notifyShow === 'function') notifyShow('Plantilla cargada.', 'success');
            } else {
                tinymce.activeEditor.setContent('<p style="color:red">Error al cargar la plantilla.</p>');
                tinymce.activeEditor.mode.set('design');
                if (typeof notifyShow === 'function') notifyShow(data.message || 'Error', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            tinymce.activeEditor.setContent('<p style="color:red">Error de conexión.</p>');
            tinymce.activeEditor.mode.set('design');
            if (typeof notifyShow === 'function') notifyShow('Error de conexión', 'danger');
        });
    }

    // Evento: cambio de plantilla
    document.getElementById('template_id').addEventListener('change', function() {
        if (this.value) {
            renderTemplate(this.value);
        } else {
            if (tinymce.activeEditor) {
                tinymce.activeEditor.setContent('');
                tinymce.activeEditor.mode.set('design');
            }
            document.getElementById('btn-preview-pdf').disabled = true;
        }
    });

    // Evento: Cargar LFT
    document.getElementById('btn-load-lft').addEventListener('click', function() {
        document.getElementById('template_id').value = '';
        const sel = document.getElementById('contract_type');
        for (let i = 0; i < sel.options.length; i++) {
            if (sel.options[i].text.toLowerCase().includes('indeterminado')) {
                sel.selectedIndex = i;
                break;
            }
        }
        renderTemplate('lft');
    });

    // Evento: Preview PDF
    document.getElementById('btn-preview-pdf').addEventListener('click', function() {
        if (!tinymce.activeEditor) return;
        const html = tinymce.activeEditor.getContent();
        if (!html || html.trim() === '') return;
        document.getElementById('preview_content').value = html;
        document.getElementById('form-preview').submit();
    });

    // Evento: Guardar Contrato
    document.getElementById('btn-save-contract').addEventListener('click', function() {
        if (!tinymce.activeEditor) return;
        const typeId = document.getElementById('contract_type').value;
        const templateId = document.getElementById('template_id').value;
        if (!typeId) {
            if (typeof notifyShow === 'function') notifyShow('Selecciona el Tipo de Contrato.', 'warning');
            return;
        }
        const html = tinymce.activeEditor.getContent();
        if (!html || html.replace(/<[^>]*>/g,'').trim() === '') {
            if (typeof notifyShow === 'function') notifyShow('El contenido del contrato está vacío.', 'warning');
            return;
        }

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';

        const fd = new FormData();
        fd.append('contract_type_id', typeId);
        fd.append('template_id', templateId);
        fd.append('reason', document.getElementById('reason').value);
        fd.append('content', html);
        fd.append('save_as_template', document.getElementById('save_as_template').checked ? 1 : 0);
        fd.append('<?= csrf_token() ?>', getCsrf());

        fetch('<?= route_to('hr.contracts.save_manual', $profileId) ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.csrf) {
                document.querySelectorAll('input[name="<?= csrf_token() ?>"]').forEach(el => el.value = data.csrf);
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i>Generar y Guardar Contrato';
            if (data.success) {
                if (typeof notifyShow === 'function') notifyShow(data.message, 'success');
                setTimeout(() => { window.location.href = '<?= route_to('hr.contracts.history', $profileId) ?>'; }, 1500);
            } else {
                if (typeof notifyShow === 'function') notifyShow(data.message || 'Error', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i>Generar y Guardar Contrato';
            if (typeof notifyShow === 'function') notifyShow('Error de conexión', 'danger');
        });
    });
});
</script>
<?php $this->endSection(); ?>
