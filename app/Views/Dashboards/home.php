<?php $this->extend($layout) ?>     

<?php $this->section('title') ?>
<?php 
  //obtenemos  el titulo desde el viewData
  //opcion 1: $title = $this->viewData['pageTitle'];
  //echo $title;
  //opcion 2: echo $pageTitle;
  echo $pageTitle;
?>
<?php $this->endSection() ?>

<?php $this->section('main') ?>  

  <main class="content">
    <div class="container-fluid p-0">
      Contenido del dashboard
    </div>
  </main>    
<?php $this->endSection() ?>


<?php $this->section('pageFooterScripts'); ?>
  <script>
    //jquery test
    $(document).ready(function(){
      console.log("jquery ok");
      
    });

    document.addEventListener("DOMContentLoaded", function() {
      console.log("Dom content loaded");

    });

  </script>
<?php $this->endSection(); ?>   
