<?php
/**
 * ============================================================
 * ARCHIVO: login.php — MÓDULO: Login (legacy)
 * ============================================================
 * QUÉ HACE: Procesa el login por email/password recibido como JSON. Verifica
 *   existencia, estado y contraseña del usuario, actualiza última sesión, guarda
 *   la sesión, opcionalmente crea una cookie de "recordarme" (30 días) y registra
 *   la actividad. Responde siempre JSON.
 * MODELO(S) QUE USA: ninguno (usa conexión PDO de db.php)
 * ENDPOINTS/RUTAS: POST procesar/login.php
 * QUIÉN LO CONSUME: Formulario legacy de login (fetch() desde JS).
 */
// procesar/login.php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Estructura base de respuesta en JSON.
$response = ['success' => false, 'message' => ''];

try {
    // Lee y decodifica el JSON enviado por el formulario.
    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $remember = isset($data['remember']) ? (bool)$data['remember'] : false;
    
    // Validación: email y contraseña obligatorios.
    if (empty($email) || empty($password)) {
        $response['message'] = 'Correo y contraseña son obligatorios';
        echo json_encode($response);
        exit();
    }
    
    // Buscar usuario por email
    $stmt = $pdo->prepare("SELECT id, email, nombre, password_hash, rol, avatar_url, estado FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        $response['message'] = 'Credenciales incorrectas';
        echo json_encode($response);
        exit();
    }
    
    // Verificar estado del usuario
    if ($usuario['estado'] !== 'activo') {
        $response['message'] = 'Tu cuenta está ' . $usuario['estado'] . '. Contacta con soporte.';
        echo json_encode($response);
        exit();
    }
    
    // Verificar contraseña
    if (!verifyPassword($password, $usuario['password_hash'])) {
        $response['message'] = 'Credenciales incorrectas';
        echo json_encode($response);
        exit();
    }
    
    // Actualizar última sesión
    $stmt = $pdo->prepare("UPDATE usuarios SET ultima_sesion = NOW() WHERE id = ?");
    $stmt->execute([$usuario['id']]);
    
    // Guardar en sesión
    $_SESSION['user'] = [
        'id' => $usuario['id'],
        'name' => $usuario['nombre'],
        'email' => $usuario['email'],
        'avatar' => $usuario['avatar_url'] ?: "https://ui-avatars.com/api/?name=" . urlencode($usuario['nombre']) . "&background=5E9DE6&color=fff",
        'rol' => $usuario['rol'],
        'login_time' => date('Y-m-d H:i:s')
    ];
    $_SESSION['user_id'] = $usuario['id'];
    
     // Cookie de remember me (30 días)
     if ($remember) {
         $token = generateToken(60);
         
         // Se persiste el token en la BD para poder verificar la cookie luego.
         $stmt = $pdo->prepare("UPDATE usuarios SET remember_token = ? WHERE id = ?");
         $stmt->execute([$token, $usuario['id']]);
         
         // Cookie segura (httpOnly, y Secure sobre HTTPS), válida por 30 días.
         $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
         setcookie('remember_token', $token, time() + (86400 * 30), '/', '', $secure, true);
     }
    
    // Registrar actividad
    $stmt = $pdo->prepare("INSERT INTO logs_actividad (usuario_id, tipo, accion, ip_address, fecha) VALUES (?, 'usuario', 'login', ?, NOW())");
    $stmt->execute([$usuario['id'], $_SERVER['REMOTE_ADDR']]);
    
    $response['success'] = true;
    $response['message'] = 'Login exitoso';
    $response['redirect'] = 'index.php';
    
} catch (PDOException $e) {
    $response['message'] = 'Error en la base de datos';
}

echo json_encode($response);
?>