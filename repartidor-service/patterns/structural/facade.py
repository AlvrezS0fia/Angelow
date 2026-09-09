"""= PATRÓN GOF: FACADE ========================================================
RepartidorFacade simplifica el flujo de registro completo exponiendo un
único método register(). Internamente orquesta repositorios, estrategias,
la cadena de documentos, el factory y los observadores — replicando el
método registro() monstruoso del controller PHP original.
"""
import bcrypt
import logging
from datetime import datetime
from typing import Optional

from sqlalchemy.exc import IntegrityError

from config import ConfigManager
from models.usuario import Usuario
from patterns.behavioral.chain_of_responsibility import build_document_chain
from patterns.behavioral.observer import (
    AdminNotificationObserver,
    AngelowSyncObserver,
    HistoryLogObserver,
)
from patterns.behavioral.template_method import RegistrationFlow
from patterns.creational.factory import RepartidorFactory
from patterns.creational.builder import SolicitudBuilder
from patterns.structural.adapter import FileStorageAdapter
from patterns.structural.angelow_adapter import AngelowClientAdapter
from repositories.documento_repository import DocumentoRepository
from repositories.historial_repository import HistorialRepository
from repositories.notificacion_repository import NotificacionRepository
from repositories.solicitud_repository import SolicitudRepository
from repositories.usuario_repository import UsuarioRepository
from repositories.vehiculo_repository import VehiculoRepository

logger = logging.getLogger("repartidor")


