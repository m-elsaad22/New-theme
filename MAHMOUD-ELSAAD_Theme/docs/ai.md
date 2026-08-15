# AI Engine

Provider abstraction: OpenAI, Anthropic, Gemini, Mistral, OpenRouter, OpenAI-compatible.

Keys are encrypted with WordPress salts (`Crypto`) and never returned over REST.

REST: `POST /wp-json/mes/v1/ai/complete`

If no key is configured, a local deterministic fallback still returns usable title/excerpt/FAQ text so the assistant is not a fake panel.
