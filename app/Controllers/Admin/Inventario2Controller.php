<?php
/**
 * ============================================================
 * ARCHIVO: Inventario2Controller.php — MÓDULO: Inventario2 (microservicio)
 * ============================================================
 * QUÉ HACE: Renderiza la vista "Inventario2", una réplica de la gestión de
 *   inventario pero que lee/escribe productos desde el MICROSERVICIO de
 *   productos (puerto 8082) en vez de consultar MySQL directamente.
 *   El CRUD en tiempo real lo hace la vista (admin/inventario2.php) mediante
 *   fetch() a PRODUCTOS_API_URL; AQUÍ solo se pre-carga el listado inicial
 *   para que la página no arranque vacía.
 * MODELO(S) QUE USA: App\Libraries\ProductoMicroservice (cliente PHP del
 *   microservicio). NUNCA toca la tabla `productos` por PDO.
 * ENDPOINTS/RUTAS: GET /admin/inventario2 (vista HTML)
 * QUIÉN LO CONSUME: admin/inventario2.php (vista renderizada por el enrutador)
 * ============================================================
 */
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\ProductoMicroservice;

/**
 * Controlador del módulo Inventario2 en el panel admin.
 */
class Inventario2Controller extends Controller
{
    /**
     * Pre-carga los primeros productos desde el microservicio y renderiza
     * la vista. Redirige al login si no es administrador.
     */
    public function index()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->redirect('/auth/login');
            return;
        }

        $cliente = new ProductoMicroservice();

        // Se piden las primeras 50 filas para que la tabla inicial sea útil.
        // Si el microservicio NO está levantado, se le avisa al usuario
        // en la página con un mensaje y la vista intentará reconectar cada
        // pocos segundos.
        $respuesta = $cliente->obtenerProductosPaginados(0, 50);

        if (isset($respuesta['error'])) {
            // El microservicio no respondió: la vista mostrará el aviso.
            $productosIniciales = [];
            $errorMicroservicio = $respuesta['error'];
        } elseif (isset($respuesta['content'])) {
            // Respuesta paginada de Spring: los productos vienen en "content".
            $productosIniciales = $respuesta['content'];
            $errorMicroservicio = '';
        } elseif (is_array($respuesta)) {
            // Respuesta plana (array de productos sin paginar).
            $productosIniciales = $respuesta;
            $errorMicroservicio = '';
        } else {
            $productosIniciales = [];
            $errorMicroservicio = 'Respuesta inesperada del microservicio';
        }

        $this->view('admin.inventario2', [
            'productosIniciales'  => $productosIniciales,
            'errorMicroservicio'  => $errorMicroservicio,
            'productosApiUrl'     => constant('PRODUCTOS_API_URL'),
        ]);
    }
}