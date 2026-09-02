<?php
namespace App\Core;

class Model {
    protected $table;
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ":$c", $columns);
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ")
                VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $set = implode(', ', array_map(fn($c) => "$c = :$c", array_keys($data)));
        $sql = "UPDATE {$this->table} SET $set WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($data, ['id' => $id]));
        return $stmt->rowCount() > 0;
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
