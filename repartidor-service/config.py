"""= PATRÓN GOF: SINGLETON ==================================================
ConfigManager garantiza una única fuente de configuración en todo el
microservicio. Cualquier módulo importa ConfigManager() y obtiene siempre
la misma instancia con los mismos valores.
"""
import os


def _load_dotenv(path: str) -> None:
    """Lee un archivo .env simple y carga variables en os.environ.

    No depende de python-dotenv. Solo procesa líneas KEY=VALUE,
    ignora comentarios (#) y líneas vacías. No sobreescribe variables
    que ya existan en el entorno del sistema.
    """
    if not os.path.isfile(path):
        return
    try:
        with open(path, "r", encoding="utf-8") as f:
            for line in f:
                line = line.strip()
                if not line or line.startswith("#"):
                    continue
                if "=" not in line:
                    continue
                key, _, value = line.partition("=")
                key = key.strip()
                value = value.strip()
                if len(value) >= 2 and value[0] in ('"', "'") and value[-1] == value[0]:
                    value = value[1:-1]
                if key and key not in os.environ:
                    os.environ[key] = value
    except Exception:
        pass


class ConfigManager:
    _instance = None

    def __new__(cls):
        if cls._instance is None:
            cls._instance = super().__new__(cls)
            cls._instance._init_env()
            cls._instance._load()
        return cls._instance

    def _init_env(self):
        base = os.path.dirname(os.path.abspath(__file__))
        _load_dotenv(os.path.join(base, ".env"))

    def _load(self):
        self.BASE_DIR = os.path.dirname(os.path.abspath(__file__))
        self.DB_URL = f"sqlite:///{os.path.join(self.BASE_DIR, 'repartidores.db')}"
        self.UPLOAD_DIR = os.path.join(self.BASE_DIR, "static", "uploads", "documentos")
        self.MAX_FILE_SIZE = 10 * 1024 * 1024
        self.ALLOWED_MIME = {"application/pdf", "image/jpeg", "image/png"}
        self.MIME_EXT_MAP = {
            "application/pdf": "pdf",
            "image/jpeg": "jpg",
            "image/png": "png",
        }

        self.TIPOS_DOCUMENTOS_VALIDOS = ["CC", "CE", "TI", "PAS"]
        self.TIPOS_DOCUMENTOS_DB_MAP = {
            "CC": "CC",
            "CE": "CE",
            "TI": "OTRO",
            "PAS": "PASAPORTE",
        }
        self.TIPOS_VEHICULO_VALIDOS = ["moto", "carro", "bicicleta", "camioneta"]
        self.CATEGORIAS_LICENCIA_VALIDAS = ["A1", "A2", "B1", "B2", "B3", "C1"]

        self.DOC_TYPES = {
            "soat-file": "soat",
            "tarjeta-file": "tarjeta_propiedad",
            "licencia-file": "licencia_conduccion",
        }

        self.RATE_LIMIT_MAX = 5
        self.RATE_LIMIT_WINDOW = 900
        self.RATE_LIMIT_ESTADO_MAX = 20
        self.RATE_LIMIT_ESTADO_WINDOW = 900

        self.HOST = os.getenv("HOST", "127.0.0.1")
        self.PORT = int(os.getenv("PORT", "8000"))

        # ── ANGELOW (panel admin) — sincronización de registros ─────────────
        self.ANGELOW_DB_HOST = os.getenv("ANGELOW_DB_HOST", "127.0.0.1")
        self.ANGELOW_DB_PORT = int(os.getenv("ANGELOW_DB_PORT", "3306"))
        self.ANGELOW_DB_NAME = os.getenv("ANGELOW_DB_NAME", "angelow_db")
        self.ANGELOW_DB_USER = os.getenv("ANGELOW_DB_USER", "root")
        self.ANGELOW_DB_PASS = os.getenv("ANGELOW_DB_PASS", "")
        self.ANGELOW_UPLOADS_DIR = os.getenv(
            "ANGELOW_UPLOADS_DIR",
            r"C:\xampp\htdocs\Angelow\public\uploads\documentos",
        )
        self.ANGELOW_ENV_PATH = os.getenv(
            "ANGELOW_ENV_PATH",
            r"C:\xampp\htdocs\Angelow\.env",
        )
        self.ANGELOW_API_URL = os.getenv(
            "ANGELOW_API_URL", "http://localhost/Angelow/public"
        )

        # ── Seguridad ──────────────────────────────────────────────────────
        self.ADMIN_TOKEN = os.getenv("ADMIN_TOKEN", "")
        self.RATE_LIMIT_LOGIN_MAX = 10
        self.RATE_LIMIT_LOGIN_WINDOW = 900
        self.JWT_SECRET_MIN_LEN = 32
        self.CORS_ORIGINS = os.getenv(
            "CORS_ORIGINS",
            "http://localhost:8000,http://127.0.0.1:8000"
        ).split(",")
        self.LOG_FILE = os.path.join(self.BASE_DIR, "logs", "repartidor.log")
        self.LOG_MAX_BYTES = 5 * 1024 * 1024
        self.LOG_BACKUP_COUNT = 3

        # Seguridad: límites de request body.
        # 3 documentos de hasta 10 MB (MAX_FILE_SIZE) + campos del formulario ≈ 40 MB.
        self.REQUEST_BODY_MAX_SIZE = 40 * 1024 * 1024

        # Seguridad: rate limit para endpoints del dashboard
        self.DASHBOARD_RATE_LIMIT_MAX = 60
        self.DASHBOARD_RATE_LIMIT_WINDOW = 60  # 1 minuto

    def get_angelow_jwt_secret(self) -> str | None:
        path = self.ANGELOW_ENV_PATH
        if not os.path.isfile(path):
            return None
        try:
            with open(path, "r", encoding="utf-8") as f:
                for line in f:
                    line = line.strip()
                    if line.startswith("JWT_SECRET="):
                        val = line[len("JWT_SECRET="):]
                        if len(val) >= 2 and val[0] in ('"', "'") and val[-1] == val[0]:
                            val = val[1:-1]
                        return val or None
        except Exception:
            return None
        return None