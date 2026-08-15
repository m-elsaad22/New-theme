# MAHMOUD-ELSAAD Legacy Theme Audit

## 1. Purpose and decision rule

This document classifies the code extracted from `ServicesTheme(YourColor)` and defines what may cross into the new MAHMOUD-ELSAAD platform. It is an audit and migration contract, not a statement that the new runtime has already been implemented.

The governing rule is:

> Preserve business data and proven user outcomes; do not preserve unsafe implementation patterns, legacy visual output, credentials, accidental behavior, or broken integrations.

The 19 HTML files in `/tmp/theme-analysis/*.html` are the visual source of truth. The old PHP and CSS are useful only for discovering data, editorial capabilities, and business workflows. They are not a visual reference.

### Classification vocabulary

| Classification | Meaning | Migration action |
|---|---|---|
| **PRESERVE** | The outcome and data model remain valid. | Reimplement with the new plugin/theme boundaries and `mes_` namespace. |
| **IMPROVE** | The capability remains useful, but its behavior, security, accessibility, or maintainability must change. | Rebuild and validate against explicit acceptance criteria. |
| **COMPATIBILITY ONLY** | Needed temporarily to read or translate existing data/URLs. | Isolate under `LegacyMigration`; never make it the default frontend path. |
| **MIGRATION ONLY** | Used once or during a controlled migration window. | Admin-only, capability-checked, observable, and removable after sign-off. |
| **DEPRECATED** | No longer part of the target product. | Do not expose in the new runtime. Preserve source data only when required. |
| **BROKEN** | The old code references missing registrations, invalid APIs, or incomplete logic. | Do not port. Recover intent from data and requirements instead. |
| **ORPHAN** | Code exists but is not connected to a complete working UI flow. | Do not treat it as a working feature; replace only if the product needs it. |
| **UNUSED** | Empty, temporary, duplicated, or not required by the new product. | Exclude from migration. |
| **SECRET** | Contains an embedded credential or secret-like value. | Never copy the value. Revoke/rotate it and use environment-backed configuration if the integration is approved. |

## 2. Sources reviewed

- Legacy bootstrap: `functions.php`, `syntax.php`, `index.php`, and `style.css`.
- Package tree: `components/packs/**`.
- Legacy styles and JavaScript: `components/styles/**` and package assets.
- New visual reference: 19 HTML pages:
  `home`, `about`, `services`, `service-single`, `service-category`, `service-city`,
  `cities`, `city-single`, `blog`, `article-single`, `portfolio`, `offers`,
  `reviews`, `booking`, `contact`, `faq`, `privacy`, `terms`, and `404`.
- Existing design summary: `docs/design-reference.md`.

This is a static source audit. Runtime behavior that depends on the old database, installed plugins, rewrite rules, server configuration, or production options must be verified during migration rehearsals.

## 3. Executive findings

1. The old theme is a framework, application, admin toolkit, data model, and frontend combined in one theme. Switching themes can therefore remove application behavior. The replacement must put persistent business behavior in the `mahmoud-elsaad-core` plugin and rendering in `mahmoud-elsaad-theme`.
2. Rendering bypasses the normal WordPress template hierarchy through `ThemeStatic::Locate()`, then finds `@` “Blade” folders and `#` parts by scanning package directories. This is custom terminology, not the Blade template engine.
3. Every package directory whose name does not begin with `@` or `#` is automatically expected to contain `setup.php`. This implicit loading model makes activation order and side effects hard to reason about.
4. The old theme registers only `faq`, `price`, and `works` post types. Several templates depend on an unregistered `services` taxonomy, and `questions` is attached to an unregistered `bot` post type.
5. The custom `/AjaxCenter/` endpoint dynamically requires a PHP file based on a URL segment. Individual handlers generally do not share a standard nonce, authentication, authorization, validation, or error contract. It must not be copied.
6. The old enqueue package dequeues or deregisters all queued scripts and many WordPress styles at very late priority. This can break plugins, accessibility, embeds, comment replies, analytics, and editor/frontend parity.
7. The form-builder artifacts contain potentially valuable configuration data but rely on an unregistered `yc-froms` post type and an unregistered `services` taxonomy. They are migration inputs, not a runtime to preserve.
8. `contact__form` defines fields but its widget renderer never outputs those fields or a `<form>`. Its matching Ajax handler exists separately. Both are orphans, not evidence of a complete contact feature.
9. A scraping helper and the currency module contain hardcoded provider credentials. Credential values are intentionally omitted from this document. They must be rotated/revoked and must never enter the new repository.
10. The old 404 path redirects to the home page with status 303 before its 404 Blade can render. This destroys the correct 404 response and must be replaced by a real, helpful 404 page.

