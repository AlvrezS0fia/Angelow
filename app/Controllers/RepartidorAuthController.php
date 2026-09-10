<?php
/**
 * ============================================================
 * ARCHIVO: RepartidorAuthController.php — MÓDULO: Autenticación del repartidor
 * ============================================================
 * QUÉ HACE: Gestiona login, registro (solicitud), logout y token JWT del repartidor.
 *   Incluye límite de intentos de login, validación exhaustiva del registro,
 *   subida de documentos (SOAT, tarjeta, licencia, tecnomecánica) y notificación
 *   a los administradores de cada nueva solicitud.
 * MODELO(S) QUE USA: UsuarioModel, Database (App\Core), JWTHelper (App\Core)
 * ENDPOINTS/RUTAS: GET/POST /repartidor/login, POST /repartidor/registro,
 *   POST /repartidor/logout, GET /repartidor/me, POST /repartidor/refresh-token
 * QUIÉN LO CONSUME: La app web del repartidor (vistas repartidor.login y el formulario
 *   de registro de la app movil/web del repartidor).
 */
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\JWTHelper;
use App\Models\UsuarioModel;

/**
 * Controlador de autenticación del repartidor. Extiende la clase base Controller.
 * Patrón MVC con manejo de sesión propia y tokens JWT (JWTHelper) para la app del repartidor.
 */
