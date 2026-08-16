# MAHMOUD-ELSAAD Quality Gates

Statuses used in this document: **COMPLETE** · **PARTIAL** · **UNTESTED** · **FAILED** · **NOT APPLICABLE**.

**COMPLETE** means implemented **and** exercised in a WordPress runtime. This file does **not** claim 100% Master Spec completion.

The platform remains a **Production Candidate**.

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

## Lab B / C — MariaDB production hardening (this increment)

| Item | Value |
|---|---|
| Path | `/tmp/mes-wp-mysql` |
| WordPress | 7.0.4 |
| PHP | 8.3.6 |
| Database | MariaDB 10.11.14, database `mes_wp` |
| Origin | `https://127.0.0.1:8443` (`siteurl` / `home` are HTTPS) |
| Upstream | PHP built-in server `http://127.0.0.1:8090` behind a TLS proxy |
| Permalinks | `/%postname%/` |
| Packages | `mahmoud-elsaad-core` 2027.0.0 + `mahmoud-elsaad-theme` |
| Rank Math | 1.0.276, registration skipped |
| 77-suite | **77 PASS / 0 FAIL** |
| Production harness | **73 PASS / 0 FAIL** |
| Legacy ZIP | `ServicesTheme(YourColor).zip` md5 `d1fa8fe4c9a796b00457bf4e953a27ca` unchanged |

No live WordPress SQL dump exists in the repository. The ZIP is theme PHP + HTML snapshots. Real-content migration used those HTML pages (ركن التطور, phone `+971586634710`, 12 services, 7 cities, 9 FAQs, 6 offers, 8 works) registered as legacy CPTs/options on a clone of this lab site.

---

## 1. Runtime suite (77 tests)

| Gate | Status | Evidence |
|---|---|---|
| Tables, CPTs, taxonomies, plugin/theme active | COMPLETE | 77-suite on MariaDB HTTPS origin |
| Routes `/`, `/ar/`, `/en/`, services, cities, service×city, search, 404 | COMPLETE | HTTP 200 / 404 as asserted |
| Forms render + POST lead | COMPLETE | `mes_sent` + lead count |
| Visual tree compile/inherit | COMPLETE | Runtime assertions |
| REST + Control Center views | COMPLETE | health/visual/forms/settings/SEO/AI/migration |
| Subscriber denied `/mes/v1/health` | COMPLETE | HTTP 403 |
| Brand audit YourColor/`YC_`/`yc_` | COMPLETE | 0 hits in plugin+theme; runtime `hits=0` |
| JSON-LD present | COMPLETE | Single Rank Math `ld+json` block when deferred; MES injects missing types into that graph |

---

## 2. Rank Math schema ownership

Inspected live HTML on `https://127.0.0.1:8443/`. One JSON-LD script tag when `defer_to_rank_math` is on.

| Type / signal | Owner | Status | Evidence |
|---|---|---|---|
| Article / BlogPosting | Rank Math | COMPLETE | Present on `/hello-world/`; MES does not inject these types |
| BreadcrumbList | Rank Math | COMPLETE | Present on service, article, FAQ |
| WebSite / Organization | Rank Math | COMPLETE | Home graph |
| canonical / title / robots / description | Rank Math | COMPLETE | Home + inner pages |
| LocalBusiness (`/#localbusiness`) | **MAHMOUD Core** if Rank Math graph has none | COMPLETE | Count=1 on home; `@id` is `/#localbusiness`, not `/#organization` |
| Service | **MAHMOUD Core** if Rank Math graph has none | COMPLETE | Service CPT page includes `Service`; also present when Rank Math schema meta exists (no second script) |
| FAQPage | **MAHMOUD Core** if Rank Math graph has none | COMPLETE | FAQ singular includes `FAQPage` |
| Service × City | **MAHMOUD Core** Service + `areaServed` if no Service type yet | COMPLETE | `/services/rukn-svc-1/rukn-city-2/` has `Service` + `areaServed`, still 1 JSON-LD block |
| Duplicate JSON-LD | — | COMPLETE | Deferred home `ld_blocks=1`. Defer-off → 2 blocks (intentional overlap) |

Rank Math free still does not natively emit LocalBusiness or Service×City. MES does **not** force Rank Math to generate those types.

---

## 3. AI provider

| Check | Status | Evidence |
|---|---|---|
| Encrypted storage | COMPLETE | `Crypto` round-trip; option wiped after tests |
| Invalid key (real OpenAI) | COMPLETE | `sk-invalid-lab-key` → HTTP 401; user message “key was rejected”; key absent from REST |
| 500 | COMPLETE | Local stub `/fail` → `ok:false` + fallback, no key leak |
| 429 | COMPLETE | Local stub `/429` → rate-limit message |
| Empty body | COMPLETE | Stub `/empty` → empty/invalid message |
| Malformed JSON | COMPLETE | Stub `/malformed` → empty/invalid message |
| Transport failure | COMPLETE | `127.0.0.1:1` → `http_request_failed`; user-facing fallback |
| Hung-socket 45s timeout | PARTIAL | Timeout value is 45s; a full hang was not waited out |
| Frontend / REST / logs | COMPLETE | No plaintext key in admin HTML, GET settings, `ai-assistant.js`, logs |
| Successful real Gemini/OpenAI completion | UNTESTED | `MES_AI_API_KEY` unset. Optional secret requested. Do not commit keys |

