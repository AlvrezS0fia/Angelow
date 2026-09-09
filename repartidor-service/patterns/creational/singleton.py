"""= PATRÓN GOF: SINGLETON ====================================================
Re-export de ConfigManager. Fuente única de configuración del microservicio.

Uso:
    from patterns.creational.singleton import ConfigManager
    cfg = ConfigManager()   # siempre la misma instancia
"""
from config import ConfigManager

__all__ = ["ConfigManager"]