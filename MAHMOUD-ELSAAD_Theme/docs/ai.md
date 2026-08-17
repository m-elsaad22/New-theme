# AI Engine

Provider abstraction: OpenAI, Anthropic, Gemini, Mistral, OpenRouter, OpenAI-compatible.

Keys are encrypted with WordPress salts (`Crypto`) in option `mes_ai_key_encrypted` and **never** returned over REST GET, admin HTML, or logs.

REST: `POST /wp-json/mes/v1/ai/complete` (managers only). Gutenberg sidebar (`ai-assistant.js`) posts the same route and displays `text` / `fallback` / `message`.

If no key is configured, a local deterministic fallback still returns usable title/excerpt/FAQ text.

| Path | Status |
|---|---|
| Encrypt/decrypt, invalid key (OpenAI 401), 429/500/empty/malformed stubs, transport error | TESTED |
| Successful real Gemini/OpenAI completion | UNTESTED (`MES_AI_API_KEY` unset). Do not commit keys. |
| Hung-socket 45s timeout | PARTIAL (timeout value is 45s; a full hang was not waited out) |

Gemini tries current model ids (`gemini-2.0-flash`, then 1.5 flash) on HTTP 404. That is the existing provider, not a new product surface.
