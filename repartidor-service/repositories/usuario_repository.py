"""Repositorio de Usuario. Replica findByEmail/create/reactivar del PHP."""
from datetime import datetime
from typing import Optional

from sqlalchemy.orm import Session

from models.usuario import Usuario
from repositories.base import RepositoryBase


class UsuarioRepository(RepositoryBase[Usuario]):
    def __init__(self, db: Session):
        super().__init__(db)
        self.model = Usuario

    def find_by_email(self, email: str) -> Optional[Usuario]:
        return (
            self.db.query(Usuario)
            .filter(Usuario.email.ilike(email.strip()))
            .first()
        )

    def find_by_cedula(self, cedula: str) -> Optional[Usuario]:
        return self.db.query(Usuario).filter(Usuario.cedula == cedula).first()

    def crear(
        self,
        email: str,
        nombre: str,
        apellido: str,
        password_hash: str,
        telefono: str,
        tipodoc: str,
        numdoc: str,
        fecha_nacimiento: Optional[datetime],
        direccion: str,
        ciudad: str,
        vehiculo: str,
        placa: str,
        licencia: str,
        catlicencia: str,
    ) -> Usuario:
        user = Usuario(
            email=email,
            nombre=nombre,
            apellido=apellido,
            password_hash=password_hash,
            rol="repartidor",
            telefono=telefono,
            tipo_documento=tipodoc,
            cedula=numdoc,
            fecha_nacimiento=fecha_nacimiento,
            direccion=direccion or None,
            ciudad=ciudad or None,
            tipo_vehiculo=vehiculo,
            placa_vehiculo=placa or None,
            numero_licencia=licencia or None,
            categoria_licencia=catlicencia or None,
            estado="pendiente",
            acepta_terminos=True,
        )
        return self.create(user)

    def reactivar(
        self,
        existing: Usuario,
        nombre: str,
        apellido: str,
        password_hash: str,
        telefono: str,
        tipodoc: str,
        numdoc: str,
        fecha_nacimiento: Optional[datetime],
        direccion: str,
        ciudad: str,
        vehiculo: str,
        placa: str,
    ) -> Usuario:
        existing.nombre = nombre
        existing.apellido = apellido
        existing.password_hash = password_hash
        existing.telefono = telefono
        existing.tipo_documento = tipodoc
        existing.cedula = numdoc or None
        existing.fecha_nacimiento = fecha_nacimiento or None
        existing.direccion = direccion or None
        existing.ciudad = ciudad or None
        existing.tipo_vehiculo = vehiculo
        existing.placa_vehiculo = placa or None
        existing.estado = "pendiente"
        existing.motivo_suspension = None
        existing.fecha_suspension = None
        return self.update(existing)