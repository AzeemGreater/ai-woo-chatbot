"""WooCommerce order lookup and status operations."""

from __future__ import annotations

from typing import Any, Optional

from app.utils.logger import get_logger
from app.utils.woo_client import woo_client

logger = get_logger(__name__)

STATUS_LABELS: dict[str, str] = {
    "pending": "Pending Payment",
    "processing": "Processing",
    "on-hold": "On Hold",
    "completed": "Completed",
    "cancelled": "Cancelled",
    "refunded": "Refunded",
    "failed": "Failed",
    "trash": "Deleted",
}


class OrderService:
    """Look up and format WooCommerce orders."""

    async def get_order(
        self, order_id: str, billing_email: Optional[str] = None
    ) -> Optional[dict[str, Any]]:
        """
        Fetch an order by ID.  If *billing_email* is provided the result is
        only returned when it matches the order's billing address (security).
        """
        try:
            raw = await woo_client.get(f"orders/{order_id}")
        except Exception:  # noqa: BLE001
            return None

        if billing_email:
            order_email = raw.get("billing", {}).get("email", "").lower()
            if order_email != billing_email.lower():
                logger.warning(
                    "Email mismatch for order %s: supplied %s", order_id, billing_email
                )
                return None

        return self._format_order(raw)

    def _format_order(self, raw: dict[str, Any]) -> dict[str, Any]:
        items = [
            {
                "name": item["name"],
                "quantity": item["quantity"],
                "subtotal": item["subtotal"],
            }
            for item in raw.get("line_items", [])
        ]
        status = raw.get("status", "")
        return {
            "id": raw.get("id"),
            "status": status,
            "status_label": STATUS_LABELS.get(status, status.title()),
            "date_created": raw.get("date_created", ""),
            "total": raw.get("total", ""),
            "currency": raw.get("currency", ""),
            "billing_name": (
                f"{raw.get('billing', {}).get('first_name', '')} "
                f"{raw.get('billing', {}).get('last_name', '')}".strip()
            ),
            "items": items,
            "tracking_number": self._extract_tracking(raw),
        }

    @staticmethod
    def _extract_tracking(raw: dict[str, Any]) -> Optional[str]:
        """Try to find a shipment tracking number in order meta data."""
        for meta in raw.get("meta_data", []):
            if meta.get("key", "").lower() in (
                "_tracking_number",
                "tracking_number",
                "wc_shipment_tracking_items",
            ):
                return str(meta.get("value", ""))
        return None


order_service = OrderService()
