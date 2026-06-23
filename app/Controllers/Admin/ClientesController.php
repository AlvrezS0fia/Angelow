<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\UsuarioModel;

class ClientesController extends Controller
{
    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
    }

    // Listar todos los usuarios con rol 'cliente' (o todos si se desea)
    public function index() {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }
        // Obtener todos los usuarios que no son administradores (opcional)
        $clientes = $this->usuarioModel->getAll(); // Debes implementar getAll en UsuarioModel
        $this->json($clientes);
    }

    // Actualizar rol de un usuario
    public function updateRole() {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['id'] ?? 0;
        $newRole = $data['rol'] ?? '';

        if (!$userId || !in_array($newRole, ['cliente', 'repartidor', 'administrador'])) {
            $this->json(['error' => 'Datos inválidos'], 400);
            return;
        }

        $result = $this->usuarioModel->updateRol($userId, $newRole);
        if ($result) {
            $this->json(['success' => true, 'message' => 'Rol actualizado']);
        } else {
            $this->json(['error' => 'Error al actualizar'], 500);
        }
    }
}