"""Repositorio de Documentos."""
from typing import Optional

from sqlalchemy.orm import Session

from models.documento import Documento
from repositories.base import RepositoryBase


class DocumentoRepository(RepositoryBase[Documento]):
    def __init__(self, db: Session):
        super().__init__(db)
        self.model = Documento

    def crear(
        self,
        repartidor_id: int,
        solicitud_id: Optional[int],
        tipo: str,
        archivo_url: str,
        nombre_archivo: str,
        mime_type: str,
        tamano_bytes: int,
        numero_documento: Optional[str] = None,
        fecha_vencimiento: Optional[str] = None,
    ) -> Documento:
        from datetime import date

        doc = Documento(
            repartidor_id=repartidor_id,
            solicitud_id=solicitud_id,
            tipo=tipo,
            archivo_url=archivo_url,
            nombre_archivo=nombre_archivo,
            mime_type=mime_type,
            tamano_bytes=tamano_bytes,
            numero_documento=numero_documento,
            fecha_vencimiento=(
                date.fromisoformat(fecha_vencimiento) if fecha_vencimiento else None
            ),
            estado="pendiente",
        )
        return self.create(doc)

    def por_solicitud(self, solicitud_id: int) -> list[Documento]:
        return (
            self.db.query(Documento)
            .filter(Documento.solicitud_id == solicitud_id)
            .order_by(Documento.fecha_subida.desc())
            .all()
        )