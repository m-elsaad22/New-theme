# Admin Control Center

Entry: `admin.php?page=mes-control-center` (capability `manage_options` / `mes_manage_platform`).

RTL admin shell with side nav, bottom nav on small screens (**PARTIAL** — no physical device test), and Ctrl/K command search (**IMPLEMENTED**, key-event **UNTESTED**).

| Screen | Page | What it does |
|---|---|---|
| Dashboard | `mes-control-center` | Health, counts, quick actions |
| Content | `mes-cc-content` and CPT subs | Services, cities, countries, offers, reviews, portfolio, team, partners |
| Forms | `mes-cc-forms` | Database-backed builder: CRUD, add/remove/reorder, drag-and-drop, actions |
| Leads | `mes-cc-leads` | Private `mes_lead` list |
| Service × City | `mes-cc-landings` | Pair overrides |
| Analytics | `mes-cc-analytics` | Clicks + CSV export |
| Design | `mes-cc-design` | Visual tree Global → Element, Desktop/Tablet/Mobile, **live homepage iframe** |
| SEO | `mes-cc-seo` | Defer Rank Math, emit schema |
| AI | `mes-cc-ai` | Provider + encrypted key (never echoed back) |
| Performance / Security / Settings | matching slugs | Toggles; brand/contact/map URL |
| Migration | `mes-cc-migration` | Detect → map → transform → validate |

REST under `mes/v1` requires `mes_manage_platform` except public search and click tracking.

Design preview is the real frontend (`?mes_preview=1`), not a mock canvas. It is **not** an Elementor-like page builder.
