"""Application configuration loaded from environment variables."""

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", extra="ignore")

    # OpenAI
    openai_api_key: str = ""
    openai_model: str = "gpt-4"

    # WooCommerce
    woo_store_url: str = ""
    woo_consumer_key: str = ""
    woo_consumer_secret: str = ""

    # Server
    backend_host: str = "0.0.0.0"
    backend_port: int = 8000

    # Redis (optional — falls back to in-memory cache)
    redis_url: str = ""

    # Bot identity
    bot_name: str = "ShopBot"
    welcome_message: str = "Hi! 👋 I'm your shopping assistant. How can I help you today?"

    # CORS origins (comma-separated)
    allowed_origins: str = "*"


settings = Settings()
