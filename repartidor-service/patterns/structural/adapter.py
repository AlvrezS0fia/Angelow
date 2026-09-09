"""= PATRÓN GOF: ADAPTER ======================================================
FileStorageAdapter abstrae el almacenamiento de archivos (disco hoy, S3 u
otro mañana). En el PHP se usaba move_uploaded_file con nombre aleatorio;
aquí el adaptador replica ese comportamiento con contenido validado.
"""
import os
import uuid
from typing import Optional

from config import ConfigManager


class FileStorageAdapter:
    def __init__(self, base_dir: Optional[str] = None):
        self.base_dir = base_dir or ConfigManager().UPLOAD_DIR

    def save(
        self,
        file,
        user_id: int,
        doc_type: str,
        ext: str,
        size: int = 0,
        content: Optional[bytes] = None,
    ) -> str:
        os.makedirs(self.base_dir, exist_ok=True)
        random_name = uuid.uuid4().hex[:16]
        filename = f"{user_id}_{doc_type}_{random_name}.{ext}"
        dest_path = os.path.join(self.base_dir, filename)

        if content is not None:
            payload = content
        else:
            payload = file.file.read()

        with open(dest_path, "wb") as fh:
            fh.write(payload)

        # Ruta relativa al BASE_DIR para poder servirse vía /static/...
        return f"static/uploads/documentos/{filename}"

    def file_exists(self, relative_path: str) -> bool:
        full = os.path.join(ConfigManager().BASE_DIR, relative_path)
        return os.path.isfile(full)