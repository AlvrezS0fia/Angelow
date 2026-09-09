"""Modelo SQLAlchemy: tabla notificaciones (notificación a administradores)."""
from datetime import datetime

from sqlalchemy import Column, Integer, String, DateTime, Text, Boolean

from database import Base


class Notificacion(Base):
    __tablename__ = "notificaciones"

    id = Column(Integer, primary_key=True, autoincrement=True)
    usuario_id = Column(Integer, nullable=False, index=True)
    tipo = Column(String(50), nullable=False)
    titulo = Column(String(255), nullable=False)
    mensaje = Column(Text, nullable=True)
    enlace = Column(String(255), nullable=True)
    datos_adicionales = Column(Text, nullable=True)
    leida = Column(Boolean, default=False)
    fecha_envio = Column(DateTime, default=datetime.utcnow)

    def __repr__(self):
        return f"<Notificacion {self.id} {self.tipo}>"