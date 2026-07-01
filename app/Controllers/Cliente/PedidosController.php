<?php
namespace App\Controllers\Cliente;

use App\Core\Controller;
use App\Models\PedidoModel;
use App\Models\UsuarioModel;

class PedidosController extends Controller
{
    private $pedidoModel;

    public function __construct() {
        $this->pedidoModel = new PedidoModel();
    }

    public function index() {
        if (!isset($_SESSION['user'])) {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }
        $usuarioId = (int) $_SESSION['user']['id'];
        $pedidos = $this->pedidoModel->getByUsuario($usuarioId);
        $this->json($pedidos);
    }

    public function detalle($pedidoId) {
        if (!isset($_SESSION['user'])) {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }
        $pedido = $this->pedidoModel->getById($pedidoId);
        if (!$pedido || $pedido['usuario_id'] != $_SESSION['user']['id']) {
            $this->json(['error' => 'Pedido no encontrado'], 404);
            return;
        }
        $items = $this->pedidoModel->getDetalles($pedidoId);
        $this->json([
            'pedido' => $pedido,
            'items' => $items
        ]);
    }

    public function factura($pedidoId) {
        if (!isset($_SESSION['user'])) {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }
        $pedido = $this->pedidoModel->getById($pedidoId);
        if (!$pedido || $pedido['usuario_id'] != $_SESSION['user']['id']) {
            $this->json(['error' => 'Pedido no encontrado'], 404);
            return;
        }
        $items = $this->pedidoModel->getDetalles($pedidoId);
        $this->json([
            'pedido' => $pedido,
            'items' => $items,
            'cliente' => [
                'nombre' => $pedido['nombre_cliente'],
                'email' => $pedido['email_cliente'],
                'telefono' => $pedido['telefono_cliente'],
                'cedula' => $pedido['cedula_cliente']
            ],
            'envio' => [
                'direccion' => $pedido['direccion_envio'],
                'destinatario' => $pedido['destinatario']
            ]
        ]);
    }

    public function cancelar($pedidoId) {
        if (!isset($_SESSION['user'])) {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }
        $pedido = $this->pedidoModel->getById($pedidoId);
        if (!$pedido || $pedido['usuario_id'] != $_SESSION['user']['id']) {
            $this->json(['error' => 'Pedido no encontrado'], 404);
            return;
        }
        $estado = strtolower($pedido['estado'] ?? '');
        if (!in_array($estado, ['pendiente', 'procesando'])) {
            $this->json(['error' => 'Solo se pueden cancelar pedidos pendientes o en proceso'], 400);
            return;
        }
        $this->pedidoModel->updateEstado($pedidoId, 'cancelado');
        $this->json(['success' => true, 'message' => 'Pedido cancelado correctamente']);
    }
}
