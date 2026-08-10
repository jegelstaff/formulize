# Testing

## PHP tests

Plain PHP scripts in this folder, run with `php tests/<name>.php`. They exit non-zero on failure and are
run by CI. There are two kinds, and the difference is when they run:

**Standalone logic tests** need nothing but PHP - they load the code under test directly and check its
behaviour. CI runs them *before* the e2e suite, since they have nothing to wait for.

* `password_hashing_test.php`
* `reference_binding_test.php`
* `normalize_then_escape_test.php`
* `date_search_test.php`
* `empty_set_search_test.php`

**Integrity tests against a built system** boot the application and read the live database, so CI runs
them *after* the e2e suite, when the setup specs have finished building the museum system.

* `element_reference_integrity_test.php` - what deleting an element would disturb, and what deleting it
  actually changes, across every screen, element, saved view and form on the system. Where the museum
  system has no example of a given kind of reference (map screens, calendars, saved views with searches
  and calculations, dynamic default values), it builds fixtures and cleans them up for real, inside a
  transaction it rolls back. It asserts invariants rather than counts, so adding screens or elements to
  the setup suite does not break it.
* `saved_views_test.php` - a saved view written, read back by id and by name, changed and deleted through
  `formulizeSavedViewsHandler`. Saved views hold several lists tied to each other only by position, and
  names people typed, so the checks are that everything survives the trip verbatim and in step. Also
  transactional.

To run one against a local docker environment:

```
docker exec formulize-web-1 php /var/www/html/tests/element_reference_integrity_test.php
```

## End to end (e2e) testings

We use the [Playwright](https://playwright.dev/) framework for configuring and executing e2e tests across browsers.

Core formulize e2e test are located in the `/tests/e2e/formulize-core` directory.

When writing your own tests for your site/application place them in an appropriately named directory inside `/tests/e2e`.

### Running tests in CI

e2e tests are automatically run in CI with a merge to the master branch. The tests are performed against new local running instance of the formulize application that is running in CI via Docker.

* Configuration files the github actions are located in the `.github/workflows/e2e-test.yml` file.
* If you are performing a small merge to master and don't want the tests to execute then add `[SKIP TEST]` to your commit message.

### Running core tests locally

#### Requirements
* Node.js (18+)
* Docker (optional)

#### Steps
1. Get a local instance of the application set up and running in your browser (`http://localhost:8080` by default). We recommend using the included docker-compose file to quickly spin up an environment using docker.
2. Navigate to the tests folder `/tests/e2e` and perform an `npm install`
3. Install playwright browsers `npx playwright install --with-deps`
4. While still in the `/tests/e2e` directory run the test with `npm t` This will run the tests in headless mode. If you'd like to have the browser load to watch the progress use `npm run test:debug`.
