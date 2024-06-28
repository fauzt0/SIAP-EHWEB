<?php $this->extend('Layouts/loggedin') ?>     

<?php $this->section('contenido') ?>  
  <main class="content">
    <div class="container-fluid p-0">

      <h1 class="h3 mb-3">Usuarios</h1>

      <!--Tabla de usuarios -->
      <div class="row">
						<div class="col-xl-8">
							<div class="card">
								<div class="card-body">
									<div class="row mb-3">
										<div class="col-md-6 mb-2 mb-md-0">
											<div class="input-group input-group-search">
												<input type="text" class="form-control" id="datatables-customers-search" placeholder="Search customers…">
												<button class="btn" type="button">
                <i class="align-middle" data-lucide="search"></i>
              </button>
											</div>
										</div>
										<div class="col-md-6">
											<div class="text-sm-end">
												<button type="button" class="btn btn-light btn-lg me-2"><i data-lucide="download"></i> Export</button>
												<button type="button" class="btn btn-primary btn-lg"><i data-lucide="plus"></i> Add Customer</button>
											</div>
										</div>
									</div>
									<table id="datatables-customers" class="table w-100">
										<thead>
											<tr>
												<th class="text-start">#</th>
												<th>Name</th>
												<th>Company</th>
												<th>Email</th>
												<th>Status</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td><img src="img/avatars/avatar.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Garrett Winters</td>
												<td>Good Guys</td>
												<td>garrett@winters.com</td>
												<td><span class="badge badge-subtle-success">Active</span></td>
											</tr>
											<tr>
												<td><img src="img/avatars/avatar.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Ashton Cox</td>
												<td>Levitz Furniture</td>
												<td>ashton@cox.com</td>
												<td><span class="badge badge-subtle-success">Active</span></td>
											</tr>
											<tr>
												<td><img src="img/avatars/avatar.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Sonya Frost</td>
												<td>Child World</td>
												<td>sonya@frost.com</td>
												<td><span class="badge badge-subtle-danger">Deleted</span></td>
											</tr>
											<tr>
												<td><img src="img/avatars/avatar.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Jena Gaines</td>
												<td>Helping Hand</td>
												<td>jena@gaines.com</td>
												<td><span class="badge badge-subtle-warning">Inactive</span></td>
											</tr>
											<tr>
												<td><img src="img/avatars/avatar-2.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Quinn Flynn</td>
												<td>Good Guys</td>
												<td>quinn@flynn.com</td>
												<td><span class="badge badge-subtle-success">Active</span></td>
											</tr>
											<tr>
												<td><img src="img/avatars/avatar-2.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Charde Marshall</td>
												<td>Price Savers</td>
												<td>charde@marshall.com</td>
												<td><span class="badge badge-subtle-success">Active</span></td>
											</tr>
											<tr>
												<td><img src="img/avatars/avatar-2.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Haley Kennedy</td>
												<td>Helping Hand</td>
												<td>haley@kennedy.com</td>
												<td><span class="badge badge-subtle-danger">Deleted</span></td>
											</tr>
											<tr>
												<td><img src="img/avatars/avatar-2.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Tatyana Fitzpatrick</td>
												<td>Good Guys</td>
												<td>tatyana@fitzpatrick.com</td>
												<td><span class="badge badge-subtle-success">Active</span></td>
											</tr>
											<tr>
												<td><img src="img/avatars/avatar-3.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Michael Silva</td>
												<td>Red Robin Stores</td>
												<td>michael@silva.com</td>
												<td><span class="badge badge-subtle-success">Active</span></td>
											</tr>
											<tr>
												<td><img src="img/avatars/avatar-3.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Yuri Berry</td>
												<td>The Wiz</td>
												<td>yuri@berry.com</td>
												<td><span class="badge badge-subtle-danger">Deleted</span></td>
											</tr>
											<tr>
												<td><img src="img/avatars/avatar-3.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Doris Wilder</td>
												<td>Red Robin Stores</td>
												<td>doris@wilder.com</td>
												<td><span class="badge badge-subtle-warning">Inactive</span></td>
											</tr>
											<tr>
												<td><img src="img/avatars/avatar-3.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Angelica Ramos</td>
												<td>The Wiz</td>
												<td>angelica@ramos.com</td>
												<td><span class="badge badge-subtle-success">Active</span></td>
											</tr>
											<tr>
												<td><img src="img/avatars/avatar-4.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Jennifer Chang</td>
												<td>Helping Hand</td>
												<td>jennifer@chang.com</td>
												<td><span class="badge badge-subtle-warning">Inactive</span></td>
											</tr>
											<tr>
												<td><img src="img/avatars/avatar-4.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Brenden Wagner</td>
												<td>The Wiz</td>
												<td>brenden@wagner.com</td>
												<td><span class="badge badge-subtle-warning">Inactive</span></td>
											</tr>
											<tr>
												<td><img src="img/avatars/avatar-4.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Fiona Green</td>
												<td>The Sample</td>
												<td>fiona@green.com</td>
												<td><span class="badge badge-subtle-warning">Inactive</span></td>
											</tr>
											<tr>
												<td><img src="img/avatars/avatar-4.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Suki Burks</td>
												<td>The Sample</td>
												<td>suki@burks.com</td>
												<td><span class="badge badge-subtle-success">Active</span></td>
											</tr>
											<tr>
												<td><img src="img/avatars/avatar-5.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Prescott Bartlett</td>
												<td>The Sample</td>
												<td>prescott@bartlett.com</td>
												<td><span class="badge badge-subtle-success">Active</span></td>
											</tr>
											<tr>
												<td><img src="img/avatars/avatar-5.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Gavin Cortez</td>
												<td>Red Robin Stores</td>
												<td>gavin@cortez.com</td>
												<td><span class="badge badge-subtle-success">Active</span></td>
											</tr>
											<tr>
												<td><img src="img/avatars/avatar-5.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Unity Butler</td>
												<td>Price Savers</td>
												<td>unity@butler.com</td>
												<td><span class="badge badge-subtle-warning">Inactive</span></td>
											</tr>
											<tr>
												<td><img src="img/avatars/avatar-5.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
												<td>Howard Hatfield</td>
												<td>Price Savers</td>
												<td>howard@hatfield.com</td>
												<td><span class="badge badge-subtle-warning">Inactive</span></td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>

						<div class="col-xl-4">
							<div class="card">
								<div class="card-header">
									<div class="card-actions float-end">
										<div class="dropdown position-relative">
											<a href="#" data-bs-toggle="dropdown" data-bs-display="static">
              <i class="align-middle" data-lucide="more-horizontal"></i>
            </a>

											<div class="dropdown-menu dropdown-menu-end">
												<a class="dropdown-item" href="#">Action</a>
												<a class="dropdown-item" href="#">Another action</a>
												<a class="dropdown-item" href="#">Something else here</a>
											</div>
										</div>
									</div>
									<h5 class="card-title mb-0">Angelica Ramos</h5>
								</div>
								<div class="card-body">
									<div class="row g-0">
										<div class="col-sm-3 col-xl-12 col-xxl-3 text-center">
											<img src="img/avatars/avatar-3.jpg" width="64" height="64" class="rounded-circle mt-2" alt="Angelica Ramos">
										</div>
										<div class="col-sm-9 col-xl-12 col-xxl-9">
											<strong>About me</strong>
											<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
										</div>
									</div>

									<table class="table table-sm my-2">
										<tbody>
											<tr>
												<th>Name</th>
												<td>Angelica Ramos</td>
											</tr>
											<tr>
												<th>Company</th>
												<td>The Wiz</td>
											</tr>
											<tr>
												<th>Email</th>
												<td>angelica@ramos.com</td>
											</tr>
											<tr>
												<th>Status</th>
												<td><span class="badge badge-subtle-success">Active</span></td>
											</tr>
										</tbody>
									</table>

									<hr />

									<strong>Activity</strong>

									<ul class="timeline mt-2 mb-0">
										<li class="timeline-item">
											<strong>Signed out</strong>
											<span class="float-end text-muted text-sm">30m ago</span>
											<p>Nam pretium turpis et arcu. Duis arcu tortor, suscipit...</p>
										</li>
										<li class="timeline-item">
											<strong>Created invoice #1204</strong>
											<span class="float-end text-muted text-sm">2h ago</span>
											<p>Sed aliquam ultrices mauris. Integer ante arcu...</p>
										</li>
										<li class="timeline-item">
											<strong>Discarded invoice #1147</strong>
											<span class="float-end text-muted text-sm">3h ago</span>
											<p>Nam pretium turpis et arcu. Duis arcu tortor, suscipit...</p>
										</li>
										<li class="timeline-item">
											<strong>Signed in</strong>
											<span class="float-end text-muted text-sm">3h ago</span>
											<p>Curabitur ligula sapien, tincidunt non, euismod vitae...</p>
										</li>
										<li class="timeline-item">
											<strong>Signed up</strong>
											<span class="float-end text-muted text-sm">2d ago</span>
											<p>Sed aliquam ultrices mauris. Integer ante arcu...</p>
										</li>
									</ul>

								</div>
							</div>
						</div>
					</div>

      <!-- Fin de tabla de usuarios -->
      
    </div>
  </main>    
<?php $this->endSection() ?>


<?php $this->section('scripts'); ?>
  

  <script>
    document.addEventListener("DOMContentLoaded", function() {
			// Datatables customers
			$("#datatables-customers").DataTable({
				destroy: true,
				responsive: true,
				order: [
					[1, "asc"]
				],
				columnDefs: [{
					targets: 0,
					orderable: false,
					width: "32px"
				}],
				layout: {
					topStart: null,
					topEnd: null,
					bottomStart: 'info',
					bottomEnd: 'paging'
				}
			});
			$("#datatables-customers-search").keyup(function() {
				$("#datatables-customers").DataTable().search($(this).val()).draw();
			});
		});
<?php $this->endSection(); ?>   
