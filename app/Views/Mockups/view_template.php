<?php $this->extend($layout); //layout base ?>

<?php $this->section('title'); ?>
<?php 
  //obtenemos  el titulo desde el viewData
  //opcion 1: $title = $this->viewData['pageTitle'];
  //echo $title;
  //opcion 2: echo $pageTitle;
  echo $pageTitle;
?>
<?php $this->endSection(); ?>


<?php $this->section('pageStyles'); ?>
<style>
    /*estilos propios*/
    body {  }   
</style>
<?php $this->endSection(); ?>

<?php $this->section('pageHeaderScripts'); ?>
    <script>
    //jquery test
    $(document).ready(function(){ console.log("jquery ok from head_scripts"); });   
  </script>
<?php $this->endSection(); ?>


<?php 
//renderizamos el contenido principal
$this->section('main') ?>  

  <main class="content">
    <div class="container-fluid p-0">
      Contenido de la vista
    </div>
  </main>
<?php $this->endSection() ?>


<?php $this->section('pageFooterScripts'); ?>
  <script>
    $(document).ready(function(){ console.log("jquery ok footer"); });
  </script>
<?php $this->endSection(); ?>   
