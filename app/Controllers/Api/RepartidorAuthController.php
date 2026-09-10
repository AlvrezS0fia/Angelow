<?php
/**
 * ============================================================
 * ARCHIVO: RepartidorAuthController.php — MÓDULO: API de autenticación de repartidores
 * ============================================================
 * QUÉ HACE: Login, logout y perfil (me) para repartidores. Login con
 *   JWT, rate limiting, verificación de rol y estado. Logout stateless.
 *   me() reconsulta la BD para datos frescos. No extiende Controller base.
 * MODELO(S) QUE USA: Ninguno — usa Database::query() directamente.
 * ENDPOINTS/RUTAS: POST /api/repartidor/auth/login,
 *   POST /api/repartidor/auth/logout, GET /api/repartidor/auth/me
 * QUIÉN LO CONSUME: app repartidor (login.js, sesión de la app móvil/SPA)
 */
namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\JWTHelper;
use App\Core\RateLimiter;

/**
 * Controlador de autenticación exclusivo para repartidores.
 * No comparte login con clientes ni admin; cada rol tiene su propio flujo.
 */
class RepartidorAuthController
{
    /**
     * POST /api/repartidor/auth/login — Autentica repartidor y retorna JWT.
     * Verifica: credenciales, rol='repartidor' en SQL, estado activo,
     *   rate limiting por IP+email (5 intentos / 15 min).
     */
    public function login()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        // El body llega del cliente (JSON). Siempre se parsea 'php://input'.
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            echo json_encode(['success' => false, 'message' => 'Email y contraseña requeridos']);
            return;
        }

        $rlKey = 'login:' . ($_SERVER['REMOTE_ADDR'] ?? '') . ':' . strtolower(trim($email));
        if (RateLimiter::tooMany($rlKey, 5, 900)) {
            http_response_code(429);
            echo json_encode(['success' => false, 'message' => 'Demasiados intentos. Espera 15 minutos.']);
            return;
        }

        // PUNTO CLAVE DE ROL: el filtro AND rol='repartidor' está en el SQL.
        // Así, aunque un cliente use su propio password, NO pasa este login.
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

        RateLimiter::clear($rlKey);

        // Estado 'pendiente' → solicitud aún sin aprobar por el admin.
        if (($user['estado'] ?? '') === 'pendiente') {
            echo json_encode(['success' => false, 'message' => 'Tu solicitud está pendiente de aprobación por el administrador', 'pending' => true]);
            return;
        }

        // Cualquier otro estado no activo ('inactivo', 'suspendido') → bloqueado.
        if (($user['estado'] ?? '') !== 'activo') {
            echo json_encode(['success' => false, 'message' => 'Tu cuenta no está activa. Contacta al administrador.']);
            return;
        }

        Database::query("UPDATE usuarios SET ultima_sesion = NOW() WHERE id = ?", [$user['id']]);

        // --- EMISIÓN DEL JWT ---
        // El token lleva el rol embebido. Cualquier endpoint protegido verificará
        // la firma con JWTHelper::decode; si el secreto cambia, los tokens viejos
        // se invalidan. Vida útil: 24 horas (exp).
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

    // POST /api/repartidor/auth/logout → Cierra sesión.
    //   Como el JWT es stateless, "cerrar sesión" solo descarta el token en la app.
    //   Nota: para invalidar de verdad habría que mantener una lista negra,
    //   algo que el proyecto no implementa hoy (ver docs/SEGURIDAD.md).
    public function logout()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    // GET /api/repartidor/auth/me → Devuelve el perfil del repartidor del token.
    //   ↓ Entrada: header HTTP_AUTHORIZATION = "Bearer {token}".
    //   ↓ Procesamiento: extrae el token, lo decodifica con JWTHelper::decode
    //     (verifica firma + expiración). Debe contener 'sub' (id de usuario).
    //   ↓ Reconsulta en DB: SELECT * FROM usuarios WHERE id = payload.sub
    //     (así los datos SIEMPRE son frescos, no los del momento del login).
    //   ↓ Retorna: JSON {success, user, documentos} → la app actualiza su estado.
    //   ROLES: cualquier token firmado con rol repartidor emitido en login().
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