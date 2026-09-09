"""= PATRÓN GOF: ADAPTER ========================================================
AngelowClientAdapter conecta el microservicio con el panel admin de ANGELOW.
Replica la escritura que haría RepartidorRegistroController::crearSolicitud
(PHP) insertando directamente en MySQL (angelow_db) y copiando los documentos
a public/uploads/documentos del proyecto Angelow, de modo que el dashboard del
admin (que lee SOLO MySQL) muestre la solicitud y permita aprobarla/rechazarla.

Política: MEJOR ESFUERZO. Cualquier fallo (MySQL apagado, sin BD, duplicado,
permisos de archivo) se registra en el log y NO bloquea el registro del
microservicio (que sigue siendo la fuente local de verdad en SQLite).
"""
import json
import logging
import os
import secrets
import shutil

import bcrypt
import pymysql
from pymysql.cursors import DictCursor

from config import ConfigManager

logger = logging.getLogger("angelow_sync")

MIME_EXT_MAP = {
    "application/pdf": "pdf",
    "image/jpeg": "jpg",
    "image/png": "png",
}


class AngelowClientAdapter:
    def __init__(self, cfg: ConfigManager = None):
        self.cfg = cfg or ConfigManager()

    # ── conexión ─────────────────────────────────────────────────────────
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

    # ── entrada pública ──────────────────────────────────────────────────
    def sincronizar_registro(self, usuario, solicitud, documentos, plain_password) -> bool:
        """Replica el registro de un repartidor en el MySQL de ANGELOW.

        `usuario`/`solicitud` son los objetos SQLAlchemy del microservicio ya
        persistidos; `documentos` la lista de Documento del repositorio local.
        Retorna True si se sincronizó, False si no (best effort, no lanza).
        """
        if not plain_password:
            logger.warning("Angelow sync: sin contraseña en claro para %s; se omite", usuario.email)
            return False

        conn = self._connect()
        copied_files = []
        try:
            with conn.cursor() as cur:
                user_id = self._upsert_usuario(cur, usuario, plain_password)
                solicitud_id = self._insertar_solicitud(cur, usuario, solicitud, user_id)
                self._insertar_vehiculo(cur, usuario, solicitud, user_id)
                self._insertar_historial(cur, user_id, solicitud_id)
                copied_files = self._copiar_guardar_documentos(
                    cur, solicitud_id, user_id, documentos
                )
                self._notificar_administradores(
                    cur, f"{usuario.nombre} {usuario.apellido}".strip(),
                    solicitud_id, user_id,
                )
                conn.commit()
            return True
        except AngelowSyncSkip as exc:
            try:
                conn.rollback()
            except Exception:  # noqa: BLE001
                pass
            logger.warning("Angelow sync: %s", exc)
            return False
        except Exception:  # noqa: BLE001  (best effort global)
            logger.exception(
                "Angelow sync: falló la sincronización de %s (rollback + limpieza)",
                getattr(usuario, "email", "?"),
            )
            try:
                conn.rollback()
            except Exception:  # noqa: BLE001
                pass
            for path in copied_files:
                try:
                    if os.path.isfile(path):
                        os.remove(path)
                except Exception:  # noqa: BLE001
                    pass
            return False
        finally:
            try:
                conn.close()
            except Exception:  # noqa: BLE001
                pass

    # ── usuarios ─────────────────────────────────────────────────────────
    def _upsert_usuario(self, cur, usuario, plain_password) -> int:
        """Crea el usuario en Angelow (o lo reactiva si es re-registro)."""
        password_hash_2b = bcrypt.hashpw(
            plain_password.encode("utf-8"), bcrypt.gensalt()
        ).decode("utf-8")
        telefono = usuario.telefono or ""
        numdoc = usuario.cedula or usuario.numero_documento or ""
        tipodoc = usuario.tipo_documento or "CC"
        tipo_vehiculo = usuario.tipo_vehiculo or "moto"
        placa = (usuario.placa_vehiculo or "").upper().replace("-", "")

        cur.execute("SELECT id, estado FROM usuarios WHERE email = %s", [usuario.email])
        row = cur.fetchone()
        if row:
            cur.execute(
                "SELECT estado FROM solicitudes_repartidores "
                "WHERE usuario_id = %s ORDER BY fecha_solicitud DESC LIMIT 1",
                [row["id"]],
            )
            ultima = cur.fetchone()
            es_reregistro = (
                row["estado"] in ("inactivo", "eliminado")
                and ultima is not None
                and ultima["estado"] == "rechazada"
            )
            if not es_reregistro:
                logger.warning(
                    "Angelow sync: el correo %s ya existe en Angelow (estado=%s); se omite",
                    usuario.email, row["estado"],
                )
                raise AngelowSyncSkip("Correo ya registrado en Angelow")

            user_id = row["id"]
            cur.execute(
                "UPDATE usuarios SET nombre = %s, apellido = %s, password_hash = %s, "
                "telefono = %s, tipo_documento = %s, tipo_vehiculo = %s, "
                "placa_vehiculo = %s, direccion = %s, ciudad = %s, "
                "estado = 'pendiente', motivo_suspension = NULL, "
                "fecha_suspension = NULL WHERE id = %s",
                [usuario.nombre, usuario.apellido, password_hash_2b, telefono,
                 tipodoc, tipo_vehiculo, placa or None,
                 usuario.direccion or None, usuario.ciudad or None, user_id],
            )
            cur.execute(
                "UPDATE usuarios SET cedula = %s, fecha_nacimiento = %s WHERE id = %s",
                [numdoc or None, usuario.fecha_nacimiento, user_id],
            )
            return user_id

        cur.execute(
            "INSERT INTO usuarios (email, nombre, apellido, password_hash, rol, "
            "telefono, tipo_documento, tipo_vehiculo, placa_vehiculo, estado, "
            "acepta_terminos, fecha_registro) "
            "VALUES (%s, %s, %s, %s, 'repartidor', %s, %s, %s, %s, 'pendiente', 1, NOW())",
            [usuario.email, usuario.nombre, usuario.apellido, password_hash_2b,
             telefono, tipodoc, tipo_vehiculo, placa or None],
        )
        user_id = cur.lastrowid
        cur.execute(
            "UPDATE usuarios SET cedula = %s, fecha_nacimiento = %s, "
            "direccion = %s, ciudad = %s WHERE id = %s",
            [numdoc or None, usuario.fecha_nacimiento,
             usuario.direccion or None, usuario.ciudad or None, user_id],
        )
        return user_id

    # ── solicitud ────────────────────────────────────────────────────────
    def _insertar_solicitud(self, cur, usuario, solicitud, user_id) -> int:
        placa = (solicitud.placa_vehiculo or usuario.placa_vehiculo or "").upper().replace("-", "")
        cur.execute(
            "INSERT INTO solicitudes_repartidores (usuario_id, nombres, apellidos, "
            "email, telefono, tipo_documento, numero_documento, tipo_vehiculo, "
            "placa_vehiculo, numero_licencia, categoria_licencia, tarjeta_propiedad, "
            "soat_vencimiento, tecnomecanica_vencimiento, vencimiento_licencia, "
            "direccion, ciudad, fecha_nacimiento, estado, fecha_solicitud) "
            "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, "
            "%s, %s, %s, %s, 'pendiente', NOW())",
            [
                user_id,
                solicitud.nombres,
                solicitud.apellidos,
                usuario.email,
                solicitud.telefono or usuario.telefono or "",
                usuario.tipo_documento or "CC",
                solicitud.numero_documento,
                solicitud.tipo_vehiculo or usuario.tipo_vehiculo,
                placa or None,
                solicitud.numero_licencia or "",
                solicitud.categoria_licencia or "",
                solicitud.tarjeta_propiedad or None,
                solicitud.soat_vencimiento,
                solicitud.tecnomecanica_vencimiento,
                solicitud.vencimiento_licencia,
                solicitud.direccion or usuario.direccion or None,
                solicitud.ciudad or usuario.ciudad or None,
                solicitud.fecha_nacimiento or usuario.fecha_nacimiento,
            ],
        )
        return cur.lastrowid

    # ── vehículo (solo si hay placa; omitir bicicleta) ───────────────────
    def _insertar_vehiculo(self, cur, usuario, solicitud, user_id) -> None:
        placa = (solicitud.placa_vehiculo or usuario.placa_vehiculo or "").upper().replace("-", "")
        if not placa:
            return
        try:
            cur.execute(
                "INSERT INTO vehiculos_repartidores (repartidor_id, tipo_vehiculo, "
                "placa, activo) VALUES (%s, %s, %s, 1) "
                "ON DUPLICATE KEY UPDATE tipo_vehiculo = VALUES(tipo_vehiculo), "
                "placa = VALUES(placa), fecha_actualizacion = NOW()",
                [user_id, (solicitud.tipo_vehiculo or usuario.tipo_vehiculo), placa],
            )
        except Exception:  # noqa: BLE001  (vehículo opcional en PHP también)
            logger.exception("Angelow sync: no se insertó el vehículo de %s", usuario.email)

    # ── historial ────────────────────────────────────────────────────────
    def _insertar_historial(self, cur, user_id, solicitud_id) -> None:
        cur.execute(
            "INSERT INTO historial_repartidores (repartidor_id, solicitud_id, "
            "accion, estado_nuevo, observaciones, fecha_accion) "
            "VALUES (%s, %s, 'registro', 'pendiente', "
            "'Solicitud de registro creada', NOW())",
            [user_id, solicitud_id],
        )

    # ── documentos (copia física + fila en Angelow) ──────────────────────
    def _copiar_guardar_documentos(self, cur, solicitud_id, user_id, documentos) -> list:
        copied = []
        uploads_dir = self.cfg.ANGELOW_UPLOADS_DIR
        os.makedirs(uploads_dir, exist_ok=True)

        for doc in documentos:
            src = os.path.join(self.cfg.BASE_DIR, doc.archivo_url.lstrip("./\\"))
            if not os.path.isfile(src):
                logger.warning(
                    "Angelow sync: faltó el archivo %s del documento %s; se omite",
                    src, doc.tipo,
                )
                continue
            ext = MIME_EXT_MAP.get(doc.mime_type or "")
            if not ext:
                ext = os.path.splitext(doc.archivo_url)[1].lstrip(".").lower() or "pdf"
            filename = f"{user_id}_{doc.tipo}_{secrets.token_hex(4)}.{ext}"
            dst = os.path.join(uploads_dir, filename)
            shutil.copy2(src, dst)
            copied.append(dst)

            cur.execute(
                "INSERT INTO documentos (repartidor_id, solicitud_id, tipo, "
                "archivo_url, numero_documento, fecha_vencimiento, estado) "
                "VALUES (%s, %s, %s, %s, %s, %s, 'pendiente')",
                [
                    user_id,
                    solicitud_id,
                    doc.tipo,
                    f"uploads/documentos/{filename}",
                    doc.numero_documento,
                    doc.fecha_vencimiento,
                ],
            )
        return copied

    # ── notificaciones a administradores activos ────────────────────────
    def _notificar_administradores(self, cur, nombre_completo, solicitud_id, user_id) -> None:
        cur.execute(
            "SELECT id FROM usuarios WHERE rol = 'administrador' AND estado = 'activo'"
        )
        for admin in cur.fetchall():
            try:
                cur.execute(
                    "INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, "
                    "enlace, datos_adicionales, leida, fecha_envio) "
                    "VALUES (%s, 'solicitud_repartidor', 'Nueva solicitud de repartidor', "
                    "%s, '/admin/repartidores', %s, 0, NOW())",
                    [
                        admin["id"],
                        f"{nombre_completo} ha enviado una solicitud para ser repartidor.",
                        json.dumps(
                            {"solicitud_id": solicitud_id, "usuario_id": user_id},
                            ensure_ascii=False,
                        ),
                    ],
                )
            except Exception:  # noqa: BLE001  (notificación opcional, como en PHP)
                logger.exception("Angelow sync: no se notificó al admin %s", admin["id"])


class AngelowSyncSkip(Exception):
    """Excepción interna para abortar la sincronización sin romper el flujo."""