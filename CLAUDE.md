# CLAUDE.md — Lanton Church of Christ Website

WordPress **block theme** for Lanton Church of Christ, a New Testament
congregation at 2898 Greens Mill Rd, Columbia, TN 38401. Built theme-first so
it deploys to any live WordPress host with no rebuild. No page builders.

## Run locally (no Docker)

```bash
npx @wp-now/wp-now start --path "lanton-church" --port 8881
```

- Front end: http://localhost:8881 — Admin: http://localhost:8881/wp-admin (`admin` / `password`)
- Package is **`@wp-now/wp-now`** (NOT `@wordpress/wp-now`, which 404s).
- Runs in **theme mode**: spins up WordPress, mounts this theme, activates it.
- The server is long-running; start it in the background and poll `wp-now.log`
  for `Server running at http://localhost:8881`.
- Verify changes with `curl`-ing pages (check status + key strings). After
  editing CSS/JS, bump `LANTON_VERSION` in `functions.php` if a cache busts.

## Layout (`lanton-church/`)

- `theme.json` — design tokens. **Palette sampled from the stained-glass logo**
  (`brand_assests/lanton logo.png`): a golden sun, warm cream glass, a teal
  window frame, sage-green hills. Slugs: `base`(warm white #FFFDF8)
  `surface`(sunlit cream #FBF1D7) `contrast`(stained-glass ink #2E2A1C —
  body/headings) `contrast-2`(window teal #548C9A) `primary`(golden amber
  #EAAA38) `primary-dark`(deep amber #C6881A) `teal-light`(#9AD2E0)
  `sage`(#7E9E63) `sage-light`(#A8B67E) `muted`(warm stone). Gold is the
  dominant brand color; **teal** is the secondary accent (photo frames on
  mission/about echo the logo's window, footer accent stripe, form focus,
  scrollcue); **sage** appears in the footer stripe. Fonts: Playfair Display
  (headings, weight 500) + Plus Jakarta Sans (body). Buttons: pill (999px),
  amber bg + white text, brighten + lift on hover.
- `functions.php` — enqueues Google Fonts + `assets/css/theme.css` +
  `assets/js/parallax.js`; registers the `lanton` pattern category; includes
  `inc/page-setup.php`, `inc/youtube.php`, `inc/contact-form.php`, `inc/smtp.php`.
- `inc/youtube.php` — the `[lanton_youtube]` shortcode that **is** the Sermons
  page: it pulls *every* upload from the church's channel (`@LantonChurchofChrist`)
  via the free YouTube Data API v3 and renders a searchable, click-to-play card
  grid (`patterns/sermons.php` just drops the shortcode into an `alignwide`
  group). New uploads appear on their own — an **hourly WP-Cron job**
  (`lanton_yt_refresh`) re-reads the channel, so nobody edits the site to add a
  sermon. The list is cached in a 1-hour transient plus a durable
  `lanton_yt_videos_backup` option, so a YouTube outage, quota trip, or dead key
  never blanks the page. A **"Most Recent Sermon" spotlight** (`lanton-featured`)
  sits above the archive showing the newest upload of any length, and the grid
  below is filterable by **All / Full Messages / Short Clips** — Shorts are told
  apart by runtime (`LANTON_YT_SHORT_MAX_SECONDS`, 3 min), which needs a second
  `videos` API pass since playlistItems carries no duration. Only one video ever
  plays at a time: starting another restores the previous card's thumbnail,
  which destroys its iframe (a cross-origin embed can't be told to pause).
  **Needs one thing: a free YouTube Data API key** —
  wp-admin → **Settings → "Sermon Videos (YouTube)"** (status, key field,
  "Refresh videos now"), or `LANTON_YT_API_KEY` in wp-config.php, or the
  `*_FALLBACK` const. Until it's set the page shows a friendly setup notice and
  admins get a dashboard nudge. The gallery used to live on its own `/videos/`
  page; that page is retired and 301s to `/sermons/`
  (`lanton_redirect_videos_page`), with `patterns/videos.php` kept only as a
  legacy fallback.
- `inc/contact-form.php` — the `[lanton_contact_form]` shortcode (used on the
  Contact page **and** the home page's bottom section) + its `admin-post`
  submission handler. Validates name/email/phone/subject/message, checks Google
  reCAPTCHA v2, and `wp_mail()`s the message to `LANTON_CONTACT_TO`. Reply-To is
  the visitor, so replying in the inbox goes straight back to them. Recipient is
  **currently dtkcenterprise@gmail.com for testing** — production is
  carterstephens6151@gmail.com (flip the const back before launch). Protected by
  a WP nonce + hidden honeypot. reCAPTCHA keys go in `wp-config.php`
  (`LANTON_RECAPTCHA_SITE_KEY` / `_SECRET_KEY`) or the `*_FALLBACK` consts —
  until both are set the form still works (nonce+honeypot) and shows a setup note
  to admins only. Success/error shows via a `?lanton_contact=…` redirect banner.
- `inc/smtp.php` — routes `wp_mail()` through a real SMTP mailbox so form emails
  actually arrive (same job as the WP Mail SMTP plugin, but built into the
  theme; defers to that plugin if it's ever installed). Configure via wp-config
  consts (`LANTON_SMTP_HOST` / `_PORT` / `_SECURE` / `_USER` / `_PASS` / `_FROM`
  / `_FROM_NAME`) or the `*_FALLBACK` consts — Gmail App Password quick-start is
  documented at the top of the file. Adds a **Tools → "Email (SMTP) Test"** admin
  page (send a test, see the failure reason) and a dashboard nudge until it's set
  up. Until SMTP is configured, `wp_mail()` won't deliver under wp-now/Playground
  (no local mail server).
- `inc/page-setup.php` — **first-run seeder** (guarded by the `lanton_seeded`
  option): creates the 8 pages, sets the static front page, pretty permalinks,
  site title, and registers the `ghost` / `ghost-light` button styles. Delete
  the `require_once` for it from `functions.php` before launch if undesired.
- `templates/front-page.html` — the **modern, restrained homepage**, namespaced
  `lh-*` (NOT the old full-bleed parallax `lanton-*` sections, which were
  removed as too busy/stock). Type-led, lots of whitespace, and minimal imagery:
  exactly **one** photo (a contained, teal-framed split hero, `.lh-hero`) and
  **one** solid-color band (the teal scripture verse, `.lh-verse`). The rest are
  clean type/cards on alternating cream/warm-white: a centered welcome + three
  `.lh-note`s, a `.lh-times` info card (service times + Get Directions), `.lh-card`
  event cards, and the contact-form section (`id="contact-form"`).
- `templates/page.html`, `index.html`, `404.html` — inner pages / fallbacks.
  `page.html` is full-width: `main` is flow, the title band (`.lanton-page-hero`)
  spans edge-to-edge with its text constrained to 980px, and `post-content` is
  constrained at 780px with a 1240px wide size — so patterns can use
  `"align":"wide"` to spread out (see `patterns/service-times.php`).
- `templates/page-sermons.html` — the Sermons page only. Same as `page.html`
  but the title band and body padding carry `is-compact` (roughly half the
  vertical space) and the `post-title` drops to `x-large`, so the video archive
  starts near the top instead of below a full-height hero.
- `templates/page-about.html` — the About page only (WP picks it up from the
  `about` slug). Drops `page.html`'s constrained wrapper and title band: `main`
  and `post-content` both use flow layout so the About sections span edge to
  edge like the homepage. The content still lives in `patterns/about.php`, so
  it stays editable in the page editor.
- `parts/header.html` — fixed header: the **stained-glass logo** (Site Logo
  block) at left + nav at right. The header stays frosted warm-white at all times
  (not transparent over the hero) so the colored logo and dark nav stay legible;
  it gains a shadow on scroll. The logo is registered as the WP Custom Logo by
  `lanton_set_custom_logo()` in `inc/page-setup.php`, which sideloads
  `assets/images/lanton-logo.png` into the media library (self-healing, runs on
  any DB). A `site-title-fallback` text wordmark shows only if the logo is
  missing (`:has()` hides it when the logo renders). `parts/footer.html`.
- `patterns/about.php` — the About page, written in the homepage's `lh-*` idiom:
  a centered hero (`.lh-about-hero`, which also carries `.lh-hero` so the
  `parallax.js` load fades — scoped to `.lh-hero .lanton-fade` — still fire), the
  "Our Story" split with the teal-framed congregation photo + a `.lh-facts`
  strip, a six-card `.lh-beliefs` grid (each card `.lh-note .lh-belief` with a
  scripture ref pinned to the bottom), a sage `.lh-verse` band (classed
  `lh-verse__quote` / `lh-verse__cite`, **not** `lh-verse__text`/`__ref` alone,
  so the homepage verse carousel leaves it alone), four numbered `.lh-step`s for
  a first visit, `.lh-leader` bio cards, and a closing `.lh-about-cta`. Cards sit
  in `.lh-grid` (CSS grid; per-section column width via `--lh-grid-min`).
- `patterns/*.php` — editable page content (about, service-times, sermons,
  events, get-involved, give, contact), category `lanton`, slug
  `lanton-church/<name>`. Pages are seeded with `<!-- wp:pattern {"slug":...} /-->`.
- `assets/css/theme.css` — all custom styling (CSS vars mirror theme.json:
  `--ink`, `--gold`, `--cream`, etc.). `assets/js/parallax.js` — vanilla JS:
  letter-by-letter hero, load fades, word-by-word verse, staggered
  IntersectionObserver reveals, transparent↔frosted nav, multi-speed parallax.

## Conventions / gotchas

- **No frameworks/libraries** except Google Fonts. Keep JS vanilla.
- Reveal/load animations (shared by homepage + patterns): add `lanton-reveal`
  for the staggered scroll-up (JS staggers siblings via `--lanton-delay`),
  `lanton-fade` for the hero load fade (JS targets `.lh-hero .lanton-fade` — keep
  that scoping if the hero class changes), and `lanton-animate-letters` for the
  hero headline letter split. All respect `prefers-reduced-motion`.
- The homepage hero is a contained **split** layout (`.lh-hero`): editorial type
  left, one photo right (`assets/images/hero-congregation.webp`) in a rounded,
  teal-framed figure. No full-bleed background, no parallax. Because the hero no
  longer carries the `.lanton-hero` class, `parallax.js` keeps the header solid
  (frosted) the whole way down — exactly what we want here.
- The scripture verse is a solid **teal band** (`.lh-verse`), not a photo. The
  congregation photo also appears on the **About** page pattern (`patterns/about.php`,
  the "Our Story" two-column block, `.lanton-about-photo`).
- Teal/sage photo frames use a `::after`/`::before` offset border (`var(--teal)`).
  Section backgrounds alternate `--cream` / `--cream-soft`; sections are full-width
  groups with constrained inner content, so their `background` spans edge-to-edge.

## Placeholders to fill (search content for `[`)

Service times, phone, email, giving link, staff/elder/deacon names + bios,
social URLs. (Sermons are no longer hand-embedded — that page is fed live from
YouTube; see `inc/youtube.php`.) The logo is set (the
stained-glass `lanton-logo.png`); replace it by swapping that file or setting a
new Custom Logo in the admin. **Contact form:** add Google reCAPTCHA v2 keys
(see `inc/contact-form.php`) to turn on the "I'm not a robot" check; **Sermons:**
add a free YouTube Data API key under Settings → Sermon Videos (YouTube); set SMTP
credentials (see `inc/smtp.php`) so form emails actually deliver, then verify
via **Tools → Email (SMTP) Test**. The form emails `LANTON_CONTACT_TO`
(currently dtkcenterprise@gmail.com for testing; production is
carterstephens6151@gmail.com).

## Going live

Copy `lanton-church/` into the host's `wp-content/themes/` and activate. The
seeder recreates pages once per install.
