# formulize.org

The Formulize project website. Jekyll, published to GitHub Pages by
`.github/workflows/jekyll.yml` on every push to `master`, and on demand from
the Actions tab.

```
cd docs
bundle install
bundle exec jekyll serve     # http://localhost:4000
```

Always run `jekyll serve` through `bundle exec`. This project's Gemfile.lock
pins specific gem versions (including the GitHub Pages gem, which tracks what
github.io actually runs); a bare `jekyll serve` uses whatever Jekyll happens
to be installed globally instead, which is usually a different version and
can behave differently from what actually gets deployed.

To check a change from another device (a phone on the same Wi-Fi, say), bind
to every network interface instead of just this machine:

```
bundle exec jekyll serve --host 0.0.0.0
```

Then, from the other device, browse to `http://<this-computer's-LAN-IP>:4000`
— find that IP with `ipconfig` (Windows) or `ifconfig`/`ip addr` (macOS/Linux).
Windows may prompt to allow Ruby through the firewall the first time; allow it
for private networks.

The published version of this same walkthrough is
[Writing and previewing documentation](https://www.formulize.org/developers/version_control/documentation/).

## Information architecture

The menu is the *understanding* layer, for people who have never heard of
Formulize: **Features · Examples · AI · Pricing · Docs · News**, edited as one
`menu` variable at the top of `_includes/nav.html`.

The footer is the *doing* layer, in four columns named after what a visitor is
trying to accomplish: getting started, professional services, help and
documentation, project and community.

The audience is two halves — developers and consultants, and staff at
non-profits and small businesses who have IT needs but do not describe them in
IT terms. Most pages have to work for both, which is why `/get/` exists.

### The pages that carry the funnel

| Page | Job |
| --- | --- |
| `/get/` | The fork, and the target of the header button. Three doors, three destinations. Not a content page. |
| `/download/` | Self-host only. Keeps its URL because ~120 markdown pages link to it. |
| `/pricing/` | Hosting plans. `_includes/start-rail.html` is for choosing, `start-table.html` for verifying. |
| `/services/` | Training, support, system design, data migration, by the hour. The half of the business the plans do not describe. |
| `/examples/` | What people build. The only page written for someone who would not call their problem "a database". |
| `/signup/` | Placeholder for the **Hosting Requests** form (formulize.net form 2, screen 3). |
| `/contact/` | Placeholder for the **Help Requests** form (formulize.net form 1, screen 1). |

`_includes/get-choice.html` is the three-way choice used on the homepage,
`/get/` and `/download/`. Each card links somewhere different — that is the
point of it, so do not collapse them back onto one destination.

Two pages are waiting on the form-embedding work. Until it lands they show a
marked placeholder and fall back to `info@formulize.org`, which works today.
Neither form is publicly reachable yet: every menu item pointing at them on
formulize.net is restricted to the Webmasters group.

## News

News is **not** Jekyll posts. It lives in the News form on formulize.net and is
pulled in at build time by `_plugins/news_pages.rb`, which generates:

- `/news/` — announcements and articles, plus the ten most recent releases
- `/news/<slug>/` — one page per story
- `/news/releases/` — the release feed, which is most of the entries by count
- `/news/entry/<id>/` — a permanent redirect per story, so editing a headline
  never strands a shared link
- `/news/feed.xml` — RSS

Slugs come from the form's `news_slug` field, falling back to a slugified
headline. `news_type` (Announcement / Article / Release) decides which index a
story lands on; anything that is not explicitly a Release is treated as a
story, so a blank value surfaces rather than disappearing.

Publishing a story therefore needs a site rebuild. The News form's
`on_after_save` hook fires a `repository_dispatch` at GitHub to do that
automatically; if it ever stops working, run the workflow by hand from the
Actions tab and the site catches up.

Every successful fetch is cached to `_data/news.json`, which is used if
formulize.net cannot be reached. `FORMULIZE_NEWS_OFFLINE=1` skips the fetch and
builds from that cache.

## Look and feel

Every colour and font comes from the `:root` block at the top of
`assets/css/formulize.css`. Nothing downstream hard-codes a colour, so the
whole site can be re-skinned from those few lines. `assets/css/style.scss` only
imports `rouge-github` for code highlighting.

Pages set `full_width: true` when they lay out their own bands; without it
`_layouts/default.html` wraps the content in `.prose`, which is what gives the
~120 markdown pages readable typography with no edits.

## Generated content

Three things in this site are generated rather than written, and all three are
built from real sources rather than kept in step by hand:

- `_plugins/news_pages.rb` — the news section, from the Public API
- `_plugins/mcp_tool_pages.rb` — one page per MCP tool, from a dump of the
  running app's `registerTools()`
- `_plugins/breadcrumbs.rb` — breadcrumb trails, by walking URL segments

`_data/` is gitignored in full. Everything in it is fetched or generated at
build time.

## Known gaps

- `/about/` carries a `TODO` comment listing facts that were left out rather
  than guessed: who else is on the team, the legal entity behind
  formulize.net, any honest deployment count, and whether there is a privacy
  policy to link to.
- `/signup/` keys off `level-1`…`level-4` in the query string rather than off
  the plan names, which is what let the plans be renamed from `Level 1`–`Level
  4` to the You/We names without breaking any shared link. Keep it that way.
- `_includes/start-rail.html` notes that backup retention values are
  provisional.
