<!DOCTYPE html>
<html lang="es" data-bs-theme="default" data-layout="fluid" data-sidebar-theme="dark" data-sidebar-position="left" data-sidebar-behavior="sticky">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>EHWEB - Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
  <link class="js-stylesheet" href="<?php echo base_url(); ?>bootstrap/css/app.css" rel="stylesheet">
</head>
<body>
  
  <div class="main d-flex justify-content-center w-100">
    <main class="content d-flex p-0">
      <div class="container d-flex flex-column">
        <div class="row h-100">
          <div class="col-sm-10 col-md-8 col-lg-6 col-xl-5 mx-auto d-table h-100">
            <div class="d-table-cell align-middle">

              <div class="text-center mt-4">
								<h1 class="h2">Bienvenid@!</h1>
								<p class="lead">
									Ingresa a tu cuenta para continuar
								</p>
							</div>

              <div class="card">
                <div class="card-body">
                  <div class="m-sm-3">
                    <div class="text-center">
                      <img src="<?php echo base_url(); ?>bootstrap/img/brands/logo.png" alt="Chris Wood" class="img-fluid rounded-circle" width="132" height="132" />
                      <?php $this->renderSection('contenido'); ?>

                    </div>
                  </div>
                </div>
              </div>   

            </div>
          </div>
        </div>
      </div>    
    </main>
  </div>
  
</body>

<!-- Bootstrap app.js -->
<script src="<?php echo base_url(); ?>bootstrap/js/app.js"></script>
</html>