## 4. Legacy bootstrap and rendering

### 4.1 Bootstrap inventory

`functions.php`:

- changes PHP upload, post, and execution limits from theme code;
- starts output buffering;
- declares the global `ThemeTree` class;
- provides generic wrappers for post-type and taxonomy registration;
- scans `components/packs/*/`;
- requires `syntax.php`;
- instantiates global `$ThemeTree` and `$ThemeStatic` objects;
- loads `setup.php` from every package not prefixed with `@` or `#`;
- removes WordPress's shutdown output-buffer flush action.

`syntax.php` declares `ThemeStatic`, which:

- hooks `template_redirect` and intercepts nearly every frontend request;
- rewrites query-string pagination and search URLs by redirect;
- redirects every 404 to the home page;
- dispatches home, author, archive, single, search, page, and 404 views;
- loads `@{page}/shape.php`, or a post-type-specific PHP file;
- loads reusable `#{part}/part.php`;
- contains unrelated login, HTTP fetching, upload, slug, URL, and pagination helpers.

`index.php` is only a bootstrap/fallback entry in this architecture. `style.css` identifies the old package as “YOURCOLOR THEME 2024.” `components/index.php` is empty.

### 4.2 Package naming behavior

| Legacy package name | Runtime meaning | Audit result |
|---|---|---|
| ordinary folder, for example `schema` | Auto-load `setup.php` during theme bootstrap. | **DEPRECATED implementation**; use explicit plugin/theme bootstraps. |
| `#header`, `#footer`, `#PriceBoxes` | Rendered by `ThemeStatic::Part()`. | **COMPATIBILITY ONLY** as a migration concept; use normal theme template parts. |
| `@single`, `@search`, `@Mega-Menu` | Rendered by `ThemeStatic::Blade()`. | **DEPRECATED implementation**; use the WordPress template hierarchy. |

The package scan performs eager filesystem discovery on every request and makes missing `setup.php` files runtime concerns. New code must use explicit includes, small service classes, and deterministic hook registration.

### 4.3 Routing classification

| Legacy behavior | Classification | New decision |
|---|---|---|
| Custom `ThemeStatic::Locate()` template router | **DEPRECATED** | Use WordPress template hierarchy and narrow rewrite handlers owned by the core plugin. |
| `@` Blade lookup | **DEPRECATED** | It is not Laravel Blade and provides no escaping or compilation guarantees. |
| `#` part lookup | **REIMPLEMENT** | Use `get_template_part()` with typed view data and escaping at output. |
| Pretty `/search/.../` intent | **IMPROVE** | Keep only if canonical URL and redirect tests prove it is SEO-safe; otherwise use WordPress search routing. |
| Query endpoint `/AjaxCenter/` | **DEPRECATED — SECURITY** | Replace with explicit REST routes or `admin-ajax.php` actions with shared policy enforcement. |
| 404-to-home redirect | **BROKEN SEO/HTTP** | Return status 404 and render `404.php` based on `404.html`. |

## 5. Data model audit

### 5.1 Registered post types

| Old object | Old registration | Classification | Target |
|---|---|---|---|
| `faq` | Public; title and editor; no custom rewrite | **PRESERVE DATA / REIMPLEMENT** | Migrate to `mes_faq`. |
| `price` | Public; title and editor; slug from `plans_url` option | **PRESERVE DATA / REIMPLEMENT** | Migrate price-plan content into `mes_offer` or service pricing structures according to the mapping worksheet. Do not register a duplicate type by default. |
| `works` | Public; title, editor, thumbnail; `/works` rewrite | **PRESERVE DATA / REIMPLEMENT** | Migrate to `mes_portfolio`. |

