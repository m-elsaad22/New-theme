# MAHMOUD-ELSAAD Architecture

## 1. Status and scope

This document is the **architecture contract for the current implementation** under `MAHMOUD-ELSAAD_Theme/`. Paths name files that exist unless marked otherwise.

The system has two deployable application packages:

1. `mahmoud-elsaad-core` — business content, relationships, forms, leads, tracking, APIs, administration, visual inheritance tree, SEO data, AI, and legacy migration.
2. `mahmoud-elsaad-theme` — WordPress templates, HTML-design components, styling, interaction scripts, and presentation-only integration.

The 19 HTML files are the frontend source of truth. The extracted legacy theme is a data/workflow discovery source only.

Visual editing is a **structured inheritance tree** (Global → Page → Section → Component → Element) with Desktop → Tablet → Mobile overrides and a live homepage iframe. It is **not** a freeform Elementor-like DOM/page builder.

Classification of the running product: **PRODUCTION READY CANDIDATE** (see `MAHMOUD-ELSAAD-QUALITY-GATES.md`). Not 100% Master Spec.

## 2. Architecture principles

1. **Persistent behavior belongs in the plugin.** Changing the active theme must not remove services, cities, offers, reviews, portfolios, forms, leads, relationships, or tracking.
2. **Presentation belongs in the theme.** Templates may query plugin services but must not create tables, register business post types, process submissions, or run migrations.
3. **WordPress conventions first.** Use the template hierarchy, registered metadata, REST controllers, capability APIs, nonces, cron/scheduled actions where available, media APIs, and prepared database queries.
4. **Explicit loading and ownership.** No directory glob bootstrap, URL-selected PHP include, or broad unprefixed global helper.
5. **Structured data over serialized UI state.** Register fields and schemas; reserve custom tables for true relationships and append-oriented events.
6. **Progressive enhancement.** Core content and navigation work without JavaScript. JavaScript adds dialogs, filters, counters, carousels, and form affordances.
7. **RTL and multilingual by design.** Arabic is not an afterthought; direction, language linkage, typography, URLs, and fallback behavior are explicit.
8. **Secure defaults.** Deny privileged actions unless capability is proven, validate against server-owned schemas, escape at the final output context, and minimize personal data.
9. **Observable migration.** Every migrated source record has a result and source-target mapping; every skipped reference is reportable.
10. **No secret in code.** Provider credentials come from deployment configuration or an approved secret mechanism, never the repository or content database when avoidable.

## 3. Repository layout

Recommended top-level layout:

```text
MAHMOUD-ELSAAD_Theme/
├── README.md
├── docs/
├── bin/
│   ├── mes-runtime-tests.php
│   └── mes-production-validation.php
├── plugins/
│   └── mahmoud-elsaad-core/
│       ├── mahmoud-elsaad-core.php
│       ├── uninstall.php
│       ├── src/
│       │   ├── Admin/
│       │   ├── AI/
│       │   ├── API/
│       │   ├── Content/
│       │   ├── Database/
│       │   ├── Demo/
│       │   ├── Forms/
│       │   ├── Helpers/
│       │   ├── LegacyMigration/
│       │   ├── Localization/
│       │   ├── Performance/
│       │   ├── Relations/
│       │   ├── Routing/
│       │   ├── Security/
│       │   ├── SEO/
│       │   ├── Support/
│       │   ├── Tracking/
│       │   └── Visual/
│       ├── assets/
│       └── languages/
└── theme/
    └── mahmoud-elsaad-theme/
        ├── style.css
        ├── functions.php
        ├── theme.json
        ├── header.php
        ├── footer.php
        ├── front-page.php
        ├── templates/service-city.php
        ├── page-templates/
        ├── template-parts/
        ├── assets/css/main.css
        └── languages/
```

The `service` CPT key is intentionally unprefixed by product requirement. Hierarchy files are `archive-service.php` and `single-service.php`.

## 4. Naming and ownership

