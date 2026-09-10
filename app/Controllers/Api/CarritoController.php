<?php
/**
 * ============================================================
 * ARCHIVO: CarritoController.php — MÓDULO: API de carrito de compras
 * ============================================================
 * QUÉ HACE: CRUD del carrito de compras: listar, agregar, actualizar
 *   cantidad, eliminar ítems, sincronizar carrito anónimo al login
 *   y vaciar. No usa CarritoModel, usa Database::query directo.
 * MODELO(S) QUE USA: Ninguno — usa Database::query() directamente.
 * ENDPOINTS/RUTAS: GET /api/carrito, POST /api/carrito/agregar,
 *   PUT /api/carrito/actualizar, DELETE /api/carrito/eliminar,
 *   POST /api/carrito/sincronizar, DELETE /api/carrito/vaciar
 * QUIÉN LO CONSUME: compra.js (flujo de compra del cliente)
 */
namespace App\Controllers\Api;

use App\Core\Database;

/**
 * Controlador del carrito de compras. Soporta usuarios logueados
 * (por usuario_id) y anónimos (por cookie cart_session).
 * No extiende Controller base; gestiona JSON manualmente.
 */
class CarritoController {

    /**
     * GET /api/carrito — Retorna los ítems del carrito.
     * Usa usuario_id si está logueado, o cart_session cookie si es anónimo.
     * JOIN con productos para obtener nombre, precio, imágenes, stock y categorías.
     */
    public function index() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            $usuario_id = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;
            $session_id = $_COOKIE['cart_session'] ?? null;

            if (!$usuario_id && !$session_id) {
                echo json_encode(['success' => true, 'cart' => []]);
                return;
            }

