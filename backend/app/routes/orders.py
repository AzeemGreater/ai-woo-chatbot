"""Order tracking endpoint."""

from __future__ import annotations

from typing import Optional

from fastapi import APIRouter, HTTPException, Query

from app.services.order_service import order_service

router = APIRouter()


@router.get("/order/track")
async def track_order(
    order_id: str = Query(..., min_length=1),
    email: Optional[str] = Query(None),
):
    """Look up an order by ID.  Email is required for security verification."""
    order = await order_service.get_order(order_id=order_id, billing_email=email)
    if not order:
        raise HTTPException(
            status_code=404,
            detail="Order not found. Please check your order ID and billing email.",
        )
    return order
