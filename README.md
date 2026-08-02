# Lanton Church of Christ — Website

A simple, elegant, **WordPress block theme** for Lanton Church of Christ — a New
Testament congregation at 2915 Greens Mill Rd, Spring Hill, TN 37174.

Built theme-first so it's **portable to any live WordPress host with no rebuild**:
no page builders, lightweight, design tokens in `theme.json`, parallax homepage
powered by the theme's own vanilla CSS/JS.

## Run it locally (wp-now)

You need [Node.js](https://nodejs.org) (already installed). No Docker, no app.

```bash
# from this folder:
npx @wp-now/wp-now start --path "lanton-church" --port 8881
```

Then open **http://localhost:8881** (front end) or
**http://localhost:8881/wp-admin** (login: `admin` / `password`).

- Stop the server: press `Ctrl+C` in its terminal.
- wp-now runs in **theme mode**: it spins up WordPress, drops this theme into
  `wp-content/themes/`, activates it, and (via the theme's first-run seeder)
  creates all the pages, the front page, and pretty permalinks automatically.

## What's in the theme (`lanton-church/`)

| Path | Purpose |
|---|---|
| `style.css` | Theme header (required by WordPress) |
| `theme.json` | Design system — colors, fonts, spacing, button/link styles |
| `functions.php` | Enqueues fonts/CSS/JS, registers pattern category |
| `inc/page-setup.php` | First-run seeder: pages, front page, permalinks, block styles |
| `templates/front-page.html` | The 5-section parallax homepage |
| `templates/page.html` | Inner pages (navy title hero + content) |
| `templates/index.html`, `404.html` | Blog fallback + not-found |
| `parts/header.html`, `footer.html` | Sticky header + footer |
| `patterns/*.php` | Editable content for About, Service Times, Sermons, Events, Get Involved, Give, Contact |
| `assets/css/theme.css` | Parallax, scroll-reveal, component polish |
| `assets/js/parallax.js` | Vanilla JS: fade-in, scroll-reveal, word-by-word verse, multi-speed parallax, sticky header |

## Design

- **Warm & traditional:** deep navy `#1F2A44`, warm cream `#FBF8F2`, brass `#B08D57`.
- **Type:** Lora (serif headings) + Inter (sans body), via Google Fonts with
  system fallbacks.
- Mobile-first responsive; `background-attachment: fixed` parallax on desktop
  with a transform-based fallback on mobile; respects `prefers-reduced-motion`.

## Editing content

Everything is editable in **Appearance → Editor** (Site Editor) or by editing the
pattern files in `patterns/`. Placeholders are marked with `[ ... ]` — search for
`[` to find every spot that needs your real info (service times, phone, giving
link, staff names, sermon embeds, social URLs).

## Going live

Because this is a standalone block theme, deploying = copying the `lanton-church/`
folder into your live host's `wp-content/themes/` and activating it. The seeder
recreates the pages there too (it runs once per install). Remove the
`require_once .../inc/page-setup.php` line from `functions.php` after launch if you
don't want the scaffolding to run on the live site.
