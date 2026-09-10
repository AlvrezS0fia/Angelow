<?php
namespace App\Models;

use App\Core\Database;

/**
 * ============================================================
 * ARCHIVO: Favorito.php — MÓDULO: Modelo de productos favoritos
 * ============================================================
 * QUÉ HACE: Gestiona la lista de favoritos por usuario: agregar,
 *           eliminar y verificar existencia. Usa Database::query() estático.
 * TABLA(S): favoritos
 * NOTA: No lleva sufijo «Model» por convención de este proyecto.
 * QUIÉN LO USA: Api\FavoritoController
 */
class Favorito
{
    protected $table = 'favoritos';

    /**
     * Obtiene los IDs de productos favoritos de un usuario
     * @param int|string $usuarioId
     * @return array<int, mixed> Lista de producto_id del usuario
     */
    public function getByUsuario($usuarioId)
    {
        $sql = "SELECT producto_id FROM {$this->table} WHERE usuario_id = :usuario_id ORDER BY fecha_agregado DESC";
        $stmt = Database::query($sql, ['usuario_id' => $usuarioId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_column($rows, 'producto_id');
    }

    /**
     * Verifica si un producto ya está en favoritos
     */
    public function existe($usuarioId, $productoId)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE usuario_id = :usuario_id AND producto_id = :producto_id";
        $stmt = Database::query($sql, ['usuario_id' => $usuarioId, 'producto_id' => $productoId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Agrega un favorito. Necesita iniciar sesión (usuarioId real).
     */
    public function agregar($usuarioId, $productoId)
    {
        $sql = "INSERT INTO {$this->table} (usuario_id, producto_id, fecha_agregado) VALUES (:usuario_id, :producto_id, NOW())";
        $stmt = Database::query($sql, ['usuario_id' => $usuarioId, 'producto_id' => $productoId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Elimina un favorito
     */
    public function eliminar($usuarioId, $productoId)
    {
        $sql = "DELETE FROM {$this->table} WHERE usuario_id = :usuario_id AND producto_id = :producto_id";
        $stmt = Database::query($sql, ['usuario_id' => $usuarioId, 'producto_id' => $productoId]);
        return $stmt->rowCount() > 0;
    }
}