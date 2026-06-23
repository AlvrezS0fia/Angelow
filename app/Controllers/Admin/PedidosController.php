<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class PedidosController extends Controller
{
    public function index()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->redirect('/auth/login');
            return;
        }
        // Obtener lista de pedidos desde un modelo (por hacer)
        $pedidos = []; // Ejemplo: $this->pedidoModel->getAll();
        $this->view('admin.pedidos', ['pedidos' => $pedidos]);
    }

    public function updateStatus()
    {
        // POST: actualizar estado de pedido
    }
}