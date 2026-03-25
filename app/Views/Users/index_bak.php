<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo(esc($pageTitle)); ?></title>
</head>
<body>
  <header>
    <h1><?php echo(esc($headTitle)); ?></h1>    
  </header>  

  <?php   
    echo "<strong>Datos de respuesta del servidor:</strong>";
    echo '<br>';
    echo "Success: ". $success;  
    echo '<br>';
    echo "Error: ". $error;
    echo '<br>';
    echo "HTTP STATUS CODE:". $statusCode; //envi
    echo '<br>';
    echo $message;
    echo '<hr>';
  ?>
  
  <br>
  BreadCrumb: <?php echo $breadCrumb;   ?>
  
  

  <section>
    <h3><?php echo $response['responseMessage']; ?></h3>
  
    <?php  
    if(!$response['isEmpty']) {
      echo "Número de usuarios: ".$response['userCount']. "<hr>";       

      //mostramos el listado de usuarios almacenado en response['usersList']
      foreach ($response['usersList'] as $user) {
        echo "id: " . $user->id . "<br>";
        echo "username: " . $user->username . "<br>";        
        echo "first_name: " . $user->first_name . "<br>";
        echo "last_name: " . $user->last_name . "<br>";           
        echo "estado: ". ($user->active == 1? "Activo" : "Inactivo"). "<br>";              
        echo anchor(route_to('user.show', $user->id), 'Detalles');
        echo "<hr>";
      }           
    }   


    ?>
  </section>
  
</body>
</html>