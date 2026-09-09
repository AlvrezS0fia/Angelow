"""Rutas API del microservicio de registro de repartidores."""
import json
import logging
import time
import urllib.request
import urllib.error
from collections import defaultdict
from typing import Optional

from fastapi import APIRouter, Depends, File, Form, HTTPException, Request, UploadFile
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from sqlalchemy.orm import Session

from config import ConfigManager
from database import get_db
from services.repartidor_service import RepartidorService
from schemas.repartidor import (
    CambioEstadoRequest,
    EstadoSolicitudResponse,
    ListadoSolicitud,
    RegistroRepartidorResponse,
)

logger = logging.getLogger("repartidor_routes")
_cfg = ConfigManager()

router = APIRouter(prefix="/api/repartidor", tags=["repartidor"])


# ── Rate-limit in-memory por IP+key ────────────────────────────────────────
_login_attempts: dict[str, list[float]] = defaultdict(list)


def _login_rate_limit(key: str) -> bool:
    now = time.time()
    bucket = [t for t in _login_attempts[key] if now - t < _cfg.RATE_LIMIT_LOGIN_WINDOW]
    _login_attempts[key] = bucket
    if len(bucket) >= _cfg.RATE_LIMIT_LOGIN_MAX:
        return True
    bucket.append(now)
    return False


# ── Admin auth dependency ──────────────────────────────────────────────────
def _require_admin(request: Request):
    """Valida X-Admin-Token o permite solo desde localhost."""
    token = request.headers.get("x-admin-token", "")
    if _cfg.ADMIN_TOKEN and token == _cfg.ADMIN_TOKEN:
        return
    client = request.client
    if client and client.host in ("127.0.0.1", "::1"):
        return
    raise HTTPException(status_code=403, detail="Acceso no autorizado")


def _svc(db: Session = Depends(get_db)) -> RepartidorService:
    return RepartidorService(db)


class LoginRequest(BaseModel):
    email: str
    password: str


@router.post("/login")
def login_proxy(body: LoginRequest, request: Request):
    client_ip = request.client.host if request.client else "unknown"
    email_key = body.email.strip().lower()
    rl_key = f"login:{client_ip}:{email_key}"
    if _login_rate_limit(rl_key):
        logger.warning("Login rate-limit: ip=%s email=%s", client_ip, email_key)
        return JSONResponse(
            status_code=429,
            content={"success": False, "message": "Demasiados intentos. Espera 15 minutos."},
        )

    url = f"{_cfg.ANGELOW_API_URL}/repartidor/login"
    payload = json.dumps({"email": body.email, "password": body.password}).encode()
    req = urllib.request.Request(
        url, data=payload,
        headers={"Content-Type": "application/json", "Accept": "application/json"},
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=10) as resp:
            data = json.loads(resp.read().decode())
            return JSONResponse(status_code=resp.status, content=data)
    except urllib.error.HTTPError as e:
        try:
            data = json.loads(e.read().decode())
        except Exception:
            data = {"success": False, "message": "Error del servidor de ANGELOW"}
        return JSONResponse(status_code=e.code, content=data)
    except Exception:
        logger.exception("Login proxy error")
        return JSONResponse(
            status_code=502,
            content={"success": False, "message": "No se pudo conectar con el servidor de ANGELOW."},
        )


