# Walkthrough videos

A walkthrough is a Playwright script that records a person's view of a workflow, slowed down, with captions. The library is `tests/e2e/walkthroughs/walkthrough.js`, and [docs/recording_walkthroughs.md](../docs/recording_walkthroughs.md) covers how to run and write one. This note covers how to make one well, and what goes wrong.

## Where they live

- **Formulize's own walkthroughs** go in `tests/e2e/walkthroughs/<system>/`, such as `museum/`. They run against a system the repository can build, so others can record them.
- **A client's or a site's walkthroughs stay out of the repository,** with their videos. They import the library by its full path and run with `WALKTHROUGHS_DIR` and `WALKTHROUGH_OUTPUT`. The library re-exports `test` and `expect` so a file outside `tests/e2e` never imports `@playwright/test` itself. A second copy of Playwright fails with "Requiring @playwright/test second time".
- **They aren't tests.** The main config only collects `*.spec.js`, so `npm test` and CI never run them. Don't add them to CI: they take minutes each on purpose.

## How to make one

1. **Storyboard first:** who signs in, each click, and each caption. Write captions for the people who'll use the application, in its terms, as in [writing-user-docs](writing-user-docs.md#who-the-reader-is).
2. **Explore before writing.** A throwaway script that screenshots a page and lists its visible buttons and fields finds reliable selectors faster than guessing.
3. **Rehearse on practice data.** Most workflows can't be done twice. Take names and dates from environment variables, rehearse against practice entries, and delete those in the application afterwards. Derived values that count entries may need recomputing after the deletions.
4. **Take a dump, record the real take, and check the data.** Assert each step's result with `expect`, so a broken workflow fails the recording instead of producing a misleading video. A failed one is saved with `-failed` in its name.
5. **Follow [testing-through-the-application](testing-through-the-application.md).** Everything happens through the screens, as it would for a person.

## Traps

- **A form in the Lyris drawer only installs its real save address and token on the first `focusin`, `click` or `touchstart`.** A script that only calls `selectOption()` changes the field, and the save then goes nowhere. `pick()` focuses the list first. See [drawer](drawer.md).
- **Save and Close in the drawer sends nothing when nothing has changed,** so there's no request to wait for. `save()` waits for the drawer to move on instead.
- **Leaving a form by `page.goto()` or a bare link click can leave its entry locked,** and a later step finds it read-only. `menu()` and `go()` leave with `leavePage()`. `menu()` shows the click without making it, because clicking and then navigating again fails with "page is navigating". See [Entry locks](testing-robust-specs.md#entry-locks).
- **Subforms differ in what Add One does.** In the drawer, a subform shown as a list opens the new entry in a sub-frame with a Back button, while one with editable rows adds a row in place. `addSubformEntry()` waits for the new entry's fields either way.
- **The ending of a workflow can take its row off a list,** such as an entry that no longer needs attention. Assert that it's gone, not that it's there.
- **Captions aren't Formulize's words.** They belong to the video, like a script, so they don't go in the language files. The overlay draws no fixed text of its own.
- **Subtitle times are measured from when the fixture starts,** a fraction of a second after Playwright starts recording, so the `.srt` can be a little early.
- **The overlay is drawn by `addInitScript()`, and its state is kept in `sessionStorage`,** so it survives page loads. It sits above everything, and captions leave 440px on the right for the drawer.

## Why

The first walkthroughs were six videos of a client's workflows, recorded in November 2026. Most of the time went on finding the traps above in rehearsal, which is why they're in the library and here.
