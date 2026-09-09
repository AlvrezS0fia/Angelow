"""= PATRÓN GOF: FACTORY METHOD ===============================================
RepartidorFactory encapsula la decisión de crear un usuario nuevo o
reactivar uno existente (re-registro tras solicitud rechazada), replicando
la lógica condicional del controller PHP original.
"""
from typing import Optional

from models.usuario import Usuario
from repositories.usuario_repository import UsuarioRepository


class RepartidorFactory:
    """Fabrica/reutiliza la entidad Usuario según su estado previo."""

    @staticmethod
    def create_or_reactivate(
        usuario_repo: UsuarioRepository,
        existing_user: Optional[Usuario],
        datos: dict,
    ) -> Usuario:
        if existing_user:
            if existing_user.estado in ("inactivo", "eliminado"):
                if usuario_repo.db:
                    from repositories.solicitud_repository import SolicitudRepository

                    sol_repo = SolicitudRepository(usuario_repo.db)
                    if sol_repo.tiene_solicitud_anterior_rechazada(existing_user.id):
                        return usuario_repo.reactivar(
                            existing_user,
                            datos["nombres"],
                            datos["apellidos"],
                            datos["password_hash"],
                            datos["telefono"],
                            datos["tipodoc"],
                            datos["numdoc"],
                            datos.get("fecha_nacimiento"),
                            datos.get("direccion", ""),
                            datos.get("ciudad", ""),
                            datos["vehiculo"],
                            datos.get("placa", "") or "",
                        )
            raise ValueError("El correo ya está registrado")

        return usuario_repo.crear(
            datos["email"],
            datos["nombres"],
            datos["apellidos"],
            datos["password_hash"],
            datos["telefono"],
            datos["tipodoc"],
            datos["numdoc"],
            datos.get("fecha_nacimiento"),
            datos.get("direccion", ""),
            datos.get("ciudad", ""),
            datos["vehiculo"],
            datos.get("placa", "") or "",
            datos.get("licencia", ""),
            datos.get("catlicencia", ""),
        )