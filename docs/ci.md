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

### Where the suite runs

Every run tests the whole suite in five environments at the same time. Each one is a job of its own, with its own runner, containers and database, so they can't get in each other's way, and a run takes about as long as one of them.

| Environment | Database | Browser | Theme | PHP tests | Decides the run |
|---|---|---|---|---|---|
| `mariadb-chromium-anari` | MariaDB | Chromium | Anari | Yes | Yes |
| `mysql-chromium-anari` | MySQL 8.4 | Chromium | Anari | Yes | Not yet |
| `mariadb-firefox-anari` | MariaDB | Firefox | Anari | No | Not yet |
| `mariadb-webkit-anari` | MariaDB | WebKit | Anari | No | Not yet |
| `mariadb-chromium-lyris` | MariaDB | Chromium | Lyris | No | Not yet |

A difference between databases doesn't depend on the browser, and a difference between browsers doesn't depend on the database, so the other browsers run on MariaDB only. The PHP tests depend on the database and nothing else, so they run once on each. The Lyris job runs the same specs as the first one, except that the site stays on Lyris after the install, so the museum system is built with Lyris's screen settings: its lists open entries in the drawer, show 20 entries a page, and keep their search boxes hidden until they're opened.

Only `mariadb-chromium-anari` decides whether the run passes. The others are allowed to fail while they're new, so a red mark on one of them sits under a green run. Look at each job in the Actions tab. Once an environment passes first time, every time, set its `decides` to `true` in the workflow's list of environments.

To run some of the environments by hand, choose one under **environments**, or `all`. From another workflow, pass ids separated by commas.

WebKit here is Playwright's own build on Linux, not Safari, so a pass there isn't proof that Safari works.

### What each job does

In order:

1. Checks out the repository and sets up Node 20.
2. Sets the file and directory permissions Formulize needs to be writable.
3. Brings up the application with `docker compose up -d --build` — a real Apache, PHP and MariaDB, exactly the same [Docker setup](../deploying_locally) used for local development. The MySQL job adds `docker-compose.mysql.yaml`, which runs MySQL 8.4 in place of MariaDB. The database starts genuinely empty; nothing is seeded.
4. Runs the **standalone PHP logic tests** — password hashing, `{element_handle}` reference binding, normalize-then-escape form sinks, `EMPTYSET` searches and search-term prefixes, partial date searches. These need nothing but PHP, so they run first and fail fast. Only the two Chromium, Anari jobs run them, one on each database.
5. Installs the Playwright dependencies and the job's browser.
6. Runs the **setup specs** — `npx playwright test formulize-core/01-setup --workers=1`. These install Formulize from scratch and build the museum sample system. One worker, in file-name order, because each depends on the last.
7. Runs the **validate specs** — `npx playwright test formulize-core/02-validate --workers=4 --fully-parallel`. These interrogate the system the setup specs built. This step is why a spec whose tests depend on each other has to say `test.describe.configure({ mode: 'serial' })` — see [Creating Tests](../version_control/testing/creating_tests).
8. Runs the **sequential validate specs** - `npx playwright test formulize-core/03-validate-sequential --workers=1`. These change things the museum system shares, such as element handles, check that what depends on them still works, and put them back, so they run after the validate specs, on one worker.
9. Runs the **appearance specs** — `npx playwright test formulize-core/04-appearance --workers=1`. These test the Lyris theme's looks and the appearance editor, switching the whole site to Lyris and back, so they run after the validate specs, on one worker, not alongside anything else.
10. Runs the **PHP integrity tests** — element reference scan, saved views round trip, element code files. These need the finished system, so they come last. Like the logic tests, they run only in the two Chromium, Anari jobs.
11. Dumps the database and collects the files the run produced, then tears the containers down.

Steps 7 to 9 are skipped when the manual run is given a test filter, because a partly built system is not the system they are asking about.

In `mariadb-chromium-anari`, the first failure stops the job, and the steps after it are skipped. The other jobs go on as far as they can, so one run shows more of what's wrong in an environment that's new. Once their setup specs pass, every later step runs, even after one that failed, and the validate specs in step 7 run to the end rather than stopping at their first failure. Their setup, sequential and appearance specs still stop at their first failure, because each of those specs builds on or changes what the ones after it use.

A few tests use something only one browser has, such as emulating a phone or clearing the browser's cache, and they're skipped in the others, with the reason in the report.

### Skipping a run

Include `[SKIP TEST]` (or `[SKIP TESTS]`) in a commit message to skip the suite for that push. It is checked against every commit message in the push, so it is for small merges to master that genuinely cannot affect behaviour — documentation, comments — not for getting a red build through.

### Reading the results

Every job uploads two artifacts, kept for seven days, named for its environment, such as `playwright-report-mariadb-chromium-lyris`:

