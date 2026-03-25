<?php $this->extend($layout); ?>

<?php $this->section('title');
echo $pageTitle;
$this->endSection(); ?>

<?php $this->section('pageStyles'); ?>
<?php $this->endSection(); ?>
<?php $this->section('pageHeaderScripts'); ?>
<?php $this->endSection(); ?>

<?php $this->section('main')?>
<main class="content">
  <div class="container-fluid p-0">

    <h1 class="h3 mb-3">
      <?= $pageTitle?>
    </h1>

    <div class="row">
      <div class="col-xl-8">
        <div class="card">
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-4 mb-2 mb-md-0">
                <div class="input-group input-group-search">
                  <input type="text" class="form-control" id="datatables-customers-search"
                    placeholder="Buscar usuarios…">
                  <button class="btn" type="button">
                    <i class="align-middle" data-lucide="search"></i>
                  </button>
                </div>
              </div>
              <div class="col-md-4 mb-2 mb-md-0">
                <div class="d-flex gap-2">
                  <select class="form-select" id="filter-role">
                    <option value="">Todos los Roles</option>
                    <option value="superadmin">Superadmin</option>
                    <option value="admin">Administrador</option>
                    <option value="seller">Vendedor</option>
                    <option value="editor">Editor</option>
                  </select>
                  <select class="form-select" id="filter-status">
                    <option value="">Cualquier Estatus</option>
                    <option value="active">Activo</option>
                    <option value="deleted">Eliminado</option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="text-sm-end">
                  <button type="button" class="btn btn-light btn-lg me-2"><i data-lucide="download"></i> Export</button>
                  <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal"
                    data-bs-target="#modalAddUser"><i data-lucide="plus"></i> Añadir Usuario</button>
                </div>
              </div>
            </div>
            <table id="datatables-customers" class="table w-100">
              <thead>
                <tr>
                  <th class="text-start" style="width: 50px;">#</th>
                  <th>Nombre Completo</th>
                  <th>Rol</th>
                  <th>Correo Electrónico</th>
                  <th>Estatus</th>
                </tr>
              </thead>
              <tbody>
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
                <img src="<?php echo base_url('bootstrap/img/avatars/avatar-3.jpg'); ?>" width="64" height="64"
                  class="rounded-circle mt-2" alt="Angelica Ramos">
              </div>
              <div class="col-sm-9 col-xl-12 col-xxl-9">
                <strong>About me</strong>
                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore
                  et dolore magna aliqua.</p>
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

  </div>
</main>
<?php $this->endSection()?>

<?php $this->section('pageFooterScripts'); ?>
<?= $this->include('Users/Partials/user_form_modal')?>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    // Inicializar dataTable automáticamente
    inicializarDataTable();

    const formAddUser = document.getElementById('formAddUser');
    const btnSaveUser = document.getElementById('btnSaveUser');

    if (btnSaveUser && formAddUser) {
      btnSaveUser.addEventListener('click', function (e) {
        e.preventDefault();
        // Validar formulario con jQuery Validate antes de procesar
        if (!$("#formAddUser").valid()) {
          return;
        }

        btnSaveUser.disabled = true;
        btnSaveUser.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

        let formData = new FormData(formAddUser);

        fetch('<?= route_to('user.create')?>', {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: formData
        })
          .then(response => {
            // Extraer el nuevo token CSRF de los encabezados (puestos por CsrfTokenFilter)
            const newCsrfHash = response.headers.get('<?= csrf_header()?>');
            if (newCsrfHash) {
              document.getElementById('csrf_token').value = newCsrfHash;
            }
            return response.json();
          })
          .then(data => {
            if (data.success) {
              notifyShow(data.message, 'success');

              formAddUser.reset();
              var myModalEl = document.getElementById('modalAddUser');
              var modal = bootstrap.Modal.getInstance(myModalEl);
              if (modal) {
                modal.hide();
              }

              // Recargar DataTables después de guardar exitosamente
              if (window.usersDataTable) {
                window.usersDataTable.ajax.reload(null, false);
              }

            } else {
              let errString = data.error || 'Revise los campos enviados.';
              if (typeof errString === 'object') {
                errString = Object.values(errString).join('<br>');
              }
              // Inyectar alerta AppStack directamente en el modal body en vez de bigNotification
              const alertHtml = `
              <div class="alert alert-danger alert-outline alert-dismissible" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <div class="alert-icon"><i class="far fa-fw fa-bell"></i></div>
                <div class="alert-message">
                  <strong>${data.message || 'Error al guardar'}</strong><br>${errString}
                </div>
              </div>`;

              let modalBody = formAddUser.closest('.modal-body');
              // remover alertas previas si hubieran
              let oldAlert = modalBody.querySelector('.alert');
              if (oldAlert) oldAlert.remove();

              modalBody.insertAdjacentHTML('beforeend', alertHtml);
            }
          })
          .catch(error => {
            const alertHtml = `
              <div class="alert alert-danger alert-outline alert-dismissible" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <div class="alert-icon"><i class="far fa-fw fa-bell"></i></div>
                <div class="alert-message">
                  <strong>Error</strong><br>Hubo un problema de conexión con el servidor.
                </div>
              </div>`;
            let modalBody = formAddUser.closest('.modal-body');
            let oldAlert = modalBody.querySelector('.alert');
            if (oldAlert) oldAlert.remove();
            modalBody.insertAdjacentHTML('beforeend', alertHtml);
            console.error(error);
          })
          .finally(() => {
            // Restaurar botón
            btnSaveUser.disabled = false;
            btnSaveUser.innerHTML = 'Guardar Usuario';
          });
      });
    }
  });
