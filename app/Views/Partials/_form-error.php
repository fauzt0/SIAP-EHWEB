<?php 
//verificamos si tenemos un mensaje de error en la session
if(session()->has('errors')): ?>
  

<div class="alert alert-danger alert-outline-coloured alert-dismissible" role="alert">
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  <div class="alert-icon">
    <i class="far fa-fw fa-bell"></i>
  </div>
  <div class="alert-message">
    <?php 
    //obtenemos el mensaje de error
    $errors = session('errors');
    //mostramos el error
    echo $errors;
    ?>
  </div>
</div>

<?php 
//end if
endif;
?>