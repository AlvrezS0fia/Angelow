<?php
/**
 * ============================================================
 * ARCHIVO: InventarioController.php — MÓDULO: Panel de inventario
 * ============================================================
 * QUÉ HACE: Renderiza la vista HTML de gestión de inventario.
 *   Solo verifica que el usuario sea administrador; no retorna JSON.
 * MODELO(S) QUE USA: Ninguno.
 * ENDPOINTS/RUTAS: GET /admin/inventario (vista HTML)
 * QUIÉN LO CONSUME: admin/inventario.php (vista renderizada por el enrutador)
 */
namespace App\Controllers\Admin;

use App\Core\Controller;

/**
 * Controlador del módulo de inventario en el panel admin.
 */
class InventarioController extends Controller
{
    /**
     * Renderiza la vista de inventario. Redirige al login si no es admin.
     */
    public function index()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->redirect('/auth/login');
            return;
        }
        $this->view('admin.inventario');
    }
}