<?php
/**
 * ============================================================
 * ARCHIVO: StockController.php — MÓDULO: API de gestión de stock (admin)
 * ============================================================
 * QUÉ HACE: CRUD de inventario: listar stock de todos los productos,
 *   actualizar stock directamente, ajustar (add/subtract/set) y eliminar
 *   producto. Solo accesible para administradores.
 * MODELO(S) QUE USA: Ninguno — usa Database::getInstance() directamente.
 * ENDPOINTS/RUTAS: GET /api/stock, PUT /api/stock/{id},
 *   POST /api/stock/{id}/ajustar, DELETE /api/stock
 * QUIÉN LO CONSUME: panel.js (sección de inventario del administrador)
 */
namespace App\Controllers\Api;

use App\Core\Controller;

/**
 * Controlador de gestión de stock para el panel administrativo.
 * Valida que cada acción requiera sesión de administrador.
 */
class StockController extends Controller
{
    /**
     * GET /api/stock — Lista todos los productos con su stock actual,
     *   stock mínimo, categoría, imagen y total vendidos.
     */
    public function index()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        $db = \App\Core\Database::getInstance()->getConnection();
        
        $sql = "SELECT p.id, p.nombre, p.precio, p.stock_total, p.stock_minimo, 
                       p.imagenes, c.nombre as categoria_nombre, sc.nombre as subcategoria_nombre,
                       p.total_vendidos
                FROM productos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                LEFT JOIN categorias sc ON p.subcategoria_id = sc.id
                ORDER BY p.fecha_creacion DESC";
        
        $stmt = $db->query($sql);
        $productos = $stmt->fetchAll();

        $resultado = [];
        foreach ($productos as $p) {
            $imagenes = json_decode($p['imagenes'] ?? '[]', true) ?: [];
            $resultado[] = [
                'id' => (int) $p['id'],
                'nombre' => $p['nombre'],
                'categoria' => $p['categoria_nombre'] ?? 'Sin categoría',
                'subcategoria' => $p['subcategoria_nombre'] ?? '',
                'precio' => (float) $p['precio'],
                'stock' => (int) $p['stock_total'],
                'stock_minimo' => (int) $p['stock_minimo'],
                'imagen' => !empty($imagenes) ? $imagenes[0] : '',
                'vendidos' => (int) $p['total_vendidos']
            ];
        }

        echo json_encode($resultado);
    }

    /**
     * PUT /api/stock/{id} — Actualiza el stock_total de un producto.
     * No permite stock negativo.
     */
    public function update($id)
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['stock'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Stock no proporcionado']);
            return;
        }

        $nuevoStock = (int) $data['stock'];
        if ($nuevoStock < 0) {
            http_response_code(400);
            echo json_encode(['error' => 'El stock no puede ser negativo']);
            return;
        }

        $db = \App\Core\Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("UPDATE productos SET stock_total = :stock WHERE id = :id");
        $result = $stmt->execute(['stock' => $nuevoStock, 'id' => $id]);

        if ($result) {
            echo json_encode(['success' => true, 'nuevo_stock' => $nuevoStock]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar stock']);
        }
    }

    /**
     * POST /api/stock/{id}/ajustar — Ajusta stock con operación aritmética.
     * Tipos: 'add' (sumar), 'subtract' (restar, mínimo 0), 'set' (establecer).
     */
    public function ajustar($id)
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['cantidad']) || !isset($data['tipo'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos incompletos']);
            return;
        }

        $cantidad = (int) $data['cantidad'];
        $tipo = $data['tipo']; // 'add', 'subtract', 'set'

        $db = \App\Core\Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT stock_total FROM productos WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $producto = $stmt->fetch();

        if (!$producto) {
            http_response_code(404);
            echo json_encode(['error' => 'Producto no encontrado']);
            return;
        }

        $stockActual = (int) $producto['stock_total'];
        $nuevoStock = $stockActual;

        if ($tipo === 'add') {
            $nuevoStock = $stockActual + $cantidad;
        } elseif ($tipo === 'subtract') {
            $nuevoStock = max(0, $stockActual - $cantidad);
        } elseif ($tipo === 'set') {
            $nuevoStock = $cantidad;
        }

        $stmt = $db->prepare("UPDATE productos SET stock_total = :stock WHERE id = :id");
        $result = $stmt->execute(['stock' => $nuevoStock, 'id' => $id]);

        if ($result) {
            echo json_encode(['success' => true, 'nuevo_stock' => $nuevoStock, 'stock_anterior' => $stockActual]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar stock']);
        }
    }

    /**
     * DELETE /api/stock — Elimina un producto por ID (recibido en body JSON).
     * No elimina variantes ni imágenes (a diferencia de products.php legacy).
     */
    public function destroy()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? 0;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID del producto requerido']);
            return;
        }

        $db = \App\Core\Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT id, nombre FROM productos WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $producto = $stmt->fetch();

        if (!$producto) {
            http_response_code(404);
            echo json_encode(['error' => 'Producto no encontrado']);
            return;
        }

        $stmt = $db->prepare("DELETE FROM productos WHERE id = :id");
        $result = $stmt->execute(['id' => $id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Producto "' . $producto['nombre'] . '" eliminado correctamente']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar el producto']);
        }
    }
}
