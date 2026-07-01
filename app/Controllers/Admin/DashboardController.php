<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;

class DashboardController extends Controller
{
    public function index()
    {
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