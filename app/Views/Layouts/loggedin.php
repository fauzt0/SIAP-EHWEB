<!DOCTYPE html>
<html lang="es" data-bs-theme="light" data-layout="fluid" data-sidebar-theme="dark" data-sidebar-position="left" data-sidebar-behavior="sticky">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>EHWEB - PANEL</title>

  <!-- Bootstrap CSS -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
  <link class="js-stylesheet" href="<?php echo base_url(); ?>bootstrap/css/app.css" rel="stylesheet">
  
</head>

<body >
  <div class="wrapper">
    <!-- sidebar -->
    <?php 
    echo $this->include('Partials/loggedin_sidebar'); 
    
    ?>

    <div class="main">
      <!-- main content y topbar -->
      <?php 
        echo $this->include('Partials/loggedin_topbar'); 
        $this->renderSection('contenido');
        echo $this->include('Partials/loggedin_footer');
      ?>    
    </div>  
  </div>  
</body>

<!-- Bootstrap app.js -->
<script src="<?php echo base_url(); ?>bootstrap/js/app.js"></script>
<?php 
  //renderizamos los scripts requeridos
  $this->renderSection('scripts');
?>
</html>