</script>

<script>

  function inicializarDataTable() {
    window.usersDataTable = $('#datatables-customers').DataTable({
      processing: true,
      serverSide: true,
      responsive: true, // Reactivado para que la tabla sea colapsable y no rompa el diseño
      dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'>>" +
           "<'row'<'col-sm-12'tr>>" +
           "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>", // Quitamos la 'f' para ocultar el buscador nativo
      ajax: {
        url: '<?= route_to('users.list_ajax')?>',
        type: 'POST', // CodeIgniter usa post en listas datatables si pasamos Token, de lo contrario GET
        data: function (d) {
          // jQuery AJAX inyecta inteligentemente X-Requested-With, así que no necesitamos hacerlo
          // CSRF Security injection
          d['<?= csrf_token()?>'] = document.getElementById('csrf_token') ? document.getElementById('csrf_token').value : '';
          
          // Inyectar custom filters al backend
          d.role = $('#filter-role').val();
          d.status = $('#filter-status').val();
        }
      },
      columns: [
        { data: 0, orderable: false, searchable: false },  // Avatar Placeholder
        { data: 1 },  // Name & Username
        { data: 2 },  // Role / Group
        { data: 3 },  // Email
        { data: 4, orderable: false, searchable: false }   // Status Badge
      ],
      language: {
        url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json'
      },
      pageLength: 10,
      order: [[1, 'asc']] // Sort by Name ascending by default
    });

    // Al finalizar una recarga AJAX de datatables, el filtro atrapa y cambia el CSRF de los headers, 
    // DataTables en modo POST quema el token, actualicémoslo:
    $('#datatables-customers').on('xhr.dt', function (e, settings, json, xhr) {
      if (xhr && xhr.getResponseHeader('<?= csrf_header()?>')) {
        const newCsrfHash = xhr.getResponseHeader('<?= csrf_header()?>');
        if (document.getElementById('csrf_token')) {
          document.getElementById('csrf_token').value = newCsrfHash;
        }
      }
    });

    // Filtro rápido de búesqueda AppStack con Debounce para evitar Race Conditions del CSRF
    let searchTimeout;
    $('#datatables-customers-search').on('keyup', function () {
      let searchValue = this.value;
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(function() {
        if (window.usersDataTable) {
          window.usersDataTable.search(searchValue).draw();
        }
      }, 500); // 500ms de retraso para evitar invalidación CSRF
    });

    // Filtros personalizados recargan la tabla
    $('#filter-role, #filter-status').on('change', function () {
      if (window.usersDataTable) {
        window.usersDataTable.ajax.reload();
      }
    });
  }
</script>

<?php $this->endSection(); ?>