"""OpenAI GPT-4 integration with context-aware conversation handling."""

from __future__ import annotations

import json
from pathlib import Path
from typing import Any, Optional

from openai import AsyncOpenAI

from app.config import settings
from app.models.chat import ChatMessage
from app.utils.logger import get_logger

logger = get_logger(__name__)

# Load system prompt from file
_PROMPTS_DIR = Path(__file__).parent.parent / "prompts"


def _load_prompt(filename: str) -> str:
    path = _PROMPTS_DIR / filename
    if path.exists():
        return path.read_text(encoding="utf-8").strip()
    return ""


SYSTEM_PROMPT = _load_prompt("system_prompt.txt")
PRODUCT_PROMPT_TEMPLATE = _load_prompt("product_prompt.txt")
FAQ_PROMPT_TEMPLATE = _load_prompt("faq_prompt.txt")


class AIService:
    """Wrapper around OpenAI chat completions."""

    def __init__(self) -> None:
        self._client = AsyncOpenAI(api_key=settings.openai_api_key)

    async def chat(
        self,
        user_message: str,
        history: list[ChatMessage],
        extra_context: Optional[str] = None,
        intent: Optional[str] = None,
        product_data: Optional[list[dict[str, Any]]] = None,
        faq_answer: Optional[str] = None,
    ) -> str:
        """Generate a chat completion and return the assistant reply text."""

        system_content = SYSTEM_PROMPT

        # Inject product data when relevant
        if product_data and intent in ("product_search", "product_details"):
            products_json = json.dumps(product_data, ensure_ascii=False, indent=2)
            system_content += "\n\n" + PRODUCT_PROMPT_TEMPLATE.replace(
                "{{PRODUCTS}}", products_json
            )

        # Inject FAQ answer when available
        if faq_answer and intent == "faq":
            system_content += "\n\n" + FAQ_PROMPT_TEMPLATE.replace(
                "{{FAQ_ANSWER}}", faq_answer
            )

        # Inject any additional context (e.g., current page product)
        if extra_context:
            system_content += f"\n\nAdditional context:\n{extra_context}"

        messages: list[dict[str, str]] = [{"role": "system", "content": system_content}]

        # Append conversation history (last N turns to stay within token limits)
        for msg in history[-settings.max_history_messages:]:
            messages.append({"role": msg.role, "content": msg.content})

        messages.append({"role": "user", "content": user_message})

        try:
            response = await self._client.chat.completions.create(
                model=settings.openai_model,
                messages=messages,  # type: ignore[arg-type]
                temperature=0.7,
                max_tokens=settings.max_response_tokens,
            )
            return response.choices[0].message.content or ""
        except Exception as exc:  # noqa: BLE001
            logger.error("OpenAI API error: %s", exc)
            return "I'm sorry, I'm having trouble responding right now. Please try again in a moment."


ai_service = AIService()
