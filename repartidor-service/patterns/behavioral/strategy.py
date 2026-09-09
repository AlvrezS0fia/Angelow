"""= PATRÓN GOF: STRATEGY =====================================================
Estrategias de validación intercambiables por etapa del registro.
Cada estrategia devuelve una lista de mensajes de error (vacía si es válido).
Replica las reglas de validación del controller PHP original, incluidas las
condicionales por tipo de vehículo.
"""
import re
from abc import ABC, abstractmethod
from datetime import datetime, date

from config import ConfigManager


class ValidationStrategy(ABC):
    """Contrato: validate(data) -> list[str] de errores."""

    @abstractmethod
    def validate(self, data: dict) -> list[str]:
        raise NotImplementedError


class PersonalDataValidation(ValidationStrategy):
    """Paso 1: nombres, documento, celular, correo, contraseña."""

    _cfg = ConfigManager()

    def validate(self, data: dict) -> list[str]:
        errors = []
        nombre = str(data.get("nombres", "")).strip()
        apellido = str(data.get("apellidos", "")).strip()
        email = str(data.get("correo", "")).strip()
        celular = re.sub(r"\s+", "", str(data.get("celular", "")))
        tipodoc = str(data.get("tipodoc", ""))
        numdoc = str(data.get("numdoc", "")).strip()
        passwd = str(data.get("pass", ""))
        passwd2 = str(data.get("pass_confirm", ""))

        if not nombre or len(nombre) < 2:
            errors.append("Nombres requeridos (mín. 2 caracteres)")
        elif not re.match(r"^[a-zA-ZáéíóúñÁÉÍÓÚÑ\s]+$", nombre):
            errors.append("Nombres solo pueden contener letras")

        if not apellido or len(apellido) < 2:
            errors.append("Apellidos requeridos (mín. 2 caracteres)")
        elif not re.match(r"^[a-zA-ZáéíóúñÁÉÍÓÚÑ\s]+$", apellido):
            errors.append("Apellidos solo pueden contener letras")

        if not email or not re.match(r"^[^@\s]+@[^@\s]+\.[^@\s]+$", email):
            errors.append("Correo electrónico inválido")

        if not celular or not re.match(r"^3\d{9}$", celular):
            errors.append("Celular inválido (debe iniciar en 3 y tener 10 dígitos)")

        if tipodoc not in self._cfg.TIPOS_DOCUMENTOS_VALIDOS:
            errors.append("Tipo de documento inválido")
        if not numdoc or not re.match(r"^\d{5,12}$", numdoc):
            errors.append("Número de documento inválido (5-12 dígitos)")

        if not passwd:
            errors.append("Contraseña requerida")
        else:
            if len(passwd) < 8:
                errors.append("Contraseña: mínimo 8 caracteres")
            if not re.search(r"[A-Z]", passwd):
                errors.append("Contraseña: al menos una mayúscula")
            if not re.search(r"[a-z]", passwd):
                errors.append("Contraseña: al menos una minúscula")
            if not re.search(r"[0-9]", passwd):
                errors.append("Contraseña: al menos un número")
            if not re.search(
                r"[!@#$%^&*()_+\-=\[\]{};':\"\\|,.<>\/?~´`]", passwd
            ):
                errors.append("Contraseña: al menos un carácter especial")
        if passwd != passwd2:
            errors.append("Las contraseñas no coinciden")

        return errors


class VehicleValidation(ValidationStrategy):
    """Paso 2: tipo de vehículo, placa (si no es bici), licencia, SOAT, tecnomecánica."""

    _cfg = ConfigManager()

    def validate(self, data: dict) -> list[str]:
        errors = []
        vehiculo = str(data.get("vehiculo", ""))
        placa = str(data.get("placa", "")).upper().replace("-", "").strip()
        licencia = str(data.get("licencia", "")).strip()
        catlicencia = str(data.get("catlicencia", "")).strip().upper()
        tarjeta = str(data.get("tarjeta", "")).strip()

        if vehiculo not in self._cfg.TIPOS_VEHICULO_VALIDOS:
            errors.append("Tipo de vehículo inválido")

        es_bici = vehiculo == "bicicleta"
        if not es_bici:
            if not placa or not re.match(r"^[A-Z]{3}\d{3}$|^[A-Z]{3}\d{2}[A-Z]$", placa):
                errors.append("Placa inválida (formato: ABC123 o ABC12D)")
            if not tarjeta or not re.match(r"^\d{6,15}$", tarjeta):
                errors.append("Tarjeta de propiedad inválida (6-15 dígitos)")
            self._validate_vigentes(data, errors)
        else:
            self._validate_vigentes_bici(data, errors)

        if not licencia or not re.match(r"^\d{5,15}$", licencia):
            errors.append("Número de licencia inválido (5-15 dígitos)")
        if catlicencia not in self._cfg.CATEGORIAS_LICENCIA_VALIDAS:
            errors.append("Categoría de licencia inválida")

        return errors

    def _validate_licencia(self, licencia, catlicencia, errors):
        if not licencia or not re.match(r"^\d{5,15}$", licencia):
            errors.append("Número de licencia inválido (5-15 dígitos)")

    def _validate_vigentes(self, data, errors):
        self._check_fecha_futura("vensoat", "El SOAT debe estar vigente", data, errors)
        self._check_fecha_futura("venlicencia", "La licencia no puede estar vencida", data, errors)

    def _validate_vigentes_bici(self, data, errors):
        self._check_fecha_futura("venlicencia", "La licencia no puede estar vencida", data, errors)

    def _check_fecha_futura(self, field, msg, data, errors):
        value = data.get(field)
        if not value:
            errors.append(f"{msg} (fecha requerida)")
            return
        try:
            fecha = date.fromisoformat(str(value)[:10])
            if fecha <= date.today():
                errors.append(msg)
        except ValueError:
            errors.append("Fecha inválida")
        return data


class DocumentFileValidation(ValidationStrategy):
    """Valida los archivos requeridos (no el MIME, eso lo hace la Chain)."""

    REQUIRED = ["soat-file", "tarjeta-file", "licencia-file"]

    def validate(self, data: dict) -> list[str]:
        errors = []
        files = data.get("files", {}) or {}
        for input_name in self.REQUIRED:
            if not files.get(input_name):
                tipo = ConfigManager().DOC_TYPES.get(input_name, input_name)
                errors.append(f"Debes adjuntar el documento de {tipo}")
        return errors


class ConsentsValidation(ValidationStrategy):
    """Paso 3: checkboxes de aceptación."""

    def validate(self, data: dict) -> list[str]:
        errors = []
        if not data.get("acepta_terminos"):
            errors.append("Debes aceptar los Términos y Condiciones")
        if not data.get("acepta_privacidad"):
            errors.append("Debes aceptar la Política de Privacidad")
        return errors