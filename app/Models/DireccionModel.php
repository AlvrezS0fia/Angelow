<?php
namespace App\Models;

use App\Core\Database;

/**
 * ============================================================
 * ARCHIVO: DireccionModel.php — MÓDULO: Modelo de direcciones de envío
 * ============================================================
 * QUÉ HACE: CRUD de direcciones de envío por usuario. Garantiza una sola
 *           dirección predeterminada por usuario (clearPredeterminada /
 *           ensureOnePredeterminada).
 * TABLA(S): direcciones
 * QUIÉN LO USA: Cliente\DireccionController
 */
class DireccionModel {
    /** @var \PDO Conexión PDO obtenida del Singleton Database */
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Direcciones de un usuario, con la predeterminada primero.
     * @param int|string $usuarioId
     * @return array<int, array<string, mixed>>
     */
    public function getByUsuario($usuarioId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM direcciones WHERE usuario_id = :uid ORDER BY es_predeterminada DESC, fecha_creacion DESC"
        );
        $stmt->execute(['uid' => (int)$usuarioId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca una dirección por ID verificando que pertenezca al usuario
     * (evita IDOR: un usuario no puede leer direcciones ajenas).
     * @param int|string $id
     * @param int|string $usuarioId
     * @return array<string, mixed>|false
     */
    public function getById($id, $usuarioId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM direcciones WHERE id = :id AND usuario_id = :uid"
        );
        $stmt->execute(['id' => (int)$id, 'uid' => (int)$usuarioId]);
        return $stmt->fetch();
    }

    /**
     * Crea una dirección. Si es predeterminada, primero quita la condición
     * a las demás del usuario (solo hay una predeterminada).
     * @param int|string $usuarioId
     * @param array<string, mixed> $data
     * @return int|false ID insertado o false
     */
    public function crear($usuarioId, $data) {
        // Solo una predeterminada por usuario: se limpia antes de marcar
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

    /**
     * Actualiza una dirección. Conserva los valores existentes cuando el campo
     * no viene en $data (merge con la fila actual).
     * @param int|string $id
     * @param int|string $usuarioId
     * @param array<string, mixed> $data
     * @return bool
     */
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

    /**
     * Elimina una dirección. Si se borra la predeterminada y no queda ninguna,
     * ensureOnePredeterminada promueve la más antigua.
     */
    public function eliminar($id, $usuarioId) {
        $stmt = $this->db->prepare("DELETE FROM direcciones WHERE id = :id AND usuario_id = :uid");
        $result = $stmt->execute(['id' => (int)$id, 'uid' => (int)$usuarioId]);
        if ($result && $stmt->rowCount() > 0) {
            $this->ensureOnePredeterminada($usuarioId);
            return true;
        }
        return false;
    }

    /** Marca una dirección como predeterminada (quita la anterior del usuario). */
    public function setPredeterminada($id, $usuarioId) {
        $this->clearPredeterminada($usuarioId);
        $stmt = $this->db->prepare("UPDATE direcciones SET es_predeterminada = 1 WHERE id = :id AND usuario_id = :uid");
        return $stmt->execute(['id' => (int)$id, 'uid' => (int)$usuarioId]);
    }

    /** Quita el flag predeterminada a todas las direcciones del usuario. */
    private function clearPredeterminada($usuarioId) {
        $stmt = $this->db->prepare("UPDATE direcciones SET es_predeterminada = 0 WHERE usuario_id = :uid");
        $stmt->execute(['uid' => (int)$usuarioId]);
    }

    /**
     * Garantiza que el usuario conserve al menos una predeterminada:
     * si no tiene ninguna, promueve la de creación más antigua.
     */
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
