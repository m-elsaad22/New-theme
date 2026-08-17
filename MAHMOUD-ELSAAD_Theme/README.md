# MAHMOUD-ELSAAD

Independent WordPress platform for service businesses. Two packages, one product:

| Package | Slug | Role |
|---|---|---|
| **MAHMOUD-ELSAAD Theme** | `mahmoud-elsaad-theme` | Frontend templates, HTML design system, responsive UI |
| **MAHMOUD-ELSAAD Core** | `mahmoud-elsaad-core` | Content, data, Control Center, APIs |

Version **2027.0.0**. Prefix `mes_`. REST `mes/v1`. Requires **PHP 8.2+** and **WordPress 6.6+** (lab-tested on WordPress **7.0.4**, PHP **8.3.6**, MariaDB).

This is a **Production Ready Candidate**. It is **not** a 100% Master Spec claim.

Brand name, phone, and WhatsApp are **never hardcoded**. Set them in Control Center → Settings. Optional **Seed sample content** uses generic copy only.

The legacy archive `ServicesTheme(YourColor).zip` is **not** installed and must stay unmodified.

---

## Theme (`theme/mahmoud-elsaad-theme`)

Presentation only. Disabling the theme does not delete business data.

- Templates for homepage, services, cities, service×city landings, blog, portfolio, offers, reviews, FAQ, booking/contact/quote/legal, search, and a real HTTP 404
- Visual system from the 19 HTML design files (Cairo + Tajawal, navy / turquoise / gold)
- Responsive RTL-first UI (320–1920px)
- Tracked phone and WhatsApp buttons via `mes_render_phone_button()` / `mes_render_whatsapp_button()`
- Homepage sections toggled from Core settings

The theme does **not** register business CPTs, process leads, or run migrations.

---

## Core (`plugins/mahmoud-elsaad-core`)

Persistent behavior:

- **Content:** `service`, `mes_city`, `mes_country`, `mes_offer`, `mes_review`, `mes_portfolio`, `mes_team`, `mes_partner`, `mes_faq`
- **Private:** `mes_lead`, `mes_form`
- **Relations:** Service × City table and public landings
- **Forms:** database-backed builder (CRUD, add/remove/reorder, drag-and-drop), validation, conditionals, lead / email / WhatsApp / webhook actions
- **Tracking:** phone/WhatsApp clicks, REST + admin-ajax, CSV export
- **AI:** provider registry (OpenAI, Anthropic, Gemini, Mistral, OpenRouter, compatible); keys encrypted; local fallback
- **SEO / schema:** internal JSON-LD; Rank Math coexistence (inject missing types only; no duplicate script when defer is on)
- **Visual control:** structured inheritance tree Global → Page → Section → Component → Element, with Desktop → Tablet → Mobile overrides and a **live homepage iframe** preview
- **Control Center:** dashboard, content, landings, forms, analytics, design, SEO, AI, performance, security, settings, migration
- **Migration:** detect → map → transform → validate. Native posts/pages/comments/menus/media stay native. Secrets are not copied.

This visual system is **not** a freeform Elementor-like DOM/page builder.

---

## Install

1. Copy `theme/mahmoud-elsaad-theme` → `wp-content/themes/mahmoud-elsaad-theme`.
2. Copy `plugins/mahmoud-elsaad-core` → `wp-content/plugins/mahmoud-elsaad-core`.
3. Activate **MAHMOUD-ELSAAD Core**, then activate **MAHMOUD-ELSAAD Theme**.
4. Enable pretty permalinks (`/%postname%/`). Serve the site over **HTTPS**.
5. Open **MAHMOUD-ELSAAD → Control Center**.
6. Set brand name, colors, phones, and WhatsApp.
7. Optionally **Seed sample content** (generic; no demo brand or phone).
8. **Design:** publish visual CSS. Preview is the real homepage iframe.
9. **Forms:** edit or create forms. Place `[mes_form type="contact"]` or `[mes_form id="N"]`, or use Contact / Quote / Booking templates. Configure SMTP so `wp_mail` is delivered.
10. **SEO:** complete or skip Rank Math’s wizard. Keep “Defer Rank Math–owned types” on.
11. **Migration:** run only against a reviewed **clone** of a legacy database. Backup first.
12. Flush permalinks if service×city or `/ar/` `/en/` 404.

Do **not** install `ServicesTheme(YourColor).zip` on production.

---

## Tests (lab)

| Suite | Result |
|---|---|
| `bin/mes-runtime-tests.php` | **77 PASS / 0 FAIL** |
| `bin/mes-production-validation.php` | **73 PASS / 0 FAIL** |

Evidence and leftovers: `docs/MAHMOUD-ELSAAD-QUALITY-GATES.md`, `docs/MAHMOUD-ELSAAD-FINAL-COMPLIANCE.md`, `docs/MAHMOUD-ELSAAD-FINAL-REPORT.md`.