---

## 4. Form + email E2E

Mail catcher: local SMTP on `127.0.0.1:1025` writing `.eml` files (Mailpit/MailHog equivalent). PHPMailer SMTP from a lab mu-plugin (not shipped).

| Case | Status | Evidence |
|---|---|---|
| Contact / Quote / Booking submit | COMPLETE | 302 `mes_sent` for forms 43 / 46 / 44 |
| Email generated | COMPLETE | 3 `.eml` files captured |
| Recipient | COMPLETE | `To: leads@example.com` |
| Subject | COMPLETE | `[MES MySQL Test] New {contact\|quote\|booking} lead #N` |
| Sender | COMPLETE | `From: MES MySQL Test <admin@example.com>` |
| Lead data | COMPLETE | Name, phone, email in body |
| Service / city | COMPLETE | Quote + booking include both; contact form has no service field so city only |
| Escaping | COMPLETE | `<script>alert(1)</script>` not present; `&` and quotes sanitized |
| No secrets | COMPLETE | No API keys / Authorization in mail |
| Headers | COMPLETE | `Content-Type: text/plain; charset=UTF-8`; no CR/LF in subject |
| Failure | COMPLETE | SMTP port 1099 closed; lead still 302 `mes_sent` |

File PNG upload COMPLETE (HTTP 302) with `CURLOPT_SSL_VERIFYPEER` off for the self-signed lab cert. `.exe` rejected.

---

## 5. HTTPS origin

`siteurl` / `home` = `https://127.0.0.1:8443`.

| Check | Status | Evidence |
|---|---|---|
| WordPress site URL | COMPLETE | Options are HTTPS |
| Mixed content | COMPLETE | Homepage `http://` attribute count 0 |
| REST | COMPLETE | `https://127.0.0.1:8443/wp-json/` Link header; `/mes/v1/health` 401 unauthenticated |
| AJAX | COMPLETE | No `http://127.0.0.1:8090/wp-admin/admin-ajax.php` in HTML |
| Forms | COMPLETE | POST to HTTPS `admin-post.php` |
| Iframe preview | COMPLETE | `https://127.0.0.1:8443/?mes_preview=1&_wpnonce=…` 384×744 |
| Fonts | COMPLETE | Google Fonts HTTPS |
| Images | COMPLETE | No insecure image URLs on home |
| Tracking | COMPLETE | `mesFront.root` under HTTPS REST |
| WhatsApp | COMPLETE | `https://wa.me/…` |
| Cookies | COMPLETE | `wordpress_test_cookie` `secure; HttpOnly` on `wp-login.php` |
| Admin | COMPLETE | `wp-login.php` HTTP 200 over HTTPS |
| Control Center | COMPLETE | Admin screenshot + keyboard tabs into MAHMOUD-ELSAAD menu |

Production does not require an insecure HTTP origin. The lab PHP `-S` upstream is an implementation detail of this environment.

---

## 6. Migration (real HTML clone; ZIP untouched)

Source: `/tmp/legacy-clone/` (unzipped **copy** of the ZIP). No WP database dump in git.

Seeded from HTML: brand ركن التطور, phone/WhatsApp `+971586634710`, 12 service categories, 7 city terms, 9 FAQs, 8 works, 6 offers, 1 native article + comment, 1 nav menu, 1 PNG attachment as featured image on a works post.

| ENTITY | SOURCE | TARGET | MIGRATED | SKIPPED | FAILED | REASON |
|---|---|---|---|---|---|---|
| Posts | 2 | 2 | 0 | 2 | 0 | Native WordPress posts remain posts. Not converted. |
| Pages | 8 | 8 | 0 | 8 | 0 | Native WordPress pages remain pages. Not converted. |
| Categories | 15 | 20 | 12 | 3 | 0 | Non-default categories → service CPT. Default Uncategorized skipped. Terms remain. |
| City terms | 11 | 43 | 7 | 4 | 0 | `city` → `mes_city`. Already-migrated slugs skipped. Terms remain. |
| FAQ | 18 | 24 | 9 | 9 | 0 | `faq` → `mes_faq`. Source kept. Prior synthetic rows skipped via `_mes_migrated_to`. |
| Works | 17 | 20 | 8 | 9 | 0 | `works` → `mes_portfolio` with client/rating/type/gallery meta. |
| Prices | 15 | 16 | 6 | 9 | 0 | `price` → `mes_offer` with price/discount/icon meta. |
| Comments | 2 | 2 | 0 | 2 | 0 | Native comments remain on their posts. |
| Menus | 1 | 1 | 0 | 1 | 0 | Native nav menus remain. Locations may need reassignment. |
| Media attachments | 1 | 1 | 0 | 1 | 0 | Attachments stay native. Featured-image ID 196 re-linked on the copied CPT. |
| Post meta | 629 | 821 | 23 | 0 | 0 | Mapped keys copied. Unmapped keys remain on source (not deleted). |
| Term meta | 25 | 44 | 19 | 0 | 0 | `icon` → `_mes_icon` when present. Other term meta remains on the term. |
| Theme options | 9 | 9 | 8 | 0 | 0 | sitename/color/social mapped. `scrapestack_key` not copied. |
| Contact options | 2 | 2 | 8 | 0 | 0 | Mail + address mapped. Map iframe may be stripped by `wp_kses_post`; source option remains. |
| Phone | 1 | 1 | 1 | 0 | 0 | `phonenumber` → `phones[]`. Duplicate digits are not appended. |
| WhatsApp | 1 | 1 | 1 | 0 | 0 | `whatsapp_number` → `whatsapps[]`. Duplicate digits are not appended. |
| Schema-related data | 0 | 0 | 0 | 0 | 0 | No legacy schema store. Runtime JSON-LD from migrated content + Rank Math. |
| Images | 1 | 1 | 0 | 1 | 0 | File `2026/08/rukn-eltatawer-work.png` exists in uploads with `_wp_attachment_metadata` (1×1 PNG; no extra thumbnail sizes). |

