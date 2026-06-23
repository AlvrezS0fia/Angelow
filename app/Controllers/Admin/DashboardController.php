<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->redirect('/auth/login');
            return;
        }

        $data = [
            'totalPedidos' => 12,
            'pendientes' => 3,
            'favoritos' => 0,
            'ganancias' => 628000,
            'nombreAdmin' => $_SESSION['user']['nombre'] ?? 'Administrador'
        ];

        $this->view('admin.panel', $data);
    }
}