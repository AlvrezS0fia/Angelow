<?php
namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\RateLimiter;
use App\Models\UsuarioModel;

// MICROSERVICIO DE REGISTRO DE REPARTIDOR
// ----------------------------------------------------------------------------
// Capa API del flujo de registro de repartidores. Recibe en un SOLO POST
// (multipart/form-data) los pasos del formulario: datos personales, vehículo
// y documentos (SOAT, licencia, tarjeta de propiedad, tecnomecánica), tal y
// como los envía la vista app/Views/repartidor/registro_repartidor.php.
//
// Endpoints:
//   POST /api/repartidor/registro   → crear la solicitud + subir documentos.
//   GET  /api/repartidor/estado     → consultar estado de la solicitud por email.
//
// Seguridad aplicada:
//   - Validación backend de TODOS los campos (nunca confiar solo en el front).
//   - Rate limiting por IP+email (RateLimiter::tooMany).
//   - Archivos validados por contenido real (finfo) + tamaño + whitelist,
//     guardados en public/uploads/documentos/ con nombre aleatorio.
//   - Errores genéricos al cliente; detalle técnico solo vía error_log.
//   - CSRF entre sitios cubierto globalmente en public/index.php (Origin/Referer).
class RepartidorRegistroController
{
    private const TIPOS_DOCUMENTOS_VALIDOS = ['CC', 'CE', 'TI', 'PAS'];
    private const TIPOS_DOCUMENTOS_DB_MAP = [
        'CC' => 'CC',
        'CE' => 'CE',
        'TI' => 'OTRO',
        'PAS' => 'PASAPORTE',
    ];
    private const TIPOS_VEHICULO_VALIDOS = ['moto', 'carro', 'bicicleta', 'camioneta'];
    private const CATEGORIAS_LICENCIA_VALIDAS = ['A1', 'A2', 'B1', 'B2', 'B3', 'C1'];
    private const MAX_BYTES_DOCUMENTO = 10 * 1024 * 1024;
    private const MIME_MAP = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
    ];

    private UsuarioModel $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
    }

    private function json(array $data, int $code = 200): void
    {
        if (ob_get_length()) ob_clean();
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // POST /api/repartidor/registro
    // Recibe multipart/form-data con los campos del formulario y los 4 archivos.
    public function registro()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
        }

        $email = trim($_POST['correo'] ?? '');
        $rlKey = 'registro_repartidor:' . ($_SERVER['REMOTE_ADDR'] ?? '') . ':' . strtolower($email);
        if (RateLimiter::tooMany($rlKey, 5, 900)) {
            $this->json(['success' => false, 'message' => 'Demasiados intentos de registro. Espera 15 minutos.'], 429);
        }

        $nombre = trim($_POST['nombres'] ?? '');
        $apellido = trim($_POST['apellidos'] ?? '');
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

        // --- VALIDACIONES BACKEND (paso 0: detección temprana de errores) ---
        $errors = $this->validate($nombre, $apellido, $email, $celular, $tipodoc, $numdoc,
            $fechaNacimiento, $direccion, $ciudad, $password, $passwordConfirm,
            $vehiculo, $placa, $licencia, $catlicencia, $tarjeta,
            $soatVencimiento, $tecnomeVencimiento, $aceptaTerminos, $aceptaPrivacidad);

        // --- VALIDACIÓN DE ARCHIVOS ---
        $tiposDocs = [
            'tarjeta-file' => 'tarjeta_propiedad',
            'licencia-file' => 'licencia_conduccion',
            'soat-file' => 'soat',
            'tecnomecanica-file' => 'tecnomecanica',
        ];
        $archivosValidos = [];
        foreach ($tiposDocs as $inputName => $tipo) {
            $archivo = $_FILES[$inputName] ?? null;
            if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $errors[] = "Debes adjuntar el documento de {$tipo}";
                continue;
            }
            if ($archivo['size'] <= 0 || $archivo['size'] > self::MAX_BYTES_DOCUMENTO) {
                $errors[] = "El documento de {$tipo} no debe superar 10 MB";
                continue;
            }
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? @finfo_file($finfo, $archivo['tmp_name']) : '';
            if ($finfo) finfo_close($finfo);
            if (!isset(self::MIME_MAP[$mime])) {
                $errors[] = "El documento de {$tipo} debe ser PDF, JPG o PNG";
                continue;
            }
            $archivosValidos[$inputName] = ['tipo' => $tipo, 'ext' => self::MIME_MAP[$mime]];
        }

        if (!empty($errors)) {
            $this->json(['success' => false, 'message' => implode(' | ', $errors)], 400);
        }

        // --- USUARIO Y SOLICITUD ---
        try {
            [$userId, $solicitudId] = $this->crearSolicitud(
                $nombre, $apellido, $email, $password, $celular, $tipodoc, $numdoc,
                $fechaNacimiento, $direccion, $ciudad, $vehiculo, $placa,
                $licencia, $catlicencia, $tarjeta
            );
        } catch (\Exception $e) {
            error_log('[RepartidorRegistro] Error al crear solicitud: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Error al crear la solicitud. Intenta de nuevo.'], 500);
        }

        // --- SUBIR DOCUMENTOS ---
        $uploadedFiles = [];
        foreach ($archivosValidos as $inputName => $info) {
            try {
                $documentoId = $this->guardarDocumento($userId, $solicitudId, $info['tipo'], $info['ext'],
                    $_FILES[$inputName],
                    $inputName === 'soat-file' ? $soatVencimiento
                        : ($inputName === 'tecnomecanica-file' ? $tecnomeVencimiento : null),
                    $inputName === 'tarjeta-file' ? $tarjeta : null);
                if ($documentoId) {
                    $uploadedFiles[] = $info['tipo'];
                }
            } catch (\Exception $e) {
                error_log('[RepartidorRegistro] Error al guardar doc ' . $info['tipo'] . ': ' . $e->getMessage());
            }
        }

        $this->notificarAdministradores($nombre . ' ' . $apellido, $solicitudId, $userId);

        // Misma experiencia que el registro web: sesión con estado 'pendiente'
        // para que el redirect a /repartidor/dashboard muestre "solicitud en revisión".
        if (session_status() === PHP_SESSION_NONE) session_start();
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

        $this->json([
            'success' => true,
            'message' => 'Solicitud enviada. Un administrador revisará tus datos y te aprobará el acceso.',
            'pending' => true,
            'redirect' => '/repartidor/dashboard',
            'files_uploaded' => $uploadedFiles,
        ]);
    }

    // GET /api/repartidor/estado?correo=...
    // Consulta el estado de la solicitud de repartidor. Devuelve solo datos
    // públicos de la solicitud (no datos del usuario ni del documento).
    public function estado()
    {
        $email = strtolower(trim($_GET['correo'] ?? ''));
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Correo inválido'], 400);
        }

        $rlKey = 'estado_repartidor:' . ($_SERVER['REMOTE_ADDR'] ?? '');
        if (RateLimiter::tooMany($rlKey, 20, 900)) {
            $this->json(['success' => false, 'message' => 'Demasiadas consultas. Espera 15 minutos.'], 429);
        }

        $solicitud = Database::query(
            "SELECT id, estado, motivo_rechazo, observaciones, fecha_solicitud, fecha_respuesta
             FROM solicitudes_repartidores
             WHERE LOWER(email) = LOWER(?)
             ORDER BY fecha_solicitud DESC
             LIMIT 1",
            [$email]
        )->fetch();

        if (!$solicitud) {
            $this->json(['success' => true, 'solicitud' => null, 'message' => 'No se encontró una solicitud para este correo.']);
        }

        $this->json([
            'success' => true,
            'solicitud' => [
                'estado' => $solicitud['estado'],
                'motivo' => $solicitud['motivo_rechazo'] ?? $solicitud['observaciones'] ?? '',
                'fecha_solicitud' => $solicitud['fecha_solicitud'],
                'fecha_respuesta' => $solicitud['fecha_respuesta'],
            ],
        ]);
    }

    // --- VALIDACIONES (mismas reglas que el registro web) ---
    private function validate(
        string $nombre, string $apellido, string $email, string $celular,
        string $tipodoc, string $numdoc, string $fechaNacimiento,
        string $direccion, string $ciudad, string $password, string $passwordConfirm,
        string $vehiculo, string $placa, string $licencia, string $catlicencia, string $tarjeta,
        string $soatVencimiento, string $tecnomeVencimiento,
        int $aceptaTerminos, int $aceptaPrivacidad
    ): array {
        $errors = [];

        if (!$nombre || mb_strlen($nombre) < 2) $errors[] = 'Nombres requeridos (mín. 2 caracteres)';
        if (!preg_match('/^[a-zA-ZáéíóúñÁÉÍÓÚÑ\s]+$/', $nombre)) $errors[] = 'Nombres solo pueden contener letras';
        if (!$apellido || mb_strlen($apellido) < 2) $errors[] = 'Apellidos requeridos (mín. 2 caracteres)';
        if (!preg_match('/^[a-zA-ZáéíóúñÁÉÍÓÚÑ\s]+$/', $apellido)) $errors[] = 'Apellidos solo pueden contener letras';
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Correo electrónico inválido';
        if (!$celular || !preg_match('/^3\d{9}$/', preg_replace('/\s+/', '', $celular))) $errors[] = 'Celular inválido (debe iniciar en 3 y tener 10 dígitos)';
        if (!$tipodoc || !in_array($tipodoc, self::TIPOS_DOCUMENTOS_VALIDOS, true)) $errors[] = 'Tipo de documento inválido';
        if (!$numdoc || !preg_match('/^\d{5,12}$/', $numdoc)) $errors[] = 'Número de documento inválido (5-12 dígitos)';
        if (!$fechaNacimiento) {
            $errors[] = 'Fecha de nacimiento requerida';
        } else {
            try {
                $born = new \DateTime($fechaNacimiento);
                if ($born->diff(new \DateTime())->y < 18) $errors[] = 'Debes ser mayor de 18 años';
            } catch (\Exception $e) {
                $errors[] = 'Fecha de nacimiento inválida';
            }
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

        if (!$vehiculo || !in_array($vehiculo, self::TIPOS_VEHICULO_VALIDOS, true)) $errors[] = 'Tipo de vehículo inválido';
        if (!$placa || !preg_match('/^[A-Z]{3}-?\d{3,4}$/', $placa)) $errors[] = 'Placa inválida (formato: ABC-123)';
        if (!$licencia || mb_strlen($licencia) < 3) $errors[] = 'Número de licencia requerido';
        if (!$catlicencia || !in_array($catlicencia, self::CATEGORIAS_LICENCIA_VALIDAS, true)) $errors[] = 'Categoría de licencia inválida';

        if (!$tarjeta) $errors[] = 'Tarjeta de propiedad requerida';
        if (!$soatVencimiento) {
            $errors[] = 'Fecha de vencimiento del SOAT requerida';
        } else {
            try {
                if (new \DateTime($soatVencimiento) <= new \DateTime()) $errors[] = 'El SOAT debe estar vigente';
            } catch (\Exception $e) {
                $errors[] = 'Fecha de vencimiento del SOAT inválida';
            }
        }
        if (!$tecnomeVencimiento) {
            $errors[] = 'Fecha de vencimiento de la tecnomecánica requerida';
        } else {
            try {
                if (new \DateTime($tecnomeVencimiento) <= new \DateTime()) $errors[] = 'La tecnomecánica debe estar vigente';
            } catch (\Exception $e) {
                $errors[] = 'Fecha de vencimiento de la tecnomecánica inválida';
            }
        }
        if (!$aceptaTerminos) $errors[] = 'Debes aceptar los Términos y Condiciones';
        if (!$aceptaPrivacidad) $errors[] = 'Debes aceptar la Política de Privacidad';

        return $errors;
    }

    // --- CREAR/REUTILIZAR CUENTA + SOLICITUD + VEHÍCULO + HISTORIAL ---
    /** @return array{0:int,1:int|null} [userId, solicitudId] */
    private function crearSolicitud(
        string $nombre, string $apellido, string $email, string $password,
        string $celular, string $tipodoc, string $numdoc, string $fechaNacimiento,
        string $direccion, string $ciudad, string $vehiculo, string $placa,
        string $licencia, string $catlicencia, string $tarjeta
    ): array {
        $tipodoc = self::TIPOS_DOCUMENTOS_DB_MAP[$tipodoc] ?? $tipodoc;
        $existingUser = $this->usuarioModel->findByEmail($email);
        $existingEstado = $existingUser['estado'] ?? '';

        // Re-registro: usuario inactivo/eliminado con solicitud previa rechazada.
        $esReRegistro = false;
        if ($existingUser && ($existingEstado === 'inactivo' || $existingEstado === 'eliminado')) {
            $solicitudAnterior = Database::query(
                "SELECT estado FROM solicitudes_repartidores WHERE usuario_id = ? ORDER BY fecha_solicitud DESC LIMIT 1",
                [$existingUser['id']]
            )->fetch();
            if ($solicitudAnterior && $solicitudAnterior['estado'] === 'rechazada') {
                $esReRegistro = true;
            }
        }

        if ($existingUser && !$esReRegistro) {
            throw new \RuntimeException('El correo ya está registrado');
        }

        $celularLimpio = preg_replace('/\s+/', '', $celular);
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if ($esReRegistro) {
            $userId = (int) $existingUser['id'];
            Database::query(
                "UPDATE usuarios SET nombre = ?, apellido = ?, password_hash = ?, telefono = ?,
                 tipo_documento = ?, tipo_vehiculo = ?,
                 placa_vehiculo = ?, direccion = ?, ciudad = ?, estado = 'pendiente',
                 motivo_suspension = NULL, fecha_suspension = NULL WHERE id = ?",
                [$nombre, $apellido, $passwordHash, $celularLimpio, $tipodoc,
                 $vehiculo, $placa, $direccion, $ciudad, $userId]
            );
            Database::query("UPDATE usuarios SET cedula = ?, fecha_nacimiento = ? WHERE id = ?", [$numdoc, $fechaNacimiento ?: null, $userId]);
            Database::query("UPDATE solicitudes_repartidores SET estado = 'cancelada' WHERE usuario_id = ? AND estado = 'rechazada'", [$userId]);
        } else {
            $userId = $this->usuarioModel->create([
                'email' => $email,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'password_hash' => $passwordHash,
                'rol' => 'repartidor',
                'telefono' => $celularLimpio,
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
                throw new \RuntimeException('No se pudo crear el usuario');
            }
            try {
                Database::query("UPDATE usuarios SET cedula = ?, fecha_nacimiento = ? WHERE id = ?", [$numdoc, $fechaNacimiento ?: null, $userId]);
            } catch (\Exception $e) {
                Database::query("DELETE FROM usuarios WHERE id = ?", [$userId]);
                throw new \RuntimeException('El número de documento ya está registrado por otro usuario.');
            }
        }

        Database::query(
            "INSERT INTO solicitudes_repartidores (usuario_id, nombres, apellidos, email, telefono,
             tipo_documento, numero_documento, tipo_vehiculo, placa_vehiculo, numero_licencia,
             categoria_licencia, direccion, ciudad, fecha_nacimiento, estado, fecha_solicitud)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())",
            [$userId, $nombre, $apellido, $email, $celularLimpio, $tipodoc, $numdoc, $vehiculo,
             $placa, $licencia, $catlicencia, $direccion, $ciudad, $fechaNacimiento ?: null]
        );
        $solicitudId = Database::query("SELECT LAST_INSERT_ID() as id")->fetch()['id'] ?? null;

        try {
            if ($esReRegistro) {
                Database::query(
                    "INSERT INTO vehiculos_repartidores (repartidor_id, tipo_vehiculo, placa, activo)
                     VALUES (?, ?, ?, 1)
                     ON DUPLICATE KEY UPDATE tipo_vehiculo = VALUES(tipo_vehiculo), placa = VALUES(placa), fecha_actualizacion = NOW()",
                    [$userId, $vehiculo, $placa]
                );
            } else {
                Database::query(
                    "INSERT INTO vehiculos_repartidores (repartidor_id, tipo_vehiculo, placa, activo) VALUES (?, ?, ?, 1)",
                    [$userId, $vehiculo, $placa]
                );
            }
        } catch (\Exception $e) {
            error_log('[RepartidorRegistro] vehiculo: ' . $e->getMessage());
        }

        try {
            Database::query(
                "INSERT INTO historial_repartidores (repartidor_id, solicitud_id, accion, estado_nuevo, observaciones, fecha_accion)
                 VALUES (?, ?, 'registro', 'pendiente', 'Solicitud de registro creada', NOW())",
                [$userId, $solicitudId]
            );
        } catch (\Exception $e) {
            error_log('[RepartidorRegistro] historial: ' . $e->getMessage());
        }

        return [$userId, $solicitudId];
    }

    // --- GUARDAR UN DOCUMENTO EN public/uploads/documentos/ ---
    /** @return int|null id del documento insertado */
    private function guardarDocumento(int $userId, ?int $solicitudId, string $tipo, string $ext,
                                       array $archivo, ?string $fechaVencimiento,
                                       ?string $numeroDocumento = null): ?int
    {
        $uploadDir = __DIR__ . '/../../../public/uploads/documentos/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $random = bin2hex(random_bytes(8));
        $filename = $userId . '_' . $tipo . '_' . $random . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (!move_uploaded_file($archivo['tmp_name'], $destPath)) {
            throw new \RuntimeException('move_uploaded_file falló para ' . $tipo);
        }

        $relativePath = 'uploads/documentos/' . $filename;
        if ($fechaVencimiento) {
            Database::query(
                "INSERT INTO documentos (repartidor_id, solicitud_id, tipo, archivo_url, numero_documento, fecha_vencimiento, estado)
                 VALUES (?, ?, ?, ?, ?, ?, 'pendiente')",
                [$userId, $solicitudId, $tipo, $relativePath, $numeroDocumento, $fechaVencimiento]
            );
        } else {
            Database::query(
                "INSERT INTO documentos (repartidor_id, solicitud_id, tipo, archivo_url, numero_documento, estado)
                 VALUES (?, ?, ?, ?, ?, 'pendiente')",
                [$userId, $solicitudId, $tipo, $relativePath, $numeroDocumento]
            );
        }

        return Database::query("SELECT LAST_INSERT_ID() as id")->fetch()['id'] ?? null;
    }

    private function notificarAdministradores(string $nombreCompleto, ?int $solicitudId, int $userId): void
    {
        $admins = Database::query("SELECT id FROM usuarios WHERE rol = 'administrador' AND estado = 'activo'")->fetchAll();
        foreach ($admins as $admin) {
            try {
                Database::query(
                    "INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, enlace, datos_adicionales, leida, fecha_envio)
                     VALUES (?, 'solicitud_repartidor', 'Nueva solicitud de repartidor', ?, '/admin/repartidores', ?, 0, NOW())",
                    [
                        $admin['id'],
                        $nombreCompleto . ' ha enviado una solicitud para ser repartidor.',
                        json_encode(['solicitud_id' => $solicitudId, 'usuario_id' => $userId]),
                    ]
                );
            } catch (\Exception $e) {
                error_log('[RepartidorRegistro] notificación admin: ' . $e->getMessage());
            }
        }
    }
}