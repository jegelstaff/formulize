# formulize.org revision — handoff

Everything in `docs/` in this project mirrors the path it belongs at in the real
`docs/` folder of the `jegelstaff/formulize` repo. Copy the tree over and run
`bundle exec jekyll serve`.

`Formulize.org preview.dc.html` is a rendered preview of the new pages
(clickable menu, no Jekyll needed). It loads the *real* stylesheet, so what you
see there is what Jekyll will output. It is not part of the site — do not commit it.

## New files

| File | What it is |
| --- | --- |
| `assets/css/formulize.css` | The whole new stylesheet. Plain CSS, served as a static asset — no Sass step, so it can also be opened directly in a browser. Organic design-system tokens at the top, Formulize blue kept as a third accent. |
| `_includes/nav.html` | The main menu. One `menu` variable at the top holds the seven items — edit that line to reorder or rename. |
| `_includes/site-footer.html` | Four-column footer (Get started / Learn / Community) plus the action strip. Documentation and Roadmap live here and on the Features page. |
| `_includes/action-strip.html` | The Download / GitHub / Ko-fi / Connect banner, unchanged in substance, restyled. Now a pre-footer band on every page. |
| `_includes/latest-news.html` | Headline list of recent posts. `{% include latest-news.html limit=5 %}` |
| `features.html` | New. The Improvise/Organize/Customize triad, the AI story, and the cards linking to Documentation, Roadmap and the search cheat sheet. |
| `pricing.html` | New. The four-step ladder (Hosted → + Training → + Support → Enterprise) above a free Open Source band, a hosting-size row (Solo/Trio/Bronze), à-la-carte add-ons, and a six-column comparison table. The earlier eight-tier version is kept outside `docs/` at `reference/pricing-8-tier-v1.html`. |
| `download.html` | New. Release downloads, requirements, install guides, PDF guides. |
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
