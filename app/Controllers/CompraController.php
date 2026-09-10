<?php
/**
 * ============================================================
 * ARCHIVO: CompraController.php — MÓDULO: Controlador de compra/checkout
 * ============================================================
 * QUÉ HACE: Muestra la página de compra (solo para clientes logueados) y
 *   procesa la creación del pedido cuando el checkout envía la compra.
 * MODELO(S) QUE USA: PedidoModel
 * ENDPOINTS/RUTAS: GET /compra, POST /procesar-compra
 * QUIÉN LO CONSUME: La vista 'paginas.compra' y el JS del checkout (carrito).
 */
namespace App\Controllers;

use App\Core\Controller;
use App\Models\PedidoModel;

/**
 * Controlador del flujo de compra. Extiende la clase base Controller.
 * Patrón MVC: restringe el acceso por sesión/rol y delega la persistencia
 * del pedido en PedidoModel (que ejecuta la transacción en MySQL).
 */
class CompraController extends Controller
{
    /**
     * Muestra la vista de compra al cliente autenticado.
     * Valida sesión y redirige por rol (administrador → /admin, repartidor → su dashboard).
     */
    public function index()
    {
        // Si no hay sesión, se obliga a iniciar sesión antes de comprar.
        if (!isset($_SESSION['user'])) {
            $this->redirect('/auth/login');
            return;
        }

        // El acceso a la compra queda restringido por rol: solo clientes.
        $rol = $_SESSION['user']['rol'] ?? 'cliente';
        if ($rol === 'administrador') {
            $this->redirect('/admin');
            return;
        }
        if ($rol === 'repartidor') {
            $this->redirect('/repartidor/dashboard');
            return;
        }

        $user = $_SESSION['user'];
        $this->view('paginas.compra', ['user' => $user]);
    }

    // POST /procesar-compra → Crea el pedido.
    //   ↓ Datos recibidos desde: JS del checkout (JSON: ítems, dirección, método pago/envío).
    //   ↓ Validación 1 (sesión/rol): invitado no pasa → 403.
    //   ↓ Validación 2 (formato): body debe ser un JSON válido → 400.
    //   ↓ Procesamiento: PedidoModel::crearPedido() (transacción en MySQL):
    //       - INSERT en `pedidos` (trigger genera ORD-YYYY-NNNN).
    //       - INSERT en `detalles_pedido` (trigger descuenta stock y notifica).
    //   ↓ Retorna: JSON {success, pedido_id, numero_pedido} → el frontend
    //     muestra confirmación y limpia el carrito.
    // ROLES PERMITIDOS: cliente autenticado.
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
            $this->json(['error' => 'Error al guardar el pedido. Intenta de nuevo.'], 500);
        }
    }
}