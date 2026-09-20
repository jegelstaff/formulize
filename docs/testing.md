---
layout: default
permalink: documentation/version_control/testing/
redirect_from:
 - developers/version_control/testing/
title: Testing
---

# Testing

Formulize has two kinds of automated test, both of which live in the repository alongside the code and both of which run on every merge to master:

* **PHP tests** — plain PHP scripts in `tests/`, which load the code under test directly and check its behaviour.
* **End to end (e2e) tests** — [Playwright](https://playwright.dev/) specs in `tests/e2e/`, which drive a real browser against a real running copy of Formulize.

Between them they install Formulize from scratch, build a complete sample application, and then check that it behaves. If they pass, the branch has not broken anything the suite covers.

[Learn how to write a new test](creating_tests). For how the suite is wired into GitHub Actions, see [Continuous Integration](../../ci).

## The museum system

The e2e suite is not a collection of independent checks against a fixture. It builds a system, in order, and then interrogates what it built.

The specs in `tests/e2e/formulize-core/setup/` run first and in sequence. They run the installer, create users and groups, create forms and elements of every type, set permissions, build menus and screens, and enter data. What they produce is the **museum system** — a small collection-management application, with artifacts, donors, collections, exhibits and survey responses.

The specs in `tests/e2e/formulize-core/validate/` run afterwards, and in parallel. They read and exercise the system the setup specs built: that the data is right, that permissions actually isolate one department from another, that derived values update, that the MCP tools answer, that the Public API reads, that user-supplied content cannot inject script into a list.

This is why the two folders are run as two separate steps rather than as one suite. The setup specs depend on each other and on their order; the validate specs depend only on the finished system.

## Running the tests locally

### Requirements

* Node.js 18 or newer
* A running copy of Formulize — the included Docker setup is the easy way to get one

### Getting a system to test against

The e2e suite talks to a copy of Formulize over HTTP, and the setup specs expect to start from an uninstalled one, because the first thing they do is run the installer.

```
docker compose up -d --build
```

By default that publishes the site on `http://localhost:8080`. If you have set `FORMULIZE_WEB_PORT` in the `.env` file at the root of the repository — which is how you run several copies of Formulize side by side — the tests follow it automatically. There is nothing to configure in the test suite itself.

See [Local development environment](../../deploying_locally) for the full walkthrough of the Docker setup.

### Running the e2e suite

Everything below is run from the `tests/e2e` directory.

```
cd tests/e2e
npm install
npx playwright install --with-deps
npm t
```

`npm t` runs the whole suite headless. To watch it work in a real browser window, with the timeout lifted so you can stop and look at things:

```
npm run test:debug
```

To run only the core suite, or only one file, or only one test:

```
npm run test:core
npx playwright test formulize-core/validate/025-validate-data.spec.js
npx playwright test -g "Check the Romain Coin record is complete"
```

Playwright writes an HTML report to `tests/e2e/test-report/`. Open it with `npx playwright show-report test-report`. Failures keep a trace and a video — `trace: 'retain-on-failure'` and `video: 'retain-on-failure'` in `playwright.config.js` — and the trace viewer is usually the fastest way to see what the browser was actually looking at when an assertion failed.

Note that the local config runs with `maxFailures: 1`: the first failure stops the run. That is deliberate. Because the setup specs build on each other, everything after the first failure would be testing a system that was never finished, and a hundred cascading failures tell you less than the first one does.

### Running the PHP tests

These are ordinary PHP scripts. They exit non-zero on failure, which is all CI needs of them. Run one against the Docker environment with:

```
docker exec formulize-web-1 php /var/www/html/tests/password_hashing_test.php
```

There are two kinds, and the difference is what they need to exist first:

**Standalone logic tests** need nothing but PHP. CI runs them before the e2e suite, since they have nothing to wait for.

* `password_hashing_test.php`
* `reference_binding_test.php`
* `normalize_then_escape_test.php`
* `date_search_test.php`
* `empty_set_search_test.php`

**Integrity tests against a built system** boot the application and read the live database, so CI runs them after the e2e suite, once the setup specs have finished building the museum system.

* `element_reference_integrity_test.php` — what deleting an element would disturb, and what deleting it actually changes, across every screen, element, saved view and form on the system.
* `saved_views_test.php` — a saved view written, read back by id and by name, changed and deleted through `formulizeSavedViewsHandler`.
* `element_code_files_test.php` — the code files that content, text, number, textarea and derived elements keep in `modules/formulize/code/`.

The integrity tests build any fixtures they need inside a transaction and roll it back, so running them does not change the system they are looking at.

## Where your own tests go

Core Formulize tests live in `tests/e2e/formulize-core/`. Tests for your own site or application go in their own directory inside `tests/e2e/` — name it for the site — so that they are kept clearly apart from the core suite and are not run by `npm run test:core`.

Your tests belong in the same branch as the code they cover, committed alongside it.
