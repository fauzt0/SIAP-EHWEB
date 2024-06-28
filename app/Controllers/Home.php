<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        echo 'Formulario de inicio de sesion';
        return view('welcome_message');
    }    

}
