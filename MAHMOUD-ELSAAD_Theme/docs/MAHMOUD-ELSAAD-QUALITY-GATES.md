# MAHMOUD-ELSAAD Quality Gates

Statuses used in this document: **COMPLETE** · **PARTIAL** · **UNTESTED** · **FAILED** · **NOT APPLICABLE**.

**COMPLETE** means implemented **and** exercised in a WordPress runtime. This file does **not** claim 100% Master Spec completion.

---

## Lab A — SQLite (prior increment)

| Item | Value |
|---|---|
| Path | `/tmp/mes-wp` |
| WordPress | 7.0.4 |
| PHP | 8.3.6 |
| Database | SQLite drop-in |
| URL | `http://127.0.0.1:8088` |
| Runner | `bin/mes-runtime-tests.php` |
| Result | **77 PASS / 0 FAIL** |

---

## Lab B — MariaDB (this production-validation phase)

| Item | Value |
|---|---|
| Path | `/tmp/mes-wp-mysql` |
| WordPress | 7.0.4 |
| PHP | 8.3.6 |
| Database | MariaDB 10.11.14, database `mes_wp` |
| URL | `http://127.0.0.1:8090` |
| HTTPS | Self-signed proxy `https://127.0.0.1:8443` in front of HTTP origin (siteurl remains HTTP) |
| Permalinks | `/%postname%/` |
| Packages | `mahmoud-elsaad-core` 2027.0.0 + `mahmoud-elsaad-theme` (symlinked from this repo) |
| Rank Math | 1.0.276, registration skipped so frontend actually boots |
| 77-suite | **77 PASS / 0 FAIL** (Rank Math-aware `rank_math_compat`) |
| Production harness | `bin/mes-production-validation.php` — **73 PASS / 0 FAIL** |

### SQLite vs MariaDB

No functional difference was observed in the 77-test suite. Differences were lab-only:

- Database engine (SQLite drop-in vs MariaDB)
- Seeded permalink slugs (e.g. `leak-detection` / `muscat` vs earlier SQLite seeds)
- Rank Math installed only on the MariaDB site
- WordPress installer initially wrote a path-style siteurl on MariaDB; corrected to `http://127.0.0.1:8090` before testing

---

## 1. Runtime suite (77 tests)

| Gate | Status | Evidence |
|---|---|---|
| Tables, CPTs, taxonomies, plugin/theme active | COMPLETE | 77-suite on MariaDB |
| Routes `/`, `/ar/`, `/en/`, services, cities, service×city, search, 404 | COMPLETE | HTTP 200 / 404 as asserted |
| Forms render + POST lead | COMPLETE | `mes_sent` + lead count |
| Visual tree compile/inherit | COMPLETE | Runtime assertions |
| REST + Control Center views | COMPLETE | health/visual/forms/settings/SEO/AI/migration |
| Subscriber denied `/mes/v1/health` | COMPLETE | HTTP 403 |
| Brand audit YourColor/`YC_`/`yc_` | COMPLETE | 0 hits in plugin+theme; runtime `hits=0` |
| JSON-LD present | COMPLETE | Rank Math `ld+json` when deferred; MES graph when Rank Math absent or defer off |

---

## 2. Rank Math (live HTML / JSON-LD)

Inspected rendered HTML after Rank Math setup wizard skip (`rank_math_registration_skip` / `RANK_MATH_REGISTRATION_SKIP`). Until that skip, Rank Math defined `RANK_MATH_VERSION` but **did not** init frontend (no canonical, no JSON-LD).

| Check | Status | Evidence |
|---|---|---|
| Activation | COMPLETE | 1.0.276 active |
| Title / meta coexistence | COMPLETE | Rank Math owns `<title>`; MES `Meta` returns early when Rank Math is active |
| Canonical | COMPLETE | `rel=canonical` on home, service, article, FAQ, contact, service×city |
| Robots | COMPLETE | `index, follow` (and WP `max-image-preview` on home) |
| BreadcrumbList JSON-LD | COMPLETE | Present on service, article, FAQ |
| Visual breadcrumbs | PARTIAL | Theme `mes_breadcrumbs()` list markup fixed this phase; Rank Math HTML crumb widget not inserted in templates |
| Article schema | COMPLETE | `BlogPosting` on `/hello-world/` |
| Service schema | COMPLETE | Emitted when Rank Math schema meta `rank_math_schema_Service` exists on the CPT (free Rank Math default snippet for `service` is ignored on the frontend) |
| FAQ schema | COMPLETE | `FAQPage` when `rank_math_schema_FAQPage` exists on a `mes_faq` post. Archive `/faq/` does **not** auto-emit FAQPage |
| LocalBusiness | PARTIAL | Rank Math free knowledge graph emits `Organization` on home, not `LocalBusiness`. MES LocalBusiness is suppressed while defer is on (duplicate prevention) |
| Service × city | PARTIAL | HTTP 200 + canonical; Rank Math graph is `CollectionPage`/`WebSite`, not Service+areaServed |
| Duplicate MES graph | COMPLETE | `mes_schema_should_emit` is false when defer is on; home has no MES `LocalBusiness`. Defer-off adds a second `ld+json` block (MES graph) |
| Defer hook | COMPLETE | Filter `mes_schema_should_emit` |

