<?php

namespace App\Controllers;
use CodeIgniter\Shield\Entities\User;

class Home extends BaseController
{ 
    /*
    public function index(): string
    {
        echo 'Formulario de inicio de sesion';
        //return view('welcome_message');
    } */   

    //public function index(): string
    public function index()
    {
      echo 'Desarrollo en proceso de ERP EHWEB - Plataforma Sandbox';        
      /*
      // Get the User Provider (UserModel by default)
      $users = auth()->getProvider();
      $user = new User([
          'username' => 'fausto-ehweb',
          'email'    => 'soporte2@especialistashosting.com',
          'password' => 'Lionman1322',
          'fist_name' => 'Fausto',

      ]);
      $users->save($user);
      // To get the complete user object with ID, we need to get from the database
      $user = $users->findById($users->getInsertID());

      // Add to default group
      $users->addToDefaultGroup($user); 
      */
      //verificamos si el usuario se agrego y mostramos un mensaje de exito
      /*
      if ($users->hasUser($user)) {
        echo 'Se agrego el usuario';
      }*/
      //verificamos si el usuario se agrego y mostramos un mensaje de exito  
    }

    public function list_users(){
      //$userModel = new \App\Models\Users\UserModel();
      $userModel = auth()->getProvider();
      $users = $userModel->findAll();
      //mostramos el listado de usuarios
      $data = array();
      foreach ($users as $user) {
        $data[] = array(
          'id' => $user->id,
          'username' => $user->username,
          'email' => $user->email,
          'first_name' => $user->first_name,
          'last_name' => $user->last_name,          
        );
      }     
      print_r($data);      
    }

    public function list_users_2(){
      /*$userModel = model('UserModel');
      var_dump($userModel->find()[0]->username);*/
      //
      $groupModel = model('GroupModel');
      var_dump($groupModel->find()[0]->name);
    }



    

}
