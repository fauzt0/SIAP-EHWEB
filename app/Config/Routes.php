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

    // -----------------------------------------------------------------------
    // Rutas del módulo de Recursos Humanos
    // -----------------------------------------------------------------------
    $routes->group('hr', ['filter' => 'permission:hr.access'], function ($routes) {

      // Vista principal: Listado de trabajadores
      $routes->get('/', 'HR\WorkerController::index', ['as' => 'hr.index']);
      $routes->get('workers', 'HR\WorkerController::index', ['as' => 'hr.workers']);
      $routes->post('workers_ajax', 'HR\WorkerController::workers_ajax', ['as' => 'hr.workers_ajax']);

      // Perfil de trabajador (Offcanvas + vista completa)
      $routes->get('worker/show_ajax/(:num)', 'HR\WorkerController::show_ajax/$1', ['as' => 'hr.worker.show_ajax', 'filter' => 'permission:hr.view']);
      
      // Gestión de Incidencias (Fase 2)
      $routes->post('worker/incidence/add', 'HR\WorkerIncidenceController::addIncidenceAjax', ['as' => 'hr.worker.incidence.add', 'filter' => 'permission:hr.edit']);
      $routes->post('worker/incidence/update_status', 'HR\WorkerIncidenceController::updateIncidenceStatusAjax', ['as' => 'hr.worker.incidence.update', 'filter' => 'permission:hr.edit']);
      
      // Gestión de Vacaciones (Fase 3)
      $routes->get('worker/vacations/global', 'HR\WorkerVacationController::getGlobalVacations', ['as' => 'hr.worker.vacations.global', 'filter' => 'permission:hr.view']);
      $routes->get('worker/vacations/get/(:num)', 'HR\WorkerVacationController::getVacations/$1', ['as' => 'hr.worker.vacations.get', 'filter' => 'permission:hr.view']);
      $routes->post('worker/vacations/add', 'HR\WorkerVacationController::addVacationAjax', ['as' => 'hr.worker.vacations.add', 'filter' => 'permission:hr.edit']);
      $routes->post('worker/vacations/update_status/(:num)', 'HR\WorkerVacationController::updateStatusAjax/$1', ['as' => 'hr.worker.vacations.update_status', 'filter' => 'permission:hr.edit']);
      $routes->post('worker/vacations/delete/(:num)', 'HR\WorkerVacationController::deleteAjax/$1', ['as' => 'hr.worker.vacations.delete', 'filter' => 'permission:hr.edit']);

      // Finiquitos y Bajas (Fase 4)
      $routes->get('worker/settlement/calculate/(:num)', 'HR\WorkerSettlementController::calculateAjax/$1', ['as' => 'hr.worker.settlement.calculate', 'filter' => 'permission:hr.view']);
      $routes->post('worker/settlement/process_baja/(:num)', 'HR\WorkerSettlementController::processBajaAjax/$1', ['as' => 'hr.worker.settlement.process_baja', 'filter' => 'permission:hr.edit']);

      $routes->get('documents/download/(:num)', 'HR\WorkerDocumentController::downloadDocument/$1', ['as' => 'hr.documents.download', 'filter' => 'permission:hr.view']);
      $routes->post('documents/delete/(:num)', 'HR\WorkerDocumentController::deleteDocument/$1', ['as' => 'hr.documents.delete', 'filter' => 'permission:hr.edit']);
      $routes->post('documents/restore/(:num)', 'HR\WorkerDocumentController::restoreDocument/$1', ['as' => 'hr.documents.restore', 'filter' => 'permission:hr.edit']);
      $routes->post('documents/update/(:num)', 'HR\WorkerDocumentController::updateDocument/$1', ['as' => 'hr.documents.update', 'filter' => 'permission:hr.edit']);

      // Alta de trabajador
      $routes->get('worker/new', 'HR\WorkerController::new_worker', ['as' => 'hr.worker.new', 'filter' => 'permission:hr.create']);
      $routes->post('worker/create', 'HR\WorkerController::create', ['as' => 'hr.worker.create', 'filter' => 'permission:hr.create']);

      // Edición de trabajador
      $routes->get('worker/edit/(:num)', 'HR\WorkerController::edit/$1', ['as' => 'hr.worker.edit', 'filter' => 'permission:hr.edit']);
      $routes->post('worker/update/(:num)', 'HR\WorkerController::update/$1', ['as' => 'hr.worker.update', 'filter' => 'permission:hr.edit']);
      $routes->post('worker/add_document_ajax/(:num)', 'HR\WorkerController::addDocumentAjax/$1', ['as' => 'hr.worker.add_document_ajax', 'filter' => 'permission:hr.edit']);

      // Eliminación y restauración (Soft Delete)
      $routes->post('worker/delete/(:num)', 'HR\WorkerController::delete/$1', ['as' => 'hr.worker.delete', 'filter' => 'permission:hr.delete']);
      $routes->post('worker/restore/(:num)', 'HR\WorkerController::restore/$1', ['as' => 'hr.worker.restore', 'filter' => 'permission:hr.edit']);

      // Catálogos (Departamentos y Puestos)
      $routes->group('catalogs', function ($routes) {
          $routes->get('/', 'HR\CatalogController::index', ['as' => 'hr.catalogs.index']);
          
          // Departamentos
          $routes->post('departments/save', 'HR\CatalogController::saveDepartment', ['as' => 'hr.catalogs.department.save']);
          $routes->post('departments/delete/(:num)', 'HR\CatalogController::deleteDepartment/$1', ['as' => 'hr.catalogs.department.delete']);
          
          // Puestos
          $routes->post('jobs/save', 'HR\CatalogController::saveJob', ['as' => 'hr.catalogs.job.save']);
          $routes->post('jobs/delete/(:num)', 'HR\CatalogController::deleteJob/$1', ['as' => 'hr.catalogs.job.delete']);
      });

      // ── Contratos y Plantillas ──────────────────────────────────────────
      $routes->group('contracts', function($routes) {
          // Plantillas
          $routes->get('templates', 'HR\ContractTemplateController::index', ['as' => 'hr.contracts.templates.index']);
          $routes->post('templates/list-ajax', 'HR\ContractTemplateController::listAjax', ['as' => 'hr.contracts.templates.listAjax']);
          $routes->get('templates/new', 'HR\ContractTemplateController::form', ['as' => 'hr.contracts.templates.new']);
          $routes->get('templates/edit/(:num)', 'HR\ContractTemplateController::form/$1', ['as' => 'hr.contracts.templates.edit']);
          $routes->get('templates/get-base-model', 'HR\ContractTemplateController::getBaseModel', ['as' => 'hr.contracts.templates.getBaseModel']);
          $routes->post('templates/save', 'HR\ContractTemplateController::save', ['as' => 'hr.contracts.templates.save']);
          $routes->post('templates/delete/(:num)', 'HR\ContractTemplateController::delete/$1', ['as' => 'hr.contracts.templates.delete']);

          // Descarga y Gestión de Contratos del Trabajador
          $routes->get('download/(:num)', 'HR\WorkerContractController::downloadContract/$1', ['as' => 'hr.contracts.download']);
          $routes->get('history/(:num)', 'HR\WorkerContractController::contractHistory/$1', ['as' => 'hr.contracts.history']);
          $routes->get('get_contracts/(:num)', 'HR\WorkerContractController::getContracts/$1');
          
          // Generación Manual Avanzada
          $routes->get('generate/(:num)', 'HR\WorkerContractController::generateContractView/$1', ['as' => 'hr.contracts.generate']);
          $routes->get('generate/(:num)/(:num)', 'HR\WorkerContractController::generateContractView/$1/$2', ['as' => 'hr.contracts.generate_edit']);
          $routes->post('render-template', 'HR\WorkerContractController::getRenderedTemplateAjax', ['as' => 'hr.contracts.render_template']);
          $routes->post('preview-raw', 'HR\WorkerContractController::previewPdfRaw', ['as' => 'hr.contracts.preview_raw']);
          $routes->post('save-manual/(:num)', 'HR\WorkerContractController::saveManualContract/$1', ['as' => 'hr.contracts.save_manual']);
      });

    }); // fin del grupo hr

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