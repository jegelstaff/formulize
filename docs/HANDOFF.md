# formulize.org revision — handoff

Everything in `docs/` in this project mirrors the path it belongs at in the real
`docs/` folder of the `jegelstaff/formulize` repo. Copy the tree over and run
`bundle exec jekyll serve`.

`Formulize.org preview.dc.html` is a rendered preview of the new pages
(clickable menu, no Jekyll needed). It loads the *real* stylesheet, so what you
see there is what Jekyll will output. It is not part of the site — do not commit it.

## Information architecture (Sept 2026 revision)

Each homepage band answers one question, in the order a visitor needs it:

1. **Hero** — *Improvise. Organize. Customize.* as the headline, one line of
   copy, two buttons (Try it yourself → `/get/`, See what it does →
   `/features/`), and the intro video at full size. Nothing else.
2. **Latest news** — three cards. Repeat visitors get the new thing immediately.
3. **See it work** — the video row.
4. **Three ways to start** — the three-way choice cards, from
   `_includes/get-choice.html`, under one button to `/pricing/`.

The Improvise/Organize/Customize block is no longer on the homepage at all. It
lives on `/features/`, where it has room to be the real thing: the four-bullet
triad, then an **Everything, in every copy** full feature list in five groups
(forms and data, workflow and people, AI, extending it, running it), with links
out to the search syntax, the API, configuration as code and the local dev
environment. That list is the place to add detail as the product grows — it is
plain `.feature-cols` markup, add a `<div><h3>…</h3><ul>…</ul></div>` per group.

A new **`/get/`** page is the single place anyone goes to obtain the software:
the same three-way choice cards, the release downloads, requirements, install
guides, PDF guides, and a link into `/pricing/` for the detailed ladder and
comparison table.

`/download/` no longer exists as its own page — `get.html` carries
`redirect_from: /download/`, so every old link and every reference in the ~120
markdown pages still lands correctly.

Menu is now six items — **Features · AI · Videos · Pricing · News · Community** —
with *Get Formulize* promoted to the primary header button (it used to say "Get
hosted" and point off-site to formulize.net; it now points at `/get/`, which
serves the free and paying audiences in one place).

The `/features/` page absorbed the hero's explanatory copy and the whole
Improvise/Organize/Customize story, so it now reads as the full "what Formulize
is and does" page — it is what the hero's *See what it does* button points at. If you would rather it were called
**About Formulize**, only two things change: the nav label in `_includes/nav.html`
and the `permalink` in `features.html` (add `redirect_from: /features/`).

## Look and feel (new palette and type)

Every colour and font on the site comes from the `:root` block at the top of
`formulize.css`. Nothing downstream hard-codes a colour, so the whole site can
be re-skinned from those few lines.

| Token | Value | Where it lands |
| --- | --- | --- |
| `--color-bg` | `#cee0ea` | the page ground, every page |
| `--color-shell` | `#3a3a3a` | header and footer |
| `--color-cta` | `#81de76` | primary buttons and the "most popular" flag, always with black type |
| `--color-accent` | `#4c7ec0` | links, prices, tick marks, hovers, the pricing rails |
| `--color-surface` | `#ffffff` | cards, tiers, table surfaces |
| `--color-text` | `#000000` | body copy and headings |

Type is **Unbounded ExtraBold** for headings and **Poppins Regular** for body,
both tracked at +4% (`--tracking: 0.04em`), loaded from Google Fonts by the
`@import` on the first line of the stylesheet.

The hero's **texture tile is the site's own `assets/img/hero-pattern.png`** — the
same checkbox, plus, circle and diamond motifs as before, but recoloured to
white on transparency at 13% and re-tiled at 198×196. The version in the repo
today is fully opaque and baked in the old light blue, so it would have painted
the previous colour over the new hero band. **This file must be copied over the
existing one when you commit.** Being alpha-only, it now reads as texture over
whatever `--color-hero` happens to be, so a future palette change needs no new
artwork.

Three deliberate departures from the mockup, all for legibility:

- **The hero band is `--color-hero: #3c66a1`**, a darker step of the accent blue
  rather than the mockup's pale blue. White body copy on the mockup's band only
  reached about 2.5:1; this clears 4.5:1.
