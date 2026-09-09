"""Modelo SQLAlchemy: tabla vehiculos_repartidores."""
from datetime import datetime

from sqlalchemy import Column, Integer, String, Boolean, DateTime

from database import Base


class VehiculoRepartidor(Base):
    __tablename__ = "vehiculos_repartidores"

    id = Column(Integer, primary_key=True, autoincrement=True)
    repartidor_id = Column(Integer, nullable=False, index=True)
    tipo_vehiculo = Column(String(20), nullable=False)
    placa = Column(String(20), nullable=False)
    numero_licencia_transito = Column(String(100), nullable=True)
    activo = Column(Boolean, default=True)
    fecha_registro = Column(DateTime, default=datetime.utcnow)
    fecha_actualizacion = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    def __repr__(self):
        return f"<VehiculoRepartidor {self.id} {self.tipo_vehiculo} {self.placa}>"