| Concern | Contract |
|---|---|
| Core plugin slug/text domain | `mahmoud-elsaad-core` |
| Theme slug/text domain | `mahmoud-elsaad-theme` |
| Function/hook/option prefix | `mes_` |
| PHP namespaces | `MahmoudElsaad\Core\...`, `MahmoudElsaad\Theme\...` |
| REST namespace | `mes/v1` |
| Protected meta | `_mes_*` |
| Public registered meta | `mes_*` |
| Tables | `$wpdb->prefix . 'mes_*'` |
| Scripts/styles/cron/nonces/transients/cookies | `mes_*` |
| Approved exception | CPT key `service` |

Legacy symbols and global names are permitted only inside `LegacyMigration`, where an adapter may need to identify old options, post types, meta, shortcodes, or menu fields. They must not leak into target public APIs.

## 5. Core plugin

### 5.1 Bootstrap and lifecycle

`mahmoud-elsaad-core.php` should:

- define plugin version and paths;
- load one autoloader;
- instantiate a small plugin kernel;
- register hooks through explicit service providers;
- register activation, upgrade, and deactivation behavior;
- avoid doing database writes or rewrite flushes on ordinary requests.

Activation/upgrade:

1. Verify minimum PHP and WordPress versions.
2. Create or upgrade custom tables with a stored schema version.
3. Register post types and rewrites before one controlled rewrite flush.
4. Schedule approved maintenance/aggregation jobs.
5. Set defaults without overwriting existing settings.

Deactivation must unschedule owned jobs and flush rewrites once. It must not delete business content. Uninstall deletion requires an explicit, separately confirmed retention setting.

### 5.2 Content objects

| Object key | Role | Public frontend | Core supports |
|---|---|---:|---|
| `service` | Service catalog and service landing pages | Yes | title, editor, excerpt, thumbnail, revisions, author as required |
| `mes_city` | City landing page and locality metadata | Yes | title, editor, excerpt, thumbnail, revisions |
| `mes_country` | Country grouping and location metadata | Optional archive/single | title, editor, thumbnail, revisions |
| `mes_offer` | Time-bound or evergreen commercial offer | Yes | title, editor, excerpt, thumbnail, revisions |
| `mes_review` | Moderated customer review/testimonial | Yes or archive-only | title, editor, thumbnail if needed, revisions |
| `mes_portfolio` | Completed work/case study | Yes | title, editor, excerpt, thumbnail, revisions |
| `mes_team` | Team member profile | Usually archive/component | title, editor, thumbnail, page attributes/order |
| `mes_partner` | Partner/client logo and link | Usually component | title, thumbnail, page attributes/order |
| `mes_faq` | Reusable question/answer | Archive/component | title, editor, page attributes/order |
| `mes_lead` | Private contact/booking/service enquiry | No | title generated internally; no public query |
| `mes_form` | Versioned form definition | No | title, revisions; schema stored as validated data |

Registration belongs to classes under `src/Content`. Labels are translatable. Rewrite slugs are filtered/configured by a single routing policy, not scattered options.

Private objects:

- `mes_lead` and `mes_form` must have `public => false`.
- REST exposure is through custom permissioned controllers rather than generic public post endpoints.
- Lead list/edit access uses dedicated capabilities such as `manage_mes_leads`.
- Sensitive fields are excluded from ordinary post search, feeds, sitemap, and frontend queries.

### 5.3 Metadata

Register all runtime meta with:

- exact object subtype;
- scalar/array type;
- single/multiple cardinality;
- sanitize callback;
- authorization callback;
- default where meaningful;
- REST schema only where required.

Representative fields:

| Domain | Fields |
|---|---|
| Translation | `translation_group_id`, `language_code` |
| Service | subtitle, icon/media ID, duration, starting price, currency, featured flag, CTA configuration |
| City | country ID, latitude, longitude, service-area copy, contact overrides |
| Offer | service ID(s), original/current price, currency, starts/ends, terms, CTA |
| Review | rating, reviewer display name, service ID, city ID, source, verified flag, moderation state |
| Portfolio | service ID(s), city ID, client-safe label, completion date, gallery IDs, result metrics |
| Team | role, social links, order |
| Partner | URL, accessible logo name, order |
| FAQ | related service/city, order, schema eligibility |
| Lead | form ID/version, normalized fields, source URL, status, consent timestamp, assignment, notification state |
| Form | schema version, field schema, success behavior, notification policy, active state |

