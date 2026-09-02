<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;

class PerfilController extends Controller
{
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