<?php
/*Controlador principal del dashboard */

namespace App\Controllers\Dashboard;
use App\Controllers\BaseController;

class Home extends BaseController
{

  //variables de la clase 
  protected $helpers = ['form', 'userdata'];
  //datos del usuario logueado

  public function index()
  {
    $data = array();
    $data['title'] = "Dashboard - EHWEB";   
    $data['view'] = "Dashboard/home";
    $data['user_data'] = getUserData();
   
   return view('Dashboard/home', $data);  
  }
}
