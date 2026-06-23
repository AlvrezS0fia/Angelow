<?php
// procesar_google.php (en la raíz)
session_start();
require_once 'procesar/db.php';

// Verificar si recibimos datos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_data'])) {
    
    $userData = json_decode($_POST['user_data'], true);
    
    if ($userData && isset($userData['email'])) {
        
        $email = $userData['email'];
        $nombre = $userData['name'] ?? explode('@', $email)[0];
        $avatar_url = $userData['picture'] ?? "https://ui-avatars.com/api/?name=" . urlencode($nombre) . "&background=5E9DE6&color=fff";
        $google_id = $userData['sub'] ?? null;
        
        try {
            // Buscar si el usuario ya existe por email
            $stmt = $pdo->prepare("SELECT id, nombre, email, rol, avatar_url, estado FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();
            
            if (!$usuario) {
                // Crear nuevo usuario (registro automático con Google)
                $sql = "INSERT INTO usuarios (
                    email, nombre, password_hash, rol, avatar_url, 
                    email_verificado, acepta_terminos, fecha_registro, estado
                ) VALUES (
                    :email, :nombre, :password_hash, 'cliente', :avatar_url,
                    1, 1, NOW(), 'activo'
                )";
                
                // Contraseña aleatoria para usuarios de Google 
                $random_password = hashPassword(bin2hex(random_bytes(16)));
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':email' => $email,
                    ':nombre' => $nombre,
                    ':password_hash' => $random_password,
                    ':avatar_url' => $avatar_url
                ]);
                
                $usuario_id = $pdo->lastInsertId();
                
                // Registrar actividad
                $stmt = $pdo->prepare("INSERT INTO logs_actividad (usuario_id, tipo, accion, ip_address, fecha) VALUES (?, 'usuario', 'registro_google', ?, NOW())");
                $stmt->execute([$usuario_id, $_SERVER['REMOTE_ADDR']]);
                
                $usuario = [
                    'id' => $usuario_id,
                    'nombre' => $nombre,
                    'email' => $email,
                    'rol' => 'cliente',
                    'avatar_url' => $avatar_url,
                    'estado' => 'activo'
                ];
            } else {
                // Usuario existe, verificar estado
                if ($usuario['estado'] !== 'activo') {
                    header('Location: login.php?error=cuenta_inactiva');
                    exit();
                }
                
                // Actualizar última sesión
                $stmt = $pdo->prepare("UPDATE usuarios SET ultima_sesion = NOW() WHERE id = ?");
                $stmt->execute([$usuario['id']]);
                
                // Registrar actividad
                $stmt = $pdo->prepare("INSERT INTO logs_actividad (usuario_id, tipo, accion, ip_address, fecha) VALUES (?, 'usuario', 'login_google', ?, NOW())");
                $stmt->execute([$usuario['id'], $_SERVER['REMOTE_ADDR']]);
            }
            
            // Guardar usuario en sesión
            $_SESSION['user'] = [
                'id' => $usuario['id'],
                'name' => $usuario['nombre'],
                'email' => $usuario['email'],
                'avatar' => $usuario['avatar_url'],
                'rol' => $usuario['rol'],
                'login_time' => date('Y-m-d H:i:s'),
                'google_login' => true
            ];
            
            // Redirigir a la tienda
            header('Location: index.php');
            exit();
            
        } catch (PDOException $e) {
            // Error de base de datos
            header('Location: login.php?error=db_error');
            exit();
        }
    }
}

// Si algo falla, volver al login
header('Location: login.php?error=1');
exit();
?>