Money must not use binary floating-point for stored arithmetic. Store minor units plus ISO currency, or use a documented decimal representation.

### 5.4 Taxonomies

Create only taxonomies justified by editorial querying:

- a hierarchical service category taxonomy, preferably `mes_service_category`;
- an optional portfolio category taxonomy;
- core `category` and `post_tag` remain for blog posts.

City and country are CPTs, not taxonomies. Do not re-register core `category` globally to attach unrelated business objects without a documented requirement.

## 6. Custom database tables

Custom table names include the WordPress prefix.

### 6.1 Service-city relationship

Suggested logical table: `mes_service_city`.

| Column | Purpose |
|---|---|
| `id` | Unsigned bigint primary key |
| `service_id` | Published or draft `service` post ID |
| `city_id` | `mes_city` post ID |
| `is_active` | Availability switch |
| `sort_order` | Optional local display priority |
| `created_at` / `updated_at` | UTC audit timestamps |

Required constraints/indexes:

- unique key on `(service_id, city_id)`;
- index on `(city_id, is_active, sort_order)`;
- index on `(service_id, is_active, sort_order)`.

WordPress does not enforce foreign keys consistently across deployments, so repositories must validate object type/existence and cleanup relations on permanent deletion. Trashing an object should not necessarily destroy its relationships.

### 6.2 Click tracking

Suggested logical table: `mes_clicks` (actual table name).

| Column | Purpose |
|---|---|
| `id` | Unsigned bigint primary key |
| `occurred_at` | UTC event time |
| `event_type` | Allow-listed CTA type, such as phone, WhatsApp, booking, offer, or contact |
| `object_id` / `object_type` | Optional related object |
| `page_url_hash` | Stable bounded grouping value; avoid storing unnecessary full query strings |
| `language_code` | Active language |
| `campaign` | Sanitized allow-listed campaign identifier when needed |
| `visitor_token_hash` | Optional short-retention, non-reversible abuse/deduplication value |

Do not store message text, phone input, email, full IP address, arbitrary headers, or uncontrolled payloads in click events. Define retention and aggregate old records before deletion. Public collection is rate-limited and accepts only server-defined event types.

### 6.3 Schema migrations

- Keep a `mes_db_version` option.
- Migrations are ordered, idempotent, and safe to rerun.
- Use `dbDelta()` for compatible table evolution and explicit prepared SQL for data backfills.
- Large backfills run in resumable batches, not plugin activation.
- Failed migrations surface a Control Center health notice and do not silently advance the schema version.

## 7. Translation architecture

Every translatable object has:

- `language_code`: normalized BCP 47-compatible product code, for example `ar` or `en`;
- `translation_group_id`: stored as post meta `_mes_translation_group`; stable opaque group identifier shared by equivalent language records.

Rules:

1. One object per language per translation group.
2. Objects in a group must have compatible object types.
3. Deleting one translation does not delete siblings.
4. Queries default to the active language and use a documented fallback only when allowed.
5. Canonical URL points to the current language record.
6. `hreflang` alternates include only published, reachable equivalents.
7. Form labels/messages may be translated, but stored lead values retain the language code used at submission.
8. Service-city relationships are object relationships. If relations vary by localized object, migration and queries must map within the correct translation group rather than guessing by slug.

A plugin adapter may integrate with an approved multilingual plugin later, but the domain identifiers remain stable and importable without that plugin.

## 8. Routing

### 8.1 Standard routes

Use WordPress-generated routes for:

- service archive and singles;
- city, country, offer, review, portfolio, and FAQ archives/singles where public;
- blog home, post singles, categories, authors if enabled, search, pages, and 404.

### 8.2 Service-city routes

The service-city combination needs a narrow rewrite owned by the core plugin. The exact public slug is configurable only through one routing policy. A resolved request must:

1. Parse sanitized service and city slugs into query variables.
2. Resolve published records in the active language.
3. Verify an active row in `mes_service_city`.
4. Set canonical query context and document title.
5. Render `templates/service-city.php`.
6. Return a true 404 for unknown records or inactive pairs.
7. Redirect known legacy variants once to the canonical URL.
8. Prevent faceted/query-string duplicates from becoming separately indexable pages.

