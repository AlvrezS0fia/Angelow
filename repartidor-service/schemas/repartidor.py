"""Schemas Pydantic para el flujo de registro de repartidor."""
from datetime import date, datetime
from typing import Optional, List

from pydantic import BaseModel, EmailStr, Field


class RegistroRepartidorRequest(BaseModel):
    nombres: str = Field(min_length=2, max_length=100)
    apellidos: str = Field(min_length=2, max_length=100)
    tipodoc: str
    numdoc: str
    celular: str
    correo: EmailStr
    pass_: str = Field(min_length=8, alias="pass")
    pass_confirm: str = Field(min_length=8, alias="pass_confirm")
    fecha_nacimiento: Optional[str] = None
    direccion: Optional[str] = None
    ciudad: Optional[str] = None

    vehiculo: str
    placa: Optional[str] = None
    licencia: Optional[str] = None
    catlicencia: Optional[str] = None
    tarjeta: Optional[str] = None
    vensoat: Optional[str] = None
    venlicencia: Optional[str] = None
    ventechno: Optional[str] = None

    acepta_terminos: int = 0
    acepta_privacidad: int = 0

    class Config:
        populate_by_name = True


class RegistroRepartidorResponse(BaseModel):
    success: bool
    message: str
    pending: bool = True
    redirect: Optional[str] = None
    files_uploaded: List[str] = Field(default_factory=list)


class SolicitudPublica(BaseModel):
    estado: str
    motivo: Optional[str] = None
    fecha_solicitud: Optional[datetime] = None
    fecha_respuesta: Optional[datetime] = None


class EstadoSolicitudResponse(BaseModel):
    success: bool
    solicitud: Optional[SolicitudPublica] = None
    message: Optional[str] = None


class ListadoSolicitud(BaseModel):
    id: int
    nombres: str
    apellidos: str
    email: EmailStr
    telefono: Optional[str] = None
    tipo_documento: Optional[str] = None
    numero_documento: Optional[str] = None
    tipo_vehiculo: Optional[str] = None
    placa_vehiculo: Optional[str] = None
    estado: str
    fecha_solicitud: datetime


class CambioEstadoRequest(BaseModel):
    estado: str
    motivo_rechazo: Optional[str] = None
    observaciones: Optional[str] = None
    administrador_id: Optional[int] = None