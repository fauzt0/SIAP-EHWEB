<!-- BEGIN  Modal Addition Form -->
<div class="modal fade" id="modalAddUser" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Agregar Nuevo Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body m-3">
        <form id="formAddUser">
          <!-- Token CSRF -->
          <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

          <div class="mb-3">
            <label class="form-label">Nombre de Usuario (Username)</label>
            <input type="text" class="form-control form-control-lg" name="username" placeholder="ej. jdoe" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Correo Electrónico</label>
            <input type="email" class="form-control form-control-lg" name="email" placeholder="ej. correo@empresa.com" required>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Nombre(s)</label>
              <input type="text" class="form-control form-control-lg" name="first_name" placeholder="John" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Apellidos</label>
              <input type="text" class="form-control form-control-lg" name="last_name" placeholder="Doe" required>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Contraseña</label>
              <div class="input-group">
                <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Contraseña de acceso" required>
                <button class="btn btn-outline-secondary toggle-password px-3" type="button" tabindex="-1">
                  <i class="align-middle fas fa-fw fa-eye eye-icon"></i>
                </button>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Confirmar Contraseña</label>
              <div class="input-group">
                <input type="password" class="form-control form-control-lg" name="password_confirm" placeholder="Repita la contraseña" required>
                <button class="btn btn-outline-secondary toggle-password px-3" type="button" tabindex="-1">
                  <i class="align-middle fas fa-fw fa-eye eye-icon"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Asignación de Grupo Inicial -->
          <div class="mb-3">
            <label class="form-label">Rol Inicial del Sistema</label>
            <select class="form-select form-select-lg" name="role" required>
              <option value="" selected disabled>Seleccione un rol...</option>
              <option value="editor">Editor (Contenido/Catálogo)</option>
              <option value="seller">Vendedor (Seller)</option>
              <option value="admin">Administrador</option>
              <option value="superadmin">Super Administrador</option>
            </select>
            <small class="form-text text-muted">Añada al usuario a un grupo inicial de trabajo.</small>
          </div>

        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary" id="btnSaveUser">Guardar Usuario</button>
      </div>
    </div>
  </div>
</div>
<!-- END Modal -->

<script>
		document.addEventListener("DOMContentLoaded", function() {
      //validaciones frontend del formulario
      $("#formAddUser").validate({
        ignore: ".ignore, .select2-input",
        focusInvalid: false,
        rules:{
          "username": {
            required: true,
            minlength: 3,
            maxlength: 20
          },
          "email": {
            required: true,
            email: true
          },
          "first_name": {
            required: true,
            minlength: 3,
            maxlength: 50
          },
          "last_name": {
            required: true,
            minlength: 3,
            maxlength: 50
          },
          "password": {
            required: true,
            minlength: 6,
            maxlength: 20
          },
          "password_confirm": {
            required: true,
            equalTo: "#password"
          },
          "role": {
            required: true
          }
        },
        messages: {
          "username": {
            required: "Por favor, ingrese un nombre de usuario",
            minlength: "El nombre de usuario debe tener al menos 3 caracteres",
            maxlength: "El nombre de usuario no puede exceder los 20 caracteres"
          },
          "email": {
            required: "Por favor, ingrese un correo electrónico",
            email: "Por favor, ingrese un correo electrónico válido"
          },
          "first_name": {
            required: "Por favor, ingrese el nombre",
            minlength: "El nombre debe tener al menos 3 caracteres",
            maxlength: "El nombre no puede exceder los 50 caracteres"
          },
          "last_name": {
            required: "Por favor, ingrese los apellidos",
            minlength: "Los apellidos deben tener al menos 3 caracteres",
            maxlength: "Los apellidos no pueden exceder los 50 caracteres"
          },
          "password": {
            required: "Por favor, ingrese una contraseña",
            minlength: "La contraseña debe tener al menos 8 caracteres",
            maxlength: "La contraseña no puede exceder los 20 caracteres"
          },
          "password_confirm": {
            required: "Por favor, confirme la contraseña",
            equalTo: "Las contraseñas no coinciden"
          },
          "role": {
            required: "Por favor, seleccione un rol"
          }
        },
        errorElement: 'div',
        errorPlacement: function(error, element) {
          error.addClass('invalid-feedback');
          if (element.parent('.input-group').length) {
            error.insertAfter(element.parent());
          } else {
            element.closest('.mb-3, .col-md-6').append(error);
          }
        },
        highlight: function(element, errorClass, validClass) {
          $(element).addClass('is-invalid');
        },
        unhighlight: function(element, errorClass, validClass) {
          $(element).removeClass('is-invalid');
        }
      });
      
      // Lógica para mostrar/ocultar contraseña con FontAwesome
      document.querySelectorAll('.toggle-password').forEach(function(btn) {
        btn.addEventListener('click', function() {
          let input = this.previousElementSibling;
          let type = input.getAttribute('type') === 'password' ? 'text' : 'password';
          input.setAttribute('type', type);
          
          let icon = this.querySelector('.eye-icon');
          if (icon) {
            if (type === 'password') {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
          }
          
          if (type === 'text') {
            this.classList.replace('btn-outline-secondary', 'btn-secondary');
          } else {
            this.classList.replace('btn-secondary', 'btn-outline-secondary');
          }
        });
      });
		});
</script>