class RepartidorAuthController extends Controller
{
    /** Instancia del modelo de usuarios. */
    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
    }

    /** Envía salida JSON limpia (sin salida previa) y termina la ejecución. */
    private function jsonResponse($data, $code = 200)
    {
        // Limpia cualquier salida previa (ej. warnings) para no romper el JSON.
        if (ob_get_length()) ob_clean();
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function showLogin()
    {
        // Si ya hay un repartidor en sesión, se va directo a su dashboard.
        if (isset($_SESSION['user']) && ($_SESSION['user']['rol'] ?? '') === 'repartidor') {
            $this->redirect('/repartidor');
        }
        $this->view('repartidor.login');
    }

    public function login()
    {
        // Lee el JSON del formulario y lo normaliza (quita espacios del email).
        $data = json_decode(file_get_contents('php://input'), true);
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            $this->jsonResponse(['success' => false, 'message' => 'Completa todos los campos']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['success' => false, 'message' => 'Formato de correo inválido']);
        }

        // Contador de intentos fallidos por email (5 máx en 15 min) guardado en sesión.
        $loginKey = 'login_attempts_' . md5($email);
        $attempts = $_SESSION[$loginKey]['count'] ?? 0;
        $lastAttempt = $_SESSION[$loginKey]['last'] ?? 0;

        if ($attempts >= 5 && (time() - $lastAttempt) < 900) {
            $remaining = 900 - (time() - $lastAttempt);
            $minutes = ceil($remaining / 60);
            $this->jsonResponse(['success' => false, 'message' => "Demasiados intentos. Espera {$minutes} minuto(s)."]);
        }

        // Verifica credenciales con password_verify (hash bcrypt almacenado).
        $user = $this->usuarioModel->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $attempts++;
            $_SESSION[$loginKey] = ['count' => $attempts, 'last' => time()];
            $remaining = 5 - $attempts;
            $msg = $remaining > 0
                ? "Credenciales incorrectas. Te quedan {$remaining} intento(s)."
                : 'Credenciales incorrectas. Cuenta bloqueada temporalmente (15 min).';
            $this->jsonResponse(['success' => false, 'message' => $msg]);
        }

        unset($_SESSION[$loginKey]);

        // Si el rol no es repartidor, se rechaza el acceso.
        if (($user['rol'] ?? '') !== 'repartidor') {
            $this->jsonResponse(['success' => false, 'message' => 'No tienes acceso como repartidor']);
        }

        // Estado "pendiente": se guarda sesión parcial y se informa que está en revisión.
        if (($user['estado'] ?? '') === 'pendiente') {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'nombre' => $user['nombre'],
                'apellido' => $user['apellido'] ?? '',
                'rol' => $user['rol'],
                'telefono' => $user['telefono'] ?? '',
                'estado' => 'pendiente',
            ];
            $this->jsonResponse(['success' => false, 'message' => 'Tu solicitud está pendiente de aprobación por el administrador', 'pending' => true, 'redirect' => '/repartidor/dashboard']);
        }

        // Estado inactivo o suspendido: no se permite ingresar.
        if (($user['estado'] ?? '') !== 'activo') {
            $this->jsonResponse(['success' => false, 'message' => 'Tu cuenta no está activa. Contacta al administrador.']);
        }

        // Guarda todos los datos del repartidor en la sesión.
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'nombre' => $user['nombre'],
            'apellido' => $user['apellido'] ?? '',
            'rol' => $user['rol'],
            'telefono' => $user['telefono'] ?? '',
            'estado' => $user['estado'] ?? 'activo',
            'tipo_documento' => $user['tipo_documento'] ?? '',
            'numero_documento' => $user['cedula'] ?? '',
            'tipo_vehiculo' => $user['tipo_vehiculo'] ?? '',
            'placa_vehiculo' => $user['placa_vehiculo'] ?? '',
            'numero_licencia' => $user['numero_licencia'] ?? '',
            'categoria_licencia' => $user['categoria_licencia'] ?? '',
            'total_entregas' => $user['total_entregas'] ?? 0,
            'calificacion_promedio' => $user['calificacion_promedio'] ?? 5.00,
        ];
        $_SESSION['user_id'] = $user['id'];
        session_regenerate_id(true);

        try {
            // Marca último acceso y pone al repartidor en línea en la BD.
            Database::query("UPDATE usuarios SET ultima_sesion = NOW(), en_linea = 1 WHERE id = ?", [$user['id']]);
        } catch (\Exception $e) {
        }

        // Devuelve un token JWT (JWTHelper) con expiración de 24 h para la app del repartidor.
        $token = JWTHelper::encode([
            'sub' => $user['id'],
            'email' => $user['email'],
            'rol' => 'repartidor',
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60),
        ]);

        $this->jsonResponse([
            'success' => true,
            'message' => 'Login exitoso',
            'redirect' => '/repartidor/dashboard',
            'token' => $token
        ]);
    }

    /**
     * Registra la solicitud de un nuevo repartidor (POST multipart).
     * Valida todos los campos (incl. edad, vehículo, SOAT/tecnomecánica vigentes),
     * crea/actualiza al usuario y su solicitud, sube los documentos de soporte y
     * notifica a los administradores. Salida: JSON con estado "pendiente".
     */
    public function registro()
    {
        // Limpia buffers y fija respuesta JSON para el formulario.
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        // Solo se acepta el método POST.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metodo no permitido']);
            exit;
        }

        // Lee todos los campos del formulario (multipart) y normaliza placa/categoría a mayúsculas.
        $nombre = trim($_POST['nombres'] ?? '');
        $apellido = trim($_POST['apellidos'] ?? '');
        $email = trim($_POST['correo'] ?? '');
        $password = $_POST['pass'] ?? '';
        $passwordConfirm = $_POST['pass_confirm'] ?? '';
        $celular = trim($_POST['celular'] ?? '');
        $tipodoc = trim($_POST['tipodoc'] ?? '');
        $numdoc = trim($_POST['numdoc'] ?? '');
        $fechaNacimiento = trim($_POST['fecha_nacimiento'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $ciudad = trim($_POST['ciudad'] ?? '');
        $vehiculo = trim($_POST['vehiculo'] ?? '');
        $placa = strtoupper(trim($_POST['placa'] ?? ''));
        $licencia = trim($_POST['licencia'] ?? '');
        $catlicencia = strtoupper(trim($_POST['catlicencia'] ?? ''));
        $tarjeta = trim($_POST['tarjeta'] ?? '');
        $aceptaTerminos = intval($_POST['acepta_terminos'] ?? 0);
        $aceptaPrivacidad = intval($_POST['acepta_privacidad'] ?? 0);
        $soatVencimiento = trim($_POST['soat_vencimiento'] ?? '');
        $tecnomeVencimiento = trim($_POST['tecnomecanica_vencimiento'] ?? '');

        // Arreglo acumulador de errores de validación (uno por regla incumplida).
        $errors = [];
        if (!$nombre || mb_strlen($nombre) < 2) $errors[] = 'Nombres requeridos (mín. 2 caracteres)';
        if (!preg_match('/^[a-zA-ZáéíóúñÁÉÍÓÚÑ\s]+$/', $nombre)) $errors[] = 'Nombres solo pueden contener letras';
        if (!$apellido || mb_strlen($apellido) < 2) $errors[] = 'Apellidos requeridos (mín. 2 caracteres)';
        if (!preg_match('/^[a-zA-ZáéíóúñÁÉÍÓÚÑ\s]+$/', $apellido)) $errors[] = 'Apellidos solo pueden contener letras';
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Correo electrónico inválido';
        if (!$celular || !preg_match('/^3\d{9}$/', preg_replace('/\s+/', '', $celular))) $errors[] = 'Celular inválido (debe iniciar en 3 y tener 10 dígitos)';
        if (!$tipodoc || !in_array($tipodoc, ['CC', 'CE', 'TI', 'PAS'])) $errors[] = 'Tipo de documento inválido';
        if (!$numdoc || !preg_match('/^\d{5,12}$/', $numdoc)) $errors[] = 'Número de documento inválido (5-12 dígitos)';
        if (!$fechaNacimiento) {
            $errors[] = 'Fecha de nacimiento requerida';
        } else {
            $born = new DateTime($fechaNacimiento);
            $now = new DateTime();
            $age = $now->diff($born)->y;
            if ($age < 18) $errors[] = 'Debes ser mayor de 18 años';
        }
        if (!$direccion || mb_strlen($direccion) < 5) $errors[] = 'Dirección requerida (mín. 5 caracteres)';
        if (!$ciudad || mb_strlen($ciudad) < 2) $errors[] = 'Ciudad requerida';

        if (!$password) {
            $errors[] = 'Contraseña requerida';
        } else {
            if (strlen($password) < 8) $errors[] = 'Contraseña: mínimo 8 caracteres';
            if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Contraseña: al menos una mayúscula';
            if (!preg_match('/[a-z]/', $password)) $errors[] = 'Contraseña: al menos una minúscula';
            if (!preg_match('/[0-9]/', $password)) $errors[] = 'Contraseña: al menos un número';
            if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"",.<>?\/\\\\|`~]/', $password)) $errors[] = 'Contraseña: al menos un carácter especial';
        }
        if ($password !== $passwordConfirm) $errors[] = 'Las contraseñas no coinciden';

        if (!$vehiculo || !in_array($vehiculo, ['moto', 'carro', 'bicicleta', 'camioneta'])) $errors[] = 'Tipo de vehículo inválido';
        if (!$placa || !preg_match('/^[A-Z]{3}-?\d{3,4}$/', $placa)) $errors[] = 'Placa inválida (formato: ABC-123)';
        if (!$licencia || mb_strlen($licencia) < 3) $errors[] = 'Número de licencia requerido';
        if (!$catlicencia || !in_array($catlicencia, ['A1', 'A2', 'B1', 'B2', 'B3', 'C1'])) $errors[] = 'Categoría de licencia inválida';

        if (!$tarjeta) $errors[] = 'Tarjeta de propiedad requerida';
        if (!$soatVencimiento) {
            $errors[] = 'Fecha de vencimiento del SOAT requerida';
        } else {
            $vencSoat = new DateTime($soatVencimiento);
            if ($vencSoat <= new DateTime()) $errors[] = 'El SOAT debe estar vigente';
        }
        if (!$tecnomeVencimiento) {
            $errors[] = 'Fecha de vencimiento de la tecnomecánica requerida';
        } else {
            $vencTecno = new DateTime($tecnomeVencimiento);
            if ($vencTecno <= new DateTime()) $errors[] = 'La tecnomecánica debe estar vigente';
        }
        if (!$aceptaTerminos) $errors[] = 'Debes aceptar los Términos y Condiciones';
        if (!$aceptaPrivacidad) $errors[] = 'Debes aceptar la Política de Privacidad';

        // Si hay errores de validación, responde 400 con todos juntos.
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => implode(' | ', $errors)], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Si el correo ya existe e inactivo/eliminado con solicitud rechazada,
        // se reactiva el registro con el mismo usuario (re-registro).
        $existingUser = $this->usuarioModel->findByEmail($email);
        if ($existingUser) {
            $existingEstado = $existingUser['estado'] ?? '';
            if ($existingEstado === 'inactivo' || $existingEstado === 'eliminado') {
                $existingSolicitud = Database::query(
                    "SELECT estado FROM solicitudes_repartidores WHERE usuario_id = ? ORDER BY fecha_solicitud DESC LIMIT 1",
                    [$existingUser['id']]
                )->fetch();
                if ($existingSolicitud && $existingSolicitud['estado'] === 'rechazada') {
                    $userId = $existingUser['id'];
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    try {
                        Database::query(
                            "UPDATE usuarios SET nombre = ?, apellido = ?, password_hash = ?, telefono = ?, tipo_documento = ?, numero_licencia = ?, categoria_licencia = ?, tipo_vehiculo = ?, placa_vehiculo = ?, direccion = ?, ciudad = ?, estado = 'pendiente', motivo_suspension = NULL, fecha_suspension = NULL WHERE id = ?",
                            [$nombre, $apellido, $passwordHash, preg_replace('/\s+/', '', $celular), $tipodoc, $licencia, $catlicencia, $vehiculo, $placa, $direccion, $ciudad, $userId]
                        );
                        Database::query("UPDATE usuarios SET cedula = ?, fecha_nacimiento = ? WHERE id = ?", [$numdoc, $fechaNacimiento ?: null, $userId]);
                        $this->reRegisterFlow($userId, $nombre, $apellido, $email, $celular, $tipodoc, $numdoc, $vehiculo, $placa, $licencia, $catlicencia, $tarjeta, $direccion, $ciudad, $fechaNacimiento, $soatVencimiento, $tecnomeVencimiento);
                    } catch (\Exception $e) {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Error al re-registrar'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    return;
                }
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El correo ya está registrado']);
            exit;
        }

        // Crea el hash bcrypt de la contraseña del repartidor.
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Inserta el usuario con rol 'repartidor' y estado 'pendiente' de aprobación.
        $userId = $this->usuarioModel->create([
            'email' => $email,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'password_hash' => $passwordHash,
            'rol' => 'repartidor',
            'telefono' => preg_replace('/\s+/', '', $celular),
            'tipo_documento' => $tipodoc,
            'numero_licencia' => $licencia,
            'categoria_licencia' => $catlicencia,
            'tipo_vehiculo' => $vehiculo,
            'placa_vehiculo' => $placa,
            'direccion' => $direccion,
            'ciudad' => $ciudad,
            'estado' => 'pendiente',
            'acepta_terminos' => 1,
        ]);

        if (!$userId) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al crear la cuenta']);
            exit;
        }

        // Se intenta guardar cédula y fecha de nacimiento; si falla (duplicado de cédula),
        // se elimina el usuario recién creado y se informa del conflicto.
        try {
            Database::query("UPDATE usuarios SET cedula = ?, fecha_nacimiento = ? WHERE id = ?", [$numdoc, $fechaNacimiento ?: null, $userId]);
        } catch (\Exception $e) {
            Database::query("DELETE FROM usuarios WHERE id = ?", [$userId]);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El número de documento ya está registrado por otro usuario.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $this->ensureSolicitudesTable();
        $solicitudId = null;
        try {
            // Crea la solicitud de repartidor en estado 'pendiente'.
            Database::query(
                "INSERT INTO solicitudes_repartidores (usuario_id, nombres, apellidos, email, telefono, tipo_documento, numero_documento, tipo_vehiculo, placa_vehiculo, numero_licencia, categoria_licencia, numero_tarjeta, direccion, ciudad, fecha_nacimiento, estado, fecha_solicitud) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())",
                [$userId, $nombre, $apellido, $email, preg_replace('/\s+/', '', $celular), $tipodoc, $numdoc, $vehiculo, $placa, $licencia, $catlicencia, $tarjeta, $direccion, $ciudad, $fechaNacimiento ?: null]
            );
            $solicitudId = Database::query("SELECT LAST_INSERT_ID() as id")->fetch()['id'] ?? null;
        } catch (\Exception $e) {
        }

        try {
            // Registra el vehículo del repartidor como activo.
            Database::query(
                "INSERT INTO vehiculos_repartidores (repartidor_id, tipo_vehiculo, placa, activo) VALUES (?, ?, ?, 1)",
                [$userId, $vehiculo, $placa]
            );
        } catch (\Exception $e) {
        }

        try {
            // Deja trazabilidad: historial de la solicitud de registro.
            Database::query(
                "INSERT INTO historial_repartidores (repartidor_id, solicitud_id, accion, estado_nuevo, observaciones, fecha_accion) VALUES (?, ?, 'registro', 'pendiente', 'Solicitud de registro creada', NOW())",
                [$userId, $solicitudId]
            );
        } catch (\Exception $e) {
        }

        // Notifica a cada administrador activo sobre la nueva solicitud.
        $adminUsers = Database::query("SELECT id FROM usuarios WHERE rol = 'administrador' AND estado = 'activo'")->fetchAll();
        foreach ($adminUsers as $admin) {
            try {
                Database::query(
                    "INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, enlace, datos_adicionales, leida, fecha_envio) VALUES (?, 'solicitud_repartidor', 'Nueva solicitud de repartidor', ?, '/admin/repartidores', ?, 0, NOW())",
                    [
                        $admin['id'],
                        $nombre . ' ' . $apellido . ' ha enviado una solicitud para ser repartidor.',
                        json_encode(['solicitud_id' => $solicitudId, 'usuario_id' => $userId])
                    ]
                );
            } catch (\Exception $e) {
            }
        }

        // Carpeta destino de los documentos subidos (se crea si no existe).
        $uploadDir = __DIR__ . '/../../../public/uploads/documentos/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        // Mapa entre el name del input del formulario y el tipo de documento.
        $tiposDocs = [
            'tarjeta-file' => 'tarjeta_propiedad',
            'licencia-file' => 'licencia_conduccion',
            'soat-file' => 'soat',
            'tecnomecanica-file' => 'tecnomecanica',
        ];

        $uploadedFiles = [];
        // Procesa cada archivo opcional: solo los que llegaron sin error de subida.
        foreach ($tiposDocs as $inputName => $tipo) {
            if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
                continue;
            }

            $tmpName = $_FILES[$inputName]['tmp_name'];
            $originalName = $_FILES[$inputName]['name'];
            $fileSize = $_FILES[$inputName]['size'];

            // Límite de tamaño: hasta 10 MB.
            if ($fileSize <= 0 || $fileSize > 10 * 1024 * 1024) {
                continue;
            }

            // Detecta el MIME real del archivo (no confía en lo que manda el navegador).
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = $finfo ? @finfo_file($finfo, $tmpName) : ($_FILES[$inputName]['type'] ?? '');
            if ($finfo) finfo_close($finfo);

            // Solo se aceptan PDF, JPG o PNG.
            $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!in_array($mimeType, $allowedMimes)) {
                continue;
            }

            // Genera un nombre único para evitar colisiones y prevenir paths maliciosos.
            $extMap = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
            $ext = $extMap[$mimeType] ?? pathinfo($originalName, PATHINFO_EXTENSION);
            $filename = $userId . '_' . $tipo . '_' . time() . '.' . $ext;
            $destPath = $uploadDir . $filename;

            if (@move_uploaded_file($tmpName, $destPath)) {
                $relativePath = 'uploads/documentos/' . $filename;

                // SOAT y tecnomecánica guardan su fecha de vencimiento si fue enviada.
                $fechaVenc = null;
                if ($tipo === 'soat' && $soatVencimiento) $fechaVenc = $soatVencimiento;
                if ($tipo === 'tecnomecanica' && $tecnomeVencimiento) $fechaVenc = $tecnomeVencimiento;

                try {
                    // Registra el documento en la BD como 'pendiente' de revisión.
                    if ($fechaVenc) {
                        Database::query(
                            "INSERT INTO documentos (repartidor_id, solicitud_id, tipo, archivo_url, fecha_vencimiento, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')",
                            [$userId, $solicitudId, $tipo, $relativePath, $fechaVenc]
                        );
                    } else {
                        Database::query(
                            "INSERT INTO documentos (repartidor_id, solicitud_id, tipo, archivo_url, estado) VALUES (?, ?, ?, ?, 'pendiente')",
                            [$userId, $solicitudId, $tipo, $relativePath]
                        );
                    }
                } catch (\Exception $e) {
                    // Si la tabla no existe aún, se crea y se reintenta el INSERT.
                    $this->ensureDocumentosTable();
                    if ($fechaVenc) {
                        Database::query(
                            "INSERT INTO documentos (repartidor_id, solicitud_id, tipo, archivo_url, fecha_vencimiento, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')",
                            [$userId, $solicitudId, $tipo, $relativePath, $fechaVenc]
                        );
                    } else {
                        Database::query(
                            "INSERT INTO documentos (repartidor_id, solicitud_id, tipo, archivo_url, estado) VALUES (?, ?, ?, ?, 'pendiente')",
                            [$userId, $solicitudId, $tipo, $relativePath]
                        );
                    }
                }
                $uploadedFiles[] = $tipo;
            }
        }

        $_SESSION['user'] = [
            'id' => $userId,
            'email' => $email,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'rol' => 'repartidor',
            'telefono' => preg_replace('/\s+/', '', $celular),
            'estado' => 'pendiente',
            'tipo_documento' => $tipodoc,
            'numero_documento' => $numdoc,
            'tipo_vehiculo' => $vehiculo,
            'placa_vehiculo' => $placa,
        ];
        $_SESSION['user_id'] = $userId;
        session_regenerate_id(true);

        echo json_encode([
            'success' => true,
            'message' => 'Solicitud enviada. Un administrador revisará tus datos y te aprobará el acceso.',
            'pending' => true,
            'redirect' => '/repartidor/dashboard',
            'files_uploaded' => $uploadedFiles
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Re-registro: reactiva a un repartidor previamente rechazado aprovechando su id.
     * Cancela la solicitud rechazada anterior, crea una nueva 'pendiente', actualiza
     * vehículo, historial, notificaciones y vuelve a subir los documentos.
     */
    private function reRegisterFlow($userId, $nombre, $apellido, $email, $celular, $tipodoc, $numdoc, $vehiculo, $placa, $licencia, $catlicencia, $tarjeta, $direccion, $ciudad, $fechaNacimiento, $soatVencimiento, $tecnomeVencimiento)
    {
        // Invalida la solicitud rechazada anterior para que solo quede la nueva.
        Database::query("UPDATE solicitudes_repartidores SET estado = 'cancelada' WHERE usuario_id = ? AND estado = 'rechazada'", [$userId]);

        // Crea la nueva solicitud de repartidor en estado 'pendiente'.
        Database::query(
            "INSERT INTO solicitudes_repartidores (usuario_id, nombres, apellidos, email, telefono, tipo_documento, numero_documento, tipo_vehiculo, placa_vehiculo, numero_licencia, categoria_licencia, numero_tarjeta, direccion, ciudad, fecha_nacimiento, estado, fecha_solicitud) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())",
            [$userId, $nombre, $apellido, $email, preg_replace('/\s+/', '', $celular), $tipodoc, $numdoc, $vehiculo, $placa, $licencia, $catlicencia, $tarjeta, $direccion, $ciudad, $fechaNacimiento ?: null]
        );
        $solicitudId = Database::query("SELECT LAST_INSERT_ID() as id")->fetch()['id'] ?? null;

        Database::query(
            "INSERT INTO vehiculos_repartidores (repartidor_id, tipo_vehiculo, placa, activo) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE tipo_vehiculo = VALUES(tipo_vehiculo), placa = VALUES(placa), fecha_actualizacion = NOW()",
            [$userId, $vehiculo, $placa]
        );

        Database::query(
            "INSERT INTO historial_repartidores (repartidor_id, solicitud_id, accion, estado_nuevo, observaciones, fecha_accion) VALUES (?, ?, 'registro', 'pendiente', 'Nueva solicitud de registro (re-registro)', NOW())",
            [$userId, $solicitudId]
        );

        $adminUsers = Database::query("SELECT id FROM usuarios WHERE rol = 'administrador' AND estado = 'activo'")->fetchAll();
        foreach ($adminUsers as $admin) {
            try {
                Database::query(
                    "INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, enlace, datos_adicionales, leida, fecha_envio) VALUES (?, 'solicitud_repartidor', 'Nueva solicitud de repartidor', ?, '/admin/repartidores', ?, 0, NOW())",
                    [$admin['id'], $nombre . ' ' . $apellido . ' ha enviado una nueva solicitud para ser repartidor.', json_encode(['solicitud_id' => $solicitudId, 'usuario_id' => $userId])]
                );
            } catch (\Exception $e) {
            }
        }

        $uploadDir = __DIR__ . '/../../../public/uploads/documentos/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);

        $tiposDocs = [
            'tarjeta-file' => 'tarjeta_propiedad',
            'licencia-file' => 'licencia_conduccion',
            'soat-file' => 'soat',
            'tecnomecanica-file' => 'tecnomecanica',
        ];

        $uploadedFiles = [];
        foreach ($tiposDocs as $inputName => $tipo) {
            if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) continue;
            $tmpName = $_FILES[$inputName]['tmp_name'];
            $originalName = $_FILES[$inputName]['name'];
            $fileSize = $_FILES[$inputName]['size'];
            if ($fileSize <= 0 || $fileSize > 10 * 1024 * 1024) continue;

            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = $finfo ? @finfo_file($finfo, $tmpName) : ($_FILES[$inputName]['type'] ?? '');
            if ($finfo) finfo_close($finfo);
            if (!in_array($mimeType, ['application/pdf', 'image/jpeg', 'image/png'])) continue;

            $extMap = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
            $ext = $extMap[$mimeType] ?? pathinfo($originalName, PATHINFO_EXTENSION);
            $filename = $userId . '_' . $tipo . '_' . time() . '.' . $ext;
            $destPath = $uploadDir . $filename;

            if (@move_uploaded_file($tmpName, $destPath)) {
                $relativePath = 'uploads/documentos/' . $filename;
                $fechaVenc = null;
                if ($tipo === 'soat' && $soatVencimiento) $fechaVenc = $soatVencimiento;
                if ($tipo === 'tecnomecanica' && $tecnomeVencimiento) $fechaVenc = $tecnomeVencimiento;
                try {
                    if ($fechaVenc) {
                        Database::query("INSERT INTO documentos (repartidor_id, solicitud_id, tipo, archivo_url, fecha_vencimiento, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')", [$userId, $solicitudId, $tipo, $relativePath, $fechaVenc]);
                    } else {
                        Database::query("INSERT INTO documentos (repartidor_id, solicitud_id, tipo, archivo_url, estado) VALUES (?, ?, ?, ?, 'pendiente')", [$userId, $solicitudId, $tipo, $relativePath]);
                    }
                } catch (\Exception $e) {}
                $uploadedFiles[] = $tipo;
            }
        }

        $_SESSION['user'] = [
            'id' => $userId,
            'email' => $email,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'rol' => 'repartidor',
            'telefono' => preg_replace('/\s+/', '', $celular),
            'estado' => 'pendiente',
            'tipo_documento' => $tipodoc,
            'numero_documento' => $numdoc,
            'tipo_vehiculo' => $vehiculo,
            'placa_vehiculo' => $placa,
        ];
        $_SESSION['user_id'] = $userId;
        session_regenerate_id(true);

        echo json_encode([
            'success' => true,
            'message' => 'Nueva solicitud enviada. Un administrador revisará tus datos.',
            'pending' => true,
            'redirect' => '/repartidor/dashboard',
            'files_uploaded' => $uploadedFiles
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function ensureDocumentosTable()
    {
        // Si la tabla `documentos` no existe, se crea (autocuración de esquema).
        try {
            Database::query("SELECT 1 FROM documentos LIMIT 1");
        } catch (\Exception $e) {
            Database::query("
                CREATE TABLE IF NOT EXISTS documentos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    repartidor_id INT NOT NULL,
                    solicitud_id INT NULL,
                    tipo ENUM('documento_identidad','licencia_conduccion','soat','tarjeta_propiedad','tecnomecanica','foto_vehiculo','otro') NOT NULL,
                    archivo_url VARCHAR(500) NOT NULL,
                    estado ENUM('pendiente','aprobado','rechazado','vencido') DEFAULT 'pendiente',
                    observaciones TEXT NULL,
                    fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
                    fecha_revision DATETIME NULL,
                    fecha_vencimiento DATE NULL,
                    revisado_por INT NULL,
                    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
    }

    private function ensureSolicitudesTable()
    {
        // Si la tabla `solicitudes_repartidores` no existe, se crea.
        try {
            Database::query("SELECT 1 FROM solicitudes_repartidores LIMIT 1");
        } catch (\Exception $e) {
            Database::query("
                CREATE TABLE IF NOT EXISTS solicitudes_repartidores (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    nombres VARCHAR(100) NOT NULL,
                    apellidos VARCHAR(100) NULL,
                    email VARCHAR(255) NOT NULL,
                    telefono VARCHAR(20) NULL,
                    tipo_documento VARCHAR(30) NULL,
                    numero_documento VARCHAR(30) NULL,
                    tipo_vehiculo VARCHAR(50) NULL,
                    placa_vehiculo VARCHAR(20) NULL,
                    numero_licencia VARCHAR(30) NULL,
                    categoria_licencia VARCHAR(10) NULL,
                    numero_tarjeta VARCHAR(30) NULL,
                    direccion VARCHAR(255) NULL,
                    ciudad VARCHAR(100) NULL,
                    fecha_nacimiento DATE NULL,
                    estado ENUM('pendiente','aprobada','rechazada','cancelada') DEFAULT 'pendiente',
                    motivo_rechazo TEXT NULL,
                    administrador_id INT NULL,
                    fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
                    fecha_respuesta DATETIME NULL,
                    fecha_actualizacion DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    observaciones TEXT NULL,
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                    FOREIGN KEY (administrador_id) REFERENCES usuarios(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
    }

    /** Cierra la sesión del repartidor: limpia la sesión y responde para redirigir al login. */
    public function logout()
    {
        $_SESSION = [];
        session_destroy();
        $this->jsonResponse(['success' => true, 'redirect' => '/repartidor/login']);
    }

    /** Devuelve los datos del repartidor en sesión (endpoint de perfil para la app). */
    public function me()
    {
        // Solo repartidores con sesión activa pueden consultar sus datos.
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'repartidor') {
            $this->jsonResponse(['success' => false, 'message' => 'No autorizado']);
        }

        $this->jsonResponse([
            'success' => true,
            'user' => $_SESSION['user']
        ]);
    }

    /** Renueva el token JWT del repartidor cuando está por vencer (24 h). */
    public function refreshToken()
    {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'repartidor') {
            $this->jsonResponse(['success' => false, 'message' => 'No autorizado'], 401);
        }

        // Recarga al usuario de la BD para validar que sigue activo antes de renovar el token.
        $user = Database::query("SELECT * FROM usuarios WHERE id = ?", [$_SESSION['user']['id']])->fetch();

        if (!$user || ($user['estado'] ?? '') !== 'activo') {
            $this->jsonResponse(['success' => false, 'message' => 'Tu cuenta no está activa'], 403);
        }

        // Emite un JWT nuevo con vigencia de 24 horas.
        $token = JWTHelper::encode([
            'sub' => $user['id'],
            'email' => $user['email'],
            'rol' => 'repartidor',
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60),
        ]);

        $this->jsonResponse(['success' => true, 'token' => $token]);
    }
}
