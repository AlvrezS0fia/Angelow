<?php
namespace App\Controllers;

use App\Core\Controller;

class RepartidorController extends Controller
{
    public function index()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'repartidor') {
            $this->redirect('/repartidor/login');
            return;
        }
        $this->view('paginas.repartidor', ['user' => $_SESSION['user']]);
    }
}
