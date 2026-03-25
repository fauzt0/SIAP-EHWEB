<?php
/**
 * @package App\Controllers\Users
 * 
 * Controlador encargado de la gestion de roles y permisos, permite agregar y quitar roles a un usuario, permite agregar y quitar permisos a un usuario, permite mostrar los permisos y roles existentes asi como editarlos
 */
namespace App\Controllers\Users;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Entities\User;  //SHIELD user managing 
use CodeIgniter\RESTful\ResourceController;

class RoleController extends BaseController
{

  protected $usersProvider;      

  public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
  {
    parent::initController($request, $response, $logger);    
    //Edit below this line

    $this->groupModel = model('GroupModel');
    $this->authGroups = config('AuthGroups');

    //ViewData default para view files  
    //$this->setPageTittleAhead("Roles", "Gestión de Roles y Permisos");  
    //$this->viewData['pageTitle'] = 'Users';
    //$this->viewData['headTitle'] = 'Users Management:';
    // Output default para comunicaciones con el archivo de salida
    //$this->outputData[''];

    $this->usersProvider = auth()->getProvider();
  }  

  /**
   * Muestra el catálogo de Roles (Grupos) y Permisos del sistema
   */
  public function index(): string
  {
    $this->setViewSuccess('Lista de Roles y Permisos del Sistema');
    $this->setPageTittleAhead('Roles y Permisos', 'Gestión de Accesos');
    
    $this->viewData['breadCrumb'] = 'Roles y Permisos';
    
    $this->viewData['response'] = [
      'responseMessage' => 'Catálogo de Roles y Permisos',
      'groups'          => $this->authGroups->groups,
      'permissions'     => $this->authGroups->permissions,
      'matrix'          => $this->authGroups->matrix
    ];

    return $this->renderLayout('Layouts/user_loggedin_layout', 'Users/roles_list');
  }

  protected function syncUserGroups(int $userId, array $groups): array
  {
    $user = $this->userModel->find($userId);

    if (empty($user)) {
      $this->outputData['statusCode'] = ResponseInterface::HTTP_NOT_FOUND;
      $this->outputData['message'] = 'User not found';
      $this->outputData['response'] = [
        'responseMessage' => 'Usuario no encontrado',
        'action' => false
      ];
      return $this->outputData;
    }

    try {
      $user->syncGroups($groups);
      $this->outputData['statusCode'] = ResponseInterface::HTTP_OK;
      $this->outputData['message'] = 'Groups synchronized';
      $this->outputData['response'] = [
        'responseMessage' => 'Grupos sincronizados correctamente',
        'action' => true
      ];
    } catch (\Exception $e) {
      $this->outputData['statusCode'] = ResponseInterface::HTTP_INTERNAL_SERVER_ERROR;
      $this->outputData['message'] = 'Sync failed';
      $this->outputData['error'] = $e->getMessage();
      $this->outputData['response'] = [
        'responseMessage' => 'Error al sincronizar grupos',
        'action' => false
      ];
    }

    return $this->outputData;
  }


  /**
   * Método para el manejo de grupos de manera masiva. Como parametro recibe el identificador del usuario y la accion que se desea realizar asi como un array de grupos probables
   * @param int $userId Identificador del usuario
   * @param string $action Accion a realizar
   * @param array $groups Grupos probables
   * @return array Contiene el estado de la operacion y el mensaje de respuesta    
   */
  protected function mannerGroups(int $userId, string $action, array $groups): array{
    //validamos que el array de grupos no este vacio
    if(empty($groups)){
      $this->outputData['statusCode'] = ResponseInterface::HTTP_BAD_REQUEST;
      $this->outputData['message'] = 'No groups selected';      
      $this->outputData['response'] = [
        'responseMessage'  => 'No se han seleccionado grupos',        
        'action'            => false
      ];      
    }   

    switch ($action) {
      case 'add':
        foreach ($groups as $group) {
          $this->assignUserToGroup($userId, $group);
        }
        break;
      case 'remove':
        foreach ($groups as $group) {
          $this->removeUserFromGroup($userId, $group);
        }
        break;   
      case 'delete':
        foreach ($groups as $group) {
          $this->deleteUserFromGroup($userId, $group);
        }     
        break;
      case 'update' :
        foreach ($groups as $group) {
          $this->syncUserGroups($userId, $group);
        }
        break;
      default:
        $this->outputData['statusCode'] = ResponseInterface::HTTP_BAD_REQUEST;
        $this->outputData['message'] = 'Invalid action';      
        $this->outputData['response'] = [
          'responseMessage'  => 'Accion invalida',        
          'action'            => false
        ];           
    }
    
    
    return $this->outputData;
  }



/**
 * Agrega un grupo a un usuario
 * @param int $userId Identificador del usuario
 * @param string $group Grupo a agregar
 * @return array Contiene el estado de la operacion y el mensaje de respuesta
 * @throws \CodeIgniter\Database\Exceptions\DatabaseException
 */
  protected function assignUserToGroup(int $userId,string $group): array
  {
    $user = $this->userModel->find($userId);    
    $userInGroup = $user->inGroup($group);
    if(!$userInGroup){
      $user->addGroup($group);
    }    

    $this->outputData['statusCode'] = $userInGroup ? ResponseInterface::HTTP_CONFLICT : ResponseInterface::HTTP_OK;
    $this->outputData['message'] = $userInGroup ? 'Group already assigned' : 'Group added';      
    $this->outputData['response'] = [
      'responseMessage'  => $userInGroup ? 'El grupo ya esta asignado al usuario' : 'Grupo agregado correctamente',        
      'action'            => $userInGroup ? false : true
    ];

    return $this->outputData;
  }  

  public function removeUserFromGroup(int $userId,string $group): array
  {
    $user = $this->userModel->find($userId);
    $userInGroup = $user->inGroup($group);
    if($userInGroup){
      $user->removeGroup($group);
    }    

    $this->outputData['statusCode'] = $userInGroup ? ResponseInterface::HTTP_OK : ResponseInterface::HTTP_NOT_FOUND;
    $this->outputData['message'] = $userInGroup ? 'Group removed' : 'Group not found';      
    $this->outputData['response'] = [
      'responseMessage'  => $userInGroup ? 'Grupo removido correctamente' : 'El grupo no se encuentra en la lista de grupos del usuario',        
      'action'            => $userInGroup ? true : false
    ];

    return $this->outputData;
  }





  /**
   * Adds permissions to a user.
   *
   * @param int $userId The ID of the user to add permissions to.
   * @param array $permissions The permissions to add.
   *
   * @return bool Whether the permissions were added successfully.
   */
  protected function addUserPermissions(int $userId,string $permissions): bool
  {
    $user = $this->userModel->find($userId);

    if ($user === null) { return false; }

    if(is_string($permission)){
      try {
        $user->addPermission($permission);
      } catch (\Exception $e) {
        return false;
      }
    }else{
      return false;
    }   

    return true;
  }

  public function removeUserPermissions(int $userId,string $permissions): bool
  {
    $user = $this->userModel->find($userId);

    if ($user === null) { return false; }

    if(is_string($permission)){
      try {
        $user->removePermission($permission);
      } catch (\Exception $e) {
        return false;
      }
    }else{
      return false;
    } 

    return true;
  }





}//end class
