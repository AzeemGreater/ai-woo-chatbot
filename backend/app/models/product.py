"""Pydantic schemas for WooCommerce products."""

from __future__ import annotations

from typing import Any, Optional
from pydantic import BaseModel


class ProductVariation(BaseModel):
    id: int
    name: str
    price: str
    in_stock: bool


class ProductSchema(BaseModel):
    id: int
    name: str
    slug: str
    permalink: str
    price: str
    regular_price: str
    sale_price: Optional[str] = None
    description: str = ""
    short_description: str = ""
    sku: str = ""
    in_stock: bool = True
    stock_quantity: Optional[int] = None
    categories: list[str] = []
    tags: list[str] = []
    image_url: Optional[str] = None
    gallery_images: list[str] = []
    rating_average: float = 0.0
    rating_count: int = 0
    variations: list[ProductVariation] = []
    attributes: dict[str, Any] = {}
