<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear Pelicula</title>
</head>
<body>

  <form action="/pelicula/create" method="post">
    <?php 
      echo view('pelicula/_form',['op'=>'Crear']); ?>
  </form>  

</body>
</html>