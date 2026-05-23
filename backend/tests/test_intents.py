"""Tests for intent detection fallback logic (no live OpenAI required)."""

from __future__ import annotations

from unittest.mock import AsyncMock, MagicMock, patch

import pytest

from app.services.intent_service import INTENTS, IntentService


@pytest.mark.asyncio
async def test_detect_returns_valid_intent():
    service = IntentService()
    mock_response = MagicMock()
    mock_response.choices[0].message.content = '{"intent": "product_search", "entities": {"keyword": "shoes"}}'

    with patch.object(service._client.chat.completions, "create", new_callable=AsyncMock, return_value=mock_response):
        result = await service.detect("Show me some shoes")

    assert result["intent"] == "product_search"
    assert result["entities"].get("keyword") == "shoes"


@pytest.mark.asyncio
async def test_detect_falls_back_on_invalid_intent():
    service = IntentService()
    mock_response = MagicMock()
    mock_response.choices[0].message.content = '{"intent": "unknown_intent", "entities": {}}'

    with patch.object(service._client.chat.completions, "create", new_callable=AsyncMock, return_value=mock_response):
        result = await service.detect("something weird")

    assert result["intent"] == "small_talk"


@pytest.mark.asyncio
async def test_detect_falls_back_on_exception():
    service = IntentService()
    with patch.object(
        service._client.chat.completions,
        "create",
        new_callable=AsyncMock,
        side_effect=Exception("API down"),
    ):
        result = await service.detect("hello")

    assert result["intent"] == "small_talk"
    assert isinstance(result["entities"], dict)


def test_all_intents_defined():
    expected = {
        "product_search",
        "product_details",
        "add_to_cart",
        "order_tracking",
        "faq",
        "small_talk",
        "lead_capture",
        "human_handoff",
    }
    assert set(INTENTS) == expected
