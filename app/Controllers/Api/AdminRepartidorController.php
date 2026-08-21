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
        return $_SESSION['user']['id'];
    }

    private function json($data, $code = 200)
    {
        if (ob_get_length()) ob_clean();
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function notificar($usuarioId, $tipo, $titulo, $mensaje, $enlace = null, $datosAdicionales = null)
    {
        try {
            Database::query(
                "INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, enlace, datos_adicionales, leida, fecha_envio) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())",
                [$usuarioId, $tipo, $titulo, $mensaje, $enlace, $datosAdicionales ? json_encode($datosAdicionales) : null]
            );
        } catch (\Exception $e) {
        }
    }

    private function historial($repartidorId, $solicitudId, $adminId, $accion, $estadoNuevo, $motivo = null, $observaciones = null)
    {
        try {
            Database::query(
                "INSERT INTO historial_repartidores (repartidor_id, solicitud_id, administrador_id, accion, estado_nuevo, motivo, observaciones, fecha_accion) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                [$repartidorId, $solicitudId, $adminId, $accion, $estadoNuevo, $motivo, $observaciones]
            );
        } catch (\Exception $e) {
        }
    }

    private function asignarPermisos($repartidorId, $adminId)
    {
        $permisos = [
            'ver_dashboard', 'ver_pedidos', 'aceptar_pedidos', 'actualizar_pedido',
            'marcar_recogido', 'marcar_en_camino', 'marcar_entregado',
            'ver_historial', 'ver_documentos', 'actualizar_perfil'
        ];
        foreach ($permisos as $permiso) {
            try {
                Database::query(
                    "INSERT INTO permisos_repartidores (repartidor_id, permiso, permitido, otorgado_por, fecha_otorgado) VALUES (?, ?, 1, ?, NOW()) ON DUPLICATE KEY UPDATE permitido = 1, otorgado_por = VALUES(otorgado_por), fecha_actualizacion = NOW()",
                    [$repartidorId, $permiso, $adminId]
                );
            } catch (\Exception $e) {
            }
        }
    }

    private function revocarPermisos($repartidorId)
    {
        try {
            Database::query(
                "UPDATE permisos_repartidores SET permitido = 0, fecha_actualizacion = NOW() WHERE repartidor_id = ?",
                [$repartidorId]
            );
        } catch (\Exception $e) {
        }
    }

    private function restaurarPermisos($repartidorId)
    {
        try {
            Database::query(
                "UPDATE permisos_repartidores SET permitido = 1, fecha_actualizacion = NOW() WHERE repartidor_id = ?",
                [$repartidorId]
            );
        } catch (\Exception $e) {
        }
    }

    public function solicitudes()
    {
        $this->requireAdmin();

        $estado = $_GET['estado'] ?? 'pendiente';
        
        $solicitudes = Database::query("
            SELECT s.*, 
                   s.nombres as nombre, 
                   s.apellidos as apellido,
                   COALESCE(u.email, s.email) as email, 
                   COALESCE(u.cedula, s.cedula) as cedula,
                   u.telefono as telefono,
                   u.estado as usuario_estado
            FROM solicitudes_repartidores s 
            LEFT JOIN usuarios u ON s.usuario_id = u.id 
            WHERE s.estado = ? 
            ORDER BY s.fecha_solicitud DESC
        ", [$estado])->fetchAll();

        foreach ($solicitudes as &$s) {
            $docs = Database::query(
                "SELECT * FROM documentos WHERE repartidor_id = ? ORDER BY fecha_subida DESC",
                [$s['usuario_id']]
            )->fetchAll();
            $s['documentos'] = $docs;
        }

        $this->json(['success' => true, 'solicitudes' => $solicitudes]);
    }

    public function aprobar()
    {
        $adminId = $this->requireAdmin();

        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? 0;
        $observaciones = trim($data['observaciones'] ?? '');

        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID de solicitud requerido'], 400);
        }

        $solicitud = Database::query("SELECT * FROM solicitudes_repartidores WHERE id = ? AND estado = 'pendiente'", [$id])->fetch();
        if (!$solicitud) {
            $this->json(['success' => false, 'message' => 'Solicitud no encontrada o ya procesada'], 404);
        }

        $usuarioId = $solicitud['usuario_id'];

        Database::query("
            UPDATE solicitudes_repartidores 
            SET estado = 'aprobada', fecha_respuesta = NOW(), observaciones = ?, administrador_id = ? 
            WHERE id = ? AND estado = 'pendiente'
        ", [$observaciones, $adminId, $id]);

        Database::query("UPDATE usuarios SET estado = 'activo', fecha_aprobacion = NOW(), aprobado_por = ? WHERE id = ?", [$adminId, $usuarioId]);

        $this->asignarPermisos($usuarioId, $adminId);

        $this->historial($usuarioId, $id, $adminId, 'aprobacion', 'aprobada', null, $observaciones ?: 'Solicitud aprobada');

        $this->notificar(
            $usuarioId,
            'aprobacion_repartidor',
            '¡Solicitud Aprobada!',
            'Tu solicitud para ser repartidor ha sido aprobada. Ya puedes acceder a tu panel y comenzar a gestionar pedidos.',
            '/repartidor/dashboard',
            ['solicitud_id' => $id]
        );

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
        $adminId = $this->requireAdmin();

        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? 0;
        $observaciones = trim($data['observaciones'] ?? '');
        $motivo = trim($data['motivo'] ?? $observaciones);

        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID de solicitud requerido'], 400);
        }

        $solicitudData = Database::query("SELECT * FROM solicitudes_repartidores WHERE id = ? AND estado = 'pendiente'", [$id])->fetch();
        if (!$solicitudData) {
            $this->json(['success' => false, 'message' => 'Solicitud no encontrada o ya procesada'], 404);
        }

        $usuarioId = $solicitudData['usuario_id'];

        Database::query("
            UPDATE solicitudes_repartidores 
            SET estado = 'rechazada', fecha_respuesta = NOW(), observaciones = ?, motivo_rechazo = ?, administrador_id = ? 
            WHERE id = ? AND estado = 'pendiente'
        ", [$observaciones, $motivo, $adminId, $id]);

        Database::query("UPDATE usuarios SET estado = 'inactivo' WHERE id = ?", [$usuarioId]);

        $this->historial($usuarioId, $id, $adminId, 'rechazo', 'rechazada', $motivo, $observaciones);

        $this->notificar(
            $usuarioId,
            'rechazo_repartidor',
            'Solicitud Rechazada',
            'Tu solicitud para ser repartidor ha sido rechazada.' . ($motivo ? ' Motivo: ' . $motivo : ''),
            '/repartidor/dashboard',
            ['solicitud_id' => $id, 'motivo' => $motivo]
        );

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

    public function activos()
    {
        $this->requireAdmin();

        $drivers = Database::query("
            SELECT u.id, u.nombre, u.apellido, u.email, u.telefono, u.cedula,
                   u.tipo_documento, u.tipo_vehiculo, u.placa_vehiculo,
                   u.numero_licencia, u.categoria_licencia,
                   u.estado, u.total_entregas,
                   u.ganancias_totales, u.calificacion_promedio, u.fecha_registro,
                   u.en_linea, u.motivo_suspension, u.fecha_suspension,
                   (SELECT COUNT(*) FROM pedidos WHERE repartidor_id = u.id AND estado = 'en_camino') as pedidos_activos,
                   (SELECT COUNT(*) FROM historial_entregas WHERE repartidor_id = u.id AND DATE(fecha_entrega) = CURDATE()) as entregas_hoy
            FROM usuarios u
            WHERE u.rol = 'repartidor' AND u.estado IN ('activo', 'suspendido')
            ORDER BY u.fecha_registro DESC
        ")->fetchAll();

        foreach ($drivers as &$d) {
            $docs = Database::query(
                "SELECT tipo, archivo_url, estado, fecha_subida, observaciones FROM documentos WHERE repartidor_id = ?",
                [$d['id']]
            )->fetchAll();
            $d['documentos'] = $docs;
        }

        $this->json(['success' => true, 'drivers' => $drivers]);
    }

    public function suspender()
    {
        $adminId = $this->requireAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);
        $motivo = trim($data['motivo'] ?? '');

        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID requerido'], 400);
        }

        $user = Database::query("SELECT id, nombre, apellido FROM usuarios WHERE id = ? AND rol = 'repartidor' AND estado = 'activo'", [$id])->fetch();
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Repartidor no encontrado o ya suspendido'], 404);
        }

        Database::query("UPDATE usuarios SET estado = 'suspendido', motivo_suspension = ?, fecha_suspension = NOW() WHERE id = ? AND rol = 'repartidor'", [$motivo ?: null, $id]);

        $this->revocarPermisos($id);

        $this->historial($id, null, $adminId, 'suspension', 'suspendido', $motivo, $motivo ?: 'Cuenta suspendida por administrador');

        $this->notificar(
            $id,
            'suspension_repartidor',
            'Cuenta Suspendida',
            'Tu cuenta como repartidor ha sido suspendida.' . ($motivo ? ' Motivo: ' . $motivo : '') . ' Contacta al administrador para más información.',
            '/repartidor/dashboard',
            ['motivo' => $motivo]
        );

        $this->json(['success' => true, 'message' => 'Repartidor suspendido correctamente']);
    }

    public function activar()
    {
        $adminId = $this->requireAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);

        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID requerido'], 400);
        }

        $user = Database::query("SELECT id, nombre, apellido FROM usuarios WHERE id = ? AND rol = 'repartidor' AND estado = 'suspendido'", [$id])->fetch();
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Repartidor no encontrado o ya activo'], 404);
        }

        Database::query("UPDATE usuarios SET estado = 'activo', motivo_suspension = NULL, fecha_suspension = NULL WHERE id = ? AND rol = 'repartidor'", [$id]);

        $this->restaurarPermisos($id);

        $this->historial($id, null, $adminId, 'reactivacion', 'activo', null, 'Cuenta reactivada por administrador');

        $this->notificar(
            $id,
            'aprobacion_repartidor',
            'Cuenta Reactivada',
            'Tu cuenta como repartidor ha sido reactivada. Ya puedes acceder a tu panel y gestionar pedidos.',
            '/repartidor/dashboard',
            null
        );

        $this->json(['success' => true, 'message' => 'Repartidor activado correctamente']);
    }

    public function revisarDocumento()
    {
        $adminId = $this->requireAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        $documentoId = intval($data['documento_id'] ?? 0);
        $accion = $data['accion'] ?? '';
        $observaciones = trim($data['observaciones'] ?? '');

        if (!$documentoId || !in_array($accion, ['aprobar', 'rechazar'])) {
            $this->json(['success' => false, 'message' => 'Parámetros inválidos'], 400);
        }

        $doc = Database::query("SELECT * FROM documentos WHERE id = ?", [$documentoId])->fetch();
        if (!$doc) {
            $this->json(['success' => false, 'message' => 'Documento no encontrado'], 404);
        }

        $nuevoEstado = $accion === 'aprobar' ? 'aprobado' : 'rechazado';
        Database::query(
            "UPDATE documentos SET estado = ?, observaciones = ?, revisado_por = ?, fecha_revision = NOW() WHERE id = ?",
            [$nuevoEstado, $observaciones ?: null, $adminId, $documentoId]
        );

        $this->notificar(
            $doc['repartidor_id'],
            'documento_repartidor',
            'Documento ' . ($accion === 'aprobar' ? 'Aprobado' : 'Rechazado'),
            'Tu documento "' . str_replace('_', ' ', $doc['tipo']) . '" ha sido ' . ($accion === 'aprobar' ? 'aprobado' : 'rechazado') . '.' . ($observaciones ? ' Observaciones: ' . $observaciones : ''),
            '/repartidor/dashboard',
            ['documento_id' => $documentoId, 'accion' => $accion]
        );

        $this->json(['success' => true, 'message' => 'Documento ' . $nuevoEstado . ' correctamente']);
    }

    public function dashboardStats()
    {
        $this->requireAdmin();

        $totalPedidos = Database::query("SELECT COUNT(*) as c FROM pedidos")->fetch()['c'] ?? 0;
        $pendientes = Database::query("SELECT COUNT(*) as c FROM pedidos WHERE estado IN ('pendiente','confirmado','procesando','listo')")->fetch()['c'] ?? 0;
        $favoritos = Database::query("SELECT COUNT(*) as c FROM favoritos")->fetch()['c'] ?? 0;
        $ganancias = Database::query("SELECT COALESCE(SUM(total), 0) as g FROM pedidos WHERE estado = 'entregado'")->fetch()['g'] ?? 0;
        $totalUsuarios = Database::query("SELECT COUNT(*) as c FROM usuarios")->fetch()['c'] ?? 0;
        $solicitudesPendientes = Database::query("SELECT COUNT(*) as c FROM solicitudes_repartidores WHERE estado = 'pendiente'")->fetch()['c'] ?? 0;
        $repartidoresActivos = Database::query("SELECT COUNT(*) as c FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo'")->fetch()['c'] ?? 0;
        $totalProductos = Database::query("SELECT COUNT(*) as c FROM productos")->fetch()['c'] ?? 0;
        $stockBajo = Database::query("SELECT COUNT(*) as c FROM productos WHERE stock_total > 0 AND stock_total <= stock_minimo")->fetch()['c'] ?? 0;
        $totalVariantes = Database::query("SELECT COALESCE(SUM(stock), 0) as s FROM variantes_producto")->fetch()['s'] ?? 0;

        $ventasMes = Database::query("SELECT COALESCE(SUM(total), 0) as g FROM pedidos WHERE estado = 'entregado' AND MONTH(fecha_pedido) = MONTH(NOW()) AND YEAR(fecha_pedido) = YEAR(NOW())")->fetch()['g'] ?? 0;
        $pedidosMes = Database::query("SELECT COUNT(*) as c FROM pedidos WHERE MONTH(fecha_pedido) = MONTH(NOW()) AND YEAR(fecha_pedido) = YEAR(NOW())")->fetch()['c'] ?? 0;
        $usuariosMes = Database::query("SELECT COUNT(*) as c FROM usuarios WHERE MONTH(fecha_registro) = MONTH(NOW()) AND YEAR(fecha_registro) = YEAR(NOW())")->fetch()['c'] ?? 0;

        $ventasMesAnterior = Database::query("SELECT COALESCE(SUM(total), 0) as g FROM pedidos WHERE estado = 'entregado' AND MONTH(fecha_pedido) = MONTH(NOW() - INTERVAL 1 MONTH) AND YEAR(fecha_pedido) = YEAR(NOW() - INTERVAL 1 MONTH)")->fetch()['g'] ?? 0;

        $ventasMensuales = [];
        $meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        for ($m = 1; $m <= 12; $m++) {
            $v = Database::query("SELECT COALESCE(SUM(total), 0) as g FROM pedidos WHERE estado = 'entregado' AND MONTH(fecha_pedido) = ? AND YEAR(fecha_pedido) = YEAR(NOW())", [$m])->fetch()['g'] ?? 0;
            $ventasMensuales[] = ['mes' => $meses[$m-1], 'total' => (float)$v];
        }

        $topProductos = Database::query("
            SELECT p.nombre, COALESCE(SUM(dp.cantidad), 0) as vendidos
            FROM productos p
            LEFT JOIN detalles_pedido dp ON p.id = dp.producto_id
            LEFT JOIN pedidos ped ON dp.pedido_id = ped.id AND ped.estado = 'entregado'
            GROUP BY p.id, p.nombre
            HAVING vendidos > 0
            ORDER BY vendidos DESC
            LIMIT 5
        ")->fetchAll();

        $pedidosPorEstado = Database::query("
            SELECT estado, COUNT(*) as total FROM pedidos GROUP BY estado
        ")->fetchAll();

        $porcentajeVentas = $ventasMesAnterior > 0 ? round((($ventasMes - $ventasMesAnterior) / $ventasMesAnterior) * 100) : 0;

        $this->json([
            'success' => true,
            'totalPedidos' => (int)$totalPedidos,
            'pendientes' => (int)$pendientes,
            'favoritos' => (int)$favoritos,
            'ganancias' => (float)$ganancias,
            'totalUsuarios' => (int)$totalUsuarios,
            'solicitudesPendientes' => (int)$solicitudesPendientes,
            'repartidoresActivos' => (int)$repartidoresActivos,
            'totalProductos' => (int)$totalProductos,
            'stockBajo' => (int)$stockBajo,
            'totalVariantes' => (int)$totalVariantes,
            'ventasMes' => (float)$ventasMes,
            'pedidosMes' => (int)$pedidosMes,
            'usuariosMes' => (int)$usuariosMes,
            'porcentajeVentas' => (int)$porcentajeVentas,
            'ventasMensuales' => $ventasMensuales,
            'topProductos' => $topProductos,
            'pedidosPorEstado' => $pedidosPorEstado
        ]);
    }
}