- **Green is a fill, never ink.** `#81de76` on the pale ground is about 1.6:1 as
  text, so it appears only as a button or badge background with black type on
  it. Where a green *word* was wanted, the accent blue does the job.
- **Display sizes came down one step.** Unbounded ExtraBold at +4% tracking sets
  much wider than the previous face, so `h1`–`h4` are smaller in px to keep the
  same words per line.

## New files

| File | What it is |
| --- | --- |
| `assets/css/formulize.css` | The whole new stylesheet. Plain CSS, served as a static asset — no Sass step, so it can also be opened directly in a browser. Palette and type tokens live in the `:root` block at the top — see *Look and feel* below. |
| `_includes/nav.html` | The main menu. One `menu` variable at the top holds the seven items — edit that line to reorder or rename. |
| `_includes/site-footer.html` | Four-column footer (Get started / Learn / Community) plus the action strip. Documentation and Roadmap live here and on the Features page. |
| `_includes/action-strip.html` | The Download / GitHub / Ko-fi / Connect banner, unchanged in substance, restyled. Now a pre-footer band on every page. |
| `_includes/latest-news.html` | Headline list of recent posts. `{% include latest-news.html limit=5 %}` |
| `_includes/latest-news-cards.html` | The same posts as three cards, with date, title and excerpt. Used on the homepage. |
| `_includes/get-choice.html` | The three-way "how involved do you want us to be" cards. Was inline on the homepage; now on `/get/` and editable in one place. |
| `features.html` | New. The Improvise/Organize/Customize triad, the AI story, and the cards linking to Documentation, Roadmap and the search cheat sheet. |
| `pricing.html` | New. The four-step ladder (Hosted → + Training → + Support → Enterprise) above a free Open Source band, a hosting-size row (Solo/Trio/Bronze), à-la-carte add-ons, and a six-column comparison table. The earlier eight-tier version is kept outside `docs/` at `reference/pricing-8-tier-v1.html`. |
| `get.html` | New, at `/get/`. The single page for obtaining Formulize: the three-way choice, release downloads, requirements, install guides, PDF guides. Replaces `download.html` and carries `redirect_from: /download/`. |
| `discounts.md` | New. Non-profit and individual prices, linked from the pricing page. |
| `news/index.html` | Paginated blog index (6 per page, `/news/page2/` etc.). |
| `_posts/*.md` | Four starter posts. |

## Changed files

- `_layouts/default.html` — top nav instead of the left sidebar column. Content
  is wrapped in `.prose` unless the page sets `full_width: true`, so the ~120
  existing markdown pages get readable typography with no edits.
- `_layouts/post.html` — date, categories, cover image, prev/next.
- `_config.yml` — added `signup_url`, `author`, pagination, `jekyll-paginate`,
  `jekyll-feed`, and a `defaults` block putting posts at `/news/:title/`.
- `assets/css/style.scss` — now only imports `rouge-github` for code
  highlighting. All other styling moved to `formulize.css`.

## Two files to delete when you commit

Both were turned into posts, and each post carries a `redirect_from` for the old
URL. Leaving the originals in place will make Jekyll warn about a URL conflict:

- `summit.md` → `_posts/2022-08-16-formulize-summit-toronto.md`
- `open-source-developer-of-the-year.md` → `_posts/2026-03-02-open-source-developer-of-the-year.md`

`_includes/links.html` is now unused (the footer replaced it) but harmless to keep.

## Placeholders to fix before publishing

- Two posts have invented dates, flagged in their own first line:
  `2026-02-10-formulize-and-ai.md` and `2026-08-01-formulize-hosting-open-to-everyone.md`.
- Every sign-up button points at `site.signup_url` (currently `https://formulize.net`).
  Set per-plan URLs there when they exist.
- The training add-ons have no price yet — they read "quoted as a one-time fee".
- The ladder collapses two rows of your sheet: Silver ($1,199, support without the training days) is
  small print on the + Support card, and Diamond ($2,299, in-person training) is folded into
  Enterprise. Split them back out if they need to be buyable on their own.
