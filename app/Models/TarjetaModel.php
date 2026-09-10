<?php
namespace App\Models;

use App\Core\Database;

/**
 * ============================================================
 * ARCHIVO: TarjetaModel.php — MÓDULO: Modelo de tarjetas de crédito
 * ============================================================
 * QUÉ HACE: CRUD de tarjetas de crédito. Almacena solo los últimos 4 dígitos
 *           (numero_enmascarado). Eliminación lógica (activa=0). Detecta
 *           tipo de tarjeta por prefijo. Garantiza una predeterminada por usuario.
 * TABLA(S): tarjetas_credito
 * QUIÉN LO USA: Cliente\TarjetaController
 */
class TarjetaModel {
    /** @var \PDO Conexión PDO obtenida del Singleton Database */
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Tarjetas activas de un usuario. Nunca expone el número completo:
     * solo numero_enmascarado (últimos 4 dígitos).
     * @param int|string $usuarioId
     * @return array<int, array<string, mixed>>
     */
    public function getByUsuario($usuarioId) {
        $stmt = $this->db->prepare(
            "SELECT id, alias, numero_enmascarado, titular, mes_expiracion, anio_expiracion, tipo_tarjeta, es_predeterminada, activa
             FROM tarjetas_credito WHERE usuario_id = :uid AND activa = 1 ORDER BY es_predeterminada DESC, fecha_creacion DESC"
        );
        $stmt->execute(['uid' => (int)$usuarioId]);
        return $stmt->fetchAll();
    }

    /** Busca una tarjeta por ID verificando propiedad del usuario. */
    public function getById($id, $usuarioId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM tarjetas_credito WHERE id = :id AND usuario_id = :uid"
        );
        $stmt->execute(['id' => (int)$id, 'uid' => (int)$usuarioId]);
        return $stmt->fetch();
    }

    /**
     * Crea una tarjeta. Nunca guarda el número completo: se limpian espacios
     * y se conserva solo numero_enmascarado con los últimos 4. El tipo se
     * deduce del prefijo (detectarTipo).
     * @param int|string $usuarioId
     * @param array<string, mixed> $data
     * @return int|false ID insertado o false
     */
    public function crear($usuarioId, $data) {
        // Solo una tarjeta predeterminada por usuario
        if (!empty($data['es_predeterminada'])) {
            $this->clearPredeterminada($usuarioId);
        }

        // Se quitan espacios del número y se extraen los últimos 4 dígitos
        $numeroLimpio = preg_replace('/\s/', '', $data['numero_tarjeta'] ?? '');
        $ultimos4 = substr($numeroLimpio, -4);

        // Detección del tipo (visa/mastercard/amex/discover) por prefijo
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

    /**
     * Actualización parcial: permite cambiar alias, titular, expiración o
     * predeterminada. NO permite cambiar el número de tarjeta (seguridad:
     * solo se guardaría la máscara, nunca el número real).
     */
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

    /**
     * Borrado lógico: activa=0 para ocultar la tarjeta sin perder el historial.
     * Luego asegura que exista una predeterminada para el usuario.
     */
    public function eliminar($id, $usuarioId) {
        $stmt = $this->db->prepare("UPDATE tarjetas_credito SET activa = 0 WHERE id = :id AND usuario_id = :uid");
        $result = $stmt->execute(['id' => (int)$id, 'uid' => (int)$usuarioId]);
        if ($result) {
            $this->ensureOnePredeterminada($usuarioId);
            return true;
        }
        return false;
    }

    /** Marca una tarjeta como predeterminada (borra el flag en las demás). */
    public function setPredeterminada($id, $usuarioId) {
        $this->clearPredeterminada($usuarioId);
        $stmt = $this->db->prepare("UPDATE tarjetas_credito SET es_predeterminada = 1 WHERE id = :id AND usuario_id = :uid");
        return $stmt->execute(['id' => (int)$id, 'uid' => (int)$usuarioId]);
    }

    /** Quita el flag predeterminada a todas las tarjetas activas del usuario. */
    private function clearPredeterminada($usuarioId) {
        $stmt = $this->db->prepare("UPDATE tarjetas_credito SET es_predeterminada = 0 WHERE usuario_id = :uid AND activa = 1");
        $stmt->execute(['uid' => (int)$usuarioId]);
    }

    /**
     * Si el usuario se queda sin predeterminada, promueve su tarjeta activa
     * más antigua.
     */
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

    /**
     * Detecta el tipo de tarjeta según los primeros dígitos (regex de prefijo):
     * 4→visa, 51-55→mastercard, 34/37→amex, 6011/65→discover.
     */
    private function detectarTipo($numero) {
        if (preg_match('/^4/', $numero)) return 'visa';
        if (preg_match('/^5[1-5]/', $numero)) return 'mastercard';
        if (preg_match('/^3[47]/', $numero)) return 'amex';
        if (preg_match('/^6(?:011|5)/', $numero)) return 'discover';
        return 'otra';
    }
}
