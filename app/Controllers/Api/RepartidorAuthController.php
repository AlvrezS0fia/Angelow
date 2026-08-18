<?php
namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\JWTHelper;

class RepartidorAuthController
{
    public function login()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            echo json_encode(['success' => false, 'message' => 'Email y contraseña requeridos']);
            return;
        }

        $user = Database::query(
            "SELECT * FROM usuarios WHERE email = ? AND rol = 'repartidor'",
            [$email]
        )->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Credenciales inválidas']);
            return;
        }

        if (!password_verify($password, $user['password_hash'])) {
            echo json_encode(['success' => false, 'message' => 'Credenciales inválidas']);
            return;
        }

        if (($user['estado'] ?? '') === 'pendiente') {
            echo json_encode(['success' => false, 'message' => 'Tu solicitud está pendiente de aprobación por el administrador', 'pending' => true]);
            return;
        }

        if (($user['estado'] ?? '') !== 'activo') {
            echo json_encode(['success' => false, 'message' => 'Tu cuenta no está activa. Contacta al administrador.']);
            return;
        }

        Database::query("UPDATE usuarios SET ultima_sesion = NOW() WHERE id = ?", [$user['id']]);

        $token = JWTHelper::encode([
            'sub' => $user['id'],
            'email' => $user['email'],
            'rol' => 'repartidor',
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60),
        ]);

        $documentos = Database::query(
            "SELECT * FROM documentos WHERE repartidor_id = ? ORDER BY fecha_subida DESC",
            [$user['id']]
        )->fetchAll();

        echo json_encode([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'nombres' => $user['nombre'],
                'apellidos' => $user['apellido'] ?? '',
                'email' => $user['email'],
                'rol' => $user['rol'],
                'telefono' => $user['telefono'] ?? '',
                'direccion' => $user['direccion'] ?? '',
                'documentos_completos' => !empty($documentos),
                'tipo_vehiculo' => $user['tipo_vehiculo'] ?? '',
                'placa_vehiculo' => $user['placa_vehiculo'] ?? '',
                'total_entregas' => $user['total_entregas'] ?? 0,
                'calificacion_promedio' => $user['calificacion_promedio'] ?? 5.00,
            ],
            'documentos' => $documentos,
        ]);
    }

    public function logout()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    public function me()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);

        if (!$token) {
            echo json_encode(['success' => false, 'message' => 'Token requerido']);
            return;
        }

        $payload = JWTHelper::decode($token);
        if (!$payload || !isset($payload['sub'])) {
            echo json_encode(['success' => false, 'message' => 'Token inválido o expirado']);
            return;
        }

        $user = Database::query("SELECT * FROM usuarios WHERE id = ?", [$payload['sub']])->fetch();
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            return;
        }

        $documentos = Database::query(
            "SELECT * FROM documentos WHERE repartidor_id = ? ORDER BY fecha_subida DESC",
            [$user['id']]
        )->fetchAll();

        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'nombres' => $user['nombre'],
                'apellidos' => $user['apellido'] ?? '',
                'email' => $user['email'],
                'rol' => $user['rol'],
                'telefono' => $user['telefono'] ?? '',
                'direccion' => $user['direccion'] ?? '',
                'documentos_completos' => !empty($documentos),
                'tipo_vehiculo' => $user['tipo_vehiculo'] ?? '',
                'placa_vehiculo' => $user['placa_vehiculo'] ?? '',
                'total_entregas' => $user['total_entregas'] ?? 0,
                'calificacion_promedio' => $user['calificacion_promedio'] ?? 5.00,
            ],
            'documentos' => $documentos,
        ]);
    }
}
