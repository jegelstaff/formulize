# Test the application by using it

To test Formulize, use Formulize. What a person does goes through the page, the POST, `readelements.php`, the handlers and the display functions, and any of those can be what's broken. A row written straight into the database skips all of that. A test built on that row shows that some code can read it. It doesn't show that a person can create it, or that the application would have stored it that way, so it isn't a valid test of the application.

This applies to Playwright specs, and to checking your own change by hand or with a script.

## What to do

- **Set up through the application.** Create forms, elements, screens, users, groups, permissions and entries in the admin UI and on screens, the way a person would. The APIs that a user or another system can call, like the Public API and MCP, are part of the application too.
- **Tidy up through the application.** Delete what you made, and put settings back, the same way.
- **Read directly to check a result.** Reading the database or a file to confirm what the application stored is fine. That's an assertion, not a shortcut.
- **Read directly for what a browser can't reach.** A person gets a 2FA or signup confirmation code by email or SMS, and the test containers can't send either. So a spec reads the code from the database and types it in, the way a person would type it from their inbox. The application still does everything else.
- **Prefer to check what a person sees.** Then check the stored value too, if the page can't show it.

## What not to do

- Don't write to the database to get a state, with `INSERT`, `UPDATE` or `DELETE`, through `dbQuery()` or `docker exec`.
- Don't create, change or delete Formulize's files with a script or a command in a container.
- Don't add code to core that only tests call. If a test can't observe something, find a signal a person would see.
- Don't make a test pass by steering it around something a real person could hit. A test that fails on real behaviour has found something. Ask "would a person see this and wait for it?" Waiting on a saving message is fair. Blocking or forcing browser behaviour is not.

Some specs still go around the application, with helpers like `setSystemConfig()`. They're due to be changed, so don't copy them.

**If the application seems to have no way to do what you need, ask the user.** The way may exist, and if it doesn't, that may be a missing feature. Don't reach around it.

Restoring a [snapshot](local-environment-and-testing.md#database-snapshots) to get ready for a run is fine, because the application made that state. Don't do it inside a spec.

## Agreed exceptions

These write data directly because Formulize can no longer produce it. Ask the user before adding another.

- `01-setup/002` stores an old SHA256 password hash, to test that login upgrades it.
- `02-validate/039` stores raw XSS payloads, like data saved before purification.
- `03-validate-sequential/020` writes handles into settings the way old versions stored them.

## PHP tests are a different thing

The PHP tests in `tests/` check that internal pieces work as expected, more like unit tests. See [tests/README.md](../tests/README.md). Some load code directly and use a stand-in for the database. Others boot the application and write fixtures inside a transaction that's rolled back.

That's fine for what they're for, such as logic with many cases that would be slow through a browser, or something that must hold across every screen and element on a system. But they don't replace testing the application. A passing PHP test shows that a piece of code gives the expected output for the values it was given, not that a person can do the thing. `tests/saved_views_test.php` is a good example, because its header says what it doesn't cover and leaves that to Playwright.

If a change affects what someone can do in Formulize, exercise it through the application, even if a PHP test also covers it.

## Why

`03-validate-sequential/010` tests renaming an element, and it once wrote a list screen's template straight to disk. Locally that passed, because anyone can write to a Windows mount. On GitHub the web server couldn't write to that file, so the rename failed silently. The shortcut had produced a state no user can produce, and the failure was about the test. It also hid a real gap: a rename didn't report files it couldn't write.

For writing specs, see [Creating tests](../docs/creating_tests.md) and [testing-robust-specs](testing-robust-specs.md).
