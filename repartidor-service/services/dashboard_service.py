"""Servicio de Dashboard del Repartidor — acceso directo a MySQL de ANGELOW.

Lee/escribe directamente en la BD de ANGELOW (angelow_db) vía pymysql,
siguiendo el mismo patrón best-effort que AngelowClientAdapter. Decodifica
el JWT emitido por ANGELOW para identificar al repartidor.
"""
import logging
import time

import jwt
import pymysql
from pymysql.cursors import DictCursor

from config import ConfigManager

logger = logging.getLogger("dashboard")

TRANSICIONES_PERMITIDAS = {
    "asignado": ["aceptado", "recogido", "en_camino", "entregado"],
    "aceptado": ["recogido", "en_camino", "entregado"],
    "recogido": ["en_camino", "entregado"],
    "en_camino": ["entregado"],
}


class DashboardService:
    def __init__(self):
        self.cfg = ConfigManager()

    def _connect(self):
        return pymysql.connect(
            host=self.cfg.ANGELOW_DB_HOST,
            port=self.cfg.ANGELOW_DB_PORT,
            user=self.cfg.ANGELOW_DB_USER,
            password=self.cfg.ANGELOW_DB_PASS,
            database=self.cfg.ANGELOW_DB_NAME,
            charset="utf8mb4",
            cursorclass=DictCursor,
        )

    # ── auth ──────────────────────────────────────────────────────────
    def decode_token(self, token: str):
        secret = self.cfg.get_angelow_jwt_secret()
        if not secret:
            return None
        try:
            payload = jwt.decode(
                token, secret, algorithms=["HS256"],
                options={"verify_sub": False},
            )
            if payload.get("exp", 0) < time.time():
                return None
            return payload
        except Exception:
            return None

    def get_repartidor(self, repartidor_id: int):
        conn = self._connect()
        try:
            with conn.cursor() as cur:
                cur.execute(
                    "SELECT id, nombre, apellido, email, telefono, estado, "
                    "tipo_vehiculo, placa_vehiculo, total_entregas, calificacion_promedio "
                    "FROM usuarios WHERE id = %s AND rol = 'repartidor'",
                    [repartidor_id],
                )
                return cur.fetchone()
        finally:
            conn.close()

    # ── resumen ───────────────────────────────────────────────────────
    def get_resumen(self, repartidor_id: int) -> dict:
        conn = self._connect()
        try:
            with conn.cursor() as cur:
                cur.execute(
                    "SELECT "
                    "  COALESCE((SELECT SUM(he.ganancia) FROM historial_entregas he "
                    "    WHERE he.repartidor_id=%s AND DATE(he.fecha_entrega)=CURDATE()),0) "
                    "    AS hoy_ganancias,"
                    "  COUNT(CASE WHEN DATE(fecha_entrega_real)=CURDATE() THEN 1 END) "
                    "    AS hoy_pedidos,"
                    "  COUNT(CASE WHEN estado IN ('asignado','aceptado','recogido','en_camino') "
                    "    THEN 1 END) AS en_transito,"
                    "  COUNT(CASE WHEN estado='entregado' THEN 1 END) AS total_entregas "
                    "FROM pedidos WHERE repartidor_id=%s",
                    [repartidor_id, repartidor_id],
                )
                stats = cur.fetchone() or {}

                cur.execute(
                    "SELECT p.*, "
                    "  (SELECT COUNT(*) FROM detalles_pedido dp WHERE dp.pedido_id=p.id) "
                    "  AS total_productos "
                    "FROM pedidos p ORDER BY p.fecha_pedido DESC"
                )
                todos = cur.fetchall() or []

                cur.execute(
                    "SELECT p.*, "
                    "  (SELECT COUNT(*) FROM detalles_pedido dp WHERE dp.pedido_id=p.id) "
                    "  AS total_productos "
                    "FROM pedidos p "
                    "WHERE p.repartidor_id=%s "
                    "  AND p.estado IN ('asignado','aceptado','recogido','en_camino') "
                    "ORDER BY p.fecha_asignacion DESC",
                    [repartidor_id],
                )
                activos = cur.fetchall() or []

                cur.execute(
                    "SELECT p.*, "
                    "  h.observacion AS obs_repartidor, "
                    "  h.fecha_cambio AS fecha_observacion, "
                    "  hc.comentario_cliente "
                    "FROM pedidos p "
                    "LEFT JOIN historial_pedidos_repartidor h "
                    "  ON h.pedido_id=p.id AND h.repartidor_id=%s "
                    "  AND h.observacion IS NOT NULL AND h.observacion!='' "
                    "LEFT JOIN historial_entregas hc "
                    "  ON hc.pedido_id=p.id AND hc.repartidor_id=p.repartidor_id "
                    "WHERE p.repartidor_id=%s "
                    "  AND p.estado IN ('entregado','cancelado','rechazado') "
                    "ORDER BY COALESCE(p.fecha_entrega_real, p.fecha_pedido) DESC "
                    "LIMIT 50",
                    [repartidor_id, repartidor_id],
                )
                terminados = cur.fetchall() or []

                return {
                    "stats": {
                        "hoy_ganancias": float(stats.get("hoy_ganancias") or 0),
                        "hoy_pedidos": int(stats.get("hoy_pedidos") or 0),
                        "en_transito": int(stats.get("en_transito") or 0),
                        "total_entregas": int(stats.get("total_entregas") or 0),
                    },
                    "todos_los_pedidos": [
                        self._serialize(p, repartidor_id) for p in todos
                    ],
                    "mis_activos": [
                        self._serialize(p, repartidor_id) for p in activos
                    ],
                    "terminados": [
                        self._serialize(p, repartidor_id) for p in terminados
                    ],
                }
        finally:
            conn.close()

    # ── aceptar ───────────────────────────────────────────────────────
    def aceptar(self, repartidor_id: int, pedido_id: int) -> dict:
        conn = self._connect()
        try:
            with conn.cursor() as cur:
                cur.execute("SELECT * FROM pedidos WHERE id=%s", [pedido_id])
                p = cur.fetchone()
                if not p:
                    return {"success": False, "error": "Pedido no encontrado"}
                if p["repartidor_id"] is not None:
                    return {
                        "success": False,
                        "error": "Este pedido ya tiene repartidor asignado",
                    }
                if p["estado"] not in (
                    "pendiente", "confirmado", "procesando", "listo"
                ):
                    return {
                        "success": False,
                        "error": f'No se puede aceptar un pedido con estado "{p["estado"]}"',
                    }
                cur.execute(
                    "UPDATE pedidos SET estado='asignado', repartidor_id=%s, "
                    "fecha_asignacion=NOW(), "
                    "fecha_estimada_entrega=DATE_ADD(NOW(), "
                    "INTERVAL COALESCE(tiempo_estimado_minutos,30) MINUTE) "
                    "WHERE id=%s",
                    [repartidor_id, pedido_id],
                )
                cur.execute(
                    "INSERT INTO historial_pedidos_repartidor "
                    "(pedido_id, repartidor_id, estado_anterior, estado_nuevo) "
                    "VALUES (%s,%s,%s,'asignado')",
                    [pedido_id, repartidor_id, p["estado"]],
                )
                conn.commit()
                return {"success": True, "message": "Pedido aceptado"}
        except Exception as e:
            conn.rollback()
            logger.exception("aceptar: error pedido %s", pedido_id)
            return {"success": False, "error": str(e)}
        finally:
            conn.close()

    # ── rechazar ──────────────────────────────────────────────────────
    def rechazar(
        self, repartidor_id: int, pedido_id: int, motivo: str = ""
    ) -> dict:
        conn = self._connect()
        try:
            with conn.cursor() as cur:
                cur.execute("SELECT * FROM pedidos WHERE id=%s", [pedido_id])
                p = cur.fetchone()
                if not p:
                    return {"success": False, "error": "Pedido no encontrado"}
                if p["repartidor_id"] is not None:
                    return {
                        "success": False,
                        "error": "Este pedido ya tiene repartidor asignado",
                    }
                if p["estado"] not in (
                    "pendiente", "confirmado", "procesando", "listo"
                ):
                    return {
                        "success": False,
                        "error": f'No se puede rechazar un pedido con estado "{p["estado"]}"',
                    }
                obs = (motivo or "").strip()
                cur.execute(
                    "UPDATE pedidos SET estado='rechazado', "
                    "notas_internas=CONCAT(COALESCE(notas_internas,''), "
                    "'\\n[Rechazado] ', %s) WHERE id=%s",
                    [obs or "Sin motivo", pedido_id],
                )
                cur.execute(
                    "INSERT INTO historial_pedidos_repartidor "
                    "(pedido_id, repartidor_id, estado_anterior, estado_nuevo, "
                    " observacion) VALUES (%s,%s,%s,'rechazado',%s)",
                    [pedido_id, repartidor_id, p["estado"], obs or None],
                )
                conn.commit()
                return {"success": True, "message": "Pedido rechazado"}
        except Exception as e:
            conn.rollback()
            logger.exception("rechazar: error pedido %s", pedido_id)
            return {"success": False, "error": str(e)}
        finally:
            conn.close()

    # ── transicion ────────────────────────────────────────────────────
    def transicion(
        self,
        repartidor_id: int,
        pedido_id: int,
        nuevo_estado: str,
        observacion: str = "",
    ) -> dict:
        conn = self._connect()
        try:
            with conn.cursor() as cur:
                cur.execute("SELECT * FROM pedidos WHERE id=%s", [pedido_id])
                p = cur.fetchone()
                if not p:
                    return {"success": False, "error": "Pedido no encontrado"}
                if int(p["repartidor_id"] or 0) != repartidor_id:
                    return {
                        "success": False,
                        "error": "No tienes permisos para actualizar este pedido",
                    }
                estado_actual = p["estado"]
                permitidos = TRANSICIONES_PERMITIDAS.get(estado_actual, [])
                if nuevo_estado not in permitidos:
                    return {
                        "success": False,
                        "error": f"Transicion no permitida de '{estado_actual}' a '{nuevo_estado}'",
                    }
                extra = ""
                if nuevo_estado == "aceptado":
                    extra = ", fecha_aceptacion=NOW()"
                elif nuevo_estado in ("recogido", "en_camino"):
                    extra = ", fecha_recogida=NOW()"
                elif nuevo_estado == "entregado":
                    extra = ", fecha_entrega_real=NOW()"
                cur.execute(
                    f"UPDATE pedidos SET estado=%s{extra} WHERE id=%s",
                    [nuevo_estado, pedido_id],
                )
                cur.execute(
                    "INSERT INTO historial_pedidos_repartidor "
                    "(pedido_id, repartidor_id, estado_anterior, estado_nuevo, "
                    " observacion) VALUES (%s,%s,%s,%s,%s)",
                    [
                        pedido_id,
                        repartidor_id,
                        estado_actual,
                        nuevo_estado,
                        observacion or None,
                    ],
                )
                if nuevo_estado == "entregado":
                    ganancia = float(p.get("ganancias_repartidor") or 5000)
                    cur.execute(
                        "INSERT INTO historial_entregas "
                        "(pedido_id, repartidor_id, ganancia, "
                        " tiempo_entrega_minutos) "
                        "VALUES (%s,%s,%s,TIMESTAMPDIFF(MINUTE,%s,NOW()))",
                        [
                            pedido_id,
                            repartidor_id,
                            ganancia,
                            p["fecha_pedido"],
                        ],
                    )
                conn.commit()
                cur.execute("SELECT * FROM pedidos WHERE id=%s", [pedido_id])
                actualizado = cur.fetchone()
                return {
                    "success": True,
                    "pedido": self._serialize(actualizado, repartidor_id),
                }
        except Exception as e:
            conn.rollback()
            logger.exception("transicion: error pedido %s", pedido_id)
            return {"success": False, "error": str(e)}
        finally:
            conn.close()

    # ── rastreo ───────────────────────────────────────────────────────
    def get_rastreo(self, repartidor_id: int) -> dict:
        conn = self._connect()
        try:
            with conn.cursor() as cur:
                cur.execute(
                    "SELECT p.* FROM pedidos p "
                    "WHERE p.repartidor_id=%s "
                    "  AND p.estado IN ('asignado','aceptado','recogido','en_camino') "
                    "ORDER BY p.fecha_asignacion DESC",
                    [repartidor_id],
                )
                pedidos = cur.fetchall() or []
                resultado = []
                for p in pedidos:
                    cur.execute(
                        "SELECT latitud, longitud, velocidad_kmh, "
                        "  bateria_porcentaje, timestamp_ubicacion "
                        "FROM seguimiento_tiempo_real "
                        "WHERE pedido_id=%s ORDER BY timestamp_ubicacion DESC LIMIT 1",
                        [p["id"]],
                    )
                    loc = cur.fetchone()
                    s = self._serialize(p, repartidor_id)
                    if loc:
                        s["repartidor_ubicacion"] = {
                            "latitud": float(loc["latitud"]),
                            "longitud": float(loc["longitud"]),
                            "velocidad_kmh": float(loc.get("velocidad_kmh") or 0),
                            "bateria": int(loc.get("bateria_porcentaje") or 0),
                            "timestamp": str(loc["timestamp_ubicacion"]),
                        }
                    resultado.append(s)
                return {"success": True, "pedidos": resultado}
        finally:
            conn.close()

    def reportar_ubicacion(
        self,
        repartidor_id: int,
        pedido_id: int,
        lat: float,
        lng: float,
        velocidad: float = None,
        bateria: int = None,
    ) -> dict:
        conn = self._connect()
        try:
            with conn.cursor() as cur:
                cur.execute(
                    "INSERT INTO seguimiento_tiempo_real "
                    "(pedido_id, repartidor_id, latitud, longitud, "
                    " velocidad_kmh, bateria_porcentaje) "
                    "VALUES (%s,%s,%s,%s,%s,%s)",
                    [pedido_id, repartidor_id, lat, lng, velocidad, bateria],
                )
                conn.commit()
                return {"success": True}
        except Exception as e:
            logger.warning("reportar_ubicacion: %s", e)
            return {"success": False, "error": str(e)}
        finally:
            conn.close()

    # ── serializacion ─────────────────────────────────────────────────
    def _serialize(self, p: dict, repartidor_id: int) -> dict:
        return {
            "id": p["id"],
            "numero_pedido": p["numero_pedido"],
            "estado": p["estado"],
            "prioridad": p.get("prioridad") or "normal",
            "cliente": p["nombre_cliente"],
            "telefono": p["telefono_cliente"],
            "email": p.get("email_cliente", ""),
            "direccion": p["direccion_envio"],
            "ciudad": p.get("ciudad", ""),
            "departamento": p.get("departamento", ""),
            "barrio": p.get("barrio", ""),
            "fecha_pedido": str(p["fecha_pedido"]) if p.get("fecha_pedido") else None,
            "fecha_asignacion": str(p["fecha_asignacion"]) if p.get("fecha_asignacion") else None,
            "fecha_entrega_real": str(p["fecha_entrega_real"]) if p.get("fecha_entrega_real") else None,
            "total": float(p.get("total") or 0),
            "costo_envio": float(p.get("costo_envio") or 0),
            "metodo_pago": p.get("metodo_pago", ""),
            "metodo_envio": p.get("metodo_envio", ""),
            "notas_cliente": p.get("notas_cliente") or "",
            "notas_internas": p.get("notas_internas") or "",
            "tiempo_estimado": int(p.get("tiempo_estimado_minutos") or 0),
            "repartidor_id": int(p["repartidor_id"]) if p.get("repartidor_id") else None,
            "destino": {
                "latitud": float(p["latitud_destino"]) if p.get("latitud_destino") else None,
                "longitud": float(p["longitud_destino"]) if p.get("longitud_destino") else None,
                "direccion": p.get("direccion_envio") or "",
            },
            "obs_repartidor": p.get("obs_repartidor") or None,
            "comentario_cliente": p.get("comentario_cliente") or None,
            "total_productos": int(p.get("total_productos") or 0),
        }
