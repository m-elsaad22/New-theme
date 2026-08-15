# MAHMOUD-ELSAAD Quality Gates

## 1. Purpose

Quality gates prevent the rebuild from treating incomplete analysis as implementation-ready work. A phase passes only when its required evidence exists, contradictions are resolved or explicitly recorded, and a named reviewer can reproduce the checks.

This document fully defines the **Phase 1 gate: documentation created and legacy classified**. Later gates are listed to preserve direction but require their own detailed test plans when implementation begins.

## 2. Gate rules

1. A checked box means evidence exists in the repository, not that work is intended.
2. “Not applicable” requires a written reason.
3. A known failure cannot be hidden by changing the test or narrowing the inventory after the fact.
4. Security failures involving credentials, authorization, unsafe dynamic includes, or personal-data exposure block release.
5. Documentation must distinguish:
   - old observed behavior;
   - target architecture;
   - migration-only behavior;
   - implementation that actually exists.
6. A phase may pass with product decisions outstanding only when those choices are listed and do not alter the safety of the completed classification.

## 3. Phase 1 — Documentation and legacy classification

### 3.1 Objective

Create a shared, evidence-based migration contract before PHP implementation. Phase 1 answers:

- What exists in the extracted theme?
- Which behavior is valid, broken, orphaned, unused, secret-bearing, or migration-only?
- Where does every important capability belong in the target platform?
- Which content and data must be preserved?
- Which legacy mechanisms must not be copied?
- What are the plugin/theme/data/routing boundaries?

### 3.2 Required artifacts

| Artifact | Required content | Gate evidence |
|---|---|---|
| `docs/MAHMOUD-ELSAAD-LEGACY-AUDIT.md` | Bootstrap, package loading, routing, CPTs, taxonomies, widgets, Ajax handlers, forms, shortcodes, mega menu, SEO/schema, utilities, security, secret policy, explicit mysterious-file classifications, migration sequence | File exists and contains final classifications rather than an outline |
| `docs/MAHMOUD-ELSAAD-FEATURE-MATRIX.md` | `OLD FEATURE`, `OLD LOCATION`, `NEW LOCATION`, `STATUS`, `NOTES`; all important feature families; only allowed statuses | File exists and maps each major old feature to a target or reasoned deprecation |
| `docs/architecture.md` | Core plugin/theme boundaries, naming, target CPTs, custom tables, translations, Control Center, REST/forms, HTML design system, security, migration, dependencies | File exists and is internally consistent with the feature matrix |
| `docs/quality-gates.md` | Reproducible Phase 1 entry, checks, blocker rules, and exit criteria | This file |
| `docs/design-reference.md` | HTML source-of-truth summary, tokens, typography, template/page map, motion and dark mode | Existing supporting evidence |

### 3.3 Entry conditions

- [x] Legacy theme is available for read-only inspection at `/tmp/theme-analysis/ServicesTheme(YourColor)/`.
- [x] Nineteen HTML reference files are available at `/tmp/theme-analysis/*.html`.
- [x] Initial inventory identifies bootstrap, packages, CPTs, taxonomies, widgets, Ajax handlers, shortcodes, prefix concerns, and suspicious files.
- [x] New target object list and package names are known.

Temporary paths are evidence inputs, not target repository dependencies. The committed documentation must remain understandable when `/tmp/theme-analysis` is no longer available.

### 3.4 Artifact completeness checks

The Phase 1 reviewer verifies:

- [x] All four requested Markdown files exist and contain substantive content.
- [x] The audit states that the 19 HTML files are the visual source of truth.
- [x] The audit explains ordinary, `@`, and `#` package loading behavior.
- [x] The audit classifies registered `faq`, `price`, and `works` post types.
- [x] The audit classifies `category`, `city`, broken `questions` → `bot`, and referenced-but-unregistered `services`.
- [x] The matrix includes every listed legacy widget.
- [x] The matrix includes all 12 Ajax handlers.
- [x] The matrix includes all six shortcodes.
- [x] The architecture includes every required target object:
  `service`, `mes_city`, `mes_country`, `mes_offer`, `mes_review`,
  `mes_portfolio`, `mes_team`, `mes_partner`, `mes_faq`, `mes_lead`, and `mes_form`.
- [x] The architecture includes the service-city relation table and click tracking table.
- [x] The architecture defines `translation_group_id` and `language_code`.
- [x] The architecture assigns persistent behavior to `mahmoud-elsaad-core` and presentation to `mahmoud-elsaad-theme`.
- [x] The architecture defines the Control Center and HTML design-system roles.
- [x] Search and a real HTTP 404 are mapped.
- [x] The old home redirect for 404s is explicitly rejected.

### 3.5 Mandatory legacy classifications

The gate is blocked unless all of these decisions appear without ambiguous “maybe preserve” wording:

