"""Simple in-memory cache with optional Redis backend."""

from __future__ import annotations

import json
import time
from typing import Any, Optional

from app.config import settings
from app.utils.logger import get_logger

logger = get_logger(__name__)

# ---------------------------------------------------------------------------
# Redis client (optional)
# ---------------------------------------------------------------------------
_redis_client = None

if settings.redis_url:
    try:
        import redis  # type: ignore

        _redis_client = redis.from_url(settings.redis_url, decode_responses=True)
        _redis_client.ping()
        logger.info("Redis cache connected at %s", settings.redis_url)
    except Exception as exc:  # noqa: BLE001
        logger.warning("Redis unavailable (%s). Falling back to in-memory cache.", exc)
        _redis_client = None

# ---------------------------------------------------------------------------
# In-memory fallback
# ---------------------------------------------------------------------------
_memory_store: dict[str, tuple[Any, float]] = {}  # key -> (value, expiry_timestamp)


def set_cache(key: str, value: Any, ttl: int = 300) -> None:
    """Store *value* under *key* for *ttl* seconds."""
    serialised = json.dumps(value)
    if _redis_client:
        _redis_client.setex(key, ttl, serialised)
    else:
        _memory_store[key] = (serialised, time.time() + ttl)


def get_cache(key: str) -> Optional[Any]:
    """Return cached value or ``None`` if missing / expired."""
    if _redis_client:
        raw = _redis_client.get(key)
        return json.loads(raw) if raw else None

    entry = _memory_store.get(key)
    if entry is None:
        return None
    value, expiry = entry
    if time.time() > expiry:
        del _memory_store[key]
        return None
    return json.loads(value)


def delete_cache(key: str) -> None:
    """Invalidate a cache entry."""
    if _redis_client:
        _redis_client.delete(key)
    else:
        _memory_store.pop(key, None)
