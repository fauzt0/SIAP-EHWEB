<!DOCTYPE html>
<html>
<head>
    <title>Película Listado</title>
    <style>
      table {
        width: 100%;
        border-collapse: collapse;
      }
      table, th, td {
        border: 1px solid black;
      }
      th, td {
        padding: 15px;
        text-align: left;
      }
      table#t01 tr:nth-child(even) {
        background-color: #eee;
      }
      table#t01 tr:nth-child(odd) {
       background-color:#fff;
      }
      table#t01 th {
        background-color: #f1f1c1;
      }
    </style>
</head>
<body>
  <h3>Listado de peliculas desde la base de datos</h3>

    <a href="/pelicula/new">Crear</a>
    <table>
      <tr>
        <th>Titulo</th>
        <th>Descripción</th>
        <th>Editar</th>
      </tr>
      
      <?php
      foreach($peliculas as $pelicula){
        echo "<tr>";
        echo "<td>".$pelicula['titulo']."</td>";
        echo "<td>".$pelicula['descripcion']."</td>";        
        echo "<td><a href='/pelicula/show/".$pelicula['id']."'>Show</a> ";
        echo "<a href='/pelicula/edit/".$pelicula['id']."'>Edit</a> ";
        echo "<form action='/pelicula/delete/".$pelicula['id']."' method='post'> <button type='submit'value='Delete'>Delete</button></td></form>";

        echo "</tr>";
      }
      ?>
       
    </table>
    
  </body>
</html>
