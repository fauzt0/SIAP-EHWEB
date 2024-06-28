<?php

namespace App\Controllers\Login;

use App\Controllers\BaseController;
use App\Models\Usuarios\UsuarioModel;

class Login extends BaseController
{
  public function index()
  {
    helper('form');
    return view('Login/login');
  }


  public function login_post()
  { 
    $usuarioModel = new UsuarioModel();

    $email = $this->request->getPost('email');
    $password = $this->request->getPost('password');
    
    $usuario = $usuarioModel->select('id_usuario, nombre, apellido_paterno, apellido_materno, email, password')
    ->where('email', $email)
    ->where('eliminado_en', null)
    ->first();

    if(!$usuario){
      return redirect()->back()->with('errors', 'Usuario y/o contraseña incorrectos');
    }

    if(!$this->passwordVerify($password, $usuario->password)){
      return redirect()->back()->with('errors', 'Usuario y/o contraseña incorrectos');
    }

    //guardar en la sesion en caso de que el acceso sea correcto
    session()->set([
      'id_usuario' => $usuario->id_usuario,
      'nombre' => $usuario->nombre,
      'apellido_paterno' => $usuario->apellido_paterno,
      'apellido_materno' => $usuario->apellido_materno,
      'email' => $usuario->email,      
      'tipo_acceso' => 'admin',
      'isLoggedIn' => true
    ]);
    return redirect()->route('usuario.dashboard');
  }

  //password hash
  private function passwordHash($password){
    return password_hash($password, PASSWORD_DEFAULT);  
  }

  //password verify
  private function passwordVerify($password, $hash){
    return password_verify($password, $hash);
  }

  public function logout()
  {
    session()->destroy();
    return redirect()->route('usuario.login');
  }

}
