<?php
/**
 * ============================================================
 * ARCHIVO: PerfilController.php — MÓDULO: Controlador de perfil de cliente
 * ============================================================
 * QUÉ HACE: Muestra la vista de perfil solo al cliente logueado. Según el rol
 *   redirige: administrador → panel admin, repartidor → su dashboard, cliente → perfil.
 * MODELO(S) QUE USA: ninguno directamente (usa el helper Auth)
 * ENDPOINTS/RUTAS: GET /perfil
 * QUIÉN LO CONSUME: Enlace "Mi perfil" del menú del cliente autenticado.
 */
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;

/**
 * Controlador del perfil del cliente. Extiende la clase base Controller.
 * Trabaja con el helper Auth (App\Core\Auth) para verificar sesión y roles.
 */
class PerfilController extends Controller
{
    /** Vista del perfil: verifica sesión y deriva por rol. */
    public function index()
    {
        // Verificar sesión
        if (!Auth::check()) {
            $this->redirect('/auth/login');
            return;
        }

        // Si el usuario es administrador, redirigir al panel de admin
        if (Auth::isAdmin()) {
            $this->redirect('/admin');
            return;
        }

        // Si el usuario es repartidor, redirigir al dashboard del repartidor
        if (Auth::isRepartidor()) {
            $this->redirect('/repartidor/dashboard');
            return;
        }

        // Si es cliente normal, mostrar su perfil
        $this->view('paginas.perfil', ['user' => Auth::user()]);
    }
}