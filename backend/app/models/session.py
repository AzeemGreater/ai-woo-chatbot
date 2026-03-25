"""Pydantic schemas for chat sessions."""

from __future__ import annotations

from datetime import datetime, timezone
from typing import Any, Optional
from pydantic import BaseModel, Field


def _now() -> datetime:
    return datetime.now(timezone.utc)


class SessionData(BaseModel):
    session_id: str
    created_at: datetime = Field(default_factory=_now)
    updated_at: datetime = Field(default_factory=_now)
    message_count: int = 0
    email: Optional[str] = None
    extra: dict[str, Any] = Field(default_factory=dict)
