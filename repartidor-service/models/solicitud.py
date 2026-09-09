"""Modelo SQLAlchemy: tabla solicitudes_repartidores."""
from datetime import datetime

from sqlalchemy import Column, Integer, String, DateTime, Text, Date

from database import Base


class SolicitudRepartidor(Base):
    __tablename__ = "solicitudes_repartidores"

    id = Column(Integer, primary_key=True, autoincrement=True)
    usuario_id = Column(Integer, nullable=False, index=True)
    nombres = Column(String(100), nullable=False)
    apellidos = Column(String(100), nullable=False)
    tipo_documento = Column(String(20), default="CC")
    numero_documento = Column(String(50), nullable=False, index=True)
    fecha_nacimiento = Column(Date, nullable=True)
    telefono = Column(String(20), nullable=True)
    email = Column(String(255), nullable=False)
    direccion = Column(Text, nullable=True)
    ciudad = Column(String(100), nullable=True)
    tipo_vehiculo = Column(String(20), nullable=True)
    placa_vehiculo = Column(String(20), nullable=True)
    numero_licencia = Column(String(100), nullable=True)
    categoria_licencia = Column(String(30), nullable=True)
    tarjeta_propiedad = Column(String(50), nullable=True)
    soat_vencimiento = Column(Date, nullable=True)
    tecnomecanica_vencimiento = Column(Date, nullable=True)
    vencimiento_licencia = Column(Date, nullable=True)

    estado = Column(String(20), default="pendiente")
    motivo_rechazo = Column(Text, nullable=True)
    observaciones = Column(Text, nullable=True)

    fecha_solicitud = Column(DateTime, default=datetime.utcnow)
    fecha_respuesta = Column(DateTime, nullable=True)
    fecha_actualizacion = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    def __repr__(self):
        return f"<SolicitudRepartidor {self.id} {self.email} [{self.estado}]>"