HTML snapshots contain **0** `<img src>` tags (icon/CSS only). The attachment is a lab file used to prove featured-image re-link.

---

## 7. Security audit

Unchanged from the prior increment except HTTPS cookies (`secure`). Subscriber/editor 403, CSV denied, SSRF/MIME, click nonce: **COMPLETE**.

---

## 8. Lighthouse (Chrome 148, HTTPS origin, `--throttling-method=provided`)

Lab INP is not produced by Lighthouse. **TBT** is the lab interaction proxy. Field INP is **UNAVAILABLE / NOT COLLECTED**.

### Mobile homepage LCP

| | Before (HTTP lab) | After (HTTPS lab, this increment) |
|---|---|---|
| LCP | 2356 ms | **254 ms** |
| LCP element | Full-viewport `#loader` (navy overlay until `window.load` + 700 ms) | `section#home h1` (hero title) |
| Perf / A11y | 0.95 / 0.98 | **1.00 / 1.00** |

Homepage sections were **not** removed. Changes: loader hidden until a slow load (>2s), particles idle-deferred, font weights reduced (Cairo 700/800/900, Tajawal 400/700). Remaining render-blocking: Google Fonts, Font Awesome CDN, `main.css` (~66 KB).

| Page | LCP | TBT | CLS | TTFB | Perf | A11y |
|---|---|---|---|---|---|---|
| Home mobile | 254 ms | 0 ms | 0.005 | 54 ms | 1.00 | 1.00 |
| Home desktop | 239 ms | 0 ms | 0.005 | 58 ms | 1.00 | 1.00 |

Inner templates were not re-scored this increment; no sections were stripped to chase the number.

---

## 9. Accessibility

Homepage axe-core (`wcag2a` / `wcag2aa` / `wcag21aa` / `best-practice`): **0 violations**.

| Item | Before | After |
|---|---|---|
| WhatsApp contrast | `#fff` on `#25D366` (~1.98:1) | `#fff` on `#075E54` (WhatsApp dark green). Hover/focus `#054c44`. Not hidden. |
| Icon-only FABs | Empty label, no `aria-label` | `aria-label="WhatsApp"` / `Call` |
| Text buttons | Icon + text | Same + `aria-label` |
| Focus | Weak | `:focus-visible` 3px gold (navy/gold on dark CTAs) |
| Footer headings | `h4` after section `h2` | `h3` |
| Mobile drawer | Open via click | `aria-expanded`, `aria-controls`, Escape closes, focus moves to close |

Keyboard (Tab on homepage): skip link → logo → search → menu → drawer (close, language, items, WhatsApp, Call) → quote → finder selects.

Remaining: some `<select>` controls (`#fnSvc`, `#fnCity`) use the UA outline (`outline: none` from theme reset is not fully restored). OS-level reduced-motion **UNTESTED**. Visual editor canvas keyboard beyond CC chrome **PARTIAL** (tabs reach MAHMOUD-ELSAAD admin items; iframe interior not key-driven).

---

## 10. Browser / device

| Target | Status | Evidence |
|---|---|---|
| Desktop Chrome | COMPLETE | Headless Chrome 148 + Lighthouse + screenshots (home, CC, design iframe) |
| Desktop Firefox | PARTIAL | Real Firefox 128.0.3 binary extracted and launched `--headless`. Process hung 20s (exit 124) on the self-signed HTTPS origin; no screenshot. Not emulation. |
| Physical Android | UNAVAILABLE | No device attached. Emulation is not counted. |
| Control Center | COMPLETE | Screenshot + HTTPS iframe 384×744 |
| Visual iframe | COMPLETE (admin) | `src` HTTPS |

---

## Gate decision

This increment is **PASS with documented leftovers**. The project is a **Production Candidate**. It is **not** 100% complete.
