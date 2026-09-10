<?php
/**
 * ============================================================
 * ARCHIVO: PerfilApiController.php — MÓDULO: API de perfil del cliente
 * ============================================================
 * QUÉ HACE: Expone el perfil del cliente autenticado en JSON: leer sus datos
 *   personales y actualizar una lista blanca de campos. Oculta datos sensibles
 *   (password_hash, reset_token, reset_expiry) antes de responder.
 * MODELO(S) QUE USA: UsuarioModel, Database (App\Core)
 * ENDPOINTS/RUTAS: GET /api/perfil, PUT /api/perfil
 * QUIÉN LO CONSUME: El formulario de perfil del cliente (fetch() desde JS).
 */
namespace App\Controllers\Cliente;

use App\Core\Controller;
use App\Models\UsuarioModel;

/**
 * Controlador API del perfil del cliente. Extiende la clase base Controller.
 * Patrón MVC: exige sesión (requireAuth) y delega la lectura en UsuarioModel.
 */
class PerfilApiController extends Controller {
    /** Instancia del modelo de usuarios. */
    private $model;

    public function __construct() {
        $this->model = new UsuarioModel();
    }

    /** Verifica sesión y devuelve el id del cliente; si no, responde 401. */
    private function requireAuth() {
        if (!isset($_SESSION['user'])) { $this->json(['error' => 'No autorizado'], 401); }
        return (int)$_SESSION['user']['id'];
    }

    /** Devuelve el perfil del cliente, sin campos sensibles ni técnicos. */
    public function index() {
        $uid = $this->requireAuth();
        $user = $this->model->findById($uid);
        if (!$user) { $this->json(['error' => 'Usuario no encontrado'], 404); return; }
        // Se eliminan campos que el cliente no debe ver (hash y tokens de reseteo).
        unset($user['password_hash'], $user['reset_token'], $user['reset_expiry']);
        $this->json(['success' => true, 'perfil' => $user]);
    }

    /** Actualiza solo los campos permitidos del perfil (lista blanca anti-sobreescritura). */
    public function actualizar() {
        $uid = $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) { $this->json(['error' => 'Datos vacíos'], 400); return; }

        $db = \App\Core\Database::getInstance()->getConnection();
        // Campos editables por el cliente; cualquier otro campo del JSON se ignora.
        $allowed = ['nombre', 'apellido', 'telefono', 'cedula', 'genero', 'fecha_nacimiento'];
        $sets = [];
        $params = ['uid' => $uid];

        // Construye dinámicamente el UPDATE solo con los campos permitidos presentes.
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

        // Refresca el nombre en la sesión para que el menú se actualice al instante.
        $_SESSION['user']['nombre'] = $updated['nombre'] ?? $_SESSION['user']['nombre'];

        $this->json(['success' => true, 'perfil' => $updated, 'message' => 'Perfil actualizado']);
    }
}
