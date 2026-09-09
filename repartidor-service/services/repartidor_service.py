"""Capa de servicio: fachada decorada. Expone al router una interfaz única."""
from patterns.structural.decorator import RateLimitDecorator, LoggingDecorator
from patterns.structural.facade import RepartidorFacade


class RepartidorService:
    """Compone la Facade con los decoradores GOF de rate-limit y logging."""

    def __init__(self, db):
        facade = RepartidorFacade(db)
        wrapped = LoggingDecorator(facade)
        wrapped = RateLimitDecorator(wrapped)
        self._svc = wrapped

    # Delegaciones explícitas (evita confusión con __getattr__)
    def register(self, form_data, files):
        return self._svc.register(form_data, files)

    def get_estado(self, email: str):
        return self._svc.get_estado(email)

    def listar(self, estado=None):
        return self._svc.listar(estado)

    def cambiar_estado(self, solicitud_id, estado, motivo=None, obs=None):
        return self._svc.cambiar_estado(solicitud_id, estado, motivo, obs)