| Legacy artifact | Required classification | Evidence |
|---|---|---|
| `questions` taxonomy attached to `bot` | **BROKEN** | `bot` is unregistered |
| `yc-froms` | **LEGACY / MIGRATION ONLY** | Old form-builder storage |
| `@wp-models/edit-forms.php` | **LEGACY / MIGRATION ONLY** | Old editor/data writer |
| `@wp-models/AllForms.php` | **LEGACY / MIGRATION ONLY** | Old list/search UI |
| `YC-Scrape` | **UNUSED + LEGACY** | No target runtime role |
| Scrapstack credential | **SECRET** | Value must not be copied |
| Contact form widget | **ORPHAN** | Fields are defined but not rendered |
| Ajax contact handler | **ORPHAN** | No complete paired form flow |
| `@Mega-Menu/Taxonomy-posts.php` | **BROKEN** | Invalid/incomplete code |
| Mega menu generally | **LEGACY / COMPATIBILITY ONLY** | Not default HTML navigation |
| `LocalBusiness.php-tmp` | **UNUSED** | Temporary artifact |
| `components/index.php` | **UNUSED** | Empty |
| `Database/DB` | **UNUSED** | No extracted implementation files |

Additional discovered credential-bearing integrations follow the same **SECRET — do not copy** policy even when not named in the original inventory.

### 3.6 Feature-matrix checks

#### Schema

- [x] Column order is exactly:
  `OLD FEATURE | OLD LOCATION | NEW LOCATION | STATUS | NOTES`.
- [x] Statuses use only:
  `Preserved`, `Improved`, `Reimplemented`, or `Deprecated — reason`.
- [x] A deprecated row states a reason in the status or notes.
- [x] New locations name target modules/files rather than old runtime packages.

#### Coverage

The matrix includes:

- [x] bootstrap and routing;
- [x] header and footer;
- [x] navigation and mega menu;
- [x] all listed widgets;
- [x] registered and missing content objects/taxonomies;
- [x] forms and leads;
- [x] comments and ratings;
- [x] schema and SEO;
- [x] all shortcodes;
- [x] the custom Ajax dispatcher and handlers;
- [x] enqueue stripping and asset version behavior;
- [x] WebP/media handling;
- [x] views and click tracking;
- [x] currency;
- [x] export/import;
- [x] Fields Machine and UI fields;
- [x] breadcrumbs;
- [x] price boxes and works;
- [x] fonts, tokens, responsiveness, and motion;
- [x] popovers;
- [x] search;
- [x] real 404 behavior.

### 3.7 Security and secret checks

Phase 1 fails if any of the following is true:

- a provider access key from the legacy source is copied into the repository;
- documentation prints an actual legacy credential value;
- target architecture endorses URL-controlled PHP includes;
- target architecture treats a nonce as an authorization check;
- public mutation endpoints lack validation/throttling requirements;
- leads are modeled as public searchable content;
- legacy scraping is included in the new runtime;
- the old global script/style stripping is recommended for reuse;
- personal data is proposed for click event payloads without an approved requirement.

Required documentation evidence:

- [x] credential values are omitted;
- [x] rotation/revocation is required;
- [x] integration secrets use deployment-backed configuration;
- [x] old Ajax dispatch is deprecated;
- [x] target APIs have explicit methods, permissions, validation, and response contracts;
- [x] form submissions create private leads before notification;
- [x] migration logs are redacted.

### 3.8 Cross-document consistency checks

The reviewer compares the audit, matrix, architecture, and design reference:

- [x] The target plugin prefix is `mes_`.
- [x] The core plugin is `mahmoud-elsaad-core`.
- [x] The theme is `mahmoud-elsaad-theme`.
- [x] Mega menu is compatibility-only and disabled by default.
- [x] Form-builder artifacts are migration-only.
- [x] Scraping code is excluded.
- [x] The default frontend comes from the HTML designs.
- [x] Brand strings and phone numbers in HTML are treated as demo content.
- [x] Cities are first-class `mes_city` posts.
- [x] Service-city is a many-to-many relation with a custom route.
- [x] Click tracking has a dedicated table and privacy boundary.
- [x] A missing/inactive service-city pair returns 404.
- [x] The theme does not own business data or table migrations.

One normalization is intentional: the registered target CPT key is `service`, so its WordPress hierarchy files are `archive-service.php` and `single-service.php`. Older planning references using a different template suffix must be corrected when templates are created.

### 3.9 Reproducible repository checks

Run from the Git repository root:

```bash
test -s MAHMOUD-ELSAAD_Theme/docs/MAHMOUD-ELSAAD-LEGACY-AUDIT.md
test -s MAHMOUD-ELSAAD_Theme/docs/MAHMOUD-ELSAAD-FEATURE-MATRIX.md
test -s MAHMOUD-ELSAAD_Theme/docs/architecture.md
test -s MAHMOUD-ELSAAD_Theme/docs/quality-gates.md
```

Confirm the matrix header and core target terms:

