"""= PATRÓN GOF: TEMPLATE METHOD ==============================================
RegistrationFlow define el esqueleto del flujo de validación (pasos fijos)
delegando cada paso a una Strategy. Permite ampliar pasos sin tocar la
máquina del flujo.
"""
from patterns.behavioral.strategy import (
    PersonalDataValidation,
    VehicleValidation,
    ConsentsValidation,
)


class RegistrationFlow:
    """Orquesta la validación completa en pasos fijos."""

    def __init__(self):
        self._step1 = PersonalDataValidation()
        self._step2 = VehicleValidation()
        self._step4 = ConsentsValidation()

    def execute(self, data: dict) -> list:
        errors = []
        errors.extend(self.step_validate_personal(data))
        errors.extend(self.step_validate_vehicle(data))
        errors.extend(self.step_validate_consents(data))
        return errors

    def step_validate_personal(self, data: dict) -> list:
        return self._step1.validate(data)

    def step_validate_vehicle(self, data: dict) -> list:
        return self._step2.validate(data)

    def step_validate_consents(self, data: dict) -> list:
        return self._step4.validate(data)