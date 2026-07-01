<?php
namespace App\Controllers\Api;

use App\Core\Controller;

class StockController extends Controller
{
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
}
