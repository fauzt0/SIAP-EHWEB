<?php $this->extend('Layouts/login') ?>     

<?php $this->section('contenido');?>
<?php
  $attributes = [
    'class' => 'form-signin', 
    'id' => 'login-form',
    'method' => 'post',    
  ]; 

  echo form_open(route_to('usuario.login_post') , $attributes); 
?>

<form>
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input class="form-control form-control-lg" type="email" name="email" placeholder="Enter your email" />
  </div>
  <div class="mb-3">
    <label class="form-label">Password</label>
    <input class="form-control form-control-lg" type="password" name="password" placeholder="Enter your password" />
    <small>
      <a href="pages-reset-password.html">Olvidé la contraseña</a>
    </small>
  </div>
  <div>
    <div class="form-check align-items-center">
      <input id="customControlInline" type="checkbox" class="form-check-input" value="remember-me" name="remember-me" checked>
      <label class="form-check-label text-small" for="customControlInline">Recordarme</label>
    </div>
  </div>
  
  <?php echo view('Partials/_form-error') ?>
  <div class="d-grid gap-2 mt-3">
    <button type="submit" class="btn btn-lg btn-primary">Ingresar</button>
  </div>
</form>

<?php $this->endSection(); ?>