@router.post("/registro", response_model=None, status_code=201)
async def registrar_repartidor(
    request: Request,
    nombres: str = Form(...),
    apellidos: str = Form(...),
    tipodoc: str = Form(...),
    numdoc: str = Form(...),
    celular: str = Form(...),
    correo: str = Form(...),
    pass_: str = Form(..., alias="pass"),
    pass_confirm: str = Form(..., alias="pass_confirm"),
    fecha_nacimiento: Optional[str] = Form(None),
    direccion: Optional[str] = Form(None),
    ciudad: Optional[str] = Form(None),
    vehiculo: str = Form(...),
    placa: Optional[str] = Form(None),
    licencia: Optional[str] = Form(None),
    catlicencia: Optional[str] = Form(None),
    tarjeta: Optional[str] = Form(None),
    vensoat: Optional[str] = Form(None),
    venlicencia: Optional[str] = Form(None),
    ventechno: Optional[str] = Form(None),
    acepta_terminos: int = Form(0),
    acepta_privacidad: int = Form(0),
    soat_file: Optional[UploadFile] = File(None, alias="soat-file"),
    tarjeta_file: Optional[UploadFile] = File(None, alias="tarjeta-file"),
    licencia_file: Optional[UploadFile] = File(None, alias="licencia-file"),
    db: Session = Depends(get_db),
):
    """Crea la solicitud de repartidor y sube los documentos adjuntos."""
    files = {
        "soat-file": soat_file,
        "tarjeta-file": tarjeta_file,
        "licencia-file": licencia_file,
    }
    data = {
        "nombres": nombres,
        "apellidos": apellidos,
        "tipodoc": tipodoc,
        "numdoc": numdoc,
        "celular": celular,
        "correo": correo,
        "pass": pass_,
        "pass_confirm": pass_confirm,
        "fecha_nacimiento": fecha_nacimiento,
        "direccion": direccion,
        "ciudad": ciudad,
        "vehiculo": vehiculo,
        "placa": placa,
        "licencia": licencia,
        "catlicencia": catlicencia,
        "tarjeta": tarjeta,
        "vensoat": vensoat,
        "venlicencia": venlicencia,
        "ventechno": ventechno,
        "acepta_terminos": bool(acepta_terminos),
        "acepta_privacidad": bool(acepta_privacidad),
        "_client_ip": request.client.host if request.client else "",
    }

    result = _svc(db).register(data, files)
    if not result.get("success"):
        raise HTTPException(status_code=400, detail=result.get("message", "Error de validación"))

    return RegistroRepartidorResponse(
        success=True,
        message=result["message"],
        pending=True,
        redirect="/repartidor/dashboard",
        files_uploaded=result.get("files_uploaded", []),
    )


@router.get("/estado", response_model=EstadoSolicitudResponse)
def consultar_estado(correo: str, db: Session = Depends(get_db)):
    """Consulta el estado de la solicitud de repartidor por email."""
    import re

    if not re.match(r"^[^@\s]+@[^@\s]+\.[^@\s]+$", correo or ""):
        raise HTTPException(status_code=400, detail="Correo inválido")
    return _svc(db).get_estado(correo)


@router.get("", response_model=list[ListadoSolicitud])
def listar_solicitudes(
    estado: Optional[str] = None,
    db: Session = Depends(get_db),
    _admin: None = Depends(_require_admin),
):
    """Lista las solicitudes (uso administrativo)."""
    solicitudes = _svc(db).listar(estado)
    return [
        ListadoSolicitud(
            id=s.id,
            nombres=s.nombres,
            apellidos=s.apellidos,
            email=s.email,
            telefono=s.telefono,
            tipo_documento=s.tipo_documento,
            numero_documento=s.numero_documento,
            tipo_vehiculo=s.tipo_vehiculo,
            placa_vehiculo=s.placa_vehiculo,
            estado=s.estado,
            fecha_solicitud=s.fecha_solicitud,
        )
        for s in solicitudes
    ]


@router.put("/{solicitud_id}/estado")
def cambiar_estado_solicitud(
    solicitud_id: int,
    body: CambioEstadoRequest,
    db: Session = Depends(get_db),
    _admin: None = Depends(_require_admin),
):
    """Aprueba o rechaza una solicitud (uso administrativo)."""
    if body.estado not in ("aprobada", "rechazada"):
        raise HTTPException(status_code=400, detail="Estado inválido")
    try:
        result = _svc(db).cambiar_estado(
            solicitud_id,
            body.estado,
            motivo=body.motivo_rechazo,
            obs=body.observaciones,
        )
    except ValueError as exc:
        raise HTTPException(status_code=404, detail=str(exc))
    return {"success": True, **result}
