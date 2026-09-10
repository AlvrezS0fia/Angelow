<?php
/**
 * ============================================================
 * ARCHIVO: RepartidorController.php — MÓDULO: Panel admin de repartidores
 * ============================================================
 * QUÉ HACE: Renderiza la vista HTML del panel de gestión de repartidores.
 *   Usa Auth::isAdmin() en lugar del guardián manual de sesión.
 * MODELO(S) QUE USA: Ninguno.
 * ENDPOINTS/RUTAS: GET /admin/repartidores (vista HTML)
 * QUIÉN LO CONSUME: admin/repartidor.php (vista renderizada por el enrutador)
 */
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;

/**
 * Controlador para la vista admin de repartidores.
 */
class RepartidorController extends Controller
{
    /**
     * Renderiza la vista de repartidores. Redirige al login si no es admin.
     */
    public function index()
    {
        if (!Auth::isAdmin()) {
            $this->redirect('/auth/login');
            return;
        }

        $this->view('admin.repartidor');
    }
}
