# CLAUDE.md — WP AI Support

Project overview and working instructions for Claude Code.

---

## Project structure

```
plugin/ai-ticket-support/   WordPress plugin
src/                         React frontend (compiled into the plugin)
website/                     Static landing page + docs
```

---

## Part 1 — Website & Docs

### Location
```
website/
  index.html          Landing page
  docs/index.html     Documentation page
  assets/
    tailwind.css      Pre-generated (do not edit manually)
    input.css         Tailwind entry (just @tailwind directives)
    images/           Plugin screenshots (PNG, used by carousel)
  fonts/
    Ravi-VF.ttf       Persian variable font
  tailwind.config.js  Scans ./**/*.html for used classes
  dist/               Build output for deployment (gitignored)
```

### Rules
- Both HTML files are self-contained — no build step for content changes.
- RTL layout (`dir="rtl"`), Persian (Farsi) language, font is Ravi.
- Tailwind CSS is local (no CDN). After adding new utility classes to any HTML file, rebuild:
  ```bash
  cd website
  npx tailwindcss -c tailwind.config.js -i assets/input.css -o assets/tailwind.css
  ```
- `overflow-x: clip` on `html` (not `hidden`) — prevents horizontal scroll without breaking `position: fixed` nav.
- To build for deployment: copy `index.html`, `docs/`, `assets/`, `fonts/` to host root.
- Do NOT commit `website/*.zip` (gitignored).

### Key custom CSS classes
- `.hero-bg` — dark blue gradient background for hero section
- `.card-hover` — lift animation on feature cards
- `.glow` — brand-colored box-shadow on hero mockup
- `.nav-link` / `.nav-link.active` — docs sidebar navigation states
- `.prose` — docs content typography; `.prose a:not([class])` for inline links only

---

## Part 2 — Frontend (React)

### Location
```
src/
  api/            REST API client (tickets.ts, admin.ts, client.ts)
  components/     Shared UI components
  pages/          One file per screen
  icons/          Inline SVG icons (currentColor, size prop)
  utils/          color.ts, date.ts helpers
  config.ts       Reads window.atsConfig injected by PHP
  App.tsx         React Router routes
  main.tsx        Entry point
  index.css       Tailwind base + Ravi @font-face
```

### Commands
```bash
npm install
npm run dev      # Vite dev server (mock data, no WordPress needed)
npm run build    # Outputs to plugin/ai-ticket-support/assets/dist/
```

### Rules
- RTL layout (`dir="rtl"` on the root div in Layout).
- `window.atsConfig` is the bridge from PHP — it carries `mode`, `restUrl`, `nonce`, `user`, `brandColor`, `basePath`.
- `mode === 'admin'` → shows admin nav (tickets queue, knowledge base, settings).
- `mode === 'user'` → shows user nav (ticket list, new ticket).
- Brand color is a CSS custom property (`--brand`) injected by PHP from saved settings. Do not hardcode `#0068ff` in components — use `text-brand`, `bg-brand` etc.
- All text facing users is in Persian (Farsi). Error messages, labels, placeholders — keep them in Persian.
- Tailwind config: `tailwind.config.js` at repo root. Custom tokens: `brand`, `brand-dark`, `brand-tint`, `ink-*`, `surface-*`, `line`.

### Route map
| Path | Component | Who |
|------|-----------|-----|
| `/tickets` | TicketListPage | User |
| `/tickets/new` | TicketNewPage | User |
| `/tickets/:id` | TicketChatPage | User |
| `/tickets/:id/loading` | TicketLoadingPage | User |
| `/tickets/:id/ai-show` | TicketAiShowPage | User |
| `/tickets/:id/not-found` | TicketNotFoundPage | User |
| `/tickets` | AdminTicketListPage | Admin |
| `/knowledge` | AdminKnowledgePage | Admin |
| `/settings` | AdminSettingsPage | Admin |

---

## Part 3 — Plugin (WordPress / PHP)

### Location
```
plugin/ai-ticket-support/
  ai-ticket-support.php       Plugin entry point (headers + bootstrap)
  rtl-license.php             RTL license check — THIS FILE GETS ENCODED
  includes/
    Plugin.php                Boots hooks; registers user REST routes
    Pages.php                 Serves /helpdesk and /helpdesk-admin URLs
    Database.php              All DB queries (custom tables)
    AiService.php             Calls GapGPT, scores knowledge base answers
    GapGptClient.php          HTTP client for GapGPT API
    Assets.php                (legacy, may be unused)
    RestApi/
      AbstractController.php  Base: ok(), error(), not_found(), forbidden()
      TicketsController.php   User REST routes (/ats/v1/tickets/*)
      AdminController.php     Admin REST routes (/ats/v1/admin/*)
      SettingsController.php  Settings REST routes (/ats/v1/settings/*)
  templates/
    app-shell.php             Standalone HTML shell that loads the React app
    license-required.php      Shown when license is inactive
  assets/dist/                Compiled React app (committed, built by Vite)
```

### REST API base
`/wp-json/ats/v1/`

User routes (always active):
- `GET/POST /tickets`
- `GET /tickets/:id`
- `POST /tickets/:id/messages`
- `POST /tickets/:id/route-to-support`
- `POST /tickets/:id/close`

Admin + settings routes (only registered when license is active via `rtl-license.php`):
- `GET /admin/tickets`
- `GET/PUT /admin/tickets/:id`
- `POST /admin/tickets/:id/messages`
- `GET/POST/PUT/DELETE /admin/categories`
- `GET/POST/PUT/DELETE /admin/answers`
- `GET/POST /settings`

### License gate
`rtl-license.php` is included from `ai-ticket-support.php` after the autoloader. When the RTL license is active it:
1. Runs `add_action('ats_admin_active', '__return_true')` — signals admin is licensed
2. Registers admin + settings REST routes via `rest_api_init`

`Pages.php` checks `has_action('ats_admin_active')` before serving the admin React app. If the hook is missing, it shows `license-required.php` instead.

**To ship:** upload `rtl-license.php` to RTL encoder → replace with the encoded version.

### Database tables
All prefixed with `{wp_prefix}ats_`:
- `tickets` — main ticket records
- `messages` — chat messages per ticket
- `categories` — knowledge base categories
- `saved_answers` — knowledge base entries
- `attachments` — file attachments

### Key constants
| Constant | Value |
|----------|-------|
| `ATS_DIR` | Absolute path to plugin folder (trailing slash) |
| `ATS_URL` | URL to plugin folder (trailing slash) |
| `ATS_VERSION` | `1.0.0` |

### Rules
- Namespace: `ATS\`. Autoloader maps `ATS\Foo\Bar` → `includes/Foo/Bar.php`.
- All DB access goes through `Database::instance()` — no raw `$wpdb` calls outside that class.
- REST responses use helpers from `AbstractController`: `$this->ok()`, `$this->error()`, `$this->not_found()`.
- `AiService::suggest()` returns a string (AI answer) or `null` (no good answer found). Null triggers the auto system message routing ticket to human support.
- Default `MAX_BODY_CHARS` = 1200, `TOP_K` = 4.
- AI providers: only GapGPT is shown in settings UI (`gapcode` provider id). Others exist in code but are hidden.
