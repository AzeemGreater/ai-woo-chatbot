"""FastAPI application entry point."""

import logging

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app.config import settings
from app.routes import chat, leads, orders, products
from app.utils.logger import get_logger

logger = get_logger(__name__)

app = FastAPI(
    title="AI WooCommerce Chatbot API",
    description="Backend API for the AI-powered WooCommerce shopping assistant.",
    version="1.0.0",
)

# ---------------------------------------------------------------------------
# CORS
# ---------------------------------------------------------------------------
origins = [o.strip() for o in settings.allowed_origins.split(",")]
app.add_middleware(
    CORSMiddleware,
    allow_origins=origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ---------------------------------------------------------------------------
# Routers
# ---------------------------------------------------------------------------
app.include_router(chat.router, prefix="/api/v1", tags=["chat"])
app.include_router(products.router, prefix="/api/v1", tags=["products"])
app.include_router(orders.router, prefix="/api/v1", tags=["orders"])
app.include_router(leads.router, prefix="/api/v1", tags=["leads"])


@app.get("/health", tags=["health"])
async def health_check() -> dict:
    """Simple liveness probe."""
    return {"status": "ok", "bot_name": settings.bot_name}
