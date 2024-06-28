<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
  $routes->get('/', 'Home::index'); //acceso principal o
   
  //grupo de rutas para el login, pantalla de acceso login, y logout
  $routes->group('acceso', function($routes)
  {    
    $routes->get('/', 'Login\Login::index');//ruta para el login del admin
    $routes->get('login', 'Login\Login::index',['as' => 'usuario.login']);//ruta para el formulario de login
    $routes->post('login_post', 'Login\Login::login_post', ['as'  => 'usuario.login_post']);//ruta para el login del admin 
    $routes->get('logout', 'Login\Login::logout', ['as'  => 'usuario.logout']);//ruta para el logout del admin    
  });

  //grupo de rutas para el admin con sesion iniciada, al dashboard y secciones del administrador genericas
  $routes->group('dashboard', function($routes)
  {
    $routes->get('/', 'Dashboard\Home::index');//ruta para el dashboard del admin
    $routes->get('dashboard', 'Dashboard\Home::index', ['as'  => 'usuario.dashboard']);//ruta para el dashboard del admin        
  });

  $routes->group('usuario', function($routes)
  {
    $routes->get('/', 'Usuarios\Usuario::index');
    $routes->presenter('accion', ['controller' => 'Usuarios\Usuario' ]);    
  });

  






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
$routes->get('/peliculas/edit/(:num)', 'PeliculaController::create/$1');*/


