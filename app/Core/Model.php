<?php
namespace App\Core;

use PDO;

/**
 * ============================================================
 * ARCHIVO: Model.php — MÓDULO: Modelo base genérico (CRUD)
 * ============================================================
 * QUÉ HACE: Clase padre para modelos con CRUD genérico (getAll, getById,
 *           create, update, delete). Usa $table protegida y Database::query().
 *           CarritoModel extiende esta clase.
 * TABLA(S): Cualquier tabla definida en $table de la subclase.
 * QUIÉN LO USA: CarritoModel (extendida), potencialmente otros modelos.
 */
class Model {
    // ENCAPSULAMIENTO: propiedades protegidas; la conexión ($db) y la tabla
    // ($table) no se exponen y solo son visibles para esta clase y sus hijas.
    protected string $table;
    protected PDO $db;

    public function __construct() {
        // Reutiliza la conexión Singleton del Database; $table debe ser definida por la subclase
        $this->db = Database::getInstance()->getConnection();
    }

    /** Obtiene todas las filas de la tabla configurada en $table. */
    public function getAll(): array {
        return Database::query("SELECT * FROM {$this->table}")->fetchAll();
    }

    /** @param int|string $id
     * @return array<string, mixed>|false */
    public function getById($id) {
        return Database::query("SELECT * FROM {$this->table} WHERE id = :id", ['id' => $id])->fetch();
    }

    // --- INSERT GENÉRICO ---
    // Construye el INSERT dinámicamente a partir de las claves de $data;
    // esas mismas claves sirven como placeholders nombrados (:clave).
    /** @param array<string, mixed> $data
     * @return int */
    public function create(array $data) {
        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ":$c", $columns);
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ")
                VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = Database::query($sql, $data);
        return (int) $this->db->lastInsertId();
    }

    // --- UPDATE GENÉRICO ---
    // Arma "col = :col" para cada columna de $data y filtra por id.
    /** @param int|string $id
     * @param array<string, mixed> $data */
    public function update($id, array $data): bool {
        $set = implode(', ', array_map(fn($c) => "$c = :$c", array_keys($data)));
        $sql = "UPDATE {$this->table} SET $set WHERE id = :id";
        return Database::query($sql, array_merge($data, ['id' => $id]))->rowCount() > 0;
    }

    // --- DELETE GENÉRICO ---
    // Elimina la fila con el id dado; true solo si afectó alguna fila.
    /** @param int|string $id */
    public function delete($id): bool {
        return Database::query("DELETE FROM {$this->table} WHERE id = :id", ['id' => $id])->rowCount() > 0;
    }
}
