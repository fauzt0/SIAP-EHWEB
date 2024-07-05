<?php
/*Controlador principal del dashboard */

namespace App\Controllers\Dashboard;
use App\Controllers\BaseController;

class Home extends BaseController
{

  //variables de la clase 
  protected $helpers = ['form', 'userdata'];  

  public function index()
  {
    $data = array();
    $data['title'] = "Dashboard - EHWEB";   
    $data['view'] = "Dashboard/home";
    $data['user_data'] = getUserData();
   
   return view('Dashboard/home', $data);  
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
