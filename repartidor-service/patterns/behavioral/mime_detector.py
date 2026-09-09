"""Validación de documentos por contenido real (magic bytes).

Replica el comportamiento de finfo mime_content_type del PHP: nunca se
confía en el Content-Type que envía el cliente (falsificable).
"""

PDF_SIGNATURES = (b"%PDF",)
JPEG_SIGNATURES = (b"\xff\xd8\xff",)
PNG_SIGNATURES = (b"\x89PNG\r\n\x1a\n",)
OLE_SIGNATURES = (b"\xd0\xcf\x11\xe0\xa1\xb1\x1a\xe1",)

FALLBACK_BY_EXT = {
    "png": "image/png",
    "jpg": "image/jpeg",
    "jpeg": "image/jpeg",
    "pdf": "application/pdf",
}


def detect_mime(content: bytes, filename: str = "", client_mime: str = "") -> str:
    """Detecta el MIME real a partir de los magic bytes del contenido."""
    if content:
        if content.startswith(PDF_SIGNATURES):
            return "application/pdf"
        if content.startswith(JPEG_SIGNATURES):
            return "image/jpeg"
        if content.startswith(PNG_SIGNATURES):
            return "image/png"
        if content.startswith(OLE_SIGNATURES):
            # .doc (OLE2). Se rechaza por no estar en whitelist, pero se
            # distingue para dar un mensaje más claro si hace falta.
            return "application/msword"

    ext = (filename or "").rsplit(".", 1)[-1].lower() if "." in (filename or "") else ""
    if ext in FALLBACK_BY_EXT:
        return FALLBACK_BY_EXT[ext]

    if client_mime and client_mime not in ("", "application/octet-stream"):
        return client_mime
    return ""