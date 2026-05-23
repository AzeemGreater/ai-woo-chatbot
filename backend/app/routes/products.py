"""Product search and details endpoints."""

from __future__ import annotations

from typing import Optional

from fastapi import APIRouter, HTTPException, Query

from app.services.product_service import product_service
from app.utils.logger import get_logger

logger = get_logger(__name__)
router = APIRouter()


@router.get("/products/search")
async def search_products(
    q: Optional[str] = Query(None, description="Search keyword"),
    category: Optional[str] = Query(None),
    min_price: Optional[float] = Query(None, ge=0),
    max_price: Optional[float] = Query(None, ge=0),
    per_page: int = Query(6, ge=1, le=20),
    featured: bool = Query(False),
    on_sale: bool = Query(False),
):
    products = await product_service.search(
        keyword=q,
        category=category,
        min_price=min_price,
        max_price=max_price,
        per_page=per_page,
        featured=featured,
        on_sale=on_sale,
    )
    return {"products": [p.model_dump() for p in products]}


@router.get("/products/{product_id}")
async def get_product(product_id: int):
    product = await product_service.get_by_id(product_id)
    if not product:
        raise HTTPException(status_code=404, detail="Product not found")
    return product.model_dump()


@router.get("/products/categories/list")
async def list_categories():
    cats = await product_service.get_categories()
    return {"categories": cats}
