<?php
use CodeIgniter\CodeIgniter;

//returns user data session in array

if (! function_exists('setUserData')) {
  function getUserData() : array
  {
    $session = session();
    $user_data = [];
    $user_data['id_usuario'] = $session->get('id_usuario');
    $user_data['nombre'] = $session->get('nombre');
    $user_data['email'] = $session->get('email');
    $user_data['id_rol'] = $session->get('id_rol');

    //aplicamos operaciones adicionales en caso de ser necesario, filtros, formato, etc         

    return $user_data;
  }
}