### 5.2 Taxonomies

| Old taxonomy | Attached objects | Classification | Target |
|---|---|---|---|
| Core `category` re-registered for `post` + `works` | Posts and work items | **IMPROVE** | Keep post categories for posts. Map work categories to an explicit portfolio taxonomy if required; avoid changing core taxonomy registration globally. |
| `city` | `post` only; non-hierarchical; dynamic slug | **PRESERVE DATA / REIMPLEMENT** | Migrate terms into the `mes_city` post type so cities can have first-class pages, fields, translations, and relationships. |
| `questions` | `bot` | **BROKEN** | `bot` is not registered. Do not port. Migrate useful question content into `mes_faq` only after source validation. |
| `services` | Referenced by forms, breadcrumbs, single templates, and UI fields | **BROKEN / UNREGISTERED** | Recover terms if present in the database, map them to the `service` CPT and its category model, and report unmatched IDs. |

### 5.3 Target content model

The new core plugin owns:

- `service`
- `mes_city`
- `mes_country`
- `mes_offer`
- `mes_review`
- `mes_portfolio`
- `mes_team`
- `mes_partner`
- `mes_faq`
- `mes_lead`
- `mes_form`

The unusual unprefixed `service` slug is an explicit product decision; all PHP functions, classes, hooks, options, meta keys, REST namespaces, table names, script handles, and non-core object names still use `mes_`.

Service availability by city is many-to-many and belongs in a custom relation table rather than duplicated posts or serialized post meta. Click tracking uses a separate append-oriented table. Translation linkage uses `translation_group_id` plus `language_code` on translatable objects.

## 6. Widget inventory

The legacy “widgets” are records in a custom page-section machine, not standard `WP_Widget` instances. Their saved configuration can be a migration source. Their PHP markup and CSS are not visual references.

| Legacy widget | Observed intent | Classification | Migration decision |
|---|---|---|---|
| `slider_intro_v1` | Homepage hero/intro slider | **REIMPLEMENT** | Build the HTML-design hero. Migrate slides only when content remains current; avoid mandatory carousel behavior. |
| `after__intro` | Feature/service summary immediately after hero | **REIMPLEMENT** | Map text, icons, and links to homepage design sections. |
| `sticky__features` | Sticky feature or service list | **IMPROVE** | Rebuild responsively and keyboard-accessibly; no reliance on legacy animation classes. |
| `benefits` | Benefit cards | **PRESERVE CONTENT / REIMPLEMENT UI** | Map reusable benefit data to theme sections or service fields. |
| `Faqs__simple2` | FAQ accordion | **REIMPLEMENT** | Query `mes_faq`; accessible buttons, ARIA state, and FAQ schema only for visible content. |
| `contact__form` | Contact details, social links, counters, and nominal form | **ORPHAN** | It defines four fields but does not render them. Replace with `mes_form` + `mes_lead`; do not claim backward functional parity. |
| `category` | Category cards/list | **REIMPLEMENT** | Render service categories or blog categories contextually. |
| `blog_v1` | Recent/filtered posts | **REIMPLEMENT** | Use normal `WP_Query`, pagination, and HTML card components. |
| `city__widget` | City cards/list | **REIMPLEMENT** | Query `mes_city` and service-city relations. |
| `price` | Pricing cards | **REIMPLEMENT** | Use offer/service pricing data and the new design-system cards. |
| `works_v1` | Portfolio/work cards | **REIMPLEMENT** | Query `mes_portfolio`. |
| `single__blog` | Single-post sidebar/content block | **REIMPLEMENT** | Build article components from `article-single.html`; avoid custom global widget state. |
| `posts__video` | Video post block | **IMPROVE** | Preserve only approved media fields; use responsive embeds, consent/loading policy, and WordPress oEmbed where safe. |
| `rating__widget` | Rating summary/input | **REIMPLEMENT** | Source from moderated `mes_review` data; calculate server-side and prevent duplicate/abusive submissions. |
| `page_url` | Page URL/link block | **REIMPLEMENT** | Use standard link fields and safe URL escaping. |

