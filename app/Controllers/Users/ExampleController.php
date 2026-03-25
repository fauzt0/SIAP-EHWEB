<?php
/**
 * @package App\Controllers\Users
 * Controlador con casos de ejemplo
 */

namespace App\Controllers\Users;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
//use App\Models\Users\UserModel; //index3, index4
//use CodeIgniter\RESTful\ResourcePresenter; // necesario para el uso ResourcePresenter

class ExampleController extends BaseController //extends ResourcePresenter
{

  protected $helpers = ['form', 'userdata', 'url'];
  //protected $viewData = [];//variable global con datos para la vista como el titulo, nombre de vista y datos de la sesion
  //protected $output = [];//variable global con los datos de la respuesta como estatus, mensaje, error y respuesta (se usa para respuestas de APIREST o datos entre controladores y clases de herencia. NO SE UTILIZA PARA ENVIAR DATOS A LA VISTA)
  /*
  * protected $userModel; //index,index4
  * protected $userModel = new App\Models\Users\UserModel;  //ejemplo index2
  */
 

  public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
  {
    // Do Not Edit This Line
    parent::initController($request, $response, $logger);
    
    /*
    $this->viewData = [
      'statusCode'  =>  ResponseInterface::HTTP_NO_CONTENT, //204
      'message'     => 'No content found',  
      'error'       => '',
      'pageTitle'   => 'Users',      
      'headTitle'   => 'Users Management:',                  
      'response'    => [], //empty response
    ];
    */

    $this->output = [
      'statusCode'  =>  ResponseInterface::HTTP_NO_CONTENT, //204
      'message'     => 'No content found',
      'error'       => '',      
      'response'    => [], //empty response
    ];


    
    //cargamos el modelo de usuario para que pueda ser utilizado en toda la clase
    //$this->userModel = auth()->getProvider();   //index1
    //$this->userModel = new UserModel();//index4 
  }   


    /*public function index() //index, usamos el modelo de usuario cargado por auth()->getProvider() de SHIELD
    {
        //
        $this->view_data = array( //array con datos generales de la vista
          'title' => 'List of all users',      
          'view'  => 'Users/index',      
          'sub-title1' => 'List with all users:',
        );
        
        $users = $this->userModel->findAll();
        $data = [
          'view_data' => $this->view_data,
          'users' => $users
        ];        
        
        return view($this->view_data['view'], $data);
        
    }*/

    /*
    public function index(){ //index2, usamos el modelo cargado en la variable $userModel de la clase ExampleController
      $modelName = 'App\Models\Users\UserModel';
      $userModel = new $modelName();
      $users = $userModel->findAll();

      $this->view_data = array( //array con datos generales de la vista
        'title' => 'List of all users',      
        'view'  => 'Users/index',      
        'sub-title1' => 'List with all users:',
      );      
      
      $users = $userModel->findAll();
      $data = [
        'view_data' => $this->view_data,
        'users' => $users
      ];          
      return view($this->view_data['view'], $data);
    }*/

    /*public function index(){ //index3
      $userModel = new UserModel();
      $users = $userModel->findAll();          
      return($this->render_data($users));
    }*/

    /*
    public function index(){ //index4
      $users = $this->userModel->findAll();          
      return($this->render_data($users));
    }*/

    /*
    public function index(){ //index5
      $userModel = new \App\Models\Users\UserModel();
      $users = $userModel->findAll();          
      return($this->render_data($users));
    }*/
    
    /*
    public function index(){ //index6
      $userModel = model('UserModel');
      $users = $userModel->findAll();          
      return($this->render_data($users));
    }*/

    /*public function index(){ //index7
      $userModel = model('App\Models\Users\UserModel');
      $users = $userModel->findAll();
      return($this->render_data($users));
    }*/

   

    private function render_data($user_info){
      $this->view_data = array( //array con datos generales de la vista
        'title' => 'List of all users',      
        'view'  => 'Users/index',      
        'sub-title1' => 'List with all users:',
      );

      $users = $user_info;
      $data = [
        'view_data' => $this->view_data,
        'users' => $user_info,
      ];          
      return view($this->view_data['view'], $data);
    }
}
