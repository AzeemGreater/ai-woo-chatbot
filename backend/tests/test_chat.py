"""Tests for the /api/v1/chat endpoint (mocked services)."""

from __future__ import annotations

from unittest.mock import AsyncMock, patch

import pytest
from fastapi.testclient import TestClient

from app.main import app

client = TestClient(app)


@pytest.fixture(autouse=True)
def mock_services():
    """Patch all external service calls so tests run without live APIs."""
    with (
        patch(
            "app.routes.chat.intent_service.detect",
            new_callable=AsyncMock,
            return_value={"intent": "small_talk", "entities": {}},
        ),
        patch(
            "app.routes.chat.ai_service.chat",
            new_callable=AsyncMock,
            return_value="Hello! How can I help you today?",
        ),
    ):
        yield


def test_chat_returns_200():
    resp = client.post(
        "/api/v1/chat",
        json={"session_id": "test-123", "message": "Hello"},
    )
    assert resp.status_code == 200


def test_chat_response_shape():
    resp = client.post(
        "/api/v1/chat",
        json={"session_id": "test-abc", "message": "Hi there"},
    )
    data = resp.json()
    assert "reply" in data
    assert "session_id" in data
    assert data["session_id"] == "test-abc"


def test_chat_empty_message_rejected():
    resp = client.post(
        "/api/v1/chat",
        json={"session_id": "test-123", "message": ""},
    )
    assert resp.status_code == 422


def test_chat_missing_session_rejected():
    resp = client.post(
        "/api/v1/chat",
        json={"message": "Hello"},
    )
    assert resp.status_code == 422
