# MAHMOUD-ELSAAD Quality Gates

Evidence from this phase. A checked result means the test was executed inside WordPress (`/tmp/mes-wp`, WP 7.0.4, PHP 8.3.6, SQLite drop-in, `http://127.0.0.1:8088`) unless noted as static.

Runner: `php /tmp/wp-cli.phar --path=/tmp/mes-wp eval-file MAHMOUD-ELSAAD_Theme/bin/mes-runtime-tests.php`

Latest run: **77 PASS / 0 FAIL**.

---

## Phase A — PHP syntax

| Tests | Result | Failures | Fixes | Remaining |
|---|---|---|---|---|
| `php -l` on plugin + theme PHP | PASS | None | — | No PHPStan/Psalm in this environment |

## Phase B — Activation and schema

| Tests | Result | Failures | Fixes | Remaining |
|---|---|---|---|---|
| Plugin activate `mahmoud-elsaad-core` | PASS (WP-CLI) | — | — | MySQL not used in this lab |
| Theme activate `mahmoud-elsaad-theme` | PASS (WP-CLI) | — | — | — |
| Tables `wp_mes_clicks`, `wp_mes_service_city`, `wp_mes_revisions`, `wp_mes_logs` | PASS | — | — | — |
| CPTs (11) and taxonomies (3) | PASS | — | — | — |
| Seeded countries/cities/forms | PASS | — | — | Demo content is optional |

## Phase C — Frontend routes

| Tests | Result | Failures | Fixes | Remaining |
|---|---|---|---|---|
| `/` homepage | PASS HTTP 200 | — | — | — |
| `/ar/` `/en/` | PASS HTTP 200 | First run 404 | Language-only rewrite + `parse_query` home mapping; do not treat unprefixed URLs as language home | Device-lab RTL QA |
| `/services/` `/cities/` | PASS HTTP 200 | — | — | — |
| Single service / city | PASS HTTP 200 | — | — | — |
| `/services/{svc}/{city}/` and `/ar/services/{svc}/{city}/` | PASS HTTP 200 | — | — | Draft pair 404 not re-hit this run |
| Missing URL | PASS HTTP 404 | Assertion bug (`'404'` array key coerced to int) | Test key renamed `missing` | — |
| `/?s=service` | PASS HTTP 200 | — | — | Result quality not scored |

## Phase D — Forms, leads, tracking

| Tests | Result | Failures | Fixes | Remaining |
|---|---|---|---|---|
| Contact form render + nonce | PASS | — | — | — |
| POST `admin-post.php` → `mes_sent` + lead row | PASS | First run 0 leads (required city + stale count cache) | Send city; count via `$wpdb` | File upload HTTP not run |
| Click persist + `admin-ajax.php` + REST `mes/v1/track-click` | PASS | Payload used `type` instead of `event_type` in an earlier draft | Aligned with `Clicks::persist` | Real browser `sendBeacon` not driven; same REST endpoint was POSTed |
| CSV export hook bound | PASS | — | — | File bytes not downloaded this run |

## Phase E — Visual system

| Tests | Result | Failures | Fixes | Remaining |
|---|---|---|---|---|
| Tree defaults Global→Page→Section→Component→Element | PASS | — | — | Not every inner card has its own node |
| Compile own overrides only; tablet/mobile media queries | PASS | — | — | — |
| Element override inherits global color, does not write parent | PASS | — | — | — |
| Homepage `data-mes-node` in HTML | PASS | — | — | — |
| Control Center Design view + iframe markup | PASS | — | — | Iframe paint not screenshot-verified |
| Reduced-motion rule in compiled CSS | PASS | — | — | OS-level motion not device-tested |

## Phase F — Form builder

| Tests | Result | Failures | Fixes | Remaining |
|---|---|---|---|---|
| Create / rename / duplicate / delete `mes_form` | PASS | — | — | Drag-drop not browser-automated |
| REST `GET /mes/v1/forms` | PASS | — | — | — |
| Control Center Forms view | PASS | — | — | — |
| `sanitize_field` notices | PASS after fix | Undefined `width` / `op` | Null-coalesce before ternary | — |

## Phase G — Control Center, settings, security

| Tests | Result | Failures | Fixes | Remaining |
|---|---|---|---|---|
| Views: dashboard, design, forms, settings, SEO, AI, analytics, migration | PASS | — | — | Mobile bottom nav CSS exists; no device lab |
| REST brand/contact/SEO/AI/homepage sections | PASS | `get_json_params()` empty in `rest_do_request` | Accept body params | — |
| Subscriber denied `/mes/v1/health` | PASS 403 | — | — | — |
| Migration REST | PASS | Extra nonce failed internal REST | Verify nonce only when header present | No live YourColor site imported |
| Brand grep plugin+theme runtime | PASS 0 hits | — | — | Legacy ZIP untouched and still contains old prefixes |

## Phase H — Rank Math, AI, media

| Tests | Result | Failures | Fixes | Remaining |
|---|---|---|---|---|
| Internal JSON-LD on homepage | PASS | — | — | — |
| Rank Math class + defer setting | PASS (plugin **not installed**) | — | — | Coexistence untested with Rank Math active |
| AI settings save | PASS | — | — | Remote complete untested (no key) |
| `mes_media()` exists | PASS | — | — | Upload pipeline not exercised |

---

## Gate decision

Critical runtime failures found in this phase were fixed before continuing. The gate for **this increment** is **PASS with documented leftovers**. This is **not** a claim of 100% Master Spec completion.
