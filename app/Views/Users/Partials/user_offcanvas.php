<!-- Offcanvas: Detalle de Usuario (cargado dinámicamente por AJAX) -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasUser" aria-labelledby="offcanvasUserLabel" style="width: 420px;">
	<div class="offcanvas-header border-bottom">
		<h5 class="offcanvas-title" id="offcanvasUserLabel">Detalle del Usuario</h5>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body" id="offcanvasUserBody">
		<!-- Spinner de carga inicial -->
		<div id="offcanvas-loading" class="text-center py-5">
			<div class="spinner-border text-primary" role="status">
				<span class="visually-hidden">Cargando...</span>
			</div>
			<p class="text-muted mt-2">Cargando datos del usuario…</p>
		</div>

		<!-- Contenido dinámico (oculto hasta que se cargue) -->
		<div id="offcanvas-content" style="display:none;">
			<!-- Avatar + Nombre + Rol -->
			<div class="text-center mb-3">
				<img id="oc-avatar" src="" width="96" height="96" class="rounded-circle mb-2" style="object-fit: cover;" alt="Avatar">
				<h4 id="oc-fullname" class="mb-1"></h4>
				<span id="oc-role-badge" class="badge bg-primary"></span>
				<span id="oc-status-badge" class="badge ms-1"></span>
			</div>

			<!-- Datos del usuario -->
			<table class="table table-sm my-3">
				<tbody>
					<tr>
						<th>Username</th>
						<td id="oc-username"></td>
					</tr>
					<tr>
						<th>Email</th>
						<td id="oc-email"></td>
					</tr>
					<tr>
						<th>Rol</th>
						<td id="oc-role"></td>
					</tr>
					<tr>
						<th>Estatus</th>
						<td id="oc-status"></td>
					</tr>
					<tr>
						<th>Creado</th>
						<td id="oc-created"></td>
					</tr>
				</tbody>
			</table>

			<div id="oc-contacts-container" style="display:none;" class="mb-3">
				<strong>Medios de Contacto</strong>
				<ul class="list-unstyled mt-2 mb-0" id="oc-contacts-list">
					<!-- Se llena dinámicamente desde JS -->
				</ul>
			</div>

			<!-- Botones de acción (contextuales según estatus del usuario) -->
			<div class="d-grid gap-2 mb-3" id="oc-actions">
				<!-- Se llena dinámicamente desde JS -->
			</div>

			<hr>

			<!-- Timeline de actividad reciente -->
			<div class="d-flex justify-content-between align-items-center mb-2">
				<strong>Actividad Reciente</strong>
				<a href="#" id="oc-activity-link" class="text-sm" style="display:none;">Ver todos los movimientos</a>
			</div>
			<ul class="timeline mt-2 mb-0" id="oc-timeline">
				<!-- Se llena dinámicamente desde JS -->
			</ul>
			<p id="oc-no-activity" class="text-muted text-center mt-2" style="display:none;">
				Sin actividad registrada.
			</p>
		</div>
	</div>
</div>

<script>
/**
 * loadUserOffcanvas(userId)
 * Carga los datos del usuario desde show_ajax y los renderiza en el offcanvas.
 */
