<?php
namespace App\Models;

use App\Core\Database;

class FacturaModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function generarNumeroFactura() {
        $anio = date('Y');
        $stmt = $this->db->prepare(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(numero_factura, 10) AS UNSIGNED)), 0) + 1 
             FROM facturas WHERE numero_factura LIKE :patron"
        );
        $stmt->execute(['patron' => "FAC-{$anio}-%"]);
        $siguiente = (int) $stmt->fetchColumn();
        return "FAC-{$anio}-" . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
    }

    private function existeColumna($tabla, $columna) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS 
             WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tabla AND COLUMN_NAME = :columna"
        );
        $stmt->execute([':db' => DB_NAME, ':tabla' => $tabla, ':columna' => $columna]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function crearDesdePedido($pedidoId, $adminId = null) {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("SELECT * FROM pedidos WHERE id = :id");
            $stmt->execute(['id' => $pedidoId]);
            $pedido = $stmt->fetch();

            if (!$pedido) {
                $this->db->rollBack();
                return null;
            }

            if ($this->existeColumna('pedidos', 'factura_generada')) {
                if (!empty($pedido['factura_generada'])) {
                    $stmt = $this->db->prepare("SELECT * FROM facturas WHERE pedido_id = :pedido_id");
                    $stmt->execute(['pedido_id' => $pedidoId]);
                    $facturaExistente = $stmt->fetch();
                    $this->db->rollBack();
                    return $facturaExistente;
                }
            } else {
                $stmt = $this->db->prepare("SELECT * FROM facturas WHERE pedido_id = :pedido_id");
                $stmt->execute(['pedido_id' => $pedidoId]);
                $facturaExistente = $stmt->fetch();
                if ($facturaExistente) {
                    $this->db->rollBack();
                    return $facturaExistente;
                }
            }

            $stmt = $this->db->prepare("SELECT * FROM detalles_pedido WHERE pedido_id = :pedido_id");
            $stmt->execute(['pedido_id' => $pedidoId]);
            $detalles = $stmt->fetchAll();

            $numero = $this->generarNumeroFactura();

            $sqlFactura = "INSERT INTO facturas (
                numero_factura, pedido_id, usuario_id,
                nombre_cliente, email_cliente, telefono_cliente, cedula_cliente,
                direccion_envio, barrio, ciudad, departamento, destinatario,
                metodo_envio, costo_envio,
                subtotal, descuento, total, metodo_pago,
                estado, fecha_emision
            ) VALUES (
                :numero_factura, :pedido_id, :usuario_id,
                :nombre_cliente, :email_cliente, :telefono_cliente, :cedula_cliente,
                :direccion_envio, :barrio, :ciudad, :departamento, :destinatario,
                :metodo_envio, :costo_envio,
                :subtotal, :descuento, :total, :metodo_pago,
                'pendiente', NOW()
            )";

            $stmt = $this->db->prepare($sqlFactura);
            $stmt->execute([
                ':numero_factura'  => $numero,
                ':pedido_id'       => $pedidoId,
                ':usuario_id'      => $pedido['usuario_id'],
                ':nombre_cliente'  => $pedido['nombre_cliente'],
                ':email_cliente'   => $pedido['email_cliente'],
                ':telefono_cliente'=> $pedido['telefono_cliente'],
                ':cedula_cliente'  => $pedido['cedula_cliente'] ?? null,
                ':direccion_envio' => $pedido['direccion_envio'],
                ':barrio'          => $pedido['barrio'] ?? null,
                ':ciudad'          => $pedido['ciudad'],
                ':departamento'    => $pedido['departamento'],
                ':destinatario'    => $pedido['destinatario'] ?? null,
                ':metodo_envio'    => $pedido['metodo_envio'] ?? 'normal',
                ':costo_envio'     => $pedido['costo_envio'] ?? 0,
                ':subtotal'        => $pedido['subtotal'],
                ':descuento'       => $pedido['descuento'] ?? 0,
                ':total'           => $pedido['total'],
                ':metodo_pago'     => $pedido['metodo_pago'] ?? null,
            ]);

            $facturaId = (int) $this->db->lastInsertId();

            $sqlDetalle = "INSERT INTO facturas_detalle (
                factura_id, producto_id, nombre_producto, cantidad,
                precio_unitario, subtotal, talla, color, imagen_url
            ) VALUES (
                :factura_id, :producto_id, :nombre_producto, :cantidad,
                :precio_unitario, :subtotal, :talla, :color, :imagen_url
            )";

            $stmtDetalle = $this->db->prepare($sqlDetalle);

            foreach ($detalles as $det) {
                $stmtDetalle->execute([
                    ':factura_id'      => $facturaId,
                    ':producto_id'     => $det['producto_id'] ?? 0,
                    ':nombre_producto' => $det['nombre_producto'],
                    ':cantidad'        => $det['cantidad'],
                    ':precio_unitario' => $det['precio_unitario'],
                    ':subtotal'        => $det['subtotal'],
                    ':talla'           => $det['talla'] ?? null,
                    ':color'           => $det['color'] ?? null,
                    ':imagen_url'      => $det['imagen_url'] ?? null,
                ]);
            }

            if ($this->existeColumna('pedidos', 'factura_generada')) {
                $stmt = $this->db->prepare("UPDATE pedidos SET factura_generada = 1 WHERE id = :id");
                $stmt->execute(['id' => $pedidoId]);
            }

            $stmt = $this->db->prepare(
                "INSERT INTO facturas_historial (factura_id, estado_nuevo, cambiado_por, notas)
                 VALUES (:factura_id, 'pendiente', :cambiado_por, 'Factura creada automaticamente')"
            );
            $stmt->execute([
                ':factura_id'   => $facturaId,
                ':cambiado_por' => $adminId,
            ]);

            $this->db->commit();

            $stmt = $this->db->prepare("SELECT * FROM facturas WHERE id = :id");
            $stmt->execute(['id' => $facturaId]);
            return $stmt->fetch();

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("FacturaModel::crearDesdePedido ERROR - " . $e->getMessage());
            throw $e;
        }
    }

    public function getAll($estado = null) {
        $query = "SELECT f.*, p.numero_pedido,
                    (SELECT COUNT(*) FROM facturas_detalle fd WHERE fd.factura_id = f.id) as total_items
                  FROM facturas f
                  LEFT JOIN pedidos p ON f.pedido_id = p.id";

        $params = [];

        if ($estado && $estado !== 'all') {
            $query .= " WHERE f.estado = :estado";
            $params[':estado'] = $estado;
        }

        $query .= " ORDER BY f.fecha_emision DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById($facturaId) {
        $stmt = $this->db->prepare(
            "SELECT f.*, p.numero_pedido,
                    p.metodo_pago as pedido_metodo_pago,
                    p.direccion_envio as pedido_direccion
             FROM facturas f
             LEFT JOIN pedidos p ON f.pedido_id = p.id
             WHERE f.id = :id"
        );
        $stmt->execute(['id' => $facturaId]);
        $factura = $stmt->fetch();

        if ($factura) {
            $stmt = $this->db->prepare("SELECT * FROM facturas_detalle WHERE factura_id = :factura_id");
            $stmt->execute(['factura_id' => $facturaId]);
            $factura['detalles'] = $stmt->fetchAll();

            $stmt = $this->db->prepare(
                "SELECT fh.*, u.nombre as cambiado_por_nombre
                 FROM facturas_historial fh
                 LEFT JOIN usuarios u ON fh.cambiado_por = u.id
                 WHERE fh.factura_id = :factura_id
                 ORDER BY fh.fecha_cambio DESC"
            );
            $stmt->execute(['factura_id' => $facturaId]);
            $factura['historial'] = $stmt->fetchAll();
        }

        return $factura;
    }

    public function getByPedido($pedidoId) {
        $stmt = $this->db->prepare("SELECT * FROM facturas WHERE pedido_id = :pedido_id");
        $stmt->execute(['pedido_id' => $pedidoId]);
        $factura = $stmt->fetch();

        if ($factura) {
            $stmt = $this->db->prepare("SELECT * FROM facturas_detalle WHERE factura_id = :factura_id");
            $stmt->execute(['factura_id' => $factura['id']]);
            $factura['detalles'] = $stmt->fetchAll();
        }

        return $factura;
    }

    public function cambiarEstado($facturaId, $nuevoEstado, $adminId = null, $notas = null) {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("SELECT * FROM facturas WHERE id = :id");
            $stmt->execute(['id' => $facturaId]);
            $factura = $stmt->fetch();

            if (!$factura) {
                $this->db->rollBack();
                return [null, 'Factura no encontrada'];
            }

            $estadoAnterior = $factura['estado'];

            $stmt = $this->db->prepare(
                "UPDATE facturas SET estado = :estado, fecha_estado = NOW(), 
                 notas = COALESCE(:notas, notas) WHERE id = :id"
            );
            $stmt->execute([
                ':estado' => $nuevoEstado,
                ':notas'  => $notas,
                ':id'     => $facturaId,
            ]);

            $stmt = $this->db->prepare(
                "INSERT INTO facturas_historial (factura_id, estado_anterior, estado_nuevo, cambiado_por, notas)
                 VALUES (:factura_id, :estado_anterior, :estado_nuevo, :cambiado_por, :notas)"
            );
            $stmt->execute([
                ':factura_id'      => $facturaId,
                ':estado_anterior' => $estadoAnterior,
                ':estado_nuevo'    => $nuevoEstado,
                ':cambiado_por'    => $adminId,
                ':notas'           => $notas,
            ]);

            $this->db->commit();

            $stmt = $this->db->prepare("SELECT * FROM facturas WHERE id = :id");
            $stmt->execute(['id' => $facturaId]);
            return [$stmt->fetch(), null];

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("FacturaModel::cambiarEstado ERROR - " . $e->getMessage());
            throw $e;
        }
    }

    public function marcarEnviadoCorreo($facturaId) {
        $stmt = $this->db->prepare(
            "UPDATE facturas SET enviado_correo = 1, fecha_envio_correo = NOW() WHERE id = :id"
        );
        $stmt->execute(['id' => $facturaId]);
    }

    public function getStats() {
        $stats = [];

        $stmt = $this->db->query("SELECT COUNT(*) as total FROM facturas");
        $stats['total'] = (int) $stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COUNT(*) as total FROM facturas WHERE estado = 'autorizada'");
        $stats['autorizadas'] = (int) $stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COUNT(*) as total FROM facturas WHERE estado = 'cancelada'");
        $stats['canceladas'] = (int) $stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COUNT(*) as total FROM facturas WHERE estado = 'devolucion'");
        $stats['devoluciones'] = (int) $stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COALESCE(SUM(total), 0) as total FROM facturas WHERE estado = 'autorizada'");
        $stats['total_ingresos'] = (float) $stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COUNT(*) as total FROM facturas WHERE estado = 'pendiente'");
        $stats['pendientes'] = (int) $stmt->fetchColumn();

        return $stats;
    }
}