## 7. Ajax and interactive behavior

### 7.1 Dispatcher risk

`AjaxCenter/setup.php` adds a root endpoint and derives `$Action` from the request path, then requires `components/packs/AjaxCenter/$Action.php`. The dispatcher does not demonstrate an allow-list before including the file. The endpoint also bypasses the normal WordPress REST/Ajax registration model.

This architecture is **DEPRECATED — SECURITY**. The replacement must provide:

- an explicit allow-list through route registration;
- method constraints;
- nonce or application-level authentication where appropriate;
- capability checks for privileged actions;
- strict schema validation and sanitization;
- escaped output;
- consistent JSON and HTTP status codes;
- throttling/spam controls for public submissions;
- no user-controlled PHP include path.

### 7.2 Handler classification

| Handler | Old purpose | Classification | New disposition |
|---|---|---|---|
| `AddComment.php` | Insert comments, optional rating/type meta, recalculate aggregate | **IMPROVE** | Use WordPress comment APIs only for blog comments; reviews use moderated `mes_review`. Add nonce, rate limiting, validation, and divide-by-zero protection. |
| `CommentContent.php` | Return comment content by ID | **IMPROVE** | Usually unnecessary; if retained, expose only public approved content through a read-only route. |
| `contact__form.php` | Email submitted contact fields | **ORPHAN / DEPRECATED** | The paired widget does not render a form. Replace with lead creation, notification service, consent, anti-spam, and audit status. |
| `fields-loadmore.php` | Paginated field/form-builder results | **MIGRATION ONLY** | Do not expose publicly. New admin lists use WordPress admin APIs. |
| `forms__services.php` | Multi-step service request processing | **REIMPLEMENT** | Validate a `mes_form` schema, create `mes_lead`, then dispatch notifications asynchronously/reliably. |
| `MenusInitialize.php` | Dynamic menu payload | **COMPATIBILITY ONLY** | Use standard menu rendering/caching; only translate legacy menu metadata when migrating. |
| `More-Ajax-objects.php` | Generic additional object loading | **DEPRECATED** | Replace generic action multiplexing with resource-specific routes. |
| `PopoverActions.php` | Popover step/action rendering | **REIMPLEMENT** | Use a narrow form-step API if the new booking flow needs server-driven steps. |
| `RateAjax.php` | Rating submission/update | **REIMPLEMENT** | Use review moderation, anti-replay controls, normalized rating bounds, and aggregate recalculation jobs. |
| `ReadMoreObject.php` | Lazy/read-more content | **IMPROVE** | Prefer complete semantic HTML or a read-only paginated endpoint; retain crawlable canonical content. |
| `TabsActions.php` | Dynamic tab content | **REIMPLEMENT** | Use accessible tabs; server calls only when data volume justifies them. |

## 8. Forms and leads

### 8.1 Mysterious form files

| Artifact | Finding | Classification |
|---|---|---|
| `@wp-models/edit-forms.php` | Creates/updates `yc-froms` records and serialized `TempForms`; links them to unregistered `services`; directly consumes request data. | **LEGACY / MIGRATION ONLY** |
| `@wp-models/AllForms.php` | Lists and searches `yc-froms` records, with custom pagination/load-more. | **LEGACY / MIGRATION ONLY** |
| `yc-froms` post type | Referenced but not registered in the extracted theme. Typo appears to be persisted as an identifier. | **LEGACY DATA / UNREGISTERED** |
| `YourColorWidgets/.../contact__form.php` | Declares name/email/phone/description fields but renders contact details and counters only. | **ORPHAN** |
| `AjaxCenter/contact__form.php` | Sends an email when `user__name` is present; no cohesive connection to a rendered widget form. | **ORPHAN** |

Legacy form data must be imported through a deterministic transformer:

