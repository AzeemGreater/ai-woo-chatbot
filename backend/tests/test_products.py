"""Tests for product service helpers (no live WooCommerce required)."""

from __future__ import annotations

from unittest.mock import AsyncMock, patch

import pytest

from app.models.product import ProductSchema
from app.services.product_service import ProductService, _parse_product


_RAW_PRODUCT = {
    "id": 42,
    "name": "Blue Sneakers",
    "slug": "blue-sneakers",
    "permalink": "https://store.test/product/blue-sneakers/",
    "price": "49.99",
    "regular_price": "59.99",
    "sale_price": "49.99",
    "description": "<p>A great pair of sneakers.</p>",
    "short_description": "Great sneakers",
    "sku": "SNKR-001",
    "stock_status": "instock",
    "stock_quantity": 20,
    "categories": [{"id": 1, "name": "Footwear", "slug": "footwear"}],
    "tags": [{"id": 5, "name": "sale", "slug": "sale"}],
    "images": [
        {"src": "https://store.test/img/sneakers.jpg"},
        {"src": "https://store.test/img/sneakers-2.jpg"},
    ],
    "average_rating": "4.5",
    "rating_count": 12,
    "attributes": [
        {"name": "Color", "options": [{"name": "Blue"}, {"name": "Red"}]}
    ],
    "variations": [],
}


def test_parse_product_basic():
    product = _parse_product(_RAW_PRODUCT)
    assert product.id == 42
    assert product.name == "Blue Sneakers"
    assert product.price == "49.99"
    assert product.in_stock is True
    assert product.categories == ["Footwear"]
    assert product.image_url == "https://store.test/img/sneakers.jpg"
    assert product.gallery_images == ["https://store.test/img/sneakers-2.jpg"]
    assert product.rating_average == 4.5
    assert product.attributes == {"Color": ["Blue", "Red"]}


def test_parse_product_out_of_stock():
    raw = {**_RAW_PRODUCT, "stock_status": "outofstock"}
    product = _parse_product(raw)
    assert product.in_stock is False


def test_format_for_prompt():
    service = ProductService()
    product = _parse_product(_RAW_PRODUCT)
    formatted = service.format_for_prompt([product])
    assert len(formatted) == 1
    assert formatted[0]["name"] == "Blue Sneakers"
    assert "price" in formatted[0]
    assert "url" in formatted[0]


@pytest.mark.asyncio
async def test_search_returns_empty_on_woo_error():
    service = ProductService()
    with patch.object(service, "_resolve_category_id", new_callable=AsyncMock, return_value=None):
        with patch("app.services.product_service.woo_client.get", side_effect=Exception("connection error")):
            results = await service.search(keyword="shoes")
    assert results == []
