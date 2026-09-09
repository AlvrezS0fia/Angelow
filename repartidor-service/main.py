"""= MICROSERVICIO DE REGISTRO DE REPARTIDORES (ANGELOW) =======================
Punto de entrada FastAPI. Sirve el HTML del formulario y la API REST.
Patrones GOF integrados: Singleton, Factory, Builder, Facade, Adapter,
Decorator, Strategy, Chain of Responsibility, Observer, Template Method
y Repository.

Ejecutar:  python main.py   →  http://127.0.0.1:8000
"""
import logging
import os
import sys
from contextlib import asynccontextmanager
from logging.handlers import RotatingFileHandler

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import FileResponse, HTMLResponse
from fastapi.staticfiles import StaticFiles
from fastapi.templating import Jinja2Templates
from starlette.middleware.base import BaseHTTPMiddleware
from starlette.requests import Request
from starlette.responses import Response

from config import ConfigManager
from database import init_db
from routes.repartidor import router as repartidor_router
from routes.dashboard import router as dashboard_router


# ── Logging rotativo a archivo ─────────────────────────────────────────────
def _setup_logging():
    cfg = ConfigManager()
    log_dir = os.path.dirname(cfg.LOG_FILE)
    os.makedirs(log_dir, exist_ok=True)

    file_handler = RotatingFileHandler(
        cfg.LOG_FILE,
        maxBytes=cfg.LOG_MAX_BYTES,
        backupCount=cfg.LOG_BACKUP_COUNT,
        encoding="utf-8",
    )
    file_handler.setFormatter(
        logging.Formatter("%(asctime)s %(levelname)s %(name)s: %(message)s")
    )
    file_handler.setLevel(logging.INFO)

    root = logging.getLogger()
    root.setLevel(logging.INFO)
    root.addHandler(file_handler)


# ── Security headers middleware ─────────────────────────────────────────────
class SecurityHeadersMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next):
        response: Response = await call_next(request)
        response.headers["X-Content-Type-Options"] = "nosniff"
        response.headers["X-Frame-Options"] = "DENY"
        response.headers["Referrer-Policy"] = "strict-origin-when-cross-origin"
        response.headers["Permissions-Policy"] = "camera=(), microphone=(), geolocation=()"
        response.headers["X-XSS-Protection"] = "1; mode=block"
        response.headers["X-Request-Id"] = request.headers.get(
            "X-Request-Id", str(id(request))
        )
        # Content Security Policy — restringe fuentes de contenido
        response.headers["Content-Security-Policy"] = (
            "default-src 'self'; "
            "script-src 'self' 'unsafe-inline'; "
            "style-src 'self' 'unsafe-inline'; "
            "img-src 'self' https://*.tile.openstreetmap.org data:; "
            "connect-src 'self' https://router.project-osrm.org https://nominatim.openstreetmap.org; "
            "font-src 'self' https://cdnjs.cloudflare.com; "
            "frame-ancestors 'none'"
        )
        # Cache-Control para respuestas de la API
        if request.url.path.startswith("/api/"):
            response.headers["Cache-Control"] = "no-store, no-cache, must-revalidate"
            response.headers["Pragma"] = "no-cache"
        return response


@asynccontextmanager
async def lifespan(app: FastAPI):
    _setup_logging()
    cfg = ConfigManager()
    logger = logging.getLogger("main")
    secret = cfg.get_angelow_jwt_secret()
    if not secret or len(secret) < cfg.JWT_SECRET_MIN_LEN:
        logger.warning(
            "JWT_SECRET es muy corto o no está definido (mínimo %d caracteres). "
            "Define JWT_SECRET en %s para producción.",
            cfg.JWT_SECRET_MIN_LEN,
            cfg.ANGELOW_ENV_PATH,
        )
    if not cfg.ADMIN_TOKEN:
        logger.warning(
            "ADMIN_TOKEN no está configurado. Los endpoints admin de "
            "/api/repartidor estarán restringidos a localhost hasta que lo definas."
        )
    init_db()
    yield


app = FastAPI(
    title="ANGELOW — Registro de Repartidores",
    description="Microservicio independiente para el registro de repartidores.",
    version="1.0.0",
    lifespan=lifespan,
)

cfg = ConfigManager()
templates = Jinja2Templates(directory="templates")


# ── Request body size limit middleware ──────────────────────────────────────
class RequestBodySizeLimitMiddleware(BaseHTTPMiddleware):
    """Rechaza requests cuyo Content-Length supere el límite configurado."""

    def __init__(self, app, max_size: int = None):
        super().__init__(app)
        self.max_size = max_size or cfg.REQUEST_BODY_MAX_SIZE

    async def dispatch(self, request: Request, call_next):
        content_length = request.headers.get("content-length")
        if content_length and int(content_length) > self.max_size:
            from fastapi.responses import JSONResponse as _JSONResponse
            return _JSONResponse(
                status_code=413,
                content={"detail": "El cuerpo de la solicitud excede el tamaño máximo permitido."},
            )
        return await call_next(request)

app.add_middleware(SecurityHeadersMiddleware)
app.add_middleware(RequestBodySizeLimitMiddleware)
app.add_middleware(
    CORSMiddleware,
    allow_origins=cfg.CORS_ORIGINS,
    allow_credentials=True,
    allow_methods=["GET", "POST", "PUT", "DELETE"],
    allow_headers=["Authorization", "Content-Type", "Accept", "X-Admin-Token"],
    max_age=600,
)

app.mount("/static", StaticFiles(directory="static"), name="static")
app.include_router(repartidor_router)
app.include_router(dashboard_router)


@app.get("/", response_class=HTMLResponse)
def formulario(request: Request):
    return templates.TemplateResponse(request, "registro.html", {})


@app.get("/favicon.ico")
def favicon():
    return FileResponse("static/uploads/documentos/logo.png", media_type="image/png")


@app.get("/dashboard", response_class=HTMLResponse)
def dashboard_page(request: Request):
    return templates.TemplateResponse(request, "dashboard.html", {})


@app.get("/loader", response_class=HTMLResponse)
def loader_registro(request: Request):
    """Pantalla de carga animada para la página de registro."""
    return templates.TemplateResponse(request, "loader_registro.html", {})


@app.get("/dashboard/loader", response_class=HTMLResponse)
def loader_dashboard(request: Request):
    """Pantalla de carga animada para el dashboard del repartidor."""
    return templates.TemplateResponse(request, "loader_dashboard.html", {})


@app.get("/health")
def health():
    return {"status": "ok", "service": "repartidor-registro"}


if __name__ == "__main__":
    import uvicorn

    uvicorn.run("main:app", host=cfg.HOST, port=cfg.PORT, reload=True)
