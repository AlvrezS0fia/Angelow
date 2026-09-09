"""Repositorio de Solicitudes de Repartidor."""
from datetime import datetime
from typing import List, Optional

from sqlalchemy.orm import Session

from models.solicitud import SolicitudRepartidor
from repositories.base import RepositoryBase


class SolicitudRepository(RepositoryBase[SolicitudRepartidor]):
    def __init__(self, db: Session):
        super().__init__(db)
        self.model = SolicitudRepartidor

    def crear(self, datos: dict) -> SolicitudRepartidor:
        solicitud = SolicitudRepartidor(**datos)
        return self.create(solicitud)

    def find_by_email(self, email: str) -> Optional[SolicitudRepartidor]:
        return (
            self.db.query(SolicitudRepartidor)
            .filter(SolicitudRepartidor.email.ilike(email.strip()))
            .order_by(SolicitudRepartidor.fecha_solicitud.desc())
            .first()
        )

    def tiene_solicitud_anterior_rechazada(self, usuario_id: int) -> bool:
        ultima = (
            self.db.query(SolicitudRepartidor)
            .filter(SolicitudRepartidor.usuario_id == usuario_id)
            .order_by(SolicitudRepartidor.fecha_solicitud.desc())
            .first()
        )
        return bool(ultima and ultima.estado == "rechazada")

    def cancelar_rechazadas_de(self, usuario_id: int) -> None:
        canceled = (
            self.db.query(SolicitudRepartidor)
            .filter(
                SolicitudRepartidor.usuario_id == usuario_id,
                SolicitudRepartidor.estado == "rechazada",
            )
            .all()
        )
        for s in canceled:
            s.estado = "cancelada"
        self.commit()

    def listar(self, estado: Optional[str] = None) -> List[SolicitudRepartidor]:
        q = self.db.query(SolicitudRepartidor)
        if estado:
            q = q.filter(SolicitudRepartidor.estado == estado)
        return q.order_by(SolicitudRepartidor.fecha_solicitud.desc()).all()

    def cambiar_estado(
        self,
        solicitud: SolicitudRepartidor,
        estado: str,
        motivo_rechazo: Optional[str] = None,
        observaciones: Optional[str] = None,
    ) -> SolicitudRepartidor:
        solicitud.estado = estado
        solicitud.motivo_rechazo = motivo_rechazo
        solicitud.observaciones = observaciones
        solicitud.fecha_respuesta = datetime.utcnow()
        return self.update(solicitud)