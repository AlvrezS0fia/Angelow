"""Modelo SQLAlchemy: tabla documentos."""
from datetime import datetime

from sqlalchemy import Column, Integer, String, DateTime, Date, Text, BigInteger

from database import Base


class Documento(Base):
    __tablename__ = "documentos"

    id = Column(Integer, primary_key=True, autoincrement=True)
    repartidor_id = Column(Integer, nullable=False, index=True)
    solicitud_id = Column(Integer, nullable=True)
    tipo = Column(String(50), nullable=False)
    numero_documento = Column(String(100), nullable=True)
    archivo_url = Column(String(500), nullable=False)
    nombre_archivo = Column(String(255), nullable=True)
    mime_type = Column(String(100), nullable=True)
    tamano_bytes = Column(BigInteger, nullable=True)
    fecha_expedicion = Column(Date, nullable=True)
    fecha_inicio = Column(Date, nullable=True)
    fecha_vencimiento = Column(Date, nullable=True)
    estado = Column(String(20), default="pendiente")
    observaciones = Column(Text, nullable=True)
    revisado_por = Column(Integer, nullable=True)
    fecha_revision = Column(DateTime, nullable=True)
    fecha_subida = Column(DateTime, default=datetime.utcnow)
    fecha_actualizacion = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    def __repr__(self):
        return f"<Documento {self.id} {self.tipo} {self.estado}>"