```bash
rg -n 'OLD FEATURE.*OLD LOCATION.*NEW LOCATION.*STATUS.*NOTES' \
  MAHMOUD-ELSAAD_Theme/docs/MAHMOUD-ELSAAD-FEATURE-MATRIX.md

rg -n 'mahmoud-elsaad-core|mahmoud-elsaad-theme|translation_group_id|language_code|mes_service_city|mes_click_events' \
  MAHMOUD-ELSAAD_Theme/docs/architecture.md
```

Confirm mandatory classifications:

```bash
rg -n 'BROKEN|ORPHAN|MIGRATION ONLY|UNUSED|SECRET|COMPATIBILITY ONLY' \
  MAHMOUD-ELSAAD_Theme/docs/MAHMOUD-ELSAAD-LEGACY-AUDIT.md
```

Secret scanning must use the repository's approved scanner when configured. A plain text search is only a supplementary check and must not print a matching secret into CI logs.

### 3.10 Blockers

Phase 1 is **blocked** by:

- a missing or outline-only required file;
- an unclassified mandatory artifact;
- feature matrix omissions in the required coverage list;
- contradictory plugin/theme ownership;
- a credential value in committed content;
- a new-location mapping that points back to the old runtime;
- presenting broken/orphan behavior as preserved functionality;
- treating the legacy PHP/CSS as the visual source of truth;
- proposing homepage redirection as 404 handling.

Phase 1 is **not blocked** by:

- target PHP files not existing yet;
- unresolved public slug wording;
- an undecided currency provider;
- an undecided public review-submission policy;
- final retention periods awaiting product/legal input;
- the temporary extraction no longer being present after documentation is committed.

Those items are implementation/product inputs and are explicitly listed in `architecture.md`.

### 3.11 Exit criteria

Phase 1 passes when:

1. All required files are non-empty and committed.
2. Mandatory artifacts have explicit classifications.
3. The matrix covers all required feature families and uses the allowed status vocabulary.
4. Architecture boundaries and target object names agree across documents.
5. No secret value has been copied.
6. The default design source is the 19 HTML files.
7. Review confirms that migration preserves data without preserving unsafe runtime patterns.
8. The branch containing the documentation is pushed for parent/reviewer consumption.

### 3.12 Gate result

**Result: PASS — documentation scope.**

Evidence:

- the four required Markdown documents are present and substantive;
- the legacy inventory is classified, including all mandatory mysterious artifacts;
- the feature matrix maps old capabilities to target ownership or reasoned deprecation;
- the target architecture defines plugin/theme boundaries, target content objects, custom tables, translations, Control Center, and HTML design-system mapping;
- credentials are discussed only as redacted risks and are prohibited from migration.

This pass authorizes implementation planning. It does not assert that the plugin, theme, migrations, routes, templates, security controls, or runtime tests have been implemented.

## 4. Later phase gates

The following gates are directional. They must be expanded with executable acceptance tests when their phases begin.

### Phase 2 — Skeleton and data contracts

- plugin and theme activate without warnings/fatals;
- all target post types/meta/taxonomies register deterministically;
- custom tables install and upgrade idempotently;
- capabilities and Control Center shell exist;
- service-city route resolves valid/invalid pairs correctly;
- automated coding, static-analysis, and unit-test baselines pass.

### Phase 3 — Migration rehearsal

- dry-run and bounded resumable import work on a production-like snapshot;
- source-target ID maps and checksums make reruns idempotent;
- form and shortcode conversions produce review reports;
- counts reconcile or every delta has an accepted reason;
- credentials and out-of-retention personal data are excluded;
- rollback/restore procedure is rehearsed.

### Phase 4 — Frontend parity with HTML source

- all 19 page types are implemented;
- visual regression at agreed widths passes;
- RTL, keyboard, focus, reduced motion, contrast, forms, and error states pass;
- no demo brand/contact value leaks into runtime;
- responsive images, fonts, and critical assets meet performance budgets;
- real 404/search/canonical behavior passes.

### Phase 5 — Integrations and hardening

- notifications, anti-spam, SEO coexistence, multilingual behavior, and optional currency integration pass failure-mode tests;
- REST authorization, rate limiting, validation, and privacy tests pass;
- dependency and secret scans pass;
- click/lead retention and deletion jobs are verified;
- backup, observability, and incident diagnostics are documented.

### Phase 6 — Cutover

- final delta migration reconciles;
- redirects and sitemap/canonical outputs are verified;
- legacy compatibility is disabled by default;
- monitoring shows no critical PHP, HTTP, form, notification, or rewrite failures;
- rollback decision points are explicit;
- removal dates are assigned to remaining compatibility adapters.

## Implementation gate (this package)

- PHP syntax: pass (`php -l` on 80 files, 0 errors).
- Runtime YourColor branding in `theme/` and `plugins/`: 0 hits.
- Packages present: `plugins/mahmoud-elsaad-core`, `theme/mahmoud-elsaad-theme`.
- WordPress activation smoke test: not runnable here (no WP runtime). Activate on WordPress 6.6+ / PHP 8.2+.


