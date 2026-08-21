<?php
namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\JWTHelper;

class RepartidorPedidosController
{
    private function getRepartidorId()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);
        if (!$token) return null;
        $payload = JWTHelper::decode($token);
        if (!$payload) return null;
        $userId = $payload['sub'] ?? null;
        if (!$userId) return null;
        $user = Database::query("SELECT id, rol, estado FROM usuarios WHERE id = ?", [$userId])->fetch();
        if (!$user || $user['rol'] !== 'repartidor' || $user['estado'] !== 'activo') return null;
        return $userId;
    }

    private function json($data, $code = 200)
    {
        if (ob_get_length()) ob_clean();
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function index()
    {
        $repartidorId = $this->getRepartidorId();
        if (!$repartidorId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        $status = $_GET['status'] ?? null;
        $date = $_GET['date'] ?? null;
        $search = $_GET['search'] ?? null;

        $sql = "SELECT p.* FROM pedidos p WHERE (p.repartidor_id = ? OR (p.repartidor_id IS NULL AND p.estado IN ('pendiente', 'listo')))";
        $params = [$repartidorId];

        if ($status) {
            $sql .= " AND p.estado = ?";
            $params[] = $status;
        }
        if ($date) {
            $sql .= " AND DATE(p.fecha_pedido) = ?";
            $params[] = $date;
        }
        if ($search) {
            $sql .= " AND (p.numero_pedido LIKE ? OR p.nombre_cliente LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY p.fecha_pedido DESC";

        $orders = Database::query($sql, $params)->fetchAll();

        $result = array_map(function ($o) {
            $items = Database::query(
                "SELECT * FROM detalles_pedido WHERE pedido_id = ?",
                [$o['id']]
            )->fetchAll();

            return [
                'id' => $o['id'],
                'numero_pedido' => $o['numero_pedido'],
                'client_name' => $o['nombre_cliente'],
                'client_phone' => $o['telefono_cliente'],
                'delivery_address' => $o['direccion_envio'],
                'client_lat' => $o['latitud_destino'] ? floatval($o['latitud_destino']) : null,
                'client_lng' => $o['longitud_destino'] ? floatval($o['longitud_destino']) : null,
                'status' => $o['estado'],
                'total' => floatval($o['total']),
                'notes' => $o['notas_cliente'],
                'created_at' => $o['fecha_pedido'],
                'items' => array_map(function ($i) {
                    return [
                        'product_name' => $i['nombre_producto'],
                        'quantity' => $i['cantidad'],
                        'price' => floatval($i['precio_unitario']),
                    ];
                }, $items),
            ];
        }, $orders);

        $this->json($result);
    }

    public function show($id)
    {
        $repartidorId = $this->getRepartidorId();
        if (!$repartidorId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        $order = Database::query(
            "SELECT * FROM pedidos WHERE id = ? AND repartidor_id = ?",
            [$id, $repartidorId]
        )->fetch();

        if (!$order) {
            $this->json(['error' => 'Pedido no encontrado o no asignado'], 404);
            return;
        }

        $items = Database::query(
            "SELECT * FROM detalles_pedido WHERE pedido_id = ?",
            [$id]
        )->fetchAll();

        $this->json([
            'id' => $order['id'],
            'numero_pedido' => $order['numero_pedido'],
            'client_name' => $order['nombre_cliente'],
            'client_phone' => $order['telefono_cliente'],
            'delivery_address' => $order['direccion_envio'],
            'client_lat' => $order['latitud_destino'] ? floatval($order['latitud_destino']) : null,
            'client_lng' => $order['longitud_destino'] ? floatval($order['longitud_destino']) : null,
            'status' => $order['estado'],
            'total' => floatval($order['total']),
            'notes' => $order['notas_cliente'],
            'created_at' => $order['fecha_pedido'],
            'items' => array_map(function ($i) {
                return [
                    'product_name' => $i['nombre_producto'],
                    'quantity' => $i['cantidad'],
                    'price' => floatval($i['precio_unitario']),
                ];
            }, $items),
        ]);
    }

    public function updateStatus($id)
    {
        $repartidorId = $this->getRepartidorId();
        if (!$repartidorId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $newStatus = $data['status'] ?? $data['estado'] ?? '';

        $validTransitions = [
            'asignado'    => ['aceptado'],
            'aceptado'    => ['recogido'],
            'recogido'    => ['en_camino'],
            'en_camino'   => ['entregado'],
        ];

        $order = Database::query("SELECT * FROM pedidos WHERE id = ? AND repartidor_id = ?", [$id, $repartidorId])->fetch();
        if (!$order) {
            $this->json(['error' => 'Pedido no encontrado o no asignado'], 404);
            return;
        }

        $currentStatus = $order['estado'];

        if ($newStatus === 'cancelado') {
            if (!in_array($currentStatus, ['asignado', 'aceptado'])) {
                $this->json(['error' => 'No se puede cancelar un pedido en estado: ' . $currentStatus], 400);
                return;
            }
        } else {
            $allowedNext = $validTransitions[$currentStatus] ?? [];
            if (!in_array($newStatus, $allowedNext)) {
                $this->json(['error' => 'Transición no válida: ' . $currentStatus . ' → ' . $newStatus], 400);
                return;
            }
        }

        if ($newStatus === 'entregado') {
            $ganancia = floatval($order['ganancias_repartidor'] ?? 5000);
            Database::query(
                "UPDATE pedidos SET estado = ?, fecha_entrega_real = NOW() WHERE id = ?",
                [$newStatus, $id]
            );
            Database::query(
                "INSERT INTO historial_entregas (pedido_id, repartidor_id, ganancia, tiempo_entrega_minutos) VALUES (?, ?, ?, TIMESTAMPDIFF(MINUTE, ?, NOW()))",
                [$id, $repartidorId, $ganancia, $order['fecha_pedido']]
            );
        } elseif ($newStatus === 'cancelado') {
            Database::query(
                "UPDATE pedidos SET estado = ?, notas_cliente = CONCAT(COALESCE(notas_cliente, ''), ' | Cancelado por repartidor') WHERE id = ?",
                [$newStatus, $id]
            );
        } else {
            Database::query(
                "UPDATE pedidos SET estado = ? WHERE id = ?",
                [$newStatus, $id]
            );
        }

        $updated = Database::query("SELECT * FROM pedidos WHERE id = ?", [$id])->fetch();

        $this->json([
            'success' => true,
            'id' => $updated['id'],
            'status' => $updated['estado'],
            'message' => 'Estado actualizado a: ' . $newStatus,
        ]);
    }

    public function destroy($id)
    {
        $this->json(['error' => 'No permitido. Use el panel administrativo.'], 403);
    }
}
