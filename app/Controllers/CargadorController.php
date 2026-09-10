<?php
/**
 * ============================================================
 * ARCHIVO: CargadorController.php — MÓDULO: Controlador de pantalla de carga
 * ============================================================
 * QUÉ HACE: Muestra la pantalla de presentación (loader/splash) del sitio,
 *   usada como punto de entrada visual de la tienda.
 * MODELO(S) QUE USA: ninguno
 * ENDPOINTS/RUTAS: GET /cargador
 * QUIÉN LO CONSUME: La home redirige aquí cuando se entra sin el parámetro `from`
 *   (ver HomeController::index).
 */
namespace App\Controllers;

use App\Core\Controller;

/**
 * Controlador de la pantalla de carga inicial. Extiende la clase base Controller
 * y trabaja bajo el patrón MVC, delegando la salida a la vista 'cargador.index'.
 */
class CargadorController extends Controller {
    /** Muestra la vista del cargador/splash de la tienda. */
    public function index() {
        $this->view('cargador.index');
    }
}
