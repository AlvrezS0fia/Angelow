<?php
namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller {
    public function index() {
        $user = $_SESSION['user'] ?? null;
        $this->view('home.bienvenida', ['user' => $user]);
    }
}