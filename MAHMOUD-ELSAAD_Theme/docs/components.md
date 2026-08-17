# Components

Homepage sections live in `theme/mahmoud-elsaad-theme/template-parts/home/` and are toggled from `mes_homepage_sections`.

Cards: `template-parts/cards/` (`service.php`, `article.php`, and related).

Contact buttons must go through `mes_render_phone_button()` / `mes_render_whatsapp_button()`.

The homepage map is `mes_render_map()` — a sandboxed iframe from an allowlisted admin URL, not raw HTML.

Visual inheritance attributes (`data-mes-node`) are emitted on designed nodes so Control Center Design can target Global → Page → Section → Component → Element. That is presentation markup, not a freeform builder.

Finder selects (`#fnSvc`, `#fnCity`) show a 3px navy focus ring on `.sel:has(select:focus)`.
