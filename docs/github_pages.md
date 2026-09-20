---
layout: default
permalink: documentation/version_control/documentation/github_pages/
redirect_from:
 - documentation/github_pages/
 - developers/github_pages/
title: Github Pages
---

# GitHub Pages and Jekyll configuration

[formulize.org](https://www.formulize.org/) is a Jekyll site kept in the
`/docs/` folder of the main [formulize](https://github.com/jegelstaff/formulize)
repository. There is no separate documentation repository, no `formulize-docs/`
folder and no `gh-pages` branch — everything lives alongside the application
code, so a documentation change can be part of the same commit and the same
pull request as the code it explains.

To write or preview a page yourself, see
[Writing and previewing documentation](/documentation/version_control/documentation).
This page is the other half of that story: how the site is configured, what
generates the parts of it nobody writes by hand, and how it gets deployed.

## Configuration

The whole site is driven by `docs/_config.yml`. The parts worth knowing:

```yaml
title: Improvise. Organize. Customize.
markdown: kramdown
baseurl: ""
url: https://formulize.org
permalink: /:title/
repository: jegelstaff/formulize

signup_url: https://formulize.net
forms_url: https://formulize.net/services

plugins:
  - breadcrumbs
  - jekyll-github-metadata
  - jekyll-redirect-from

exclude:
  - _data/news.json
  - _data/writableFilesAndFolders.json
```

`baseurl` is empty and `permalink` has no repository name in it, because the
site is served from its own domain (formulize.org) rather than from a
`github.io/<repo>/` path — so URLs and links across the site are written as
plain absolute paths (`/features/`, `/documentation/classes`), not prefixed with
anything. The global `permalink: /:title/` is only a fallback; in practice every
page declares its own `permalink` in its front matter, which is what lets a
page's file name and its URL differ.

`forms_url` is the Formulize site that serves the embedded screens on
`/contact/` and `/signup/`, and it is load-bearing: a browser will not send
Formulize's session cookie into a frame whose domain differs from the page
around it, so while it points at formulize.net those forms display but saving
fails. The comments in `_config.yml` spell out what changing it involves.

`exclude` lists the two files that plugins rewrite on every build. Without
excluding them, `jekyll serve` sees its own output change and rebuilds forever.
Excluding them only stops the *watcher* from reacting; Jekyll loads `_data`
files without consulting `exclude`, so everything that reads them keeps working.
Leave them excluded — and remember that `_config.yml` itself is read once, at
startup, so restart the server after editing it.

Most pages are Markdown (kramdown); a handful that lay out their own visual
bands, like the homepage, are plain `.html`. Front matter, permalink conventions
and link style are all covered in
[Writing and previewing documentation](/documentation/version_control/documentation).

## What generates the site

A large part of formulize.org is not written by hand. Several processes feed it —
mostly Jekyll plugins that run on every build, plus one step that runs only in
CI — and they are why the site can describe the live application, the current
roadmap and the latest news without anyone editing a Markdown file.

| Process | Runs | Produces |
|---|---|---|
| `_plugins/news_pages.rb` | every build | the whole `/news/` section |
| `_plugins/mcp_tool_pages.rb` | every build | one page per MCP tool |
| `_plugins/mcp_item_extractor.rb` | every build | MCP resources and prompts |
| Roadmap fetch in `jekyll.yml` | CI only | the roadmap release sections |
| `_plugins/copy_writable_folders.rb` | every build | the writable folders list |
| `_plugins/breadcrumbs.rb` | every build | the breadcrumb trail on every page |

Plugins in `docs/_plugins/` run simply because they are files in that folder.
Only `breadcrumbs` is named in the `plugins:` list, alongside the two gem-based
plugins.

### News

`_plugins/news_pages.rb` builds the entire news section at build time from the
News form on formulize.net, read through the Public API. It generates a page per
story at `/news/<slug>/`, a stable `/news/entry/<id>/` redirect for each one,
the `/news/` index, `/news/releases/`, and an RSS feed at `/news/feed.xml`. It
also hands the stories to `site.data.news_stories`, which is what the news band
on the homepage reads.

It is build-time rather than browser-side on purpose. The previous version
fetched the API from JavaScript on every visit, which meant search engines
indexed an empty page, there were no per-story URLs to link to or share, and
there was no feed. The cost of generating the pages instead is that a story
reaches the site on a rebuild rather than on save — which is exactly why saving
a story triggers a rebuild (see [below](#how-it-deploys)).

There are no Jekyll `_posts` anywhere on this site, which is why
`jekyll-paginate` and `jekyll-feed` are deliberately absent: both only ever
worked on posts.

Every successful fetch is cached to `_data/news.json`, and a fetch that fails
all three attempts falls back to that cache. Building with
`FORMULIZE_NEWS_OFFLINE=1` skips the network entirely and builds from the cache
— useful on a plane, or when formulize.net is unreachable.

### MCP tool pages

The [AI reference](/ai/mcp-reference/) documents every MCP tool, one page each
under `/ai/mcp-reference/tools/<name>/`, generated by
`_plugins/mcp_tool_pages.rb` from `docs/_data/mcp_tools.json`.

That JSON is a real dump of `registerTools()`'s output, produced by
`mcp/dump_tools_for_docs.php` against a running copy of Formulize. It is a dump
rather than a scrape because most tool schemas cannot be reconstructed from the
PHP source text: they are assembled at runtime from variables and function
calls, and the create/update element tools are built entirely at runtime by
`buildFormElementTools()`. Asking the application itself is the only way to get
what it actually exposes.

The plugin makes two presentation decisions of its own, which is why they live
there rather than in the dump script: three easter-egg tools are skipped, and
the two local MCP-proxy cache tools (`cache_stats`, `cache_refresh`) are
declared by hand, because they belong to the local server that fronts Formulize
and so can never appear in a dump of `tools.php`. The pages are nested under
`/ai/mcp-reference/` so that page is a genuine URL ancestor of every tool page,
which is what gets it to appear as an intermediate breadcrumb.

### MCP resources and prompts

`_plugins/mcp_item_extractor.rb` handles the other half of the MCP reference. It
reads `mcp/resources.php` and `mcp/prompts.php` out of the repository and
scrapes their definitions into `site.data.mcp_items`. Scraping is fine here,
unlike for tools, because resource and prompt schemas are static text in the
source. It runs at high priority so its data is ready before the pages that
render it.

### Roadmap

The [roadmap](/documentation/roadmap) is the one feed that is not a plugin: it
is a step in the deploy workflow, because it needs a GitHub API token. It queries
the repository for every **open** milestone whose title is a plain version
number (8.3, 9.0, 10.0 …), collects the issues and pull requests in each one
labelled `Marquee Feature`, sorts the versions numerically, and writes
`_data/roadmap_issues.json`.

Nothing about that list is hardcoded, so closing a milestone on GitHub drops it
from the roadmap on the next deploy and opening one adds it, with no edit to the
workflow. Milestones whose titles are not version numbers (Backlog, Deep
Backlog) are ignored. Because this runs only in CI, a local build has no roadmap
data, and the roadmap page renders with no release sections.

### Writable folders

`_plugins/copy_writable_folders.rb` copies the canonical
`writableFilesAndFolders.json` from the repository root into `_data/`, so the
installation docs can list the folders that need to be writable without that
list being maintained in two places.

### Breadcrumbs

`_plugins/breadcrumbs.rb` builds the trail on each page by walking its URL
segments and matching each one against a real page on the site. Being purely
URL-based has a practical consequence when adding pages: a page shows up as a
breadcrumb ancestor only if a real page exists at that URL. A missing
intermediate page means a missing crumb.

### Everything in `_data/` is generated

`docs/_data/` is gitignored in full — nothing in it is committed. Every file
there is produced during the build: `news.json` by the news plugin,
`mcp_tools.json` by the dump step, `roadmap_issues.json` by the roadmap step,
`writableFilesAndFolders.json` by the copy plugin.

The practical consequence is that CI always starts cold, with no previous copy of
anything to fall back on. Each feed is written to degrade instead of failing the
deploy: news retries and then falls back to its cache (cold in CI, so the
retries are what matter there); a failed roadmap fetch publishes a roadmap with
no release sections and logs a warning; a failed tool dump means the tool pages
are not generated for that build, and the plugin logs an error. In every case the
site still builds and deploys. Slightly incomplete beats broken.

## How it deploys

Deployment is automatic. There is no manual publish step — pushing to `master`
is enough. The actual work happens in
[`.github/workflows/jekyll.yml`](https://github.com/jegelstaff/formulize/blob/master/.github/workflows/jekyll.yml),
which runs on:

- every push to `master`,
- a manual run from the **Actions** tab (`workflow_dispatch`), and
- a `repository_dispatch` of type `formulize-news-updated`, fired by the News
  form on formulize.net whenever a story is saved — publishing a news story
  therefore triggers its own rebuild, without anyone needing to touch the
  repository. If that hook is ever broken or its token expires, nothing here
  breaks: run the workflow by hand from the Actions tab and the site catches
  back up.

The build job does rather more than run Jekyll, because two of the feeds above
need something the repository alone cannot provide:

1. Check out the repository and set up Ruby, installing the gems from
   `docs/Gemfile`.
2. Fetch the roadmap from the GitHub API.
3. Start Formulize in Docker and install it using the e2e suite's own install
   test — a fresh checkout's database has no schema until the installer runs,
   because the developer-local seed SQL is gitignored, so CI's database boots
   genuinely empty.
4. Dump the MCP tool schemas out of that running application, retrying a few
   times (a cold container can restart once shortly after coming up), then shut
   it back down.
5. Build the site, with `JEKYLL_ENV=production`.

Every step in that Formulize-in-Docker sequence is `continue-on-error`, and the
dump itself falls through to a warning, so an unrelated problem booting the
application cannot block a documentation deploy.

The build is the same command you would run locally:

```bash
bundle exec jekyll build
```

Run it through `bundle exec`, here and locally, so the versions pinned in
`docs/Gemfile.lock` are what build the site — including the `github-pages` gem,
which tracks what GitHub Pages itself actually runs.

The result is uploaded and published with GitHub's own `actions/deploy-pages`
action — nothing formulize-specific there. Deploys are serialised through a
`pages` concurrency group, and a run already in progress is never cancelled.

The workflow file is the source of truth for the exact steps; this page just
orients you to what it is doing and why.
