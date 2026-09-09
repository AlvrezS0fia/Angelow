"""Blacklist de tokens JWT en memoria.

Almacena tokens revocados (logout) con su timestamp de expiración.
La limpieza automática elimina tokens expirados para no crecer infinitamente.

Patrón: Singleton (una sola instancia global de TokenBlacklist).
"""
import time
import threading
from typing import Optional


class TokenBlacklist:
    """Almacén en memoria de tokens JWT revocados."""

    _instance: Optional["TokenBlacklist"] = None
    _lock = threading.Lock()

    def __new__(cls):
        if cls._instance is None:
            with cls._lock:
                if cls._instance is None:
                    cls._instance = super().__new__(cls)
                    cls._instance._tokens = {}  # jti -> expiry_timestamp
        return cls._instance

    def revoke(self, jti: str, exp_timestamp: float) -> None:
        """Agrega un token a la blacklist hasta su expiración."""
        if not jti:
            return
        self._tokens[jti] = exp_timestamp
        self._cleanup()

    def is_revoked(self, jti: str) -> bool:
        """Retorna True si el token está en la blacklist y aún no expiró."""
        if not jti:
            return False
        exp = self._tokens.get(jti)
        if exp is None:
            return False
        if time.time() >= exp:
            # Ya expiró, limpiar
            self._tokens.pop(jti, None)
            return False
        return True

    def _cleanup(self) -> None:
        """Elimina tokens que ya expiraron (memoización temporal)."""
        now = time.time()
        expired = [jti for jti, exp in self._tokens.items() if now >= exp]
        for jti in expired:
            self._tokens.pop(jti, None)

    @classmethod
    def reset(cls) -> None:
        """Resetea la instancia (solo para tests)."""
        with cls._lock:
            if cls._instance is not None:
                cls._instance._tokens.clear()
            cls._instance = None


# Instancia global
blacklist = TokenBlacklist()
