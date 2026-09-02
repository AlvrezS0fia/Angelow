<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\ProductoModel;

class ProductsController extends Controller {
    private $productoModel;

    public function __construct() {
        $this->productoModel = new ProductoModel();
    }

    private function checkAdmin() {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
    }

    public function index() {
        $this->checkAdmin();
        $productos = $this->productoModel->getAll();

        $resultado = [];
        foreach ($productos as $p) {
            $imagenes = json_decode($p['imagenes'] ?? '[]', true) ?: [];
            $tallas = json_decode($p['tallas_disponibles'] ?? '[]', true) ?: [];
            $colores = json_decode($p['colores_disponibles'] ?? '[]', true) ?: [];
            $caracteristicas = json_decode($p['caracteristicas'] ?? '[]', true) ?: [];

            $resultado[] = [
                'id' => (int) $p['id'],
                'nombre' => $p['nombre'],
                'slug' => $p['slug'],
                'descripcion' => $p['descripcion'] ?? '',
                'descripcion_corta' => $p['descripcion_corta'] ?? '',
                'categoria' => $p['categoria_nombre'] ?? 'Sin categoría',
                'categoria_id' => (int) $p['categoria_id'],
                'subcategoria' => $p['subcategoria_nombre'] ?? '',
                'subcategoria_id' => $p['subcategoria_id'] ? (int) $p['subcategoria_id'] : null,
                'precio' => (float) $p['precio'],
                'precio_original' => $p['precio_original'] ? (float) $p['precio_original'] : null,
                'descuento_porcentaje' => (float) ($p['descuento_porcentaje'] ?? 0),
                'sku' => $p['sku'] ?? '',
                'stock' => (int) $p['stock_total'],
                'stock_minimo' => (int) $p['stock_minimo'],
                'tallas' => $tallas,
                'colores' => $colores,
                'imagenes' => $imagenes,
                'imagen' => !empty($imagenes) ? $imagenes[0] : '',
                'caracteristicas' => $caracteristicas,
                'material' => $p['material'] ?? '',
                'edad_recomendada' => $p['edad_recomendada'] ?? '',
                'vendidos' => (int) $p['total_vendidos'],
                'destacado' => (bool) $p['destacado'],
                'nuevo' => (bool) $p['nuevo'],
                'oferta' => (bool) $p['oferta'],
                'popular' => (bool) $p['popular'],
                'visible' => (bool) $p['visible'],
            ];
        }

        echo json_encode($resultado);
    }

    public function show($id) {
        $this->checkAdmin();
        $producto = $this->productoModel->getById($id);

        if (!$producto) {
            http_response_code(404);
            echo json_encode(['error' => 'Producto no encontrado']);
            return;
        }

        $imagenes = json_decode($producto['imagenes'] ?? '[]', true) ?: [];
        $tallas = json_decode($producto['tallas_disponibles'] ?? '[]', true) ?: [];
        $colores = json_decode($producto['colores_disponibles'] ?? '[]', true) ?: [];
        $caracteristicas = json_decode($producto['caracteristicas'] ?? '[]', true) ?: [];

        echo json_encode([
            'id' => (int) $producto['id'],
            'nombre' => $producto['nombre'],
            'slug' => $producto['slug'],
            'descripcion' => $producto['descripcion'] ?? '',
            'descripcion_corta' => $producto['descripcion_corta'] ?? '',
            'categoria_id' => (int) $producto['categoria_id'],
            'subcategoria_id' => $producto['subcategoria_id'] ? (int) $producto['subcategoria_id'] : null,
            'precio' => (float) $producto['precio'],
            'precio_original' => $producto['precio_original'] ? (float) $producto['precio_original'] : null,
            'descuento_porcentaje' => (float) ($producto['descuento_porcentaje'] ?? 0),
            'sku' => $producto['sku'] ?? '',
            'stock' => (int) $producto['stock_total'],
            'stock_minimo' => (int) $producto['stock_minimo'],
            'tallas' => $tallas,
            'colores' => $colores,
            'imagenes' => $imagenes,
            'caracteristicas' => $caracteristicas,
            'material' => $producto['material'] ?? '',
            'edad_recomendada' => $producto['edad_recomendada'] ?? '',
            'destacado' => (bool) $producto['destacado'],
            'nuevo' => (bool) $producto['nuevo'],
            'oferta' => (bool) $producto['oferta'],
            'popular' => (bool) $producto['popular'],
            'visible' => (bool) $producto['visible'],
        ]);
    }

    public function store() {
        $this->checkAdmin();
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['nombre']) || !isset($data['precio'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nombre y precio son requeridos']);
            return;
        }

        $result = $this->productoModel->create($data);

        if ($result) {
            echo json_encode(['success' => true, 'id' => $result['id'], 'message' => 'Producto creado']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al crear producto']);
        }
    }

    public function update($id) {
        $this->checkAdmin();
        $data = json_decode(file_get_contents('php://input'), true);

        $existing = $this->productoModel->getById($id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'Producto no encontrado']);
            return;
        }

        $result = $this->productoModel->update($id, $data);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Producto actualizado']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar producto']);
        }
    }

    public function destroy($id) {
        $this->checkAdmin();
        $existing = $this->productoModel->getById($id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'Producto no encontrado']);
            return;
        }

        $result = $this->productoModel->delete($id);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Producto eliminado']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar producto']);
        }
    }
}
