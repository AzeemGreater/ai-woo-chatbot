"""Smart product recommendation engine."""

from __future__ import annotations

from typing import Any

from app.models.product import ProductSchema
from app.services.product_service import product_service
from app.utils.logger import get_logger

logger = get_logger(__name__)


class RecommendationService:
    """Generate product recommendations based on context."""

    async def related_to(self, product: ProductSchema, limit: int = 4) -> list[ProductSchema]:
        """Return products from the same category, excluding the source product."""
        if not product.categories:
            return []

        category = product.categories[0]
        results = await product_service.search(category=category, per_page=limit + 1)
        return [p for p in results if p.id != product.id][:limit]

    async def upsell(self, cart_items: list[dict[str, Any]], limit: int = 3) -> list[ProductSchema]:
        """
        Suggest complementary products for items currently in the cart.
        Falls back to featured products when cart is empty.
        """
        if not cart_items:
            return await product_service.search(featured=True, per_page=limit)

        categories: list[str] = []
        for item in cart_items:
            cats = item.get("categories", [])
            if isinstance(cats, list):
                categories.extend(cats)

        if categories:
            return await product_service.search(category=categories[0], per_page=limit)
        return await product_service.search(featured=True, per_page=limit)


recommendation_service = RecommendationService()