Rewrite rules are regenerated on activation, relevant settings change, or an administrator action—not on every request.

### 8.3 HTTP correctness

- Invalid content returns 404, never a home-page 303.
- Permanent accepted legacy URL changes use 301; temporary operational redirects use 302/307 as appropriate.
- Validation failures return 400/422.
- Failed authentication returns 401; failed authorization returns 403.
- Rate limits return 429 with retry guidance.
- APIs return structured WordPress REST errors; frontend pages return the theme error experience.

## 9. REST and action layer

All API endpoints use `mes/v1`. There is no generic action dispatcher.

Candidate controllers:

| Route family | Methods | Permission |
|---|---|---|
| `/forms/{id}/submissions` | `POST` | Public with active form, anti-spam, throttle, and schema validation |
| `/reviews` | `POST` if public review intake is approved | Public with moderation and anti-abuse; never direct publish |
| `/service-city` | `GET` when client filtering needs it | Public, published/active records only |
| `/clicks` | `POST` | Public, allow-listed bounded event schema and throttle |
| `/control-center/...` | As needed | Dedicated administrator capability |
| `/migration/...` | Prefer WP-CLI; guarded admin operations only | Migration capability + nonce for browser action |

Controller responsibilities:

1. Declare method and argument schema.
2. Perform permission checks.
3. Delegate domain work to an application service.
4. Return a stable response DTO.
5. Avoid rendering templates or sending email directly.

## 10. Forms and leads

### 10.1 Form schema

`mes_form` stores a versioned, server-owned schema. Supported fields should be intentionally small:

- text;
- email;
- telephone;
- textarea;
- select/radio/checkbox with server-defined options;
- consent checkbox;
- service/city relation selectors where approved;
- hidden context values populated and verified by the server.

Do not support arbitrary PHP, raw executable HTML, untrusted SVG, arbitrary callback names, or client-defined recipient addresses.

### 10.2 Submission flow

```text
Theme form
  -> POST admin-post.php?action=mes_submit_form
  -> form active/version check
  -> nonce + honeypot
  -> schema validation and normalization
  -> create private mes_lead
  -> email / webhook / WhatsApp actions as configured
  -> redirect with mes_sent (or WhatsApp) + reference
```

Form **definitions** are edited in Control Center (REST `mes/v1` CRUD, drag-and-drop field list). The lead is the system of record. If email fails, the lead remains and the visitor still receives `mes_sent`. Logs contain IDs and error codes, not full submitted personal data.

### 10.3 Retention and access

- Define lead retention by status and business/legal need.
- Support manual deletion/anonymization where required.
- Restrict exports.
- Record consent text version and timestamp when consent is required.
- Never expose lead fields through public REST responses, search, sitemap, feeds, or frontend templates.

## 11. Control Center

`src/Admin/ControlCenter.php` provides one coherent administration entry point:

- Dashboard and health;
- Content (services, cities, countries, offers, reviews, portfolio, team, partners);
- Forms (database-backed builder);
- Leads;
- Service × City landings;
- Analytics;
- Design (visual inheritance tree + live homepage iframe);
- SEO and schema;
- AI;
- Performance;
- Security;
- Settings (brand, contact, map URL allowlist);
- Migration and reconciliation.

Requirements:

- capability per sensitive domain, not only `manage_options`;
- nonces on browser mutations;
- registered settings with sanitizers;
- accessible WordPress admin patterns;
- no frontend CSS/JS loaded globally in admin;
- list-table pagination for large data;
- clear empty, loading, success, partial, and failure states;
- redacted diagnostics;
- no embedded secret values returned to browser after save.

### 11.1 Visual inheritance editor

`src/Visual` stores a tree (Global → Page → Section → Component → Element). Each node has own Desktop / Tablet / Mobile props. The compiler emits only a node's own declarations. Control Center Design loads the **real homepage** in an iframe (`?mes_preview=1`). This is not a freeform page builder.

## 12. Theme and HTML design system

### 12.1 Template map

