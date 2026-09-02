<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\FacturaModel;

class FacturasController extends Controller
{
    private function requireAdmin()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->json(['error' => 'No autorizado'], 403);
            return false;
        }
        return true;
    }

    public function index()
    {
        if (!$this->requireAdmin()) return;

        $estado = $_GET['estado'] ?? 'all';
        $model = new FacturaModel();
        $facturas = $model->getAll($estado);
        $this->json($facturas);
    }

    public function stats()
    {
        if (!$this->requireAdmin()) return;

        $model = new FacturaModel();
        $stats = $model->getStats();
        $this->json($stats);
    }

    public function show($id)
    {
        if (!$this->requireAdmin()) return;

        $model = new FacturaModel();
        $factura = $model->getById($id);

        if (!$factura) {
            $this->json(['error' => 'Factura no encontrada'], 404);
            return;
        }

        $this->json($factura);
    }

    public function crear()
    {
        if (!$this->requireAdmin()) return;

        $data = json_decode(file_get_contents('php://input'), true);
        $pedidoId = $data['pedido_id'] ?? null;
        $adminId = $_SESSION['user']['id'] ?? null;

        if (!$pedidoId) {
            $this->json(['error' => 'pedido_id es requerido'], 400);
            return;
        }

        $model = new FacturaModel();
        $factura = $model->crearDesdePedido($pedidoId, $adminId);

        if (!$factura) {
            $this->json(['error' => 'No se pudo crear la factura'], 400);
            return;
        }

        $this->json(['success' => true, 'factura' => $factura]);
    }

    public function cambiarEstado($id)
    {
        if (!$this->requireAdmin()) return;

        $data = json_decode(file_get_contents('php://input'), true);
        $nuevoEstado = $data['estado'] ?? null;
        $adminId = $_SESSION['user']['id'] ?? null;
        $notas = $data['notas'] ?? null;

        $estadosValidos = ['pendiente', 'autorizada', 'cancelada', 'devolucion'];
        if (!$nuevoEstado || !in_array($nuevoEstado, $estadosValidos)) {
            $this->json(['error' => 'Estado inválido. Valores permitidos: ' . implode(', ', $estadosValidos)], 400);
            return;
        }

        $model = new FacturaModel();
        [$factura, $error] = $model->cambiarEstado($id, $nuevoEstado, $adminId, $notas);

        if ($error) {
            $this->json(['error' => $error], 400);
            return;
        }

        $this->json(['success' => true, 'factura' => $factura]);
    }

    public function enviarCorreo($id)
    {
        if (!$this->requireAdmin()) return;

        $model = new FacturaModel();
        $factura = $model->getById($id);

        if (!$factura) {
            $this->json(['error' => 'Factura no encontrada'], 404);
            return;
        }

        $model->marcarEnviadoCorreo($id);
        $this->json(['success' => true, 'message' => 'Factura marcada como enviada por correo']);
    }

    public function pdf($id)
    {
        if (!$this->requireAdmin()) return;

        $model = new FacturaModel();
        $factura = $model->getById($id);

        if (!$factura) {
            $this->json(['error' => 'Factura no encontrada'], 404);
            return;
        }

        $this->json(['error' => 'Generación de PDF no disponible aún. Use la vista de factura del cliente.'], 501);
    }

    public function porPedido($pedidoId)
    {
        if (!$this->requireAdmin()) return;

        $model = new FacturaModel();
        $factura = $model->getByPedido($pedidoId);

        if (!$factura) {
            $this->json(['error' => 'No existe factura para este pedido'], 404);
            return;
        }

        $this->json($factura);
    }
}
