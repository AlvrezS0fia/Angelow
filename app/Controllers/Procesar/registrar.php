<?php
// procesar/registrar.php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

$response = ['success' => false, 'message' => ''];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = trim($data['email'] ?? '');
    $nombre = trim($data['nombre'] ?? '');
    $password = $data['password'] ?? '';
    $acepta_marketing = isset($data['newsletter']) ? (bool)$data['newsletter'] : true;
    $acepta_terminos = isset($data['terms']) ? (bool)$data['terms'] : false;
    
    // Validaciones
    if (empty($email)) {
        $response['message'] = 'El correo electrónico es obligatorio';
        echo json_encode($response);
        exit();
    }
    
    if (empty($nombre)) {
        $response['message'] = 'El nombre completo es obligatorio';
        echo json_encode($response);
        exit();
    }
    
    if (strlen($password) < 8) {
        $response['message'] = 'La contraseña debe tener al menos 8 caracteres';
        echo json_encode($response);
        exit();
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $response['message'] = 'La contraseña debe contener al menos una mayúscula';
        echo json_encode($response);
        exit();
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $response['message'] = 'La contraseña debe contener al menos un número';
        echo json_encode($response);
        exit();
    }
    
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $response['message'] = 'La contraseña debe contener al menos un carácter especial';
        echo json_encode($response);
        exit();
    }
    
    if (!$acepta_terminos) {
        $response['message'] = 'Debes aceptar los términos y condiciones';
        echo json_encode($response);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'El correo electrónico no es válido';
        echo json_encode($response);
        exit();
    }
    
    // Verificar si el email ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        $response['message'] = 'Este correo ya está registrado';
        echo json_encode($response);
        exit();
    }
    
    // Insertar usuario
    $password_hash = hashPassword($password);
    
    $sql = "INSERT INTO usuarios (
        email, nombre, password_hash, rol, acepta_marketing, acepta_terminos, 
        email_verificado, avatar_url, fecha_registro, estado
    ) VALUES (
        :email, :nombre, :password_hash, 'cliente', :acepta_marketing, :acepta_terminos,
        0, :avatar_url, NOW(), 'activo'
    )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':email' => $email,
        ':nombre' => $nombre,
        ':password_hash' => $password_hash,
        ':acepta_marketing' => $acepta_marketing,
        ':acepta_terminos' => $acepta_terminos,
        ':avatar_url' => "https://ui-avatars.com/api/?name=" . urlencode($nombre) . "&background=5E9DE6&color=fff"
    ]);
    
    $usuario_id = $pdo->lastInsertId();
    
    // Iniciar sesión automáticamente
    $_SESSION['user'] = [
        'id' => $usuario_id,
        'name' => $nombre,
        'email' => $email,
        'avatar' => "https://ui-avatars.com/api/?name=" . urlencode($nombre) . "&background=5E9DE6&color=fff",
        'rol' => 'cliente',
        'login_time' => date('Y-m-d H:i:s')
    ];
    
    // Registrar actividad
    $stmt = $pdo->prepare("INSERT INTO logs_actividad (usuario_id, tipo, accion, ip_address, fecha) VALUES (?, 'usuario', 'registro', ?, NOW())");
    $stmt->execute([$usuario_id, $_SERVER['REMOTE_ADDR']]);
    
    $response['success'] = true;
    $response['message'] = 'Registro exitoso';
    $response['redirect'] = 'index.php';
    
} catch (PDOException $e) {
    $response['message'] = 'Error en la base de datos';
}

echo json_encode($response);
?>