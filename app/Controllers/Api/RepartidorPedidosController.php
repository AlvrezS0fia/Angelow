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
        if ($token) {
            $payload = JWTHelper::decode($token);
            if ($payload && isset($payload['sub'])) {
                return $payload['sub'];
            }
        }
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['user']) && ($_SESSION['user']['rol'] ?? '') === 'repartidor') {
            return $_SESSION['user']['id'];
        }
        return null;
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
        $available = isset($_GET['available']);

        if ($available) {
            $sql = "SELECT p.* FROM pedidos p WHERE p.repartidor_id IS NULL AND p.estado IN ('pendiente','confirmado','listo')";
            $params = [];

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
        } else {
            $sql = "SELECT p.* FROM pedidos p WHERE p.repartidor_id = ?";
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
        }

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
            "SELECT * FROM pedidos WHERE id = ?",
            [$id]
        )->fetch();

        if (!$order) {
            $this->json(['error' => 'Pedido no encontrado'], 404);
            return;
        }

        if ((int)($order['repartidor_id'] ?? 0) !== $repartidorId) {
            $this->json(['error' => 'No tienes permisos para consultar este pedido'], 403);
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

    public function create()
    {
        $repartidorId = $this->getRepartidorId();
        if (!$repartidorId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $clientId = intval($data['client_id'] ?? 0);
        $itemsData = $data['items'] ?? [];
        $notas = $data['notes'] ?? '';

        $clienteNombre = 'Cliente';
        $clienteTelefono = '';
        $clienteEmail = '';
        $direccion = '';

        if ($clientId) {
            $client = Database::query("SELECT * FROM usuarios WHERE id = ?", [$clientId])->fetch();
            if ($client) {
                $clienteNombre = $client['nombre'] . ' ' . ($client['apellido'] ?? '');
                $clienteTelefono = $client['telefono'] ?? '';
                $clienteEmail = $client['email'] ?? '';
                $direccion = $client['direccion'] ?? '';
            }
        }

        $total = floatval($data['total'] ?? 0);
        if ($total <= 0) {
            foreach ($itemsData as $item) {
                $total += (floatval($item['price'] ?? 0)) * (intval($item['quantity'] ?? 1));
            }
        }

        Database::query(
            "INSERT INTO pedidos (usuario_id, repartidor_id, nombre_cliente, email_cliente, telefono_cliente, direccion_envio, ciudad, departamento, subtotal, total, estado, notas_cliente, fecha_pedido) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', ?, NOW())",
            [$clientId ?: $repartidorId, $repartidorId, $clienteNombre, $clienteEmail, $clienteTelefono, $direccion, 'Medellín', 'Antioquia', $total, $total, $notas]
        );

        $pedidoId = Database::getInstance()->getConnection()->lastInsertId();

        foreach ($itemsData as $item) {
            Database::query(
                "INSERT INTO detalles_pedido (pedido_id, producto_id, nombre_producto, cantidad, precio_unitario, subtotal) VALUES (?, 1, ?, ?, ?, ?)",
                [$pedidoId, $item['product_name'] ?? 'Producto', intval($item['quantity'] ?? 1), floatval($item['price'] ?? 0), (intval($item['quantity'] ?? 1)) * (floatval($item['price'] ?? 0))]
            );
        }

        $order = Database::query("SELECT * FROM pedidos WHERE id = ?", [$pedidoId])->fetch();

        $this->json([
            'success' => true,
            'id' => $order['id'],
            'numero_pedido' => $order['numero_pedido'],
            'message' => 'Pedido creado',
        ], 201);
    }

    private function ensureAsignadoStatus()
    {
        try {
            Database::query("SELECT 'asignado' FROM DUAL WHERE 'asignado' IN ('pendiente','asignado','confirmado')");
        } catch (\Exception $e) {
            try {
                Database::query("ALTER TABLE pedidos MODIFY COLUMN estado ENUM('pendiente','confirmada','cambio','devolucion','rechazada') DEFAULT 'pendiente'");
            } catch (\Exception $e2) {}
        }
    }

    public function updateStatus($id)
    {
        $repartidorId = $this->getRepartidorId();
        if (!$repartidorId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $newStatus = $data['status'] ?? '';

        $validStatuses = ['pendiente', 'confirmada', 'cambio', 'devolucion', 'rechazada'];
        if (!in_array($newStatus, $validStatuses)) {
            $this->json(['error' => 'Estado inválido'], 400);
            return;
        }

        $order = Database::query("SELECT * FROM pedidos WHERE id = ?", [$id])->fetch();
        if (!$order) {
            $this->json(['error' => 'Pedido no encontrado'], 404);
            return;
        }

        $this->ensureAsignadoStatus();

        if ($newStatus === 'entregado') {
            $ganancia = floatval($order['ganancias_repartidor'] ?? 5000);
            Database::query(
                "UPDATE pedidos SET estado = ?, fecha_entrega_real = NOW() WHERE id = ?",
                [$newStatus, $id]
            );
            Database::query(
                "INSERT INTO historial_entregas (pedido_id, repartidor_id, ganancia, tiempo_entrega_minutos) VALUES (?, ?, ?, TIMESTAMPDIFF(MINUTE, ?, NOW()))",
                [$id, $order['repartidor_id'] ?: $repartidorId, $ganancia, $order['fecha_pedido']]
            );
        } elseif ($newStatus === 'asignado') {
            Database::query(
                "UPDATE pedidos SET estado = ?, repartidor_id = ?, fecha_estimada_entrega = DATE_ADD(NOW(), INTERVAL COALESCE(tiempo_estimado_minutos, 30) MINUTE) WHERE id = ?",
                [$newStatus, $repartidorId, $id]
            );
        } elseif ($newStatus === 'en_camino' && !$order['repartidor_id']) {
            Database::query(
                "UPDATE pedidos SET estado = ?, repartidor_id = ?, fecha_estimada_entrega = DATE_ADD(NOW(), INTERVAL COALESCE(tiempo_estimado_minutos, 30) MINUTE) WHERE id = ?",
                [$newStatus, $repartidorId, $id]
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
            'message' => 'Estado actualizado',
        ]);
    }

    public function destroy($id)
    {
        $repartidorId = $this->getRepartidorId();
        if (!$repartidorId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        Database::query("DELETE FROM detalles_pedido WHERE pedido_id = ?", [$id]);
        Database::query("DELETE FROM pedidos WHERE id = ?", [$id]);

        $this->json(['message' => 'Pedido eliminado']);
    }
}
