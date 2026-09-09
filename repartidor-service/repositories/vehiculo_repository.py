"""Repositorio de Vehículos de Repartidor."""
from datetime import datetime
from typing import Optional

from sqlalchemy.orm import Session

from models.vehiculo import VehiculoRepartidor
from repositories.base import RepositoryBase


class VehiculoRepository(RepositoryBase[VehiculoRepartidor]):
    def __init__(self, db: Session):
        super().__init__(db)
        self.model = VehiculoRepartidor

    def upsert(
        self,
        repartidor_id: int,
        tipo_vehiculo: str,
        placa: str,
        numero_licencia_transito: Optional[str] = None,
    ) -> VehiculoRepartidor:
        vehiculo = (
            self.db.query(VehiculoRepartidor)
            .filter(VehiculoRepartidor.repartidor_id == repartidor_id)
            .first()
        )
        if vehiculo:
            vehiculo.tipo_vehiculo = tipo_vehiculo
            vehiculo.placa = placa
            vehiculo.numero_licencia_transito = numero_licencia_transito
            vehiculo.activo = True
            vehiculo.fecha_actualizacion = datetime.utcnow()
            return self.update(vehiculo)

        vehiculo = VehiculoRepartidor(
            repartidor_id=repartidor_id,
            tipo_vehiculo=tipo_vehiculo,
            placa=placa,
            numero_licencia_transito=numero_licencia_transito,
            activo=True,
        )
        return self.create(vehiculo)