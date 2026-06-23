<?php
// procesar/login.php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

$response = ['success' => false, 'message' => ''];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $remember = isset($data['remember']) ? (bool)$data['remember'] : false;
    
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
    
     // Cookie de remember me (30 días)
     if ($remember) {
         $token = generateToken(60);
         
         $stmt = $pdo->prepare("UPDATE usuarios SET remember_token = ? WHERE id = ?");
         $stmt->execute([$token, $usuario['id']]);
         
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