* **playwright-report-&lt;environment&gt;** — the HTML reports, one folder for each set of specs that ran: `01-setup`, `02-validate`, `03-validate-sequential` and `04-appearance`, or `filtered` for a run given a test filter. Download it and open `index.html` in a folder, or run `npx playwright show-report` against the folder. Failures carry a trace and a video, because `playwright.config.js` sets `trace: 'retain-on-failure'` and `video: 'retain-on-failure'`. The trace viewer shows the DOM, the network and the console at each step of the failing test.
* **final-state-&lt;environment&gt;** — the state of the system when the run ended: a database dump, `mainfile.php`, `install.lock`, and the contents of `trust/`, `logs/`, `modules/formulize/code/` and `modules/formulize/templates/screens/`. This is collected `if: always()`, so it is there for a failed run too, which is when it is worth having: you can restore the dump locally and look at the system the test was actually standing in.

The run also uses a PHP error reporter (`tests/e2e/php-error-reporter.js`), so PHP notices and warnings raised during the tests surface in the report rather than only in the container log.

Because the suite runs on `master`, a red build means master is broken, and that is what the badge on the repository readme is showing.

### Measuring coverage

Run the workflow by hand with **coverage** ticked to find out which lines of PHP the suite runs. It counts every PHP test and every request the Playwright specs make, so the numbers are for the whole suite. Only the `mariadb-chromium-anari` job measures it, since one report is enough. The option is in the workflow on branches that have `tests/coverage/`, so choose one of those under **Use workflow from**, as well as the branch to test.

The run's summary page shows:

* the share of the lines that can run that the suite ran, by folder
* what each set of tests ran, and what no other set ran
* the files with the most lines that didn't run
* the lines that differ from where the branch left **coverage_base**, `master` unless you change it, that can run, and that no test ran. This is the list to check before merging a branch: a changed line on it isn't tested by anything.

The **coverage-report** artifact has those as Markdown, along with `files.md`, every file and its share, `coverage.json`, which has the lines for each file and set of tests, and `lcov.info`. Editors can show `lcov.info` beside the code. In VS Code, put it in the root of the repository and use the Coverage Gutters extension.

A coverage run is slower, because PHP runs more slowly while it's measured, so a spec close to its time limit can fail in a coverage run when it passes otherwise. Use the ordinary run to decide whether a branch passes.

A line counts as run if any request ran it. That doesn't mean a test checked what the line did.

How it works is in `tests/coverage/`. `prepend.php` runs before every request and records the lines it ran, and `merge.php` makes the report.

## Deploy Jekyll site to Pages

**`jekyll.yml`** — builds this documentation site and deploys it to GitHub Pages. It runs on pushes to `master`, can be run by hand, and is also fired by Formulize itself: the News form on formulize.net POSTs a `repository_dispatch` with the type `formulize-news-updated` when a story is saved, because news is baked into the site at build time and only reaches it on a rebuild.

Two parts of the build are not just Jekyll:

* **The roadmap** is fetched live from the GitHub API. Every open milestone whose title is a plain version number becomes a section on the [roadmap](../roadmap), ordered by version, with its Marquee Feature issues under it. Closing a milestone on GitHub drops it from the roadmap on the next deploy; opening one adds it. Nothing about the roadmap is hardcoded in the workflow.
* **The MCP tool reference** is dumped from a real running copy of Formulize. The workflow brings up Docker, runs the install spec from the e2e suite to get an installed system, and then runs `mcp/dump_tools_for_docs.php` against it, so the [tool reference](../../ai/mcp-reference/) shows actual resolved schemas — including the tools whose schemas are built entirely at runtime and cannot be read as static text.

If any part of the site can't be built, such as the news, the roadmap or the tool reference, the build fails and nothing is deployed, so the site stays as it was. See [An incomplete site is a failed build](../version_control/documentation/github_pages#an-incomplete-site-is-a-failed-build).

See [Writing and previewing documentation](../version_control/documentation) for how to work on the site, and [GitHub Pages config](../version_control/documentation/github_pages) for how it is hosted.

## Publish PHP Development Docker image

**`build-docker-dev-image.yaml`** — rebuilds and pushes the `formulize/php-dev` image to Docker Hub. It runs only when `docker/php/Dockerfile` or the workflow itself changes, and builds for both `linux/amd64` and `linux/arm64` so the development environment works on Apple Silicon as well as Intel and AMD machines.

This is the image `docker-compose.yaml` pulls, so a change to it reaches every developer's environment the next time they rebuild.

## Related

* [Testing](../version_control/testing) — what the test suites are and how to run them locally
* [Creating Tests](../version_control/testing/creating_tests) — writing a new Playwright spec
* [Local development environment](../deploying_locally) — the Docker setup CI uses
