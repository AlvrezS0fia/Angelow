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

    public function index() {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }
        $usuarios = $this->usuarioModel->getAll();
        $this->json($usuarios);
    }

    public function buscar() {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            $this->json(['error' => 'JSON inválido'], 400);
            return;
        }
        $nombre = trim($data['nombre'] ?? '');

        if ($nombre === '') {
            $this->json($this->usuarioModel->getAll());
            return;
        }

        $usuarios = $this->usuarioModel->buscarPorNombre($nombre);
        $this->json($usuarios);
    }

    public function cambiarRol() {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            $this->json(['error' => 'JSON inválido'], 400);
            return;
        }
        $userId = $data['id'] ?? 0;
        $newRole = $data['rol'] ?? '';

        if (!$userId || !in_array($newRole, ['cliente', 'repartidor', 'administrador'])) {
            $this->json(['error' => 'Datos inválidos'], 400);
            return;
        }

        $result = $this->usuarioModel->updateRol($userId, $newRole);
        if ($result) {
            $this->json(['success' => true, 'message' => 'Rol actualizado correctamente']);
        } else {
            $this->json(['error' => 'Error al actualizar'], 500);
        }
    }

    public function store() {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            $this->json(['error' => 'JSON inválido'], 400);
            return;
        }

        $nombre = trim($data['nombre'] ?? '');
        $email = trim($data['email'] ?? '');
        $telefono = trim($data['telefono'] ?? '');
        $rol = trim($data['rol'] ?? 'cliente');

        if (!$nombre || !$email) {
            $this->json(['error' => 'Nombre y email son obligatorios'], 400);
            return;
        }

        $id = $this->usuarioModel->create([
            'nombre' => $nombre,
            'apellido' => $data['apellido'] ?? '',
            'email' => $email,
            'telefono' => $data['telefono'] ?? '',
            'direccion' => $data['direccion'] ?? '',
            'tipo_vehiculo' => $data['tipo_vehiculo'] ?? 'moto',
            'placa_vehiculo' => $data['placa_vehiculo'] ?? '',
            'rol' => $rol,
            'estado' => $data['estado'] ?? 'activo',
            'password_hash' => password_hash(uniqid(), PASSWORD_DEFAULT)
        ]);

        if ($id) {
            $this->json(['success' => true, 'id' => $id, 'message' => 'Usuario creado correctamente']);
        } else {
            $this->json(['error' => 'Error al crear usuario'], 500);
        }
    }

    public function destroy($id) {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }
        $this->usuarioModel->delete($id);
        $this->json(['success' => true, 'message' => 'Usuario eliminado']);
    }
}