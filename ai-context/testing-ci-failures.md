# Diagnosing a failed CI run

For how CI runs the suite, see [Concurrency](testing-robust-specs.md#concurrency) and [docs/ci.md](../docs/ci.md).

## What you can get at

- **Failure annotations are public.** `GET /repos/jegelstaff/formulize/commits/{sha}/check-runs` lists the checks. The annotations URL of the failed one has the Playwright error and call log for the test that failed. The same API walks back through runs, to find the last good one and where each one stopped.
- **Job logs and artifacts need admin rights.** Ask a maintainer to download them.
- **`playwright-report`** has the HTML report and the traces.
- **`final-state`** has the database dump, `logs/`, the `code/` folder and the screen templates, as they were at the end of the run. `logs/formulize_log_active.log` gives a timeline of every request, and it can show that a click never produced a request.

## Reading the evidence

**Look at the trace before theorizing.** A trace is a zip with `0-trace.trace` inside, in JSON lines. Scan it for `pageError` and console events. A JavaScript error in the page is often the real cause of a failure that looks like a timing problem, and it's quicker to find than to reason out. `0-trace.network` has the response bodies, which helps with [masked admin save errors](admin-save-error-masking.md).

**Read a Playwright call log carefully.** A bare `waiting for <locator>` with no `locator resolved to` line means the locator matched nothing. A hidden element shows `resolved to` and then `element is not visible`.

**`page.goto: net::ERR_ABORTED` next to a test timeout means the navigation hung.** Teardown aborted it. In the trace, the `goto` has a `before` and no `after` until the After Hooks.

**A red run can hide what's behind it.** With `maxFailures: 1`, a failing spec stops everything after it, so breakage in a later spec can stay hidden for days. When a later spec suddenly fails, find the last run that reached it, not the last commit.

**Consecutive red runs may be different failures.** Compare their annotations. A downloaded report belongs to the run it came from, so check that it's the one you're looking at.

## Common causes

**A form drawn read-only.** There are many possible causes, and one is an entry lock left by an earlier test. Look for the lock message in the page's HTML in the trace, and at "Entry locks not released" in the run's summary, which lists the pages tests left whose release never arrived. Subform entries lock too, not just the main entry. To find which test left it, search `logs/formulize_log_active.log` for `rendering-form` events for that entry, and see which session drew it last. See [Entry locks](testing-robust-specs.md#entry-locks).

**CI fails and live sites work.** Suspect a static file that live browsers have cached, while CI's fresh browser gets the new one. A script loaded with no `?v=` can stay cached for weeks. Load scripts with `formulize_get_file_version()`.

**One bad ready handler.** A jQuery error inside a `jQuery(document).ready()` aborts every later ready handler on the page, so the symptom appears far from the cause. An `.on()` in the admin UI is the usual culprit, because the [admin runs jQuery 1.4.2](coding-guidelines.md#javascript-in-admin-templates).

**An id that changed.** Admin `#form-N` and `#tabs-N` ids are tab positions, so adding a tab renumbers them. Select by field name.
