<?php
namespace App\Core;

use PDO;

class Model {
    // ENCAPSULAMIENTO: propiedades protegidas; la conexión ($db) y la tabla
    // ($table) no se exponen y solo son visibles para esta clase y sus hijas.
    protected string $table;
    protected PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(): array {
        return Database::query("SELECT * FROM {$this->table}")->fetchAll();
    }

    /** @param int|string $id
     * @return array<string, mixed>|false */
    public function getById($id) {
        return Database::query("SELECT * FROM {$this->table} WHERE id = :id", ['id' => $id])->fetch();
    }

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

    /** @param int|string $id
     * @param array<string, mixed> $data */
    public function update($id, array $data): bool {
        $set = implode(', ', array_map(fn($c) => "$c = :$c", array_keys($data)));
        $sql = "UPDATE {$this->table} SET $set WHERE id = :id";
        return Database::query($sql, array_merge($data, ['id' => $id]))->rowCount() > 0;
    }

    /** @param int|string $id */
    public function delete($id): bool {
        return Database::query("DELETE FROM {$this->table} WHERE id = :id", ['id' => $id])->rowCount() > 0;
    }
}