function loadUserOffcanvas(userId) {
	const offcanvasEl = document.getElementById('offcanvasUser');
	const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
	offcanvas.show();

	// Mostrar loading, ocultar contenido
	document.getElementById('offcanvas-loading').style.display = 'block';
	document.getElementById('offcanvas-content').style.display = 'none';

	// Guardamos el userId actual en el offcanvas para usarlo en el botón Editar
	offcanvasEl.dataset.currentUserId = userId;

	fetch('<?= route_to('user.show_ajax', 0) ?>'.replace('/0', '/' + userId), {
		headers: { 'X-Requested-With': 'XMLHttpRequest' }
	})
	.then(response => response.json())
	.then(data => {
		if (!data.success) {
			document.getElementById('offcanvas-loading').innerHTML =
				'<div class="alert alert-danger">' + (data.message || 'Error al cargar') + '</div>';
			return;
		}

		const u = data.response;

		// Rellenamos los campos
		document.getElementById('oc-avatar').src = u.avatar;
		document.getElementById('oc-fullname').textContent = u.first_name + ' ' + u.last_name;
		document.getElementById('oc-role-badge').textContent = u.role;
		document.getElementById('oc-status-badge').textContent = u.status;
		document.getElementById('oc-status-badge').className = 'badge ms-1 bg-' + u.statusClass;
		document.getElementById('oc-username').textContent = u.username;
		document.getElementById('oc-email').textContent = u.email;
		document.getElementById('oc-role').textContent = u.role;
		document.getElementById('oc-status').innerHTML = '<span class="badge badge-subtle-' + u.statusClass + '">' + u.status + '</span>';
		document.getElementById('oc-created').textContent = u.created_at ?? 'N/A';

		// Botones contextuales según el estatus del usuario
		const actionsEl = document.getElementById('oc-actions');
		if (u.statusClass === 'danger') {
			// Usuario eliminado: solo botón de restaurar
			let restoreHtml = '';
			<?php if (auth()->user()->can('users.edit')): ?>
			restoreHtml = `
				<button class="btn btn-success" onclick="restoreUser(${u.id})">
					<i class="fas fa-fw fa-undo"></i> Restaurar Usuario
				</button>`;
			<?php endif; ?>
			actionsEl.innerHTML = restoreHtml;
		} else {
			// Usuario activo: Editar y Permisos
			let activeHtml = '';
			<?php if (auth()->user()->can('users.edit')): ?>
			activeHtml += `
				<button class="btn btn-outline-warning" onclick="openEditModal()">
					<i class="fas fa-fw fa-edit"></i> Editar Usuario
				</button>`;
			<?php endif; ?>
			<?php if (auth()->user()->can('admin.manage-users')): ?>
			activeHtml += `
				<a href="<?= route_to('user.permissions', 0) ?>".replace('/0', '/' + u.id)
				   class="btn btn-outline-info" id="oc-permissions-link">
					<i class="fas fa-fw fa-shield-alt"></i> Editar Permisos
				</a>`;
			<?php endif; ?>
			
			actionsEl.innerHTML = activeHtml;

			<?php if (auth()->user()->can('admin.manage-users')): ?>
			// Insertamos el href dinámicamente después de que el DOM exista
			setTimeout(function() {
				const permBtn = document.getElementById('oc-permissions-link');
				if (permBtn) permBtn.href = '<?= route_to('user.permissions', 0) ?>'.replace('/0', '/' + u.id);
			}, 0);
			<?php endif; ?>
		}

		// Contactos
		const contactsContainer = document.getElementById('oc-contacts-container');
		const contactsList = document.getElementById('oc-contacts-list');
		contactsList.innerHTML = '';
		if(u.contacts && u.contacts.length > 0) {
			contactsContainer.style.display = 'block';
			u.contacts.forEach(function(c) {
				const li = document.createElement('li');
				li.className = 'mb-2 small d-flex align-items-center';
				
				const source = c.contact_source;
				const iconsMap = {
					'phone_number': 'fas fa-phone',
					'mobile_number': 'fas fa-mobile-alt',
					'email': 'fas fa-envelope',
					'facebook': 'fab fa-facebook',
					'twitter': 'fab fa-twitter',
					'instagram': 'fab fa-instagram',
					'whatsapp': 'fab fa-whatsapp',
					'skype': 'fab fa-skype',
					'telegram': 'fab fa-telegram-plane',
					'github': 'fab fa-github',
					'linkedin': 'fab fa-linkedin'
				};
				const iconClass = iconsMap[source] || 'fas fa-external-link-alt';
				
				li.innerHTML = `<i class="${iconClass} fa-fw align-middle me-2 text-primary" style="font-size: 0.85rem;"></i> 
							   <span class="align-middle">${c.contact_value}</span>`;
				contactsList.appendChild(li);
			});
		} else {
			contactsContainer.style.display = 'none';
		}

		// Timeline de actividad
		const timeline = document.getElementById('oc-timeline');
		const noActivity = document.getElementById('oc-no-activity');
        const activityLink = document.getElementById('oc-activity-link');
		timeline.innerHTML = '';

        if (activityLink) {
            activityLink.href = '<?= route_to('user.activity', 0) ?>'.replace('/0', '/' + u.id);
            activityLink.style.display = 'block';
        }


		if (u.activity && u.activity.length > 0) {
			noActivity.style.display = 'none';
			u.activity.forEach(function(log) {
				// Mapeo de iconos por tipo de actividad
				let typeLabel = log.type.replace(/_/g, ' ');
				typeLabel = typeLabel.charAt(0).toUpperCase() + typeLabel.slice(1);

				const li = document.createElement('li');
				li.className = 'timeline-item';
				li.innerHTML =
					'<strong>' + typeLabel + '</strong>' +
					'<span class="float-end text-muted text-sm">' + (log.created_at || '') + '</span>' +
					'<p class="mb-0">' + log.description + '</p>' +
					'<small class="text-muted">IP: ' + (log.ip_address || 'N/A') + '</small>';
				timeline.appendChild(li);
			});
		} else {
			noActivity.style.display = 'block';
		}

		// Ocultar spinner, mostrar contenido
		document.getElementById('offcanvas-loading').style.display = 'none';
		document.getElementById('offcanvas-content').style.display = 'block';

		// Reinicializar iconos Lucide en el offcanvas
		if (typeof lucide !== 'undefined') { lucide.createIcons(); }
	})
	.catch(error => {
		console.error('Error en show_ajax:', error);
		document.getElementById('offcanvas-loading').innerHTML =
			'<div class="alert alert-danger">Error de conexión con el servidor.</div>';
	});
}

