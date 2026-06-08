<nav class="navbar navbar-expand navbar-bg">
	<a class="sidebar-toggle">
		<i class="hamburger align-self-center"></i>
	</a>

	<form class="d-none d-sm-inline-block">
		<div class="input-group input-group-navbar">
			<input type="text" class="form-control" placeholder="Search projects…" aria-label="Search">
			<button class="btn" type="button">
				<i class="align-middle" data-lucide="search"></i>
			</button>
		</div>
	</form>

	<ul class="navbar-nav">
		<li class="nav-item px-2 dropdown d-none d-sm-inline-block">
			<a class="nav-link dropdown-toggle" href="#" id="servicesDropdown" role="button" data-bs-toggle="dropdown"
				aria-haspopup="true" aria-expanded="false">
				Mega menu
			</a>
			<div class="dropdown-menu dropdown-menu-start dropdown-mega" aria-labelledby="servicesDropdown">
				<div class="d-md-flex align-items-start justify-content-start">
					<div class="dropdown-mega-list">
						<div class="dropdown-header">UI Elements</div>
						<a class="dropdown-item" href="#">Alerts</a>
						<a class="dropdown-item" href="#">Buttons</a>
						<a class="dropdown-item" href="#">Cards</a>
						<a class="dropdown-item" href="#">Carousel</a>
						<a class="dropdown-item" href="#">General</a>
						<a class="dropdown-item" href="#">Grid</a>
						<a class="dropdown-item" href="#">Modals</a>
						<a class="dropdown-item" href="#">Tabs</a>
						<a class="dropdown-item" href="#">Typography</a>
					</div>
					<div class="dropdown-mega-list">
						<div class="dropdown-header">Forms</div>
						<a class="dropdown-item" href="#">Layouts</a>
						<a class="dropdown-item" href="#">Basic Inputs</a>
						<a class="dropdown-item" href="#">Input Groups</a>
						<a class="dropdown-item" href="#">Advanced Inputs</a>
						<a class="dropdown-item" href="#">Editors</a>
						<a class="dropdown-item" href="#">Validation</a>
						<a class="dropdown-item" href="#">Wizard</a>
					</div>
					<div class="dropdown-mega-list">
						<div class="dropdown-header">Tables</div>
						<a class="dropdown-item" href="#">Basic Tables</a>
						<a class="dropdown-item" href="#">Responsive Table</a>
						<a class="dropdown-item" href="#">Table with Buttons</a>
						<a class="dropdown-item" href="#">Column Search</a>
						<a class="dropdown-item" href="#">Muulti Selection</a>
						<a class="dropdown-item" href="#">Ajax Sourced Data</a>
					</div>
				</div>
			</div>
		</li>
	</ul>

	<div class="navbar-collapse collapse">
		<ul class="navbar-nav navbar-align">
			<li class="nav-item dropdown">
				<a class="nav-icon dropdown-toggle" href="#" id="messagesDropdown" data-bs-toggle="dropdown">
					<div class="position-relative">
						<i class="align-middle text-body" data-lucide="message-circle"></i>
						<span class="indicator">4</span>
					</div>
				</a>
				<div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0" aria-labelledby="messagesDropdown">
					<div class="dropdown-menu-header">
						<div class="position-relative">
							4 New Messages
						</div>
					</div>
					<div class="list-group">
						<a href="#" class="list-group-item">
							<div class="row g-0 align-items-center">
								<div class="col-2">
									<img src="<?php echo base_url(); ?>bootstrap/img/avatars/avatar-5.jpg"
										class="img-fluid rounded-circle" alt="Ashley Briggs" width="40" height="40">
								</div>
								<div class="col-10 ps-2">
									<div>Ashley Briggs</div>
									<div class="text-muted small mt-1">Nam pretium turpis et arcu. Duis arcu tortor.
									</div>
									<div class="text-muted small mt-1">15m ago</div>
								</div>
							</div>
						</a>
						<a href="#" class="list-group-item">
							<div class="row g-0 align-items-center">
								<div class="col-2">
									<img src="<?php echo base_url(); ?>bootstrap/img/avatars/avatar-2.jpg"
										class="img-fluid rounded-circle" alt="Carl Jenkins" width="40" height="40">
								</div>
								<div class="col-10 ps-2">
									<div>Carl Jenkins</div>
									<div class="text-muted small mt-1">Curabitur ligula sapien euismod vitae.</div>
									<div class="text-muted small mt-1">2h ago</div>
								</div>
							</div>
						</a>
						<a href="#" class="list-group-item">
							<div class="row g-0 align-items-center">
								<div class="col-2">
									<img src="<?php echo base_url(); ?>bootstrap/img/avatars/avatar-4.jpg"
										class="img-fluid rounded-circle" alt="Stacie Hall" width="40" height="40">
								</div>
								<div class="col-10 ps-2">
									<div>Stacie Hall</div>
									<div class="text-muted small mt-1">Pellentesque auctor neque nec urna.</div>
									<div class="text-muted small mt-1">4h ago</div>
								</div>
							</div>
						</a>
						<a href="#" class="list-group-item">
							<div class="row g-0 align-items-center">
								<div class="col-2">
									<img src="<?php echo base_url(); ?>bootstrap/img/avatars/avatar-3.jpg"
										class="img-fluid rounded-circle" alt="Bertha Martin" width="40" height="40">
								</div>
								<div class="col-10 ps-2">
									<div>Bertha Martin</div>
									<div class="text-muted small mt-1">Aenean tellus metus, bibendum sed, posuere ac,
										mattis non.</div>
									<div class="text-muted small mt-1">5h ago</div>
								</div>
							</div>
						</a>
					</div>
					<div class="dropdown-menu-footer">
						<a href="#" class="text-muted">Show all messages</a>
					</div>
				</div>
			</li>
			<li class="nav-item dropdown">
				<a class="nav-icon dropdown-toggle" href="#" id="alertsDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside">
					<div class="position-relative">
						<i class="align-middle text-body" data-lucide="bell"></i>
						<span class="indicator" id="alertBadge" style="display:none">0</span>
					</div>
				</a>
				<div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0" aria-labelledby="alertsDropdown">
					<div class="dropdown-menu-header">
						<span id="alertHeader">Notificaciones</span>
					</div>
					<div class="list-group" id="alertList">
						<div class="text-center py-3 text-muted">
							<i class="fas fa-spinner fa-spin fa-fw me-1"></i>Cargando...
						</div>
					</div>
					<div class="dropdown-menu-footer d-flex justify-content-between">
						<a href="#" class="text-muted small" id="markAllReadBtn" onclick="return markAllAlertsRead();">
							<i class="fas fa-check-double fa-fw me-1"></i>Marcar todo leído
						</a>
						<a href="<?= route_to('alerts.history') ?>" class="text-muted small">
							<i class="fas fa-list fa-fw me-1"></i>Ver todas
						</a>
					</div>
				</div>
			</li>
			<li class="nav-item nav-theme-toggle dropdown">
				<a class="nav-icon js-theme-toggle" href="#">
					<div class="position-relative">
						<i class="align-middle text-body nav-theme-toggle-light" data-lucide="sun"></i>
						<i class="align-middle text-body nav-theme-toggle-dark" data-lucide="moon"></i>
					</div>
				</a>
			</li>
			<!--
						<li class="nav-item dropdown">
							<a class="nav-flag dropdown-toggle" href="#" id="languageDropdown" data-bs-toggle="dropdown">
				<img src="<?php echo base_url(); ?>bootstrap/img/flags/us.png" alt="English" />
			  </a>
							<div class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
								<a class="dropdown-item" href="#">
				  <img src="<?php echo base_url(); ?>bootstrap/img/flags/us.png" alt="English" width="20" class="align-middle me-1" />
				  <span class="align-middle">English</span>
				</a>
								<a class="dropdown-item" href="#">
				  <img src="<?php echo base_url(); ?>bootstrap/img/flags/es.png" alt="Spanish" width="20" class="align-middle me-1" />
				  <span class="align-middle">Spanish</span>
				</a>
								<a class="dropdown-item" href="#">
				  <img src="<?php echo base_url(); ?>bootstrap/img/flags/de.png" alt="German" width="20" class="align-middle me-1" />
				  <span class="align-middle">German</span>
				</a>
								<a class="dropdown-item" href="#">
				  <img src="<?php echo base_url(); ?>bootstrap/img/flags/nl.png" alt="Dutch" width="20" class="align-middle me-1" />
				  <span class="align-middle">Dutch</span>
				</a>
							</div>
						</li> -->
			<li class="nav-item dropdown">
				<a class="nav-icon dropdown-toggle d-inline-block d-sm-none" href="#" data-bs-toggle="dropdown">
					<i class="align-middle" data-lucide="settings"></i>
				</a>

				<a class="nav-link dropdown-toggle d-none d-sm-inline-block" href="#" data-bs-toggle="dropdown">
					<?php
					$sessionUser = auth()->user();
					$avatarFile = $sessionUser->avatar ?? null;
					$avatarUrl = (!empty($avatarFile) && file_exists(FCPATH . 'uploads/avatars/' . $avatarFile))
						? base_url('uploads/avatars/' . $avatarFile)
						: base_url('bootstrap/img/avatars/avatar.jpg');
					$displayName = trim(($sessionUser->first_name ?? '') . ' ' . ($sessionUser->last_name ?? ''));
					$displayName = $displayName ?: ($sessionUser->username ?? 'Usuario');
					?>
					<img src="<?= $avatarUrl ?>" class="img-fluid rounded-circle me-1 mt-n2 mb-n2"
						alt="<?= esc($displayName) ?>" width="40" height="40" /> <span><?= esc($displayName) ?></span>
				</a>
				<div class="dropdown-menu dropdown-menu-end">
					<a class="dropdown-item" href="<?= route_to('user.profile') ?>"><i class="align-middle me-1"
							data-lucide="user"></i> Mi Perfil</a>
					<div class="dropdown-divider"></div>
					<a class="dropdown-item" href="<?= route_to('logout') ?>"><i class="align-middle me-1"
							data-lucide="log-out"></i> Cerrar Sesión</a>
				</div>

			</li>
		</ul>
	</div>
