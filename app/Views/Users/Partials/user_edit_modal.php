<!-- BEGIN Modal Edición de Usuario -->
<div class="modal fade" id="modalEditUser" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Editar Usuario</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body m-3">
				<form id="formEditUser">
					<!-- Token CSRF -->
					<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token_edit">
					<input type="hidden" name="user_id" id="edit_user_id">

					<div class="mb-3">
						<label class="form-label">Nombre de Usuario (Username)</label>
						<input type="text" class="form-control form-control-lg" name="username" id="edit_username" placeholder="ej. jdoe" required>
					</div>

					<div class="mb-3">
						<label class="form-label">Correo Electrónico</label>
						<input type="email" class="form-control form-control-lg" name="email" id="edit_email" placeholder="ej. correo@empresa.com" required>
					</div>

					<div class="row">
						<div class="col-md-6 mb-3">
							<label class="form-label">Nombre(s)</label>
							<input type="text" class="form-control form-control-lg" name="first_name" id="edit_first_name" placeholder="John" required>
						</div>
						<div class="col-md-6 mb-3">
							<label class="form-label">Apellidos</label>
							<input type="text" class="form-control form-control-lg" name="last_name" id="edit_last_name" placeholder="Doe" required>
						</div>
					</div>

					<!-- Contraseña: siempre visible, vacío = no cambia -->
					<div class="row">
						<div class="col-md-6 mb-3">
							<label class="form-label">Nueva Contraseña</label>
							<div class="input-group">
								<input type="password" class="form-control form-control-lg" id="edit_password" name="password" placeholder="Dejar vacío si no cambia">
								<button class="btn btn-outline-secondary toggle-password-edit px-3" type="button" tabindex="-1">
									<i class="align-middle fas fa-fw fa-eye eye-icon-edit"></i>
								</button>
							</div>
							<small class="form-text text-muted">Dejar vacío para conservar la contraseña actual.</small>
						</div>
						<div class="col-md-6 mb-3">
							<label class="form-label">Confirmar Contraseña</label>
							<div class="input-group">
								<input type="password" class="form-control form-control-lg" name="password_confirm" id="edit_password_confirm" placeholder="Repita la contraseña">
								<button class="btn btn-outline-secondary toggle-password-edit px-3" type="button" tabindex="-1">
									<i class="align-middle fas fa-fw fa-eye eye-icon-edit"></i>
								</button>
							</div>
						</div>
					</div>

					<!-- Rol -->
					<div class="mb-3">
						<label class="form-label">Rol del Sistema</label>
						<select class="form-select form-select-lg" name="role" id="edit_role" required>
							<option value="" disabled>Seleccione un rol...</option>
							<option value="editor">Editor (Contenido/Catálogo)</option>
							<option value="seller">Vendedor (Seller)</option>
							<option value="admin">Administrador</option>
							<option value="superadmin">Super Administrador</option>
						</select>
					</div>

					<div class="mb-3">
						<label class="form-label">Medios de Contacto Adicionales</label>
						<div id="edit-contacts-container">
							<!-- Llenado dinámico por JS -->
						</div>
						<button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btn-add-edit-contact">
							<i class="fas fa-plus"></i> Agregar Contacto
						</button>
					</div>

				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
				<button type="button" class="btn btn-warning" id="btnUpdateUser">Guardar Cambios</button>
			</div>
		</div>
	</div>
</div>
<!-- END Modal Edición -->

