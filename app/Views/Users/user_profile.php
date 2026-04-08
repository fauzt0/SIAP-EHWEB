<?php
/**
 * Vista: Perfil del usuario en sesión
 * Permite editar nombre, username, contraseña y avatar
 */
$user      = $response['user'];
$role      = $response['role'];
$avatarUrl = $response['avatarUrl'];
$contacts  = $response['contacts'] ?? [];

$icons = [
  'phone_number'  => 'phone',
  'mobile_number' => 'smartphone',
  'email'         => 'mail',
  'facebook'      => 'facebook',
  'twitter'       => 'twitter',
  'instagram'     => 'instagram',
  'whatsapp'      => 'message-circle',
  'skype'         => 'video',
  'telegram'      => 'send',
  'github'        => 'github',
  'linkedin'      => 'linkedin',
  'other'         => 'link'
];
?>

<?= $this->extend('Layouts/user_loggedin_layout') ?>

<?= $this->section('title') ?>Mi Perfil — <?= esc($user->first_name . ' ' . $user->last_name) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<main class="content">
  <div class="container-fluid p-0">

    <div class="mb-3">
      <h1 class="h3 d-inline align-middle">Mi Perfil</h1>
    </div>

    <!-- Alertas de éxito / errores -->
    <?php if (session()->getFlashdata('exito')): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="align-middle me-1" data-lucide="check-circle"></i>
        <?= esc(session()->getFlashdata('exito')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('errors')): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="align-middle me-1" data-lucide="alert-circle"></i>
        <strong>Revisa los siguientes campos:</strong>
        <ul class="mb-0 mt-1">
          <?php foreach (session()->getFlashdata('errors') as $error): ?>
            <li><?= esc($error) ?></li>
          <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="row">

      <!-- Columna izquierda: Tarjeta de info del usuario -->
      <div class="col-md-4 col-xl-3">
        <div class="card mb-3">
          <div class="card-body text-center pt-4">

            <!-- Avatar con preview de cambio -->
            <div class="position-relative d-inline-block mb-3">
              <img id="avatar-preview"
                   src="<?= $avatarUrl ?>"
                   alt="<?= esc($user->first_name) ?>"
                   class="rounded-circle img-fluid"
                   style="width: 120px; height: 120px; object-fit: cover;">
              <label for="avatar-input"
                     class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                     style="width: 32px; height: 32px; cursor: pointer;" title="Cambiar foto">
                <i data-lucide="camera" style="width:16px;height:16px;"></i>
              </label>
            </div>

            <h5 class="card-title mb-0"><?= esc($user->first_name . ' ' . $user->last_name) ?></h5>
            <div class="text-muted small mb-3">@<?= esc($user->username) ?></div>

            <span class="badge bg-primary-subtle text-primary fs-6 px-3 py-2">
              <i class="align-middle me-1" data-lucide="shield" style="width:14px;height:14px;"></i>
              <?= esc($role) ?>
            </span>

            <hr class="my-3">

            <!-- Info adicional solo lectura -->
            <div class="text-start small text-muted">
              <div class="d-flex align-items-center mb-2">
                <i class="align-middle me-2" data-lucide="mail" style="width:14px;height:14px;"></i>
                <?= esc($user->email ?? 'Sin correo') ?>
              </div>
              <div class="d-flex align-items-center mb-2">
                <i class="align-middle me-2" data-lucide="calendar" style="width:14px;height:14px;"></i>
                Miembro desde: <?= is_object($user->created_at) ? $user->created_at->format('d/m/Y') : ($user->created_at ? date('d/m/Y', strtotime($user->created_at)) : 'N/A') ?>
              </div>
            </div>

            <?php if(!empty($contacts)): ?>
            <hr class="my-3">
            <div class="text-start">
              <h5 class="card-title fw-bold fs-6 mb-3">Redes y Contacto</h5>
              <ul class="list-unstyled mb-0">
                <?php foreach($contacts as $contact): 
                    $source = $contact['contact_source'];
                    $icon = $icons[$source] ?? 'link';
                ?>
                <li class="mb-2 text-muted">
                  <i data-lucide="<?= $icon ?>" class="align-middle me-2" style="width: 14px; height: 14px;"></i>
                  <span class="small align-middle"><?= esc($contact['contact_value']) ?></span>
                </li>
                <?php endforeach; ?>
              </ul>
            </div>
            <?php endif; ?>

          </div>
        </div>
      </div>

      <!-- Columna derecha: Formulario de edición -->
      <div class="col-md-8 col-xl-9">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="align-middle me-2" data-lucide="edit-3"></i>
              Editar Información
            </h5>
          </div>
          <div class="card-body">

            <form action="<?= route_to('user.profile_save') ?>" method="POST" enctype="multipart/form-data" id="profile-form">
              <?= csrf_field() ?>

              <!-- Input de avatar (oculto, se activa desde el botón de la tarjeta) -->
              <input type="file" id="avatar-input" name="avatar" class="d-none" accept="image/png,image/jpg,image/jpeg,image/gif">

              <div class="row g-3">

                <div class="col-md-6">
                  <label for="first_name" class="form-label">Nombre <span class="text-danger">*</span></label>
                  <input type="text"
                         class="form-control <?= (session()->getFlashdata('errors') && isset(session()->getFlashdata('errors')['first_name'])) ? 'is-invalid' : '' ?>"
                         id="first_name" name="first_name"
                         value="<?= old('first_name', esc($user->first_name)) ?>"
                         required>
                </div>

                <div class="col-md-6">
                  <label for="last_name" class="form-label">Apellido <span class="text-danger">*</span></label>
                  <input type="text"
                         class="form-control"
                         id="last_name" name="last_name"
                         value="<?= old('last_name', esc($user->last_name)) ?>"
                         required>
                </div>

                <div class="col-md-6">
                  <label for="username" class="form-label">Nombre de usuario <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text">@</span>
                    <input type="text"
                           class="form-control"
                           id="username" name="username"
                           value="<?= old('username', esc($user->username)) ?>"
                           required minlength="3" maxlength="30">
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Correo electrónico</label>
                  <input type="email" class="form-control" value="<?= esc($user->email ?? '') ?>" disabled>
                  <div class="form-text text-muted">
                    <i data-lucide="info" style="width:12px;height:12px;"></i>
                    El correo no se puede cambiar desde aquí.
                  </div>
                </div>

                <div class="col-md-6">
                  <label for="password" class="form-label">Nueva contraseña</label>
                  <input type="password" class="form-control" id="password" name="password"
                         placeholder="Dejar vacío para no cambiar" minlength="8">
                  <div class="form-text">Mínimo 8 caracteres.</div>
                </div>

                <div class="col-md-6">
                  <label for="password_confirm" class="form-label">Confirmar nueva contraseña</label>
                  <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                         placeholder="Repetir nueva contraseña">
                </div>

                <div class="col-12">
                  <label class="form-label">Rol actual</label>
                  <input type="text" class="form-control" value="<?= esc($role) ?>" disabled>
                  <div class="form-text text-muted">El rol es asignado por un administrador.</div>
                </div>

              </div><!-- /row -->

              <div class="mt-4">
                <h5 class="card-title mb-3">
                  <i class="align-middle me-2 text-primary" data-lucide="share-2"></i>
                  Medios de Contacto y Redes Sociales
                </h5>
                <div id="contacts-container">
                  <?php if(empty($contacts)): ?>
                    <!-- Formulario vacío de muestra si no hay contactos -->
                  <?php else: ?>
                    <?php foreach($contacts as $idx => $c): ?>
                    <div class="row g-2 align-items-center mb-2 contact-row">
                      <div class="col-md-4">
                        <select name="contacts[<?= $idx ?>][source]" class="form-select bg-light" required>
                          <?php foreach($icons as $key => $icon): ?>
                          <option value="<?= $key ?>" <?= $key === $c['contact_source'] ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $key)) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-7">
                        <input type="text" name="contacts[<?= $idx ?>][value]" class="form-control" value="<?= esc($c['contact_value']) ?>" placeholder="Usuario, Enlace o Número" required>
                      </div>
                      <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle p-1 btn-remove-contact" title="Eliminar">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
                
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btn-add-contact">
                  <i class="align-middle me-1" data-lucide="plus"></i> Agregar Contacto
                </button>
              </div>

              <hr class="my-4">

              <div class="d-flex gap-2 justify-content-end">
                <a href="<?= route_to('dashboard.index') ?>" class="btn btn-outline-secondary">
                  <i class="align-middle me-1" data-lucide="x"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary" id="btn-save-profile">
                  <i class="align-middle me-1" data-lucide="save"></i> Guardar Cambios
                </button>
              </div>

            </form>

          </div>
        </div>
      </div><!-- /col derecha -->

    </div><!-- /row -->
  </div><!-- /container -->
