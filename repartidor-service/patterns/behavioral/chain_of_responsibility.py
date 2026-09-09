"""= PATRÓN GOF: CHAIN OF RESPONSIBILITY ======================================
Cadena de validación y persistencia de documentos. Cada handler decide si
procesa o delega al siguiente; si uno falla, la cadena se corta.
Replica la secuencia de seguridad de los controllers PHP (validación por
contenido real, límite de tamaño, whitelist MIME, nombre aleatorio).
"""
import os
import uuid
from abc import ABC, abstractmethod
from datetime import date
from typing import Optional

from config import ConfigManager
from patterns.behavioral.mime_detector import detect_mime


class Handler(ABC):
    """Base de la cadena."""

    def __init__(self):
        self._next: Optional["Handler"] = None

    def set_next(self, handler: "Handler") -> "Handler":
        self._next = handler
        return handler

    def handle(self, context: dict) -> dict:
        """Procesa el contexto; agrega errores si falla y sigue."""
        try:
            self._process(context)
        except Exception as exc:  # noqa: BLE001
            context.setdefault("errors", []).append(str(exc))
            context["failed"] = True
        if not context.get("failed") and self._next:
            self._next.handle(context)
        return context

    @abstractmethod
    def _process(self, context: dict) -> None:
        raise NotImplementedError


class FileExistsHandler(Handler):
    def _process(self, context: dict) -> None:
        f = context["file"]
        if f is None or not getattr(f, "filename", None):
            raise ValueError(f"Debes adjuntar el documento de {context['tipo']}")

        raw = context.get("content")
        if raw is None:
            raw = f.file.read() if hasattr(f, "file") else f.read()
        if not raw:
            raise ValueError(f"El archivo de {context['tipo']} está vacío")
        context["content"] = raw
        context["upload_size"] = len(raw)


class FileSizeHandler(Handler):
    def _process(self, context: dict) -> None:
        size = context.get("upload_size", 0)
        max_bytes = ConfigManager().MAX_FILE_SIZE
        if size <= 0:
            raise ValueError(f"El archivo de {context['tipo']} está vacío")
        if size > max_bytes:
            raise ValueError(f"El documento de {context['tipo']} no debe superar 10 MB")


class MimeValidationHandler(Handler):
    def _process(self, context: dict) -> None:
        cfg = ConfigManager()
        mime = context.get("mime_type") or detect_mime(
            context.get("content", b""),
            filename=context["file"].filename,
            client_mime=getattr(context["file"], "content_type", ""),
        )
        context["mime_type"] = mime
        if mime not in cfg.ALLOWED_MIME:
            raise ValueError(
                f"El documento de {context['tipo']} debe ser PDF, JPG o PNG"
            )
        context["ext"] = cfg.MIME_EXT_MAP[mime]


class FileSaveHandler(Handler):
    """Guarda el archivo en disco usando FileStorageAdapter."""

    def __init__(self, storage=None):
        super().__init__()
        if storage is None:
            from patterns.structural.adapter import FileStorageAdapter

            storage = FileStorageAdapter()
        self.storage = storage

    def _process(self, context: dict) -> None:
        path = self.storage.save(
            file=context["file"],
            user_id=context["user_id"],
            doc_type=context["tipo"],
            ext=context["ext"],
            size=context.get("upload_size", 0),
            content=context.get("content"),
        )
        context["saved_path"] = path


class DatabaseInsertHandler(Handler):
    """Inserta el documento en BD (observador del save)."""

    def __init__(self, doc_repo, solicitud_id=None):
        super().__init__()
        self.doc_repo = doc_repo
        self.solicitud_id = solicitud_id

    def _process(self, context: dict) -> None:
        cfg = ConfigManager()
        numero_doc = context.get("numero_documento")
        if context["tipo"] == "tarjeta_propiedad":
            numero_doc = numero_doc or context.get("tarjeta")
        fecha_venc = context.get("fecha_vencimiento")
        self.doc_repo.crear(
            repartidor_id=context["user_id"],
            solicitud_id=self.solicitud_id,
            tipo=context["tipo"],
            archivo_url=context["saved_path"],
            nombre_archivo=context["file"].filename,
            mime_type=context["mime_type"],
            tamano_bytes=context.get("upload_size", 0),
            numero_documento=numero_doc,
            fecha_vencimiento=fecha_venc,
        )


def build_document_chain(doc_repo, solicitud_id: Optional[int]) -> Handler:
    """Fábrica de la cadena completa (FileExists → ... → DatabaseInsert)."""
    first = FileExistsHandler()
    tail = first
    tail = tail.set_next(FileSizeHandler())
    tail = tail.set_next(MimeValidationHandler())
    tail = tail.set_next(FileSaveHandler())
    tail = tail.set_next(DatabaseInsertHandler(doc_repo, solicitud_id))
    return first