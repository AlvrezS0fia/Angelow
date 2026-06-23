<?php
namespace App\Controllers;

use App\Core\Controller;

class SeguimientoController extends Controller
{
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