<!DOCTYPE html>
<html lang="en">
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
    

    Formulario
    <form action="https://sandbox.especialistashosting.com/nat/user/update/22" method="post">
      <input type="text" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="token" />
      <!-- Otros campos del formulario aquí -->
      <button type="submit">Submit</button>
    </form>

      <script>
        
        const tokenInput  = document.getElementById('token');
        //obtenemos el nombre y el valor del token de  document.getElementById('token')
        var tokenName = tokenInput.getAttribute('name');
        let tokenValue= tokenInput.getAttribute('value');
         
       
        console.log('Token Name (from input):', tokenName);
        console.log('Token Value (from input):', tokenValue);

         tokenName = '<?= csrf_header(); ?>';
        /*var tokenValue= '<?= csrf_hash(); ?>';
*/
      
        fetch('https://sandbox.especialistashosting.com/nat/user/update/122', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            // Incluimos el token CSRF en el header generados en tokenName y tokenValue
            [tokenName]: tokenValue
            
          },
          body: JSON.stringify({
            'sampleData': 'test',
           })
        })        
        .then(response =>{
          const newToken = response.headers.get(tokenName); //obtenemos el nuevo token  CSRF del header de respuesta
          console.log('New Token Value from Response Header:', newToken);
          return response.text()
        })//recibimos la respuesta del servidor en formato texto
        //mostramos el resultado en la consola, tamibien verificamos que del header de respuesta, tenemos el nuevo token
       .then(data => console.log(data))
       .catch(error => console.error('Error:', error));
        

      </script>





        <?php //helper('form'); ?>
    <?php //echo form_open('nat/user/update/122') ?>
        <!-- Other form fields here 
        <button type="submit">Submit</button>-->
    <?php //echo  form_close() ?>


    
    <br>
    BreadCrumb: <?php echo $breadCrumb; ?>
    <h3><?php echo $response['responseMessage']; ?></h3>
    <!-- Mostramos los detalles del usuario -->
    <?php if(!$response['isEmpty']):?>
      <section>
        
        <table border="1" >
          <tr>
            <td>Id:</td>
            <td><?php echo $response['userDetails']->id; ?></td>
          </tr>
          <tr>
            <td>Username:</td>
            <td><?php echo $response['userDetails']->username; ?></td>
          </tr>
          <tr>
            <td>Email</td>
            <td><?php echo $response['userDetails']->email; ?></td>
          </tr>
          <tr>
            <td>Nombres:</td>
            <td><?php echo $response['userDetails']->first_name; ?></td>
          </tr>
          <tr>
            <td>Apellido:</td>
            <td><?php echo $response['userDetails']->last_name; ?></td>
          </tr>
          <tr>
            <td>Avatar:</td>
            <td><?php echo $response['userDetails']->type; ?></td>
          </tr>
          <tr>
            <td>Creado:</td>
            <td><?php echo $response['userDetails']->created_at; ?></td>
          </tr>
          <tr>
            <td>Actualizado:</td>
            <td><?php echo $response['userDetails']->updated_at; ?></td>
          </tr>
          <tr>
            <td>Eliminado:</td>
            <td><?php echo $response['userDetails']->deleted_at; ?></td>
          </tr>
        </table>

                    
        <h4>Grupos del usuario</h4>
        <?php 
          //echo var_dump($response['userDetails']->getGroups());
          foreach ($response['userDetails']->getGroups() as $group) {
            echo $group . " || ";
          }
        ?>

        <h4>Permisos del usuario</h4>
        <?php echo var_dump($response['userDetails']->getPermissions()); ?>

        <hr>

        <h4>Listado de grupos disponibles en el sistema</h4>
        <?php //echo var_dump($response['groups']); ?>      
        <?php foreach ($response['groups'] as $key => $value) : ?>
          <strong><?= $key; ?></strong>  ||  
          <?= $value['title']; ?> -
          <?= $value['description']; ?> 
          <br>     
        <?php endforeach; ?>

        <h4>Listado de permisos disponibles en el sistema</h4>
        <?php //echo var_dump($response['permissions']); ?>
          
        <?php foreach($response['permissions'] as $key => $value) : ?>
          
          <strong><?= $key; ?></strong> 
          <?php //agregamos una marca o color o check, en caso de que el permiso este activo
            if($response['userDetails']->can($key)){
              echo "<span style='color: green;'>✓</span>";
            }        
          ?>

          ||         
        <?php endforeach; ?>
        


      </section>    
    <?php endif;?>
    </body>
</html>