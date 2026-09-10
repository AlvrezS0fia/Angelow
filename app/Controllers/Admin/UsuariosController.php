<?php
/**
 * ============================================================
 * ARCHIVO: UsuariosController.php — MÓDULO: Panel admin de usuarios
 * ============================================================
 * QUÉ HACE: Renderiza la vista HTML de gestión de usuarios.
 *   Actualmente retorna un array vacío de usuarios (pendiente de integrar modelo).
 * MODELO(S) QUE USA: Ninguno (el array $usuarios está vacío).
 * ENDPOINTS/RUTAS: GET /admin/usuarios (vista HTML)
 * QUIÉN LO CONSUME: admin/usuarios.php (vista renderizada por el enrutador)
 */
namespace App\Controllers\Admin;

use App\Core\Controller;

/**
 * Controlador para la vista admin de usuarios.
 */
class UsuariosController extends Controller
{
    /**
     * Renderiza la vista de usuarios. Redirige al login si no es admin.
     */
    public function index()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->redirect('/auth/login');
            return;
        }
        // Obtener lista de usuarios
        $usuarios = []; // Modelo de usuarios
        $this->view('admin.usuarios', ['usuarios' => $usuarios]);
    }
}