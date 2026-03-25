"""Pydantic schemas for chat messages and sessions."""

from __future__ import annotations

from typing import Any, Optional
from pydantic import BaseModel, Field


class ChatMessage(BaseModel):
    role: str = Field(..., pattern="^(user|assistant|system)$")
    content: str


class ChatRequest(BaseModel):
    session_id: str = Field(..., min_length=1, max_length=128)
    message: str = Field(..., min_length=1, max_length=2000)
    history: list[ChatMessage] = Field(default_factory=list)
    # Optional context injected by the WordPress plugin
    current_product_id: Optional[int] = None
    cart_items: list[dict[str, Any]] = Field(default_factory=list)


class QuickReply(BaseModel):
    label: str
    payload: str


class ProductCard(BaseModel):
    id: int
    name: str
    price: str
    image_url: Optional[str] = None
    permalink: str
    in_stock: bool = True


class ChatResponse(BaseModel):
    session_id: str
    reply: str
    intent: Optional[str] = None
    product_cards: list[ProductCard] = Field(default_factory=list)
    quick_replies: list[QuickReply] = Field(default_factory=list)
    handoff: bool = False
