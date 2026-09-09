<?php
namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\JWTHelper;

class RepartidorRastreoController
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

    public function listar()
    {
        $repartidorId = $this->getRepartidorId();
        if (!$repartidorId) {
            $this->json(['success' => false, 'error' => 'Tu sesión no es válida o ha expirado'], 401);
            return;
        }

        $pedidos = Database::query(
            "SELECT p.* FROM pedidos p
             WHERE p.repartidor_id = ? AND p.estado IN ('asignado','aceptado','recogido','en_camino')
             ORDER BY p.fecha_asignacion DESC",
            [$repartidorId]
        )->fetchAll();

        $resultado = array_map(function ($p) use ($repartidorId) {
            return $this->serializar($p, $repartidorId);
        }, $pedidos);

        $this->json(['success' => true, 'pedidos' => $resultado]);
    }

    public function buscar()
    {
        $repartidorId = $this->getRepartidorId();
        if (!$repartidorId) {
            $this->json(['success' => false, 'error' => 'Tu sesión no es válida o ha expirado'], 401);
            return;
        }

        $numero = trim($_GET['numero'] ?? '');
        if ($numero === '') {
            $this->json(['success' => false, 'error' => 'Ingresa un número de pedido para realizar la búsqueda.'], 422);
            return;
        }

        $pedido = Database::query(
            "SELECT * FROM pedidos WHERE numero_pedido = ?",
            [$numero]
        )->fetch();

        if (!$pedido) {
            $this->json(['success' => false, 'error' => 'Pedido no encontrado.'], 404);
            return;
        }

        if ((int)($pedido['repartidor_id'] ?? 0) !== $repartidorId) {
            $this->json(['success' => false, 'error' => 'No tienes permisos para consultar este pedido.'], 403);
            return;
        }

        $this->json(['success' => true, 'pedido' => $this->serializar($pedido, $repartidorId)]);
    }

    private function serializar($p, $repartidorId)
    {
        $items = Database::query(
            "SELECT * FROM detalles_pedido WHERE pedido_id = ?",
            [$p['id']]
        )->fetchAll();

        $ubicacionRepartidor = Database::query(
            "SELECT latitud, longitud, timestamp_ubicacion, velocidad_kmh, bateria_porcentaje
             FROM seguimiento_tiempo_real
             WHERE pedido_id = ? ORDER BY timestamp_ubicacion DESC LIMIT 1",
            [$p['id']]
        )->fetch();

        return [
            'id' => $p['id'],
            'numero_pedido' => $p['numero_pedido'],
            'estado' => $p['estado'],
            'prioridad' => $p['prioridad'] ?? 'normal',
            'cliente' => $p['nombre_cliente'],
            'telefono' => $p['telefono_cliente'],
            'direccion' => $p['direccion_envio'],
            'ciudad' => $p['ciudad'] ?? '',
            'fecha_pedido' => $p['fecha_pedido'],
            'total' => floatval($p['total']),
            'moneda' => 'COP',
            'notas_cliente' => $p['notas_cliente'] ?? '',
            'destino' => [
                'latitud' => $p['latitud_destino'] !== null ? floatval($p['latitud_destino']) : null,
                'longitud' => $p['longitud_destino'] !== null ? floatval($p['longitud_destino']) : null,
            ],
            'repartidor_ubicacion' => $ubicacionRepartidor ? [
                'latitud' => floatval($ubicacionRepartidor['latitud']),
                'longitud' => floatval($ubicacionRepartidor['longitud']),
                'velocidad_kmh' => floatval($ubicacionRepartidor['velocidad_kmh'] ?? 0),
                'bateria' => intval($ubicacionRepartidor['bateria_porcentaje'] ?? 0),
                'timestamp' => $ubicacionRepartidor['timestamp_ubicacion'],
            ] : null,
            'items' => array_map(function ($i) {
                return [
                    'producto' => $i['nombre_producto'],
                    'cantidad' => $i['cantidad'],
                    'precio' => floatval($i['precio_unitario']),
                ];
            }, $items),
        ];
    }

    public function historial($id)
    {
        $repartidorId = $this->getRepartidorId();
        if (!$repartidorId) {
            $this->json(['success' => false, 'error' => 'Tu sesión no es válida o ha expirado'], 401);
            return;
        }

        $pedido = Database::query(
            "SELECT * FROM pedidos WHERE id = ?",
            [$id]
        )->fetch();

        if (!$pedido) {
            $this->json(['success' => false, 'error' => 'Pedido no encontrado.'], 404);
            return;
        }

        if ((int)($pedido['repartidor_id'] ?? 0) !== $repartidorId) {
            $this->json(['success' => false, 'error' => 'No tienes permisos para consultar este pedido.'], 403);
            return;
        }

        $registros = Database::query(
            "SELECT estado_nuevo, estado_anterior, observacion, fecha_cambio
             FROM historial_pedidos_repartidor
             WHERE pedido_id = ? ORDER BY fecha_cambio ASC",
            [$id]
        )->fetchAll();

        $timeline = $this->construirTimeline($pedido, $registros);

        $this->json(['success' => true, 'estado' => $pedido['estado'], 'timeline' => $timeline]);
    }

    private function construirTimeline($pedido, $registros)
    {
        $tl = [];

        $tl[] = ['estado' => 'pendiente', 'titulo' => 'Pedido creado', 'fecha' => $pedido['fecha_pedido'], 'completado' => true];

        $registroPorEstado = [];
        foreach ($registros as $r) {
            $registroPorEstado[$r['estado_nuevo']] = $r;
        }

        $tl[] = ['estado' => 'confirmado', 'titulo' => 'Confirmado', 'fecha' => null, 'completado' => in_array($pedido['estado'], ['confirmado','procesando','listo','asignado','aceptado','recogido','en_camino','entregado'])];
        $tl[] = ['estado' => 'procesando', 'titulo' => 'En preparación', 'fecha' => null, 'completado' => in_array($pedido['estado'], ['procesando','listo','asignado','aceptado','recogido','en_camino','entregado'])];
        $tl[] = ['estado' => 'listo', 'titulo' => 'Listo para recoger', 'fecha' => null, 'completado' => in_array($pedido['estado'], ['listo','asignado','aceptado','recogido','en_camino','entregado'])];

        if (in_array($pedido['estado'], ['asignado','aceptado','recogido','en_camino','entregado'])) {
            $tl[] = ['estado' => 'asignado', 'titulo' => 'Asignado a repartidor', 'fecha' => $pedido['fecha_asignacion'] ?: null, 'completado' => true];
        }

        if (in_array($pedido['estado'], ['en_camino','entregado'])) {
            $tl[] = ['estado' => 'en_camino', 'titulo' => 'En camino', 'fecha' => $pedido['fecha_recogida'] ?: null, 'completado' => ($pedido['estado'] === 'en_camino' || $pedido['estado'] === 'entregado')];
        }

        $tl[] = ['estado' => 'entregado', 'titulo' => 'Entregado', 'fecha' => $pedido['fecha_entrega_real'] ?: null, 'completado' => ($pedido['estado'] === 'entregado')];

        return $tl;
    }

    public function actualizar($id)
    {
        $repartidorId = $this->getRepartidorId();
        if (!$repartidorId) {
            $this->json(['success' => false, 'error' => 'Tu sesión no es válida o ha expirado'], 401);
            return;
        }

        $pedido = Database::query(
            "SELECT * FROM pedidos WHERE id = ?",
            [$id]
        )->fetch();

        if (!$pedido) {
            $this->json(['success' => false, 'error' => 'Pedido no encontrado.'], 404);
            return;
        }

        if ((int)($pedido['repartidor_id'] ?? 0) !== $repartidorId) {
            $this->json(['success' => false, 'error' => 'No tienes permisos para actualizar este pedido.'], 403);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $nuevoEstado = $data['estado'] ?? '';

        $estadoActual = $pedido['estado'];
        $permitidos = [
            'asignado' => ['aceptado', 'recogido', 'en_camino', 'entregado'],
            'aceptado' => ['recogido', 'en_camino', 'entregado'],
            'recogido' => ['en_camino', 'entregado'],
            'en_camino' => ['entregado'],
        ];

        if (!isset($permitidos[$estadoActual]) || !in_array($nuevoEstado, $permitidos[$estadoActual])) {
            $this->json(['success' => false, 'error' => "Transición no permitida de '{$estadoActual}' a '{$nuevoEstado}'."], 400);
            return;
        }

        $estadosValidos = [
            'asignado', 'aceptado', 'recogido', 'en_camino', 'entregado', 'cancelado'
        ];
        if (!in_array($nuevoEstado, $estadosValidos)) {
            $this->json(['success' => false, 'error' => 'Estado inválido.'], 400);
            return;
        }

        $extra = '';
        $params = [$nuevoEstado, $id];
        if ($nuevoEstado === 'aceptado') {
            $extra = ", fecha_aceptacion = NOW()";
        } elseif ($nuevoEstado === 'recogido' || $nuevoEstado === 'en_camino') {
            $extra = ", fecha_recogida = NOW()";
        } elseif ($nuevoEstado === 'entregado') {
            $extra = ", fecha_entrega_real = NOW()";
        }

        Database::query("UPDATE pedidos SET estado = ?{$extra} WHERE id = ?", $params);

        Database::query(
            "INSERT INTO historial_pedidos_repartidor (pedido_id, repartidor_id, estado_anterior, estado_nuevo) VALUES (?, ?, ?, ?)",
            [$id, $repartidorId, $estadoActual, $nuevoEstado]
        );

        if ($nuevoEstado === 'entregado') {
            $ganancia = floatval($pedido['ganancias_repartidor'] ?? 5000);
            Database::query(
                "INSERT INTO historial_entregas (pedido_id, repartidor_id, ganancia, tiempo_entrega_minutos) VALUES (?, ?, ?, TIMESTAMPDIFF(MINUTE, ?, NOW()))",
                [$id, $repartidorId, $ganancia, $pedido['fecha_pedido']]
            );
        }

        $actualizado = Database::query(
            "SELECT * FROM pedidos WHERE id = ?",
            [$id]
        )->fetch();

        $this->json(['success' => true, 'pedido' => $this->serializar($actualizado, $repartidorId)]);
    }
}
