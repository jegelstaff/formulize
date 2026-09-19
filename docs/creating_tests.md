---
layout: default
permalink: documentation/version_control/testing/creating_tests/
redirect_from:
 - developers/version_control/testing/creating_tests/
title: Creating Tests
---

# Creating Tests

This page is about writing new end to end tests with [Playwright](https://playwright.dev/). For what the test suites are, and how to run them, see [Testing](../). For how they are wired into GitHub Actions, see [Continuous Integration](../../../ci).

## Where the file goes

```
tests/e2e/
  playwright.config.js     the shared configuration
  base-url.js              works out which copy of Formulize to talk to
  utils.js                 the helpers every spec uses
  formulize-core/
    config.js              the admin credentials and base URL for the core suite
    setup/                 build the museum system, in order, one worker
    validate/              interrogate what setup built, in parallel
```

Core Formulize tests go in `formulize-core/setup/` or `formulize-core/validate/`. Tests for your own site or application go in a directory of their own inside `tests/e2e/`, named for the site.

Which of the two core folders a spec belongs in is a question about what it needs, not about what it checks:

* **`setup/`** if it builds something later specs depend on — a form, a user, a permission, a menu. These run in file-name order, on a single worker, and each one assumes everything numbered below it has already happened.
* **`validate/`** if it only reads or exercises the finished system. These run four-way parallel in CI, in no guaranteed order.

File names start with a number, and the number is the running order within the folder. Leave gaps — the existing files go up in fives and ones precisely so a new spec can be slotted between two others without renumbering the suite.

## The shape of a spec

```js
const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from '../config';
import { login, saveFormulizeForm } from '../../utils';

test.describe('Artifacts list', () => {
	test('shows the acquisition date column', async ({ page }) => {
		await login(page, 'curator1', '12345');
		await page.goto('/modules/formulize/index.php?fid=2');
		await expect(page.getByText('Date of acquisition')).toBeVisible();
	});
});
```

Two things to notice. There is no base URL in the file: `playwright.config.js` sets `baseURL` from `base-url.js`, which reads `FORMULIZE_WEB_PORT` out of the repository's `.env` — so `page.goto('/modules/...')` follows whichever port Docker published the site on, and hard-coding `http://localhost:8080` is what breaks a colleague's run. And credentials come from `formulize-core/config.js`, which reads `E2E_TEST_ADMIN_USERNAME` and `E2E_TEST_ADMIN_PASSWORD` from the environment with sensible defaults, rather than being typed into each spec.

## Use the helpers in utils.js

`tests/e2e/utils.js` is where the hard-won knowledge about driving Formulize lives. Reach for it before writing raw Playwright, because most of these helpers exist to work around a race that only shows up under load, and a spec that rolls its own version of one will be the flaky spec in the suite.

The ones you will want first:

| Helper | What it is for |
| --- | --- |
| `login(page, username, password)` | Log in and wait for the redirect into Formulize |
| `saveFormulizeForm(page, buttonText)` | Save a data-entry form: waits for the form token, watches the saving animation appear and disappear, and asserts no "the data you submitted" error |
| `saveAdminForm(page, type)` | The same for the admin UI, which saves differently |
| `waitForWorkingMessage(page)` | Wait out an in-page list action — search, sort, paging, changing view |
| `waitForAdminPageReady(page)` | Wait for the admin UI wrapper, which starts hidden and is revealed on window load |
| `applyColumnChanges(popup)` | Submit the "Change columns" popup, tolerating the fact that the popup destroys itself as a result of the click |
| `addElementForm(page, type)`, `openElementAccordion`, `deleteElement` | Build and edit elements without re-deriving the admin UI's markup |
| `clearEntryLocks(page)` | Release the entry locks this page is holding |
| `dbQuery(sql)`, `getSystemConfig`, `setSystemConfig`, `getUserByLogin` | Read and write the test database directly, through `docker exec`, for the handful of things a browser cannot reach |

`ElementType` in the same file maps every element type to the tab and heading the admin UI shows for it, which is what `addElementForm` uses.

### Direct database access

`dbQuery` and the helpers built on it run `mariadb` inside the Docker container. They exist for things that genuinely cannot be done through the browser — reading the confirmation code for a self-registration, since the container cannot send email, or flipping a system setting that has no convenient UI. They are not a shortcut past the interface. A test that sets up its fixture with SQL and then checks it with SQL has not tested Formulize.

## Things that will bite you

### Entry locks

Formulize locks an entry while someone has it open, so that two people do not overwrite each other. A test that opens an entry and then simply ends still holds that lock, and the next test that wants the same entry — quite possibly in a different spec file, run minutes later — fails with no obvious connection to the test that caused it.

So a test that opens an entry must leave it: navigate away at the end, or call `clearEntryLocks(page)`. Where a whole describe block opens entries, put it in an `afterEach`:

```js
test.afterEach(async ({ page }) => {
	await clearEntryLocks(page);
});
```

Note that `users.php` renders System Users form elements for every user it lists, so merely *listing* users takes locks on all of them.

### CI runs the validate suite four ways parallel

`playwright.config.js` sets `fullyParallel: false` and `workers: 1`, which is what you get locally. **CI overrides both** — the validate step runs `npx playwright test formulize-core/validate --workers=4 --fully-parallel`. A spec whose tests depend on each other therefore passes on your machine and fails in CI, which is the worst way to find out.

If the tests in a file must run in order, in the same worker, say so in the file:

```js
// The second test reads the API key the first one creates, so they must run in
// order, in the same worker. CI runs the validate suite --fully-parallel.
test.describe.configure({ mode: 'serial' });
```

Better still, where you can, make each test set up what it needs so the question does not arise.

### Shared state between validate specs

The validate specs all run against one museum system. If your spec modifies something, check that nothing else touches it, and say so in a comment at the top of the file — that comment is what the next person needs when their unrelated spec starts failing. Prefer building your own form and cleaning it up (`createMuseumForm`, `deleteMuseumForm`) over editing a shared one.

### The first failure stops the run

`maxFailures: 1`. Because the setup specs build on each other, everything after a failure would be testing a half-built system.

## Writing selectors that hold up

Prefer Playwright's role- and label-based locators — `getByRole('button', { name: 'Save' })`, `getByRole('checkbox', { name: 'Height' })` — over CSS paths. They survive markup changes, and they fail with a message that says what was being looked for.

Where a generated id is genuinely the right handle (`#celladdress_2_9` for a particular cell of a list, `td#key-1` for the first API key), use it, but do not derive one from a form id you have hard-coded. `getFidFromListPage(page)` and `getFidFromFormAdminPage(page)` read the real id off the page, because form ids depend on the order the setup suite created things in.

Add a class or a `data-` attribute to the application markup if that is what it takes to make a reliable selector possible. That is a legitimate change to make in the same branch as the test.

## Debugging a failure

Run the one file, headed, with the timeout off:

```
cd tests/e2e
npx playwright test formulize-core/validate/030-mcp-working.spec.js --headed --timeout=0
```

Failures keep a trace and a video. The trace viewer shows the DOM, the network and the console at every step, and is almost always faster than adding `console.log`:

```
npx playwright show-trace test-results/<the-failing-test>/trace.zip
```

The HTML report from the last run is in `test-report/`:

```
npx playwright show-report test-report
```

There is also a PHP error reporter wired into the run (`php-error-reporter.js`), which surfaces PHP notices and warnings the application emitted during the test. A test that passes while printing warnings is worth a second look.

## Committing

Tests go in the same branch and the same pull request as the code they cover. If a test needs a fixture the setup suite does not build, add it to the setup suite rather than building it in the validate spec — and remember that inserting a setup spec changes what every later spec sees.
