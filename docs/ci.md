---
layout: default
permalink: documentation/ci/
redirect_from:
 - developers/ci/
title: Continuous Integration
---

# Continuous Integration

Formulize runs its continuous integration on [GitHub Actions](https://github.com/jegelstaff/formulize/actions). The workflow files are in `.github/workflows/` in the repository, and there is nothing to sign up for or configure outside it — no external CI service, no credentials, no per-fork setup. A fork gets the same builds its parent does.

There are four workflows.

## e2e test suite

**`e2e-test.yml`** — runs on every push to `master`. It is a thin trigger; the work is in `e2e-test-manual.yml`, which it calls.

**`e2e-test-manual.yml`** — the actual suite. It can also be run by hand from the Actions tab (**Manual E2E Test Suite**), against any branch, which is how you check a branch before merging it.

What a run does, in order:

1. Checks out the repository and sets up Node 20.
2. Sets the file and directory permissions Formulize needs to be writable.
3. Brings up the application with `docker compose up -d --build` — a real Apache, PHP and MariaDB, exactly the same [Docker setup](../deploying_locally) used for local development. The database starts genuinely empty; nothing is seeded.
4. Runs the **standalone PHP logic tests** — password hashing, `{element_handle}` reference binding, normalize-then-escape form sinks, `EMPTYSET` searches and search-term prefixes, partial date searches. These need nothing but PHP, so they run first and fail fast.
5. Installs the Playwright dependencies and the Chromium browser.
6. Runs the **setup specs** — `npx playwright test formulize-core/setup --workers=1`. These install Formulize from scratch and build the museum sample system. One worker, in file-name order, because each depends on the last.
7. Runs the **validate specs** — `npx playwright test formulize-core/validate --workers=4 --fully-parallel`. These interrogate the system the setup specs built. This step is why a spec whose tests depend on each other has to say `test.describe.configure({ mode: 'serial' })` — see [Creating Tests](../version_control/testing/creating_tests).
8. Runs the **PHP integrity tests** — element reference scan, saved views round trip, element code files. These need the finished system, so they come last.
9. Dumps the database and collects the files the run produced, then tears the containers down.

Steps 7 to 9 are skipped when the manual run is given a test filter, because a partly built system is not the system they are asking about.

### Skipping a run

Include `[SKIP TEST]` (or `[SKIP TESTS]`) in a commit message to skip the suite for that push. It is checked against every commit message in the push, so it is for small merges to master that genuinely cannot affect behaviour — documentation, comments — not for getting a red build through.

### Reading the results

Every run uploads two artifacts, kept for seven days:

* **playwright-report** — the HTML report. Download it and open `index.html`, or run `npx playwright show-report` against it. Failures carry a trace and a video, because `playwright.config.js` sets `trace: 'retain-on-failure'` and `video: 'retain-on-failure'`. The trace viewer shows the DOM, the network and the console at each step of the failing test.
* **final-state** — the state of the system when the run ended: a database dump, `mainfile.php`, `install.lock`, and the contents of `trust/`, `logs/`, `modules/formulize/code/` and `modules/formulize/templates/screens/`. This is collected `if: always()`, so it is there for a failed run too, which is when it is worth having: you can restore the dump locally and look at the system the test was actually standing in.

The run also uses a PHP error reporter (`tests/e2e/php-error-reporter.js`), so PHP notices and warnings raised during the tests surface in the report rather than only in the container log.

Because the suite runs on `master`, a red build means master is broken, and that is what the badge on the repository readme is showing.

## Deploy Jekyll site to Pages

**`jekyll.yml`** — builds this documentation site and deploys it to GitHub Pages. It runs on pushes to `master`, can be run by hand, and is also fired by Formulize itself: the News form on formulize.net POSTs a `repository_dispatch` with the type `formulize-news-updated` when a story is saved, because news is baked into the site at build time and only reaches it on a rebuild.

Two parts of the build are not just Jekyll:

* **The roadmap** is fetched live from the GitHub API. Every open milestone whose title is a plain version number becomes a section on the [roadmap](../roadmap), ordered by version, with its Marquee Feature issues under it. Closing a milestone on GitHub drops it from the roadmap on the next deploy; opening one adds it. Nothing about the roadmap is hardcoded in the workflow.
* **The MCP tool reference** is dumped from a real running copy of Formulize. The workflow brings up Docker, runs the install spec from the e2e suite to get an installed system, and then runs `mcp/dump_tools_for_docs.php` against it, so the [tool reference](../../ai/mcp-reference/) shows actual resolved schemas — including the tools whose schemas are built entirely at runtime and cannot be read as static text. If any of that fails, the step falls back to the committed `docs/_data/mcp_tools.json` rather than failing the deploy. Slightly stale tool docs beat a broken site.

See [Writing and previewing documentation](../version_control/documentation) for how to work on the site, and [GitHub Pages config](../version_control/documentation/github_pages) for how it is hosted.

## Publish PHP Development Docker image

**`build-docker-dev-image.yaml`** — rebuilds and pushes the `formulize/php-dev` image to Docker Hub. It runs only when `docker/php/Dockerfile` or the workflow itself changes, and builds for both `linux/amd64` and `linux/arm64` so the development environment works on Apple Silicon as well as Intel and AMD machines.

This is the image `docker-compose.yaml` pulls, so a change to it reaches every developer's environment the next time they rebuild.

## Related

* [Testing](../version_control/testing) — what the test suites are and how to run them locally
* [Creating Tests](../version_control/testing/creating_tests) — writing a new Playwright spec
* [Local development environment](../deploying_locally) — the Docker setup CI uses
