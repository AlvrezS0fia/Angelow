<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class UsuarioModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $email = $data['email'] ?? null;
        $nombre = $data['nombre'] ?? null;
        $apellido = $data['apellido'] ?? null;
        $password_hash = $data['password_hash'] ?? null;
        $rol = $data['rol'] ?? 'cliente';
        $telefono = $data['telefono'] ?? null;
        $direccion = $data['direccion'] ?? null;
        $tipo_vehiculo = $data['tipo_vehiculo'] ?? null;
        $placa_vehiculo = $data['placa_vehiculo'] ?? null;
        $estado = $data['estado'] ?? 'activo';
        $acepta_terminos = isset($data['acepta_terminos']) ? ($data['acepta_terminos'] ? 1 : 0) : 0;
        $fecha_registro = $data['fecha_registro'] ?? date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO usuarios (email, nombre, apellido, password_hash, rol, telefono, direccion, tipo_vehiculo, placa_vehiculo, estado, acepta_terminos, fecha_registro) 
                VALUES (:email, :nombre, :apellido, :password_hash, :rol, :telefono, :direccion, :tipo_vehiculo, :placa_vehiculo, :estado, :acepta_terminos, :fecha_registro)";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            'email' => $email,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'password_hash' => $password_hash,
            'rol' => $rol,
            'telefono' => $telefono,
            'direccion' => $direccion,
            'tipo_vehiculo' => $tipo_vehiculo,
            'placa_vehiculo' => $placa_vehiculo,
            'estado' => $estado,
            'acepta_terminos' => $acepta_terminos,
            'fecha_registro' => $fecha_registro
        ]);
    }

    public function updatePassword($email, $passwordHash) {
        $stmt = $this->db->prepare("UPDATE usuarios SET password_hash = :pwd WHERE email = :email");
        return $stmt->execute(['pwd' => $passwordHash, 'email' => $email]);
    }

    public function setResetToken($email, $token, $expires) {
        $stmt = $this->db->prepare("UPDATE usuarios SET reset_token = :token, reset_expiry = :expires WHERE email = :email");
        return $stmt->execute(['token' => $token, 'expires' => $expires, 'email' => $email]);
    }

    public function findByResetToken($token) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE reset_token = :token AND reset_expiry > NOW()");
        $stmt->execute(['token' => $token]);
        return $stmt->fetch();
    }

    public function clearResetToken($email) {
        $stmt = $this->db->prepare("UPDATE usuarios SET reset_token = NULL, reset_expiry = NULL WHERE email = :email");
        return $stmt->execute(['email' => $email]);
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT id, nombre, email, telefono, rol, fecha_registro FROM usuarios ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function buscarPorNombre($nombre) {
        $stmt = $this->db->prepare("SELECT id, nombre, email, telefono, rol, fecha_registro FROM usuarios WHERE nombre LIKE :nombre OR email LIKE :nombre ORDER BY id DESC");
        $stmt->execute(['nombre' => '%' . $nombre . '%']);
        return $stmt->fetchAll();
    }

    public function updateRol($id, $rol) {
        $stmt = $this->db->prepare("UPDATE usuarios SET rol = :rol WHERE id = :id");
        return $stmt->execute(['rol' => $rol, 'id' => $id]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}