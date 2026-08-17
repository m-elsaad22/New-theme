# Hooks

| Hook | Type | Purpose |
|---|---|---|
| `mes_core_booted` | action | Core plugin finished booting |
| `mes_lead_created` | action | After a lead is stored (`$lead_id`, `$data`) |
| `mes_contact_click` | action | After a call/WhatsApp click is stored |
| `mes_schema_should_emit` | filter | Whether to print the standalone MES JSON-LD script |
| `mes_form_upload_overrides` | filter | `wp_handle_upload` overrides for form files |
| `mes_webhook_allow_loopback` | filter | Allow loopback webhook URLs (debug) |
| `mes_webhook_url_allowed` | filter | Final webhook URL allow decision |
| `mes_core_ready` | function | Theme helper; true when Core is loaded |

All public functions and options use the `mes_` prefix.
