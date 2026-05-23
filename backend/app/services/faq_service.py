"""FAQ matching using keyword search and AI-assisted fallback."""

from __future__ import annotations

import json
import re
from pathlib import Path
from typing import Optional

from app.utils.logger import get_logger

logger = get_logger(__name__)

_DATA_FILE = Path(__file__).parent.parent.parent / "data" / "faqs.json"


class FAQService:
    """Load FAQs from JSON and match user queries."""

    def __init__(self) -> None:
        self._faqs: list[dict] = []
        self._load()

    def _load(self) -> None:
        if _DATA_FILE.exists():
            try:
                self._faqs = json.loads(_DATA_FILE.read_text(encoding="utf-8"))
                logger.info("Loaded %d FAQs from %s", len(self._faqs), _DATA_FILE)
            except Exception as exc:  # noqa: BLE001
                logger.error("Failed to load FAQs: %s", exc)
        else:
            logger.warning("FAQ file not found: %s", _DATA_FILE)

    def reload(self) -> None:
        """Hot-reload FAQs without restarting the server."""
        self._load()

    def find_answer(self, query: str) -> Optional[str]:
        """
        Return the best matching FAQ answer or None.
        Uses simple keyword matching; an AI layer adds context on top.
        """
        query_tokens = set(re.findall(r"\w+", query.lower()))
        best_score = 0
        best_answer: Optional[str] = None

        for faq in self._faqs:
            keywords = set(re.findall(r"\w+", faq.get("question", "").lower()))
            keywords |= set(faq.get("keywords", []))
            score = len(query_tokens & keywords)
            if score > best_score:
                best_score = score
                best_answer = faq.get("answer", "")

        # Require at least one keyword overlap
        if best_score >= 1:
            return best_answer
        return None

    def all_questions(self) -> list[str]:
        return [f.get("question", "") for f in self._faqs]


faq_service = FAQService()
