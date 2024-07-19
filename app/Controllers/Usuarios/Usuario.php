<?php
namespace App\Controllers\Usuarios;
//use App\Controllers\BaseController;
use CodeIgniter\RESTful\ResourcePresenter;

//use App\Models\Usuarios\UsuarioModel;
//class Usuario extends BaseController
class Usuario extends ResourcePresenter
{
  protected $helpers = ['form', 'userdata'];
  protected $modelName =  'App\Models\Usuarios\UsuarioModel';
  //variable global con datos para la vista como el titulo, nombre de vista y datos de la sesion
  protected $view_data = array();
  
  public function index()
  {                      
    //datos para la vista
    $this->viewdata['title'] = "Usuarios - EHWEB";   
    $this->viewdata['view'] = "Usuarios/usuarios_home";
    $this->viewdata['user_data'] = getUserData(); //datos de sesion del usuario
    $this->viewdata['breadcrumbs'] = array('Home' => 'dashboard', 'Usuarios' => 'usuarios');
    //renderizamos la vista
    return view($this->viewdata['view'], $this->viewdata);    
  }

  //metodo que lista y busca a los usuarios
  public function ajaxList(){    
    //mostramos todo los reques posibles post o get
    $search = $this->request->getPost('search'); 
    $data['success'] = true;
    $data['search'] = $search."hola";  
    return $this->response->setJSON($data); 

    //$this->response->setStatusCode(200);
    //return $this->response;   

  }

  /*
   * Show the form for creating a new resource and store it in storage.
   */
  public function new(){
    echo "<br>";
    echo "Formulario de creacion de usuario";
    //creamos un formulario sencillo
    echo "<form method='post' action='".base_url()."/usuario/create'>";
    echo "Nombre: <input type='text' name='nombre'><br>";
    echo "Email: <input type='text' name='email'><br>";
    echo "Password: <input type='text' name='password'><br>";
    echo "<input type='submit' value='Crear usuario'>";
    echo "</form>";
  }

  
  /*store a newly created user in storage. */
  public function create()
  {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Allow-Headers: Content-Type");

    //ci4 
    //$validation = service('validation');
    echo "Se guardo el usuario";

    /*
    $validation->setRules = [
      'nombre' => 'required|min_length[3]|max_length[50]',
      'apellido_paterno' => 'max_length[50]',
      'apellido_materno' => 'max_length[50]',
      'email' => 'required|valid_email|max_length[100]|is_unique[usuario.email]|valid_email',
      'password' => 'required|min_length[8]|max_length[50]',
      'telefono' => 'max_length[50]|required|is_unique[usuario.telefono]',
      'movil' => 'max_length[50]',     
    ];

    $usermodel = new UsuarioModel();
    
    $result = $usermodel -> insert([
        'nombre' => $this->request->getPost('nombre'),
        'apellido_paterno' => $this->request->getPost('apellido_paterno'),
        'apellido_materno' => $this->request->getPost('apellido_materno'),        
        'email' => $this->request->getPost('email'),
        'password' => $this->request->getPost('password'),        
        'telefono' => $this->request->getPost('telefono'),
        'movil' => $this->request->getPost('movil'),        
    ]);     */     


  }

  //mostrar el perfil del usuario en la base de datos con el formulario 
  function show($userId=null){        
  
    if($userId == null || !is_numeric($userId)){ //verificamos que el userId exista y sea un entero
      //mostramos un mensaje de error mediante un mensaje en flashdata             
      return redirect()->route('usuario.error')->with('message', 'Usuario no válido');
    }

    //verificamos que el usuario exista en la base de datos
    
    $user = $this->model->find($userId);
    if($user == null){
      return redirect()->route('usuario.error')->with('message', 'Usuario no válido');
    }

    //obtenemos la informacion del usuario para monstrarla en el controlador   
    
    $data = array();
    $data['title'] = "Usuario - EHWEB";   
    $data['view'] = "Usuarios/usuario_show";
    $data['user_data'] = getUserData(); //datos de sesion del usuario
    $data['user'] = $user;

    return view($data['view'], $data);
  }

  
}//end of class