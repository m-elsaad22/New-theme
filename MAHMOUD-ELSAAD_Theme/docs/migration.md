# Migration

Pipeline: Backup → Detect → Map → Transform → Validate.

Control Center → Migration runs `Migrator::run()`.

Maps:

- `faq` → `mes_faq`
- `works` → `mes_portfolio`
- `price` → `mes_offer`
- `city` terms → `mes_city`
- service-like `category` terms → `service`
- contact options → `mes_contact_settings`

API keys are never copied. Source IDs stored in `_mes_migrated_from` / `_mes_migrated_to`.
