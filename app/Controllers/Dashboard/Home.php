<?php
/*Controlador principal del dashboard 
* Mostrara informacion relevante segun el rol del usuario y sus permisos
*/

namespace App\Controllers\Dashboard;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\Breadcrumb;
use CodeIgniter\RESTful\ResourceController;

class Home extends BaseController
{

  //variables de la clase
  protected $helpers = ['form', 'userdata', 'url'];         
  protected $breadCrumb = []; //objeto global breadcrumb con las rutas por 


  //creamos el init controller de la clase padre BaseController para inicializar variables globales
  public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
  {
    // Do Not Edit This Line
    parent::initController($request, $response, $logger);    
    //Edit below this line

    //creamos el objeto breadcrumb con las rutas por defecto
    $this->breadcrumb  =  new Breadcrumb([
      'Inicio'    => base_url(),      
    ]);     

    $this->setViewNoContent('No se tiene contenido');
    $this->setPageTittleAhead('Dashboard','Panel de Control'); //seteamos titulo y cabecera

    /*Salidas del viewData personalizadas ()
    * 
    * Esta estructura se puede implementar en el constructor o en el método que muestre una salida, para establecer todos los datos de salida necesarios para la vista, sin embargo, se recomienda implementar los metodos de viewData disponibles de App:info. Se uttiliza array_merge() para unir los arrays en caso de que se tengan datos previamente establecidos en el constructor
    **/
    /*
    *$this->viewData = array_merge($this->viewData, [
      'success'     => true,     
      'statusCode'  => $isEmpty ? ResponseInterface::HTTP_NO_CONTENT : ResponseInterface::HTTP_OK,
      'message'     => $isEmpty ? 'No users found' : 'List of all users',            
      'error'       => $isEmpty ? 'No users found. Empty Data' : '',
      'response'    => [
        'breadcrumb'      => $breadCrumb,
        'responseMessage' => $isEmpty ? 'No se encontraron usuarios' : 'Listado de Usuarios:',
        'isEmpty'         => $isEmpty, //boolean
        'usersList'       => $users, //array  
      ],         
    ]);*/

    /*
    * La estructura outputData, permite enviar y devolver datos a otros métodos o a solicitudes HTTP, de esta manera se puede controlar la salida y la estructura de los datos que se retornan
    */

    /**
     * $this->outputData['success']     = true;     
     * $this->outputData['statusCode']  = $isEmpty? ResponseInterface::HTTP_NO_CONTENT : ResponseInterface::HTTP_OK;
     * $this->outputData['message']     = $isEmpty? 'No users found' : 'List of all users';            
     * $this->outputData['error']       = $isEmpty? 'No users found. Empty Data' : '';
     * $this->outputData['response']    = []; //empty response
    */
  }

  public function index(): string
  {
    ///cargamos los datos iniciales
    $this->setViewSuccess('Bienvenido al panel de control');	      
    $this->setPageTittleAhead('Dashboard', 'Panel de Control'); 
    
    //establecemos el breadcrumb
    $this->viewData['breadCrumb'] = $this->breadcrumb->getBreadCrumbHtml();
    
    //se envian datos personalizados a la vista
    $this->viewData['response'] = [
      'responseMessage' => 'Bienvenido al panel de control'            
    ];
    
    //usamos el metodo renderLayout para renderizar la vista directamente
    return $this->renderLayout('Layouts/user_loggedin_layout', 'Dashboards/home');
  }

  //mostramos el mensaje de error del flashdata
  public function error(){

    $data = array();
    $data['title'] = "Error - EHWEB";   
    $data['view'] = "Dashboard/error";
    $data['user_data'] = getUserData();
    $data['message'] = session()->getFlashdata('message');
    echo  $data['message'];
   // return view('Dashboard/error', $data);

  }


}
