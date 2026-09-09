"""Modelo SQLAlchemy: tabla historial_repartidores (auditoría)."""
from datetime import datetime

from sqlalchemy import Column, Integer, String, DateTime, Text

from database import Base


class HistorialRepartidor(Base):
    __tablename__ = "historial_repartidores"

    id = Column(Integer, primary_key=True, autoincrement=True)
    repartidor_id = Column(Integer, nullable=False, index=True)
    solicitud_id = Column(Integer, nullable=True)
    estado_anterior = Column(String(30), nullable=True)
    estado_nuevo = Column(String(30), nullable=False)
    accion = Column(String(50), nullable=False)
    motivo = Column(Text, nullable=True)
    observaciones = Column(Text, nullable=True)
    fecha_accion = Column(DateTime, default=datetime.utcnow)

    def __repr__(self):
        return f"<HistorialRepartidor {self.id} {self.accion} {self.estado_nuevo}>"