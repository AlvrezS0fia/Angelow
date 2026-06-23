<?php
namespace App\Controllers;

use App\Core\Controller;

class CompraController extends Controller
{
    public function index()
    {
        // Verificar si el usuario está logueado
        if (!isset($_SESSION['user'])) {
            $this->redirect('/auth/login');
            return;
        }

        // Pasar datos del usuario a la vista
        $user = $_SESSION['user'];
        $this->view('paginas.compra', ['user' => $user]);
    }

    public function procesar()
    {
        // Aquí procesarás el pedido (recibir JSON del carrito, etc.)
        // Por ahora solo devolvemos un mensaje de éxito
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar, guardar pedido en BD, etc.
        
        $this->json(['success' => true, 'message' => 'Pedido procesado', 'pedido_id' => 123]);
    }
}