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
        if ($token) {
            $payload = JWTHelper::decode($token);
            if ($payload && isset($payload['sub'])) {
                return $payload['sub'];
            }
        }
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['user']) && ($_SESSION['user']['rol'] ?? '') === 'repartidor') {
            return $_SESSION['user']['id'];
        }
        return null;
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

        // Un repartidor solo puede consultar SUS propios documentos (evita IDOR
        // al enumerar otros repartidores). Si el rol es administrador, puede ver
        // cualquier documento indicado en la URL.
        if ($repartidorId && $repartidorId != $authRepartidorId) {
            $esAdmin = isset($_SESSION['user']['rol']) && $_SESSION['user']['rol'] === 'administrador';
            if (!$esAdmin) {
                $this->json(['error' => 'No autorizado'], 403);
                return;
            }
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

        // Whitelist de tipos de documento permitidos para evitar path traversal
        // y almacenamiento de archivos con nombre arbitrario.
        $tiposPermitidos = [
            'cedula',
            'licencia_conduccion',
            'tarjeta_profesional',
            'tarjeta_propiedad',
            'soat',
            'tecnomecanica',
        ];
        $tipo = $_POST['tipo'] ?? '';
        if (!in_array($tipo, $tiposPermitidos, true) || $tipo === '') {
            $this->json(['error' => 'Tipo de documento no válido'], 400);
            return;
        }

        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Archivo requerido'], 400);
            return;
        }

        // Límite de tamaño (10 MB)
        $maxBytes = 10 * 1024 * 1024;
        if ($_FILES['archivo']['size'] > $maxBytes) {
            $this->json(['error' => 'El archivo no debe superar 10 MB'], 400);
            return;
        }

        // Validación del contenido real mediante finfo (no confiar en el tipo
        // enviado por el cliente, que puede ser falsificado).
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($_FILES['archivo']['tmp_name']);
        $mimeMap = [
            'application/pdf' => 'pdf',
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
        ];
        if (!isset($mimeMap[$detectedMime])) {
            $this->json(['error' => 'Solo PDF, JPG y PNG'], 400);
            return;
        }
        $ext = $mimeMap[$detectedMime];

        $uploadDir = __DIR__ . '/../../../uploads/documentos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Nombre de archivo aleatorio (no adivinable): id_repartidor_tipo_hash.ext
        $random = bin2hex(random_bytes(8));
        $filename = $repartidorId . '_' . $tipo . '_' . $random . '.' . $ext;
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
