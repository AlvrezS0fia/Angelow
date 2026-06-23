<?php
namespace App\Controllers;

use App\Core\Controller;

class FacturaController extends Controller
{
    public function index()
    {
        if (!isset($_SESSION['user'])) {
            $this->redirect('/auth/login');
            return;
        }
        $user = $_SESSION['user'];
        $this->view('paginas.factura', ['user' => $user]);
    }
}