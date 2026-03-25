<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = [];

  //Valores personalizados

    /**
     * Variable global con datos para la vista como el titulo, nombre de vista y datos de la sesion
     *
     * @var array
     */
    protected $viewData = [];

    /**
     * Variable global con los datos de la respuesta como estatus, mensaje, error y respuesta
     *
     * @var array
     */
    protected $outputData = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = \Config\Services::session();
        //echo "Soy el init Controller";

        //output and viewdata dedfault 

        //general viewdata for view files
        $this->viewData = [
          'success'     => true,
          'statusCode'  => ResponseInterface::HTTP_NO_CONTENT, //204
          'message'     => 'Respuesta sin contenido',  
          'error'       => '',
          'pageTitle'   => '',      
          'headTitle'   => '',   
          'validate'    => '', //parsea alerts ala vista desde el init controller
          'showAlert'   => false, // Flag especifico para detonar tools.js
          'pageView'    => '', //elemento requerido para mostrar la vista en el layout
          'breadcrumb'  => '',//establecemos breadcrumbs para la vista en general               
          'response'    => [], //empty response
        ];        

      // General outputdata for comuinications file
      $this->outputData = [
        'success'     => true,  
        'statusCode'  => ResponseInterface::HTTP_NO_CONTENT, //204
        'message'     => 'Respuesta sin contenido',
        'error'       => '',      
        'response'    => [], //empty response
      ];     
    }

    /**
     * Establece título de la página y el título de la cabecera en viewData
     * 
     * @param string $pageTitle Título de la página (por defecto '')
     * @param string $headTitle Título de la cabecera (por defecto '')  
     * @return void 
     * */
    protected function setPageTittleAhead(string $pageTitle="" , string $headTitle=""): void
    {
      $this->viewData['pageTitle'] = $pageTitle;
      $this->viewData['headTitle'] = $headTitle;
    }
    
    /**
     * Establece estado "sin contenido" (204) en viewData
     * 
     * @param string $message Mensaje a mostrar (por defecto "No Content Found")
     * @return void
     */
    protected function setViewNoContent(string $message = 'No se ha encontrado contenido'): void
    {
      
      $this->viewData['success'] = true;
      $this->viewData['statusCode'] = ResponseInterface::HTTP_NO_CONTENT;        
      $this->viewData['message'] = $message;
      $this->viewData['showAlert'] = false;
      $this->viewData['error'] = '';//no tiene mensaje de error
    }

    /**
     * Establece estado "no encontrado" (404) en viewData, para busquedas sin resultados
     * 
     * @param string $message Mensaje a mostrar (por defecto "No Content Found")
     * @param string $error Mensaje de error (por defecto "Búsqueda sin resultados")
     * @return void
     * */
    protected function setViewNoResult(string $message = 'No se han encontrado resultados', string $error = 'Búsqueda sin resultados' ){
      $this->viewData['success'] = false;      
      $this->viewData['statusCode'] = ResponseInterface::HTTP_NOT_FOUND;
      $this->viewData['message'] = $message;
      $this->viewData['error'] = $error;
      $this->viewData['showAlert'] = false;
      $this->viewData['response'] = []; //empty response 
    }

    
    /**
     * Establece datos de éxito en el array viewData
     * 
     * @param string $message Mensaje de éxito
     * @param int $statusCode Código de estado HTTP (opcional)
     * @param bool $success Indicador de éxito (opcional)
     * @param bool $showAlert Indica si se debe lanzar la notificación en JS (opcional)
     * @return void
     */
    protected function setViewSuccess(string $message, int $statusCode = ResponseInterface::HTTP_OK, bool $success = true, bool $showAlert = false): void
    {
      $this->viewData['success']    = $success; 
      $this->viewData['statusCode'] = $statusCode;
      $this->viewData['message']    = $message;
      $this->viewData['showAlert']  = $showAlert;
      $this->viewData['response']   = []; //vaciamos el listado de response 
    }
    
    /**
     * Establece datos de error en el array viewData
     * 
     * @param string $message Mensaje de error
     * @param mixed $errors Detalles de errores (opcional)
     * @param int $statusCode Código de estado HTTP (opcional)
     * @param bool $showAlert Indica si se debe lanzar la notificación en JS (opcional)
     * @return void
     */
    protected function setViewError(string $message, $errors = null, int $statusCode = ResponseInterface::HTTP_BAD_REQUEST, bool $showAlert = true)
    {
        $this->viewData['statusCode'] = $statusCode;
        $this->viewData['success'] = false;
        $this->viewData['message'] = $message;
        $this->viewData['showAlert'] = $showAlert;
        
        if ($errors !== null) {
            $this->viewData['errors'] = $errors;
            $this->viewData['error'] = $errors; // compatibilidad con clave singular
        }
                
    }

    /**
     * Renderiza la vista dentro del layout general validando que exista
     * 
     * @param string $viewPath Ruta de la vista a renderizar
     * @return string
     */
    protected function renderLayout(string $layout, string $viewPath): string
    {
        // 1. Validar if (empty($viewPath))
        if(empty($viewPath)){
             throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("No se definió una vista para renderizar.");
        }
        // 2. Establecemos la vista
        $this->viewData['pageView'] = $viewPath;
        $this->viewData['layout'] = $layout;
        // 3. Retornamos usando el layout estandar de tu aplicación (como veo en el Home)
        return view($viewPath, $this->viewData);
    }
    

    /****Metodos para OutputData (No vistas) ****/

    /**
     * Establece datos de éxito en el array outputData
     * 
     * @param string $message Mensaje de éxito
     * @param mixed $data Datos adicionales (opcional)
     * @param int $statusCode Código de estado HTTP (opcional)
     * @return $this
     */
    protected function setOutputSuccess(string $message, $response = null, int $statusCode = ResponseInterface::HTTP_OK)
    {
        //limpiamos todo el array de outputData antes de establecer nuevos valores
        $this->outputData = [];
        $this->outputData['success'] = true;
        $this->outputData['statusCode'] = $statusCode;
        $this->outputData['message'] = $message;
        $this->outputData['error'] = '';         
        $this->outputData['response'] = $response; // compatibilidad con controladores que usan 'response'
        
        
        return $this->outputData;
    }
    
    /**
     * Establece datos de error en el array outputData
     * 
     * @param string $message Mensaje de error
     * @param mixed $errors Detalles de errores (opcional)
     * @param int $statusCode Código de estado HTTP (opcional)
     * @return $this
     */
    protected function setOutputError(string $message, $errors = null, int $statusCode = ResponseInterface::HTTP_BAD_REQUEST)
    {
        $this->outputData['statusCode'] = $statusCode;
        $this->outputData['success'] = false;
        $this->outputData['message'] = $message;
        
        if ($errors !== null) {
            $this->outputData['errors'] = $errors;
            $this->outputData['error'] = $errors; // compatibilidad con clave singular
        }
        
        return $this->outputData;
    }
    
    /**
     * Establece estado "sin contenido" (204) en outputData
     * 
     * @param string $message Mensaje a mostrar (por defecto "No Content Found")
     * @return $this
     */
    protected function setOutputNoContent(string $message = 'No Content Found')
    {
        $this->outputData['statusCode'] = ResponseInterface::HTTP_NO_CONTENT;
        $this->outputData['success'] = true;
        $this->outputData['message'] = $message;
        // Limpia posibles datos previos y asegura claves compatibles
        unset($this->outputData['data'], $this->outputData['errors']);
        $this->outputData['response'] = [];
        $this->outputData['error'] = 'No Content Found';
        
        return $this->outputData;
    }
}
