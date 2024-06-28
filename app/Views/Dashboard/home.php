<?php $this->extend('Layouts/loggedin') ?>     

<?php $this->section('contenido') ?>  
  <main class="content">
    <div class="container-fluid p-0">
      Contenido del dashboard
    </div>
  </main>    
<?php $this->endSection() ?>


<?php $this->section('scripts'); ?>
  <script>
    //jquery test
    $(document).ready(function(){
      console.log("jquery ok");
      
    });
  </script>
<?php $this->endSection(); ?>   
