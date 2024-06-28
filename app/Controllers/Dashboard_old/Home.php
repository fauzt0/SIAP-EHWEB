<?php

namespace App\Controllers\Dashboard;

use App\Controllers\BaseController;

class Home extends BaseController
{
    public function index()
    {
        echo view('Dashboard/home');
    }
}
