"""= PATRÓN GOF: REPOSITORY ===================================================
Base genérica de repositorios. Encapsula el acceso a datos; cada
repositorio concreto extiende operaciones CRUD y queries propias.
"""
from typing import List, Optional, Type, TypeVar, Generic

from sqlalchemy.orm import Session

T = TypeVar("T")


class RepositoryBase(Generic[T]):
    model: Type[T]

    def __init__(self, db: Session):
        self.db = db

    def get_by_id(self, id: int) -> Optional[T]:
        return self.db.query(self.model).filter(self.model.id == id).first()

    def get_all(self) -> List[T]:
        return self.db.query(self.model).all()

    def create(self, obj: T) -> T:
        self.db.add(obj)
        self.db.commit()
        self.db.refresh(obj)
        return obj

    def update(self, obj: T) -> T:
        self.db.commit()
        self.db.refresh(obj)
        return obj

    def delete(self, obj: T) -> None:
        self.db.delete(obj)
        self.db.commit()

    def commit(self) -> None:
        self.db.commit()

    def flush(self) -> None:
        self.db.flush()