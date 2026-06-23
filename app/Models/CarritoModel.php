<?php
namespace App\Models;

use App\Core\Model;

class CarritoModel extends Model {
    protected $table = 'carrito';

    // Obtener carrito por usuario (logueado)
    public function getByUsuario($usuario_id) {
        $sql = "SELECT c.*, p.nombre, p.precio, p.imagenes 
                FROM {$this->table} c
                JOIN productos p ON c.producto_id = p.id
                WHERE c.usuario_id = ?";
        return $this->db->query($sql, [$usuario_id])->fetchAll();
    }

    // Obtener carrito por session_id (invitado)
    public function getBySession($session_id) {
        $sql = "SELECT c.*, p.nombre, p.precio, p.imagenes 
                FROM {$this->table} c
                JOIN productos p ON c.producto_id = p.id
                WHERE c.session_id = ?";
        return $this->db->query($sql, [$session_id])->fetchAll();
    }

    // Agregar o actualizar item
    public function addOrUpdate($data) {
        $exists = $this->db->query(
            "SELECT id FROM {$this->table} 
             WHERE (usuario_id = ? OR (session_id = ? AND usuario_id IS NULL))
               AND producto_id = ? AND (variante_id = ? OR (variante_id IS NULL AND ? IS NULL))
               AND talla_seleccionada = ?",
            [$data['usuario_id'], $data['session_id'], $data['producto_id'], 
             $data['variante_id'], $data['variante_id'], $data['talla_seleccionada']]
        )->fetch();

        if ($exists) {
            // Actualizar cantidad
            return $this->db->query(
                "UPDATE {$this->table} SET cantidad = cantidad + ?, actualizado_en = NOW() WHERE id = ?",
                [$data['cantidad'], $exists['id']]
            );
        } else {
            return $this->db->query(
                "INSERT INTO {$this->table} 
                (usuario_id, session_id, producto_id, variante_id, cantidad, precio_unitario, talla_seleccionada, color_seleccionado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$data['usuario_id'], $data['session_id'], $data['producto_id'], $data['variante_id'],
                 $data['cantidad'], $data['precio_unitario'], $data['talla_seleccionada'], $data['color_seleccionado']]
            );
        }
    }

    // Eliminar item
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
        return $this->db->query($sql, $params);
    }

    // Vaciar carrito
    public function clear($usuario_id = null, $session_id = null) {
        if ($usuario_id) return $this->db->query("DELETE FROM {$this->table} WHERE usuario_id = ?", [$usuario_id]);
        if ($session_id) return $this->db->query("DELETE FROM {$this->table} WHERE session_id = ?", [$session_id]);
        return false;
    }

    // Migrar carrito de invitado a usuario al iniciar sesión
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