<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\PedidoModel;

class CompraController extends Controller
{
    public function index()
    {
        if (!isset($_SESSION['user'])) {
            $this->redirect('/auth/login');
            return;
        }

        $user = $_SESSION['user'];
        $this->view('paginas.compra', ['user' => $user]);
    }

    public function procesar()
    {
        if (!isset($_SESSION['user'])) {
            error_log("PROCESAR-COMPRA: No autorizado - sin sesion");
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }

        $rawInput = file_get_contents('php://input');
        error_log("PROCESAR-COMPRA: Input recibido - " . substr($rawInput, 0, 200));
        
        $data = json_decode($rawInput, true);
        error_log("PROCESAR-COMPRA: Data decodificada - " . (is_array($data) ? 'OK' : 'FALLO'));
        
        if (!$data || !is_array($data)) {
            error_log("PROCESAR-COMPRA: Datos inválidos");
            $this->json(['error' => 'Datos inválidos'], 400);
            return;
        }

        $pedidoModel = new PedidoModel();
        
        try {
            $resultado = $pedidoModel->crearPedido($data);
            error_log("PROCESAR-COMPRA: EXITO - ID={$resultado['id']} Numero={$resultado['numero_pedido']}");
            $this->json([
                'success' => true,
                'message' => 'Pedido guardado correctamente',
                'pedido_id' => $resultado['id'],
                'numero_pedido' => $resultado['numero_pedido']
            ]);
        } catch (\Exception $e) {
            error_log("PROCESAR-COMPRA: ERROR - " . $e->getMessage());
            $this->json(['error' => 'Error al guardar el pedido: ' . $e->getMessage()], 500);
        }
    }
}