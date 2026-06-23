<?php
// procesar/recuperar_password.php
header('Content-Type: application/json');
require_once 'db.php';

$response = ['success' => false, 'message' => ''];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');
    
    if (empty($email)) {
        $response['message'] = 'El correo electrónico es obligatorio';
        echo json_encode($response);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'El correo no es válido';
        echo json_encode($response);
        exit();
    }
    
    // Verificar si el email existe
    $stmt = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        // No revelamos si el email existe o no por seguridad
        $response['success'] = true;
        $response['message'] = 'Si el correo existe, recibirás un enlace para restablecer tu contraseña';
        echo json_encode($response);
        exit();
    }
    
    // Generar token de recuperación
    $token = generateToken(60);
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $stmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_expiry = ? WHERE id = ?");
    $stmt->execute([$token, $expiry, $usuario['id']]);
    
    // Registrar solicitud
    $stmt = $pdo->prepare("INSERT INTO logs_actividad (usuario_id, tipo, accion, ip_address, fecha) VALUES (?, 'seguridad', 'recuperar_password', ?, NOW())");
    $stmt->execute([$usuario['id'], $_SERVER['REMOTE_ADDR']]);
    
    $response['success'] = true;
    $response['message'] = 'Si el correo existe, recibirás un enlace para restablecer tu contraseña';
    
} catch (PDOException $e) {
    $response['message'] = 'Error en la base de datos';
}

echo json_encode($response);
?>