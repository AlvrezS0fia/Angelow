<?php
namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\JWTHelper;

class RepartidorSeguimientoController
{
    private function getRepartidorId()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);
        $payload = JWTHelper::decode($token);
        return $payload['sub'] ?? null;
    }

    private function json($data, $code = 200)
    {
        if (ob_get_length()) ob_clean();
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function ubicacion()
    {
        $repartidorId = $this->getRepartidorId();
        if (!$repartidorId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $pedidoId = $data['pedido_id'] ?? null;
        $latitud = $data['latitud'] ?? null;
        $longitud = $data['longitud'] ?? null;
        $velocidad = $data['velocidad_kmh'] ?? null;
        $bateria = $data['bateria_porcentaje'] ?? null;

        if (!$pedidoId || $latitud === null || $longitud === null) {
            $this->json(['error' => 'pedido_id, latitud y longitud requeridos'], 400);
            return;
        }

        Database::query(
            "INSERT INTO seguimiento_tiempo_real (pedido_id, repartidor_id, latitud, longitud, velocidad_kmh, bateria_porcentaje) VALUES (?, ?, ?, ?, ?, ?)",
            [$pedidoId, $repartidorId, $latitud, $longitud, $velocidad, $bateria]
        );

        $this->json(['success' => true, 'message' => 'Ubicación actualizada']);
    }

    public function show($pedidoId)
    {
        $repartidorId = $this->getRepartidorId();
        if (!$repartidorId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        $tracking = Database::query(
            "SELECT * FROM seguimiento_tiempo_real WHERE pedido_id = ? ORDER BY timestamp_ubicacion DESC LIMIT 1",
            [$pedidoId]
        )->fetch();

        $order = Database::query(
            "SELECT p.*, u.nombre as repartidor_nombre, u.telefono as repartidor_telefono 
             FROM pedidos p 
             LEFT JOIN usuarios u ON p.repartidor_id = u.id 
             WHERE p.id = ?",
            [$pedidoId]
        )->fetch();

        if (!$tracking) {
            $this->json([
                'success' => true,
                'pedido_id' => $pedidoId,
                'estado' => $order['estado'] ?? 'desconocido',
                'ubicacion' => null,
                'repartidor' => $order ? [
                    'nombre' => $order['repartidor_nombre'] ?? '',
                    'telefono' => $order['repartidor_telefono'] ?? '',
                ] : null,
            ]);
            return;
        }

        $this->json([
            'success' => true,
            'pedido_id' => $pedidoId,
            'estado' => $order['estado'] ?? '',
            'ubicacion' => [
                'latitud' => floatval($tracking['latitud']),
                'longitud' => floatval($tracking['longitud']),
                'velocidad_kmh' => floatval($tracking['velocidad_kmh'] ?? 0),
                'bateria_porcentaje' => intval($tracking['bateria_porcentaje'] ?? 0),
                'timestamp' => $tracking['timestamp_ubicacion'],
            ],
            'repartidor' => $order ? [
                'nombre' => $order['repartidor_nombre'] ?? '',
                'telefono' => $order['repartidor_telefono'] ?? '',
                'tipo_vehiculo' => '',
                'placa_vehiculo' => '',
            ] : null,
            'destino' => $order ? [
                'direccion' => $order['direccion_envio'] ?? '',
                'latitud' => $order['latitud_destino'] ? floatval($order['latitud_destino']) : null,
                'longitud' => $order['longitud_destino'] ? floatval($order['longitud_destino']) : null,
            ] : null,
        ]);
    }
}
