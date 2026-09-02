<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;

class RepartidorController extends Controller
{
    public function index()
    {
        if (!Auth::isAdmin()) {
            $this->redirect('/auth/login');
            return;
        }

        $this->view('admin.repartidor');
    }
}
