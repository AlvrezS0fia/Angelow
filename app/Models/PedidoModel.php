<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * ============================================================
 * ARCHIVO: PedidoModel.php — MÓDULO: Modelo de pedidos
 * ============================================================
 * QUÉ HACE: CRUD completo de pedidos con transacción ACID en crearPedido.
 *           Genera número de pedido secuencial (ORD-AAAA-NNNN), recalcula
 *           precios y stock desde BD, valida disponibilidad. NO descuenta
 *           stock directamente (solo valida).
 * TABLA(S): pedidos, detalles_pedido, productos, usuarios (JOIN)
 * QUIÉN LO USA: CompraController, Admin\PedidosController,
 *               Cliente\PedidosController
 */
class PedidoModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    private function generarNumeroPedido() {
        // Número secuencial: ORD-2026-0001 (máximo actual + 1 por año)
        $anio = date('Y');
        $stmt = $this->db->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING(numero_pedido, 10) AS UNSIGNED)), 0) + 1 as next_num FROM pedidos WHERE numero_pedido LIKE :patron");
        $stmt->execute(['patron' => "ORD-{$anio}-%"]);
        $next = (int) $stmt->fetchColumn();
        return "ORD-{$anio}-" . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Crea un pedido dentro de una transacción ACID: si algo falla (producto
     * inválido, stock insuficiente, error de SQL) se hace rollBack y no queda
     * información parcial. NO descuenta stock: solo valida que alcance.
     * @param array<string, mixed> $data
     * @return array{id: int, numero_pedido: string}
     * @throws \Exception
     */
    public function crearPedido($data) {
        // Inicio de transacción: todo o nada entre pedido y sus detalles
        $this->db->beginTransaction();

        try {
            $numeroPedido = $this->generarNumeroPedido();

            if (empty($data['usuario_id']) || !(int)$data['usuario_id']) {
                throw new \Exception("ID de usuario inválido. Debes iniciar sesión para hacer un pedido.");
            }

            if (empty($data['productos']) || !is_array($data['productos'])) {
                throw new \Exception("El pedido debe contener al menos un producto.");
            }

            // Obtener precios reales desde la BD y calcular subtotal real
            $subtotalReal = 0;
            $productosValidados = [];
            $stmtPrice = $this->db->prepare("SELECT id, precio, nombre, stock_total FROM productos WHERE id = :id");

            foreach ($data['productos'] as $producto) {
                $pid = (int)($producto['producto_id'] ?? 0);
                $cant = (int)($producto['cantidad'] ?? 0);

                if ($pid <= 0) {
                    throw new \Exception("ID de producto inválido.");
                }
                if ($cant <= 0) {
                    throw new \Exception("La cantidad del producto debe ser mayor a 0.");
                }

                $stmtPrice->execute(['id' => $pid]);
                $prod = $stmtPrice->fetch();

                if (!$prod) {
                    throw new \Exception("Producto ID {$pid} no encontrado en la base de datos.");
                }
                if ((int)$prod['stock_total'] < $cant) {
                    throw new \Exception("Stock insuficiente para \"{$prod['nombre']}\": solicita {$cant} pero hay {$prod['stock_total']} unidades.");
                }

                $precioReal = (float)$prod['precio'];
                $subtotalProducto = $precioReal * $cant;
                $subtotalReal += $subtotalProducto;

                $productosValidados[] = [
                    'producto_id' => $pid,
                    'nombre' => $prod['nombre'],
                    'cantidad' => $cant,
                    'precio_unitario' => $precioReal,
                    'subtotal' => $subtotalProducto,
                    'talla' => $producto['talla'] ?? 'Única',
                    'color' => $producto['color'] ?? null,
                    'imagen' => $producto['imagen'] ?? null,
                ];
            }

            // Recalcular totals en el servidor
            $costoEnvio = (float)($data['costo_envio'] ?? 0);
            $descuento = (float)($data['descuento'] ?? 0);
            if ($descuento > $subtotalReal) {
                $descuento = $subtotalReal;
            }
            $totalReal = $subtotalReal - $descuento + $costoEnvio;
            if ($totalReal < 0) $totalReal = 0;

            $sql = "INSERT INTO pedidos (
                usuario_id, numero_pedido,
                nombre_cliente, email_cliente, telefono_cliente, cedula_cliente,
                direccion_envio, direccion_complementaria, barrio, ciudad, departamento, destinatario, informacion_adicional,
                metodo_pago, metodo_envio, costo_envio, subtotal, descuento, total,
                estado_pago, zona, prioridad,
                latitud_destino, longitud_destino
            ) VALUES (
                :usuario_id, :numero_pedido,
                :nombre_cliente, :email_cliente, :telefono_cliente, :cedula_cliente,
                :direccion_envio, :direccion_complementaria, :barrio, :ciudad, :departamento, :destinatario, :informacion_adicional,
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
                'direccion_complementaria' => $data['direccion_complementaria'] ?? null,
                'barrio' => $data['barrio'] ?? null,
                'ciudad' => $data['ciudad'],
                'departamento' => $data['departamento'],
                'destinatario' => $data['destinatario'],
                'informacion_adicional' => $data['informacion_adicional'] ?? null,
                'metodo_pago' => $data['metodo_pago'],
                'metodo_envio' => $data['metodo_envio'],
                'costo_envio' => $costoEnvio,
                'subtotal' => $subtotalReal,
                'descuento' => $descuento,
                'total' => $totalReal,
                'estado_pago' => 'procesando',
                'zona' => 'centro',
                'prioridad' => 'normal',
                'latitud_destino' => $data['latitud_destino'] ?? null,
                'longitud_destino' => $data['longitud_destino'] ?? null
            ]);

            $pedidoId = (int) $this->db->lastInsertId();

            $sqlDetalle = "INSERT INTO detalles_pedido (
                pedido_id, producto_id, nombre_producto, cantidad, precio_unitario, subtotal, talla, color, imagen_url
            ) VALUES (
                :pedido_id, :producto_id, :nombre_producto, :cantidad, :precio_unitario, :subtotal, :talla, :color, :imagen_url
            )";

            $stmtDetalle = $this->db->prepare($sqlDetalle);

            foreach ($productosValidados as $producto) {
                $stmtDetalle->execute([
                    'pedido_id' => $pedidoId,
                    'producto_id' => $producto['producto_id'],
                    'nombre_producto' => $producto['nombre'],
                    'cantidad' => $producto['cantidad'],
                    'precio_unitario' => $producto['precio_unitario'],
                    'subtotal' => $producto['subtotal'],
                    'talla' => $producto['talla'],
                    'color' => $producto['color'],
                    'imagen_url' => $producto['imagen']
                ]);
            }

            $this->db->commit();
            return ['id' => $pedidoId, 'numero_pedido' => $numeroPedido];

        } catch (\Exception $e) {
            // Deshacer todos los INSERT si algo falló a mitad de camino
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Todos los pedidos con nombre/email del usuario via LEFT JOIN y total de items.
     * @return array<int, array<string, mixed>>
     */
    public function getAll() {
        $sql = "SELECT p.*, u.nombre as nombre_usuario, u.email as email_usuario,
                (SELECT COUNT(*) FROM detalles_pedido dp WHERE dp.pedido_id = p.id) as total_productos
                FROM pedidos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                ORDER BY p.fecha_pedido DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /** Pedidos filtrados por usuario (para el panel del cliente). */
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

    /** Líneas de un pedido (detalles) con datos del encabezado. */
    public function getDetalles($pedidoId) {
        $sql = "SELECT dp.*, p.numero_pedido, p.nombre_cliente, p.estado, p.fecha_pedido, p.total
                FROM detalles_pedido dp
                JOIN pedidos p ON dp.pedido_id = p.id
                WHERE dp.pedido_id = :pedido_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pedido_id' => $pedidoId]);
        return $stmt->fetchAll();
    }

    /** Pedido único con datos del usuario (nombre, email, teléfono). */
    public function getById($pedidoId) {
        $sql = "SELECT p.*, u.nombre as nombre_usuario, u.email as email_usuario, u.telefono as telefono_usuario
                FROM pedidos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                WHERE p.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $pedidoId]);
        return $stmt->fetch();
    }

    // Whitelist de estados válidos del ciclo de vida de un pedido
    const ESTADOS_VALIDOS = ['pendiente', 'confirmado', 'procesando', 'listo', 'asignado', 'aceptado', 'recogido', 'en_camino', 'entregado', 'cancelado', 'reembolsado'];

    /**
     * Actualiza el estado de un pedido validando contra ESTADOS_VALIDOS.
     * Lanza excepción si el estado no está en la whitelist.
     */
    public function updateEstado($pedidoId, $estado) {
        $estado = strtolower(trim($estado));
        if (!in_array($estado, self::ESTADOS_VALIDOS)) {
            throw new \Exception("Estado inválido: {$estado}. Estados permitidos: " . implode(', ', self::ESTADOS_VALIDOS));
        }
        $sql = "UPDATE pedidos SET estado = :estado WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['estado' => $estado, 'id' => $pedidoId]);
    }

    /**
     * Pedido completo: encabezado + items (con precio actual y stock del producto).
     * @return array<string, mixed>|null
     */
    public function getDetallesCompletos($pedidoId) {
        $sql = "SELECT p.*, 
                u.nombre as nombre_usuario, u.email as email_usuario, u.telefono as telefono_usuario,
                u.cedula as cedula_usuario
                FROM pedidos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                WHERE p.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $pedidoId]);
        $pedido = $stmt->fetch();

        if (!$pedido) return null;

        $sqlItems = "SELECT dp.*, pr.precio as precio_actual, pr.stock_total
                     FROM detalles_pedido dp
                     LEFT JOIN productos pr ON dp.producto_id = pr.id
                     WHERE dp.pedido_id = :pedido_id";
        $stmtItems = $this->db->prepare($sqlItems);
        $stmtItems->execute(['pedido_id' => $pedidoId]);
        $pedido['items'] = $stmtItems->fetchAll();

        return $pedido;
    }
}
