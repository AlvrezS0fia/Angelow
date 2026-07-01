<?php
namespace App\Controllers\Api;

use App\Core\Database;

class CarritoController {

    public function index() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $usuario_id = $_SESSION['user_id'] ?? null;
        $session_id = $_COOKIE['cart_session'] ?? null;

        if (!$usuario_id && !$session_id) {
            echo json_encode(['success' => true, 'cart' => []]);
            return;
        }

        // Obtener el nombre de la subcategoría desde la tabla categorias
        if ($usuario_id) {
            $sql = "SELECT c.*, p.nombre, p.precio, p.imagenes, subcat.nombre as subcategoria, cat.nombre as categoria
                    FROM carrito c 
                    JOIN productos p ON c.producto_id = p.id 
                    LEFT JOIN categorias subcat ON p.subcategoria_id = subcat.id
                    LEFT JOIN categorias cat ON p.categoria_id = cat.id
                    WHERE c.usuario_id = ?";
            $items = Database::query($sql, [$usuario_id])->fetchAll();
        } else {
            $sql = "SELECT c.*, p.nombre, p.precio, p.imagenes, subcat.nombre as subcategoria, cat.nombre as categoria
                    FROM carrito c 
                    JOIN productos p ON c.producto_id = p.id 
                    LEFT JOIN categorias subcat ON p.subcategoria_id = subcat.id
                    LEFT JOIN categorias cat ON p.categoria_id = cat.id
                    WHERE c.session_id = ?";
            $items = Database::query($sql, [$session_id])->fetchAll();
        }

        $cart = array_map(function($item) {
            $imagenes = json_decode($item['imagenes'], true);
            return [
                'cartId'      => $item['id'],
                'id'          => $item['producto_id'],
                'name'        => $item['nombre'],
                'price'       => floatval($item['precio']),
                'quantity'    => $item['cantidad'],
                'selectedSize'=> $item['talla_seleccionada'],
                'subcategory' => $item['subcategoria'] ?? '',
                'category'    => $item['categoria'] ?? '',
                'imgs'        => $imagenes ?: ['assets/imagenes/general/producto.png']
            ];
        }, $items);

        echo json_encode(['success' => true, 'cart' => $cart]);
    }

    public function agregar() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $producto_id = $data['producto_id'] ?? null;
        $talla = $data['talla'] ?? null;
        $cantidad = $data['cantidad'] ?? 1;

        if (!$producto_id) {
            echo json_encode(['success' => false, 'message' => 'Producto no especificado']);
            return;
        }

        $usuario_id = $_SESSION['user_id'] ?? null;
        $session_id = $usuario_id ? null : ($_COOKIE['cart_session'] ?? null);

        // Obtener producto (stock actualizado)
        $producto = Database::query("SELECT * FROM productos WHERE id = ?", [$producto_id])->fetch();
        if (!$producto) {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
            return;
        }

        if ((int) $producto['stock_total'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'Producto agotado']);
            return;
        }

        // Verificar si ya existe
        $existing = Database::query(
            "SELECT id FROM carrito 
             WHERE (usuario_id = ? OR (session_id = ? AND usuario_id IS NULL))
               AND producto_id = ? AND talla_seleccionada = ?",
            [$usuario_id, $session_id, $producto_id, $talla]
        )->fetch();

        if ($existing) {
            $currentQty = (int) Database::query(
                "SELECT cantidad FROM carrito WHERE id = ?",
                [$existing['id']]
            )->fetch()['cantidad'];

            if ($currentQty + (int) $cantidad > (int) $producto['stock_total']) {
                echo json_encode(['success' => false, 'message' => 'Stock insuficiente']);
                return;
            }

            Database::query(
                "UPDATE carrito SET cantidad = cantidad + ?, actualizado_en = NOW() WHERE id = ?",
                [$cantidad, $existing['id']]
            );
        } else {
            if ((int) $cantidad > (int) $producto['stock_total']) {
                echo json_encode(['success' => false, 'message' => 'Stock insuficiente']);
                return;
            }

            Database::query(
                "INSERT INTO carrito (usuario_id, session_id, producto_id, cantidad, precio_unitario, talla_seleccionada) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$usuario_id, $session_id, $producto_id, $cantidad, $producto['precio'], $talla]
            );
        }

        echo json_encode(['success' => true]);
    }

    public function actualizar() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $item_id = $data['item_id'] ?? null;
        $cantidad = $data['cantidad'] ?? null;

        if (!$item_id || $cantidad === null) {
            echo json_encode(['success' => false]);
            return;
        }

        $item = Database::query("SELECT producto_id, cantidad FROM carrito WHERE id = ?", [$item_id])->fetch();
        if (!$item) {
            echo json_encode(['success' => true]);
            return;
        }

        if ($cantidad <= 0) {
            Database::query("DELETE FROM carrito WHERE id = ?", [$item_id]);
        } else {
            $producto = Database::query("SELECT stock_total FROM productos WHERE id = ?", [$item['producto_id']])->fetch();
            if ($producto && $cantidad > (int) $producto['stock_total']) {
                echo json_encode(['success' => false, 'message' => 'Stock insuficiente']);
                return;
            }
            Database::query("UPDATE carrito SET cantidad = ?, actualizado_en = NOW() WHERE id = ?", [$cantidad, $item_id]);
        }

        echo json_encode(['success' => true]);
    }

    public function eliminar() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $item_id = $data['item_id'] ?? null;
        if ($item_id) {
            Database::query("DELETE FROM carrito WHERE id = ?", [$item_id]);
        }
        echo json_encode(['success' => true]);
    }

    public function sincronizar() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'No autenticado']);
            return;
        }
        $session_id = $_COOKIE['cart_session'] ?? null;
        if ($session_id) {
            $items = Database::query("SELECT * FROM carrito WHERE session_id = ?", [$session_id])->fetchAll();
            foreach ($items as $item) {
                $existing = Database::query(
                    "SELECT id FROM carrito WHERE usuario_id = ? AND producto_id = ? AND talla_seleccionada = ?",
                    [$_SESSION['user_id'], $item['producto_id'], $item['talla_seleccionada']]
                )->fetch();
                if ($existing) {
                    Database::query(
                        "UPDATE carrito SET cantidad = cantidad + ? WHERE id = ?",
                        [$item['cantidad'], $existing['id']]
                    );
                } else {
                    Database::query(
                        "INSERT INTO carrito (usuario_id, producto_id, cantidad, precio_unitario, talla_seleccionada) 
                         VALUES (?, ?, ?, ?, ?)",
                        [$_SESSION['user_id'], $item['producto_id'], $item['cantidad'], $item['precio_unitario'], $item['talla_seleccionada']]
                    );
                }
            }
            Database::query("DELETE FROM carrito WHERE session_id = ?", [$session_id]);
            setcookie('cart_session', '', time() - 3600, '/');
        }
        echo json_encode(['success' => true]);
    }
}