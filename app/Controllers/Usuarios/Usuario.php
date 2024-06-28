<?php
namespace App\Controllers\Usuarios;
use App\Controllers\BaseController;
use App\Models\Usuarios\UsuarioModel;

class Usuario extends BaseController
{

  protected $helpers = ['form', 'userdata'];
  public function index()
  {
    //echo "Listado de usuarios";          
    $data = array();
    $data['title'] = "Usuarios - EHWEB";   
    $data['view'] = "Usuarios/usuarios_home";
    $data['user_data'] = getUserData();
  
    return view($data['view'], $data);  
  }

  public function new(){
    echo "Formulario de creacion de usuario";
  }


  /*store a newly created user in storage. */
  public function create()
  {
    
  }

  
}//end of class