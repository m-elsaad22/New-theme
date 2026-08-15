# MAHMOUD-ELSAAD Feature Matrix

## Reading this matrix

This matrix maps legacy outcomes to the intended MAHMOUD-ELSAAD platform. “New location” names the owning component or intended file/module; it does not claim that implementation already exists.

Allowed status meanings:

- **Preserved** — behavior and data semantics remain substantially the same.
- **Improved** — the capability remains, with stronger security, accessibility, performance, or editorial behavior.
- **Reimplemented** — the user outcome remains but the implementation and usually the presentation are replaced.
- **Deprecated — reason** — deliberately excluded from the target runtime, with the reason stated.

The core plugin owns persistent content, relationships, submissions, tracking, migration, and APIs. The theme owns templates, components, assets, and presentation. The HTML designs are the visual source of truth.

| OLD FEATURE | OLD LOCATION | NEW LOCATION | STATUS | NOTES |
|---|---|---|---|---|
| Theme bootstrap and package scan | `functions.php` (`ThemeTree`) | `mahmoud-elsaad-core/mahmoud-elsaad-core.php`; `mahmoud-elsaad-theme/functions.php` | Reimplemented | Replace directory globbing and implicit `setup.php` loading with explicit, deterministic bootstraps. |
| Custom frontend router | `syntax.php` (`ThemeStatic::Locate`) | WordPress template hierarchy in `mahmoud-elsaad-theme`; narrow rewrites in `mahmoud-elsaad-core/src/Routing` | Reimplemented | Stop terminating normal requests from `template_redirect`; keep only explicit service-city and compatibility rewrites. |
| `@` “Blade” template lookup | `syntax.php` (`Blade`) and `components/packs/@*` | Standard theme templates and `template-parts/` | Deprecated — custom loader adds no required capability | It is a custom include convention, not the Blade engine. |
| `#` part lookup | `syntax.php` (`Part`) and `components/packs/#*` | `mahmoud-elsaad-theme/template-parts/` | Reimplemented | Use `get_template_part()`, typed view data, and output-context escaping. |
| Homepage | `@index`/legacy widgets | `mahmoud-elsaad-theme/front-page.php` | Reimplemented | Match `home.html`; content remains settings/query driven. |
| Generic pages | `@page` and page fields | `mahmoud-elsaad-theme/page.php`; `page-templates/` | Improved | Use dedicated About, Contact, Booking, and legal layouts where the HTML set defines them. |
| Header | `components/packs/#header/part.php` | `mahmoud-elsaad-theme/header.php`; `template-parts/site/header.php` | Reimplemented | Implement glass-on-scroll header, RTL navigation, mobile panel, semantic landmarks, and configurable branding. |
| Footer | `components/packs/#footer/part.php` | `mahmoud-elsaad-theme/footer.php`; `template-parts/site/footer.php` | Reimplemented | Implement four-column footer, map, contact/social settings, copyright, and floating actions from HTML. |
| Standard navigation | `nav-menus/setup.php`; header/footer parts | Theme menu locations and `template-parts/navigation/` | Improved | Preserve editorial menu control; add keyboard operation, focus management, current-item state, and escaped attributes. |
| Custom menu-item fields | `MenuField/setup.php` | `mahmoud-elsaad-core/src/LegacyMigration/MenuMetaAdapter.php` initially; minimal theme integration if approved | Improved | Translate only documented fields. Remove fields that merely compensate for old markup. |
| Mega menu | `@Mega-Menu/taxonomy.php`, `@Mega-Menu/post.php` | `mahmoud-elsaad-core/src/LegacyMigration/MegaMenuAdapter.php` | Deprecated — not part of the default HTML frontend | A temporary compatibility adapter may render safe simplified submenus until menus are reviewed. |
| Taxonomy-post mega-menu experiment | `@Mega-Menu/Taxonomy-posts.php` | Migration diagnostics only | Deprecated — broken and incomplete | Invalid call signature, undefined values, placeholder output, duplicate branches, and unfinished logic. |
| Hero slider widget | `YourColorWidgets/model-selector/intro-models/slider_intro_v1.php` | `mahmoud-elsaad-theme/template-parts/home/hero.php` | Reimplemented | Preserve approved slide copy/media; use the HTML hero and avoid carousel dependence when one message is sufficient. |
| After-intro widget | `YourColorWidgets/model-widgets/Standard/after__intro.php` | `mahmoud-elsaad-theme/template-parts/home/service-summary.php` | Reimplemented | Map useful copy/icons/links to the new homepage section. |
| Sticky features widget | `YourColorWidgets/model-widgets/Standard/sticky__features.php` | `mahmoud-elsaad-theme/template-parts/components/feature-list.php` | Improved | Responsive and keyboard-safe sticky behavior; disable sticky treatment where viewport or reduced-motion needs require it. |
| Benefits widget | `YourColorWidgets/model-widgets/Standard/benefits.php` | `mahmoud-elsaad-theme/template-parts/components/benefit-grid.php` | Reimplemented | Preserve content semantics, not legacy markup or CSS. |
| FAQ widget | `YourColorWidgets/model-widgets/Standard/Faqs__simple2.php` | `mahmoud-elsaad-theme/template-parts/components/faq-accordion.php`; `mes_faq` query service | Improved | Accessible accordion; schema emitted only for visible published FAQ content. |
| Contact form widget | `YourColorWidgets/model-widgets/Standard/contact__form.php` | Contact template + `mes_form` renderer | Deprecated — old widget is orphaned | Old field definitions are not rendered. Build a complete form against `mes_form` and `mes_lead`. |
| Category widget | `YourColorWidgets/model-widgets/Standard/category.php` | `template-parts/components/service-category-grid.php` or blog category component | Reimplemented | Select the target object explicitly instead of overloading one generic category widget. |
| Blog listing widget | `YourColorWidgets/model-widgets/Standard/blog_v1.php` | `mahmoud-elsaad-theme/home.php`; `template-parts/cards/article.php` | Improved | Normal queries, canonical pagination, semantic cards, image dimensions, and empty state. |
| City widget | `YourColorWidgets/model-widgets/Standard/city__widget.php` | `archive-mes_city.php`; `template-parts/cards/city.php` | Reimplemented | Cities become first-class posts and can show service availability from the relation table. |
| Price widget | `YourColorWidgets/model-widgets/Standard/price.php` | Offer/service pricing component; `template-parts/cards/price.php` | Reimplemented | Use explicit currency, price type, validity, and CTA fields. |
| Works widget | `YourColorWidgets/model-widgets/Standard/works.php` (`works_v1`) | `archive-mes_portfolio.php`; `template-parts/cards/portfolio.php` | Reimplemented | Map old work entries to `mes_portfolio`. |
| Single-blog widget | `YourColorWidgets/model-widgets/SingleBar/single__blog.php` | `single.php`; `template-parts/article/` | Reimplemented | Match `article-single.html`, including author/meta/share/related sections where configured. |
| Video posts widget | `YourColorWidgets/model-widgets/SingleBar/posts__video.php` | `template-parts/components/responsive-media.php` | Improved | Use approved oEmbed/media URLs, responsive containers, lazy loading, and privacy-conscious provider behavior. |
| Rating widget | `YourColorWidgets/model-widgets/SingleBar/rating__widget.php` | `mes_review` service; `template-parts/components/rating-summary.php` | Reimplemented | Aggregate only published reviews; submission is moderated and abuse-protected. |
| Page URL widget | `YourColorWidgets/model-widgets/SingleBar/page_url.php` | Reusable link field/component | Improved | Validate URL schemes and use `esc_url`; remove global widget dependencies. |
| FAQ post type | `post-types/setup.php` (`faq`) | `mahmoud-elsaad-core/src/Content/FAQ.php` (`mes_faq`) | Improved | Preserve question/answer data; add status, ordering, language linkage, and schema eligibility. |
| Price post type | `post-types/setup.php` (`price`) | `mes_offer` and/or structured service pricing | Reimplemented | Resolve each old record through migration mapping; avoid a duplicate price CPT unless real data requires it. |
| Works post type | `post-types/setup.php` (`works`) | `mahmoud-elsaad-core/src/Content/Portfolio.php` (`mes_portfolio`) | Improved | Preserve title/content/image and map service/category/client/result fields explicitly. |
| Service content | Implicit post/service usage across templates and forms | `mahmoud-elsaad-core/src/Content/Service.php` (`service`) | Reimplemented | First-class public service object with archive, single, categories, language, SEO fields, and city relationships. |
| City content | Legacy `city` terms attached to posts | `mahmoud-elsaad-core/src/Content/City.php` (`mes_city`) | Improved | Convert terms to posts for rich city pages, media, country relation, translations, and metadata. |
| Country content | No dedicated old object | `mahmoud-elsaad-core/src/Content/Country.php` (`mes_country`) | Reimplemented | New first-class country model supporting city grouping and localization. |
| Offers | Legacy `price` records/price widgets | `mahmoud-elsaad-core/src/Content/Offer.php` (`mes_offer`) | Improved | Add validity windows, status, service relation, pricing, CTA, and language support. |
| Reviews | Comment rating meta and rating Ajax | `mahmoud-elsaad-core/src/Content/Review.php` (`mes_review`) | Improved | Separate editorial/moderated reviews from blog comments and calculate aggregates server-side. |
| Team | Theme options/widgets if present | `mahmoud-elsaad-core/src/Content/Team.php` (`mes_team`) | Reimplemented | Structured member name, role, portrait, biography, order, and links. |
| Partners | Theme options/widgets if present | `mahmoud-elsaad-core/src/Content/Partner.php` (`mes_partner`) | Reimplemented | Structured partner logo, accessible name, URL, order, and publication state. |
| Leads | Email-only contact/service submissions | `mahmoud-elsaad-core/src/Content/Lead.php` (`mes_lead`) | Improved | Store a minimal, access-controlled system-of-record entry before notifications; enforce retention and consent policy. |
| Form definitions | `yc-froms`, `@wp-models/edit-forms.php`, `AllForms.php` | `mahmoud-elsaad-core/src/Content/Form.php` (`mes_form`) | Reimplemented | Import supported serialized definitions into a versioned schema, inactive until reviewed. |
| `questions` taxonomy | `taxonomies/setup.php` attached to `bot` | `mes_faq` migration mapping | Deprecated — attached post type is unregistered | Recover valid content only; do not register `bot` to make broken code appear functional. |
| Core category attached to works | `taxonomies/setup.php` | Core category for posts; optional dedicated portfolio taxonomy | Improved | Avoid globally re-registering core `category` with mixed object semantics. |
| City taxonomy | `taxonomies/setup.php` (`city` on posts) | `mes_city` CPT + migration ID map | Reimplemented | Preserve term names/slugs/descriptions/meta while assigning stable target IDs. |
| Referenced `services` taxonomy | Forms, breadcrumbs, and single templates | `service` CPT + optional service category taxonomy | Deprecated — taxonomy is unregistered in extracted theme | Recover terms and references in migration; report unresolved IDs. |
| Service-by-city availability | Implicit city/service references | `$wpdb->prefix . 'mes_service_city'`; repository under `src/Relations` | Improved | Normalized many-to-many table with unique pair, status/order metadata as needed, indexes, and route integration. |
| Service-city landing route | Not consistently modeled | Core rewrite/query vars + `templates/service-city.php` | Reimplemented | Resolve service and city canonically, emit a true 404 for invalid pairs, and avoid duplicate indexable combinations. |
| Multilingual linkage | Ad hoc or absent | `translation_group_id` + `language_code` registered fields and repository | Reimplemented | Validate language codes, group only equivalent content, and generate alternates/canonicals consistently. |
| Contact submission Ajax | `AjaxCenter/contact__form.php` | `mes/v1/forms/{form}/submissions` | Deprecated — old handler is orphaned and insufficiently protected | New route validates schema, consent, honeypot/throttle, creates a lead, and queues notification. |
| Service-request form | `@Popovers/form_services.php`; `AjaxCenter/forms__services.php` | Booking/contact templates + `mes_form`/`mes_lead` application service | Improved | Preserve multi-step outcome where useful; enforce server-owned step and field definitions. |
| Form-builder list and editor | `@wp-models/AllForms.php`, `edit-forms.php` | Control Center Forms screens | Reimplemented | WordPress capabilities, nonces, schema versioning, revisions/audit information, and preview. |
| Comments | `AjaxCenter/AddComment.php`, `CommentContent.php` | WordPress comments on blog posts; explicit REST/Ajax adapter only if needed | Improved | Use native moderation, nonce, sanitization, approved-comment visibility, spam controls, and safe aggregate logic. |
| Ratings submitted with comments | `AddComment.php`, `RateAjax.php` | `mes_review` submission/moderation service | Reimplemented | Validate rating range, prevent replay/duplicates, and never trust client aggregates. |
| Custom Ajax endpoint | `AjaxCenter/setup.php` and handler files | Explicit `mes/v1` REST controllers; admin Ajax only when WordPress UI requires it | Deprecated — dynamic file dispatch is unsafe | No URL-derived PHP includes. Every route declares methods, permissions, validation, and response schema. |
| Tabs Ajax | `AjaxCenter/TabsActions.php` | Accessible tabs component; optional read-only REST endpoint | Improved | Prefer already-rendered semantic content when payload size is reasonable. |
| Popover Ajax | `AjaxCenter/PopoverActions.php` | Dialog/form-step controller in theme plus narrow REST endpoint | Improved | Correct focus trap, Escape behavior, labels, and server-side authorization/validation. |
| Read-more Ajax | `AjaxCenter/ReadMoreObject.php` | Paginated query endpoint or semantic expandable component | Improved | Canonical content remains crawlable; endpoint output is escaped and cacheable. |
| Generic object loader | `AjaxCenter/More-Ajax-objects.php` | Resource-specific controllers | Deprecated — generic action multiplexing obscures permissions | Define one bounded contract per resource. |
| Field load-more | `AjaxCenter/fields-loadmore.php` | Control Center list endpoints / standard admin pagination | Deprecated — tied to legacy field/form runtime | Migration can read old data directly without exposing this public endpoint. |
| Menu initialization Ajax | `AjaxCenter/MenusInitialize.php` | Standard WordPress menu render/cache | Reimplemented | Server-render default navigation; no public generic menu action. |
| Schema graph | `schema/setup.php`, `SchemaItems/*` | `mahmoud-elsaad-core/src/SEO/SchemaGraph.php` | Improved | Emit validated JSON-LD once, with route-specific entities and no claims unsupported by visible content. |
| LocalBusiness schema temporary file | `SchemaItems/LocalBusiness.php-tmp` | None | Deprecated — unused temporary artifact | Do not package or load it. |
| SEO titles | `theme-seo/setup.php` | Core `title-tag` integration; optional SEO-plugin adapter | Improved | Prevent duplicate titles/canonicals and define ownership when an SEO plugin is active. |
| Meta descriptions and canonical URLs | Theme options/templates | `mahmoud-elsaad-core/src/SEO`; WordPress/SEO-plugin filters | Improved | Per-object override with deterministic fallback; canonicalize service-city and language routes. |
| Breadcrumbs | `Breadcrumb/setup.php` | `template-parts/navigation/breadcrumbs.php`; schema graph service | Improved | Use target content graph, visible labels, correct positions, and matching JSON-LD. |
| Search results | `@search/shape.php`; custom `/search/` redirect | `mahmoud-elsaad-theme/search.php` | Improved | Accessible query form, escaped query, result types, pagination, empty state, and canonical URL policy. |
| 404 handling | `syntax.php` and `@404/shape.php` | `mahmoud-elsaad-theme/404.php` | Deprecated — redirecting home returns the wrong response | New page follows `404.html`, sends HTTP 404, and offers search and useful links. |
| Price boxes | `#PriceBoxes/part.php` | `template-parts/cards/price.php` and offer/service pricing query | Reimplemented | Explicit monetary value, currency, qualifiers, features, and CTA; no silent fallback to home. |
| Works cards and filters | `#Works-box/part.php`, `#workstap/part.php` | Portfolio archive/card/filter components | Improved | Filter URLs are canonical and progressively enhanced; invalid filters do not silently point elsewhere. |
| Content shortcodes | `shortcodes/codes/*.php` | Structured blocks/fields; temporary compatibility renderers under migration module | Reimplemented | Convert `[post_prices]`, `[post_steps]`, `[post_features]`, `[post_gallery]`, `[post_call]`, and `[post_services]`; report remaining instances. |
| Field machine | `FieldsMachine/**` | Registered meta schemas, REST exposure where needed, and Control Center field components | Deprecated — custom framework is too broad and tightly coupled | Preserve field data through mapping, not the framework runtime or bundled UI libraries. |
| UI field types | `FieldsMachine/FieldsContext/**`, `@UIFields/**` | Control Center components for approved text, media, select, relation, repeater, date, and rich-text types | Improved | Allow-list field types; sanitize SVG/HTML; do not support executable code fields. |
| Theme options | `FieldsMachine/ThemeOptions.php`, `SetupFields/ThemeOptions/**` | Control Center Settings with `mes_*` options | Improved | Group Brand, Contact, Social, SEO, Integrations, Appearance, Forms, and Migration; validate every setting. |
| Control Center admin | Scattered custom admin pages | `mahmoud-elsaad-core/src/Admin/ControlCenter` | Reimplemented | One capability-aware entry point with focused screens, notices, health checks, and no frontend coupling. |
| Export/import | `export-import/**` | `mahmoud-elsaad-core/src/LegacyMigration`; WP-CLI and guarded admin runner | Improved | Dry-run, idempotency, checksums, source-target maps, redacted logs, attachment policy, and reconciliation. |
| Scraping workflow | `YC-Scrape/**`; scraping helper in `syntax.php` | None | Deprecated — outside target runtime and contains secret-bearing integration | Do not copy code or credentials. Revoke/rotate legacy credential separately. |
| Currency conversion | `currency_edits/setup.php` | Settings/formatting service; optional provider adapter under `src/Integrations/Currency` | Improved | Prefer one configured display currency. If conversion is approved, use environment secrets, decimal-safe math, allow-list, caching, and stale-data behavior. |
| Number/date formatting | `Numberformat`, `DisplayDate`, `DatedFormate` | `src/Support/Formatting.php` plus WordPress locale/date APIs | Improved | Arabic-friendly localized output without redefining broad global helpers. |
| View counting | `ViewsCounter/setup.php`; mutations in `syntax.php` | Analytics/click repository and aggregate service | Improved | Avoid write-on-every-render post meta, exclude bots where possible, and remain cache-safe. |
| Click tracking | Scattered link/Ajax behavior | `$wpdb->prefix . 'mes_click_events'`; `src/Tracking/ClickTracker.php` | Reimplemented | Append minimal event data for approved CTA types; retention, indexes, aggregation, and privacy are explicit. |
| Global enqueue stripping | `Enqueues/setup.php` (`disable_all_scripts`) | Theme asset manifest/enqueues; optional route-aware dequeue allow-list | Deprecated — breaks plugins and WordPress behavior | Never iterate through and remove every queued asset. Optimize only owned or explicitly reviewed handles. |
| Asset version removal | `Enqueues/setup.php` filters | Build-generated asset versions | Deprecated — harms cache invalidation | Use content hashes or theme/plugin versions. |
| Bundled old jQuery | `#footer/js/jquery-3.4.1.min.js` | No default dependency; WordPress-registered jQuery only if a component genuinely needs it | Deprecated — duplicate outdated runtime | New interactions should use small modern JavaScript modules. |
| WebP upload conversion | `webp_Converter/setup.php` | Media policy/service using WordPress image editor APIs | Improved | Validate support, preserve correct metadata, define original/derivative policy, and fail without corrupting uploads. |
| Image sizes/thumbnails | `thumbnails/setup.php` | Theme image-size registration and responsive image components | Improved | Derive sizes from the HTML layouts, declare width/height, use `srcset`, and regenerate after migration. |
| Content image lazy loading | `contentLazyLoad`, `modify_img` | Native WordPress loading attributes and responsive image APIs | Improved | Do not mutate arbitrary HTML with broad string replacement. |
| Fonts | Legacy style/font includes | Theme font tokens and enqueue layer | Improved | Cairo/Tajawal stack from design reference; preconnect only when remote fonts are used and support future self-hosting. |
| Design tokens | Scattered old CSS variables/styles | `mahmoud-elsaad-theme/assets/css/tokens.css` | Reimplemented | Use documented navy/blue/turquoise/gold palette, radii, shadows, spacing, typography, and dark tokens. |
| Responsive frontend | Legacy responsive CSS | Theme component CSS | Improved | RTL-first behavior across 320–1920px, with primary breakpoints 640/768/1024px. |
| Motion and effects | Legacy animation classes/footer scripts | `assets/js/motion.js`; component CSS | Improved | Scroll reveal, counters, accordion, filters, before/after, carousel, particles, and ripple only where designed; honor reduced motion. |
| Popover/form UI | `@Popovers/**` | Accessible dialog component and form-flow templates | Improved | Semantic dialog behavior, field errors, loading state, retry state, and no injected executable markup. |
| Empty component index | `components/index.php` | None | Deprecated — empty file has no function | No migration action. |
| Empty Database/DB loader | `Database/setup.php`, missing/empty `Database/DB` | Core repositories and `dbDelta` migrations | Deprecated — legacy loader has no implementation to preserve | New tables are versioned, indexed, and created by plugin activation/upgrade routines. |
| Brand/contact demo values | Hardcoded/option-driven legacy output and HTML demo copy | Control Center Brand and Contact settings | Improved | HTML brand strings and demo phone numbers are placeholders; runtime output must come from settings. |

## Cross-cutting acceptance conditions

Every row marked Preserved, Improved, or Reimplemented is subject to:

1. `mes_` ownership for runtime identifiers, except WordPress core identifiers and the explicitly approved `service` CPT.
2. No legacy credential or secret value in source, fixtures, documentation, logs, or migration exports.
3. Arabic and RTL output, English-safe administration where needed, and consistent language metadata.
4. Capability checks for privileged operations; nonces for browser state changes; validation before storage; escaping at output.
5. Real HTTP semantics: successful responses, validation errors, authorization errors, redirects, 404s, and server failures must not all collapse into HTML/200 responses.
6. Accessibility for keyboard, focus, labels, landmarks, reduced motion, contrast, dialog, tabs, accordion, and validation feedback.
7. Migration reconciliation with source count, imported count, skipped count, unresolved references, and redacted error logs.
8. Theme independence for business data: disabling the theme must not unregister business content or destroy forms, leads, relationships, tracking, or migration records.

