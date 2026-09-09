<?php
namespace App\Core;

use PDO;
use PDOException;

class Database {
    // ENCAPSULAMIENTO - patrón Singleton: $instance y $conn son privados y
    // el constructor es privado → nadie instancia ni toca la conexión a mano;
    // siempre se accede vía Database::getInstance() (abstracción de PDO).
    private static ?Database $instance = null;
    private PDO $conn;

    // --- CONSTRUCTOR PRIVADO (patrón Singleton) ---
    // Entrada: constantes DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET
    //          (definidas en config/database.php a partir de .env).
    // Procesamiento: crea el DSN mysql, activa ERRMODE_EXCEPTION (los errores
    //          SQL lanzan excepciones) y FETCH_ASSOC (arrays asociativos).
    // Salida: una única conexión PDO compartida por toda la app.
    private function __construct() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $this->conn = new PDO($dsn, DB_USER, DB_PASS);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[Database] Error de conexión: ' . $e->getMessage());
            die('Error de conexión con la base de datos. Intenta más tarde.');
        }
    }

    // --- ACCESO AL SINGLETON ---
    // Si aún no existe conexión, la crea; si ya existe, la reutiliza.
    public static function getInstance(): Database {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->conn;
    }

    // --- MÉTODO QUERY (estático para compatibilidad) ---
    // Entrada: SQL con marcadores `?` o `:nombre` + array de parámetros.
    // Procesamiento: PDO::prepare + execute → SIEMPRE sentencias preparadas,
    //          lo que neutraliza la inyección SQL (los valores jamás se
    //          concatenan en el SQL).
    // Salida: PDOStatement listo para fetch()/fetchAll().
    // Ejemplo real: Database::query("SELECT * FROM documentos WHERE repartidor_id = ?", [$id])
    /** @return \PDOStatement */
    public static function query(string $sql, array $params = []) {
        $db = self::getInstance();
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}