<?php
/**
 * ============================================================
 * ARCHIVO: db.php — MÓDULO: Conexión a la base de datos (legacy)
 * ============================================================
 * QUÉ HACE: Crea la conexión PDO a MySQL usando variables de entorno con
 *   valores por defecto (localhost / angelow_db / root) y define funciones
 *   auxiliares de contraseña y tokens usadas por los scripts legacy.
 * MODELO(S) QUE USA: ninguno (usa PDO directo)
 * ENDPOINTS/RUTAS: no aplica (es un include requerido por los scripts legacy)
 * QUIÉN LO CONSUME: procesar/login.php, procesar/registrar.php,
 *   procesar/recuperar_password.php y ProcesarGoogleController.
 *
 * NOTA: usa $_ENV (requiere variables de entorno reales); si no están
 *   definidas, usa los valores por defecto. NO se corrige: solo se documenta.
 */
// procesar/db.php
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'angelow_db';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

try {
    // Conexión PDO a MySQL con charset utf8mb4.
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Excepciones en errores y resultado de consultas como array asociativo.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Registra el error y corta con un JSON genérico (no expone detalles).
    error_log('[Procesar/db] Error de conexión: ' . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'Error de conexión con la base de datos']));
}

// Función para generar hash seguro de contraseña
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Función para verificar contraseña
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Función para generar token único
function generateToken($length = 60) {
    return bin2hex(random_bytes($length));
}
?>