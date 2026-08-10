<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\UsuarioModel;

class RepartidorAuthController extends Controller
{
    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
    }

    public function showLogin()
    {
        if (isset($_SESSION['user']) && ($_SESSION['user']['rol'] ?? '') === 'repartidor') {
            $this->redirect('/repartidor');
        }
        $this->view('auth.login-repartidor');
    }

    public function login()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');

        if (!$email || !$password) {
            $this->json(['success' => false, 'message' => 'Completa todos los campos']);
            return;
        }

        $user = $this->usuarioModel->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->json(['success' => false, 'message' => 'Credenciales incorrectas']);
            return;
        }

        if (($user['rol'] ?? '') !== 'repartidor') {
            $this->json(['success' => false, 'message' => 'No tienes acceso como repartidor']);
            return;
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'nombre' => $user['nombre'],
            'apellido' => $user['apellido'] ?? '',
            'rol' => $user['rol'],
            'telefono' => $user['telefono'] ?? '',
            'tipo_vehiculo' => $user['tipo_vehiculo'] ?? '',
            'placa_vehiculo' => $user['placa_vehiculo'] ?? '',
            'total_entregas' => $user['total_entregas'] ?? 0,
            'calificacion_promedio' => $user['calificacion_promedio'] ?? 5.00,
        ];

        $this->json([
            'success' => true,
            'message' => 'Login exitoso',
            'redirect' => '/repartidor'
        ]);
    }

    public function logout()
    {
        $_SESSION = [];
        session_destroy();
        $this->json(['success' => true, 'redirect' => '/repartidor/login']);
    }

    public function me()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'repartidor') {
            $this->json(['success' => false, 'message' => 'No autorizado']);
            return;
        }

        $this->json([
            'success' => true,
            'user' => $_SESSION['user']
        ]);
    }
}
