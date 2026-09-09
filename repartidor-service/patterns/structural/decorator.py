"""= PATRÓN GOF: DECORATOR ====================================================
Envuelven un servicio para añadir rate-limiting y logging sin modificar el
servicio original. Replica el RateLimiter del PHP y añade trazabilidad.
"""
import logging
import time
from collections import defaultdict

from fastapi import HTTPException

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
logger = logging.getLogger("repartidor")

from config import ConfigManager


class ServiceDecorator:
    """Base de decoradores de servicios."""

    def __init__(self, service):
        self._service = service

    def __getattr__(self, name):
        return getattr(self._service, name)


class RateLimitDecorator(ServiceDecorator):
    """Limita intentos por IP+email (ventana deslizante en memoria).

    Equivalente al RateLimiter::tooMany($rlKey, 5, 900) del PHP. Para un
    microservicio de un solo proceso es suficiente; para varios procesos
    debería respaldarse en Redis.
    """

    def __init__(self, service, max_attempts=None, window_seconds=None, max_status=None, status_window=None):
        super().__init__(service)
        cfg = ConfigManager()
        self.max_attempts = max_attempts or cfg.RATE_LIMIT_MAX
        self.window_seconds = window_seconds or cfg.RATE_LIMIT_WINDOW
        self.max_status = max_status or cfg.RATE_LIMIT_ESTADO_MAX
        self.status_window = status_window or cfg.RATE_LIMIT_ESTADO_WINDOW
        self._attempts: dict[str, list[float]] = defaultdict(list)

    def _too_many(self, key: str, limit: int, window: int) -> bool:
        now = time.time()
        bucket = [t for t in self._attempts[key] if now - t < window]
        self._attempts[key] = bucket
        if len(bucket) >= limit:
            return True
        bucket.append(now)
        return False

    def register(self, form_data: dict, files: dict) -> dict:
        email = str(form_data.get("correo", "")).strip().lower()
        client_ip = form_data.get("_client_ip", "")
        key = f"registro_repartidor:{client_ip}:{email}"
        if self._too_many(key, self.max_attempts, self.window_seconds):
            raise HTTPException(
                status_code=429,
                detail="Demasiados intentos de registro. Espera 15 minutos.",
            )
        return self._service.register(form_data, files)

    def get_estado(self, email: str) -> dict:
        client_ip = "<ext>"
        key = f"estado_repartidor:{client_ip}"
        if self._too_many(key, self.max_status, self.status_window):
            raise HTTPException(
                status_code=429,
                detail="Demasiadas consultas. Espera 15 minutos.",
            )
        return self._service.get_estado(email)


class LoggingDecorator(ServiceDecorator):
    """Registra duraciones y resultados de la operación."""

    def register(self, form_data: dict, files: dict) -> dict:
        email = str(form_data.get("correo", ""))
        start = time.perf_counter()
        result = self._service.register(form_data, files)
        elapsed = round(time.perf_counter() - start, 3)
        logger.info(
            "registro correo=%s success=%s elapsed=%ss",
            email, result.get("success"), elapsed,
        )
        return result

    def get_estado(self, email: str) -> dict:
        result = self._service.get_estado(email)
        logger.info("estado consulta=%s found=%s", email, bool(result.get("solicitud")))
        return result