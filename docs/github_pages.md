---
layout: default
permalink: documentation/github_pages/
redirect_from:
 - developers/github_pages/
title: Github Pages
---

# GitHub Pages and Jekyll configuration

[formulize.org](https://www.formulize.org/) is a Jekyll site kept in the
`/docs/` folder of the main [formulize](https://github.com/jegelstaff/formulize)
repository, and published through GitHub Pages. To write or preview a page
yourself, see [Writing and previewing documentation](/documentation/version_control/documentation)
— this page is about how the site is configured and how it actually gets
deployed.

## Configuration

The whole site is driven by `docs/_config.yml`:

```yaml
title: Improvise. Organize. Customize.
markdown: kramdown
baseurl: ""
url: https://formulize.org
permalink: /:title/
repository: jegelstaff/formulize
```

`baseurl` is empty and `permalink` has no repository name in it, because the
site is served from its own domain (formulize.org) rather than from a
`github.io/<repo>/` path — so URLs and links across the site are written as
plain absolute paths (`/features/`, `/documentation/classes`), not prefixed with
anything.

Most pages are Markdown (kramdown); a handful that lay out their own visual
bands, like the homepage, are plain `.html`. Both are covered in
[Writing and previewing documentation](/documentation/version_control/documentation).

## How it deploys

Deployment is automatic. There is no separate `gh-pages` branch and no manual
publish step — pushing to `master` is enough. The actual work happens in
[`.github/workflows/jekyll.yml`](https://github.com/jegelstaff/formulize/blob/master/.github/workflows/jekyll.yml),
which runs on:

- every push to `master`,
- a manual run from the **Actions** tab (`workflow_dispatch`), and
- a `repository_dispatch` fired by the News form on formulize.net whenever a
  story is saved — publishing a news story therefore triggers its own
  rebuild, without anyone needing to touch the repository. If that hook is
  ever broken, running the workflow by hand from the Actions tab catches the
  site back up.

Besides building the Jekyll site, that workflow also fetches the current
roadmap from the GitHub API, and briefly boots Formulize itself inside Docker
so it can dump real MCP tool schemas for the [AI reference pages](/ai/mcp-reference/)
— both fall back to their last committed values in `docs/_data/` if that step
fails, so a CI hiccup in either one does not block the docs deploy. The build
itself is the same command you would run locally:

```bash
bundle exec jekyll build
```

The result is uploaded and published with GitHub's own `actions/deploy-pages`
action — nothing formulize-specific there. The workflow file is the source of
truth for the exact steps; this page just orients you to what it is doing and
why.
