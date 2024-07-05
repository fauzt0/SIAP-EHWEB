<?php $this->extend('Layouts/loggedin') ?>     

<?php $this->section('contenido') ?>  
  <main class="content">
    <div class="container-fluid p-0">

      <h1 class="h3 mb-3">Usuarios de Administración</h1>

      <!--Tabla de usuarios -->
      <div class="row">
        <div class="col-xl-12">
          <div class="card">
            <div class="card-body">
              <div class="row mb-3">
                <div class="col-md-6 mb-2 mb-md-0">
                  <div class="input-group input-group-search">
                    <input type="text" class="form-control" id="datatables-users-search" placeholder="Buscar usuarios…">
                    <button class="btn" type="button">
                      <i class="align-middle" data-lucide="search"></i>
                    </button>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="text-sm-end">
                    <button type="button" class="btn btn-light btn-lg me-2"><i data-lucide="download"></i> Export</button>
                    <button type="button" class="btn btn-primary btn-lg"><i data-lucide="plus"></i> Agregar Usuario</button>
                  </div>
                </div>
              </div>
              <table id="datatables-users" class="table w-100">
                <thead>
                  <tr>
                    <th class="text-start">#</th>
                    <th>Nombre</th>
                    <th>Rol</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><img src="<?php echo base_url(); ?>bootstrap/img/avatars/avatar.jpg" width="32" height="32" class="rounded-circle my-n1" alt="Avatar"></td>
                    <td>Garrett Winters</td>
                    <td>Administrador</td>
                    <td>garrett@winters.com</td>                    
                    <td><span class="badge badge-subtle-success">Active</span></td>
                    <td><a href="#" class="btn btn-sm btn-primary">Editar</a></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>						
			</div>
      <!-- Fin de tabla de usuarios -->      
    </div>
  </main>    
<?php $this->endSection() ?>


<?php $this->section('scripts'); ?>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
  <script>    
    var csrfName = '<?php echo csrf_token(); ?>'; // CSRF Token name
    var csrfHash = '<?php echo csrf_hash(); ?>'; // CSRF hash


    

    
    function tester(){
      $.ajax({
        type: "POST",
        url: "usuario/ajaxList",
        contentType: "application/x-www-form-urlencoded", // Sin charset=UTF-8
        headers: { 'X-Requested-With': 'XMLHttpRequest' }, 
        data: { 
          csrf_dev_ehweb: csrfHash,
          search: "prueba"
        },         
          success: function(data){
            console.log(data);
          }         
        });

    }
      
    

    /*
    document.addEventListener("DOMContentLoaded", function() {
			// Datatables users
			var table = $("#datatables-users").DataTable({				
				responsive: true,
				order: [],
        processing: true,
        serverSide: true,
        ajax: {
          url: "usuario/ajaxList",
          type: "POST",
          contentType: "application/x-www-form-urlencoded",                
          data: function (data) {                        
          data[csrfName] = csrfHash;
            data.search = "test";
          }
        },        
				columnDefs: [{
					targets: 0,
					orderable: true,
					width: "32px"
				}],
				layout: {
					topStart: null,
					topEnd: null,
					bottomStart: 'info',
					bottomEnd: 'paging'
				}
			});

      table.on('xhr', function() {
        var json = table.ajax.json();
        csrfHash = json.csrfHash; // Asume que tu respuesta incluye el nuevo token CSRF
      });

			$("#datatables-users-search").keyup(function() {
				$("#datatables-users").DataTable().search($(this).val()).draw();
			});


		});*/
  </script>
<?php $this->endSection(); ?>   
