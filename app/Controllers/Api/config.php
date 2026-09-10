<?php
/**
 * ============================================================
 * ARCHIVO: config.php — MÓDULO: Configuración de BD (script legacy)
 * ============================================================
 * QUÉ HACE: Establece la conexión a MySQL usando mysqli. Variables
 *   leídas de entorno $_ENV con fallbacks por defecto. Define $conn.
 *   Los scripts legacy categories.php y products.php lo referencian
 *   como $pdo, aunque aquí se crea $conn (posible desajuste).
 * MODELO(S) QUE USA: Ninguno — conexión directa mysqli.
 * QUIÉN LO CONSUME: categories.php, products.php (scripts legacy).
 */
header('Content-Type: application/json');
$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';
$database = $_ENV['DB_NAME'] ?? 'angelow_db';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Error de conexión: ' . $conn->connect_error]));
}
$conn->set_charset('utf8mb4');
?>