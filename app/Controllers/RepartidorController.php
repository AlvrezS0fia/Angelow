<?php
/**
 * ============================================================
 * ARCHIVO: RepartidorController.php — MÓDULO: Controlador del repartidor
 * ============================================================
 * QUÉ HACE: Sirve las vistas internas del repartidor (dashboard y perfil)
 *   restringidas por sesión/rol, y redirige el registro hacia el microservicio
 *   de repartidores (MICROSERVICE_URL).
 * MODELO(S) QUE USA: Database (App\Core) solo para consultas directas
 * ENDPOINTS/RUTAS: GET /repartidor, GET /repartidor/perfil, GET /repartidor/registro
 * QUIÉN LO CONSUME: La app web del repartidor (dashboard, perfil y enlace de registro).
 */
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

/**
 * Controlador del área del repartidor. Extiende la clase base Controller.
 * Patrón MVC: valida que el usuario de sesión sea repartidor antes de mostrar vistas.
 */
class RepartidorController extends Controller
{
    /**
     * Dashboard del repartidor. Si su cuenta está en estado distinto de 'activo'
     * (pendiente/suspendida), recarga datos frescos de la BD, su última solicitud y
     * sus notificaciones para mostrar el estado real de la cuenta.
     */
    public function index()
    {
        // Bloqueo por sesión/rol: solo repartidores logueados.
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'repartidor') {
            $this->redirect('/repartidor/login');
            return;
        }

        $user = $_SESSION['user'];
        $estado = $user['estado'] ?? 'activo';

        // Cuenta no activa: se consulta la BD para no depender de datos viejos de sesión.
        if ($estado !== 'activo') {
            $freshUser = Database::query(
                "SELECT * FROM usuarios WHERE id = ?",
                [$user['id']]
            )->fetch();

            // Actualiza estado, motivo y fecha de suspensión desde la BD.
            if ($freshUser) {
                $user['estado'] = $freshUser['estado'];
                $user['motivo_suspension'] = $freshUser['motivo_suspension'] ?? null;
                $user['fecha_suspension'] = $freshUser['fecha_suspension'] ?? null;
                $estado = $freshUser['estado'];
            }

            // Última solicitud de reparto (para el caso pendiente).
            $solicitud = Database::query(
                "SELECT * FROM solicitudes_repartidores WHERE usuario_id = ? ORDER BY fecha_solicitud DESC LIMIT 1",
                [$user['id']]
            )->fetch();

            // Últimas 10 notificaciones del repartidor.
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

        // Cuenta activa: dashboard sin solicitud ni notificaciones precargadas.
        $this->view('repartidor.dashboard', [
            'user' => $user,
            'solicitud' => null,
            'notificaciones' => []
        ]);
    }

    /** Muestra la vista de perfil del repartidor en sesión. */
    public function perfil()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'repartidor') {
            $this->redirect('/repartidor/login');
            return;
        }
        $this->view('repartidor.perfil', ['user' => $_SESSION['user']]);
    }

    /** Redirige el registro del repartidor hacia el microservicio externo. */
    public function registro()
    {
        header('Location: ' . MICROSERVICE_URL);
        exit;
    }
}
