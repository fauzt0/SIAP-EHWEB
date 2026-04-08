<?php $this->extend($layout); ?>

<?php $this->section('title');
echo $pageTitle;
$this->endSection(); ?>

<?php $this->section('pageStyles'); ?>
<?php $this->endSection(); ?>
<?php $this->section('pageHeaderScripts'); ?>
<?php $this->endSection(); ?>

<?php $this->section('main') ?>
<main class="content">
  <div class="container-fluid p-0">

    <div class="row mb-2 mb-xl-3">
      <div class="col-auto d-none d-sm-block">
        <h1 class="h3 mb-3"><?= esc($pageTitle) ?></h1>
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
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-12">
                <div class="text-sm-start ">
                  <button type="button" class="btn btn-light btn-lg me-2"><i data-lucide="download"></i> Export</button>
                  <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal"
                    data-bs-target="#modalAddUser"><i data-lucide="plus"></i> Añadir Usuario</button>
                </div>
              </div>
            </div>

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
              <div class="col-md-8 mb-2 mb-md-0">
                <div class="d-flex gap-2 flex-wrap align-items-center">
                  <!-- Filtro por Rol -->
                  <select class="form-select" id="filter-role" style="max-width: 160px;">
                    <option value="">Todos los Roles</option>
                    <option value="superadmin">Superadmin</option>
                    <option value="admin">Administrador</option>
                    <option value="seller">Vendedor</option>
                    <option value="editor">Editor</option>
                  </select>
                  <!-- Filtro por Estatus -->
                  <select class="form-select" id="filter-status" style="max-width: 160px;">
                    <option value="active">Activos</option>
                    <option value="deleted">Eliminados</option>
                    <option value="">Todos</option>
                  </select>

                  <!-- Dropdown para Filtros de Fecha -->
                  <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" id="dropdownDateFilters"
                      data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                      <i class="fas fa-calendar-alt me-1"></i> Rango de Fechas
                    </button>
                    <div class="dropdown-menu p-3" aria-labelledby="dropdownDateFilters"
                      style="min-width: 320px; box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);">
                      <div class="row g-2">
                        <div class="col-12">
                          <label class="form-label small fw-bold">Desde:</label>
                          <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calendar-day"></i></span>
                            <input type="date" class="form-control" id="filter-date-from">
                          </div>
                        </div>
                        <div class="col-12">
                          <label class="form-label small fw-bold">Hasta:</label>
                          <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calendar-day"></i></span>
                            <input type="date" class="form-control" id="filter-date-to">
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Botón limpiar filtros -->
                  <button class="btn btn-outline-secondary" id="btn-clear-filters" title="Limpiar filtros">
                    <i class="fas fa-times"></i>
                  </button>
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
                  <th style="width: 120px;">Acciones</th>
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>
<?php $this->endSection() ?>

