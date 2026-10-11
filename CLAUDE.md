# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A static marketing site for a Ukrainian driving school ("Автошкола «Драйвер»"), with two branches sharing one codebase: Тетіїв at `/` (https://www.avtoshkoladriver.com.ua/) and Сквира at `/skvyra/`. There is no framework and no application layer — it is two statically-built Parcel pages, plus one shared PHP endpoint for the contact form. UI text is Ukrainian; code comments are mostly Russian/Ukrainian.

## Commands

```bash
npm install        # install dependencies
npm start          # dev server with HMR at http://localhost:1234 (serves both entries flat: /index.html, /skvyra.html)
npm run build      # builds BOTH branches into dist/ (see Build & deploy pipeline)
```

There are no tests, linters, or type checks in this project.

## Build & deploy pipeline

- **Parcel** is the bundler, with **two separate entry points built by two separate `parcel build` invocations** (`npm run build:main` / `npm run build:skvyra`, chained by `npm run build`) — not a single glob — because each branch needs a different `--public-url`:
  - `build:main`: `parcel build src/index.html --dist-dir dist --public-url ./` → `dist/index.html` + assets at `dist/` root, relative URLs (unchanged from before the branches feature).
  - `build:skvyra`: `parcel build src/skvyra.html --dist-dir dist/skvyra --public-url /skvyra/` → `dist/skvyra/skvyra.html` + its own asset copies, then `scripts/rename-skvyra-entry.js` renames that file to `dist/skvyra/index.html`. The `/skvyra/`-absolute public URL (not relative `./`) is deliberate: it makes the page's assets resolve correctly regardless of whether the host redirects bare `/skvyra` to `/skvyra/` or not.
  - Result: `dist/` already has the exact shape to upload straight to the PHP host's `www/` — `dist/index.html` + `dist/skvyra/index.html` as a real subfolder. No `.htaccess`/rewrite rules are needed for routing; `/skvyra/` works because it's a physical directory with its own `index.html`.
- `.github/workflows/deploy.yml` runs `npm ci && npm run build` on every push to `main` and publishes `dist/` to the `gh-pages` branch — unaffected by the two-invocation build, since it still just calls `npm run build`. This is **not** the real production deploy (see below).
- Real production deploy is manual: `dist/`'s contents are uploaded as-is to the PHP host's `www/`. `package.json`'s `homepage` still points at the unrelated GitHub Pages URL — that mismatch predates the branches feature and is harmless (relative/absolute `--public-url` values don't depend on it).
- **PHP files are not part of the Parcel build** (`send_mail.php`, `PHPMailer/`, `template_mail.html`, the external `config.php`) — they live directly on the host, outside `dist/`, and are deployed/edited separately. Critically, `template_mail.html` must be uploaded from `src/`, not from `dist/` — `src/template_mail.html` also happens to sit in Parcel's old top-level glob path, but since entries are now explicit (`src/index.html`, `src/skvyra.html`), Parcel no longer builds it as a mangled "page" by accident.

## Architecture / how the pieces connect

### Branch data

`src/data/branches.json` is the canonical reference for both branches' contact info, SEO copy, and map data (phone, address, maps link/embed, schema.org address fields, notify emails). It is **not** imported by code (no shared JS/HTML templating layer reads it directly) — it exists so a human maintainer has one place to look up "what are Сквира's current values," which must then be hand-mirrored into the few places below. When updating a branch's phone/address/etc., update `branches.json` *and* the actual rendered values.

### Two thin entry files + shared partials, templated via `posthtml-include` locals

1. **`src/index.html`** (branch: `main`/Тетіїв) and **`src/skvyra.html`** (branch: `skvyra`/Сквира) are the two Parcel entry points. Each composes the page from the *same* partials in `src/partials/` via `posthtml-include`'s `<include src="./partials/x.html" locals='{...}'>` tags, passing that branch's data as `locals`. Section order lives in these two files (kept in sync by hand — there's no shared "body" partial, since nesting locals through a shared aggregator partial wasn't worth the added indirection for just two entries).
2. Partials that differ per branch use **`{{ variable }}` interpolation** and **`<if condition="...">...</if><else>...</else>` conditionals** from `posthtml-expressions` (bundled inside `posthtml-include@1.7.4`, confirmed working — no extra dependency). Conventions:
   - `header.html` / `footer.html` / `map.html`: `phone`, `phoneDisplay`, `address`, `mapsLink` (map.html also takes `mapEmbedSrc`). Сквира currently has no phone/maps link yet (`null` in locals) — the `<if>` blocks hide the phone link and map iframe entirely rather than rendering empty/broken markup; a plain `<span>` shows the address text with no link.
   - `hero.html` and the visually-hidden SEO `<h2>` in `category/cost/credit/exam/practice/schedule.html`: just `townName`.
   - `modal-choise.html`: `currentBranch` (`"main"` or `"skvyra"`) — see below.
   - **Each top-level `<include>` needs its own `locals` attribute** — locals do not automatically propagate from a parent include to sibling includes in the same file, so the same values get repeated across the ~10 includes that need them in each entry file. This is intentional data duplication (bounded, mechanical, per-entry-file) rather than new tooling.
