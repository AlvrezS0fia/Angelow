"""= PATRÓN GOF: BUILDER =====================================================
SolicitudBuilder construye paso a paso el dict de una solicitud de
repartidor. Separa la construcción (con campos condicionales) del
representado final.
"""
from datetime import date, datetime
from typing import Optional


class SolicitudBuilder:
    def __init__(self):
        self._data = {}

    def set_personal(
        self,
        usuario_id: int,
        nombres: str,
        apellidos: str,
        email: str,
        telefono: str,
        tipodoc: str,
        numdoc: str,
        fecha_nacimiento: Optional[str],
        direccion: str,
        ciudad: str,
    ) -> "SolicitudBuilder":
        self._data.update(
            {
                "usuario_id": usuario_id,
                "nombres": nombres,
                "apellidos": apellidos,
                "email": email,
                "telefono": telefono,
                "tipo_documento": tipodoc,
                "numero_documento": numdoc,
                "fecha_nacimiento": self._parse_date(fecha_nacimiento),
                "direccion": direccion or None,
                "ciudad": ciudad or None,
            }
        )
        return self

    def set_vehicle(
        self,
        tipo_vehiculo: str,
        placa: Optional[str],
        numero_licencia: Optional[str],
        categoria_licencia: Optional[str],
        tarjeta_propiedad: Optional[str],
        soat_vencimiento: Optional[str],
        tecnomecanica_vencimiento: Optional[str],
        vencimiento_licencia: Optional[str] = None,
    ) -> "SolicitudBuilder":
        self._data.update(
            {
                "tipo_vehiculo": tipo_vehiculo,
                "placa_vehiculo": placa or None,
                "numero_licencia": numero_licencia or None,
                "categoria_licencia": categoria_licencia or None,
                "tarjeta_propiedad": tarjeta_propiedad or None,
                "soat_vencimiento": self._parse_date(soat_vencimiento),
                "tecnomecanica_vencimiento": self._parse_date(tecnomecanica_vencimiento),
                "vencimiento_licencia": self._parse_date(vencimiento_licencia),
            }
        )
        return self

    def set_estado(self, estado: str = "pendiente") -> "SolicitudBuilder":
        self._data["estado"] = estado
        return self

    def build(self) -> dict:
        self._data.setdefault("estado", "pendiente")
        return dict(self._data)

    @staticmethod
    def _parse_date(value: Optional[str]):
        if not value:
            return None
        try:
            return date.fromisoformat(str(value)[:10])
        except (ValueError, TypeError):
            return None