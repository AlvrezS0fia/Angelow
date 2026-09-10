<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * ============================================================
 * ARCHIVO: ProductoModel.php — MÓDULO: Modelo de productos
 * ============================================================
 * QUÉ HACE: CRUD de productos del catálogo. Serializa arrays a JSON
 *           (tallas, colores, imágenes, características) al guardar.
 *           Incluye generación de slug y actualización parcial de campos.
 * TABLA(S): productos, categorias (JOIN para nombres de categoría/sub)
 * QUIÉN LO USA: Api\ProductsController
 */
class ProductoModel {
    /** @var \PDO Conexión PDO obtenida del Singleton Database */
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Lista todos los productos con nombre de categoría y subcategoría.
     * @return array<int, array<string, mixed>>
     */
    public function getAll() {
        // JOIN doble con categorias para traer los nombres legibles
        $sql = "SELECT p.*, c.nombre as categoria_nombre, sc.nombre as subcategoria_nombre
                FROM productos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                LEFT JOIN categorias sc ON p.subcategoria_id = sc.id
                ORDER BY p.fecha_creacion DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /** Obtiene un producto con nombres de categoría/subcategoría. */
    public function getById($id) {
        $sql = "SELECT p.*, c.nombre as categoria_nombre, sc.nombre as subcategoria_nombre
                FROM productos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                LEFT JOIN categorias sc ON p.subcategoria_id = sc.id
                WHERE p.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Inserta un producto. Los arrays (tallas, colores, imágenes, características)
     * se serializan a JSON para almacenarse en columnas tipo TEXT/JSON.
     * @return array{id: int}|false ID insertado o false
     */
    public function create($data) {
        $sql = "INSERT INTO productos (
            nombre, slug, descripcion, descripcion_corta,
            categoria_id, subcategoria_id,
            precio, precio_original, descuento_porcentaje,
            sku, stock_total, stock_minimo,
            tallas_disponibles, colores_disponibles, imagenes,
            caracteristicas, material, edad_recomendada,
            destacado, nuevo, oferta, popular, visible
        ) VALUES (
            :nombre, :slug, :descripcion, :descripcion_corta,
            :categoria_id, :subcategoria_id,
            :precio, :precio_original, :descuento_porcentaje,
            :sku, :stock_total, :stock_minimo,
            :tallas_disponibles, :colores_disponibles, :imagenes,
            :caracteristicas, :material, :edad_recomendada,
            :destacado, :nuevo, :oferta, :popular, :visible
        )";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'nombre' => $data['nombre'] ?? '',
            'slug' => $data['slug'] ?? $this->generarSlug($data['nombre'] ?? ''),
            'descripcion' => $data['descripcion'] ?? '',
            'descripcion_corta' => $data['descripcion_corta'] ?? '',
            'categoria_id' => $data['categoria_id'] ?? 1,
            'subcategoria_id' => $data['subcategoria_id'] ?? null,
            'precio' => $data['precio'] ?? 0,
            'precio_original' => $data['precio_original'] ?? null,
            'descuento_porcentaje' => $data['descuento_porcentaje'] ?? 0,
            'sku' => $data['sku'] ?? null,
            'stock_total' => $data['stock_total'] ?? 0,
            'stock_minimo' => $data['stock_minimo'] ?? 5,
            'tallas_disponibles' => json_encode($data['tallas_disponibles'] ?? []),
            'colores_disponibles' => json_encode($data['colores_disponibles'] ?? []),
            'imagenes' => json_encode($data['imagenes'] ?? []),
            'caracteristicas' => json_encode($data['caracteristicas'] ?? []),
            'material' => $data['material'] ?? null,
            'edad_recomendada' => $data['edad_recomendada'] ?? null,
            'destacado' => isset($data['destacado']) ? ($data['destacado'] ? 1 : 0) : 0,
            'nuevo' => isset($data['nuevo']) ? ($data['nuevo'] ? 1 : 0) : 1,
            'oferta' => isset($data['oferta']) ? ($data['oferta'] ? 1 : 0) : 0,
            'popular' => isset($data['popular']) ? ($data['popular'] ? 1 : 0) : 0,
            'visible' => isset($data['visible']) ? ($data['visible'] ? 1 : 0) : 1,
        ]);

        if ($result) {
            return ['id' => (int) $this->db->lastInsertId()];
        }
        return false;
    }

    /**
     * Actualización parcial dinámica: solo campos presentes en $data.
     * Los campos JSON se codifican antes de guardar (whitelist $camposJson).
     */
    public function update($id, $data) {
        $campos = [];
        $params = ['id' => $id];

        $camposPermitidos = [
            'nombre', 'slug', 'descripcion', 'descripcion_corta',
            'categoria_id', 'subcategoria_id',
            'precio', 'precio_original', 'descuento_porcentaje',
            'sku', 'stock_total', 'stock_minimo',
            'material', 'edad_recomendada',
            'destacado', 'nuevo', 'oferta', 'popular', 'visible'
        ];

        foreach ($camposPermitidos as $campo) {
            if (array_key_exists($campo, $data)) {
                $campos[] = "$campo = :$campo";
                $params[$campo] = $data[$campo];
            }
        }

        $camposJson = [
            'tallas_disponibles', 'colores_disponibles', 'imagenes', 'caracteristicas'
        ];
        foreach ($camposJson as $campo) {
            if (array_key_exists($campo, $data)) {
                $campos[] = "$campo = :$campo";
                $params[$campo] = json_encode($data[$campo]);
            }
        }

        if (empty($campos)) {
            return false;
        }

        $sql = "UPDATE productos SET " . implode(', ', $campos) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /** Elimina un producto por ID. */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM productos WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Similar a CategoriaModel::generarSlug: genera un slug lower-case
     * con guiones; fallback 'producto-' + timestamp si queda vacío.
     */
    private function generarSlug($texto) {
        $slug = strtolower(trim($texto));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        if (empty($slug)) {
            $slug = 'producto-' . time();
        }
        return $slug;
    }
}
