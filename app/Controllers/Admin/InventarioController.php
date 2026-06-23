<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class InventarioController extends Controller
{
    public function index()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->redirect('/auth/login');
            return;
        }
        $this->view('admin.inventario');
    }
}