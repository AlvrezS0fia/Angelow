import mysql.connector
from mysql.connector import Error
from config import DB_CONFIG


def get_connection():
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        return conn
    except Error as e:
        print(f"Error de conexion MySQL: {e}")
        return None


class FacturaModel:

    def __init__(self):
        self.conn = get_connection()

    def _ensure_conn(self):
        if self.conn is None or not self.conn.is_connected():
            self.conn = get_connection()

    def _close(self):
        if self.conn and self.conn.is_connected():
            self.conn.close()

    def generar_numero_factura(self):
        self._ensure_conn()
        cursor = self.conn.cursor()
        anio = __import__('datetime').datetime.now().year
        cursor.execute(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(numero_factura, 10) AS UNSIGNED)), 0) + 1 FROM facturas WHERE numero_factura LIKE %s",
            (f"FAC-{anio}-%",)
        )
        total = cursor.fetchone()[0]
        cursor.close()
        return f"FAC-{anio}-{int(total):04d}"

    def crear_desde_pedido(self, pedido_id, admin_id=None):
        self._ensure_conn()
        cursor = self.conn.cursor(dictionary=True)

        cursor.execute("SELECT * FROM pedidos WHERE id = %s", (pedido_id,))
        pedido = cursor.fetchone()
        if not pedido:
            cursor.close()
            return None, "Pedido no encontrado"

        if pedido.get('factura_generada'):
            cursor.execute(
                "SELECT * FROM facturas WHERE pedido_id = %s", (pedido_id,))
            factura = cursor.fetchone()
            cursor.close()
            if factura:
                return factura, "Ya existe factura para este pedido"
            return None, "Pedido ya tiene factura asociada"

        cursor.execute(
            "SELECT * FROM detalles_pedido WHERE pedido_id = %s", (pedido_id,))
        detalles = cursor.fetchall()

        numero = self.generar_numero_factura()

        cursor.execute("""
            INSERT INTO facturas (
                numero_factura, pedido_id, usuario_id,
                nombre_cliente, email_cliente, telefono_cliente, cedula_cliente,
                direccion_envio, barrio, ciudad, departamento, destinatario,
                metodo_envio, costo_envio,
                subtotal, descuento, total, metodo_pago,
                estado, fecha_emision
            ) VALUES (
                %s, %s, %s,
                %s, %s, %s, %s,
                %s, %s, %s, %s, %s,
                %s, %s,
                %s, %s, %s, %s,
                'pendiente', NOW()
            )
        """, (
            numero, pedido_id, pedido['usuario_id'],
            pedido['nombre_cliente'], pedido['email_cliente'],
            pedido['telefono_cliente'], pedido.get('cedula_cliente'),
            pedido['direccion_envio'], pedido.get('barrio'),
            pedido['ciudad'], pedido['departamento'], pedido.get('destinatario'),
            pedido.get('metodo_envio', 'normal'), pedido.get('costo_envio', 0),
            pedido['subtotal'], pedido.get('descuento', 0), pedido['total'],
            pedido.get('metodo_pago'),
        ))

        factura_id = cursor.lastrowid

        for det in detalles:
            cursor.execute("""
                INSERT INTO facturas_detalle (
                    factura_id, producto_id, nombre_producto, cantidad,
                    precio_unitario, subtotal, talla, color, imagen_url
                ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
            """, (
                factura_id, det.get('producto_id'), det['nombre_producto'],
                det['cantidad'], det['precio_unitario'], det['subtotal'],
                det.get('talla'), det.get('color'), det.get('imagen_url')
            ))

        cursor.execute(
            "UPDATE pedidos SET factura_generada = 1 WHERE id = %s",
            (pedido_id,)
        )

        cursor.execute("""
            INSERT INTO facturas_historial (factura_id, estado_nuevo, cambiado_por, notas)
            VALUES (%s, 'pendiente', %s, 'Factura creada automaticamente')
        """, (factura_id, admin_id))

        self.conn.commit()

        cursor.execute("SELECT * FROM facturas WHERE id = %s", (factura_id,))
        factura = cursor.fetchone()
        cursor.close()
        return factura, None

    def get_all(self, estado=None):
        self._ensure_conn()
        cursor = self.conn.cursor(dictionary=True)
        query = """
            SELECT f.*, p.numero_pedido,
                   (SELECT COUNT(*) FROM facturas_detalle fd WHERE fd.factura_id = f.id) as total_items
            FROM facturas f
            LEFT JOIN pedidos p ON f.pedido_id = p.id
        """
        params = []
        if estado and estado != 'all':
            query += " WHERE f.estado = %s"
            params.append(estado)
        query += " ORDER BY f.fecha_emision DESC"
        cursor.execute(query, params)
        result = cursor.fetchall()
        cursor.close()
        return result

    def get_by_id(self, factura_id):
        self._ensure_conn()
        cursor = self.conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT f.*, p.numero_pedido,
                   p.metodo_pago as pedido_metodo_pago,
                   p.direccion_envio as pedido_direccion
            FROM facturas f
            LEFT JOIN pedidos p ON f.pedido_id = p.id
            WHERE f.id = %s
        """, (factura_id,))
        factura = cursor.fetchone()

        if factura:
            cursor.execute(
                "SELECT * FROM facturas_detalle WHERE factura_id = %s",
                (factura_id,))
            factura['detalles'] = cursor.fetchall()

            cursor.execute("""
                SELECT fh.*, u.nombre as cambiado_por_nombre
                FROM facturas_historial fh
                LEFT JOIN usuarios u ON fh.cambiado_por = u.id
                WHERE fh.factura_id = %s
                ORDER BY fh.fecha_cambio DESC
            """, (factura_id,))
            factura['historial'] = cursor.fetchall()

        cursor.close()
        return factura

    def get_by_pedido(self, pedido_id):
        self._ensure_conn()
        cursor = self.conn.cursor(dictionary=True)
        cursor.execute(
            "SELECT * FROM facturas WHERE pedido_id = %s", (pedido_id,))
        factura = cursor.fetchone()
        if factura:
            cursor.execute(
                "SELECT * FROM facturas_detalle WHERE factura_id = %s",
                (factura['id'],))
            factura['detalles'] = cursor.fetchall()
        cursor.close()
        return factura

    def cambiar_estado(self, factura_id, nuevo_estado, admin_id=None, notas=None):
        self._ensure_conn()
        cursor = self.conn.cursor(dictionary=True)

        cursor.execute("SELECT * FROM facturas WHERE id = %s", (factura_id,))
        factura = cursor.fetchone()
        if not factura:
            cursor.close()
            return None, "Factura no encontrada"

        estado_anterior = factura['estado']

        cursor.execute("""
            UPDATE facturas SET estado = %s, fecha_estado = NOW(), notas = COALESCE(%s, notas)
            WHERE id = %s
        """, (nuevo_estado, notas, factura_id))

        cursor.execute("""
            INSERT INTO facturas_historial (factura_id, estado_anterior, estado_nuevo, cambiado_por, notas)
            VALUES (%s, %s, %s, %s, %s)
        """, (factura_id, estado_anterior, nuevo_estado, admin_id, notas))

        self.conn.commit()

        cursor.execute("SELECT * FROM facturas WHERE id = %s", (factura_id,))
        factura_actualizada = cursor.fetchone()
        cursor.close()
        return factura_actualizada, None

    def marcar_enviado_correo(self, factura_id):
        self._ensure_conn()
        cursor = self.conn.cursor()
        cursor.execute("""
            UPDATE facturas SET enviado_correo = 1, fecha_envio_correo = NOW()
            WHERE id = %s
        """, (factura_id,))
        self.conn.commit()
        cursor.close()

    def get_stats(self):
        self._ensure_conn()
        cursor = self.conn.cursor(dictionary=True)
        stats = {}

        cursor.execute("SELECT COUNT(*) as total FROM facturas")
        stats['total'] = cursor.fetchone()['total']

        cursor.execute(
            "SELECT COUNT(*) as total FROM facturas WHERE estado = 'autorizada'")
        stats['autorizadas'] = cursor.fetchone()['total']

        cursor.execute(
            "SELECT COUNT(*) as total FROM facturas WHERE estado = 'cancelada'")
        stats['canceladas'] = cursor.fetchone()['total']

        cursor.execute(
            "SELECT COUNT(*) as total FROM facturas WHERE estado = 'devolucion'")
        stats['devoluciones'] = cursor.fetchone()['total']

        cursor.execute(
            "SELECT COALESCE(SUM(total), 0) as total FROM facturas WHERE estado = 'autorizada'")
        stats['total_ingresos'] = float(cursor.fetchone()['total'])

        cursor.execute(
            "SELECT COUNT(*) as total FROM facturas WHERE estado = 'pendiente'")
        stats['pendientes'] = cursor.fetchone()['total']

        cursor.close()
        return stats
