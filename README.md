# WP AI Support

A WordPress plugin for professional customer support with AI-powered ticket management.

When a customer submits a ticket, the AI reads your knowledge base and replies instantly. If it can't find a good answer, the ticket goes to a human agent. Customers can also choose to skip the AI and talk to support directly.

**Sell on:** [rtl-theme.com](https://www.rtl-theme.com/) &nbsp;|&nbsp; **Docs:** [wpaisupport.ir](http://wpaisupport.ir/)

---

## What's in this repo

```
plugin/      WordPress plugin (PHP)
src/         React frontend (TypeScript + Tailwind)
website/     Landing page + documentation site (plain HTML)
```

---

## 1. Plugin

The WordPress plugin lives in `plugin/ai-ticket-support/`. Install it like any other plugin — upload the folder to `wp-content/plugins/` and activate.

Two URLs are registered after activation:

| URL | Who sees it |
|-----|-------------|
| `yoursite.com/helpdesk` | Customers (logged-in WordPress users) |
| `yoursite.com/helpdesk-admin` | Admins (`manage_options` capability) |

**License:** The admin panel requires an active RTL license. `rtl-license.php` contains the license check — upload this file to the RTL encoder before shipping.

---

## 2. Frontend (React app)

The React app is embedded inside the plugin. It serves both the user panel and the admin panel depending on the `mode` passed from PHP.

**Dev setup:**

```bash
npm install
npm run dev       # starts Vite dev server
npm run build     # outputs to plugin/ai-ticket-support/assets/dist/
```

**Routes:**

| Path | Description |
|------|-------------|
| `/tickets` | Ticket list |
| `/tickets/new` | Submit a new ticket |
| `/tickets/:id` | Chat view |
| `/tickets/:id/ai-show` | AI answer + accept/escalate |
| `/knowledge` | Admin knowledge base |
| `/settings` | Admin settings |

---

## 3. Website

Static landing page and documentation at `website/`. No build tool needed for HTML — just edit and open in a browser. Tailwind CSS is pre-generated.

**Rebuild Tailwind** (only needed after adding new utility classes to the HTML):

```bash
cd website
npx tailwindcss -c tailwind.config.js -i assets/input.css -o assets/tailwind.css
```

**Deploy:** copy the contents of `website/dist/` to your host root.

---

## Stack

- **Plugin:** PHP 8.1+, WordPress 6.0+, REST API
- **Frontend:** React 18, TypeScript, Vite, Tailwind CSS 3, React Router 6
- **Font:** Ravi (variable, Persian)
- **AI provider:** GapGPT (Iranian AI service with Farsi support)
