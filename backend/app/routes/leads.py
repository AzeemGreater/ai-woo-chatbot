"""Lead capture endpoint."""

from __future__ import annotations

from pydantic import BaseModel, EmailStr
from fastapi import APIRouter

from app.utils.logger import get_logger

logger = get_logger(__name__)
router = APIRouter()


class LeadRequest(BaseModel):
    session_id: str
    name: str = ""
    email: EmailStr
    phone: str = ""
    source: str = "chatbot"


@router.post("/leads", status_code=201)
async def capture_lead(lead: LeadRequest):
    """Save a lead/newsletter subscriber originating from the chat widget."""
    # In a production deployment this would persist to a database or CRM.
    logger.info("Lead captured | session=%s email=%s", lead.session_id, lead.email)
    return {"status": "ok", "message": "Thank you! We'll be in touch soon."}
