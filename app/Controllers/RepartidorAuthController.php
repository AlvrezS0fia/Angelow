<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\JWTHelper;
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
        $this->view('repartidor.login');
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

        if (($user['estado'] ?? '') === 'pendiente') {
            $this->json(['success' => false, 'message' => 'Tu solicitud está pendiente de aprobación por el administrador', 'pending' => true]);
            return;
        }

        if (($user['estado'] ?? '') !== 'activo') {
            $this->json(['success' => false, 'message' => 'Tu cuenta no está activa. Contacta al administrador.']);
            return;
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'nombre' => $user['nombre'],
            'apellido' => $user['apellido'] ?? '',
            'rol' => $user['rol'],
            'telefono' => $user['telefono'] ?? '',
            'estado' => $user['estado'] ?? 'activo',
            'tipo_vehiculo' => $user['tipo_vehiculo'] ?? '',
            'placa_vehiculo' => $user['placa_vehiculo'] ?? '',
            'total_entregas' => $user['total_entregas'] ?? 0,
            'calificacion_promedio' => $user['calificacion_promedio'] ?? 5.00,
        ];

        $token = JWTHelper::encode([
            'sub' => $user['id'],
            'email' => $user['email'],
            'rol' => 'repartidor',
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60),
        ]);

        $this->json([
            'success' => true,
            'message' => 'Login exitoso',
            'redirect' => '/repartidor',
            'token' => $token
        ]);
    }

    public function registro()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        $nombre = trim($_POST['nombres'] ?? '');
        $apellido = trim($_POST['apellidos'] ?? '');
        $email = trim($_POST['correo'] ?? '');
        $password = $_POST['pass'] ?? '';
        $celular = trim($_POST['celular'] ?? '');
        $tipodoc = trim($_POST['tipodoc'] ?? '');
        $numdoc = trim($_POST['numdoc'] ?? '');
        $vehiculo = trim($_POST['vehiculo'] ?? '');
        $placa = strtoupper(trim($_POST['placa'] ?? ''));
        $licencia = trim($_POST['licencia'] ?? '');
        $catlicencia = strtoupper(trim($_POST['catlicencia'] ?? ''));
        $tarjeta = trim($_POST['tarjeta'] ?? '');

        if (!$nombre || !$apellido || !$email || !$password || !$celular || !$tipodoc || !$numdoc || !$vehiculo || !$placa || !$licencia || !$catlicencia || !$tarjeta) {
            $this->json(['success' => false, 'message' => 'Completa todos los campos obligatorios'], 400);
            return;
        }

        if ($this->usuarioModel->findByEmail($email)) {
            $this->json(['success' => false, 'message' => 'El correo ya está registrado'], 400);
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $userId = $this->usuarioModel->create([
            'email' => $email,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'password_hash' => $passwordHash,
            'rol' => 'repartidor',
            'telefono' => $celular,
            'tipo_vehiculo' => $vehiculo,
            'placa_vehiculo' => $placa,
            'estado' => 'pendiente',
            'acepta_terminos' => 1,
        ]);

        if ($userId) {
            Database::query("UPDATE usuarios SET cedula = ? WHERE id = ?", [$numdoc, $userId]);
            $this->ensureSolicitudesTable();
            Database::query(
                "INSERT INTO solicitudes_repartidores (usuario_id, nombres, apellidos, email, telefono, tipo_vehiculo, placa_vehiculo, estado, fecha_solicitud) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())",
                [$userId, $nombre, $apellido, $email, $celular, $vehiculo, $placa]
            );
        }

        if (!$userId) {
            $this->json(['success' => false, 'message' => 'Error al crear la cuenta'], 500);
            return;
        }

        $uploadDir = __DIR__ . '/../../../uploads/documentos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $tiposDocs = [
            'soat-file' => 'soat',
            'tarjeta-file' => 'tarjeta_propiedad',
            'licencia-file' => 'licencia'
        ];

        foreach ($tiposDocs as $inputName => $tipo) {
            if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                $fileType = $_FILES[$inputName]['type'];
                if (in_array($fileType, $allowedTypes)) {
                    $ext = pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION);
                    $filename = $userId . '_' . $tipo . '_' . time() . '.' . $ext;
                    $destPath = $uploadDir . $filename;
                    move_uploaded_file($_FILES[$inputName]['tmp_name'], $destPath);

                    try {
                        Database::query(
                            "INSERT INTO documentos (repartidor_id, tipo, archivo_url, estado) VALUES (?, ?, ?, 'pendiente')",
                            [$userId, $tipo, 'uploads/documentos/' . $filename]
                        );
                    } catch (\Exception $e) {
                        $this->ensureDocumentosTable();
                        Database::query(
                            "INSERT INTO documentos (repartidor_id, tipo, archivo_url, estado) VALUES (?, ?, ?, 'pendiente')",
                            [$userId, $tipo, 'uploads/documentos/' . $filename]
                        );
                    }
                }
            }
        }

        $this->json([
            'success' => true,
            'message' => 'Solicitud enviada. Un administrador revisará tus datos y te aprobará el acceso.',
            'pending' => true,
            'redirect' => '/repartidor/login?pending=1'
        ]);
    }

    private function ensureDocumentosTable()
    {
        try {
            Database::query("SELECT 1 FROM documentos LIMIT 1");
        } catch (\Exception $e) {
            Database::query("
                CREATE TABLE IF NOT EXISTS documentos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    repartidor_id INT NOT NULL,
                    tipo VARCHAR(50) NOT NULL,
                    archivo_url VARCHAR(500),
                    estado VARCHAR(50) DEFAULT 'pendiente',
                    observaciones TEXT,
                    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
    }

    private function ensureSolicitudesTable()
    {
        try {
            Database::query("SELECT 1 FROM solicitudes_repartidores LIMIT 1");
        } catch (\Exception $e) {
            Database::query("
                CREATE TABLE IF NOT EXISTS solicitudes_repartidores (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    nombres VARCHAR(100) NOT NULL,
                    apellidos VARCHAR(100),
                    email VARCHAR(255) NOT NULL,
                    telefono VARCHAR(20),
                    tipo_vehiculo VARCHAR(50),
                    placa_vehiculo VARCHAR(20),
                    estado ENUM('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente',
                    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    fecha_respuesta TIMESTAMP NULL DEFAULT NULL,
                    observaciones TEXT,
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
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
