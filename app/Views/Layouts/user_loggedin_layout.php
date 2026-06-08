<?php
/**
 * Layout general para todas las vistas generales del sistema con usuario logueado(no clientes)
 * 
 * Incluye el sidebar, topbar y footer con el template core de bootstrap appstack
 */
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light" data-layout="fluid" data-sidebar-theme="dark" data-sidebar-position="left" data-sidebar-behavior="sticky">
<head>
  <!-- Aplicar tema guardado antes del render (evita flash) -->
  <script>(function(){var t=localStorage.getItem('appstack-theme');if(t)document.documentElement.setAttribute('data-bs-theme',t);})();</script>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= $this->renderSection('title') ?></title>

  <!-- Bootstrap CSS template core -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
  <link class="js-stylesheet" href="<?php echo base_url(); ?>bootstrap/css/app.css" rel="stylesheet">

  <!--External css -->
  <?= $this->renderSection('pageStyles') ?>
  <!-- Render initial scripts (header required scripts) -->
  <?= $this->renderSection('pageHeaderScripts') ?>  
</head>

<body >
  <div class="wrapper">
    <!-- sidebar -->
    <?php 
      echo $this->include('Partials/loggedin_sidebar');     
    ?>

    <div class="main">
      <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">
      <!-- main content y topbar -->
      <?php 
        echo $this->include('Partials/loggedin_topbar');         
        $this->renderSection('main');//renderizamos el contenido principal
        echo $this->include('Partials/loggedin_footer');
      ?>    
    </div>  
  </div>  
</body>

<!-- Bootstrap app.js -->
<script src="<?php echo base_url(); ?>bootstrap/js/app.js"></script>
<script src="<?php echo base_url(); ?>bootstrap/assets/tools.js"></script>

<!-- Alertas Globales Conectadas al Controlador -->
<?= $this->include('Partials/global_alerts') ?>

<?php 
  //Render final scripts (footer required scripts)
  $this->renderSection('pageFooterScripts');
?>

</html>