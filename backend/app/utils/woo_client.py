"""Async WooCommerce REST API v3 client."""

from __future__ import annotations

import asyncio
from typing import Any, Optional
from urllib.parse import urljoin

import httpx

from app.config import settings
from app.utils.logger import get_logger

logger = get_logger(__name__)

WOO_API_BASE = "/wp-json/wc/v3/"
_MAX_RETRIES = 2
_RETRY_BACKOFF = 1.0  # seconds (doubles on each retry)

# Only retry on transient network/server errors, not on client errors
_RETRYABLE_STATUS_CODES = {429, 500, 502, 503, 504}


class WooClient:
    """Thin async wrapper around the WooCommerce REST API."""

    def __init__(self) -> None:
        base = settings.woo_store_url.rstrip("/") + "/"
        self._base = urljoin(base, WOO_API_BASE)
        self._auth = (settings.woo_consumer_key, settings.woo_consumer_secret)
        self._client: Optional[httpx.AsyncClient] = None

    async def _get_client(self) -> httpx.AsyncClient:
        if self._client is None or self._client.is_closed:
            self._client = httpx.AsyncClient(
                auth=self._auth,
                timeout=15.0,
                headers={"User-Agent": "AI-WooBot/1.0"},
            )
        return self._client

    async def get(self, endpoint: str, params: Optional[dict] = None) -> Any:
        """Perform a GET request against the WooCommerce API (with retry)."""
        url = self._base + endpoint.lstrip("/")
        client = await self._get_client()
        for attempt in range(_MAX_RETRIES + 1):
            try:
                resp = await client.get(url, params=params or {})
                resp.raise_for_status()
                return resp.json()
            except httpx.HTTPStatusError as exc:
                logger.error("WooCommerce API error %s: %s", exc.response.status_code, exc.response.text)
                if exc.response.status_code in _RETRYABLE_STATUS_CODES and attempt < _MAX_RETRIES:
                    await asyncio.sleep(_RETRY_BACKOFF * (2 ** attempt))
                    continue
                raise
            except httpx.RequestError as exc:
                logger.error("WooCommerce request failed: %s", exc)
                if attempt < _MAX_RETRIES:
                    await asyncio.sleep(_RETRY_BACKOFF * (2 ** attempt))
                    continue
                raise

    async def post(self, endpoint: str, data: dict) -> Any:
        """Perform a POST request against the WooCommerce API (with retry)."""
        url = self._base + endpoint.lstrip("/")
        client = await self._get_client()
        for attempt in range(_MAX_RETRIES + 1):
            try:
                resp = await client.post(url, json=data)
                resp.raise_for_status()
                return resp.json()
            except httpx.HTTPStatusError as exc:
                logger.error("WooCommerce API error %s: %s", exc.response.status_code, exc.response.text)
                if exc.response.status_code in _RETRYABLE_STATUS_CODES and attempt < _MAX_RETRIES:
                    await asyncio.sleep(_RETRY_BACKOFF * (2 ** attempt))
                    continue
                raise
            except httpx.RequestError as exc:
                logger.error("WooCommerce request failed: %s", exc)
                if attempt < _MAX_RETRIES:
                    await asyncio.sleep(_RETRY_BACKOFF * (2 ** attempt))
                    continue
                raise

    async def close(self) -> None:
        if self._client and not self._client.is_closed:
            await self._client.aclose()


# Singleton instance reused across requests
woo_client = WooClient()
