<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class RepartidorController extends Controller
{
    public function index()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'repartidor') {
            $this->redirect('/repartidor/login');
            return;
        }

        $user = $_SESSION['user'];
        $estado = $user['estado'] ?? 'activo';

        if ($estado !== 'activo') {
            $freshUser = Database::query(
                "SELECT * FROM usuarios WHERE id = ?",
                [$user['id']]
            )->fetch();

            if ($freshUser) {
                $user['estado'] = $freshUser['estado'];
                $user['motivo_suspension'] = $freshUser['motivo_suspension'] ?? null;
                $user['fecha_suspension'] = $freshUser['fecha_suspension'] ?? null;
                $estado = $freshUser['estado'];
            }

            $solicitud = Database::query(
                "SELECT * FROM solicitudes_repartidores WHERE usuario_id = ? ORDER BY fecha_solicitud DESC LIMIT 1",
                [$user['id']]
            )->fetch();

            $notificaciones = Database::query(
                "SELECT * FROM notificaciones WHERE usuario_id = ? ORDER BY fecha_envio DESC LIMIT 10",
                [$user['id']]
            )->fetchAll();

            $this->view('repartidor.dashboard', [
                'user' => $user,
                'solicitud' => $solicitud,
                'notificaciones' => $notificaciones
            ]);
            return;
        }

        $this->view('repartidor.dashboard', [
            'user' => $user,
            'solicitud' => null,
            'notificaciones' => []
        ]);
    }

    public function perfil()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'repartidor') {
            $this->redirect('/repartidor/login');
            return;
        }
        $this->view('repartidor.perfil', ['user' => $_SESSION['user']]);
    }

    public function registro()
    {
        $this->view('repartidor.registro_repartidor');
    }
}
