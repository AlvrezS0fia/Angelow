"""Repositorio de Notificaciones (aviso a administradores)."""
from sqlalchemy.orm import Session

from models.notificacion import Notificacion
from repositories.base import RepositoryBase


class NotificacionRepository(RepositoryBase[Notificacion]):
    def __init__(self, db: Session):
        super().__init__(db)
        self.model = Notificacion

    def crear(
        self,
        usuario_id: int,
        tipo: str,
        titulo: str,
        mensaje: str | None = None,
        enlace: str | None = None,
        datos_adicionales: str | None = None,
    ) -> Notificacion:
        return self.create(
            Notificacion(
                usuario_id=usuario_id,
                tipo=tipo,
                titulo=titulo,
                mensaje=mensaje,
                enlace=enlace,
                datos_adicionales=datos_adicionales,
                leida=False,
            )
        )