<script>
document.addEventListener("DOMContentLoaded", function() {

	// Validación frontend del formulario de edición
	$("#formEditUser").validate({
		ignore: ".ignore, .select2-input",
		focusInvalid: false,
		rules: {
			"username":   { required: true, minlength: 3, maxlength: 20 },
			"email":      { required: true, email: true },
			"first_name": { required: true, minlength: 2, maxlength: 50 },
			"last_name":  { required: true, minlength: 2, maxlength: 50 },
			"password":   { minlength: 8 },
			"password_confirm": { equalTo: "#edit_password" },
			"role":       { required: true }
		},
		messages: {
			"username":         { required: "Ingrese un nombre de usuario", minlength: "Mínimo 3 caracteres" },
			"email":            { required: "Ingrese un correo electrónico", email: "Correo inválido" },
			"first_name":       { required: "Ingrese el nombre" },
			"last_name":        { required: "Ingrese los apellidos" },
			"password":         { minlength: "La contraseña debe tener al menos 8 caracteres" },
			"password_confirm": { equalTo: "Las contraseñas no coinciden" },
			"role":             { required: "Seleccione un rol" }
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
		highlight: function(element) { $(element).addClass('is-invalid'); },
		unhighlight: function(element) { $(element).removeClass('is-invalid'); }
	});

	// Toggle contraseña edición
	document.querySelectorAll('.toggle-password-edit').forEach(function(btn) {
		btn.addEventListener('click', function() {
			let input = this.previousElementSibling;
			let type = input.getAttribute('type') === 'password' ? 'text' : 'password';
			input.setAttribute('type', type);
			let icon = this.querySelector('.eye-icon-edit');
			if (icon) {
				icon.classList.toggle('fa-eye');
				icon.classList.toggle('fa-eye-slash');
			}
			this.classList.toggle('btn-outline-secondary');
			this.classList.toggle('btn-secondary');
		});
	});

	// Lógica de medios de contacto en edición
	const editContactsContainer = document.getElementById('edit-contacts-container');
	const btnAddEditContact = document.getElementById('btn-add-edit-contact');
	
	const getEditSourceOptions = () => {
		const options = [
			'phone_number', 'mobile_number', 'email', 'facebook', 'twitter', 
			'instagram', 'whatsapp', 'skype', 'telegram', 'github', 'linkedin', 'other'
		];
		return options.map(opt => `<option value="${opt}">${opt.charAt(0).toUpperCase() + opt.slice(1).replace('_', ' ')}</option>`).join('');
	};

	if (btnAddEditContact) {
		btnAddEditContact.addEventListener('click', function(e) {
			e.preventDefault();
			const idx = Date.now();
			const row = document.createElement('div');
			row.className = 'row g-2 align-items-center mb-2';
			row.innerHTML = `
				<div class="col-md-4">
					<select name="contacts[${idx}][source]" class="form-select bg-light" required>
						${getEditSourceOptions()}
					</select>
				</div>
				<div class="col-md-7">
					<input type="text" name="contacts[${idx}][value]" class="form-control" placeholder="Usuario, Enlace o Número" required>
				</div>
				<div class="col-md-1 text-end">
					<button type="button" class="btn btn-outline-danger btn-sm p-1 btn-edit-remove-contact" title="Eliminar">
						<i class="fas fa-trash"></i>
					</button>
				</div>
			`;
			editContactsContainer.appendChild(row);
		});
	}

	if (editContactsContainer) {
		editContactsContainer.addEventListener('click', function(e) {
			const btn = e.target.closest('.btn-edit-remove-contact');
			if (btn) btn.closest('.row').remove();
		});
	}

	// Envío AJAX del formulario de edición
	const formEditUser = document.getElementById('formEditUser');
	const btnUpdateUser = document.getElementById('btnUpdateUser');

	if (btnUpdateUser && formEditUser) {
		btnUpdateUser.addEventListener('click', function(e) {
			e.preventDefault();
			if (!$("#formEditUser").valid()) return;

			const userId = document.getElementById('edit_user_id').value;
			btnUpdateUser.disabled = true;
			btnUpdateUser.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Guardando...';

			let formData = new FormData(formEditUser);

			// Construir la URL de actualización dinámicamente
			const updateUrl = '<?= base_url('nat/user/update/') ?>' + userId;

			fetch(updateUrl, {
				method: 'POST',
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
				body: formData
			})
			.then(response => {
				// Actualizar CSRF desde headers
				const newCsrfHash = response.headers.get('<?= csrf_header()?>');
				if (newCsrfHash) {
					document.getElementById('csrf_token_edit').value = newCsrfHash;
					if (document.getElementById('csrf_token')) {
						document.getElementById('csrf_token').value = newCsrfHash;
					}
				}
				return response.json();
			})
			.then(data => {
				if (data.success) {
					notifyShow(data.message, 'success');

					// Cerrar modal
					var modalEl = document.getElementById('modalEditUser');
					var modal = bootstrap.Modal.getInstance(modalEl);
					if (modal) modal.hide();

					// Recargar DataTables
					if (window.usersDataTable) {
						window.usersDataTable.ajax.reload(null, false);
					}
				} else {
					let errString = data.error || 'Revise los campos enviados.';
					if (typeof errString === 'object') {
						errString = Object.values(errString).join('<br>');
					}
					const alertHtml = `
					<div class="alert alert-danger alert-outline alert-dismissible" role="alert">
						<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
						<div class="alert-icon"><i class="far fa-fw fa-bell"></i></div>
						<div class="alert-message">
							<strong>${data.message || 'Error al guardar'}</strong><br>${errString}
						</div>
					</div>`;
					let modalBody = formEditUser.closest('.modal-body');
					let oldAlert = modalBody.querySelector('.alert');
					if (oldAlert) oldAlert.remove();
					modalBody.insertAdjacentHTML('beforeend', alertHtml);
				}
			})
			.catch(error => {
				console.error(error);
				const alertHtml = `
				<div class="alert alert-danger alert-outline alert-dismissible" role="alert">
					<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					<div class="alert-message"><strong>Error</strong><br>Problema de conexión con el servidor.</div>
				</div>`;
				let modalBody = formEditUser.closest('.modal-body');
				let oldAlert = modalBody.querySelector('.alert');
				if (oldAlert) oldAlert.remove();
				modalBody.insertAdjacentHTML('beforeend', alertHtml);
			})
			.finally(() => {
				btnUpdateUser.disabled = false;
				btnUpdateUser.innerHTML = 'Guardar Cambios';
			});
		});
	}
});
</script>
