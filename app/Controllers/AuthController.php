<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UsuarioModel;

class AuthController extends Controller {
    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
    }

    // Mostrar formulario de login/registro
    public function showLogin() {
        if (isset($_SESSION['user'])) {
            $this->redirect('/');
        }
        $this->view('auth.login');
    }

    // Procesar login con email
    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $remember = $data['remember'] ?? false;

        if (!$email || !$password) {
            $this->json(['success' => false, 'message' => 'Completa todos los campos']);
            return;
        }

        $user = $this->usuarioModel->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->json(['success' => false, 'message' => 'Credenciales incorrectas']);
            return;
        }

        // Guardar en sesión
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'nombre' => $user['nombre'],
            'rol' => $user['rol'] ?? 'cliente'
        ];

        // Redirección según el rol
        $redirectUrl = ($_SESSION['user']['rol'] === 'administrador') ? '/admin' : '/';

        $this->json([
            'success' => true,
            'message' => 'Login exitoso',
            'redirect' => '/',
            'rol' => $_SESSION['user']['rol']
        ]);
    }

    // Procesar registro con email
    public function register() {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        $nombre = $data['nombre'] ?? '';
        $password = $data['password'] ?? '';
        $terms = $data['terms'] ?? false;

        if (!$email || !$nombre || !$password || !$terms) {
            $this->json(['success' => false, 'message' => 'Completa todos los campos y acepta términos']);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Email inválido']);
            return;
        }
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $this->json(['success' => false, 'message' => 'La contraseña no cumple los requisitos']);
            return;
        }
        if ($this->usuarioModel->findByEmail($email)) {
            $this->json(['success' => false, 'message' => 'El email ya está registrado']);
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Crear usuario sin google_id (tu tabla no tiene esa columna)
        $result = $this->usuarioModel->create([
            'email' => $email,
            'nombre' => $nombre,
            'password_hash' => $passwordHash,
            'rol' => 'cliente',
            'acepta_terminos' => $terms,
            'fecha_registro' => date('Y-m-d H:i:s'),
            'estado' => 'activo'
        ]);

        if ($result) {
            $user = $this->usuarioModel->findByEmail($email);
            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'nombre' => $user['nombre'],
                'rol' => $user['rol'] ?? 'cliente'
            ];
            
            // ==============================================
            // ENVIAR CORREO DE BIENVENIDA (MÓDULO EXTERNO)
            // ==============================================
            $emailSent = false;
            $emailServicePath = __DIR__ . '/../Libraries/EmailService.php';
            
            if (file_exists($emailServicePath)) {
                require_once $emailServicePath;
                if (class_exists('\App\Libraries\EmailService')) {
                    $emailSent = \App\Libraries\EmailService::enviar($email, $nombre, 'bienvenida');
                    
                    if (!$emailSent) {
                        error_log("No se pudo enviar correo de bienvenida a: $email");
                    }
                } else {
                    error_log("EmailService class not found");
                }
            } else {
                error_log("EmailService.php no encontrado en: $emailServicePath");
            }
            // ==============================================
            
            $this->json([
                'success' => true,
                'message' => 'Registro exitoso',
                'redirect' => APP_URL . '/'
            ]);
        } else {
            $this->json(['success' => false, 'message' => 'Error al registrar. Intenta de nuevo']);
        }
    }

    // Procesar login con Google (SIN google_id - adaptado a tu BD)
    public function googleLogin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_data'])) {
            $userData = json_decode($_POST['user_data'], true);
            $email = $userData['email'] ?? '';
            $nombre = $userData['name'] ?? '';
            $googleId = $userData['sub'] ?? '';

            if (empty($email) || empty($nombre)) {
                $this->redirect('/auth/login');
                return;
            }

            $user = $this->usuarioModel->findByEmail($email);
            $esNuevoRegistro = false;

            if (!$user) {
                // Usuario nuevo - registrar sin google_id
                $passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                $result = $this->usuarioModel->create([
                    'email' => $email,
                    'nombre' => $nombre,
                    'password_hash' => $passwordHash,
                    'rol' => 'cliente',
                    'acepta_terminos' => true,
                    'email_verificado' => true,  // Google ya verifica el email
                    'fecha_registro' => date('Y-m-d H:i:s'),
                    'estado' => 'activo'
                ]);
                
                if ($result) {
                    $user = $this->usuarioModel->findByEmail($email);
                    $esNuevoRegistro = true;
                }
            }
            
            // Verificar que el usuario existe
            if (!$user) {
                $this->redirect('/auth/login');
                return;
            }
            
            // Enviar correo de bienvenida si es registro nuevo
            if ($esNuevoRegistro) {
                $emailServicePath = __DIR__ . '/../Libraries/EmailService.php';
                if (file_exists($emailServicePath)) {
                    require_once $emailServicePath;
                    if (class_exists('\App\Libraries\EmailService')) {
                        \App\Libraries\EmailService::enviar($email, $nombre, 'bienvenida');
                    }
                }
            }

            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'nombre' => $user['nombre'],
                'rol' => $user['rol'] ?? 'cliente'
            ];

            // Redirección según rol
            $redirectUrl = ($_SESSION['user']['rol'] === 'administrador') ? '/admin' : '/';
            $this->redirect($redirectUrl);
        } else {
            $this->redirect('/auth/login');
        }
    }

    // Solicitar restablecimiento de contraseña
    public function forgotPassword() {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        
        if (!$email) {
            $this->json(['success' => false, 'message' => 'Email requerido']);
            return;
        }
        
        $user = $this->usuarioModel->findByEmail($email);
        if (!$user) {
            $this->json(['success' => false, 'message' => 'No existe cuenta con ese email']);
            return;
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $this->usuarioModel->setResetToken($email, $token, $expires);
        
        // ==============================================
        // ENVIAR CORREO DE RECUPERACIÓN
        // ==============================================
        $emailSent = false;
        $emailServicePath = __DIR__ . '/../Libraries/EmailService.php';
        
        if (file_exists($emailServicePath)) {
            require_once $emailServicePath;
            if (class_exists('\App\Libraries\EmailService')) {
                $emailSent = \App\Libraries\EmailService::enviar($email, $user['nombre'], 'recuperacion', ['token' => $token]);
            }
        }
        // ==============================================
        
        $mensaje = $emailSent 
            ? 'Revisa tu correo para restablecer tu contraseña'
            : 'No se pudo enviar el correo. Intenta más tarde';
        
        $this->json(['success' => $emailSent, 'message' => $mensaje]);
    }

    // Mostrar formulario de reset
    public function showResetForm() {
        $token = $_GET['token'] ?? '';
        $user = $this->usuarioModel->findByResetToken($token);
        if (!$user) {
            echo "Token inválido o expirado";
            exit;
        }
        $this->view('auth.reset-password', ['token' => $token]);
    }

       // Procesar nuevo password
    public function resetPassword() {
        $data = json_decode(file_get_contents('php://input'), true);
        $token = $data['token'] ?? '';
        $password = $data['password'] ?? '';
        
        $user = $this->usuarioModel->findByResetToken($token);
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Token inválido o expirado']);
            return;
        }
        
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || 
            !preg_match('/[0-9]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $this->json(['success' => false, 'message' => 'La contraseña no cumple los requisitos']);
            return;
        }
        
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $this->usuarioModel->updatePassword($user['email'], $passwordHash);
        $this->usuarioModel->clearResetToken($user['email']);
        
        // Enviar correo de confirmación de cambio de contraseña
        $emailServicePath = __DIR__ . '/../Libraries/EmailService.php';
        if (file_exists($emailServicePath)) {
            require_once $emailServicePath;
            if (class_exists('\App\Libraries\EmailService')) {
                \App\Libraries\EmailService::enviar($user['email'], $user['nombre'], 'cambio-contrasena');
            }
        }
        
        $this->json(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
    }

    // Cerrar sesión
    public function logout() {
        $_SESSION = [];
        session_destroy();
        $this->redirect('/auth/login');
    }

        // Mostrar formulario de cambio de contraseña (usuario logueado)
    public function showChangePassword() {
        if (!isset($_SESSION['user'])) {
            $this->redirect('/auth/login');
            return;
        }
        $this->view('auth.change-password');
    }

    // Procesar cambio de contraseña (usuario logueado)
    public function changePassword() {
        // Verificar que el usuario está logueado
        if (!isset($_SESSION['user'])) {
            $this->json(['success' => false, 'message' => 'Debes iniciar sesión']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';
        
        if (!$currentPassword || !$newPassword) {
            $this->json(['success' => false, 'message' => 'Completa todos los campos']);
            return;
        }
        
        // Validar nueva contraseña
        if (strlen($newPassword) < 8 || !preg_match('/[A-Z]/', $newPassword) || 
            !preg_match('/[0-9]/', $newPassword) || !preg_match('/[^a-zA-Z0-9]/', $newPassword)) {
            $this->json(['success' => false, 'message' => 'La nueva contraseña no cumple los requisitos']);
            return;
        }
        
        // Obtener usuario actual
        $user = $this->usuarioModel->findById($_SESSION['user']['id']);
        
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            $this->json(['success' => false, 'message' => 'Contraseña actual incorrecta']);
            return;
        }
        
        // Actualizar contraseña
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $result = $this->usuarioModel->updatePassword($user['email'], $newPasswordHash);
        
        if ($result) {
            // Enviar correo de notificación de cambio
            $emailServicePath = __DIR__ . '/../Libraries/EmailService.php';
            if (file_exists($emailServicePath)) {
                require_once $emailServicePath;
                if (class_exists('\App\Libraries\EmailService')) {
                    \App\Libraries\EmailService::enviar($user['email'], $user['nombre'], 'cambio-contrasena');
                }
            }
            
            $this->json(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
        } else {
            $this->json(['success' => false, 'message' => 'Error al actualizar la contraseña']);
        }
    }


}