class RepartidorFacade:
    def __init__(self, db):
        self.db = db
        self.cfg = ConfigManager()

        self.usuario_repo = UsuarioRepository(db)
        self.solicitud_repo = SolicitudRepository(db)
        self.vehiculo_repo = VehiculoRepository(db)
        self.documento_repo = DocumentoRepository(db)
        self.historial_repo = HistorialRepository(db)
        self.notificacion_repo = NotificacionRepository(db)

        self.validator = RegistrationFlow()
        self.storage = FileStorageAdapter()
        self._observers = [
            AdminNotificationObserver(self.notificacion_repo, self.usuario_repo),
            HistoryLogObserver(self.historial_repo),
            AngelowSyncObserver(
                AngelowClientAdapter(self.cfg), self.documento_repo
            ),
        ]

    def register(self, form_data: dict, files: dict) -> dict:
        """Punto único de entrada del registro. Retorna dict de resultado."""
        errors = self._validate(form_data, files)
        if errors:
            return {"success": False, "message": " | ".join(errors)}

        user = None
        try:
            user, solicitud_id = self._persist(form_data, files)
        except ValueError as exc:
            self.db.rollback()
            return {"success": False, "message": str(exc)}
        except IntegrityError:
            self.db.rollback()
            return {
                "success": False,
                "message": (
                    "Ya existe un registro con ese número de documento o correo. "
                    "Si ya te registraste antes, usa el correo con el que lo hiciste."
                ),
            }
        except Exception:  # noqa: BLE001
            self.db.rollback()
            logger.exception(
                "Registro fallido correo=%s",
                str(form_data.get("correo", "")).strip().lower(),
            )
            return {"success": False, "message": "Error al crear la solicitud. Intenta de nuevo."}

        try:
            self._notify(user, self.solicitud_repo.get_by_id(solicitud_id))
        finally:
            # SEGURIDAD: Limpiar la contraseña en claro del objeto en memoria
            if user is not None and hasattr(user, "angelow_plain_password"):
                try:
                    del user.angelow_plain_password
                except Exception:  # noqa: BLE001
                    pass

        return {
            "success": True,
            "message": "Solicitud enviada. Un administrador revisará tus datos y te aprobará el acceso.",
            "pending": True,
            "redirect": "/repartidor/dashboard",
            "id": user.id,
            "email": user.email,
        }

    def get_estado(self, email: str) -> dict:
        solicitud = self.solicitud_repo.find_by_email(email)
        if not solicitud:
            return {"success": True, "solicitud": None,
                    "message": "No se encontró una solicitud para este correo."}
        return {
            "success": True,
            "solicitud": {
                "estado": solicitud.estado,
                "motivo": solicitud.motivo_rechazo or solicitud.observaciones or "",
                "fecha_solicitud": solicitud.fecha_solicitud,
                "fecha_respuesta": solicitud.fecha_respuesta,
            },
        }

    def listar(self, estado: Optional[str] = None) -> list:
        return self.solicitud_repo.listar(estado)

    def cambiar_estado(self, solicitud_id: int, estado: str, motivo=None, obs=None) -> dict:
        from patterns.behavioral.observer import SolicitudStatusSubject

        subject = SolicitudStatusSubject(self.solicitud_repo, self.usuario_repo)
        for observer in self._observers:
            if isinstance(observer, HistoryLogObserver):
                subject.attach(observer)
        return subject.cambiar_estado(solicitud_id, estado, motivo, obs)

    # ── paso interno: VALIDAR ──────────────────────────────────────────
    def _validate(self, data: dict, files: dict) -> list:
        errors = self.validator.execute(data)
        errors.extend(self._validar_documentos(data, files))
        return errors

    def _validar_documentos(self, data: dict, files: dict) -> list:
        """Pre-validación de documentos (existencia, tamaño, MIME real).

        Lee el contenido una sola vez, detecta el MIME por magic bytes
        (como finfo en PHP) y guarda el resultado para reutilizarlo al
        persistir, evitando doble lectura de los UploadFile.
        """
        from patterns.behavioral.mime_detector import detect_mime

        errors = []
        validated = {}
        for input_name, tipo in self.cfg.DOC_TYPES.items():
            f = files.get(input_name)
            if f is None or not getattr(f, "filename", ""):
                errors.append(f"Debes adjuntar el documento de {tipo}")
                continue
            raw = f.file.read() if hasattr(f, "file") else f.read()
            if not raw:
                errors.append(f"El archivo de {tipo} está vacío")
                continue
            if len(raw) > self.cfg.MAX_FILE_SIZE:
                errors.append(f"El documento de {tipo} no debe superar 10 MB")
                continue
            mime = detect_mime(raw, f.filename, getattr(f, "content_type", ""))
            if mime not in self.cfg.ALLOWED_MIME:
                errors.append(f"El documento de {tipo} debe ser PDF, JPG o PNG")
                continue
            validated[input_name] = {
                "file": f,
                "content": raw,
                "mime_type": mime,
                "filename": f.filename,
            }
        data["_validated_files"] = validated
        return errors

    # ── paso interno: PERSISTIR ─────────────────────────────────────────
    def _persist(self, data: dict, files: dict) -> tuple[Usuario, int]:
        password_hash = bcrypt.hashpw(data["pass"].encode("utf-8"), bcrypt.gensalt())
        fecha_nac = self._parse_datetime(data.get("fecha_nacimiento"))
        celular_limpio = "".join(data.get("celular", "").split())
        tipodoc_db = self.cfg.TIPOS_DOCUMENTOS_DB_MAP.get(
            data["tipodoc"], data["tipodoc"]
        )

        datos_factory = {
            "nombres": data["nombres"].strip(),
            "apellidos": data["apellidos"].strip(),
            "email": data["correo"].strip().lower(),
            "password_hash": password_hash,
            "telefono": celular_limpio,
            "tipodoc": tipodoc_db,
            "numdoc": data["numdoc"].strip(),
            "fecha_nacimiento": fecha_nac,
            "direccion": (data.get("direccion") or "").strip(),
            "ciudad": (data.get("ciudad") or "").strip(),
            "vehiculo": data["vehiculo"],
            "placa": (data.get("placa") or "").upper().replace("-", "") if not self._es_bici(data) else "",
            "licencia": data.get("licencia", "") or "",
            "catlicencia": (data.get("catlicencia") or "").upper(),
        }

        existing = self.usuario_repo.find_by_email(datos_factory["email"])
        if existing is None and datos_factory["numdoc"]:
            dup = self.usuario_repo.find_by_cedula(datos_factory["numdoc"])
            if dup is not None:
                raise ValueError(
                    f"El número de documento {datos_factory['numdoc']} ya está "
                    f"registrado con el correo {dup.email}. Usa ese correo para acceder."
                )
        user = RepartidorFactory.create_or_reactivate(
            self.usuario_repo, existing, datos_factory
        )
        user.angelow_plain_password = data["pass"]

        placa = (data.get("placa") or "").upper().replace("-", "")
        solicitud_data = (
            SolicitudBuilder()
            .set_personal(
                usuario_id=user.id,
                nombres=data["nombres"].strip(),
                apellidos=data["apellidos"].strip(),
                email=data["correo"].strip().lower(),
                telefono=celular_limpio,
                tipodoc=tipodoc_db,
                numdoc=data["numdoc"].strip(),
                fecha_nacimiento=data.get("fecha_nacimiento"),
                direccion=(data.get("direccion") or "").strip(),
                ciudad=(data.get("ciudad") or "").strip(),
            )
            .set_vehicle(
                tipo_vehiculo=data["vehiculo"],
                placa=placa,
                numero_licencia=data.get("licencia", ""),
                categoria_licencia=(data.get("catlicencia") or "").upper(),
                tarjeta_propiedad=self._from(data, "tarjeta"),
                soat_vencimiento=self._from(data, "vensoat"),
                tecnomecanica_vencimiento=self._from(data, "ventechno"),
                vencimiento_licencia=self._from(data, "venlicencia"),
            )
            .set_estado("pendiente")
            .build()
        )
        solicitud = self.solicitud_repo.crear(solicitud_data)

        # Vehículo (si aplica; las bici no tienen placa)
        if not self._es_bici(data):
            try:
                self.vehiculo_repo.upsert(
                    repartidor_id=user.id,
                    tipo_vehiculo=data["vehiculo"],
                    placa=placa,
                    numero_licencia_transito=data.get("licencia", "") or None,
                )
            except Exception:  # noqa: BLE001
                pass

        # Documentos vía cadena de responsabilidad
        self._guardar_documentos(user.id, solicitud.id, data)

        return user, solicitud.id

    def _guardar_documentos(self, user_id, solicitud_id, data):
        cfg = self.cfg
        validated = data.get("_validated_files", {}) or {}
        chain = build_document_chain(self.documento_repo, solicitud_id)
        for input_name, info in validated.items():
            f = info["file"]
            tipo = cfg.DOC_TYPES.get(input_name)
            if tipo is None:
                continue
            vencimiento = None
            if input_name == "soat-file":
                vencimiento = data.get("vensoat")
            elif input_name == "tecnomecanica-file":
                vencimiento = data.get("ventechno")
            elif input_name == "licencia-file":
                vencimiento = data.get("venlicencia")
            numero_doc = data.get("tarjeta") if input_name == "tarjeta-file" else None
            context = {
                "file": f,
                "tipo": tipo,
                "user_id": user_id,
                "mime_type": info["mime_type"],
                "content": info["content"],
                "fecha_vencimiento": vencimiento,
                "numero_documento": numero_doc,
                "tarjeta": data.get("tarjeta", ""),
                "failed": False,
                "errors": [],
            }
            chain.handle(context)

    # ── paso interno: NOTIFICAR ─────────────────────────────────────────
    def _notify(self, user: Usuario, solicitud) -> None:
        for observer in self._observers:
            try:
                observer.on_registration_created(user, solicitud)
            except Exception:  # noqa: BLE001
                continue

    @staticmethod
    def _es_bici(data: dict) -> bool:
        return data.get("vehiculo") == "bicicleta"

    @staticmethod
    def _from(data: dict, key: str, default=""):
        return data.get(key) or default

    @staticmethod
    def _parse_datetime(value):
        if not value:
            return None
        try:
            return datetime.strptime(str(value)[:10], "%Y-%m-%d")
        except ValueError:
            return None