"""Rutas del Dashboard de Repartidor — FastAPI.

Endpoints protegidos por JWT Bearer token (mismo token emitido por
ANGLEOW en /repartidor/login). Toda la lógica de negocio se ejecuta
en DashboardService (acceso directo a MySQL de ANGELOW).
"""
from fastapi import APIRouter, Header, HTTPException, Request
from fastapi.responses import HTMLResponse, JSONResponse
from pydantic import BaseModel, Field
from typing import Optional
import logging
import time
from collections import defaultdict

from services.dashboard_service import DashboardService
from security.blacklist import blacklist

logger = logging.getLogger("dashboard_routes")
security_logger = logging.getLogger("dashboard_security")

router = APIRouter(prefix="/api/dashboard", tags=["dashboard"])
_svc = DashboardService()

# ── Rate limit in-memory por repartidor_id ────────────────────────────────
_dashboard_rate: dict[int, list[float]] = defaultdict(list)


def _dashboard_rate_limit(repartidor_id: int, max_req: int = 60, window: int = 60) -> bool:
    """Retorna True si se excedió el límite de requests en la ventana."""
    now = time.time()
    bucket = [t for t in _dashboard_rate[repartidor_id] if now - t < window]
    _dashboard_rate[repartidor_id] = bucket
    if len(bucket) >= max_req:
        return True
    bucket.append(now)
    return False


def _auth(authorization: str = "", request: Request = None) -> int:
    """Valida JWT Bearer token, verifica blacklist y rate limit."""
    client_ip = request.client.host if request and request.client else "unknown"
    token = authorization.replace("Bearer ", "").strip()
    if not token:
        raise HTTPException(status_code=401, detail="Token requerido")

    # Extraer jti para blacklist
    payload = _svc.decode_token(token)
    if not payload or "sub" not in payload:
        security_logger.warning(
            "Dashboard auth fallido: token invalido o expirado, ip=%s, token_ultimos_4=%s",
            client_ip, token[-4:] if len(token) >= 4 else "****",
        )
        raise HTTPException(status_code=401, detail="Token invalido o expirado")

    # Verificar blacklist
    jti = payload.get("jti")
    if jti and blacklist.is_revoked(jti):
        security_logger.warning(
            "Dashboard auth rechazado: token revocado (logout), ip=%s, jti=%s",
            client_ip, jti,
        )
        raise HTTPException(status_code=401, detail="Token revocado. Inicia sesión de nuevo.")

    repartidor_id = int(payload["sub"])

    # Rate limit por repartidor
    from config import ConfigManager
    cfg = ConfigManager()
    if _dashboard_rate_limit(repartidor_id, cfg.DASHBOARD_RATE_LIMIT_MAX, cfg.DASHBOARD_RATE_LIMIT_WINDOW):
        security_logger.warning(
            "Dashboard rate limit: repartidor_id=%d, ip=%s", repartidor_id, client_ip,
        )
        raise HTTPException(status_code=429, detail="Demasiadas peticiones. Espera un momento.")

    return repartidor_id


@router.get("/me")
def me(authorization: str = Header(""), request: Request = None):
    rid = _auth(authorization, request)
    user = _svc.get_repartidor(rid)
    if not user:
        raise HTTPException(status_code=401, detail="Usuario no encontrado")
    return {"success": True, "user": user}


@router.get("/resumen")
def resumen(authorization: str = Header(""), request: Request = None):
    rid = _auth(authorization, request)
    return _svc.get_resumen(rid)


class RechazarBody(BaseModel):
    motivo: str = ""


@router.post("/pedidos/{pedido_id}/aceptar")
def aceptar(pedido_id: int, authorization: str = Header(""), request: Request = None):
    rid = _auth(authorization, request)
    return _svc.aceptar(rid, pedido_id)


@router.post("/pedidos/{pedido_id}/rechazar")
def rechazar(
    pedido_id: int, body: RechazarBody, authorization: str = Header(""),
    request: Request = None,
):
    rid = _auth(authorization, request)
    return _svc.rechazar(rid, pedido_id, body.motivo)


class TransicionBody(BaseModel):
    estado: str
    observacion: str = ""


@router.put("/pedidos/{pedido_id}/estado")
def transicion(
    pedido_id: int, body: TransicionBody, authorization: str = Header(""),
    request: Request = None,
):
    rid = _auth(authorization, request)
    return _svc.transicion(rid, pedido_id, body.estado, body.observacion)


@router.get("/rastreo")
def rastreo(authorization: str = Header(""), request: Request = None):
    rid = _auth(authorization, request)
    return _svc.get_rastreo(rid)


class UbicacionBody(BaseModel):
    pedido_id: int
    latitud: float
    longitud: float
    velocidad_kmh: Optional[float] = None
    bateria_porcentaje: Optional[int] = None


@router.post("/ubicacion")
def ubicacion(body: UbicacionBody, authorization: str = Header(""), request: Request = None):
    rid = _auth(authorization, request)
    return _svc.reportar_ubicacion(
        rid, body.pedido_id,
        body.latitud, body.longitud,
        body.velocidad_kmh, body.bateria_porcentaje,
    )


class LogoutBody(BaseModel):
    token: str = ""


@router.post("/logout")
def logout(body: LogoutBody, request: Request = None):
    """Revoca el token JWT en la blacklist para que no pueda reusarse."""
    client_ip = request.client.host if request and request.client else "unknown"
    token = body.token.replace("Bearer ", "").strip()
    if token:
        payload = _svc.decode_token(token)
        if payload:
            jti = payload.get("jti")
            exp = payload.get("exp", 0)
            if jti:
                blacklist.revoke(jti, exp)
                security_logger.info(
                    "Token revocado: ip=%s, jti=%s", client_ip, jti,
                )
    return {"success": True, "message": "Sesión cerrada correctamente"}
