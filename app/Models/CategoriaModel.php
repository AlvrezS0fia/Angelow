<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * ============================================================
 * ARCHIVO: CategoriaModel.php — MÓDULO: Modelo de categorías
 * ============================================================
 * QUÉ HACE: CRUD de categorías y subcategorías del catálogo de ropa.
 *           Calcula conteo de productos y subcategorías vía subconsultas.
 * TABLA(S): categorias, productos (subconsulta COUNT)
 * QUIÉN LO USA: Api\CategoriesController
 */
class CategoriaModel {
    /** @var \PDO Conexión PDO obtenida del Singleton Database */
    private $db;

    public function __construct() {
        // Singleton: reutiliza la conexión de Database en lugar de crear una nueva
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Lista todas las categorías principales con conteo de productos y subcategorías.
     * Solo retorna registros tipo='categoria' (no subcategorías).
     * @return array<int, array<string, mixed>>
     */
    public function getAll() {
        // Subconsultas COUNT para total_productos y total_subcategorias en una sola query
        $sql = "SELECT c.*,
                (SELECT COUNT(*) FROM productos WHERE categoria_id = c.id) as total_productos,
                (SELECT COUNT(*) FROM categorias WHERE parent_id = c.id) as total_subcategorias
                FROM categorias c
                WHERE c.tipo = 'categoria'
                ORDER BY c.orden ASC, c.nombre ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Lista todas las subcategorías con conteo de productos.
     * @return array<int, array<string, mixed>>
     */
    public function getAllWithSub() {
        // Subconsulta COUNT para total_productos de cada subcategoría
        $sql = "SELECT c.*,
                (SELECT COUNT(*) FROM productos WHERE subcategoria_id = c.id) as total_productos
                FROM categorias c
                WHERE c.tipo = 'subcategoria'
                ORDER BY c.orden ASC, c.nombre ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /** Obtiene una categoría por su ID. */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM categorias WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Inserta una categoría nueva. Genera slug automáticamente si no se provee.
     * @return array{id: int}|false ID insertado o false si falla
     */
    public function create($data) {
        $sql = "INSERT INTO categorias (nombre, slug, descripcion, imagen_url, parent_id, orden, visible, tipo, destacada)
                VALUES (:nombre, :slug, :descripcion, :imagen_url, :parent_id, :orden, :visible, :tipo, :destacada)";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'nombre' => $data['nombre'] ?? '',
            'slug' => $data['slug'] ?? $this->generarSlug($data['nombre'] ?? ''),
            'descripcion' => $data['descripcion'] ?? null,
            'imagen_url' => $data['imagen_url'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'orden' => $data['orden'] ?? 0,
            'visible' => isset($data['visible']) ? ($data['visible'] ? 1 : 0) : 1,
            'tipo' => $data['tipo'] ?? 'categoria',
            'destacada' => isset($data['destacada']) ? ($data['destacada'] ? 1 : 0) : 0,
        ]);

        if ($result) {
            return ['id' => (int) $this->db->lastInsertId()];
        }
        return false;
    }

    /**
     * Actualiza parcialmente solo los campos provistos en $data (update dinámico).
     * Solo permite modificar campos de la whitelist $camposPermitidos.
     */
    public function update($id, $data) {
        $campos = [];
        $params = ['id' => $id];

        // Whitelist: solo estos campos pueden actualizarse
        $camposPermitidos = ['nombre', 'slug', 'descripcion', 'imagen_url', 'parent_id', 'orden', 'visible', 'tipo', 'destacada'];

        foreach ($camposPermitidos as $campo) {
            if (array_key_exists($campo, $data)) {
                $campos[] = "$campo = :$campo";
                $params[$campo] = $data[$campo];
            }
        }

        if (empty($campos)) {
            return false;
        }

        $sql = "UPDATE categorias SET " . implode(', ', $campos) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /** Elimina una categoría por ID. */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM categorias WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Genera un slug URL-friendly a partir de un texto.
     * Convierte a minúsculas, reemplaza caracteres especiales por guiones.
     * Si el resultado queda vacío, usa 'categoria-' + timestamp.
     */
    private function generarSlug($texto) {
        $slug = strtolower(trim($texto));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        if (empty($slug)) {
            $slug = 'categoria-' . time();
        }
        return $slug;
    }
}
