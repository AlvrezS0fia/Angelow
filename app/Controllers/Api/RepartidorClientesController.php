<?php
/**
 * ============================================================
 * ARCHIVO: RepartidorClientesController.php — MÓDULO: API de clientes del repartidor
 * ============================================================
 * QUÉ HACE: Permite al repartidor ver los clientes asociados a sus pedidos
 *   (quienes le han comprado). Búsqueda por nombre/email/teléfono.
 * MODELO(S) QUE USA: Ninguno — usa Database::query() directamente.
 * ENDPOINTS/RUTAS: GET /api/repartidor/clientes, GET /api/repartidor/clientes/{id}
 * QUIÉN LO CONSUME: app repartidor (sección de clientes en la interfaz de reparto)
 */
namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\JWTHelper;

/**
 * Controlador para consultar clientes del repartidor.
 * Filtra por pedidos asignados al repartidor autenticado.
 */
class RepartidorClientesController
{
    /**
     * Extrae el ID del repartidor desde JWT (Authorization header)
     * o desde sesión PHP como fallback.
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
     * GET /api/repartidor/clientes — Lista clientes que tienen pedidos
     *   asignados a este repartidor. Soporta búsqueda por nombre/email/teléfono.
     */
    public function index()
    {
        $repartidorId = $this->getRepartidorId();
        if (!$repartidorId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        $search = $_GET['search'] ?? null;

        $sql = "SELECT DISTINCT u.id, u.nombre, u.email, u.telefono, u.direccion, u.ciudad
                FROM usuarios u
                JOIN pedidos p ON p.usuario_id = u.id
                WHERE p.repartidor_id = ?";
        $params = [$repartidorId];

        if ($search) {
            $sql .= " AND (u.nombre LIKE ? OR u.email LIKE ? OR u.telefono LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY u.nombre";

        $clients = Database::query($sql, $params)->fetchAll();

        $result = array_map(function ($c) {
            return [
                'id' => $c['id'],
                'name' => $c['nombre'],
                'email' => $c['email'] ?? '',
                'phone' => $c['telefono'] ?? '',
                'address' => $c['direccion'] ?? '',
                'city' => $c['ciudad'] ?? '',
            ];
        }, $clients);

        $this->json($result);
    }

    /**
     * GET /api/repartidor/clientes/{id} — Detalle de un cliente específico.
     */
    public function show($id)
    {
        $repartidorId = $this->getRepartidorId();
        if (!$repartidorId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        $client = Database::query(
            "SELECT id, nombre as name, email, telefono as phone, direccion as address, ciudad as city 
             FROM usuarios WHERE id = ?",
            [$id]
        )->fetch();

        if (!$client) {
            $this->json(['error' => 'Cliente no encontrado'], 404);
            return;
        }

        $this->json($client);
    }
}
