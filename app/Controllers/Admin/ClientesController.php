<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\UsuarioModel;

// HERENCIA: extiende la base Controller y usa su json() heredado para la API.
class ClientesController extends Controller
{
    private UsuarioModel $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
    }

    // --- GUARDIÁN DE ROL (repetido en cada acción; ver ProductsController) ---
    // Entrada: $_SESSION['user'].
    // Procesamiento: si no hay sesión o el rol no es 'administrador' → 403 JSON.
    // Salida: nada si OK; 403 si el que llama no es administrador.
    private function requireAdmin() {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
            $this->json(['error' => 'No autorizado'], 403);
        }
    }

    // GET /api/clientes → Lista de usuarios (solo admin).
    //   ↓ Los datos vienen de: tabla `usuarios` vía UsuarioModel::getAll()
    //   ↓ Validación en: requireAdmin()
    //   ↓ Retorna a: fetch() del panel (JSON array)
    public function index() {
        $this->requireAdmin();
        $usuarios = $this->usuarioModel->getAll();
        $this->json($usuarios);
    }

    // POST /api/clientes/buscar → Busca clientes (solo admin).
    //   ↓ Datos recibidos desde: buscador del panel (JSON {nombre})
    //   ↓ Validación: requireAdmin() + JSON válido
    //   ↓ Se procesan en: UsuarioModel::buscarPorNombre (LIKE %...%)
    //   ↓ Retorna: JSON array (si nombre vacío → lista completa)
    public function buscar() {
        $this->requireAdmin();
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

    // POST /api/clientes/rol → CAMBIA EL ROL DE UN USUARIO (solo admin).
    //   ↓ Datos recibidos desde: panel usuarios (JSON {id, rol})
    //   ↓ Validación 1 (rol del que llama): requireAdmin() → 403 si no es admin.
    //   ↓ Validación 2 (rol objetivo): whitelist ['cliente','repartidor','administrador'] → 400.
    //   ↓ Se guarda en: tabla `usuarios` vía UsuarioModel::updateRol:
    //     "UPDATE usuarios SET rol = :rol WHERE id = :id"
    //   ↓ Retorna: JSON {success, message}
    //   ESTO AFECTA A: vista del usuario, rutas /admin*, /repartidor*, /perfil,
    //   redirección post-login y login JWT de la app de repartos.
    public function cambiarRol() {
        $this->requireAdmin();
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

    // POST /api/clientes → Crea un usuario manualmente (solo admin).
    //   ↓ Datos recibidos desde: formulario del panel (JSON)
    //   ↓ Validación: requireAdmin() + nombre/email obligatorios
    //   ↓ Se guarda en: tabla `usuarios` (INSERT con password aleatorio + rol elegido)
    //   ↓ Retorna: JSON {success, id}
    public function store() {
        $this->requireAdmin();
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

    // DELETE /api/clientes/{id} → Elimina un usuario (solo admin).
    //   ↓ Validación: requireAdmin()
    //   ↓ Se procesa en: UsuarioModel::delete → DELETE de `usuarios`
    //   ↓ Retorna: JSON {success}
    public function destroy(int $id) {
        $this->requireAdmin();
        $this->usuarioModel->delete($id);
        $this->json(['success' => true, 'message' => 'Usuario eliminado']);
    }
}