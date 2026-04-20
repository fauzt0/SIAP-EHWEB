<?php $this->extend($layout); ?>

<?php $this->section('title');
echo $pageTitle;
$this->endSection(); ?>

<?php $this->section('pageStyles'); ?>
<style>
  .permission-module {
    background-color: #f8f9fa;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid #dee2e6;
  }
  .module-title {
    text-transform: uppercase;
    font-size: 14px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 8px;
  }
  .permission-item {
    padding: 6px 10px 6px 2.5em;
    margin-bottom: 4px;
    border-radius: 6px;
    transition: all 0.3s ease;
    border: 1px solid transparent;
  }
  .permission-item:hover {
    background-color: #f0f7ff;
    box-shadow: 0 0 8px rgba(0, 123, 255, 0.4);
    border-color: rgba(0, 123, 255, 0.2);
    transform: translateX(4px);
  }
  .permission-item label {
    cursor: pointer;
    width: 100%;
    margin-left: 8px;
  }
  .permission-item.disabled-item:hover {
    background-color: transparent;
    box-shadow: none;
    border-color: transparent;
    transform: none;
  }
  .badge-inherited {
    font-size: 0.7em;
    margin-left: 8px;
  }
</style>
<?php $this->endSection(); ?>

<?php $this->section('main') ?>
<main class="content">
  <div class="container-fluid p-0">

    <div class="row mb-2 mb-xl-3">
      <div class="col-auto d-none d-sm-block">
        <h1 class="h3 mb-3"><strong>Permisos</strong> <?= esc($response['first_name'] . ' ' . $response['last_name']) ?> (<?= esc($response['username']) ?>)</h1>
      </div>
      <div class="col-auto ms-auto text-end mt-n1">
        <nav aria-label="breadcrumb">
          <?= isset($breadcrumb) ? $breadcrumb : '' ?>
        </nav>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Gestión de Permisos Adicionales</h5>
            <div class="text-muted mt-2">
              <span class="fw-bold text-dark">Rol actual:</span> 
              <?php if (!empty($userRolesTitles)): ?>
                <?php foreach($userRolesTitles as $roleTitle): ?>
                  <span class="badge bg-primary ms-1"><?= esc($roleTitle) ?></span>
                <?php endforeach; ?>
              <?php else: ?>
                <span class="badge bg-danger ms-1">Sin Rol Asignado</span>
              <?php endif; ?>
            </div>
            <div class="text-muted mt-2 small">
              Los permisos marcados con <span class="badge bg-secondary badge-inherited mx-1"><i data-lucide="lock" class="lucide-sm"></i> Heredado del Rol</span> son otorgados automáticamente por el perfil del usuario y no pueden ser revocados desde esta pantalla. Solo puedes asignar permisos <strong>Adicionales</strong> (no heredados).
            </div>
          </div>
          <div class="card-body mt-3">
            
            <form action="<?= route_to('user.permissions_save', $response['userId']) ?>" method="POST" id="form-permissions">
              <?= csrf_field() ?>
              
              <div class="row">
                <?php if(empty($groupedPermissions)): ?>
                  <div class="col-12 text-center text-muted">No hay permisos definidos en el sistema.</div>
                <?php else: ?>
                  
                  <?php foreach ($groupedPermissions as $module => $permissions): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                      <div class="permission-module h-100 shadow-sm transition-hover">
                        <div class="module-title d-flex align-items-center">
                          <i data-lucide="folder" class="me-2 text-primary"></i> <?= esc(strtoupper($module)) ?>
                        </div>
                        
                        <?php foreach($permissions as $perm): ?>
                          <div class="form-check form-switch permission-item py-1 <?= $perm['is_group'] ? 'disabled-item' : '' ?>">
                            <input class="form-check-input" type="checkbox" 
                                   name="permissions[]" 
                                   value="<?= esc($perm['key']) ?>" 
                                   id="perm_<?= esc(str_replace('.', '_', $perm['key'])) ?>"
                                   <?= $perm['is_group'] || $perm['is_personal'] ? 'checked' : '' ?>
                                   <?= $perm['is_group'] ? 'disabled' : '' ?>>
                            
                            <label class="form-check-label ms-1 <?= $perm['is_group'] ? 'text-muted' : '' ?>" for="perm_<?= esc(str_replace('.', '_', $perm['key'])) ?>">
                              <?= esc($perm['description']) ?>
                              <?php if($perm['is_group']): ?>
                                <span class="badge bg-secondary badge-inherited" title="Este permiso está incluido en su rol"><i data-lucide="lock" class="lucide-sm"></i> Heredado</span>
                              <?php endif; ?>
                            </label>
                            
                            <?php if($perm['is_group']): ?>
                              <!-- Hidden input para asegurar que si se enviaran todos los datos en un futuro, el heredado siga estando allí. 
                                  Aunque nuestra lógica en el controlador ignora los heredados de la suma de sync, no hace daño. -->
                            <?php endif; ?>
                          </div>
                        <?php endforeach; ?>
                        
                      </div>
                    </div>
                  <?php endforeach; ?>

                <?php endif; ?>
              </div>

              <div class="mt-4 border-top pt-3 text-end">
                <a href="<?= route_to('users.list') ?>" class="btn btn-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-primary" id="btn-save-permissions"><i data-lucide="save"></i> Guardar Permisos</button>
              </div>

            </form>

          </div>
        </div>
      </div>
    </div>

  </div>
</main>
<?php $this->endSection() ?>

<?php $this->section('pageFooterScripts'); ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    
    // Si queremos prevenir submits múltiples
    const form = document.getElementById('form-permissions');
    const btnSubmit = document.getElementById('btn-save-permissions');
    
    if (form && btnSubmit) {
      form.addEventListener('submit', function() {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
      });
    }
  });
</script>
<?php $this->endSection(); ?>
