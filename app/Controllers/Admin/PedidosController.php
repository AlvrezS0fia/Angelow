<?php
/**
 * ============================================================
 * ARCHIVO: PedidosController.php — MÓDULO: Administración de pedidos
 * ============================================================
 * QUÉ HACE: Gestión admin de pedidos: listar todos, obtener detalles
 *   completos de uno y actualizar su estado (pendiente → entregado, etc.).
 * MODELO(S) QUE USA: PedidoModel
 * ENDPOINTS/RUTAS: GET /admin/pedidos, GET /api/admin/pedidos,
 *   GET /api/admin/pedidos/{id}, PUT /api/admin/pedidos/status
 * QUIÉN LO CONSUME: panel.js (sección de pedidos del administrador)
 */
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\PedidoModel;

/**
 * Controlador admin para la gestión y seguimiento de pedidos.
 */
class PedidosController extends Controller
{
    private $pedidoModel;

    public function __construct() {
        $this->pedidoModel = new PedidoModel();
    }

    /**
     * Redirige al dashboard admin. Solo accesible para administradores.
     */
    public function index()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->redirect('/auth/login');
            return;
        }
        $this->redirect('/admin');
    }

    /**
     * Retorna todos los pedidos como JSON (solo admin).
     * @return JSON array de pedidos
     */
    public function obtenerPedidos()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }

        try {
            $pedidos = $this->pedidoModel->getAll();
            $this->json($pedidos);
        } catch (\Exception $e) {
            error_log('[Admin\\Pedidos] obtenerPedidos: ' . $e->getMessage());
            $this->json(['error' => 'Error al obtener pedidos'], 500);
        }
    }

    /**
     * Retorna los detalles completos de un pedido específico.
     * @param int $pedidoId ID del pedido a consultar
     */
    public function obtenerPedido($pedidoId)
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }

        try {
            $pedido = $this->pedidoModel->getDetallesCompletos((int)$pedidoId);
            if (!$pedido) {
                $this->json(['error' => 'Pedido no encontrado'], 404);
                return;
            }
            $this->json(['success' => true, 'pedido' => $pedido]);
        } catch (\Exception $e) {
            error_log('[Admin\\Pedidos] obtenerPedido: ' . $e->getMessage());
            $this->json(['error' => 'Error al obtener pedido'], 500);
        }
    }

    /**
     * Actualiza el estado de un pedido. Valida contra la whitelist
     * PedidoModel::ESTADOS_VALIDOS antes de persistir.
     */
    public function updateStatus()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $pedidoId = (int)($data['id'] ?? 0);
            $estado = $data['estado'] ?? '';

            if (!$pedidoId || !$estado) {
                $this->json(['error' => 'Datos inválidos: id y estado son requeridos'], 400);
                return;
            }

            if (!in_array(strtolower($estado), PedidoModel::ESTADOS_VALIDOS)) {
                $this->json([
                    'error' => 'Estado inválido. Estados permitidos: ' . implode(', ', PedidoModel::ESTADOS_VALIDOS)
                ], 400);
                return;
            }

            $pedido = $this->pedidoModel->getById($pedidoId);
            if (!$pedido) {
                $this->json(['error' => 'Pedido no encontrado'], 404);
                return;
            }

            $resultado = $this->pedidoModel->updateEstado($pedidoId, $estado);
            if ($resultado) {
                $this->json([
                    'success' => true,
                    'message' => 'Estado actualizado a "' . ucfirst($estado) . '"',
                    'estado' => $estado
                ]);
            } else {
                $this->json(['error' => 'Error al actualizar el estado'], 500);
            }
        } catch (\Exception $e) {
            error_log("[Admin\\Pedidos] updateStatus: " . $e->getMessage());
            $this->json(['error' => 'No se pudo actualizar el estado del pedido'], 400);
        }
    }
}