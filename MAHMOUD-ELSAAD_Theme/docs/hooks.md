# Hooks

| Hook | Type | Purpose |
|---|---|---|
| `mes_core_booted` | action | Core plugin finished booting |
| `mes_lead_created` | action | After a lead is stored (`$lead_id`, `$data`) |
| `mes_contact_click` | action | After a call/WhatsApp click is stored |
| `mes_schema_should_emit` | filter | Whether to print internal JSON-LD |
| `mes_core_ready` | filter | Signals the core plugin is present |

All public functions and options use the `mes_` prefix.
