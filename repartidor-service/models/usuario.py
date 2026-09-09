"""Modelo SQLAlchemy: tabla usuarios (adaptado de angelow.sql)."""
from datetime import datetime

from sqlalchemy import Column, Integer, String, Boolean, DateTime, Text, DECIMAL

from database import Base


class Usuario(Base):
    __tablename__ = "usuarios"

    id = Column(Integer, primary_key=True, autoincrement=True)
    email = Column(String(255), unique=True, nullable=False, index=True)
    nombre = Column(String(100), nullable=False)
    apellido = Column(String(100), nullable=False)
    cedula = Column(String(50), unique=True, nullable=True)
    tipo_documento = Column(String(20), default="CC")
    fecha_nacimiento = Column(DateTime, nullable=True)
    telefono = Column(String(20), nullable=True)
    direccion = Column(Text, nullable=True)
    ciudad = Column(String(100), nullable=True)
    password_hash = Column(String(255), nullable=False)
    rol = Column(String(20), default="repartidor")
    tipo_vehiculo = Column(String(20), default="moto")
    placa_vehiculo = Column(String(20), nullable=True)
    numero_licencia = Column(String(100), nullable=True)
    categoria_licencia = Column(String(30), nullable=True)
    acepta_terminos = Column(Boolean, default=False)

    estado = Column(String(20), default="pendiente")
    motivo_suspension = Column(Text, nullable=True)
    fecha_suspension = Column(DateTime, nullable=True)
    fecha_aprobacion = Column(DateTime, nullable=True)

    fecha_registro = Column(DateTime, default=datetime.utcnow)

    def __repr__(self):
        return f"<Usuario {self.id} {self.email}>"