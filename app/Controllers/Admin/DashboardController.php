<?php
/**
 * ============================================================
 * ARCHIVO: DashboardController.php — MÓDULO: Panel de administración
 * ============================================================
 * QUÉ HACE: Renderiza la vista principal del dashboard admin con
 *   estadísticas: total pedidos, pendientes, favoritos, ganancias y usuarios.
 * MODELO(S) QUE USA: Ninguno — usa Database::getInstance() directamente.
 * ENDPOINTS/RUTAS: GET /admin (vista HTML, no JSON)
 * QUIÉN LO CONSUME: admin/dashboard.php (vista renderizada por el enrutador)
 */
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;

/**
 * Controlador del dashboard administrativo.
 * Recopila métricas clave de la tienda y renderiza la vista del panel.
 */
class DashboardController extends Controller
{
    /**
     * Muestra el dashboard admin con estadísticas generales de la tienda.
     * Redirige al login si no hay sesión de administrador.
     */
    public function index()
    {
        // Guardián de sesión: solo administradores acceden al panel.
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->redirect('/auth/login');
            return;
        }

        $db = Database::getInstance()->getConnection();

        $totalPedidos = (int) $db->query("SELECT COUNT(*) as c FROM pedidos")->fetch()['c'];
        $pendientes = (int) $db->query("SELECT COUNT(*) as c FROM pedidos WHERE estado IN ('pendiente','confirmado','procesando','listo')")->fetch()['c'];
        $favoritos = (int) $db->query("SELECT COUNT(*) as c FROM favoritos")->fetch()['c'];
        $ganancias = (float) $db->query("SELECT COALESCE(SUM(total), 0) as g FROM pedidos WHERE estado = 'entregado'")->fetch()['g'];

        $totalUsuarios = (int) $db->query("SELECT COUNT(*) as c FROM usuarios")->fetch()['c'];

        $data = [
            'totalPedidos' => $totalPedidos,
            'pendientes' => $pendientes,
            'favoritos' => $favoritos,
            'ganancias' => $ganancias,
            'totalUsuarios' => $totalUsuarios,
            'nombreAdmin' => $_SESSION['user']['nombre'] ?? 'Administrador'
        ];

        $this->view('admin.panel', $data);
    }
}