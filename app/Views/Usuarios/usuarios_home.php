<?php $this->extend('Layouts/loggedin') ?>     

<?php 
//incluimos todo el head, meta, estilos, etc
$this->section('head'); ?>
  <?php echo csrf_meta("csrf-field"); ?>
<?php $this->endSection() ?>


<?php 
//incluimos todo el contenido principal
$this->section('contenido') ?>  
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



<?php 
//incluimos todos los scripts de esta vista
$this->section('scripts'); ?>
  <script src="<?php echo base_url(); ?>bootstrap/js/manager-csrf.js" type="text/javascript""></script>


  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
  <script>    

    
    function tester_old(){          

      $.ajax({
        type: "POST",        
        url: "usuario/ajaxList",
        contentType: "application/x-www-form-urlencoded; charset=UTF-8", // Sin charset=UTF-8
        dataType: "json",
        headers: {  //agregamos el header con el csrf token
          'X-Requested-With': 'XMLHttpRequest', // Especificamos que es una peticion ajax          
         }, 
        data: {           
          search: "prueba",           
        },          
          success: function(response){
            console.log(response);            
          },
          error: function( xhr, status, error ){
            console.log(error);
          }
        });
    }

    
    async function tester() {      
      const response = await fetch('usuario/ajaxList', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',  
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: 'search=prueba fetch',
      });
      const data = await response.json();
      console.log(data);
    }


    function tester2(){
      fetch('usuario/ajaxList', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',  
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: 'search=prueba fetch',
        }).then(function(response) {
          return response.json();
        }).then(function(data) {
          console.log(data);  
        }).catch(function(error) {
          console.log(error);
        });
        
      }

      function tester3(){
        const data = {
          param1: 'value1',
          param2: 'value2'
        };
      //fetch por get
        fetch('usuario/ajaxList', {
          method: 'GET',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',  
            'X-Requested-With': 'XMLHttpRequest',
          },          
          }).then(function(response) {
            return response.json();
          }).then(function(data) {
            console.log(data);  
          }).catch(function(error) {
            console.log(error);
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