| HTML reference | Theme target |
|---|---|
| `home.html` | `front-page.php` |
| `about.html` | `page-templates/about.php` |
| `services.html` | `archive-service.php` |
| `service-single.html` | `single-service.php` |
| `service-category.html` | service category taxonomy template |
| `service-city.html` | `templates/service-city.php` |
| `cities.html` | `archive-mes_city.php` |
| `city-single.html` | `single-mes_city.php` |
| `blog.html` | `home.php` |
| `article-single.html` | `single.php` |
| `portfolio.html` | `archive-mes_portfolio.php` |
| `offers.html` | `archive-mes_offer.php` |
| `reviews.html` | `archive-mes_review.php` |
| `booking.html` | `page-templates/booking.php` |
| `contact.html` | `page-templates/contact.php` |
| `faq.html` | `archive-mes_faq.php` |
| `privacy.html`, `terms.html` | `page.php` with legal layout/template |
| `404.html` | `404.php` |

### 12.2 Tokens

The theme exposes the documented token system:

- navy `#0A1F4E`, navy-2 `#1A3A6B`;
- blue `#2980D4`, turquoise `#2E9DF7`, aqua `#4FA8FF`;
- gold `#C9A227`, gold highlight `#F0CE73`;
- footer blue `#124C9C`;
- success `#18C96A`, WhatsApp `#075E54` (contrast-safe; not the brighter `#25D366` brand green);
- background `#F4F8FD`, text `#1C2E44`, secondary text `#3A5068`;
- border `#E2EAF5`, muted `#6B8099`, white `#FFFFFF`.

Tokens live in `theme/mahmoud-elsaad-theme/assets/css/main.css` (`:root`) and are overridable from Control Center. They are mirrored to `theme.json` where WordPress editor support benefits. Dark mode uses `[data-mes-theme="dark"]`; it must preserve contrast and not be implemented by inverting images.

Typography:

- body: Tajawal, Cairo, sans-serif;
- headings/navigation/buttons: Cairo;
- body default: 18px/1.7 and 16px on narrow screens;
- article prose: 17px/1.9;
- fluid headings according to `docs/design-reference.md`.

### 12.3 Component contract

Components should be composable and route-independent:

- site header/mobile navigation;
- site footer/map/contact;
- loader;
- breadcrumbs;
- hero;
- section heading;
- service, city, article, portfolio, offer, and review cards;
- FAQ accordion;
- rating summary;
- price card;
- gallery;
- before/after media;
- filters/pagination;
- form fields and validation summary;
- dialog/popover;
- floating action stack.

Each component:

- has semantic HTML before JavaScript;
- escapes data at output;
- supports RTL and long Arabic labels;
- has keyboard/focus behavior;
- reserves image dimensions;
- handles missing optional data without broken placeholders;
- does not query global state when the caller can pass data explicitly.

### 12.4 JavaScript

Use small modules scoped by `data-mes-*` selectors. No global `$`, no bundled duplicate jQuery, and no inline PHP-generated application script.

Modules may cover:

- mobile navigation;
- sticky/glass header;
- FAQ accordion;
- dialogs;
- filters;
- before/after control;
- review carousel;
- counters;
- form enhancement;
- scroll reveal;
- floating actions;
- theme preference.

All motion honors `prefers-reduced-motion`. A module failure must not hide core content or navigation.

### 12.5 Assets

- Enqueue only owned entry points.
- Use build hashes or package versions for cache invalidation.
- Never dequeue every plugin/core asset.
- Load component assets only where justified, but avoid fragile route condition trees.
- Use WordPress media APIs for `srcset`, sizes, loading priority, decoding, width, and height.
- Google Fonts may use preconnect while configured; self-hosting remains supported without template changes.

## 13. SEO and structured data

SEO data ownership belongs to the core plugin, while theme templates provide visible content and hooks.

The schema graph may include only applicable entities:

- `Organization` or approved `LocalBusiness`;
- `WebSite`;
- `WebPage` and page subtype;
- `BreadcrumbList`;
- `Service`;
- `Article`;
- `FAQPage` when visible qualifying FAQs exist;
- offer and aggregate rating properties only when data meets search-engine and factual requirements.

Rules:

