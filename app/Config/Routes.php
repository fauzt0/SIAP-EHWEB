<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

//$routes->presenter('home');// para consumir desde el navegador
$routes->get('/', 'Home::index');
//$routes->resource('home');// para api rest ('api/photo');
$routes->presenter('pelicula');// para api rest ('api/photo');
/*$routes->presenter('pelicula', [
  'controller' => 'Pelicula',
]);// para consumir desde el navegador ('admin/photos');
*/


/*
$routes->get('/peliculas', 'PeliculaController::index');
$routes->get('/peliculas/new', 'PeliculaController::create');
$routes->get('/peliculas/edit/(:num)', 'PeliculaController::create/$1');*/