            if ($usuario_id) {
                $sql = "SELECT c.*, p.nombre, p.precio, p.imagenes, p.stock_total, subcat.nombre as subcategoria, cat.nombre as categoria
                        FROM carrito c 
                        JOIN productos p ON c.producto_id = p.id 
                        LEFT JOIN categorias subcat ON p.subcategoria_id = subcat.id
                        LEFT JOIN categorias cat ON p.categoria_id = cat.id
                        WHERE c.usuario_id = ?";
                $items = Database::query($sql, [$usuario_id])->fetchAll();
            } else {
                $sql = "SELECT c.*, p.nombre, p.precio, p.imagenes, p.stock_total, subcat.nombre as subcategoria, cat.nombre as categoria
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
                    'imgs'        => $imagenes ?: ['assets/imagenes/general/producto.png'],
                    'stock'       => (int)($item['stock_total'] ?? 0)
                ];
            }, $items);

            echo json_encode(['success' => true, 'cart' => $cart]);

        } catch (\Exception $e) {
            error_log("CarritoController::index error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al obtener el carrito', 'cart' => []]);
        }
    }

    /**
     * POST /api/carrito/agregar — Agrega un producto al carrito.
     * Si ya existe (misma talla), incrementa cantidad respetaando stock.
     * Si no existe, crea un nuevo registro con precio_unitario actual.
     */
    public function agregar() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $producto_id = $data['producto_id'] ?? null;
            $talla = $data['talla'] ?? null;
            $cantidad = $data['cantidad'] ?? 1;

            if (!$producto_id) {
                echo json_encode(['success' => false, 'message' => 'Producto no especificado']);
                return;
            }

            $usuario_id = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;
            $session_id = $usuario_id ? null : ($_COOKIE['cart_session'] ?? null);

            $producto = Database::query("SELECT * FROM productos WHERE id = ?", [$producto_id])->fetch();
            if (!$producto) {
                echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
                return;
            }

            if ((int) $producto['stock_total'] <= 0) {
                echo json_encode(['success' => false, 'message' => 'Producto agotado']);
                return;
            }

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

                echo json_encode(['success' => true, 'cartId' => (int)$existing['id']]);
                return;
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
                $newId = (int) Database::getInstance()->getConnection()->lastInsertId();

                echo json_encode(['success' => true, 'cartId' => $newId]);
                return;
            }

        } catch (\Exception $e) {
            error_log("CarritoController::agregar error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al agregar al carrito']);
        }
    }

    /**
     * PUT /api/carrito/actualizar — Cambia la cantidad de un ítem.
     * Si cantidad ≤ 0, elimina el ítem del carrito.
     */
    public function actualizar() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $item_id = $data['item_id'] ?? null;
            $cantidad = $data['cantidad'] ?? null;

            if (!$item_id || $cantidad === null) {
                echo json_encode(['success' => false]);
                return;
            }

            $usuario_id = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;
            $session_id = $_COOKIE['cart_session'] ?? null;

            if ($usuario_id) {
                $item = Database::query("SELECT producto_id, cantidad FROM carrito WHERE id = ? AND usuario_id = ?", [$item_id, $usuario_id])->fetch();
            } else {
                $item = Database::query("SELECT producto_id, cantidad FROM carrito WHERE id = ? AND session_id = ?", [$item_id, $session_id])->fetch();
            }

            if (!$item) {
                echo json_encode(['success' => false, 'message' => 'Item no encontrado']);
                return;
            }

            if ($cantidad <= 0) {
                if ($usuario_id) {
                    Database::query("DELETE FROM carrito WHERE id = ? AND usuario_id = ?", [$item_id, $usuario_id]);
                } else {
                    Database::query("DELETE FROM carrito WHERE id = ? AND session_id = ?", [$item_id, $session_id]);
                }
            } else {
                $producto = Database::query("SELECT stock_total FROM productos WHERE id = ?", [$item['producto_id']])->fetch();
                if ($producto && $cantidad > (int) $producto['stock_total']) {
                    echo json_encode(['success' => false, 'message' => 'Stock insuficiente']);
                    return;
                }
                Database::query("UPDATE carrito SET cantidad = ?, actualizado_en = NOW() WHERE id = ?", [$cantidad, $item_id]);
            }

            echo json_encode(['success' => true]);

        } catch (\Exception $e) {
            error_log("CarritoController::actualizar error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el carrito']);
        }
    }

    /**
     * DELETE /api/carrito/eliminar — Elimina un ítem específico del carrito.
     */
    public function eliminar() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $item_id = $data['item_id'] ?? null;

            if (!$item_id) {
                echo json_encode(['success' => false]);
                return;
            }

            $usuario_id = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;
            $session_id = $_COOKIE['cart_session'] ?? null;

            if ($usuario_id) {
                Database::query("DELETE FROM carrito WHERE id = ? AND usuario_id = ?", [$item_id, $usuario_id]);
            } elseif ($session_id) {
                Database::query("DELETE FROM carrito WHERE id = ? AND session_id = ?", [$item_id, $session_id]);
            }

            echo json_encode(['success' => true]);

        } catch (\Exception $e) {
            error_log("CarritoController::eliminar error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al eliminar del carrito']);
        }
    }

    /**
     * POST /api/carrito/sincronizar — Fusiona el carrito anónimo (session_id)
     *   con el del usuario logueado. Si hay duplicados, suma cantidades.
     *   Limpia los registros anónimos y la cookie cart_session.
     */
    public function sincronizar() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            $usuario_id = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;
            if (!$usuario_id) {
                echo json_encode(['success' => false, 'message' => 'No autenticado']);
                return;
            }
            $session_id = $_COOKIE['cart_session'] ?? null;
            if ($session_id) {
                $items = Database::query("SELECT * FROM carrito WHERE session_id = ?", [$session_id])->fetchAll();
                foreach ($items as $item) {
                    $existing = Database::query(
                        "SELECT id FROM carrito WHERE usuario_id = ? AND producto_id = ? AND talla_seleccionada = ?",
                        [$usuario_id, $item['producto_id'], $item['talla_seleccionada']]
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
                            [$usuario_id, $item['producto_id'], $item['cantidad'], $item['precio_unitario'], $item['talla_seleccionada']]
                        );
                    }
                }
                Database::query("DELETE FROM carrito WHERE session_id = ?", [$session_id]);
                setcookie('cart_session', '', time() - 3600, '/');
            }
            echo json_encode(['success' => true]);

        } catch (\Exception $e) {
            error_log("CarritoController::sincronizar error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al sincronizar el carrito']);
        }
    }

    /**
     * DELETE /api/carrito/vaciar — Elimina todos los ítems del carrito
     *   del usuario logueado o de la sesión anónima.
     */
    public function vaciar() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            $usuario_id = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;
            $session_id = $_COOKIE['cart_session'] ?? null;

            if ($usuario_id) {
                Database::query("DELETE FROM carrito WHERE usuario_id = ?", [$usuario_id]);
            } elseif ($session_id) {
                Database::query("DELETE FROM carrito WHERE session_id = ?", [$session_id]);
            }

            echo json_encode(['success' => true]);

        } catch (\Exception $e) {
            error_log("CarritoController::vaciar error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al vaciar el carrito']);
        }
    }
}