/**
 * Abre el modal de edición prellenando los datos del usuario actualmente visible en el offcanvas.
 */
function openEditModal() {
	const offcanvasEl = document.getElementById('offcanvasUser');
	const userId = offcanvasEl.dataset.currentUserId;
	if (!userId) return;

	// Cerramos el offcanvas
	const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
	if (offcanvas) offcanvas.hide();

	// Cargamos datos del usuario para prellenar el modal de edición
	fetch('<?= route_to('user.show_ajax', 0) ?>'.replace('/0', '/' + userId), {
		headers: { 'X-Requested-With': 'XMLHttpRequest' }
	})
	.then(r => r.json())
	.then(data => {
		if (!data.success) return;
		const u = data.response;

		// Prellenar el modal de edición
		document.getElementById('edit_user_id').value = u.id;
		document.getElementById('edit_username').value = u.username;
		document.getElementById('edit_first_name').value = u.first_name;
		document.getElementById('edit_last_name').value = u.last_name;
		document.getElementById('edit_email').value = u.email;
		document.getElementById('edit_role').value = u.roleKey;
		document.getElementById('edit_password').value = '';
		document.getElementById('edit_password_confirm').value = '';
		
		document.getElementById('edit-avatar-preview').src = u.avatar;
		document.getElementById('edit-avatar-input').value = ''; // Limpiar caché file input

		// Prellenar contactos en el modal de edición
		const editContactsContainer = document.getElementById('edit-contacts-container');
		if (editContactsContainer) {
			editContactsContainer.innerHTML = '';
			if (u.contacts && u.contacts.length > 0) {
				const getEditSourceOptions = () => {
					const options = [
						'phone_number', 'mobile_number', 'email', 'facebook', 'twitter', 
						'instagram', 'whatsapp', 'skype', 'telegram', 'github', 'linkedin', 'other'
					];
					return options;
				};
				const optionsList = getEditSourceOptions();

				u.contacts.forEach((c, idx) => {
					const row = document.createElement('div');
					row.className = 'row g-2 align-items-center mb-2';
					
					let optionsHtml = '';
					optionsList.forEach(opt => {
					    const selected = (opt === c.contact_source) ? 'selected' : '';
					    const label = opt.charAt(0).toUpperCase() + opt.slice(1).replace('_', ' ');
						optionsHtml += `<option value="${opt}" ${selected}>${label}</option>`;
					});

					row.innerHTML = `
						<div class="col-md-4">
							<select name="contacts[${idx}][source]" class="form-select bg-light" required>
								${optionsHtml}
							</select>
						</div>
						<div class="col-md-7">
							<input type="text" name="contacts[${idx}][value]" class="form-control" value="${c.contact_value}" placeholder="Usuario, Enlace o Número" required>
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
		}

		// Abrir modal
		const editModal = new bootstrap.Modal(document.getElementById('modalEditUser'));
		editModal.show();
	});
}
/**
 * restoreUser(userId)
 * Llama al endpoint restore por AJAX y recarga el DataTable.
 */
function restoreUser(userId) {
  if (!confirm('¿Restaurar al usuario? Volverá a estar activo en el sistema.')) return;

  fetch('<?= base_url('nat/user/restore/') ?>' + userId, {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: '<?= csrf_token() ?>=' + (document.getElementById('csrf_token') ? document.getElementById('csrf_token').value : '')
  })
  .then(r => {
    const newCsrf = r.headers.get('<?= csrf_header()?>');
    if (newCsrf && document.getElementById('csrf_token')) {
      document.getElementById('csrf_token').value = newCsrf;
    }
    return r.json();
  })
  .then(data => {
    if (data.success) {
      // Cerrar offcanvas
      const offcanvasEl = document.getElementById('offcanvasUser');
      const oc = bootstrap.Offcanvas.getInstance(offcanvasEl);
      if (oc) oc.hide();

      if (typeof notifyShow === 'function') notifyShow(data.message, 'success');
      if (window.usersDataTable) window.usersDataTable.draw();
    } else {
      if (typeof notifyShow === 'function') notifyShow(data.message || 'Error al restaurar', 'danger');
    }
  })
  .catch(err => {
    console.error(err);
    if (typeof notifyShow === 'function') notifyShow('Error de conexión con el servidor', 'danger');
  });
}
</script>