1. Read old `yc-froms` posts and their `TempForms`, `actionType`, and mail-format metadata.
2. Decode serialized arrays without executing code.
3. Normalize supported field types to a versioned `mes_form` schema.
4. Reject or quarantine raw HTML, PHP, unknown field types, invalid taxonomy references, and executable icon/code fields.
5. Map old service term IDs to new `service` IDs using an explicit ID map.
6. Produce counts for imported forms, skipped forms, field conversions, and unresolved references.
7. Require an administrator to review and activate each imported form.

Runtime submissions create `mes_lead` records. Email is a notification side effect, not the system of record.

## 9. Shortcodes

The old theme registers:

- `[post_prices]`
- `[post_steps]`
- `[post_features]`
- `[post_gallery]`
- `[post_call]`
- `[post_services]`

These shortcodes combine field registration, content storage, and output. They are **COMPATIBILITY ONLY** during migration.

The migration process must parse post content and:

- convert known shortcodes to blocks, structured post meta, or theme components;
- preserve surrounding Arabic content and shortcode order;
- report unknown/malformed attributes;
- avoid rendering old PHP/CSS in the new theme;
- optionally retain inert compatibility renderers for a time-boxed window;
- remove compatibility only after a content scan reports zero active occurrences.

## 10. Header, footer, navigation, and mega menu

The old `#header` and `#footer` contain substantial inline behavior, option lookups, social/contact data, search UI, floating buttons, lightbox setup, and JavaScript globals. New shared chrome comes from the HTML design system:

- 80px header, reducing to 70px glass treatment after scroll;
- full-screen navy mobile panel;
- four-column footer with map and copyright;
- floating action stack after scroll;
- configurable brand, contact, social, and map values;
- RTL-first keyboard navigation and visible focus states.

### Mega menu decision

Mega menu is **LEGACY — COMPATIBILITY ONLY** and is not the default frontend navigation. The HTML designs explicitly use standard navigation.

- `@Mega-Menu/taxonomy.php` and `@Mega-Menu/post.php` reveal useful legacy menu metadata that may be translated.
- `@Mega-Menu/Taxonomy-posts.php` is **BROKEN**: it calls `get_term_by()` with an invalid argument list, prints placeholder text, uses apparently undefined variables, duplicates branches, and has incomplete control flow.
- The new compatibility layer may read old menu-item metadata and render a safe, simplified submenu.
- It must be disabled by default and removable after menus are manually reviewed.

## 11. SEO, schema, search, breadcrumbs, and HTTP behavior

| Area | Legacy finding | Classification and replacement |
|---|---|---|
| SEO titles | `ThemeSeo` builds titles from options and query context, otherwise uses older title APIs. | **IMPROVE**: use `title-tag`, canonical WordPress APIs, and avoid duplicate output with SEO plugins. |
| Schema | A large `YourColor__Schema` class prints schema from theme options. | **REIMPLEMENT**: small JSON-LD graph service with valid `Organization/LocalBusiness`, `WebSite`, `WebPage`, `BreadcrumbList`, `Service`, `Article`, and eligible FAQ data. |
| Breadcrumbs | Outputs microdata and includes references to several absent taxonomies. | **REIMPLEMENT**: context-aware breadcrumbs using target objects and matching JSON-LD positions. |
| Search | Custom Blade and URL redirect to `/search/.../`. | **IMPROVE**: accessible search results, escaped terms, pagination, noindex policy decision, and canonical routing. |
| 404 | Redirects home with 303; separate 404 shape is therefore unreachable in normal flow. | **REPLACE**: true 404 status, helpful Arabic-friendly page based on `404.html`, search, and key links. |
| View counters | Increments post meta on every single render; category/trending counters are also mutated in routing. | **REIMPLEMENT**: bot-aware, cache-safe tracking; aggregate asynchronously or use a dedicated event model. |

Schema must never assert ratings, FAQs, prices, addresses, or service availability that are not visibly present and backed by published records.

## 12. Supporting packages

