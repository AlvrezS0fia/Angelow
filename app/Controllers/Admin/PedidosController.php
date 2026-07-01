<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\PedidoModel;

class PedidosController extends Controller
{
    private $pedidoModel;

    public function __construct() {
        $this->pedidoModel = new PedidoModel();
    }

    public function index()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->redirect('/auth/login');
            return;
        }
        $this->redirect('/admin');
    }

    public function obtenerPedidos()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }

        $pedidos = $this->pedidoModel->getAll();
        $this->json($pedidos);
    }

    public function updateStatus()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $pedidoId = $data['id'] ?? 0;
        $estado = $data['estado'] ?? '';

        if (!$pedidoId || !$estado) {
            $this->json(['error' => 'Datos inválidos'], 400);
            return;
        }

        $resultado = $this->pedidoModel->updateEstado($pedidoId, $estado);
        if ($resultado) {
            $this->json(['success' => true, 'message' => 'Estado actualizado']);
        } else {
            $this->json(['error' => 'Error al actualizar'], 500);
        }
    }
}