<?php $this->section('pageFooterScripts'); ?>
<?= $this->include('Users/Partials/user_form_modal') ?>
<?= $this->include('Users/Partials/user_edit_modal') ?>
<?= $this->include('Users/Partials/user_offcanvas') ?>

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

        fetch('<?= route_to('user.create') ?>', {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: formData
        })
          .then(response => {
            // Extraer el nuevo token CSRF de los encabezados (puestos por CsrfTokenFilter)
            const newCsrfHash = response.headers.get('<?= csrf_header() ?>');
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
    // Guard: si ya está inicializada, no reinicializar (evita el error al cambiar tema)
    if ($.fn.dataTable.isDataTable('#datatables-customers')) return;

    window.usersDataTable = $('#datatables-customers').DataTable({
      processing: true,
      serverSide: true,
      responsive: true, // Reactivado para que la tabla sea colapsable y no rompa el diseño
      dom: "<'row'<'col-sm-12 col-md-6'><'col-sm-12 col-md-6 text-end'l>>" +
        "<'row'<'col-sm-12'tr>>" +
        "<'row mt-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center'p>>", // Quitamos la 'f' para ocultar el buscador nativo
      ajax: {
        url: '<?= route_to('users.list_ajax') ?>',
        type: 'POST', // CodeIgniter usa post en listas datatables si pasamos Token, de lo contrario GET
        data: function (d) {
          d['<?= csrf_token() ?>'] = document.getElementById('csrf_token') ? document.getElementById('csrf_token').value : '';
          // Inyectar filtros personalizados en cada llamada AJAX
          d.role = $('#filter-role').val();
          d.status = $('#filter-status').val();
          d.date_from = $('#filter-date-from').val();
          d.date_to = $('#filter-date-to').val();
        }
      },
      columns: [
        { data: 0, orderable: false, searchable: false },  // Avatar Placeholder
        { data: 1 },  // Name & Username
        { data: 2 },  // Role / Group
        { data: 3 },  // Email
        { data: 4, orderable: false, searchable: false },  // Status Badge
        { data: 5, orderable: false, searchable: false }   // Acciones
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
      if (xhr && xhr.getResponseHeader('<?= csrf_header() ?>')) {
        const newCsrfHash = xhr.getResponseHeader('<?= csrf_header() ?>');
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
      searchTimeout = setTimeout(function () {
        if (window.usersDataTable) {
          window.usersDataTable.search(searchValue).draw();
        }
      }, 500); // 500ms de retraso para evitar invalidación CSRF
    });

    // Filtros personalizados: usan .draw() para que la función data() se re-ejecute con los valores actuales
    $('#filter-role, #filter-status, #filter-date-from, #filter-date-to').on('change', function () {
      if (window.usersDataTable) {
        window.usersDataTable.draw();
      }
    });

    // Botón limpiar filtros
    document.getElementById('btn-clear-filters').addEventListener('click', function () {
      $('#filter-role').val('');
      $('#filter-status').val('active');
      $('#filter-date-from').val('');
      $('#filter-date-to').val('');
      $('#datatables-customers-search').val('');
      if (window.usersDataTable) {
        window.usersDataTable.search('').draw();
      }
    });
  }

  // Delegación de eventos para los botones de acción en DataTables
  document.addEventListener('click', function (e) {
    // Botón VER (offcanvas)
    const btnView = e.target.closest('.btn-view-user');
    if (btnView) {
      const userId = btnView.dataset.userId;
      loadUserOffcanvas(userId);
      return;
    }

    // Botón EDITAR (modal)
    const btnEdit = e.target.closest('.btn-edit-user');
    if (btnEdit) {
      const userId = btnEdit.dataset.userId;
      // Simular la carga vía offcanvas -> openEditModal
      document.getElementById('offcanvasUser').dataset.currentUserId = userId;
      openEditModal();
      return;
    }

    // Botón ELIMINAR (soft delete con confirmación)
    const btnDelete = e.target.closest('.btn-delete-user');
    if (btnDelete) {
      const userId = btnDelete.dataset.userId;
      if (!confirm('¿Está seguro de que desea eliminar este usuario? Esta acción puede ser revertida.')) return;

      fetch('<?= base_url('nat/user/delete/') ?>' + userId, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: '<?= csrf_token() ?>=' + (document.getElementById('csrf_token') ? document.getElementById('csrf_token').value : '')
      })
        .then(r => {
          const newCsrf = r.headers.get('<?= csrf_header() ?>');
          if (newCsrf && document.getElementById('csrf_token')) {
            document.getElementById('csrf_token').value = newCsrf;
          }
          return r.json();
        })
        .then(data => {
          if (data.success) {
            notifyShow(data.message, 'success');
            if (window.usersDataTable) window.usersDataTable.ajax.reload(null, false);
          } else {
            notifyShow(data.message || 'Error al eliminar', 'danger');
          }
        })
        .catch(err => {
          console.error(err);
          notifyShow('Error de conexión con el servidor', 'danger');
        });
      return;
    }
  });

  // Reinicializar iconos Lucide después de cada recarga de DataTables
  $('#datatables-customers').on('draw.dt', function () {
    if (typeof lucide !== 'undefined') lucide.createIcons();
  });
</script>

<?php $this->endSection(); ?>