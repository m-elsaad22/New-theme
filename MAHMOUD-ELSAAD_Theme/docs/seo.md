# SEO and Rank Math

Rank Math is an integration, not a replacement.

When Rank Math is active and `defer_to_rank_math` is on (default):

- MES does **not** print a second `<script type="application/ld+json">` tag.
- MES injects missing types into Rank Math’s `rank_math/json_ld` graph.

## Who owns each type

| Type / signal | Owner when Rank Math is active | MES emits standalone when Rank Math is absent |
|---|---|---|
| Article / BlogPosting | Rank Math | Yes |
| BreadcrumbList | Rank Math | No (Rank Math / theme HTML crumbs) |
| WebSite / Organization | Rank Math | Yes |
| canonical / title / robots / description | Rank Math | MES `Meta` fallback |
| LocalBusiness (`/#localbusiness`) | **MAHMOUD Core**, only if Rank Math graph has no LocalBusiness | Yes (separate node from Organization) |
| Service | **MAHMOUD Core**, only if Rank Math graph has no Service | Yes on `service` CPT |
| Service × City | **MAHMOUD Core** Service + `areaServed`, only if no Service type already | Yes |
| FAQPage | **MAHMOUD Core**, only if Rank Math graph has no FAQPage | Yes on FAQ archive/singular |
| ContactPage / AboutPage | **MAHMOUD Core** if absent | Yes |

Do **not** force Rank Math to generate types it does not natively output. Do **not** duplicate JSON-LD.

Turning `defer_to_rank_math` off prints the full MES graph as a second script (intentional overlap; not the production default).