Rank Math is **not** marked product-COMPLETE for LocalBusiness or automatic FAQ/Service without schema meta.

---

## 3. AI provider

| Check | Status | Evidence |
|---|---|---|
| Credential storage + encrypt/decrypt | COMPLETE | `Crypto` round-trip; option `mes_ai_key_encrypted` |
| Provider selection | COMPLETE | Settings + registry (`openai` / `gemini` / compatible) |
| Invalid key | COMPLETE | REST complete with fake key; plaintext absent from REST body |
| API / transport error | COMPLETE | Compatible endpoint `http://127.0.0.1:8765/fail` → HTTP 500 → local fallback; key not in response |
| Timeout | PARTIAL | `wp_remote_post` timeout 45s; not waited out against a hanging socket this run |
| Rate limit | UNTESTED | No real vendor 429 |
| Empty response | COMPLETE | Registry treats empty body as fallback and logs `Provider empty response` without the key |
| Logging | COMPLETE | Logger redacts `key`/`secret`/`token`/`password`/`authorization`/`bearer` |
| Frontend exposure | COMPLETE | Admin HTML, REST GET settings, `ai-assistant.js`, logs — test key absent |
| Real Gemini/OpenAI key | UNTESTED | `MES_AI_API_KEY` not provided. Optional secret requested; do not commit keys |
| Gemini URL leak | COMPLETE (code) | Key sent as `x-goog-api-key` header, not `?key=` |

---

## 4. Form E2E

Form: Name, Email, Phone, Service, City, File, Message. Actions: lead, email, webhook `http://127.0.0.1:8765/hook`, WhatsApp `+10000000099`.

| Case | Status | Evidence |
|---|---|---|
| A normal submit | COMPLETE | 302 `mes_sent` + lead row |
| B validation | COMPLETE | bad email → `mes_error` |
| C honeypot | COMPLETE | `mes_hp=bot` → no extra lead |
| D nonce | COMPLETE | bad nonce → 400 Invalid form submission |
| E lead data | COMPLETE | `post_content` JSON email `e2e@example.com` |
| F file upload | COMPLETE | HTTP multipart PNG 302 `mes_sent`; `.exe` rejected (`File type not allowed`). WP 7 `wp_handle_upload` always requires `is_uploaded_file` |
| G WhatsApp | COMPLETE | stored number; redirect `https://wa.me/10000000099?...` |
| H email | PARTIAL | `wp_mail` invoked in engine; no mail catcher in this lab |
| I webhook | COMPLETE | payload on local listener (loopback allowed only because `WP_DEBUG`) |
| J webhook failure | COMPLETE | `/fail` 500 still 302 `mes_sent` |
| K malformed | COMPLETE | missing form id/nonce → 400 |
| L unauthorized | COMPLETE | same as bad nonce (public form; capability is not required, nonce is) |

SSRF: `169.254.169.254` and `file://` blocked; unresolved DNS fail-closed.

---

## 5. Visual editor E2E

| Check | Status | Evidence |
|---|---|---|
| Global → Page → Section → Component → Element | COMPLETE | Tree persist/reload |
| Inheritance + child override | COMPLETE | Element inherits global `#111111`; does not write parent `font-size` |
| Desktop / Tablet / Mobile | COMPLETE | Tablet 32px / mobile 22px; desktop CSS omits 22px |
| Save / reload | COMPLETE | `Tree::save` + `Tree::get` |
| Preview iframe | PARTIAL | Control Center iframe `?mes_preview=1` present (384×744). Unauthenticated HTTP 403 (nonce). Admin screenshot shows live preview of homepage |
| Generated CSS | COMPLETE | `@media (max-width: 1024px)` and `640px` |

---

## 6. Migration (clone of legacy types; ZIP untouched)

Original `ServicesTheme(YourColor).zip` was **not** modified. Legacy CPTs were registered on the MariaDB test site and seeded, then `Migrator::run()`.

| ENTITY | SOURCE COUNT | TARGET COUNT | MIGRATED | SKIPPED | FAILED | REASON |
|---|---|---|---|---|---|---|
| faq | 3 | 3 | 3 | 0 | 0 | `faq` → `mes_faq` |
| works | 3 | 3 | 3 | 0 | 0 | `works` → `mes_portfolio` |
| price | 3 | 3 | 3 | 0 | 0 | `price` → `mes_offer` |
| city terms | 2 | 2 | 2 | 0 | 0 | `city` → `mes_city` |
| category → service | 1 | 1 | 1 | 0 | 0 | skips default category |
| options (phone/WA/sitename) | 3 | 2 | 2 | 1 | 0 | sitename skipped when brand name already set; secrets not copied |
| comments | — | — | 0 | — | — | NOT SUPPORTED |
| menus | — | — | 0 | — | — | NOT SUPPORTED |
| generic blog posts | — | — | 0 | — | — | NOT SUPPORTED |
| images | — | — | 0 | — | — | thumbnails copy when present; this clone had none |

