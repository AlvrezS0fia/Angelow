<?php
namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\JWTHelper;

class RepartidorDashboardController
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

    public function stats()
    {
        $repartidorId = $this->getRepartidorId();
        if (!$repartidorId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        $user = Database::query("SELECT * FROM usuarios WHERE id = ?", [$repartidorId])->fetch();

        $pedidosHoy = Database::query(
            "SELECT COUNT(*) as total FROM pedidos WHERE repartidor_id = ? AND DATE(fecha_pedido) = CURDATE()",
            [$repartidorId]
        )->fetch();

        $pedidosPendientes = Database::query(
            "SELECT COUNT(*) as total FROM pedidos WHERE repartidor_id = ? AND estado = 'pendiente'",
            [$repartidorId]
        )->fetch();

        $enTransito = Database::query(
            "SELECT COUNT(*) as total FROM pedidos WHERE repartidor_id = ? AND estado = 'en_camino'",
            [$repartidorId]
        )->fetch();

        $entregados = Database::query(
            "SELECT COUNT(*) as total FROM pedidos WHERE repartidor_id = ? AND estado = 'entregado'",
            [$repartidorId]
        )->fetch();

        $gananciasHoy = Database::query(
            "SELECT COALESCE(SUM(ganancia), 0) as total FROM historial_entregas WHERE repartidor_id = ? AND DATE(fecha_entrega) = CURDATE()",
            [$repartidorId]
        )->fetch();

        $totalClientes = Database::query(
            "SELECT COUNT(DISTINCT p.usuario_id) as total FROM pedidos p WHERE p.repartidor_id = ?",
            [$repartidorId]
        )->fetch();

        $calificacion = Database::query(
            "SELECT COALESCE(AVG(calificacion_cliente), 0) as promedio FROM historial_entregas WHERE repartidor_id = ? AND calificacion_cliente IS NOT NULL",
            [$repartidorId]
        )->fetch();

        $this->json([
            'total_products' => intval($user['total_entregas'] ?? 0),
            'total_sales' => floatval($user['ganancias_totales'] ?? 0),
            'today_orders' => intval($pedidosHoy['total'] ?? 0),
            'total_clients' => intval($totalClientes['total'] ?? 0),
            'pending_orders' => intval($pedidosPendientes['total'] ?? 0),
            'in_transit' => intval($enTransito['total'] ?? 0),
            'total_stock' => intval($entregados['total'] ?? 0),
            'low_stock_products' => 0,
            'today_earnings' => floatval($gananciasHoy['total'] ?? 0),
            'total_deliveries' => intval($user['total_entregas'] ?? 0),
            'rating' => floatval($calificacion['promedio'] ?? $user['calificacion_promedio'] ?? 5.00),
        ]);
    }

    public function recentOrders()
    {
        $repartidorId = $this->getRepartidorId();
        if (!$repartidorId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        $orders = Database::query(
            "SELECT id, numero_pedido, nombre_cliente as client_name, total, estado, fecha_pedido as created_at 
             FROM pedidos WHERE repartidor_id = ? 
             ORDER BY fecha_pedido DESC LIMIT 10",
            [$repartidorId]
        )->fetchAll();

        $result = array_map(function ($o) {
            $o['total'] = floatval($o['total']);
            return $o;
        }, $orders);

        $this->json($result);
    }

    public function lowStock()
    {
        $this->json([]);
    }
}
