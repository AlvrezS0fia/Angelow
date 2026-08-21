<?php
namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\JWTHelper;

class RepartidorDocumentosController
{
    private function getRepartidorId()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);
        if (!$token) return null;
        $payload = JWTHelper::decode($token);
        if (!$payload) return null;
        $userId = $payload['sub'] ?? null;
        if (!$userId) return null;
        $user = Database::query("SELECT id, rol, estado FROM usuarios WHERE id = ?", [$userId])->fetch();
        if (!$user || $user['rol'] !== 'repartidor' || $user['estado'] !== 'activo') return null;
        return $userId;
    }

    private function json($data, $code = 200)
    {
        if (ob_get_length()) ob_clean();
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function index($repartidorId = null)
    {
        $authRepartidorId = $this->getRepartidorId();
        if (!$authRepartidorId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        $id = $repartidorId ?: $authRepartidorId;

        $documentos = Database::query(
            "SELECT * FROM documentos WHERE repartidor_id = ? ORDER BY fecha_subida DESC",
            [$id]
        )->fetchAll();

        $result = array_map(function ($d) {
            return [
                'id' => $d['id'],
                'tipo' => $d['tipo'],
                'estado' => $d['estado'] ?? 'pendiente',
                'fecha_subida' => $d['fecha_subida'],
                'observaciones' => $d['observaciones'] ?? '',
            ];
        }, $documentos);

        $this->json(['success' => true, 'documentos' => $result]);
    }

    public function subir()
    {
        $repartidorId = $this->getRepartidorId();
        if (!$repartidorId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        $tipo = $_POST['tipo'] ?? '';

        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Archivo requerido'], 400);
            return;
        }

        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $fileType = $_FILES['archivo']['type'];
        if (!in_array($fileType, $allowedTypes)) {
            $this->json(['error' => 'Solo PDF, JPG y PNG'], 400);
            return;
        }

        $uploadDir = __DIR__ . '/../../../public/uploads/documentos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION);
        $filename = $repartidorId . '_' . $tipo . '_' . time() . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $destPath)) {
            $this->json(['error' => 'Error al subir archivo'], 500);
            return;
        }

        $this->ensureDocumentosTable();

        Database::query(
            "INSERT INTO documentos (repartidor_id, tipo, archivo_url, estado) VALUES (?, ?, ?, 'pendiente')",
            [$repartidorId, $tipo, 'uploads/documentos/' . $filename]
        );

        $this->json(['success' => true, 'message' => 'Documento subido correctamente']);
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
}
