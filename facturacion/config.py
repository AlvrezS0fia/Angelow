import os
from dotenv import load_dotenv

load_dotenv()

DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USER', 'root'),
    'password': os.getenv('DB_PASS', ''),
    'database': os.getenv('DB_NAME', 'angelow_db'),
    'port': int(os.getenv('DB_PORT', 3306)),
    'charset': 'utf8mb4',
    'collation': 'utf8mb4_unicode_ci'
}

SMTP_CONFIG = {
    'host': os.getenv('SMTP_HOST', 'smtp.gmail.com'),
    'port': int(os.getenv('SMTP_PORT', 587)),
    'username': os.getenv('SMTP_USERNAME', 'angelow.contacto@gmail.com'),
    'password': os.getenv('SMTP_PASSWORD', 'vncn qkjn upop iuey'),
    'from_email': os.getenv('SMTP_FROM_EMAIL', 'angelow.contacto@gmail.com'),
    'from_name': os.getenv('SMTP_FROM_NAME', 'Angelow')
}

APP_URL = os.getenv('APP_URL', 'http://localhost/Angelow/public')
PYTHON_PORT = int(os.getenv('PYTHON_PORT', 5000))

# Secreto compartido para autenticar llamadas PHP -> API de facturación.
# Proviene SOLO de .env (nunca hardcodeado en código). Si no está definido,
# la API niega el acceso (fail-closed) en lugar de quedar abierta.
FACTURA_API_SECRET = os.getenv('FACTURA_API_SECRET', '')
