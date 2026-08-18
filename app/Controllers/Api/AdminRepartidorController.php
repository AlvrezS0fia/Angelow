<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;

class AdminRepartidorController extends Controller
{
    private function requireAdmin()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->json(['success' => false, 'message' => 'No autorizado'], 401);
        }
        return true;
    }

    private function json($data, $code = 200)
    {
        if (ob_get_length()) ob_clean();
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function solicitudes()
    {
        $this->requireAdmin();

        $estado = $_GET['estado'] ?? 'pendiente';
        
        $solicitudes = Database::query("
            SELECT s.*, u.email as usuario_email 
            FROM solicitudes_repartidores s 
            LEFT JOIN usuarios u ON s.usuario_id = u.id 
            WHERE s.estado = ? 
            ORDER BY s.fecha_solicitud DESC
        ", [$estado])->fetchAll();

        $this->json(['success' => true, 'solicitudes' => $solicitudes]);
    }

    public function aprobar()
    {
        $this->requireAdmin();

        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? 0;
        $observaciones = trim($data['observaciones'] ?? '');

        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID de solicitud requerido'], 400);
        }

        Database::query("
            UPDATE solicitudes_repartidores 
            SET estado = 'aprobada', fecha_respuesta = NOW(), observaciones = ? 
            WHERE id = ? AND estado = 'pendiente'
        ", [$observaciones, $id]);

        Database::query("UPDATE usuarios SET estado = 'activo' WHERE id = (SELECT usuario_id FROM solicitudes_repartidores WHERE id = ?)", [$id]);

        $solicitud = Database::query("SELECT * FROM solicitudes_repartidores WHERE id = ?", [$id])->fetch();
        
        if ($solicitud) {
            $this->json([
                'success' => true, 
                'message' => 'Solicitud aprobada correctamente',
                'solicitud' => $solicitud
            ]);
        } else {
            $this->json(['success' => false, 'message' => 'Solicitud no encontrada'], 404);
        }
    }

    public function rechazar()
    {
        $this->requireAdmin();

        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? 0;
        $observaciones = trim($data['observaciones'] ?? '');

        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID de solicitud requerido'], 400);
        }

        Database::query("
            UPDATE solicitudes_repartidores 
            SET estado = 'rechazada', fecha_respuesta = NOW(), observaciones = ? 
            WHERE id = ? AND estado = 'pendiente'
        ", [$observaciones, $id]);

        Database::query("UPDATE usuarios SET estado = 'inactivo' WHERE id = (SELECT usuario_id FROM solicitudes_repartidores WHERE id = ?)", [$id]);

        $solicitud = Database::query("SELECT * FROM solicitudes_repartidores WHERE id = ?", [$id])->fetch();
        
        if ($solicitud) {
            $this->json([
                'success' => true, 
                'message' => 'Solicitud rechazada',
                'solicitud' => $solicitud
            ]);
        } else {
            $this->json(['success' => false, 'message' => 'Solicitud no encontrada'], 404);
        }
    }

    public function estadisticas()
    {
        $this->requireAdmin();

        $pendientes = Database::query("SELECT COUNT(*) as count FROM solicitudes_repartidores WHERE estado = 'pendiente'")->fetch();
        $aprobadas = Database::query("SELECT COUNT(*) as count FROM solicitudes_repartidores WHERE estado = 'aprobada'")->fetch();
        $rechazadas = Database::query("SELECT COUNT(*) as count FROM solicitudes_repartidores WHERE estado = 'rechazada'")->fetch();
        $activos = Database::query("SELECT COUNT(*) as count FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo'")->fetch();

        $this->json([
            'success' => true,
            'pendientes' => (int)($pendientes['count'] ?? 0),
            'aprobadas' => (int)($aprobadas['count'] ?? 0),
            'rechazadas' => (int)($rechazadas['count'] ?? 0),
            'activos' => (int)($activos['count'] ?? 0)
        ]);
    }
}
