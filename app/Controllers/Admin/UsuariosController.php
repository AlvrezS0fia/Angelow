<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class UsuariosController extends Controller
{
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