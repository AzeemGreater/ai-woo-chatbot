"""WooCommerce product operations."""

from __future__ import annotations

from typing import Any, Optional

from app.models.product import ProductSchema, ProductVariation
from app.utils.cache import delete_cache, get_cache, set_cache
from app.utils.logger import get_logger
from app.utils.woo_client import woo_client

logger = get_logger(__name__)

_CACHE_TTL = 300  # 5 minutes


def _parse_product(raw: dict[str, Any]) -> ProductSchema:
    images = raw.get("images", [])
    categories = [c.get("name", "") for c in raw.get("categories", [])]
    tags = [t.get("name", "") for t in raw.get("tags", [])]
    variations_raw = raw.get("variations", [])

    return ProductSchema(
        id=raw["id"],
        name=raw.get("name", ""),
        slug=raw.get("slug", ""),
        permalink=raw.get("permalink", ""),
        price=raw.get("price", "0"),
        regular_price=raw.get("regular_price", "0"),
        sale_price=raw.get("sale_price") or None,
        description=raw.get("description", ""),
        short_description=raw.get("short_description", ""),
        sku=raw.get("sku", ""),
        in_stock=raw.get("stock_status", "outofstock") == "instock",
        stock_quantity=raw.get("stock_quantity"),
        categories=categories,
        tags=tags,
        image_url=images[0]["src"] if images else None,
        gallery_images=[img["src"] for img in images[1:]],
        rating_average=float(raw.get("average_rating", 0)),
        rating_count=int(raw.get("rating_count", 0)),
        variations=[],  # fetched separately when needed
        attributes={
            attr["name"]: [o["name"] for o in attr.get("options", [])]
            for attr in raw.get("attributes", [])
        },
    )


class ProductService:
    """High-level product operations backed by WooCommerce REST API."""

    async def search(
        self,
        keyword: Optional[str] = None,
        category: Optional[str] = None,
        min_price: Optional[float] = None,
        max_price: Optional[float] = None,
        per_page: int = 6,
        featured: bool = False,
        on_sale: bool = False,
    ) -> list[ProductSchema]:
        cache_key = f"products:search:{keyword}:{category}:{min_price}:{max_price}:{per_page}:{featured}:{on_sale}"
        cached = await get_cache(cache_key)
        if cached:
            return [ProductSchema(**p) for p in cached]

        params: dict[str, Any] = {"per_page": per_page, "status": "publish"}
        if keyword:
            params["search"] = keyword
        if category:
            # Try to resolve category slug/name to ID
            cat_id = await self._resolve_category_id(category)
            if cat_id:
                params["category"] = cat_id
        if min_price is not None:
            params["min_price"] = min_price
        if max_price is not None:
            params["max_price"] = max_price
        if featured:
            params["featured"] = True
        if on_sale:
            params["on_sale"] = True

        try:
            raw_list = await woo_client.get("products", params=params)
        except Exception:  # noqa: BLE001
            return []

        products = [_parse_product(p) for p in raw_list]
        await set_cache(cache_key, [p.model_dump() for p in products], ttl=_CACHE_TTL)
        return products

    async def get_by_id(self, product_id: int) -> Optional[ProductSchema]:
        cache_key = f"products:id:{product_id}"
        cached = await get_cache(cache_key)
        if cached:
            return ProductSchema(**cached)

        try:
            raw = await woo_client.get(f"products/{product_id}")
        except Exception:  # noqa: BLE001
            return None

        product = _parse_product(raw)
        await set_cache(cache_key, product.model_dump(), ttl=_CACHE_TTL)
        return product

    async def get_categories(self) -> list[dict[str, Any]]:
        cache_key = "products:categories"
        cached = await get_cache(cache_key)
        if cached:
            return cached

        try:
            raw = await woo_client.get("products/categories", params={"per_page": 50})
        except Exception:  # noqa: BLE001
            return []

        cats = [{"id": c["id"], "name": c["name"], "slug": c["slug"]} for c in raw]
        await set_cache(cache_key, cats, ttl=_CACHE_TTL)
        return cats

    async def _resolve_category_id(self, category_name: str) -> Optional[int]:
        cats = await self.get_categories()
        name_lower = category_name.lower()
        for cat in cats:
            if cat["name"].lower() == name_lower or cat["slug"].lower() == name_lower:
                return cat["id"]
        return None

    def format_for_prompt(self, products: list[ProductSchema]) -> list[dict[str, Any]]:
        """Return a concise representation suitable for injecting into GPT prompts."""
        return [
            {
                "id": p.id,
                "name": p.name,
                "price": p.price,
                "in_stock": p.in_stock,
                "categories": p.categories,
                "short_description": p.short_description[:200],
                "rating": p.rating_average,
                "url": p.permalink,
            }
            for p in products
        ]


product_service = ProductService()
