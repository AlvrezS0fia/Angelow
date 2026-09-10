<?php
/**
 * ============================================================
 * ARCHIVO: AdminRepartidorController.php — MÓDULO: API de administración de repartidores
 * ============================================================
 * QUÉ HACE: Permite al administrador revisar solicitudes de repartidores
 *   (aprobar, rechazar, suspender, reactivar), gestionar documentos,
 *   ver estadísticas y dashboard. Usa Database::query directamente.
 * MODELO(S) QUE USA: Ninguno — usa Database::query() directamente.
 * ENDPOINTS/RUTAS: GET/POST solicitudes, aprobar, rechazar, suspender,
 *   activar, estadisticas, activos, revisarDocumento, dashboardStats
 * QUIÉN LO CONSUME: panel.js (sección de repartidores del administrador)
 */
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;

/**
 * Controlador admin para todo el ciclo de vida de repartidores:
 * solicitudes de registro, aprobación/rechazo, suspensión y documentos.
 * No extiende Controller base; define sus propios helpers json() y requireAdmin().
 */
class AdminRepartidorController extends Controller
{
    /**
     * Verifica que haya sesión de administrador. Si no, retorna 403 y false.
     * Diferente al guardián de otros controladores: retorna bool en vez de salir.
     */
    private function requireAdmin()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->json(['error' => 'No autorizado'], 403);
            return false;
        }
        return true;
    }

    /**
     * Retorna JSON con cabeceras HTTP y sale del script.
     * Usa ob_clean para evitar basura de output buffering.
     */
    private function jsonOut($data, $code = 200)
    {
        if (ob_get_length()) ob_clean();
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Lista solicitudes de repartidor con paginación y filtro por estado.
     * Adjunta documentos y vehículo a cada solicitud.
     */
    public function solicitudes()
    {
        if (!$this->requireAdmin()) return;

        $estado = $_GET['estado'] ?? 'pendiente';
        $perPage = (int) ($_GET['per_page'] ?? 50);
        $page = (int) ($_GET['page'] ?? 1);
        $offset = max(0, ($page - 1) * $perPage);

        $where = "WHERE s.estado = :estado";
        $params = [':estado' => $estado];

        if ($estado === 'pendiente') {
            $where = "WHERE s.estado IN ('pendiente')";
            $params = [];
        } elseif ($estado !== 'todas' && $estado !== 'all') {
            $where = "WHERE s.estado = :estado";
            $params = [':estado' => $estado];
        } else {
            $where = "";
            $params = [];
        }

        $totalStmt = Database::query("SELECT COUNT(*) FROM solicitudes_repartidores s $where", $params);
        $total = (int) $totalStmt->fetchColumn();

        $sql = "SELECT s.*
                FROM solicitudes_repartidores s
                $where
                ORDER BY 
                    CASE s.estado WHEN 'pendiente' THEN 0 ELSE 1 END,
                    s.fecha_solicitud DESC
                LIMIT :limit OFFSET :offset";
        $stmt = Database::getInstance()->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, \PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $solicitudes = $stmt->fetchAll();

        foreach ($solicitudes as &$sol) {
            $sol['documentos'] = $this->getDocumentos($sol['id']);
            $sol['vehiculo'] = $this->getVehiculo($sol['usuario_id']);
        }
        unset($sol);

        $this->jsonOut([
            'success' => true,
            'solicitudes' => $solicitudes,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, ceil($total / $perPage)),
        ]);
    }

    /**
     * Obtiene los documentos asociados a una solicitud de repartidor.
     * Adjunta la URL pública de descarga a cada documento.
     */
    private function getDocumentos($solicitudId)
    {
        try {
            $stmt = Database::query(
                "SELECT id, repartidor_id, tipo, archivo_url, estado, observaciones, fecha_subida FROM documentos WHERE solicitud_id = ? ORDER BY fecha_subida DESC",
                [$solicitudId]
            );
            $docs = $stmt->fetchAll();
            foreach ($docs as &$d) {
                $d['url'] = APP_URL . '/api/documentos/' . $d['id'] . '/archivo';
            }
            unset($d);
            return $docs;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtiene el vehículo activo de un repartidor por su usuario_id.
     */
    private function getVehiculo($usuarioId)
    {
        try {
            $stmt = Database::query(
                "SELECT * FROM vehiculos_repartidores WHERE repartidor_id = ? AND activo = 1 ORDER BY id DESC LIMIT 1",
                [$usuarioId]
            );
            return $stmt->fetch();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Aprueba una solicitud de repartidor dentro de una transacción:
     * activa el usuario, aprueba la solicitud, aprueba documentos,
     * otorga permisos por defecto, registra historial y notifica.
     */
    public function aprobar()
    {
        if (!$this->requireAdmin()) return;

        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int) ($data['id'] ?? 0);
        $observaciones = $data['observaciones'] ?? null;
        $adminId = $_SESSION['user']['id'] ?? null;

        if (!$id) {
            $this->jsonOut(['success' => false, 'message' => 'ID de solicitud requerido'], 400);
        }

        $stmt = Database::query("SELECT * FROM solicitudes_repartidores WHERE id = ?", [$id]);
        $solicitud = $stmt->fetch();

        if (!$solicitud) {
            $this->jsonOut(['success' => false, 'message' => 'Solicitud no encontrada'], 404);
        }

        $usuarioId = (int) $solicitud['usuario_id'];

        try {
            Database::getInstance()->getConnection()->beginTransaction();

            Database::query(
                "UPDATE usuarios SET estado = 'activo', apellido = ?, cedula = ?, tipo_documento = ?, fecha_aprobacion = NOW(), aprobado_por = ?, motivo_suspension = NULL, fecha_suspension = NULL WHERE id = ?",
                [
                    $solicitud['apellidos'],
                    $solicitud['numero_documento'],
                    $solicitud['tipo_documento'],
                    $adminId,
                    $usuarioId
                ]
            );

            Database::query(
                "UPDATE solicitudes_repartidores SET estado = 'aprobada', administrador_id = ?, observaciones = COALESCE(?, observaciones), fecha_respuesta = NOW() WHERE id = ?",
                [$adminId, $observaciones, $id]
            );

            Database::query(
                "UPDATE documentos SET estado = 'aprobado', revisado_por = ?, fecha_revision = NOW() WHERE repartidor_id = ? AND estado IN ('pendiente')",
                [$adminId, $usuarioId]
            );

            $this->grantDefaultPermissions($usuarioId, $adminId);

            Database::query(
                "INSERT INTO historial_repartidores (repartidor_id, solicitud_id, administrador_id, estado_anterior, estado_nuevo, accion, observaciones) VALUES (?, ?, ?, 'pendiente', 'activo', 'aprobacion', ?)",
                [$usuarioId, $id, $adminId, $observaciones]
            );

            try {
                Database::query(
                    "INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, enlace, leida, fecha_envio) VALUES (?, 'aprobacion_repartidor', '¡Solicitud aprobada!', 'Tu solicitud para ser repartidor fue aprobada. Ya puedes iniciar sesión y gestionar entregas.', '/repartidor/dashboard', 0, NOW())",
                    [$usuarioId]
                );
            } catch (\Exception $e) {
            }

            Database::getInstance()->getConnection()->commit();

            $this->jsonOut(['success' => true, 'message' => 'Repartidor aprobado correctamente. Ya puede iniciar sesión.']);
        } catch (\Exception $e) {
            Database::getInstance()->getConnection()->rollBack();
            error_log("AdminRepartidor::aprobar ERROR - " . $e->getMessage());
            $this->jsonOut(['success' => false, 'message' => 'Error al aprobar la solicitud'], 500);
        }
    }

    /**
     * Rechaza una solicitud de repartidor dentro de una transacción:
     * marca la solicitud como rechazada, desactiva al usuario,
     * registra historial y envía notificación de rechazo.
     */
    public function rechazar()
    {
        if (!$this->requireAdmin()) return;

        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int) ($data['id'] ?? 0);
        $motivo = $data['motivo'] ?? $data['observaciones'] ?? null;
        $adminId = $_SESSION['user']['id'] ?? null;

        if (!$id) {
            $this->jsonOut(['success' => false, 'message' => 'ID de solicitud requerido'], 400);
        }

        $stmt = Database::query("SELECT * FROM solicitudes_repartidores WHERE id = ?", [$id]);
        $solicitud = $stmt->fetch();

        if (!$solicitud) {
            $this->jsonOut(['success' => false, 'message' => 'Solicitud no encontrada'], 404);
        }

        $usuarioId = (int) $solicitud['usuario_id'];

        try {
            Database::getInstance()->getConnection()->beginTransaction();

            Database::query(
                "UPDATE solicitudes_repartidores SET estado = 'rechazada', administrador_id = ?, motivo_rechazo = ?, observaciones = COALESCE(?, observaciones), fecha_respuesta = NOW() WHERE id = ?",
                [$adminId, $motivo, $motivo, $id]
            );

            Database::query(
                "UPDATE usuarios SET estado = 'inactivo', motivo_suspension = ?, fecha_suspension = NOW() WHERE id = ?",
                [$motivo, $usuarioId]
            );

            Database::query(
                "INSERT INTO historial_repartidores (repartidor_id, solicitud_id, administrador_id, estado_anterior, estado_nuevo, accion, motivo, observaciones) VALUES (?, ?, ?, 'pendiente', 'inactivo', 'rechazo', ?, ?)",
                [$usuarioId, $id, $adminId, $motivo, $motivo]
            );

            try {
                Database::query(
                    "INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, enlace, leida, fecha_envio) VALUES (?, 'rechazo_repartidor', 'Solicitud rechazada', 'Tu solicitud para ser repartidor fue rechazada. Contacta al administrador para más información.', '/repartidor/login', 0, NOW())",
                    [$usuarioId]
                );
            } catch (\Exception $e) {
            }

            Database::getInstance()->getConnection()->commit();

            $this->jsonOut(['success' => true, 'message' => 'Solicitud rechazada correctamente.']);
        } catch (\Exception $e) {
            Database::getInstance()->getConnection()->rollBack();
            error_log("AdminRepartidor::rechazar ERROR - " . $e->getMessage());
            $this->jsonOut(['success' => false, 'message' => 'Error al rechazar la solicitud'], 500);
        }
    }

    /**
     * Otorga permisos por defecto a un repartidor aprobado.
     * Inserta permisos con ON DUPLICATE KEY para ser idempotente.
     */
    private function grantDefaultPermissions($usuarioId, $adminId = null)
    {
        $permissions = [
            'ver_dashboard', 'ver_pedidos', 'aceptar_pedidos', 'actualizar_pedido',
            'marcar_recogido', 'marcar_en_camino', 'marcar_entregado',
            'ver_historial', 'ver_documentos', 'actualizar_perfil'
        ];
        foreach ($permissions as $permiso) {
            try {
                Database::query(
                    "INSERT INTO permisos_repartidores (repartidor_id, permiso, permitido, otorgado_por)
                     VALUES (?, ?, 1, ?)
                     ON DUPLICATE KEY UPDATE permitido = 1, otorgado_por = VALUES(otorgado_por)",
                    [$usuarioId, $permiso, $adminId]
                );
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Suspende un repartidor activo: cambia estado a 'suspendido',
     * registra en historial_repartidores. No envía notificación.
     */
    public function suspender()
    {
        if (!$this->requireAdmin()) return;

        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int) ($data['id'] ?? 0);
        $motivo = $data['motivo'] ?? null;
        $adminId = $_SESSION['user']['id'] ?? null;

        if (!$id) {
            $this->jsonOut(['success' => false, 'message' => 'ID de repartidor requerido'], 400);
        }

        try {
            Database::query(
                "UPDATE usuarios SET estado = 'suspendido', motivo_suspension = ?, fecha_suspension = NOW() WHERE id = ? AND rol = 'repartidor'",
                [$motivo, $id]
            );

            Database::query(
                "INSERT INTO historial_repartidores (repartidor_id, administrador_id, estado_anterior, estado_nuevo, accion, motivo) VALUES (?, ?, 'activo', 'suspendido', 'suspension', ?)",
                [$id, $adminId, $motivo]
            );

            $this->jsonOut(['success' => true, 'message' => 'Repartidor suspendido correctamente.']);
        } catch (\Exception $e) {
            error_log("AdminRepartidor::suspender ERROR - " . $e->getMessage());
            $this->jsonOut(['success' => false, 'message' => 'Error al suspender el repartidor'], 500);
        }
    }

    /**
     * Reactiva un repartidor suspendido: limpia motivo de suspensión,
     * otorga permisos por defecto, registra historial y notifica.
     */
    public function activar()
    {
        if (!$this->requireAdmin()) return;

        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int) ($data['id'] ?? 0);
        $adminId = $_SESSION['user']['id'] ?? null;

        if (!$id) {
            $this->jsonOut(['success' => false, 'message' => 'ID de repartidor requerido'], 400);
        }

        try {
            Database::query(
                "UPDATE usuarios SET estado = 'activo', motivo_suspension = NULL, fecha_suspension = NULL WHERE id = ? AND rol = 'repartidor'",
                [$id]
            );

            $this->grantDefaultPermissions($id, $adminId);

            Database::query(
                "INSERT INTO historial_repartidores (repartidor_id, administrador_id, estado_anterior, estado_nuevo, accion, observaciones) VALUES (?, ?, 'suspendido', 'activo', 'reactivacion', 'Repartidor reactivado por el administrador')",
                [$id, $adminId]
            );

            try {
                Database::query(
                    "INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, enlace, leida, fecha_envio) VALUES (?, 'suspension_repartidor', 'Cuenta reactivada', 'Tu cuenta de repartidor fue reactivada. Ya puedes gestionar entregas.', '/repartidor/dashboard', 0, NOW())",
                    [$id]
                );
            } catch (\Exception $e) {
            }

            $this->jsonOut(['success' => true, 'message' => 'Repartidor reactivado correctamente.']);
        } catch (\Exception $e) {
            error_log("AdminRepartidor::activar ERROR - " . $e->getMessage());
            $this->jsonOut(['success' => false, 'message' => 'Error al reactivar al repartidor'], 500);
        }
    }

    /**
     * Retorna estadísticas generales de repartidores: pendientes,
     * total, activos, suspendidos, rechazadas y total de entregas.
     */
    public function estadisticas()
    {
        if (!$this->requireAdmin()) return;

        $stats = [];

        try {
            $stmt = Database::query("SELECT COUNT(*) FROM solicitudes_repartidores WHERE estado = 'pendiente'");
            $stats['pendientes'] = (int) $stmt->fetchColumn();
        } catch (\Exception $e) {
            $stats['pendientes'] = 0;
        }

        try {
            $stmt = Database::query("SELECT COUNT(*) FROM usuarios WHERE rol = 'repartidor'");
            $stats['total'] = (int) $stmt->fetchColumn();
        } catch (\Exception $e) {
            $stats['total'] = 0;
        }

        try {
            $stmt = Database::query("SELECT COUNT(*) FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo'");
            $stats['activos'] = (int) $stmt->fetchColumn();
        } catch (\Exception $e) {
            $stats['activos'] = 0;
        }

        try {
            $stmt = Database::query("SELECT COUNT(*) FROM usuarios WHERE rol = 'repartidor' AND estado = 'suspendido'");
            $stats['suspendidos'] = (int) $stmt->fetchColumn();
        } catch (\Exception $e) {
            $stats['suspendidos'] = 0;
        }

        try {
            $stmt = Database::query("SELECT COUNT(*) FROM solicitudes_repartidores WHERE estado = 'rechazada'");
            $stats['rechazadas'] = (int) $stmt->fetchColumn();
        } catch (\Exception $e) {
            $stats['rechazadas'] = 0;
        }

        try {
            $stmt = Database::query("SELECT COALESCE(SUM(total_entregas), 0) FROM usuarios WHERE rol = 'repartidor'");
            $stats['total_entregas'] = (int) $stmt->fetchColumn();
        } catch (\Exception $e) {
            $stats['total_entregas'] = 0;
        }

        $this->jsonOut(['success' => true] + $stats);
    }

    /**
     * Lista todos los repartidores con estado 'activo'.
     * Retorna campos seleccionados (sin password_hash ni datos sensibles).
     */
    public function activos()
    {
        if (!$this->requireAdmin()) return;

        $stmt = Database::query(
            "SELECT id, nombre, apellido, email, telefono, estado, tipo_vehiculo, placa_vehiculo, total_entregas, calificacion_promedio
             FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo'
             ORDER BY nombre ASC"
        );
        $this->jsonOut(['success' => true, 'repartidores' => $stmt->fetchAll()]);
    }

    /**
     * Permite al admin aprobar o rechazar individualmente un documento
     * de repartidor, actualizando estado y observaciones.
     */
    public function revisarDocumento()
    {
        if (!$this->requireAdmin()) return;

        $data = json_decode(file_get_contents('php://input'), true);
        $documentoId = (int) ($data['documento_id'] ?? 0);
        $accion = $data['accion'] ?? null;
        $observaciones = $data['observaciones'] ?? null;
        $adminId = $_SESSION['user']['id'] ?? null;

        if (!$documentoId || !in_array($accion, ['aprobar', 'rechazar'])) {
            $this->jsonOut(['success' => false, 'message' => 'Datos inválidos'], 400);
        }

        $nuevoEstado = $accion === 'aprobar' ? 'aprobado' : 'rechazado';

        try {
            Database::query(
                "UPDATE documentos SET estado = ?, observaciones = COALESCE(?, observaciones), revisado_por = ?, fecha_revision = NOW() WHERE id = ?",
                [$nuevoEstado, $observaciones, $adminId, $documentoId]
            );

            $this->jsonOut([
                'success' => true,
                'message' => $accion === 'aprobar' ? 'Documento aprobado' : 'Documento rechazado'
            ]);
        } catch (\Exception $e) {
            error_log("AdminRepartidor::revisarDocumento ERROR - " . $e->getMessage());
            $this->jsonOut(['success' => false, 'message' => 'Error al revisar el documento'], 500);
        }
    }

    /**
     * Retorna estadísticas del dashboard admin: pedidos pendientes,
     * totales, ingresos y repartidores activos.
     */
    public function dashboardStats()
    {
        if (!$this->requireAdmin()) return;

        $stats = [];

        try {
            $stmt = Database::query("SELECT COUNT(*) FROM pedidos WHERE estado = 'pendiente'");
            $stats['pedidos_pendientes'] = (int) $stmt->fetchColumn();
        } catch (\Exception $e) {
            $stats['pedidos_pendientes'] = 0;
        }

        try {
            $stmt = Database::query("SELECT COUNT(*) FROM pedidos");
            $stats['pedidos_totales'] = (int) $stmt->fetchColumn();
        } catch (\Exception $e) {
            $stats['pedidos_totales'] = 0;
        }

        try {
            $stmt = Database::query("SELECT COALESCE(SUM(total), 0) FROM pedidos WHERE estado NOT IN ('cancelado')");
            $stats['ingresos'] = (float) $stmt->fetchColumn();
        } catch (\Exception $e) {
            $stats['ingresos'] = 0;
        }

        try {
            $stmt = Database::query("SELECT COUNT(*) FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo'");
            $stats['repartidores_activos'] = (int) $stmt->fetchColumn();
        } catch (\Exception $e) {
            $stats['repartidores_activos'] = 0;
        }

        $this->jsonOut(['success' => true] + $stats);
    }
}