| Package/artifact | Finding | Classification / action |
|---|---|---|
| `FieldsMachine` | Large custom field framework with post/taxonomy/options hooks and many UI field renderers. | **MIGRATION ONLY for data interpretation**. Rebuild target fields with WordPress-native registration and explicit schemas. |
| `FieldsMachine/UI` and `@UIFields` | Bundled editors, date pickers, color pickers, taxonomy selectors, group fields, and admin scripts. | **DEPRECATED UI**. Keep a field-type mapping document; do not ship duplicate framework assets. |
| `FormsUI` | Generic old form rendering support. | **DEPRECATED implementation**; target is `mes_form` schema and controlled renderer. |
| `export-import` | Custom XML/data extraction and insertion, including remote media. | **MIGRATION ONLY**. Build a CLI/admin migrator with dry-run, capabilities, checksums, logs, and idempotency. |
| `YC-Scrape` | Publishing/scraping workflow. | **LEGACY / UNUSED** for the new runtime. Do not migrate. |
| `syntax.php::premium_file_get_contents()` | Contains a hardcoded Scrapstack credential and scraping proxy call. | **SECRET / UNUSED**. Do not copy the secret or helper; revoke/rotate the credential. |
| `currency_edits` | Huge currency list, external exchange-rate request, cookie override, and a hardcoded provider credential. | **IMPROVE or DEPRECATE by product decision**. Default to configured display currency; if conversion is required, use server-side secret management, allow-listed currencies, caching, failure behavior, and money-safe decimal arithmetic. |
| `Enqueues` | Loads bundled jQuery late, then globally dequeues/deregisters all scripts/styles and strips versions. | **DEPRECATED — HIGH RISK**. Enqueue only owned assets and optimize by measurement. |
| `webp_Converter` | Rewrites JPEG/PNG upload temp files into WebP using GD. | **IMPROVE**. Use WordPress-supported formats, preserve metadata/error paths, capability checks, and original/derivative policy. |
| `contentLazyLoad` / `modify_img` | String-level content image mutations. | **IMPROVE** using WordPress image APIs and native lazy loading. |
| `ViewsCounter` | Increments post meta before single render. | **REIMPLEMENT** with cache/bot/concurrency controls. |
| `schema` | Theme-owned JSON-LD. | **REIMPLEMENT** in core/plugin integration service. |
| `theme-seo` | Theme-owned title output. | **REIMPLEMENT** with core APIs and SEO-plugin coexistence. |
| `Breadcrumb` | Theme helper with stale taxonomy assumptions. | **REIMPLEMENT** against target routes. |
| `#PriceBoxes` | Price-plan partial driven by `price` posts/meta. | **REIMPLEMENT** as offer/pricing cards from the new model. |
| `#Works-box`, `#workstap` | Portfolio card/filter partials. | **REIMPLEMENT** against `mes_portfolio`. |
| `MenuField` | Adds nav-item custom fields and controls menu output. | **COMPATIBILITY ONLY**, then replace with a minimal documented field set if still needed. |
| `SvgCenter` / `ALordIcons` | Custom SVG/icon rendering and endpoint behavior. | **IMPROVE**. Use an audited local icon set; sanitize SVG and never accept arbitrary executable markup. |
| `TransientCenter`, formatting helpers, JSON helpers | Mixed utility behavior. | **REVIEW PER CALL SITE**; use core APIs and small namespaced utilities only when target behavior requires them. |

## 13. Explicit mysterious-file register

The following decisions are mandatory and remove ambiguity:

| File or symbol | Final classification | Reason |
|---|---|---|
| taxonomy `questions` attached to `bot` | **BROKEN** | `bot` is not registered. |
| `yc-froms` | **LEGACY / MIGRATION ONLY** | Unregistered form-builder storage identifier. |
| `@wp-models/edit-forms.php` | **LEGACY / MIGRATION ONLY** | Old form editor and serialized data writer. |
| `@wp-models/AllForms.php` | **LEGACY / MIGRATION ONLY** | Old form list/search UI. |
| `YC-Scrape/**` | **UNUSED + LEGACY** | Scraping is outside the target runtime. |
| Scrapstack access key in `syntax.php` | **SECRET** | Must not be copied; rotate/revoke. |
| contact widget `contact__form.php` | **ORPHAN** | Fields are declared but not rendered as a form. |
| Ajax `contact__form.php` | **ORPHAN** | Separate mail handler without a complete paired widget flow. |
| `@Mega-Menu/Taxonomy-posts.php` | **BROKEN** | Invalid/incomplete logic and placeholder output. |
| `LocalBusiness.php-tmp` | **UNUSED** | Temporary artifact; no target runtime role. If absent from an extraction, that does not change the classification. |
| `components/index.php` | **UNUSED** | Empty file. |
| `components/packs/Database/DB` | **UNUSED** | Loader points at `Database/DB/*.*`, but the extracted directory contains no implementation files. |

