<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
  $routes->get('/', 'Home::index'); //acceso principal 
  service('auth')->routes($routes); //rutas de autenticacion shield (autenticacion de usuarios)

  
  //grupo de rutas para el login, pantalla de acceso login, y logout
  /*
  $routes->group('acceso', function($routes)
  {    
    $routes->get('/', 'Login\Login::index');//ruta para el login del admin
    $routes->get('login', 'Login\Login::index',['as' => 'usuario.login']);//ruta para el formulario de login
    $routes->post('login_post', 'Login\Login::login_post', ['as'  => 'usuario.login_post']);//ruta para el login del admin 
    $routes->get('logout', 'Login\Login::logout', ['as'  => 'usuario.logout']);//ruta para el logout del admin    
  });*/

  //grupo de rutas para el admin con sesion iniciada, al dashboard y secciones del administrador genericas
  /*
  $routes->group('dashboard', function($routes)
  {
    $routes->get('/', 'Dashboard\Home::index');//ruta para el dashboard del admin
    $routes->get('dashboard', 'Dashboard\Home::index', ['as'  => 'usuario.dashboard']);//ruta para el dashboard del admin      
    //ruta para errores 
    $routes->get('error', 'Dashboard\Home::error', ['as'  => 'usuario.error']);  
  });*/
  
  //presenter de rutas de usuario para consumir desde el navegador. Cuenta con el filtro 'AdminFilter' para que solo el admin pueda acceder y el filtro 'PermissionFilter' para que solo el usuario pueda acceder dependiendo de sus permisos
  //$routes->presenter('usuario', ['controller' => 'Usuarios\Usuario', 'filter' => ['AdminFilter', 'PermissionFilter']] );  
  /*
  $routes->group('usuario', function($routes)
  {  
    $routes->post('ajaxList', 'Usuarios\Usuario::ajaxList');     
    //restful routes with presenter          
  });
  $routes->presenter('usuario', ['controller' => 'Usuarios\Usuario', 'filter' => ['AdminFilter', 'PermissionFilter']] );

*/
  /*
  $routes->group('usuario', function($routes)
  {
    //generamos todas las rutas equivalentes al presenter de usuario con el controlador 'Usuarios\Usuario' y el filtro 'PermissionFilter' con 2 parametros, para que solo el usuario pueda acceder dependiendo de sus permisos
    $routes->get('/','Usuarios\Usuario::index', ['filter' => ['PermissionFilter:usuario,consultar']]);
    $routes->get('show/(.*)', 'Usuarios\Usuario::show/$1', ['filter' => ['PermissionFilter:usuario,consultar']]);//ruta para el dashboard del admin
    $routes->get('new', 'Usuarios\Usuario::new', ['filter' => ['PermissionFilter:usuario,agregar']]);
    $routes->get('edit/(.*)', 'Usuarios\Usuario::edit/$1', ['filter' => ['PermissionFilter:usuario,editar']]);
    $routes->get('remove/(.*)', 'Usuarios\Usuario::remove/$1', ['filter' => ['PermissionFilter:usuario,editar']]);
    $routes->get('/(.*)', 'Usuarios\Usuario::show/$1', ['filter' => ['PermissionFilter:usuario,consultar']]);

    $routes->post('create', 'Usuarios\Usuario::create', ['filter' => ['PermissionFilter:usuario,agregar']]);
    $routes->post('update/(.*)', 'Usuarios\Usuario::update/$1', ['filter' => ['PermissionFilter:usuario,editar']]);
    $routes->post('delete/(.*)', 'Usuarios\Usuario::delete/$1', ['filter' => ['PermissionFilter:usuario,editar']]);
    $routes->post('/', 'Usuarios\Usuario::create', ['filter' => ['PermissionFilter:usuario,agregar']]);   
  n
  }); */
  


 //nos permite organizar nuestras rutas en grupos
  /*
 $routes->group('dashboard', function($routes)
 {

   $routes->get('users', 'Dashboard::users');
    $routes->get('posts', 'Dashboard::posts');
    $routes->get('comments', 'Dashboard::comments');
    $routes->get('/', 'Pelicula::index');
    $routes->presenter('pelicula');// para api rest ('api/photo');
    $routes->get('test', 'Pelicula::test',['as' => 'pelicula.test']);   

 });*/

//$routes->presenter('home');// para consumir desde el navegador

//$routes->resource('home');// para api rest ('api/photo');

/*$routes->presenter('pelicula', [
  'controller' => 'Pelicula',
]);// para consumir desde el navegador ('admin/photos');
*/


/*
$routes->get('/peliculas', 'PeliculaController::index');
$routes->get('/peliculas/new', 'PeliculaController::create');

service('auth')->routes($routes);
$routes->get('/peliculas/edit/(:num)', 'PeliculaController::create/$1');*/


