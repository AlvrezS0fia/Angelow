<?php

// crea la conexión PDO a la base de datos y la devuelve (singleton) 
namespace App\Core;

namespace App\Core;

use PDO;
use PDOException;

/**
 * ============================================================
 * ARCHIVO: Database.php — MÓDULO: Conexión a base de datos
 * ============================================================
 * QUÉ HACE: Singleton que crea y devuelve una única conexión PDO a MySQL.
 *           Configura modo de errores exception y fetch assoc por defecto.
 *           Expone query() estático para consultas parametrizadas directas.
 * TABLA(S): Todas (capa de acceso a datos global)
 * QUIÉN LO USA: Todos los modelos y clases que necesitan BD.
 */
class Database {
    // ENCAPSULAMIENTO - patrón Singleton: $instance y $conn son privados y
    // el constructor es privado → nadie instancia ni toca la conexión a mano;
    // siempre se accede vía Database::getInstance() (abstracción de PDO).
    private static ?Database $instance = null;
    private PDO $conn;

    // --- CONSTRUCTOR PRIVADO (patrón Singleton) ---
    // Entrada: constantes DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET
    //          (definidas en config/database.php a partir de .env).
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