"""= PATRÓN GOF: OBSERVER =====================================================
Efectos secundarios desacoplados del registro: notificar administradores y
escribir historial/auditoría. Se registran observadores que reaccionan a
eventos del flujo (nueva solicitud creada, cambio de estado).
"""
import json
import logging
from abc import ABC, abstractmethod

from repositories.documento_repository import DocumentoRepository
from repositories.historial_repository import HistorialRepository
from repositories.notificacion_repository import NotificacionRepository
from repositories.solicitud_repository import SolicitudRepository
from repositories.usuario_repository import UsuarioRepository

logger = logging.getLogger(__name__)


class RegistrationObserver(ABC):
    @abstractmethod
    def on_registration_created(self, user, solicitud) -> None:
        raise NotImplementedError


class AdminNotificationObserver(RegistrationObserver):
    """INSERT INTO notificaciones para cada administrador activo."""

    TIPO_SOLICITUD = "solicitud_repartidor"

    def __init__(self, notificacion_repo: NotificacionRepository, usuario_repo: UsuarioRepository):
        self.notificacion_repo = notificacion_repo
        self.usuario_repo = usuario_repo

    def on_registration_created(self, user, solicitud) -> None:
        admins = [
            u for u in self.usuario_repo.get_all()
            if u.rol == "administrador" and u.estado == "activo"
        ]
        nombre_completo = f"{user.nombre} {user.apellido}".strip()
        for admin in admins:
            try:
                self.notificacion_repo.crear(
                    usuario_id=admin.id,
                    tipo=self.TIPO_SOLICITUD,
                    titulo="Nueva solicitud de repartidor",
                    mensaje=f"{nombre_completo} ha enviado una solicitud para ser repartidor.",
                    enlace="/admin/repartidores",
                    datos_adicionales=json.dumps(
                        {"solicitud_id": solicitud.id, "usuario_id": user.id},
                        ensure_ascii=False,
                    ),
                )
            except Exception:  # noqa: BLE001  (error_log del PHP equivalente)
                continue


class HistoryLogObserver(RegistrationObserver):
    """INSERT INTO historial_repartidores (auditoría)."""

    def __init__(self, hist_repo: HistorialRepository):
        self.hist_repo = hist_repo

    def on_registration_created(self, user, solicitud) -> None:
        try:
            self.hist_repo.registrar(
                repartidor_id=user.id,
                solicitud_id=solicitud.id,
                accion="registro",
                estado_nuevo="pendiente",
                observaciones="Solicitud de registro creada",
            )
        except Exception:  # noqa: BLE001
            pass

    def on_status_changed(self, user_id: int, solicitud_id, estado_anterior, estado_nuevo, motivo=None) -> None:
        try:
            accion = {
                "aprobada": "aprobacion",
                "rechazada": "rechazo",
            }.get(estado_nuevo, "actualizacion_documentos")
            self.hist_repo.registrar(
                repartidor_id=user_id,
                solicitud_id=solicitud_id,
                accion=accion,
                estado_nuevo=estado_nuevo,
                estado_anterior=estado_anterior,
                observaciones=motivo,
            )
        except Exception:  # noqa: BLE001
            pass


class AngelowSyncObserver(RegistrationObserver):
    """Replica el registro en el MySQL del panel admin de ANGELOW (mejor esfuerzo).

    El facade inyecta el repositorio de documentos para copiar los archivos tal
    cual los guardó el microservicio, y la contraseña en claro viaja como
    atributo transitorio del usuario (user.angelow_plain_password) para generar
    un hash $2b$ compatible con password_verify de PHP.
    """

    def __init__(self, adapter, documento_repo: DocumentoRepository):
        self.adapter = adapter
        self.documento_repo = documento_repo

    def on_registration_created(self, user, solicitud) -> None:
        try:
            docs = self.documento_repo.por_solicitud(solicitud.id)
            plain = getattr(user, "angelow_plain_password", None)
            self.adapter.sincronizar_registro(user, solicitud, docs, plain)
        except Exception:  # noqa: BLE001
            logger.exception("AngelowSyncObserver: error al sincronizar %s", getattr(user, "email", "?"))


class SolicitudStatusSubject:
    """Subject observable para el cambio de estado (Observer)."""

    def __init__(self, solicitud_repo: SolicitudRepository, usuario_repo: UsuarioRepository):
        self.solicitud_repo = solicitud_repo
        self.usuario_repo = usuario_repo
        self._observers: list[HistoryLogObserver] = []

    def attach(self, observer: HistoryLogObserver) -> None:
        self._observers.append(observer)

    def cambiar_estado(self, solicitud_id: int, estado: str, motivo=None, obs=None) -> dict:
        solicitud = self.solicitud_repo.get_by_id(solicitud_id)
        if not solicitud:
            raise ValueError("Solicitud no encontrada")
        estado_anterior = solicitud.estado
        solicitud = self.solicitud_repo.cambiar_estado(
            solicitud, estado, motivo_rechazo=motivo if estado == "rechazada" else None,
            observaciones=obs,
        )
        user = self.usuario_repo.get_by_id(solicitud.usuario_id)
        if user:
            user.estado = "activo" if estado == "aprobada" else user.estado
            from datetime import datetime
            if estado == "aprobada":
                user.fecha_aprobacion = datetime.utcnow()
            self.usuario_repo.update(user)
        for observer in self._observers:
            observer.on_status_changed(
                solicitud.usuario_id, solicitud.id, estado_anterior, estado, motivo
            )
        return {
            "id": solicitud.id,
            "estado": solicitud.estado,
            "estado_anterior": estado_anterior,
        }