<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class CategoriaModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $sql = "SELECT c.*,
                (SELECT COUNT(*) FROM productos WHERE categoria_id = c.id) as total_productos,
                (SELECT COUNT(*) FROM categorias WHERE parent_id = c.id) as total_subcategorias
                FROM categorias c
                WHERE c.tipo = 'categoria'
                ORDER BY c.orden ASC, c.nombre ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getAllWithSub() {
        $sql = "SELECT c.*,
                (SELECT COUNT(*) FROM productos WHERE subcategoria_id = c.id) as total_productos
                FROM categorias c
                WHERE c.tipo = 'subcategoria'
                ORDER BY c.orden ASC, c.nombre ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM categorias WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

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

    public function update($id, $data) {
        $campos = [];
        $params = ['id' => $id];

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

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM categorias WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

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
