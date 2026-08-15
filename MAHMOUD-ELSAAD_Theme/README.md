# MAHMOUD-ELSAAD Theme

WordPress platform (theme + core plugin) for service businesses.

## Install

1. Copy `theme/mahmoud-elsaad-theme` to `wp-content/themes/`.
2. Copy `plugins/mahmoud-elsaad-core` to `wp-content/plugins/`.
3. Activate **MAHMOUD-ELSAAD Core**, then activate **MAHMOUD-ELSAAD Theme**.
4. Open **MAHMOUD-ELSAAD → Control Center**.
5. Set brand name, colors, phones, and WhatsApp (never hardcode numbers).
6. Optionally click **Seed sample content** (generic services/FAQs/pages — no demo brand or phone).
7. Run **Migration** if a legacy ServicesTheme site exists.
8. Flush permalinks.

Requires PHP 8.2+ and WordPress 6.6+.

The original `ServicesTheme(YourColor).zip` is left unchanged.

## What you get

- Service CPT, cities, countries (UAE/SA/QA/OM/BH/KW/EG pre-seeded), offers, reviews, portfolio, team, partners, FAQs, leads, forms
- Dynamic `/ar|en/services/{service}/{city}/` landings with overrides
- Tracked phone/WhatsApp buttons (`mes_render_phone_button` / `mes_render_whatsapp_button`)
- Control Center (dashboard, analytics, design, SEO, AI, migration, service×city)
- HTML design system (Cairo + Tajawal, navy/turq/gold)
- Rank Math coexistence (no duplicate schema)
- AI provider abstraction with encrypted keys
