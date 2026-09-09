"""Configuración de SQLAlchemy (motor, sesión y borrado de BD)."""
from sqlalchemy import create_engine
from sqlalchemy.orm import declarative_base, sessionmaker, Session

from config import ConfigManager

_config = ConfigManager()

engine = create_engine(
    _config.DB_URL,
    connect_args={"check_same_thread": False},
    echo=False,
)

SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

Base = declarative_base()


def get_db():
    db: Session = SessionLocal()
    try:
        yield db
    finally:
        db.close()


def init_db():
    import models  # noqa: F401  (registra los modelos en Base.metadata)
    Base.metadata.create_all(bind=engine)