3. **`src/sass/index.scss`** — the single stylesheet linked by both HTML entries (shared, no per-branch CSS). It `@import`s `modern-normalize`, then `_fonts`/`_reset`/`_base`, then one partial `_x.scss` per section. Section SCSS partials must be registered here or their styles won't ship.
4. **`src/index.js`** — the single ES module referenced by both HTML entries. It imports every behavior module from `src/js/` and initializes `AOS` (scroll animations). New JS behavior must be imported here to run.

### JS behavior modules (`src/js/`)

Plain DOM scripts, no framework — they query the DOM at load and attach listeners. Cross-cutting conventions:

- **Modals are attribute-driven.** `multi-modal.js` wires open/close buttons by data attributes: an element `[data-modal-order]` is the modal, `[data-modal-order-open]` opens it, `[data-modal-order-close]` closes it. To add a modal, add its base attribute to the `modals` array in `multi-modal.js` and use the same `-open`/`-close` suffix convention in HTML. `toggleModal` also swaps the modal title/button style based on the clicked element's `data-title`.
- **`form.js`** handles the contact form (`#form`): validates name/phone are non-empty, POSTs JSON (`{name, phone, branch}`) to the **absolute** path `/send_mail.php` (must stay absolute, not relative — a relative `send_mail.php` resolves inconsistently depending on whether the current URL is `/`, `/skvyra`, or `/skvyra/`), shows a `Notiflix` toast, and opens the success modal via the shared `toggleModal`. `branch` comes from `document.body.dataset.branch`, set per-entry (`<body data-branch="main">` / `<body data-branch="skvyra">`).
- **`modal-choise.js`** is a branch-picker overlay (not the `multi-modal.js` attribute convention): on every page load it force-shows `#overlay` (`modal-choise.html`) and locks body scroll. The markup itself (server-rendered via the `currentBranch` local) decides which button is "you are here" (a `<button data-branch-current>` that just closes the overlay) vs. "go to the other branch" (a plain `<a href="...">` link to that branch's URL — no JS needed for navigation). The JS only wires the close behavior.
- Other modules are self-contained scroll/interaction effects: `header-bg.js` (sticky header background past 50px), `scroll-top.js` (scroll-to-top button), `optimizate-map.js` (lazy-shows the map iframe once the exam section scrolls into view, throttled with `lodash.throttle`), `openContact.js` (floating contact/phone widget animation).

### Contact form backend (PHP)

`src/send_mail.php` + the vendored `PHPMailer/` library send form submissions via SMTP to the same two addresses for both branches (`avtoshkoladriver.tet@gmail.com`, `solodovnik.denis123@gmail.com`) — the branch is identified in the **subject line and email body** (`%branch%` placeholder in `src/template_mail.html`), not by routing to different inboxes. `send_mail.php` keeps its own small hardcoded branch id→label allow-list (independent from `branches.json`, deliberately — it's a server-side trust boundary that validates/falls back the client-supplied `branch` field rather than trusting it directly). This requires a PHP host — it is **not** exercised by the Parcel dev server or GitHub Pages, so the form only works end-to-end on the production PHP host. When editing the form, keep the field names (`name`, `phone`, `branch`) in sync between `form.js`, and `send_mail.php`/`template_mail.html`.

### Adding a third branch

1. Add its data to `src/data/branches.json` (reference only).
2. Create `src/<branch>.html` mirroring `src/skvyra.html`'s structure with that branch's `locals` on each include.
3. Add a `build:<branch>` npm script (copy `build:skvyra`'s pattern — own `--dist-dir dist/<branch>`, own absolute `--public-url /<branch>/`, own rename step) and chain it into `build`.
4. Add a third button/link to `modal-choise.html` (currently hand-written for two branches, not a loop).
5. Add the branch id/label to `send_mail.php`'s allow-list.
6. Add `https://.../<branch>/` to `sitemap.xml`.

## Libraries in use

`aos` (scroll animations), `notiflix` (toast notifications), `lodash.throttle`, `modern-normalize`, `posthtml-include` (bundles `posthtml-expressions` for the `{{ }}`/`<if>` templating described above). Fonts are a large local Proxima Nova family in `src/fonts/`, declared in `src/sass/_fonts.scss`.
