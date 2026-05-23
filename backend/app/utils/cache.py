"""Simple in-memory cache with optional async Redis backend."""

from __future__ import annotations

import json
import time
from typing import Any, Optional

from app.config import settings
from app.utils.logger import get_logger

logger = get_logger(__name__)

# ---------------------------------------------------------------------------
# Async Redis client (optional)
# ---------------------------------------------------------------------------
_redis_client = None

if settings.redis_url:
    try:
        from redis.asyncio import from_url as _redis_async_from_url  # type: ignore

        _redis_client = _redis_async_from_url(settings.redis_url, decode_responses=True)
        logger.info("Async Redis cache configured at %s", settings.redis_url)
    except Exception as exc:  # noqa: BLE001
        logger.warning("Redis unavailable (%s). Falling back to in-memory cache.", exc)
        _redis_client = None

# ---------------------------------------------------------------------------
# In-memory fallback
# ---------------------------------------------------------------------------
_memory_store: dict[str, tuple[Any, float]] = {}  # key -> (value, expiry_timestamp)


async def set_cache(key: str, value: Any, ttl: int = 300) -> None:
    """Store *value* under *key* for *ttl* seconds."""
    serialised = json.dumps(value)
    if _redis_client:
        try:
            await _redis_client.setex(key, ttl, serialised)
            return
        except Exception as exc:  # noqa: BLE001
            logger.warning("Redis set failed (%s); writing to memory cache.", exc)
    _memory_store[key] = (serialised, time.time() + ttl)


async def get_cache(key: str) -> Optional[Any]:
    """Return cached value or ``None`` if missing / expired."""
    if _redis_client:
        try:
            raw = await _redis_client.get(key)
            return json.loads(raw) if raw else None
        except Exception as exc:  # noqa: BLE001
            logger.warning("Redis get failed (%s); falling back to memory cache.", exc)

    entry = _memory_store.get(key)
    if entry is None:
        return None
    value, expiry = entry
    if time.time() > expiry:
        del _memory_store[key]
        return None
    return json.loads(value)


async def delete_cache(key: str) -> None:
    """Invalidate a cache entry."""
    if _redis_client:
        try:
            await _redis_client.delete(key)
            return
        except Exception as exc:  # noqa: BLE001
            logger.warning("Redis delete failed (%s); removing from memory cache.", exc)
    _memory_store.pop(key, None)
