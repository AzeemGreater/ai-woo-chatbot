"""POST /api/v1/chat — main chat endpoint."""

from __future__ import annotations

from fastapi import APIRouter

from app.models.chat import ChatMessage, ChatRequest, ChatResponse, ProductCard, QuickReply
from app.services.ai_service import ai_service
from app.services.faq_service import faq_service
from app.services.intent_service import intent_service
from app.services.order_service import order_service
from app.services.product_service import product_service
from app.utils.logger import get_logger

logger = get_logger(__name__)
router = APIRouter()

# Default quick replies shown at session start
_DEFAULT_QUICK_REPLIES = [
    QuickReply(label="🛍️ Browse Products", payload="Show me your products"),
    QuickReply(label="📦 Track My Order", payload="I want to track my order"),
    QuickReply(label="❓ FAQs", payload="What are your most common questions?"),
    QuickReply(label="💬 Talk to Human", payload="I want to speak to a human"),
]


@router.post("/chat", response_model=ChatResponse)
async def chat(request: ChatRequest) -> ChatResponse:
    """
    Accept a user message, detect intent, fetch relevant data, and
    return an AI-generated reply with optional product cards and quick replies.
    """
    message = request.message.strip()
    session_id = request.session_id

    # Detect intent and extract entities
    detection = await intent_service.detect(message)
    intent: str = detection["intent"]
    entities: dict = detection["entities"]

    logger.info("Session %s | intent=%s | message=%r", session_id, intent, message[:80])

    product_cards: list[ProductCard] = []
    quick_replies: list[QuickReply] = []
    handoff = False
    product_data_for_prompt = None
    faq_answer_for_prompt = None
    extra_context = None

    # ------------------------------------------------------------------ #
    # Intent-specific data fetching
    # ------------------------------------------------------------------ #
    if intent in ("product_search", "product_details"):
        products = await product_service.search(
            keyword=entities.get("keyword") or entities.get("product_name"),
            category=entities.get("category"),
            min_price=entities.get("min_price"),
            max_price=entities.get("max_price"),
            per_page=6,
        )
        product_data_for_prompt = product_service.format_for_prompt(products)
        product_cards = [
            ProductCard(
                id=p.id,
                name=p.name,
                price=p.price,
                image_url=p.image_url,
                permalink=p.permalink,
                in_stock=p.in_stock,
            )
            for p in products[:4]
        ]
        quick_replies = [
            QuickReply(label="🛒 Add to Cart", payload=f"Add {p.name} to my cart")
            for p in products[:2]
        ]

    elif intent == "add_to_cart":
        # Search for the product the user wants to add
        product_name = entities.get("product_name") or entities.get("keyword") or None
        if product_name:
            products = await product_service.search(keyword=product_name, per_page=3)
            product_data_for_prompt = product_service.format_for_prompt(products)
            product_cards = [
                ProductCard(
                    id=p.id,
                    name=p.name,
                    price=p.price,
                    image_url=p.image_url,
                    permalink=p.permalink,
                    in_stock=p.in_stock,
                )
                for p in products[:3]
            ]
            extra_context = (
                "The user wants to add a product to their cart. "
                "Show the matching products below and let them click the 'Add to Cart' button on the product card."
            )
        else:
            extra_context = "The user wants to add something to their cart but hasn't specified a product yet. Ask them which product they'd like to add."

    elif intent == "order_tracking":
        order_id = entities.get("order_id")
        email = entities.get("email")
        if order_id:
            order = await order_service.get_order(order_id=order_id, billing_email=email)
            if order:
                extra_context = f"Order information:\n{order}"
            else:
                extra_context = "No order was found with those details."
        else:
            extra_context = "The user wants to track an order but hasn't provided an order ID yet."

    elif intent == "faq":
        faq_answer_for_prompt = faq_service.find_answer(message)

    elif intent == "human_handoff":
        handoff = True
        quick_replies = [QuickReply(label="📧 Email Support", payload="email_support")]

    elif intent == "small_talk":
        quick_replies = _DEFAULT_QUICK_REPLIES

    # ------------------------------------------------------------------ #
    # Generate AI reply
    # ------------------------------------------------------------------ #
    reply = await ai_service.chat(
        user_message=message,
        history=request.history,
        extra_context=extra_context,
        intent=intent,
        product_data=product_data_for_prompt,
        faq_answer=faq_answer_for_prompt,
    )

    return ChatResponse(
        session_id=session_id,
        reply=reply,
        intent=intent,
        product_cards=product_cards,
        quick_replies=quick_replies,
        handoff=handoff,
    )
