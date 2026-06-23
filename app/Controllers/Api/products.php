<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        try {
            if (isset($_GET['id'])) {
                // Obtener un producto específico
                $stmt = $pdo->prepare("
                    SELECT p.*, c.nombre as categoria_nombre, sc.nombre as subcategoria_nombre
                    FROM productos p
                    LEFT JOIN categorias c ON p.categoria_id = c.id
                    LEFT JOIN categorias sc ON p.subcategoria_id = sc.id
                    WHERE p.id = :id
                ");
                $stmt->execute([':id' => $_GET['id']]);
                $product = $stmt->fetch();
                
                if ($product) {
                    // Obtener variantes
                    $stmt = $pdo->prepare("SELECT * FROM variantes_producto WHERE producto_id = :id");
                    $stmt->execute([':id' => $_GET['id']]);
                    $product['variantes'] = $stmt->fetchAll();
                }
                
                echo json_encode($product);
            } else {
                // Obtener todos los productos
                $stmt = $pdo->query("
                    SELECT p.*, c.nombre as categoria_nombre, sc.nombre as subcategoria_nombre
                    FROM productos p
                    LEFT JOIN categorias c ON p.categoria_id = c.id
                    LEFT JOIN categorias sc ON p.subcategoria_id = sc.id
                    ORDER BY p.fecha_creacion DESC
                ");
                echo json_encode($stmt->fetchAll());
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'POST':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                throw new Exception('Datos no válidos');
            }
            
            // Obtener IDs de categorías
            $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nombre = :nombre AND tipo = 'categoria'");
            $stmt->execute([':nombre' => $data['categoria']]);
            $categoria = $stmt->fetch();
            
            if (!$categoria) {
                throw new Exception('Categoría no encontrada');
            }
            
            $subcategoria_id = null;
            if (!empty($data['subcategoria'])) {
                $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nombre = :nombre AND tipo = 'subcategoria'");
                $stmt->execute([':nombre' => $data['subcategoria']]);
                $subcategoria = $stmt->fetch();
                $subcategoria_id = $subcategoria['id'] ?? null;
            }
            
            $sql = "INSERT INTO productos (
                nombre, slug, descripcion, descripcion_corta,
                categoria_id, subcategoria_id, precio, stock_total,
                tallas_disponibles, imagenes, caracteristicas
            ) VALUES (
                :nombre, :slug, :descripcion, :descripcion_corta,
                :categoria_id, :subcategoria_id, :precio, :stock_total,
                :tallas_disponibles, :imagenes, :caracteristicas
            )";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                ':nombre' => $data['nombre'],
                ':slug' => strtolower(str_replace(' ', '-', $data['nombre'])) . '-' . uniqid(),
                ':descripcion' => $data['descripcion'] ?? '',
                ':descripcion_corta' => substr($data['descripcion'] ?? '', 0, 200),
                ':categoria_id' => $categoria['id'],
                ':subcategoria_id' => $subcategoria_id,
                ':precio' => $data['precio'],
                ':stock_total' => $data['stock'],
                ':tallas_disponibles' => json_encode($data['tallas']),
                ':imagenes' => json_encode($data['imagenes'] ?? []),
                ':caracteristicas' => json_encode($data['caracteristicas'] ?? [])
            ]);
            
            if ($result) {
                echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            } else {
                throw new Exception('Error al guardar el producto');
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        try {
            if (!isset($_GET['id'])) {
                throw new Exception('ID no proporcionado');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                throw new Exception('Datos no válidos');
            }
            
            // Obtener IDs de categorías
            $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nombre = :nombre AND tipo = 'categoria'");
            $stmt->execute([':nombre' => $data['categoria']]);
            $categoria = $stmt->fetch();
            
            if (!$categoria) {
                throw new Exception('Categoría no encontrada');
            }
            
            $subcategoria_id = null;
            if (!empty($data['subcategoria'])) {
                $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nombre = :nombre AND tipo = 'subcategoria'");
                $stmt->execute([':nombre' => $data['subcategoria']]);
                $subcategoria = $stmt->fetch();
                $subcategoria_id = $subcategoria['id'] ?? null;
            }
            
            $sql = "UPDATE productos SET
                    nombre = :nombre,
                    slug = :slug,
                    descripcion = :descripcion,
                    categoria_id = :categoria_id,
                    subcategoria_id = :subcategoria_id,
                    precio = :precio,
                    stock_total = :stock_total,
                    tallas_disponibles = :tallas_disponibles,
                    imagenes = :imagenes,
                    caracteristicas = :caracteristicas
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                ':id' => $_GET['id'],
                ':nombre' => $data['nombre'],
                ':slug' => strtolower(str_replace(' ', '-', $data['nombre'])) . '-' . $_GET['id'],
                ':descripcion' => $data['descripcion'] ?? '',
                ':categoria_id' => $categoria['id'],
                ':subcategoria_id' => $subcategoria_id,
                ':precio' => $data['precio'],
                ':stock_total' => $data['stock'],
                ':tallas_disponibles' => json_encode($data['tallas']),
                ':imagenes' => json_encode($data['imagenes'] ?? []),
                ':caracteristicas' => json_encode($data['caracteristicas'] ?? [])
            ]);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Error al actualizar el producto');
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        try {
            if (!isset($_GET['id'])) {
                throw new Exception('ID no proporcionado');
            }
            
            // Eliminar variantes primero
            $stmt = $pdo->prepare("DELETE FROM variantes_producto WHERE producto_id = :id");
            $stmt->execute([':id' => $_GET['id']]);
            
            // Eliminar producto
            $stmt = $pdo->prepare("DELETE FROM productos WHERE id = :id");
            $stmt->execute([':id' => $_GET['id']]);
            
            echo json_encode(['success' => true]);
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}
?>