`scrapestack_key` / `SHOULD-NOT-COPY` did not appear in `mes_contact_settings` or `mes_brand_settings`.

---

## 7. Security audit

| Surface | Status | Evidence |
|---|---|---|
| REST `mes/v1/*` manage routes | COMPLETE | subscriber + editor 403 on health, forms, AI, migration; admin health 200 |
| Public search | COMPLETE | published titles/URLs only |
| AI settings GET | COMPLETE | no plaintext key |
| Form POST | COMPLETE | nonce + honeypot + sanitization |
| Upload MIME | COMPLETE | allow-list jpg/png/gif/webp/pdf; exe rejected |
| Webhooks SSRF | COMPLETE | http(s) only; metadata IP / file scheme / empty DNS blocked |
| Click tracking | COMPLETE | public + `mes_track_click` nonce; bad nonce 403; ~40/min/IP |
| CSV export | COMPLETE | `mes_manage` + `mes_export_clicks` nonce; subscriber/anonymous HTTP 400 |
| Migration REST/admin | COMPLETE | capability; admin-post nonce |
| Prepared SQL | COMPLETE | `$wpdb->prepare` on clicks/logs/revisions/service-city |
| Path traversal | COMPLETE | `wp_handle_upload` on real uploads |
| Preview | COMPLETE | nonce; unauthenticated 403 |

---

## 8. Lighthouse (Chrome 148, `127.0.0.1:8090`, no network throttle)

Lab INP is unavailable; **TBT** is recorded as the lab interaction proxy. Field INP is **UNTESTED**.

| Page | LCP | TBT | CLS | TTFB | JS | CSS | Requests | Perf | A11y |
|---|---|---|---|---|---|---|---|---|---|
| Home (desktop) | 223 ms | 0 ms | 0.003 | 46 ms | 9.9 KB | 83.9 KB | 18 | 1.00 | 0.98 |
| Home (mobile emulation) | 2356 ms | 0 ms | 0.000 | 48 ms | 9.9 KB | 83.9 KB | 18 | 0.95 | 0.98 |
| Service | 172 ms | 0 ms | 0.003 | 33 ms | 9.9 KB | 83.9 KB | 16 | 1.00 | 0.94 |
| Service × city | 167 ms | 0 ms | 0.003 | 34 ms | 9.9 KB | 83.9 KB | 16 | 1.00 | 0.98 |
| Article | 1120 ms | 0 ms | 0.011 | 36 ms | 9.9 KB | 83.9 KB | 16 | 0.95 | 0.92 |
| Contact | 182 ms | 0 ms | 0.004 | 35 ms | 9.9 KB | 83.9 KB | 16 | 1.00 | 0.94 |
| 404 | — | — | — | — | — | — | — | UNTESTED | Lighthouse aborts on HTTP 404; axe ran (2 moderate) |

No functional regression was “fixed” by stripping CSS/JS. CSS ~84 KB is the dominant payload. Mobile homepage LCP ~2.4 s is the obvious lab hotspot (loader/hero); not optimized in this phase.

Blog is the posts index (same URL as home in this install).

---

## 9. Accessibility (axe-core, wcag2a/aa + wcag21aa + best-practice)

| Violation | Impact | Where | Notes |
|---|---|---|---|
| `color-contrast` | serious | service, article, contact | WhatsApp button `#fff` on `#25d366` (~1.98:1). Not restyled this phase (brand-color CTA) |
| `list` | serious | article breadcrumbs | `<ol class="crumb">` had raw `<a>` children — **fixed** to `<li>` |
| `heading-order` | moderate | most templates | One skipped heading level |
| `region` | moderate | most templates | Mobile drawer / language row outside landmarks — `#mob` wrapped in `<nav>` this phase |

Keyboard: skip link + native buttons/forms present; full keyboard tour of Control Center **UNTESTED**. Reduced-motion CSS exists; OS-level **UNTESTED**.

---

## 10. Browser / device

| Target | Status | Evidence |
|---|---|---|
| Desktop Chrome | COMPLETE | Headless Chrome 148 screenshots: header, nav, hero, cards, footer, floating Call/WhatsApp, contact footer CTAs |
| Desktop Firefox | UNTESTED | `firefox` / `firefox-esr` not installable in this image |
| Android Chrome | PARTIAL | Lighthouse mobile + 390×844 screenshot; no physical Android device |
| Control Center | COMPLETE | Design tree + iframe preview; Forms list/builder chrome |
| Visual iframe | COMPLETE (admin) | `?mes_preview=1` iframe 384×744 |

---

## Gate decision

Critical runtime failures found in this phase were fixed before documenting. The gate for **this increment** is **PASS with documented leftovers**. This is **not** 100% complete.
