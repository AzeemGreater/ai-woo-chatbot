"""Intent detection and entity extraction using GPT."""

from __future__ import annotations

import json
from typing import Any, Optional

from openai import AsyncOpenAI

from app.config import settings
from app.utils.logger import get_logger

logger = get_logger(__name__)

INTENTS = [
    "product_search",
    "product_details",
    "add_to_cart",
    "order_tracking",
    "faq",
    "small_talk",
    "lead_capture",
    "human_handoff",
]

_INTENT_SYSTEM_PROMPT = """You are an intent classifier for an e-commerce chatbot.
Given a user message, respond ONLY with a valid JSON object in this exact format:
{
  "intent": "<one of: product_search, product_details, add_to_cart, order_tracking, faq, small_talk, lead_capture, human_handoff>",
  "entities": {
    "product_name": "<string or null>",
    "category": "<string or null>",
    "min_price": "<number or null>",
    "max_price": "<number or null>",
    "order_id": "<string or null>",
    "email": "<string or null>",
    "keyword": "<string or null>"
  }
}
Do not include any explanation or extra text."""


class IntentService:
    """Classify user messages into intents and extract entities."""

    def __init__(self) -> None:
        self._client = AsyncOpenAI(api_key=settings.openai_api_key)

    async def detect(self, message: str) -> dict[str, Any]:
        """
        Return a dict with keys ``intent`` and ``entities``.
        Falls back to ``small_talk`` on any error.
        """
        try:
            response = await self._client.chat.completions.create(
                model=settings.openai_model,
                messages=[
                    {"role": "system", "content": _INTENT_SYSTEM_PROMPT},
                    {"role": "user", "content": message},
                ],
                temperature=0,
                max_tokens=200,
            )
            raw = response.choices[0].message.content or "{}"
            data = json.loads(raw)
            intent = data.get("intent", "small_talk")
            if intent not in INTENTS:
                intent = "small_talk"
            return {
                "intent": intent,
                "entities": data.get("entities", {}),
            }
        except Exception as exc:  # noqa: BLE001
            logger.warning("Intent detection failed: %s", exc)
            return {"intent": "small_talk", "entities": {}}


intent_service = IntentService()