</nav>

<script>
	// Persistir tema al hacer click en el toggle
	(function () {
		var toggle = document.querySelector('.js-theme-toggle');
		if (toggle) {
			toggle.addEventListener('click', function (e) {
				e.preventDefault();       // Evita scroll al top
				e.stopPropagation();      // Evita que app.js de AppStack dispare su propio handler (causa conflicto con DataTables)
				var html = document.documentElement;
				var current = html.getAttribute('data-bs-theme') || 'light';
				var next = current === 'dark' ? 'light' : 'dark';
				html.setAttribute('data-bs-theme', next);
				localStorage.setItem('appstack-theme', next);
				// Forzar re-render de los iconos Lucide si están disponibles
				if (typeof lucide !== 'undefined') lucide.createIcons();
			});
		}
	})();
</script>

<script>
	// ── Sistema de Alertas / Notificaciones ──────────────────────────────
	// Carga las alertas vía AJAX, las renderiza en el dropdown y actualiza el badge.
	(function () {
		const UNREAD_URL = '<?= route_to('alerts.get_unread') ?>';
		const MARK_READ_URL = '<?= route_to('alerts.mark_read') ?>';
		const MARK_ALL_READ_URL = '<?= route_to('alerts.mark_all_read') ?>';
		const POLL_INTERVAL = 60000; // 60 segundos

		/**
		 * Obtiene el icono FontAwesome según el tipo de alerta.
		 */
		function getTypeIcon(type) {
			const map = {
				'info': 'fa-info-circle',
				'success': 'fa-check-circle',
				'warning': 'fa-exclamation-triangle',
				'danger': 'fa-times-circle'
			};
			return map[type] || 'fa-bell';
		}

		/**
		 * Obtiene la clase CSS de color según el tipo de alerta.
		 */
		function getTypeClass(type) {
			const map = {
				'info': 'text-primary',
				'success': 'text-success',
				'warning': 'text-warning',
				'danger': 'text-danger'
			};
			return map[type] || 'text-body';
		}

		/**
		 * Escapa HTML para prevenir XSS.
		 */
		function escapeHtml(str) {
			if (!str) return '';
			var div = document.createElement('div');
			div.appendChild(document.createTextNode(str));
			return div.innerHTML;
		}

		/**
		 * Formatea un timestamp ISO a formato relativo (hace X minutos/horas/días).
		 */
		function timeAgoFormat(isoString) {
			if (!isoString) return '';
			var now = new Date();
			var date = new Date(isoString.replace(' ', 'T') + 'Z');
			var diffMs = now - date;
			var diffMin = Math.floor(diffMs / 60000);
			if (diffMin < 1) return 'Ahora';
			if (diffMin < 60) return 'Hace ' + diffMin + ' min';
			var diffHr = Math.floor(diffMin / 60);
			if (diffHr < 24) return 'Hace ' + diffHr + ' h';
			var diffDays = Math.floor(diffHr / 24);
			if (diffDays < 7) return 'Hace ' + diffDays + ' d';
			return date.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
		}

		function showAlertsError(message) {
			var list = document.getElementById('alertList');
			var header = document.getElementById('alertHeader');
			if (header) {
				header.textContent = 'Notificaciones';
			}
			if (list) {
				list.innerHTML = '<div class="text-center py-3 text-muted"><i class="fas fa-exclamation-triangle fa-fw me-1"></i>' + escapeHtml(message || 'No se pudieron cargar las notificaciones') + '</div>';
			}
		}

		/**
		 * Carga las alertas no leídas desde el servidor.
		 */
		function loadAlerts() {
			var csrfToken = document.getElementById('csrf_token');
			var tokenValue = csrfToken ? csrfToken.value : '';

			fetch(UNREAD_URL, {
				method: 'GET',
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					'<?= csrf_header() ?>': tokenValue
				}
			})
			.then(function (response) {
				// Renovar token CSRF desde headers
				var newToken = response.headers.get('<?= csrf_header() ?>');
				if (newToken && csrfToken) {
					csrfToken.value = newToken;
				}
				return response.json().then(function (data) {
					return { ok: response.ok, data: data };
				});
			})
			.then(function (result) {
				var data = result.data;
				if (data.csrf && csrfToken) {
					csrfToken.value = data.csrf;
				}
				if (result.ok && data.success) {
					var payload = data.response || data;
					renderAlerts(payload.alerts || [], payload.count || 0);
				} else {
					showAlertsError(data.message || 'No se pudieron cargar las notificaciones');
					console.warn('[Alertas] Error al cargar:', data.message);
				}
			})
			.catch(function (err) {
				showAlertsError('Error de conexión al cargar notificaciones');
				console.error('[Alertas] Error de red:', err);
			});
		}

		/**
		 * Renderiza las alertas en el dropdown y actualiza el badge.
		 */
		function renderAlerts(alerts, count) {
			var list = document.getElementById('alertList');
			var badge = document.getElementById('alertBadge');
			var header = document.getElementById('alertHeader');

			if (!list) return;

			// Actualizar badge
			if (badge) {
				if (count > 0) {
					badge.textContent = count > 99 ? '99+' : count;
					badge.style.display = '';
				} else {
					badge.style.display = 'none';
				}
			}

			// Actualizar header
			if (header) {
				var text = count > 0 ? 'Tienes ' + count + ' notificacione' + (count === 1 ? '' : 's') : 'Notificaciones';
				header.textContent = text;
			}

			// Si no hay alertas
			if (!alerts || alerts.length === 0) {
				list.innerHTML = '<div class="text-center py-3 text-muted"><i class="fas fa-bell-slash fa-fw me-1"></i>Sin notificaciones nuevas</div>';
				return;
			}

			// Construir HTML
			var html = '';
			for (var i = 0; i < alerts.length; i++) {
				var a = alerts[i];
				var icon = getTypeIcon(a.type);
				var iconClass = getTypeClass(a.type);
				var title = escapeHtml(a.title);
				var message = escapeHtml(a.message || '');
				var time = timeAgoFormat(a.created_at);
				var targetUrl = a.target_url || '';
				var alertId = a.alert_id || a.id;
				var isAuto = a.is_auto ? 1 : 0;

				html += '<a href="' + targetUrl + '" class="list-group-item list-group-item-action border-bottom alert-item" data-alert-id="' + alertId + '" onclick="return handleAlertClick(this, event, ' + alertId + ', ' + isAuto + ');">';
				html += '	<div class="row align-items-center">';
				html += '		<div class="col-auto">';
				html += '			<i class="fas fa-fw ' + icon + ' fa-lg ' + iconClass + '"></i>';
				html += '		</div>';
				html += '		<div class="col ps-0">';
				html += '			<div class="text-body fw-bold">' + title + '</div>';
				if (message) {
					html += '			<div class="text-muted small text-truncate" style="max-width:250px;">' + message + '</div>';
				}
				html += '			<small class="text-muted">' + time + '</small>';
				html += '		</div>';
				html += '	</div>';
				html += '</a>';
			}

			list.innerHTML = html;
		}

		/**
		 * Maneja el click en una alerta.
		 * - Alerts automáticas (isAuto=1): solo navega, no marca como leída.
		 * - Alerts almacenadas (isAuto=0): marca como leída vía AJAX + navega.
		 */
		window.handleAlertClick = function (el, event, alertId, isAuto) {
			// Auto-alertas: solo navegar, no marcar como leída
			if (isAuto) {
				var href = el.getAttribute('href');
				if (!href || href === '' || href === '#') {
					if (event) event.preventDefault();
					return false;
				}
				return true;
			}

			// Alertas almacenadas: marcar como leída (fire-and-forget) y navegar
			var csrfToken = document.getElementById('csrf_token');
			var tokenValue = csrfToken ? csrfToken.value : '';
			var formData = new FormData();
			formData.append('alert_id', alertId);

			fetch(MARK_READ_URL, {
				method: 'POST',
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					'<?= csrf_header() ?>': tokenValue
				},
				body: formData
			})
			.then(function (response) {
				var newToken = response.headers.get('<?= csrf_header() ?>');
				if (newToken && csrfToken) {
					csrfToken.value = newToken;
				}
				return response.json();
			})
			.then(function (data) {
				if (data.success) {
					loadAlerts();
				}
			})
			.catch(function (err) {
				console.error('[Alertas] Error al marcar como leída:', err);
			});

			// Navegar si hay target_url real
			var href = el.getAttribute('href');
			if (!href || href === '' || href === '#') {
				if (event) event.preventDefault();
				return false;
			}

			return true;
		};

		/**
		 * Marca todas las alertas como leídas vía AJAX.
		 */
		window.markAllAlertsRead = function () {
			var csrfToken = document.getElementById('csrf_token');
			var tokenValue = csrfToken ? csrfToken.value : '';

			fetch(MARK_ALL_READ_URL, {
				method: 'POST',
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					'<?= csrf_header() ?>': tokenValue
				}
			})
			.then(function (response) {
				var newToken = response.headers.get('<?= csrf_header() ?>');
				if (newToken && csrfToken) {
					csrfToken.value = newToken;
				}
				return response.json();
			})
			.then(function (data) {
				if (data.success) {
					if (typeof notifyShow === 'function') {
						notifyShow(data.message || 'Todas las notificaciones marcadas como leídas', 'success');
					}
					loadAlerts();
				} else {
					if (typeof notifyShow === 'function') {
						notifyShow(data.message || 'Error al marcar notificaciones', 'danger');
					}
				}
			})
			.catch(function (err) {
				console.error('[Alertas] Error al marcar todas como leídas:', err);
			});

			return false; // Prevenir navegación del enlace #
		};

		// Inicializar al cargar DOM
		document.addEventListener('DOMContentLoaded', function () {
			loadAlerts();
			setInterval(loadAlerts, POLL_INTERVAL);
		});
	})();
</script>