<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        try {
            // Obtener categorías principales
            $stmt = $pdo->query("SELECT * FROM categorias WHERE tipo = 'categoria' ORDER BY orden, nombre");
            $mainCategories = $stmt->fetchAll();
            
            // Obtener subcategorías
            $stmt = $pdo->query("
                SELECT c.*, p.nombre as padre_nombre 
                FROM categorias c 
                LEFT JOIN categorias p ON c.parent_id = p.id 
                WHERE c.tipo = 'subcategoria' 
                ORDER BY c.nombre
            ");
            $subCategories = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'main' => $mainCategories,
                'sub' => $subCategories
            ]);
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
            
            if ($data['tipo'] === 'categoria') {
                $sql = "INSERT INTO categorias (nombre, slug, descripcion, tipo, visible, destacada, orden) 
                        VALUES (:nombre, :slug, :descripcion, 'categoria', :visible, :destacada, :orden)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $data['nombre'],
                    ':slug' => strtolower(str_replace(' ', '-', $data['nombre'])),
                    ':descripcion' => $data['descripcion'] ?? '',
                    ':visible' => $data['visible'] ? 1 : 0,
                    ':destacada' => $data['destacada'] ? 1 : 0,
                    ':orden' => $data['orden'] ?? 0
                ]);
                
                echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
                
            } else if ($data['tipo'] === 'subcategoria') {
                // Obtener el id de la categoría padre
                $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nombre = :padre AND tipo = 'categoria'");
                $stmt->execute([':padre' => $data['padre']]);
                $parent = $stmt->fetch();
                
                if (!$parent) {
                    throw new Exception('Categoría padre no encontrada');
                }
                
                $sql = "INSERT INTO categorias (nombre, slug, descripcion, parent_id, tipo, visible) 
                        VALUES (:nombre, :slug, :descripcion, :parent_id, 'subcategoria', :visible)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $data['nombre'],
                    ':slug' => strtolower(str_replace(' ', '-', $data['nombre'])),
                    ':descripcion' => $data['descripcion'] ?? '',
                    ':parent_id' => $parent['id'],
                    ':visible' => $data['visible'] ? 1 : 0
                ]);
                
                echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID no proporcionado');
            }
            
            if ($data['tipo'] === 'categoria') {
                $sql = "UPDATE categorias SET 
                        nombre = :nombre,
                        slug = :slug,
                        descripcion = :descripcion,
                        visible = :visible,
                        destacada = :destacada,
                        orden = :orden
                        WHERE id = :id AND tipo = 'categoria'";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $id,
                    ':nombre' => $data['nombre'],
                    ':slug' => strtolower(str_replace(' ', '-', $data['nombre'])),
                    ':descripcion' => $data['descripcion'] ?? '',
                    ':visible' => $data['visible'] ? 1 : 0,
                    ':destacada' => $data['destacada'] ? 1 : 0,
                    ':orden' => $data['orden'] ?? 0
                ]);
                
                echo json_encode(['success' => true]);
                
            } else if ($data['tipo'] === 'subcategoria') {
                // Obtener el id de la categoría padre
                $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nombre = :padre AND tipo = 'categoria'");
                $stmt->execute([':padre' => $data['padre']]);
                $parent = $stmt->fetch();
                
                if (!$parent) {
                    throw new Exception('Categoría padre no encontrada');
                }
                
                $sql = "UPDATE categorias SET 
                        nombre = :nombre,
                        slug = :slug,
                        descripcion = :descripcion,
                        parent_id = :parent_id,
                        visible = :visible
                        WHERE id = :id AND tipo = 'subcategoria'";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $id,
                    ':nombre' => $data['nombre'],
                    ':slug' => strtolower(str_replace(' ', '-', $data['nombre'])),
                    ':descripcion' => $data['descripcion'] ?? '',
                    ':parent_id' => $parent['id'],
                    ':visible' => $data['visible'] ? 1 : 0
                ]);
                
                echo json_encode(['success' => true]);
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        try {
            $id = $_GET['id'] ?? null;
            $tipo = $_GET['tipo'] ?? null;
            
            if (!$id || !$tipo) {
                throw new Exception('ID o tipo no proporcionado');
            }
            
            if ($tipo === 'categoria') {
                // Primero eliminar subcategorías
                $stmt = $pdo->prepare("DELETE FROM categorias WHERE parent_id = :id");
                $stmt->execute([':id' => $id]);
                
                // Luego eliminar la categoría
                $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = :id AND tipo = 'categoria'");
                $stmt->execute([':id' => $id]);
                
            } else if ($tipo === 'subcategoria') {
                $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = :id AND tipo = 'subcategoria'");
                $stmt->execute([':id' => $id]);
            }
            
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