## 14. Prefix and namespace replacement

The following identifiers must not appear in new runtime code:

- `ThemeTree`
- `ThemeStatic`
- `YC__*`
- `YourColor__*`
- old globally declared helper names
- generic options, actions, filters, meta keys, transients, cookies, handles, and globals without an ownership prefix

Replacement policy:

- Plugin slug/text domain: `mahmoud-elsaad-core`
- Theme slug/text domain: `mahmoud-elsaad-theme`
- Procedural prefix: `mes_`
- PHP namespace where practical: `MahmoudElsaad\Core` and `MahmoudElsaad\Theme`
- REST namespace: `mes/v1`
- Options: `mes_*`
- Post/term/user meta: `_mes_*` for protected keys, `mes_*` where public registration is needed
- Database tables: `$wpdb->prefix . 'mes_*'`
- Cron hooks, transients, nonces, script/style handles, block names, and cookies: `mes_*`

Unprefixed WordPress core identifiers such as `post`, `page`, and `category` remain core-owned and must not be renamed.

## 15. Security and privacy disposition

Before any legacy dataset is used:

1. Revoke or rotate every credential found in legacy source, history, deployment bundles, or database options.
2. Do not write credential values into documentation, fixtures, logs, screenshots, commits, migration reports, or issue comments.
3. Treat old form submissions, emails, phone numbers, addresses, comments, IP-derived data, and leads as personal data.
4. Import only records covered by the retention policy and lawful business need.
5. Restrict migration commands and Control Center screens by capability.
6. Use nonces for browser-originated state changes, but do not confuse nonces with authorization.
7. Sanitize on input, validate against a schema, and escape for the output context.
8. Add anti-spam and throttling to public forms, reviews, comments, and click endpoints.
9. Never execute serialized or stored PHP, raw shortcode callbacks, icon markup, or legacy templates while importing.
10. Keep a redacted migration audit log with actor, timestamp, source ID, target ID, result, and error code.

## 16. Migration sequence

1. Snapshot and checksum the legacy database and uploads.
2. Inventory counts by post type, taxonomy, status, language, shortcode, form field type, and legacy option.
3. Build stable source-to-target ID maps.
4. Import countries and cities, then services, then service-city relationships.
5. Import portfolio, offers/pricing, reviews, FAQs, team, and partners.
6. Convert form definitions into inactive `mes_form` records; review before activation.
7. Convert content shortcodes and widget configurations into structured target fields/components.
8. Import only retained comments and leads under the privacy policy.
9. Rebuild menus as standard navigation; enable the mega-menu compatibility adapter only for unresolved legacy metadata.
10. Generate redirects for accepted old URLs and return 410/404 for intentionally removed content.
11. Recalculate aggregates, regenerate image sizes, flush rewrites once during controlled activation, and warm critical caches.
12. Run reconciliation reports and obtain editorial sign-off before disabling migration tooling.

## 17. Definition of classified

Legacy classification is complete for Phase 1 when:

- every feature family in this audit has a target decision;
- all explicitly mysterious files have one unambiguous classification;
- no credential value is present in new documentation or code;
- data-preservation decisions are separated from implementation-preservation decisions;
- broken, orphan, migration-only, compatibility-only, and unused items are not presented as active target features;
- the feature matrix and architecture documents use the same target names and boundaries;
- unresolved product choices are recorded as decisions to make, not silently guessed.

