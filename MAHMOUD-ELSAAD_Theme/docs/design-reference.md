# Design Reference — MAHMOUD-ELSAAD Theme

Source of truth: the 19 HTML files in the uploaded archive. All pages share one CSS hash (`700ebd69`). PHP/CSS from `ServicesTheme(YourColor)` is **not** a visual reference.

Brand strings such as «ركن التطور» and the demo phone `+971 58 663 4710` are **demo content only**. Runtime values come from Brand / Contact settings.

## Tokens

| Token | Default | Role |
|---|---|---|
| `--mes-navy` / `--navy` | `#0A1F4E` | Primary dark / headings |
| `--mes-navy-2` / `--navy2` | `#1A3A6B` | Gradient stop |
| `--mes-blue` / `--blue` | `#2980D4` | Primary blue |
| `--mes-turq` / `--turq` | `#2E9DF7` | Interactive accent |
| `--mes-aqua` / `--aqua` | `#4FA8FF` | Highlight |
| `--mes-gold` / `--gold` | `#C9A227` | Premium / quote CTA |
| `--mes-gold-2` / `--gold2` | `#F0CE73` | Gold highlight |
| `--mes-footer` / `--footer` | `#124C9C` | Footer background |
| `--mes-success` / `--success` | `#18C96A` | Success |
| `--mes-wa` / `--wa` | `#25D366` | WhatsApp |
| `--mes-bg` / `--bg` | `#F4F8FD` | Page background |
| `--mes-text` / `--text` | `#1C2E44` | Body text |
| `--mes-text-2` / `--text2` | `#3A5068` | Secondary text |
| `--mes-border` / `--border` | `#E2EAF5` | Borders |
| `--mes-muted` / `--muted` | `#6B8099` | Meta |
| `--mes-white` / `--white` | `#FFFFFF` | Cards |

Gradients:

- Brand: `linear-gradient(135deg, navy 0%, navy2 45%, turq 100%)`
- CTA: `linear-gradient(135deg, blue, turq)`
- Gold button: `linear-gradient(120deg, gold, gold2)`

Radii: `--r-s 16px`, `--r-m 24px`, `--r-l 32px`, buttons `14px`, pills `999px`.

Shadows: `--sh-s`, `--sh-m`, `--sh-l`, `--sh-glow`.

Layout: max `1400px`, section padding `120 / 80 / 60`, horizontal pad `24px`.

## Typography

- Body: Tajawal, Cairo, sans-serif — 18px / 1.7 (16px ≤640px)
- Headings / nav / buttons: Cairo
- h1 `clamp(36px, 5vw, 56px)` weight 900
- h2 `clamp(28px, 3.6vw, 42px)` weight 800
- h3 `clamp(22px, 2.4vw, 28px)` weight 700
- Article prose 17px / 1.9

Fonts are loaded from Google Fonts with `preconnect`, and can be self-hosted later via settings.

## Breakpoints

`640px`, `768px`, `1024px` (HTML). Theme also targets 320–1920 as required.

## Shared chrome

- Loader `#loader` navy splash
- Header `#hdr` 80px → 70px glass after 40px scroll
- Mobile panel `.mob` full-screen navy
- Footer 4 columns + map + copyright
- Floating `.fab-stack` after 500px scroll
- No mega menu in the default visual

## Page map

| HTML | Theme template |
|---|---|
| home.html | `front-page.php` |
| about.html | `page-templates/about.php` |
| services.html | `archive-mes_service.php` |
| service-single.html | `single-mes_service.php` |
| service-category.html | `taxonomy-mes_service_cat.php` |
| service-city.html | rewrite template `templates/service-city.php` |
| cities.html | `archive-mes_city.php` |
| city-single.html | `single-mes_city.php` |
| blog.html | `home.php` |
| article-single.html | `single.php` |
| portfolio.html | `archive-mes_portfolio.php` |
| offers.html | `archive-mes_offer.php` |
| reviews.html | `archive-mes_review.php` |
| booking.html | `page-templates/booking.php` |
| contact.html | `page-templates/contact.php` |
| faq.html | `archive-mes_faq.php` |
| privacy.html / terms.html | `page.php` legal layout |
| 404.html | `404.php` |

## Motion

Scroll reveal `.rv` / `.rv-l`, counters `[data-count]`, FAQ accordion, filters, before/after, review carousel, particles on home, button ripple. Honor `prefers-reduced-motion`.

## Dark mode

Separate token set under `[data-mes-theme="dark"]` mapping navy surfaces to deep navy and cards to `#12284F`.
