<?php
/**
 * ============================================================
 * ARCHIVO: HomeController.php — MÓDULO: Controlador de la página de inicio
 * ============================================================
 * QUÉ HACE: Muestra la página de bienvenida cuando el usuario viene desde la
 *   pantalla de carga (parámetro `from`). Si no viene de allí, redirige al cargador.
 * MODELO(S) QUE USA: ninguno
 * ENDPOINTS/RUTAS: GET / (requiere ?from=... para no redirigir)
 * QUIÉN LO CONSUME: La pantalla de carga (CargadorController) que enlaza a la home.
 */
namespace App\Controllers;

use App\Core\Controller;

/**
 * Controlador de la página de inicio. Extiende la clase base Controller.
 * Patrón MVC: fuerza el flujo visual cargador → bienvenida.
 */
class HomeController extends Controller {
    /** Muestra la bienvenida solo si se llega desde el cargador; si no, redirige a /cargador. */
    public function index() {
        // Control de flujo: sin ?from=, el home redirige a la pantalla de carga.
        if (!isset($_GET['from'])) {
            header('Location: ' . APP_URL . '/cargador');
            exit();
        }

        // Con sesión o sin ella, la vista recibe el usuario actual (puede ser null).
        $user = $_SESSION['user'] ?? null;
        $this->view('home.bienvenida', ['user' => $user]);
    }
}