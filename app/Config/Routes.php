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

    // ── Módulo de Organización (Perfil de Compañía y Sucursales) ──
    $routes->group('organization', ['filter' => 'permission:admin.manage-organization'], function ($routes) {
        $routes->get('profile', 'Organization\OrganizationController::profile', ['as' => 'organization.profile']);
        $routes->post('profile/update', 'Organization\OrganizationController::updateProfile', ['as' => 'organization.profile.update']);
        
        $routes->get('branches', 'Organization\OrganizationController::branches', ['as' => 'organization.branches']);
        $routes->post('branches/list_ajax', 'Organization\OrganizationController::list_ajax', ['as' => 'organization.branches_ajax']);
        $routes->get('branch/get_ajax/(:num)', 'Organization\OrganizationController::get_ajax/$1', ['as' => 'organization.branch.get_ajax']);
        $routes->get('branch/new', 'Organization\OrganizationController::createBranch', ['as' => 'organization.branch.new']);
        $routes->get('branch/edit/(:num)', 'Organization\OrganizationController::editBranch/$1', ['as' => 'organization.branch.edit']);
        $routes->post('branch/save', 'Organization\OrganizationController::saveBranch', ['as' => 'organization.branch.save']);
        $routes->post('branch/toggle_status/(:num)', 'Organization\OrganizationController::toggleBranchStatus/$1', ['as' => 'organization.branch.toggle_status']);
        $routes->post('branch/delete/(:num)', 'Organization\OrganizationController::deleteBranch/$1', ['as' => 'organization.branch.delete']);
    });

        // ── Módulo de Gestión de Proveedores ───────────────────────────
        $routes->group('suppliers', ['filter' => 'permission:catalog.access'], function ($routes) {

            // ── Proveedores (SupplierController) ───────────────────────
            $routes->get('/', 'Financial\SupplierController::index', ['as' => 'suppliers.index']);
            $routes->post('list_ajax', 'Financial\SupplierController::list_ajax', ['as' => 'suppliers.list_ajax']);
            $routes->get('select_ajax', 'Financial\SupplierController::select_ajax', ['as' => 'suppliers.select_ajax']);
            $routes->get('show/(:num)', 'Financial\SupplierController::show/$1', ['as' => 'suppliers.show']);
            $routes->get('get_ajax/(:num)', 'Financial\SupplierController::get_ajax/$1', ['as' => 'suppliers.get_ajax']);

            $routes->post('store', 'Financial\SupplierController::store', ['as' => 'suppliers.store', 'filter' => 'permission:catalog.manage-suppliers']);
            $routes->post('update/(:num)', 'Financial\SupplierController::update/$1', ['as' => 'suppliers.update', 'filter' => 'permission:catalog.manage-suppliers']);
            $routes->post('delete/(:num)', 'Financial\SupplierController::delete/$1', ['as' => 'suppliers.delete', 'filter' => 'permission:catalog.manage-suppliers']);
            $routes->post('restore/(:num)', 'Financial\SupplierController::restore/$1', ['as' => 'suppliers.restore', 'filter' => 'permission:catalog.manage-suppliers']);
            $routes->post('toggle_status/(:num)', 'Financial\SupplierController::toggle_status/$1', ['as' => 'suppliers.toggle_status', 'filter' => 'permission:catalog.manage-suppliers']);

            // Asociación de productos del catálogo al proveedor
            $routes->get('(:num)/products', 'Financial\SupplierController::get_linked_products/$1', ['as' => 'suppliers.products.list']);
            $routes->post('(:num)/products/link', 'Financial\SupplierController::link_product/$1', ['as' => 'suppliers.products.link', 'filter' => 'permission:catalog.manage-suppliers']);
            $routes->post('(:num)/products/unlink/(:num)', 'Financial\SupplierController::unlink_product/$1/$2', ['as' => 'suppliers.products.unlink', 'filter' => 'permission:catalog.manage-suppliers']);

            // ── Contactos (SupplierContactController) ──────────────────
            $routes->get('(:num)/contacts', 'Financial\SupplierContactController::list_ajax/$1', ['as' => 'suppliers.contacts.list']);
            $routes->get('contacts/get_ajax/(:num)', 'Financial\SupplierContactController::get_ajax/$1', ['as' => 'suppliers.contacts.get_ajax']);
            $routes->post('(:num)/contacts/store', 'Financial\SupplierContactController::store/$1', ['as' => 'suppliers.contacts.store', 'filter' => 'permission:catalog.manage-suppliers']);
            $routes->post('contacts/update/(:num)', 'Financial\SupplierContactController::update/$1', ['as' => 'suppliers.contacts.update', 'filter' => 'permission:catalog.manage-suppliers']);
            $routes->post('contacts/delete/(:num)', 'Financial\SupplierContactController::delete/$1', ['as' => 'suppliers.contacts.delete', 'filter' => 'permission:catalog.manage-suppliers']);
            $routes->post('contacts/set_primary/(:num)', 'Financial\SupplierContactController::set_primary/$1', ['as' => 'suppliers.contacts.set_primary', 'filter' => 'permission:catalog.manage-suppliers']);

            // ── Órdenes de Compra (PurchaseOrderController) ────────────
            $routes->get('purchase-orders', 'Financial\PurchaseOrderController::index', ['as' => 'suppliers.po.index']);
            $routes->post('purchase-orders/list_ajax', 'Financial\PurchaseOrderController::list_ajax', ['as' => 'suppliers.po.list_ajax']);
            $routes->get('purchase-orders/show/(:num)', 'Financial\PurchaseOrderController::show/$1', ['as' => 'suppliers.po.show']);
            $routes->get('purchase-orders/get_ajax/(:num)', 'Financial\PurchaseOrderController::get_ajax/$1', ['as' => 'suppliers.po.get_ajax']);
            $routes->get('purchase-orders/generate_po_number', 'Financial\PurchaseOrderController::generate_po_number', ['as' => 'suppliers.po.generate_number']);
            $routes->get('(:num)/purchase-orders', 'Financial\PurchaseOrderController::by_supplier/$1', ['as' => 'suppliers.po.by_supplier']);

            $routes->post('purchase-orders/store', 'Financial\PurchaseOrderController::store', ['as' => 'suppliers.po.store', 'filter' => 'permission:catalog.manage-suppliers']);
            $routes->post('purchase-orders/update/(:num)', 'Financial\PurchaseOrderController::update/$1', ['as' => 'suppliers.po.update', 'filter' => 'permission:catalog.manage-suppliers']);
            $routes->post('purchase-orders/update_status/(:num)', 'Financial\PurchaseOrderController::update_status/$1', ['as' => 'suppliers.po.update_status', 'filter' => 'permission:catalog.manage-suppliers']);
            $routes->post('purchase-orders/delete/(:num)', 'Financial\PurchaseOrderController::delete/$1', ['as' => 'suppliers.po.delete', 'filter' => 'permission:catalog.manage-suppliers']);
            $routes->post('purchase-orders/restore/(:num)', 'Financial\PurchaseOrderController::restore/$1', ['as' => 'suppliers.po.restore', 'filter' => 'permission:catalog.manage-suppliers']);

            // ── PDF Export — Purchase Order Download (§9) ─────────────────
            $routes->get('purchase-orders/download/(:num)', 'Financial\PurchaseOrderController::download/$1', ['as' => 'suppliers.po.download']);

            // ── Activity Timeline (Offcanvas) (§10) ──────────────────────
            $routes->get('(:num)/activity', 'Financial\SupplierController::activity/$1', ['as' => 'suppliers.activity']);

            // ── Recurring Expenses / Services Association (§11) ──────────
            $routes->get('(:num)/services', 'Financial\SupplierController::list_services/$1', ['as' => 'suppliers.services.list']);
            $routes->post('(:num)/services/store', 'Financial\SupplierController::store_service/$1', ['as' => 'suppliers.services.store', 'filter' => 'permission:catalog.manage-suppliers']);

        }); // fin del grupo suppliers

        // ── Catálogo de Productos y Servicios ──────────────────────────
    $routes->group('catalog', ['filter' => 'permission:catalog.access'], function ($routes) {
        
        // Categorías
        $routes->get('categories', 'Catalog\CatalogCategoryController::index', ['as' => 'catalog.categories']);
        $routes->post('categories/list_ajax', 'Catalog\CatalogCategoryController::categories_ajax', ['as' => 'catalog.categories_ajax']);
        $routes->get('categories/get_ajax/(:num)', 'Catalog\CatalogCategoryController::get_ajax/$1', ['as' => 'catalog.categories.get']);
        $routes->post('categories/save', 'Catalog\CatalogCategoryController::save_ajax', ['as' => 'catalog.categories.save', 'filter' => 'permission:catalog.manage']);
        $routes->post('categories/delete/(:num)', 'Catalog\CatalogCategoryController::delete_ajax/$1', ['as' => 'catalog.categories.delete', 'filter' => 'permission:catalog.manage']);
        $routes->post('categories/toggle_status/(:num)', 'Catalog\CatalogCategoryController::toggle_status_ajax/$1', ['as' => 'catalog.categories.toggle_status', 'filter' => 'permission:catalog.manage']);

        // Productos
        $routes->get('products', 'Catalog\CatalogProductController::index', ['as' => 'catalog.products']);
        $routes->post('products/list_ajax', 'Catalog\CatalogProductController::products_ajax', ['as' => 'catalog.products_ajax']);
        $routes->get('products/new', 'Catalog\CatalogProductController::create', ['as' => 'catalog.products.create', 'filter' => 'permission:catalog.manage']);
        $routes->post('products/store', 'Catalog\CatalogProductController::store', ['as' => 'catalog.products.store', 'filter' => 'permission:catalog.manage']);
        $routes->get('products/edit/(:num)', 'Catalog\CatalogProductController::edit/$1', ['as' => 'catalog.products.edit', 'filter' => 'permission:catalog.manage']);
        $routes->post('products/update/(:num)', 'Catalog\CatalogProductController::update/$1', ['as' => 'catalog.products.update', 'filter' => 'permission:catalog.manage']);
        $routes->post('products/delete/(:num)', 'Catalog\CatalogProductController::delete/$1', ['as' => 'catalog.products.delete', 'filter' => 'permission:catalog.manage']);
        $routes->post('products/restore/(:num)', 'Catalog\CatalogProductController::restore/$1', ['as' => 'catalog.products.restore', 'filter' => 'permission:catalog.manage']);
        $routes->post('products/toggle_status/(:num)', 'Catalog\CatalogProductController::toggle_status_ajax/$1', ['as' => 'catalog.products.toggle_status', 'filter' => 'permission:catalog.manage']);

        // Imágenes de producto
        $routes->post('products/images/delete/(:num)', 'Catalog\CatalogProductController::delete_image/$1', ['as' => 'catalog.products.image.delete', 'filter' => 'permission:catalog.manage']);
        $routes->post('products/images/set_main/(:num)', 'Catalog\CatalogProductController::set_main_image/$1', ['as' => 'catalog.products.image.set_main', 'filter' => 'permission:catalog.manage']);

        // ── Planes de Precios (Motor de Precios) ──
        $routes->get('products/(:num)/plans', 'Catalog\CatalogPlanController::getPlans/$1', ['as' => 'catalog.plans.list']);
        $routes->post('products/(:num)/plans/store', 'Catalog\CatalogPlanController::storePlan/$1', ['as' => 'catalog.plans.store', 'filter' => 'permission:catalog.manage']);
        $routes->post('plans/update/(:num)', 'Catalog\CatalogPlanController::updatePlan/$1', ['as' => 'catalog.plans.update', 'filter' => 'permission:catalog.manage']);
        $routes->post('plans/delete/(:num)', 'Catalog\CatalogPlanController::deletePlan/$1', ['as' => 'catalog.plans.delete', 'filter' => 'permission:catalog.manage']);
        $routes->post('plans/toggle/(:num)', 'Catalog\CatalogPlanController::togglePlan/$1', ['as' => 'catalog.plans.toggle', 'filter' => 'permission:catalog.manage']);

        // ── Relaciones de Productos (Bundles / Gifts / Upsells) ──
        $routes->get('products/(:num)/relations', 'Catalog\CatalogPlanController::getRelations/$1', ['as' => 'catalog.relations.list']);
        $routes->post('products/(:num)/relations/store', 'Catalog\CatalogPlanController::storeRelation/$1', ['as' => 'catalog.relations.store', 'filter' => 'permission:catalog.manage']);
        $routes->post('relations/delete/(:num)', 'Catalog\CatalogPlanController::deleteRelation/$1', ['as' => 'catalog.relations.delete', 'filter' => 'permission:catalog.manage']);

        // ── Inventario y Stock (Hito 4) ──
        $routes->get('products/(:num)/stock',              'Catalog\InventoryController::getStock/$1',        ['as' => 'catalog.inventory.stock']);
        $routes->get('products/(:num)/kardex',             'Catalog\InventoryController::getKardex/$1',       ['as' => 'catalog.inventory.kardex']);
        $routes->post('products/(:num)/stock/movement',    'Catalog\InventoryController::registerMovement/$1',['as' => 'catalog.inventory.movement', 'filter' => 'permission:catalog.manage']);
        $routes->post('products/(:num)/stock/min_alert',   'Catalog\InventoryController::updateMinAlert/$1',  ['as' => 'catalog.inventory.min_alert', 'filter' => 'permission:catalog.manage']);
    });

        // ── Sistema de Alertas / Notificaciones ─────────────────────────
        $routes->group('alerts', function ($routes) {
            // AJAX: Obtener alertas no leídas para el dropdown del topbar
            $routes->get('get-unread', 'System\AlertController::get_unread_ajax', [
                'as' => 'alerts.get_unread',
            ]);

            // AJAX: Marcar una alerta como leída
            $routes->post('mark-read', 'System\AlertController::mark_read_ajax', [
                'as' => 'alerts.mark_read',
            ]);

            // AJAX: Marcar todas como leídas
            $routes->post('mark-all-read', 'System\AlertController::mark_all_read_ajax', [
                'as' => 'alerts.mark_all_read',
            ]);

            // Vista HTML: Historial completo de notificaciones
            $routes->get('history', 'System\AlertController::history', [
                'as' => 'alerts.history',
            ]);

            // AJAX: DataTable del historial
            $routes->post('history-ajax', 'System\AlertController::history_ajax', [
                'as' => 'alerts.history_ajax',
            ]);
        });

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