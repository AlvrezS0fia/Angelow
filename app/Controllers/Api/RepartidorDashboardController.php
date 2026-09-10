<?php
/**
 * ============================================================
 * ARCHIVO: RepartidorDashboardController.php — MÓDULO: Dashboard del repartidor
 * ============================================================
 * QUÉ HACE: Retorna estadísticas y datos del dashboard del repartidor:
 *   pedidos de hoy, pendientes, en tránsito, entregados, ganancias,
 *   clientes únicos, calificación promedio, pedidos recientes y notificaciones.
 * MODELO(S) QUE USA: Ninguno — usa Database::query() directamente.
 * ENDPOINTS/RUTAS: GET /api/repartidor/dashboard/stats,
 *   GET /api/repartidor/dashboard/recent, GET /api/repartidor/dashboard/low-stock,
 *   GET /api/repartidor/dashboard/notificaciones
 * QUIÉN LO CONSUME: app repartidor (dashboard principal de la app)
 */
namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\JWTHelper;

/**
 * Controlador del dashboard del repartidor. Consulta métricas personales
 * de pedidos, ganancias y calificaciones desde la BD.
 */
class RepartidorDashboardController
{
    /**
     * Extrae el ID del repartidor desde JWT o sesión PHP.
     */
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

    /**
     * Retorna JSON con cabeceras HTTP y sale del script.
     */
    private function json($data, $code = 200)
    {
        if (ob_get_length()) ob_clean();
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * GET /api/repartidor/dashboard/stats — Estadísticas del repartidor:
     *   pedidos hoy, pendientes, en tránsito, entregados, ganancias del día,
     *   total clientes y calificación promedio.
     */
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

    /**
     * GET /api/repartidor/dashboard/recent — Últimos 10 pedidos del repartidor.
     */
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

    /**
     * GET /api/repartidor/dashboard/low-stock — Retorna array vacío (stub).
     * Los repartidores no manejan inventario directamente.
     */
    public function lowStock()
    {
        $this->json([]);
    }

    /**
     * GET /api/repartidor/dashboard/notificaciones — Últimas 20 notificaciones.
     * Mapea tipos internos (aprobacion, rechazo, suspension, nuevo_pedido)
     * a títulos legibles para la app.
     */
    public function notificaciones()
    {
        $repartidorId = $this->getRepartidorId();
        if (!$repartidorId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        $notificaciones = Database::query(
            "SELECT id, titulo, mensaje, tipo, leida, fecha_envio
             FROM notificaciones
             WHERE usuario_id = ?
             ORDER BY fecha_envio DESC
             LIMIT 20",
            [$repartidorId]
        )->fetchAll();

        $resultado = array_map(function ($n) {
            $titulo = $n['titulo'] ?? 'Notificación';
            $mensaje = $n['mensaje'] ?? '';
            if ($n['tipo'] === 'aprobacion_repartidor') {
                $titulo = 'Solicitud aprobada';
            } elseif ($n['tipo'] === 'rechazo_repartidor') {
                $titulo = 'Solicitud rechazada';
            } elseif ($n['tipo'] === 'suspension_repartidor') {
                $titulo = 'Cuenta suspendida';
            } elseif ($n['tipo'] === 'nuevo_pedido') {
                $titulo = 'Nuevo pedido disponible';
            } elseif ($n['tipo'] === 'solicitud_repartidor') {
                $titulo = 'Solicitud';
            }
            return [
                'id' => $n['id'],
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'tipo' => $n['tipo'],
                'fecha' => $n['fecha_envio'],
            ];
        }, $notificaciones);

        $this->json(['success' => true, 'notificaciones' => $resultado]);
    }
}
