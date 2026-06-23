<?php
namespace App\Controllers;

use App\Core\Controller;

class PerfilController extends Controller
{
    public function index()
    {
        // Verificar sesión
        if (!isset($_SESSION['user'])) {
            $this->redirect('/auth/login');
            return;
        }

        // Si el usuario es administrador, redirigir al panel de admin
        if (($_SESSION['user']['rol'] ?? '') === 'administrador') {
            $this->redirect('/admin');
            return;
        }

        // Si es cliente normal, mostrar su perfil
        $this->view('paginas.perfil', ['user' => $_SESSION['user']]);
    }
}