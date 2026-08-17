# Schema

`MahmoudElsaad\Core\SEO\SchemaGraph` can emit a `@graph` with Organization/LocalBusiness, WebSite+SearchAction, Service, Article/BlogPosting, FAQPage, ContactPage, AboutPage, and Service × City (`areaServed`).

Duplicate nodes are dropped by type+id.

When Rank Math is active and `defer_to_rank_math` is on (default):

- MES does **not** print a second JSON-LD script.
- Missing LocalBusiness / Service / FAQPage / Service×City types are injected into `rank_math/json_ld`.

See `docs/seo.md` for ownership.
