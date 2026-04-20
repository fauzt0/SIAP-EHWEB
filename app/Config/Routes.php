<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index'); //acceso principal  

//verificamos la variable de entorno, en caso de ser produccion, no mostramos la ruta de ejemplo
if (ENVIRONMENT !== 'production') {
  $routes->get('example_user', 'Users\ExampleController::index'); //ejemplo (eliminar en produccion)    
}




//rutas de autenticacion shield (autenticacion de usuarios administradores)   
$routes->group('nat', function ($routes) { //equivalente a /admin o /erp 
  service('auth')->routes($routes);  //rutas de autenticacion de shield 

  // subgrupo protegido por inicio de sesion obligatorio
  $routes->group('', ['filter' => 'session'], function ($routes) {

    //rutas para los dashboard
    $routes->get('/', 'Dashboard\Home::index', ['as' => 'dashboard.index']); //acceso principal            

    //rutas de administracion de usuarios
    $routes->group('user', function ($routes) {//agrupamos todas las rutas de administracion de usuarios en un solo grupo
      $routes->get('/', 'Users\UserController::index', ['as' => 'users.list', 'filter' => 'permission:admin.manage-users']); //acceso principal            
      $routes->get('list', 'Users\UserController::index', ['filter' => 'permission:admin.manage-users']); //acceso principal      
      $routes->post('list_ajax', 'Users\UserController::list_ajax', ['as' => 'users.list_ajax', 'filter' => 'permission:admin.manage-users']); //acceso principal 

      $routes->post('create', 'Users\UserController::create', ['as' => 'user.create', 'filter' => 'permission:users.create']); //crear nuevo usuario (peticion de form o ajax)
      $routes->get('show/(:num)', 'Users\UserController::show/$1', ['as' => 'user.show', 'filter' => 'permission:users.view']);//ruta para mostrar los detalles del usuario (nos permitira ver el perfil y editarlo)                 
      $routes->get('show_ajax/(:num)', 'Users\UserController::show_ajax/$1', ['as' => 'user.show_ajax', 'filter' => 'permission:users.view']); // datos del usuario en JSON para el offcanvas
      $routes->post('update/(:num)', 'Users\UserController::update/$1', ['as' => 'user.update', 'filter' => 'permission:users.edit']);
      $routes->post('delete/(:num)', 'Users\UserController::delete/$1', ['as' => 'user.delete', 'filter' => 'permission:users.delete']); // soft delete
      $routes->post('restore/(:num)', 'Users\UserController::restore/$1', ['as' => 'user.restore', 'filter' => 'permission:users.edit']); // restaurar usuario

      // User Activity
      $routes->get('activity/([0-9]+)', 'Users\UserController::activity/$1', ['as' => 'user.activity', 'filter' => 'permission:users.view']);
      $routes->post('activity_ajax/([0-9]+)', 'Users\UserController::activity_ajax/$1', ['as' => 'user.activity_ajax', 'filter' => 'permission:users.view']);

      // User Permissions
      $routes->get('permissions/([0-9]+)', 'Users\UserController::permissions/$1', ['as' => 'user.permissions', 'filter' => 'permission:admin.manage-users']);
      $routes->post('permissions_save/([0-9]+)', 'Users\UserController::permissions_save/$1', ['as' => 'user.permissions_save', 'filter' => 'permission:admin.manage-users']);

      $routes->get('addPermission/(:num)/', 'Users\UserController::addPermission/$1', ['as' => 'user.permission']); //agregamos el permiso beta.access al usuario', 'Users\UserController::addPermission', ['as'  => 'user.permission']);

      // Perfil del usuario en sesión
      $routes->get('profile', 'Users\UserController::profile', ['as' => 'user.profile']);
      $routes->post('profile_save', 'Users\UserController::profile_save', ['as' => 'user.profile_save']);

      // Catálogo de Roles y Permisos
      $routes->get('roles', 'Users\RoleController::index', ['as' => 'users.roles', 'filter' => 'permission:admin.manage-users']);

    });//fin del grupo user

  });//fin del grupo protegido por sesion

});


/*
$routes->group('user', function($routes)
{   
  //shield routes

  //Dashboards del erp
  //rutas de administracion de usuario
  //$routes->presenter('user',  ['controller' => 'Users\UserController', 'only' => 'index,new,create', 'as'  => 'usr' ] );        


  //$routes->get('new', 'Usuarios\Usuario::new', ['as'  => 'usuario.new']);
  //$routes->get('/', 'Usuarios\Usuario::listar_usuarios'); //acceso principal 
  //$routes->get('show/(:num)', 'Usuarios\Usuario::show/$1', ['as'  => 'usuario.show' ] );//ruta para el dashboard del admin    
  //$routes->get('new', 'Usuarios\Usuario::new', ['as'  => 'usuario.new']);
  //$routes->get('edit/(:num)', 'Usuarios\Usuario::edit/$1', ['as'  => 'usuario.edit']);
  //$routes->get('remove/(:num)', 'Usuarios\Usuario::remove/$1', ['as'  => 'usuario.remove']);
  //$routes->get('/(.*)', 'Usuarios\Usuario::show/$1', ['as'  => 'usuario.show']);
  //$routes->get('create', 'Usuarios\Usuario::create', ['as'  => 'usuario.create']);
  //$routes->post('update/(:num)', 'Usuarios\Usuario::update/$1', ['as'  => 'usuario.update']);
  //$routes->post('delete/(:num)', 'Usuarios\Usuario::delete/$1', ['as'  => 'usuario.delete']);

});
*/



//$routes->presenter('home');// para consumir desde el navegador

//$routes->resource('home');// para api rest ('api/photo');