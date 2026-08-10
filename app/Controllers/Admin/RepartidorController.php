<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class RepartidorController extends Controller
{
    public function index()
    {
        $this->view('admin.repartidor');
    }
}
