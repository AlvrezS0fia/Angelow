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
        $this->view('repartidor.dashboard', ['user' => $_SESSION['user']]);
    }

    public function registro()
    {
        $this->view('repartidor.registro_repartidor');
    }
}
