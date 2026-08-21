<?php
namespace App\Controllers\Cliente;

use App\Core\Controller;
use App\Core\Database;
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

    public function seguimiento($pedidoId) {
        if (!isset($_SESSION['user'])) {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }
        $usuarioId = (int) $_SESSION['user']['id'];
        $pedido = $this->pedidoModel->getById($pedidoId);
        if (!$pedido || (int)$pedido['usuario_id'] !== $usuarioId) {
            $this->json(['error' => 'Pedido no encontrado'], 404);
            return;
        }

        $tracking = Database::query(
            "SELECT * FROM seguimiento_tiempo_real WHERE pedido_id = ? ORDER BY timestamp_ubicacion DESC LIMIT 1",
            [$pedidoId]
        )->fetch();

        $repartidor = null;
        if (!empty($pedido['repartidor_id'])) {
            $repartidor = Database::query(
                "SELECT id, nombre, telefono FROM usuarios WHERE id = ?",
                [$pedido['repartidor_id']]
            )->fetch();
        }

        $this->json([
            'success' => true,
            'pedido' => [
                'id' => (int) $pedido['id'],
                'numero_pedido' => $pedido['numero_pedido'],
                'estado' => $pedido['estado'],
                'fecha_pedido' => $pedido['fecha_pedido'],
                'direccion_envio' => $pedido['direccion_envio'] ?? '',
                'latitud_destino' => isset($pedido['latitud_destino']) ? floatval($pedido['latitud_destino']) : null,
                'longitud_destino' => isset($pedido['longitud_destino']) ? floatval($pedido['longitud_destino']) : null,
                'metodo_envio' => $pedido['metodo_envio'] ?? '',
            ],
            'ubicacion' => $tracking ? [
                'latitud' => floatval($tracking['latitud']),
                'longitud' => floatval($tracking['longitud']),
                'velocidad_kmh' => floatval($tracking['velocidad_kmh'] ?? 0),
                'bateria_porcentaje' => intval($tracking['bateria_porcentaje'] ?? 0),
                'timestamp' => $tracking['timestamp_ubicacion'],
            ] : null,
            'repartidor' => $repartidor ? [
                'id' => (int) $repartidor['id'],
                'nombre' => $repartidor['nombre'] ?? '',
                'telefono' => $repartidor['telefono'] ?? '',
            ] : null,
        ]);
    }
}
