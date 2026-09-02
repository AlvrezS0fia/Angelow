<?php
namespace App\Controllers;

use App\Core\Controller;

class CargadorController extends Controller {
    public function index() {
        $this->view('cargador.index');
    }
}
