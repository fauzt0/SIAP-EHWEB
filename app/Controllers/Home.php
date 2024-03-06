<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        echo 'Hello World!';
        return view('welcome_message');
    }    

}
