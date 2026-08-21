<?php
namespace App\Models;

use App\Core\Database;

class DireccionModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByUsuario($usuarioId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM direcciones WHERE usuario_id = :uid ORDER BY es_predeterminada DESC, fecha_creacion DESC"
        );
        $stmt->execute(['uid' => (int)$usuarioId]);
        return $stmt->fetchAll();
    }

    public function getById($id, $usuarioId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM direcciones WHERE id = :id AND usuario_id = :uid"
        );
        $stmt->execute(['id' => (int)$id, 'uid' => (int)$usuarioId]);
        return $stmt->fetch();
    }

    public function crear($usuarioId, $data) {
        if (!empty($data['es_predeterminada'])) {
            $this->clearPredeterminada($usuarioId);
        }

        $stmt = $this->db->prepare(
            "INSERT INTO direcciones (usuario_id, titulo, pais, departamento, municipio, calle, info_adicional, barrio, destinatario, codigo_postal, es_predeterminada)
             VALUES (:uid, :titulo, :pais, :dep, :mun, :calle, :info, :barrio, :dest, :cp, :pred)"
        );

        $result = $stmt->execute([
            'uid'    => (int)$usuarioId,
            'titulo' => $data['titulo'] ?? 'Casa',
            'pais'   => $data['pais'] ?? 'Colombia',
            'dep'    => $data['departamento'],
            'mun'    => $data['municipio'],
            'calle'  => $data['calle'],
            'info'   => $data['info_adicional'] ?? null,
            'barrio' => $data['barrio'],
            'dest'   => $data['destinatario'],
            'cp'     => $data['codigo_postal'] ?? '05001',
            'pred'   => !empty($data['es_predeterminada']) ? 1 : 0,
        ]);

        return $result ? (int)$this->db->lastInsertId() : false;
    }

    public function actualizar($id, $usuarioId, $data) {
        $existing = $this->getById($id, $usuarioId);
        if (!$existing) return false;

        if (!empty($data['es_predeterminada'])) {
            $this->clearPredeterminada($usuarioId);
        }

        $stmt = $this->db->prepare(
            "UPDATE direcciones SET
                titulo = :titulo, pais = :pais, departamento = :dep, municipio = :mun,
                calle = :calle, info_adicional = :info, barrio = :barrio,
                destinatario = :dest, codigo_postal = :cp, es_predeterminada = :pred
             WHERE id = :id AND usuario_id = :uid"
        );

        return $stmt->execute([
            'id'    => (int)$id,
            'uid'   => (int)$usuarioId,
            'titulo'=> $data['titulo'] ?? $existing['titulo'],
            'pais'  => $data['pais'] ?? $existing['pais'],
            'dep'   => $data['departamento'] ?? $existing['departamento'],
            'mun'   => $data['municipio'] ?? $existing['municipio'],
            'calle' => $data['calle'] ?? $existing['calle'],
            'info'  => $data['info_adicional'] ?? $existing['info_adicional'],
            'barrio'=> $data['barrio'] ?? $existing['barrio'],
            'dest'  => $data['destinatario'] ?? $existing['destinatario'],
            'cp'    => $data['codigo_postal'] ?? $existing['codigo_postal'],
            'pred'  => !empty($data['es_predeterminada']) ? 1 : ($existing['es_predeterminada'] ? 1 : 0),
        ]);
    }

    public function eliminar($id, $usuarioId) {
        $stmt = $this->db->prepare("DELETE FROM direcciones WHERE id = :id AND usuario_id = :uid");
        $result = $stmt->execute(['id' => (int)$id, 'uid' => (int)$usuarioId]);
        if ($result && $stmt->rowCount() > 0) {
            $this->ensureOnePredeterminada($usuarioId);
            return true;
        }
        return false;
    }

    public function setPredeterminada($id, $usuarioId) {
        $this->clearPredeterminada($usuarioId);
        $stmt = $this->db->prepare("UPDATE direcciones SET es_predeterminada = 1 WHERE id = :id AND usuario_id = :uid");
        return $stmt->execute(['id' => (int)$id, 'uid' => (int)$usuarioId]);
    }

    private function clearPredeterminada($usuarioId) {
        $stmt = $this->db->prepare("UPDATE direcciones SET es_predeterminada = 0 WHERE usuario_id = :uid");
        $stmt->execute(['uid' => (int)$usuarioId]);
    }

    private function ensureOnePredeterminada($usuarioId) {
        $has = $this->db->prepare("SELECT COUNT(*) FROM direcciones WHERE usuario_id = :uid AND es_predeterminada = 1");
        $has->execute(['uid' => (int)$usuarioId]);
        if ((int)$has->fetchColumn() === 0) {
            $first = $this->db->prepare("SELECT id FROM direcciones WHERE usuario_id = :uid ORDER BY fecha_creacion ASC LIMIT 1");
            $first->execute(['uid' => (int)$usuarioId]);
            $row = $first->fetch();
            if ($row) {
                $upd = $this->db->prepare("UPDATE direcciones SET es_predeterminada = 1 WHERE id = :id");
                $upd->execute(['id' => $row['id']]);
            }
        }
    }
}
