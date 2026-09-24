---
layout: default
permalink: documentation/version_control/documentation/
redirect_from:
 - developers/version_control/documentation/
title: Writing and Previewing Documentation
---

# Writing and previewing documentation

All of the documentation for Formulize — including this page, and the whole
of [formulize.org](https://www.formulize.org/) — is a Jekyll site kept in the
`/docs/` folder of the main repository. There is no separate repository and no
`formulize-docs/` folder; everything lives alongside the application code, so
a documentation change can be part of the same commit and the same pull
request as the code it explains.

## Running it locally

You need [Ruby](https://www.ruby-lang.org/) and
[Bundler](https://bundler.io/) installed. From there:

```bash
cd docs
bundle install
bundle exec jekyll serve
```

Then browse to [http://localhost:4000](http://localhost:4000).

### Live updates while you write

`jekyll serve` already watches the `/docs/` folder and rebuilds as you save —
watching is part of `serve`, so there is no `--watch` flag to add. Save a file,
refresh the browser, and the change is there.

To skip the refresh as well, add `--livereload`:

```bash
bundle exec jekyll serve --livereload
```

That injects a small script into every page which reloads the tab itself once
the rebuild finishes, so the browser follows along beside the editor.

Three things the watcher will not pick up:

- **Changes to `_config.yml`.** Jekyll reads it once, at startup. Stop the
  server and start it again after editing it.
- **Anything a plugin generates on each build.** `_plugins/news_pages.rb`
  rewrites `_data/news.json` and `_plugins/copy_writable_folders.rb` rewrites
  `_data/writableFilesAndFolders.json` every time the site is built. Both are
  listed under `exclude:` in `_config.yml` for exactly that reason — without
  it the watcher sees its own output change and rebuilds forever. Leave them
  excluded.
- **Changes to the Formulize UI catalog.** The
  [Formulize UI](/documentation/formulize_ui/) pages are generated from
  `modules/formulize/include/ui_catalog.php`, which is outside `docs/`.
  Restart the server to see a change to it. Generating them needs PHP, or the
  local development environment running; see
  [GitHub Pages config](/documentation/version_control/documentation/github_pages).

### Always use `bundle exec`

Run `jekyll serve` through `bundle exec`, not on its own. `docs/Gemfile.lock`
pins exact versions of Jekyll and every plugin — including the
`github-pages` gem, which tracks what GitHub Pages itself actually runs — so
that what you see locally matches what gets deployed. Running plain
`jekyll serve` instead uses whatever version of Jekyll happens to be
installed globally on your machine, which is often different, and can render
pages, especially ones using newer or older plugin features, differently than
the live site does.

### Previewing on another device

To check a page on a phone or tablet on the same network, bind the server to
every network interface instead of only this computer:

```bash
bundle exec jekyll serve --host 0.0.0.0
```

Then, on the other device, browse to `http://<this-computer's-LAN-IP>:4000`.
Find that IP with `ipconfig` on Windows, or `ifconfig`/`ip addr` on macOS and
Linux — look for the address on your Wi-Fi or Ethernet adapter, not a
`127.x.x.x` or `169.254.x.x` one. The two devices need to be on the same
network. Windows may prompt to allow Ruby through the firewall the first
time you connect from another device — allow it for private networks.

## Writing a page

Most pages are plain Markdown (kramdown), which is what makes them quick to
write and review. A few pages that lay out their own visual bands, like the
homepage, are `.html` instead — you will know which you need by looking at
the page you are closest to.

Every page starts with front matter like this:

```yaml
---
layout: default
permalink: documentation/some-page/
title: Some Page
---
```

- **layout** is almost always `default`.
- **permalink** is the page's URL, written *without* a leading slash and
  *with* a trailing one.
- **title** shows up in the browser tab and in any auto-generated
  breadcrumbs or link listings.

Link to other pages with standard Markdown syntax, using the permalink as the
destination — but here, unlike in the front matter above, *with* a leading
slash and *without* a trailing one:

```markdown
[Link text](/documentation/some-page)
```

Relative links (`../some-other-page`) also work, resolved the normal way a
browser resolves them — against the current page's own URL, not its file
path in `/docs/`.

## Where things fit together

- [GitHub Pages and Jekyll configuration](/documentation/version_control/documentation/github_pages) covers
  `_config.yml`, the plugins and CI steps that generate the news, AI reference
  and roadmap pages, and how the site actually gets deployed.
- The [Documentation](/documentation/) hub links out to the rest of what a
  contributor is likely to need — version control, git tips, testing, the
  roadmap.
