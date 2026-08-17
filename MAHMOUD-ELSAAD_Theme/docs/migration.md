# Migration

Pipeline: Backup → Detect → Map → Transform → Validate.

Control Center → Migration runs `Migrator::run()`.

Maps:

- `faq` → `mes_faq`
- `works` → `mes_portfolio`
- `price` → `mes_offer`
- `city` terms → `mes_city`
- service-like `category` terms → `service`
- contact options → `mes_contact_settings` (email, address, phones, WhatsApp)
- `company__map_code` → trusted HTTPS map URL via `Contact::map_src()` (Google Maps / OpenStreetMap embed only)

**Native WordPress posts, pages, comments, menus, and attachments stay native.** They are counted as skipped, not failed.

API keys are never copied. Source IDs stored in `_mes_migrated_from` / `_mes_migrated_to`.

A live production legacy SQL dump is **UNAVAILABLE** in this repository (HTML snapshots + theme PHP only). The legacy ZIP must remain unmodified (md5 `d1fa8fe4c9a796b00457bf4e953a27ca`).
