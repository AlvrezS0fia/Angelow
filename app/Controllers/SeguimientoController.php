<?php
/**
 * ============================================================
 * ARCHIVO: SeguimientoController.php — MÓDULO: Controlador de seguimiento de pedidos
 * ============================================================
 * QUÉ HACE: Muestra la página de seguimiento de pedidos del cliente.
 *   El control de acceso por sesión está comentado (queda abierto al público).
 * MODELO(S) QUE USA: ninguno
 * ENDPOINTS/RUTAS: GET /seguimiento
 * QUIÉN LO CONSUME: Página pública de seguimiento (vista paginas.seguimiento).
 */
namespace App\Controllers;

use App\Core\Controller;

/**
 * Controlador de la página de seguimiento. Extiende la clase base Controller.
 * Patrón MVC: renderiza una vista con el tracking del pedido.
 */
class SeguimientoController extends Controller
{
    /** Muestra la vista de seguimiento de pedidos. */
    public function index()
    {
        // Opcional: verificar si el usuario está logueado
        // if (!isset($_SESSION['user'])) {
        //     $this->redirect('/auth/login');
        //     return;
        // }
        $this->view('paginas.seguimiento');
    }
}