<?php
namespace App\Controllers\Cliente;

use App\Core\Controller;
use App\Models\UsuarioModel;

class PerfilApiController extends Controller {
    private $model;

    public function __construct() {
        $this->model = new UsuarioModel();
    }

    private function requireAuth() {
        if (!isset($_SESSION['user'])) { $this->json(['error' => 'No autorizado'], 401); }
        return (int)$_SESSION['user']['id'];
    }

    public function index() {
        $uid = $this->requireAuth();
        $user = $this->model->findById($uid);
        if (!$user) { $this->json(['error' => 'Usuario no encontrado'], 404); return; }
        unset($user['password_hash'], $user['reset_token'], $user['reset_expiry']);
        $this->json(['success' => true, 'perfil' => $user]);
    }

    public function actualizar() {
        $uid = $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) { $this->json(['error' => 'Datos vacíos'], 400); return; }

        $db = \App\Core\Database::getInstance()->getConnection();
        $allowed = ['nombre', 'apellido', 'telefono', 'cedula', 'genero', 'fecha_nacimiento'];
        $sets = [];
        $params = ['uid' => $uid];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $input)) {
                $sets[] = "{$field} = :{$field}";
                $params[$field] = $input[$field];
            }
        }

        if (empty($sets)) { $this->json(['error' => 'Sin cambios'], 400); return; }

        $sql = "UPDATE usuarios SET " . implode(', ', $sets) . " WHERE id = :uid";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $updated = $this->model->findById($uid);
        unset($updated['password_hash'], $updated['reset_token'], $updated['reset_expiry']);

        $_SESSION['user']['nombre'] = $updated['nombre'] ?? $_SESSION['user']['nombre'];

        $this->json(['success' => true, 'perfil' => $updated, 'message' => 'Perfil actualizado']);
    }
}
