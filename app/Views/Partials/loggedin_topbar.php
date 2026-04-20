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
				<a class="nav-icon dropdown-toggle" href="#" id="alertsDropdown" data-bs-toggle="dropdown">
					<div class="position-relative">
						<i class="align-middle text-body" data-lucide="bell-off"></i>
					</div>
				</a>
				<div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0" aria-labelledby="alertsDropdown">
					<div class="dropdown-menu-header">
						4 New Notifications
					</div>
					<div class="list-group">
						<a href="#" class="list-group-item">
							<div class="row g-0 align-items-center">
								<div class="col-2">
									<i class="text-danger" data-lucide="alert-circle"></i>
								</div>
								<div class="col-10">
									<div>Update completed</div>
									<div class="text-muted small mt-1">Restart server 12 to complete the update.</div>
									<div class="text-muted small mt-1">2h ago</div>
								</div>
							</div>
						</a>
						<a href="#" class="list-group-item">
							<div class="row g-0 align-items-center">
								<div class="col-2">
									<i class="text-warning" data-lucide="bell"></i>
								</div>
								<div class="col-10">
									<div>Lorem ipsum</div>
									<div class="text-muted small mt-1">Aliquam ex eros, imperdiet vulputate hendrerit
										et.</div>
									<div class="text-muted small mt-1">6h ago</div>
								</div>
							</div>
						</a>
						<a href="#" class="list-group-item">
							<div class="row g-0 align-items-center">
								<div class="col-2">
									<i class="text-primary" data-lucide="home"></i>
								</div>
								<div class="col-10">
									<div>Login from 192.186.1.1</div>
									<div class="text-muted small mt-1">8h ago</div>
								</div>
							</div>
						</a>
						<a href="#" class="list-group-item">
							<div class="row g-0 align-items-center">
								<div class="col-2">
									<i class="text-success" data-lucide="user-plus"></i>
								</div>
								<div class="col-10">
									<div>New connection</div>
									<div class="text-muted small mt-1">Anna accepted your request.</div>
									<div class="text-muted small mt-1">12h ago</div>
								</div>
							</div>
						</a>
					</div>
					<div class="dropdown-menu-footer">
						<a href="#" class="text-muted">Show all notifications</a>
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