- exactly one coherent JSON-LD graph owner;
- disable or adapt output when an approved SEO plugin owns the same entity;
- canonical URLs follow language and service-city routing;
- breadcrumbs visible in HTML match schema order;
- review aggregates use only published eligible reviews;
- no fabricated price, location, rating, availability, or FAQ data.

## 14. Migration architecture

`src/LegacyMigration` is isolated and removable. It may know old identifiers; normal runtime modules may not.

Components:

- source reader;
- normalizers per old object family;
- ID-map repository;
- media importer;
- shortcode converter;
- form-schema converter;
- menu metadata adapter;
- redirect generator;
- reconciliation reporter;
- WP-CLI commands;
- optional guarded Control Center runner.

Every import operation supports:

- dry-run;
- bounded batches;
- resume cursor;
- idempotent update/skip policy;
- source checksum;
- source ID to target ID map;
- warning and error codes;
- redacted logs;
- final counts.

Do not execute old templates, callbacks, stored PHP, raw SVG, or remote scraping code. Do not migrate embedded credentials.

## 15. Security boundaries

| Boundary | Required controls |
|---|---|
| Public form/review/click routes | Strict schema, size limits, throttle, anti-spam, allow-list, safe response |
| Admin settings | Capability, nonce, sanitize callback, secret redaction |
| Leads | Private object, dedicated capabilities, no public REST/search/feed/sitemap |
| Migration | Highest dedicated capability, dry-run, path/source allow-list, audit log |
| Database | Repository methods, `$wpdb->prepare`, bounded queries, indexes |
| Templates | Escape by context, safe HTML allow-list only when intentional |
| Media/SVG | MIME verification, WordPress media API, SVG sanitization or disallow |
| Integrations | Environment secret, timeout, TLS, retry/circuit behavior, no secret logging |

## 16. Performance and caching

- Avoid database writes during ordinary page rendering.
- Cache stable service-city lookups and invalidate on relevant object/relation changes.
- Cache schema and expensive aggregates by object/version.
- Paginate admin and frontend collections.
- Prevent unbounded meta queries and wildcard option autoload.
- Keep click writes append-only and aggregate in batches.
- Use object cache APIs when available; correctness must not require an external cache.
- Optimize Core Web Vitals from measured templates, not by stripping all WordPress/plugin assets.
- Critical content remains server-rendered.

## 17. Error handling and observability

- Domain services return typed results or `WP_Error`; they do not echo.
- REST controllers map errors to stable codes and appropriate HTTP statuses.
- User messages are localized and do not expose stack traces, SQL, paths, secrets, or personal data.
- Operational logs include request/correlation ID, object ID, action, result code, and duration where useful.
- Control Center health checks cover database schema, rewrites, scheduled work, notification transport, and configuration—not raw secrets.
- Migration reports are downloadable only by authorized administrators and are redacted.

## 18. Dependency direction

Allowed:

```text
Theme templates -> plugin public query/facade APIs
REST/Admin controllers -> application services
Application services -> repositories/domain policies
Repositories -> WordPress APIs/custom tables
LegacyMigration -> target application services/repositories
```

Disallowed:

```text
Core plugin -> active theme PHP classes
Theme -> direct custom-table SQL
Templates -> lead creation/email/database migration
Normal runtime -> legacy adapters
Client request -> PHP filename/include
```

If the core plugin is inactive, the theme should show an administrator notice and degrade safely for business templates. It must not fatal-error.

## 19. Remaining limitations (not product guesses)

Resolved in the current packages: visual inheritance editor, form builder, Rank Math inject-missing types, Service × City routes, MariaDB/HTTPS lab.

Still **not** claimed complete:

1. Public URL slug policy per language beyond `/ar/` `/en/` prefix stripping — **PARTIAL**.
2. Whether visitors may submit reviews publicly — product policy; `mes_review` is editorial.
3. Live currency conversion — not required; display currency from settings.
4. Field INP — **UNAVAILABLE** / not collected.
5. Real Gemini/OpenAI successful completion — **UNTESTED**.
6. Firefox on trusted HTTPS — **UNTESTED** (environment limitation).
7. Physical Android — **UNAVAILABLE**.
8. Freeform Elementor-like page builder — **OUT OF SCOPE**.

None of these permits copying legacy secrets or restoring the dynamic Ajax dispatcher.