</main>
<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
  #avatar-preview {
    border: 3px solid var(--bs-border-color);
    transition: opacity 0.2s;
  }
  #avatar-preview:hover { opacity: 0.85; }
  label[for="avatar-input"] {
    transition: transform 0.2s, box-shadow 0.2s;
  }
  label[for="avatar-input"]:hover {
    transform: scale(1.1);
    box-shadow: 0 2px 8px rgba(0,0,0,.25);
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('pageHeaderScripts') ?>
<!-- Script en head para aplicar tema guardado antes del render -->
<script>
  (function() {
    var t = localStorage.getItem('appstack-theme');
    if (t) document.documentElement.setAttribute('data-bs-theme', t);
  })();
</script>
<?= $this->endSection() ?>

<?= $this->section('pageFooterScripts') ?>
<script>
// Preview inmediato del avatar al seleccionar archivo
document.getElementById('avatar-input')?.addEventListener('change', function() {
  var file = this.files[0];
  if (file) {
    var reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('avatar-preview').src = e.target.result;
    };
    reader.readAsDataURL(file);
  }
});

// Lógica de medios de contacto dinámicos
(function() {
  const container = document.getElementById('contacts-container');
  const btnAdd    = document.getElementById('btn-add-contact');
  // Usamos una estampa de tiempo o contador para los indices dinámicos
  let contactIndex = <?= count($contacts) ?>;

  const getSourceOptions = () => {
    const options = [
      'phone_number', 'mobile_number', 'email', 'facebook', 'twitter', 
      'instagram', 'whatsapp', 'skype', 'telegram', 'github', 'linkedin', 'other'
    ];
    return options.map(opt => `<option value="${opt}">${opt.charAt(0).toUpperCase() + opt.slice(1).replace('_', ' ')}</option>`).join('');
  };

  if(btnAdd) {
    btnAdd.addEventListener('click', function(e) {
      e.preventDefault();
      const row = document.createElement('div');
      row.className = 'row g-2 align-items-center mb-2 contact-row';
      row.innerHTML = `
        <div class="col-md-4">
          <select name="contacts[new_${contactIndex}][source]" class="form-select bg-light" required>
            ${getSourceOptions()}
          </select>
        </div>
        <div class="col-md-7">
          <input type="text" name="contacts[new_${contactIndex}][value]" class="form-control" placeholder="Usuario, Enlace o Número" required>
        </div>
        <div class="col-md-1 text-end">
          <button type="button" class="btn btn-outline-danger btn-sm rounded-circle p-1 btn-remove-contact" title="Eliminar">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      `;
      container.appendChild(row);
      contactIndex++;
    });
  }

  // Delegación de eventos para botón eliminar (incluso en agregados dinámicamente)
  if(container) {
    container.addEventListener('click', function(e) {
      const btn = e.target.closest('.btn-remove-contact');
      if(btn) {
        btn.closest('.contact-row').remove();
      }
    });
  }
})();
</script>
<?= $this->endSection() ?>
