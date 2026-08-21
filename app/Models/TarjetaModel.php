<?php
namespace App\Models;

use App\Core\Database;

class TarjetaModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByUsuario($usuarioId) {
        $stmt = $this->db->prepare(
            "SELECT id, alias, numero_enmascarado, titular, mes_expiracion, anio_expiracion, tipo_tarjeta, es_predeterminada, activa
             FROM tarjetas_credito WHERE usuario_id = :uid AND activa = 1 ORDER BY es_predeterminada DESC, fecha_creacion DESC"
        );
        $stmt->execute(['uid' => (int)$usuarioId]);
        return $stmt->fetchAll();
    }

    public function getById($id, $usuarioId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM tarjetas_credito WHERE id = :id AND usuario_id = :uid"
        );
        $stmt->execute(['id' => (int)$id, 'uid' => (int)$usuarioId]);
        return $stmt->fetch();
    }

    public function crear($usuarioId, $data) {
        if (!empty($data['es_predeterminada'])) {
            $this->clearPredeterminada($usuarioId);
        }

        $numeroLimpio = preg_replace('/\s/', '', $data['numero_tarjeta'] ?? '');
        $ultimos4 = substr($numeroLimpio, -4);

        $tipoTarjeta = $this->detectarTipo($numeroLimpio);

        $mes = (int)($data['mes_expiracion'] ?? 0);
        $anio = (int)($data['anio_expiracion'] ?? 0);

        $stmt = $this->db->prepare(
            "INSERT INTO tarjetas_credito (usuario_id, alias, numero_enmascarado, titular, mes_expiracion, anio_expiracion, tipo_tarjeta, pais, departamento, municipio, codigo_postal, calle, barrio, es_predeterminada)
             VALUES (:uid, :alias, :num, :titular, :mes, :anio, :tipo, :pais, :dep, :mun, :cp, :calle, :barrio, :pred)"
        );

        $result = $stmt->execute([
            'uid'    => (int)$usuarioId,
            'alias'  => $data['alias'] ?? 'Mi tarjeta',
            'num'    => '**** **** **** ' . $ultimos4,
            'titular'=> strtoupper(trim($data['titular'] ?? '')),
            'mes'    => $mes,
            'anio'   => $anio,
            'tipo'   => $tipoTarjeta,
            'pais'   => $data['pais'] ?? 'Colombia',
            'dep'    => $data['departamento'],
            'mun'    => $data['municipio'],
            'cp'     => $data['codigo_postal'],
            'calle'  => $data['calle'],
            'barrio' => $data['barrio'],
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

        $fields = [];
        $params = ['id' => (int)$id, 'uid' => (int)$usuarioId];

        if (isset($data['alias'])) { $fields[] = 'alias = :alias'; $params['alias'] = $data['alias']; }
        if (isset($data['titular'])) { $fields[] = 'titular = :titular'; $params['titular'] = strtoupper(trim($data['titular'])); }
        if (isset($data['mes_expiracion'])) { $fields[] = 'mes_expiracion = :mes'; $params['mes'] = (int)$data['mes_expiracion']; }
        if (isset($data['anio_expiracion'])) { $fields[] = 'anio_expiracion = :anio'; $params['anio'] = (int)$data['anio_expiracion']; }
        if (isset($data['es_predeterminada'])) { $fields[] = 'es_predeterminada = :pred'; $params['pred'] = !empty($data['es_predeterminada']) ? 1 : 0; }

        if (empty($fields)) return true;

        $stmt = $this->db->prepare("UPDATE tarjetas_credito SET " . implode(', ', $fields) . " WHERE id = :id AND usuario_id = :uid");
        return $stmt->execute($params);
    }

    public function eliminar($id, $usuarioId) {
        $stmt = $this->db->prepare("UPDATE tarjetas_credito SET activa = 0 WHERE id = :id AND usuario_id = :uid");
        $result = $stmt->execute(['id' => (int)$id, 'uid' => (int)$usuarioId]);
        if ($result) {
            $this->ensureOnePredeterminada($usuarioId);
            return true;
        }
        return false;
    }

    public function setPredeterminada($id, $usuarioId) {
        $this->clearPredeterminada($usuarioId);
        $stmt = $this->db->prepare("UPDATE tarjetas_credito SET es_predeterminada = 1 WHERE id = :id AND usuario_id = :uid");
        return $stmt->execute(['id' => (int)$id, 'uid' => (int)$usuarioId]);
    }

    private function clearPredeterminada($usuarioId) {
        $stmt = $this->db->prepare("UPDATE tarjetas_credito SET es_predeterminada = 0 WHERE usuario_id = :uid AND activa = 1");
        $stmt->execute(['uid' => (int)$usuarioId]);
    }

    private function ensureOnePredeterminada($usuarioId) {
        $has = $this->db->prepare("SELECT COUNT(*) FROM tarjetas_credito WHERE usuario_id = :uid AND es_predeterminada = 1 AND activa = 1");
        $has->execute(['uid' => (int)$usuarioId]);
        if ((int)$has->fetchColumn() === 0) {
            $first = $this->db->prepare("SELECT id FROM tarjetas_credito WHERE usuario_id = :uid AND activa = 1 ORDER BY fecha_creacion ASC LIMIT 1");
            $first->execute(['uid' => (int)$usuarioId]);
            $row = $first->fetch();
            if ($row) {
                $upd = $this->db->prepare("UPDATE tarjetas_credito SET es_predeterminada = 1 WHERE id = :id");
                $upd->execute(['id' => $row['id']]);
            }
        }
    }

    private function detectarTipo($numero) {
        if (preg_match('/^4/', $numero)) return 'visa';
        if (preg_match('/^5[1-5]/', $numero)) return 'mastercard';
        if (preg_match('/^3[47]/', $numero)) return 'amex';
        if (preg_match('/^6(?:011|5)/', $numero)) return 'discover';
        return 'otra';
    }
}
