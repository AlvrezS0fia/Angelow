<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class PedidoModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    private function generarNumeroPedido() {
        $anio = date('Y');
        $stmt = $this->db->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING(numero_pedido, 10) AS UNSIGNED)), 0) + 1 as next_num FROM pedidos WHERE numero_pedido LIKE :patron");
        $stmt->execute(['patron' => "ORD-{$anio}-%"]);
        $next = (int) $stmt->fetchColumn();
        return "ORD-{$anio}-" . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function crearPedido($data) {
        $this->db->beginTransaction();

        try {
            $numeroPedido = $this->generarNumeroPedido();

            if (empty($data['usuario_id']) || !(int)$data['usuario_id']) {
                throw new \Exception("ID de usuario inválido. Debes iniciar sesión para hacer un pedido.");
            }

            $sql = "INSERT INTO pedidos (
                usuario_id, numero_pedido,
                nombre_cliente, email_cliente, telefono_cliente, cedula_cliente,
                direccion_envio, barrio, ciudad, departamento, destinatario, informacion_adicional,
                metodo_pago, metodo_envio, costo_envio, subtotal, descuento, total,
                estado_pago, zona, prioridad,
                latitud_destino, longitud_destino
            ) VALUES (
                :usuario_id, :numero_pedido,
                :nombre_cliente, :email_cliente, :telefono_cliente, :cedula_cliente,
                :direccion_envio, :barrio, :ciudad, :departamento, :destinatario, :informacion_adicional,
                :metodo_pago, :metodo_envio, :costo_envio, :subtotal, :descuento, :total,
                :estado_pago, :zona, :prioridad,
                :latitud_destino, :longitud_destino
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'usuario_id' => $data['usuario_id'],
                'numero_pedido' => $numeroPedido,
                'nombre_cliente' => $data['nombre_cliente'],
                'email_cliente' => $data['email_cliente'],
                'telefono_cliente' => $data['telefono_cliente'],
                'cedula_cliente' => $data['cedula_cliente'] ?? null,
                'direccion_envio' => $data['direccion_envio'],
                'barrio' => $data['barrio'] ?? null,
                'ciudad' => $data['ciudad'],
                'departamento' => $data['departamento'],
                'destinatario' => $data['destinatario'],
                'informacion_adicional' => $data['informacion_adicional'] ?? null,
                'metodo_pago' => $data['metodo_pago'],
                'metodo_envio' => $data['metodo_envio'],
                'costo_envio' => $data['costo_envio'],
                'subtotal' => $data['subtotal'],
                'descuento' => $data['descuento'],
                'total' => $data['total'],
                'estado_pago' => 'procesando',
                'zona' => 'centro',
                'prioridad' => 'normal',
                'latitud_destino' => $data['latitud_destino'] ?? null,
                'longitud_destino' => $data['longitud_destino'] ?? null
            ]);

            $pedidoId = (int) $this->db->lastInsertId();

            if (!empty($data['productos']) && is_array($data['productos'])) {
                $sqlDetalle = "INSERT INTO detalles_pedido (
                    pedido_id, producto_id, nombre_producto, cantidad, precio_unitario, subtotal, talla, color, imagen_url
                ) VALUES (
                    :pedido_id, :producto_id, :nombre_producto, :cantidad, :precio_unitario, :subtotal, :talla, :color, :imagen_url
                )";

                $stmtDetalle = $this->db->prepare($sqlDetalle);

                foreach ($data['productos'] as $producto) {
                    $stmtDetalle->execute([
                        'pedido_id' => $pedidoId,
                        'producto_id' => $producto['producto_id'] ?? 0,
                        'nombre_producto' => $producto['nombre'],
                        'cantidad' => $producto['cantidad'],
                        'precio_unitario' => $producto['precioUnitario'],
                        'subtotal' => $producto['precioUnitario'] * $producto['cantidad'],
                        'talla' => $producto['talla'] ?? 'Única',
                        'color' => $producto['color'] ?? null,
                        'imagen_url' => $producto['imagen'] ?? null
                    ]);

                    // Actualizar stock del producto
                    if (!empty($producto['producto_id'])) {
                        $stmtUpdate = $this->db->prepare("UPDATE productos SET stock_total = stock_total - :cantidad, total_vendidos = total_vendidos + :cantidad WHERE id = :id");
                        $stmtUpdate->execute([
                            'cantidad' => $producto['cantidad'],
                            'id' => $producto['producto_id']
                        ]);
                    }
                }
            }

            $this->db->commit();
            return ['id' => $pedidoId, 'numero_pedido' => $numeroPedido];

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getAll() {
        $sql = "SELECT p.*, u.nombre as nombre_usuario, u.email as email_usuario,
                (SELECT COUNT(*) FROM detalles_pedido dp WHERE dp.pedido_id = p.id) as total_productos
                FROM pedidos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                ORDER BY p.fecha_pedido DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getByUsuario($usuarioId) {
        $sql = "SELECT p.*,
                (SELECT COUNT(*) FROM detalles_pedido dp WHERE dp.pedido_id = p.id) as total_productos
                FROM pedidos p
                WHERE p.usuario_id = :usuario_id
                ORDER BY p.fecha_pedido DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function getDetalles($pedidoId) {
        $sql = "SELECT dp.*, p.numero_pedido, p.nombre_cliente, p.estado, p.fecha_pedido, p.total
                FROM detalles_pedido dp
                JOIN pedidos p ON dp.pedido_id = p.id
                WHERE dp.pedido_id = :pedido_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pedido_id' => $pedidoId]);
        return $stmt->fetchAll();
    }

    public function getById($pedidoId) {
        $sql = "SELECT p.*, u.nombre as nombre_usuario, u.email as email_usuario, u.telefono as telefono_usuario
                FROM pedidos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                WHERE p.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $pedidoId]);
        return $stmt->fetch();
    }

    public function updateEstado($pedidoId, $estado) {
        $sql = "UPDATE pedidos SET estado = :estado WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['estado' => $estado, 'id' => $pedidoId]);
    }
}
