"""Repositorio de Historial de Repartidores (auditoría)."""
from sqlalchemy.orm import Session

from models.historial import HistorialRepartidor
from repositories.base import RepositoryBase


class HistorialRepository(RepositoryBase[HistorialRepartidor]):
    def __init__(self, db: Session):
        super().__init__(db)
        self.model = HistorialRepartidor

    def registrar(
        self,
        repartidor_id: int,
        solicitud_id,
        accion: str,
        estado_nuevo: str,
        estado_anterior: str | None = None,
        observaciones: str | None = None,
    ) -> HistorialRepartidor:
        return self.create(
            HistorialRepartidor(
                repartidor_id=repartidor_id,
                solicitud_id=solicitud_id,
                accion=accion,
                estado_nuevo=estado_nuevo,
                estado_anterior=estado_anterior,
                observaciones=observaciones,
            )
        )