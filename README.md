# 🤖 AI WooCommerce Chatbot

An AI-powered shopping assistant for WordPress / WooCommerce stores — inspired by WoowBot Pro.  
Built with **Python FastAPI + OpenAI GPT-4** (backend) and a **custom WordPress plugin + vanilla JS widget** (frontend).

---

## ✨ Features

| Feature | Description |
|---|---|
| **Product Search & Recommendations** | Ask "show me red sneakers under $50" — GPT understands intent and fetches matching products |
| **Rich Product Cards** | Products shown as visual cards with image, price, and "Add to Cart" button |
| **Order Tracking** | Look up order status by order ID + billing email |
| **FAQ Handling** | Keyword-matched answers from a customisable FAQ database |
| **Add to Cart** | Add items directly from the chat window |
| **Exit Intent** | Shows a chat prompt when the visitor is about to leave |
| **Cart Abandonment** | Nudges users who have cart items and go inactive |
| **Lead Capture** | Collect customer email/name during the conversation |
| **Admin Settings** | Bot name, welcome message, colour, position, behaviour toggles |
| **FAQ Manager** | Add / delete custom FAQs from the WordPress admin |
| **Analytics Dashboard** | Session counts, message counts, popular query keywords |

---

## 🏗️ Architecture

```
WordPress Site
 ├── Chat Widget (JS/CSS) ──► WordPress REST API (PHP Plugin)
 │                                │
 │                                ▼
 │                         Python FastAPI Backend
 │                                │
 │                          ┌─────┴─────┐
 │                          │  OpenAI   │  WooCommerce
 │                          │  GPT-4    │  REST API v3
 │                          └───────────┘
```

---

## 📁 Project Structure

```
ai-woo-chatbot/
├── backend/                         # Python FastAPI service
│   ├── app/
│   │   ├── main.py                  # FastAPI entry point + CORS
│   │   ├── config.py                # Environment-based configuration
│   │   ├── routes/                  # chat, products, orders, leads
│   │   ├── services/                # ai, intent, product, order, faq, recommendation
│   │   ├── models/                  # Pydantic schemas
│   │   ├── prompts/                 # GPT system / product / FAQ prompts
│   │   └── utils/                   # woo_client, cache, logger
│   ├── data/faqs.json               # Default FAQ entries
│   ├── tests/                       # pytest tests
│   ├── requirements.txt
│   ├── Dockerfile
│   └── docker-compose.yml
│
└── wordpress-plugin/                # WordPress / WooCommerce plugin
    ├── ai-woo-chatbot.php           # Main plugin bootstrap
    ├── includes/                    # PHP core classes
    ├── admin/                       # Admin settings, analytics, FAQ manager
    ├── assets/
    │   ├── js/                      # chat-widget.js, exit-intent.js, cart-watcher.js
    │   ├── css/                     # chat-widget.css, admin.css
    │   └── images/bot-avatar.svg
    └── templates/                   # chat-window.php, product-card.php
```

---

## 🚀 Quick Start

### 1. Backend (Python FastAPI)

**Prerequisites:** Python 3.11+, (optional) Redis, Docker

```bash
cd backend
cp ../.env.example .env
# Edit .env with your OpenAI API key and WooCommerce credentials

pip install -r requirements.txt
uvicorn app.main:app --reload
# API running at http://localhost:8000
```

**Or with Docker Compose:**
```bash
cd backend
docker-compose up --build
```

### 2. WordPress Plugin

1. Copy the `wordpress-plugin/` folder to `wp-content/plugins/ai-woo-chatbot/`
2. Activate **AI WooCommerce Chatbot** in *Plugins → Installed Plugins*
3. Go to **AI Chatbot → Settings**
4. Set the **Backend API URL** to your FastAPI server (e.g. `http://your-server:8000`)
5. Optionally configure bot name, colour, and behaviour toggles

---

## ⚙️ Configuration

All backend settings are loaded from environment variables (see `.env.example`):

| Variable | Description |
|---|---|
| `OPENAI_API_KEY` | Your OpenAI API key |
| `OPENAI_MODEL` | Model to use (default: `gpt-4`) |
| `WOO_STORE_URL` | Your WooCommerce store URL |
| `WOO_CONSUMER_KEY` | WooCommerce REST API consumer key |
| `WOO_CONSUMER_SECRET` | WooCommerce REST API consumer secret |
| `REDIS_URL` | Redis URL (optional — in-memory fallback used when empty) |
| `ALLOWED_ORIGINS` | CORS-allowed origins (comma-separated) |

---

## 🔌 Backend API Reference

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/chat` | Main chat — intent detection + GPT response |
| `GET`  | `/api/v1/products/search` | Search products (`?q=&category=&min_price=&max_price=`) |
| `GET`  | `/api/v1/products/{id}` | Single product details |
| `GET`  | `/api/v1/products/categories/list` | All product categories |
| `GET`  | `/api/v1/order/track` | Order lookup (`?order_id=&email=`) |
| `POST` | `/api/v1/leads` | Save a lead |
| `GET`  | `/health` | Liveness probe |

---

## 🧪 Running Tests

```bash
cd backend
pip install -r requirements.txt
pytest tests/ -v
```

---

## 🔒 Security Notes

- API keys are stored server-side only — never exposed to the browser
- Cart `add` endpoint requires a WordPress nonce (`X-WP-Nonce` header)
- Order tracking verifies billing email before returning order details
- All WordPress inputs are sanitised with native WP functions
- CORS origins are configurable — restrict to your store domain in production

---

## 📄 Licence

GPL-2.0-or-later
