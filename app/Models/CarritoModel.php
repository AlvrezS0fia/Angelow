<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * ============================================================
 * ARCHIVO: CarritoModel.php — MÓDULO: Modelo de carrito de compras
 * ============================================================
 * QUÉ HACE: Gestiona items del carrito para usuarios logueados y
 *           invitados (session_id), incluyendo migración al login.
 * TABLA(S): carrito, productos (JOIN para precio/nombre)
 * NOTA: Modelo NO utilizado en runtime — el carrito real está en
 *       app/Controllers/Api/CarritoController.php con Database::query directo.
 *       Este archivo queda documentado como referencia de diseño.
 * HERENCIA: Hereda CRUD genérico de App\Core\Model ($table = 'carrito').
 */
class CarritoModel extends Model {
    // ENCAPSULAMIENTO: $table es protected: el padre la usa en su CRUD
    // y no se expone al exterior (solo la clase y sus hijas la ven).
    protected string $table = 'carrito';

    // Obtener carrito por usuario (logueado)
    /** @param int|string $usuario_id
     * @return array<int, array<string, mixed>> */
    public function getByUsuario($usuario_id) {
        // JOIN con productos para traer nombre, precio vigente e imágenes
        $sql = "SELECT c.*, p.nombre, p.precio, p.imagenes 
                FROM {$this->table} c
                JOIN productos p ON c.producto_id = p.id
                WHERE c.usuario_id = ?";
        return Database::query($sql, [$usuario_id])->fetchAll();
    }

    // Obtener carrito por session_id (invitado)
    /** @param int|string $session_id
     * @return array<int, array<string, mixed>> */
    public function getBySession($session_id) {
        $sql = "SELECT c.*, p.nombre, p.precio, p.imagenes 
                FROM {$this->table} c
                JOIN productos p ON c.producto_id = p.id
                WHERE c.session_id = ?";
        return Database::query($sql, [$session_id])->fetchAll();
    }

    // Agregar o actualizar item
    /** @param array<string, mixed> $data
     * @return \PDOStatement */
    public function addOrUpdate($data) {
        // Upsert (INSERT o UPDATE según exista): busca el item equivalente
        // combinando usuario/sesión, producto, variante y talla
        $exists = Database::query(
            "SELECT id FROM {$this->table} 
             WHERE (usuario_id = ? OR (session_id = ? AND usuario_id IS NULL))
               AND producto_id = ? AND (variante_id = ? OR (variante_id IS NULL AND ? IS NULL))
               AND talla_seleccionada = ?",
            [$data['usuario_id'], $data['session_id'], $data['producto_id'], 
             $data['variante_id'], $data['variante_id'], $data['talla_seleccionada']]
        )->fetch();

        if ($exists) {
            // Actualizar cantidad
            return Database::query(
                "UPDATE {$this->table} SET cantidad = cantidad + ?, actualizado_en = NOW() WHERE id = ?",
                [$data['cantidad'], $exists['id']]
            );
        } else {
            return Database::query(
                "INSERT INTO {$this->table} 
                (usuario_id, session_id, producto_id, variante_id, cantidad, precio_unitario, talla_seleccionada, color_seleccionado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$data['usuario_id'], $data['session_id'], $data['producto_id'], $data['variante_id'],
                 $data['cantidad'], $data['precio_unitario'], $data['talla_seleccionada'], $data['color_seleccionado']]
            );
        }
    }

    // Eliminar item
    /** @param int|string $item_id
     * @param int|string|null $usuario_id
     * @param string|null $session_id
     * @return \PDOStatement|false */
    public function remove($item_id, $usuario_id = null, $session_id = null) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $params = [$item_id];
        if ($usuario_id) {
            $sql .= " AND usuario_id = ?";
            $params[] = $usuario_id;
        } elseif ($session_id) {
            $sql .= " AND session_id = ?";
            $params[] = $session_id;
        }
        return Database::query($sql, $params);
    }

    // Vaciar carrito
    // Requiere usuario_id o session_id; si ninguno viene, no borra nada.
    /** @param int|string|null $usuario_id
     * @param string|null $session_id
     * @return \PDOStatement|false */
    public function clear($usuario_id = null, $session_id = null) {
        if ($usuario_id) return Database::query("DELETE FROM {$this->table} WHERE usuario_id = ?", [$usuario_id]);
        if ($session_id) return Database::query("DELETE FROM {$this->table} WHERE session_id = ?", [$session_id]);
        return false;
    }

    // Migrar carrito de invitado a usuario al iniciar sesión
    /** @param string $session_id
     * @param int|string $usuario_id */
    public function mergeGuestCart($session_id, $usuario_id) {
        // Actualizar items existentes del usuario con los del invitado (sumar cantidades)
        $guestItems = $this->getBySession($session_id);
        foreach ($guestItems as $item) {
            $this->addOrUpdate([
                'usuario_id' => $usuario_id,
                'session_id' => null,
                'producto_id' => $item['producto_id'],
                'variante_id' => $item['variante_id'],
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $item['precio_unitario'],
                'talla_seleccionada' => $item['talla_seleccionada'],
                'color_seleccionado' => $item['color_seleccionado']
            ]);
        }
        // Eliminar carrito invitado
        $this->clear(null, $session_id);
    }
}