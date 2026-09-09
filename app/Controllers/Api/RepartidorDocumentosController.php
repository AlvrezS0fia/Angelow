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
                'url' => APP_URL . '/api/documentos/' . $d['id'] . '/archivo',
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
        // CAPA 7 ISO-OSI (Aplicación): whitelist de tipo -> evita path traversal / archivos arbitrarios.
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

    // --- ID RESUELTO (sesión o JWT), sin lanzar sesión si no hay token ---
    /** @return int|null */
    private function id()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);
        if ($token) {
            $payload = JWTHelper::decode($token);
            if ($payload && isset($payload['sub'])) {
                return (int) $payload['sub'];
            }
        }
        if (!empty($_SESSION['user']['id'])) {
            return (int) $_SESSION['user']['id'];
        }
        return null;
    }

    // GET /api/documentos/{id}/archivo → Sirve un documento autenticado.
    //   Autenticación: sesión PHP o JWT válido.
    //   Autorización: solo admin puede leer documentos de cualquiera; el resto
    //     solo los suyos (IDOR).
    //   Sirve el BINARIO real (no pública) desde uploads/documentos/ o
    //   public/uploads/documentos/, con Content-Type estricto y nosniff.
    public function archivo($id)
    {
        $authId = $this->id();
        if (!$authId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }
        $id = (int) $id;
        if ($id <= 0) {
            $this->json(['error' => 'Documento no encontrado'], 404);
            return;
        }

        $doc = Database::query("SELECT * FROM documentos WHERE id = ?", [$id])->fetch();
        if (!$doc) {
            $this->json(['error' => 'Documento no encontrado'], 404);
            return;
        }

        $esAdmin = isset($_SESSION['user']['rol']) && $_SESSION['user']['rol'] === 'administrador';
        if (!$esAdmin && (int) $doc['repartidor_id'] !== $authId) {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }

        $rel = ltrim((string) ($doc['archivo_url'] ?? ''), '/');
        if ($rel === '') {
            $this->json(['error' => 'Documento sin archivo'], 404);
            return;
        }

        // El registro apunta a uploads/documentos/... (sin public). Se buscan
        // ambas rutas reales para servir también documentos de flujos previos.
        $base = dirname(__DIR__, 3); // raíz del proyecto
        $candidatos = [
            $base . '/' . $rel,
            $base . '/public/' . $rel,
        ];
        $path = null;
        foreach ($candidatos as $cand) {
            $norm = str_replace('\\', '/', $cand);
            $real = realpath($norm);
            if ($real !== false && is_file($real)) {
                $path = $real;
                break;
            }
        }
        if ($path === null) {
            $this->json(['error' => 'Archivo no encontrado'], 404);
            return;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeByExt = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
        ];
        $mime = $mimeByExt[$ext] ?? 'application/octet-stream';
        $nombre = 'documento_' . $doc['tipo'] . '.' . ($ext ?: 'file');

        if (ob_get_level()) {
            while (ob_get_level()) {
                ob_end_clean();
            }
        }
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="' . $nombre . '"');
        header('Content-Length: ' . (string) filesize($path));
        // X-Sendfile / X-Accel-Redirect: si Apache XSendfile está activo lo
        // delega; si no, lee el archivo (los docs son <10MB → seguro).
        header('X-Sendfile: ' . $path);
        readfile($path);
        exit;
    }
}
