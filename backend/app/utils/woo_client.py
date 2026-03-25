"""Async WooCommerce REST API v3 client."""

from __future__ import annotations

from typing import Any, Optional
from urllib.parse import urljoin

import httpx

from app.config import settings
from app.utils.logger import get_logger

logger = get_logger(__name__)

WOO_API_BASE = "/wp-json/wc/v3/"


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
        """Perform a GET request against the WooCommerce API."""
        url = self._base + endpoint.lstrip("/")
        client = await self._get_client()
        try:
            resp = await client.get(url, params=params or {})
            resp.raise_for_status()
            return resp.json()
        except httpx.HTTPStatusError as exc:
            logger.error("WooCommerce API error %s: %s", exc.response.status_code, exc.response.text)
            raise
        except httpx.RequestError as exc:
            logger.error("WooCommerce request failed: %s", exc)
            raise

    async def post(self, endpoint: str, data: dict) -> Any:
        """Perform a POST request against the WooCommerce API."""
        url = self._base + endpoint.lstrip("/")
        client = await self._get_client()
        try:
            resp = await client.post(url, json=data)
            resp.raise_for_status()
            return resp.json()
        except httpx.HTTPStatusError as exc:
            logger.error("WooCommerce API error %s: %s", exc.response.status_code, exc.response.text)
            raise

    async def close(self) -> None:
        if self._client and not self._client.is_closed:
            await self._client.aclose()


# Singleton instance reused across requests
woo_client = WooClient()
