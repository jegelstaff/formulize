# Writing Playwright specs that hold up

The rules for how to test are in [testing-through-the-application](testing-through-the-application.md), and the basics of writing a spec are in [docs/creating_tests.md](../docs/creating_tests.md). This note covers why specs go wrong in this suite, and what to do about it. Most helpers in `tests/e2e/utils.js` exist because of something here, so use them before writing raw Playwright.

## The standard: every test passes first try

Race conditions are hidden on some machines and show up on others, and they usually get worse in CI. `retries` is a safety net for rare environmental blips, such as a transient `net::ERR_NETWORK_CHANGED`. It isn't a way to live with a flaky test.

**To find flaky tests,** run a long sequence with `--retries=2`. Playwright marks any test that fails and then passes as "flaky", so one run gives the whole list. With the config's `retries: 0` and `maxFailures: 1`, the run stops at the first one instead. Harden each one with a real readiness wait, and aim for none at `--retries=1`.

Don't run specs repeatedly to build confidence. CI is the real check.

## Playwright against the browser UI

The Formulize admin UI does a lot of asynchronous work: AJAX saves, fades, accordions, token refreshes. Playwright can run its next action, or report a test as passing, before the page has caught up. The click happened and the animation ran, but the data was never written, or was written for only some groups. The test goes green and leaves bad state that surfaces in a later test.

### Saves

- **A green save-type test with no assertion after the save is a latent bug.** Re-navigate to the page, reopen the tab or panel, and assert that the values are there.
- **Never end a test on the save trigger.** If the last thing a test does is click Save, Playwright can tear down the page before the request goes out. Follow the save with a wait or assertion that depends on the save having finished.
- **For saves that later tests depend on, use save, re-navigate, verify, retry.** `setMenuEntryGroups()` in `utils.js` is the model.
- **Some buttons save the whole page and reload it, without saying so.** "Show permissions for these groups" on a form's Permissions tab is one: what it shows is only on the reloaded page. Wait for the page to be ready before the click, because the button's handler is bound by a script at the end of the page, and wait for the `load` event after it. `showPermissionsFor()` does both.
- **Reopen with a direct URL.** Capture `page.url()` right after opening a form, and `page.goto()` it. Admin nav links aren't there from every state after a save. Checkbox and select assertions work on attached elements, so they don't need the tab to be active.

**How `saveAdminForm()` waits.** The admin's Save fades `div.admin-ui` to 0.5, posts each tab's form to `save.php` in turn, with a PHP syntax check first on screen and template saves, and returns to full opacity when they're all done. So the opacity is what says the save is finished. If the save asks for a reload, the page stays faded until the reload replaces it. The helper's `networkidle` wait only counts after a reload, because `networkidle` fires once per page. Every admin page also fades in over 250ms once it has loaded, and the save's own fade waits behind that one. So a Save clicked during the fade-in looks finished as soon as the page is fully shown, and the helper waits for animations to end before it clicks. The page settings popup fades itself the same way, but a quick save can come and go between two checks of its opacity, so for a popup save the helper waits for the POST to `save.php?popupsave=1`.

**How `saveFormulizeForm()` waits.** This is the same idea for forms on the user side. It clicks into the form and waits for the [security token](security-tokens.md#tokens-on-form-pages) to be filled in. Then it clicks the button while watching for `#savingmessage` to appear, because the animation can start and finish quickly, so the wait has to begin before the click. It waits for the animation to go, then asserts that "Your changes were not saved." isn't showing. So `#savingmessage` is what says the save is finished. The helper proves the save ran and wasn't refused, not that the values are right, so still check them.

### `networkidle`

Playwright's `networkidle` fires once per document and stays set. A `click` or `press` that navigates waits for the new document, so a wait after it is real. A wait after something that happens later, on the same document, passes at once and proves nothing. Wait for what a person would see.

### Hidden tabs and autocompletes

The form admin tabs keep all their content in the DOM, `display:none` until the tab is shown. jQuery UI's autocomplete only draws its dropdown for a visible input, so an autocomplete in a tab that hasn't been shown yet gives no dropdown, and the test times out waiting for a suggestion that never appears.

- Click the tab, then `await expect(theInput).toBeVisible({ timeout: 30000 })` before typing.
- Use `selectAutocompleteOption(page, input, optionText, {searchText})` for the admin autocompletes. It types with `pressSequentially`, waits for the exact text, clicks the item's `<a>` (the admin widget binds to the `<a>`, so clicking the `<li>` selects nothing) and retries the cycle if the list redraws.
- On fast machines, a mouse click can leave the menu open, and its `<a>` then intercepts the next click. To avoid that, type a term that matches one item, select with `ArrowDown` then `Enter`, and wait for `ul.ui-autocomplete:visible` to have a count of 0.

**`.fill()` and `pressSequentially` aren't interchangeable.** The admin's jQuery UI 1.8.2 autocomplete searches on keydown, so it needs real keystrokes, and `.fill()` does nothing. That re-renders the list on each keystroke, which is why the helper retries. The front-end linked element picker responds to `.fill()` with one search and one render, so its items are stable. Don't move those onto the keystroke helper. Front-end picker items have no `<a>`, so click the `<li>`.

### Conditional elements

Conditional elements are redrawn over AJAX while the page sits there, so nothing tells Playwright when they've finished. Changing an element posts to `formulize_xhr_responder.php` for each set of elements it governs. Each response replaces an element's markup, and the new element fades in over a second. A redrawn element can govern others in turn, so one change can set off a cascade of further checks. `window.conditionalCheckInProgress` counts the checks that are running.

**Use `conditionalElementReady(page, handle, triggerAction)` whenever an action makes a conditional element appear or change.** Going on to the next step without it can touch an element that's about to be replaced.

```js
await conditionalElementReady(
    page,
    'exhibits_artifacts',
    () => page.getByText('Ancient History', { exact: true }).click()
);
```

- **`handle` is the conditional element, not the one you're changing.**
- **Pass the action in. Don't do it first.** The helper records the element's HTML, runs your action, and waits for that HTML to change, or for the element to appear. Then it waits for the fade to finish, and for `conditionalCheckInProgress` to stay at 0 for a second, which is what covers a cascade.
- **Only use it when the element will change.** If the action leaves the element's HTML as it was, the wait for a change times out.
- **It doesn't work for an element that disappears.** For that, use `await expect(locator).not.toBeVisible()`, which retries through the fade.

### Form rows

Don't check that an element is showing with its `.form-row`, which is what `elementRow()` returns. In a two-column form, which is what Anari starts with, the row holds only floated columns. So the row has no height, and Playwright counts it as hidden, on a form screen and in the drawer. And `not.toBeVisible()` passes whether the element is there or not. Check a field in the row, or the element's label, `.formulize-label-<handle>`. Lyris's rows have a height, so a check like this can pass there and fail in CI.

### Reads that don't wait

`count()`, `isVisible()`, `isHidden()`, `all()`, `allTextContents()`, `allInnerTexts()` and `evaluateAll()` answer at once with what's on the page at that moment. Right after an action, nothing there can just mean it hasn't arrived yet. `textContent()`, `inputValue()` and `getAttribute()` wait for their element, and assertions like `toHaveText()` retry.

Before one of the reads that doesn't wait, wait for something that proves the content has arrived, or use an assertion that retries. Check what the wait really proves. A click that navigates returns once the new page starts arriving, not when it has all arrived, so a message near the top can be showing while the rows of a table below it aren't there yet. Run `await page.waitForLoadState('domcontentloaded')` before reading the whole page. The page settings popup on a screen's Pages tab loads over AJAX after the click, so reading it straight away finds an empty list, with nothing wrong in the app. Waiting for "Title for page number" works there because the popup's contents arrive all at once.

### Accordion drawers

A drawer slides for 300ms, and the one closing and the one opening are both on the page until it's over. A locator for something that's in every drawer, like "Edit this page", matches two links during that time, and that's a strict mode violation. Look inside the drawer you want. `editScreenPage()` does this for a screen's pages, and `openElementAccordion()` and `openMenuAccordion()` do it for elements and menu entries.

The accordion ignores clicks while a drawer is moving. Playwright's wait for a heading to stop moving doesn't get past this. A heading above the drawers that are moving is already still, and one below them can pass Playwright's check partway through the slide. If the click was ignored, an unscoped locator can go on to act on the wrong drawer without any error, and a scoped one times out. Clicking the drawer that's closing is the worst case, because its contents stay visible while it slides, so a check that it opened passes when the click was ignored. Run `waitForJQueryAnimations(page)` between clicks on two headings.

Only accordions that save their order can be dragged. A heading's link has `draggable="false"`, because otherwise Chromium starts its own drag of the link, the sortable stops getting mouse events, and the accordion stops responding to clicks. `dragDrawer()` in `070-admin-page-reload-keeps-drawer-open.spec.js` shows how to drag one: the sortable needs a mouse that moves in steps, which `dragTo()` doesn't do.

When an admin page reloads, the drawer that was open stays open. A page with no drawer to go back to opens its first drawer, unless the accordion is one that starts closed, and the first one slides open while the page settles. Its "Edit this page" link is visible as soon as the slide starts, so an assertion on that link doesn't mean the accordion will take a click on another heading yet. Run `waitForJQueryAnimations(page)` after a load or reload before clicking another drawer's heading. `openElementAccordion()`, `openMenuAccordion()` and `editScreenPage()` do this already. On the admin home, an application's section slides open and moves the forms in it, so open it with `openApplicationAccordion(page, name)` and not a bare click on its link. `waitForJQueryAnimations()` also waits for jQuery's ready event, because a slide that starts on load only begins in a ready handler.

### Other animations

Anything that slides, fades or grows open is visible from its first frame. So `toBeVisible()` says an animation has started, not that it has finished, and on something that's closing it passes until the very end. Before clicking inside something that's opening, or acting on what an animation leaves behind, run `waitForJQueryAnimations(page)`. Playwright's checks cope with something that only moves while it's clicked, because it retries a click that something else would receive, and keeps that click from the page's scripts. They don't cope with what the browser does with a stray click itself, like closing an open popover, nor with a widget that ignores input while it animates, or with a page that decides something from what it shows mid-animation.

- **Every front-end page on Anari, and in the embed theme.** They hide the body and fade it in once the page has loaded. It has to be a fade: `show()` with a duration grows the body from its top left corner, so everything moves for its first moments. A popover opened straight after a reload would open while the page was still growing, and be somewhere else by the time something in it was clicked. Playwright retries a click that lands on something else, but the browser closes a popover when anything outside it is clicked, whatever Playwright does with the events. Lyris doesn't hide the body.
- **The admin home.** Clicking a form's name grows its details open, and Permissions, Procedures, Clone and Delete are only in there. The Elements and Screens links in the form's own box don't move.
- **Form settings.** The entries-are-users and entries-are-groups settings, and each default group's conditions, slide open. `enableEntriesAreUsers()`, `enableEntriesAreGroups()` and `showConditionsPanel()` wait for them.
- **Conditional elements.** An element that a condition shows or hides fades for a second, starting when the check comes back, and one that's hidden is only emptied when its fade ends. `conditionalChecksFinished()` waits for the fade as well as the checks.

### Lists

`waitForWorkingMessage(page)` is only for after a list action: search, sort, page jump, view change. A plain navigation to a list renders on the server and never shows the working message, so wait for the "Showing entries" footer instead.

`waitForAdminPageReady(page)` needs jQuery UI, so it's only for the admin pages, not for front-end pages like `groups.php` or `users.php`.

### Controls that close their own window

The "Change columns" popup hands its selection to the opener and closes itself, so Playwright's bookkeeping after the click can race the window closing, and fail with *Target page, context or browser has been closed*. `applyColumnChanges(popup)` handles that one. For any other control like it, treat the close as the completion signal:

```js
await Promise.all([
	popup.waitForEvent('close'),
	popup.getByRole('button', { name: 'Change columns' }).click({ noWaitAfter: true }),
]);
```

## Entry locks

A test that opens an entry must leave the page the way a person would, then check something on the page it arrives at. Use `leavePage(page, url)`, or click a link. A test that just ends still holds the lock, and the next test to open that entry as a different user finds it read-only, possibly in another spec file. See [Entry locks](../docs/creating_tests.md#entry-locks).

- The lock is a file in `modules/formulize/temp/`, set when an element renders as editable, and released by a `fetch(keepalive:true)` on the `pagehide` event. Closing the browser at the end of a test doesn't release it.
- **The release lands after the next page has loaded,** by about 200ms locally. Playwright doesn't report it. In Chromium it goes straight to the network, past the [installable app's worker](installable-app.md). In other browsers it goes through the worker, which stops when the browser closes. So a test that clicks a link and ends at once can lose it, and a later test finds the entry locked. `leavePage()` waits until the server has handled the release: the page's code names a file, `<token><uid>.token` in `temp/`, that lists what it locked, and the release deletes it. Leave with `leavePage()` at the end of a test, and reload with `reloadPage()` when a test checks the lock after a reload.
- **In CI a release sometimes never arrives,** with the browser still open and the page fully loaded. Run 42543820246 is one: the worker sent the earlier pages' releases, and the last page's appears nowhere. It doesn't happen locally. So `leavePage()` waits ten seconds and then lets the test carry on, with an annotation saying what's still locked. `entry-lock-reporter.js` lists those at the end of the run, and in the GitHub Actions summary. A form drawn read-only in a later test is the usual consequence, so check that list first.
- A page that's still loading can't release its locks yet. `leavePage()` does that wait. If you click a link instead, call `await page.waitForLoadState('load')` first.
- `users.php` renders the System Users form for every listed user, and the entry id in that form is the uid. If elements render as editable, an admin who only visits `users.php` locks all of those entries, and a user can then be blocked from editing their own profile.
- Don't add a function to core for tests to call. That would mean "leaving releases the lock" is never tested.

## Concurrency

CI runs the whole suite in several environments at once, each a job on its own runner with its own containers: MariaDB and MySQL in Chromium on Anari, Firefox and WebKit on MariaDB and Anari, and Chromium on MariaDB and Lyris. `E2E_BROWSER`, `E2E_THEME` and `E2E_DB_CLIENT` tell the specs which. Only the MariaDB, Chromium, Anari job decides whether the run passes until the others are green. See [docs/ci.md](../docs/ci.md).

A browser feature only one browser has, such as clearing the cache over a DevTools session or emulating a phone, is skipped in the others with `test.skip()` and the reason. `aDayPassesForTheWorker()` does this itself.

Firefox runs with its own user agent, not the one in Playwright's profile. Playwright gives an overridden user agent to a Firefox page and not to its service worker. Our [worker](installable-app.md) makes the page loads and the page makes the POSTs, so with an override each [security token](security-tokens.md) is made for one user agent and checked against another, and every save is refused. Don't set `userAgent` in a spec that runs in Firefox.

Each job runs the suite in four passes, in `.github/workflows/e2e-test-manual.yml`:

| Folder | Flags |
|---|---|
| `formulize-core/01-setup` | `--workers=1` |
| `formulize-core/02-validate` | `--workers=4 --fully-parallel` |
| `formulize-core/03-validate-sequential` | `--workers=1` |
| `formulize-core/04-appearance` | `--workers=1` |

**The flags on `02-validate` override `playwright.config.js`.** A validate spec that passes locally can fail in CI, where its own tests run at the same time as each other. To reproduce it, pass the same flags:

```bash
npm test -- formulize-core/02-validate/0XX-whatever.spec.js --workers=4 --fully-parallel
```

The tests share one live site, so anything stored on the server is shared between tests running at once: system and module settings, API keys, form and element definitions, and entry locks. Under `--fully-parallel`, `beforeAll` and `afterAll` run once per worker, so an `afterAll` that restores a setting can fire while another worker is mid-test.

**To keep a file on one worker,** put this at the top level of the file, after the imports. It pins every test in the file to one worker, in order. A failure skips the rest of the file.

```js
test.describe.configure({ mode: 'serial' });
```

Use it when one test toggles a setting the others depend on, one test consumes something an earlier test made, or several tests act as the same user on the same screen. It doesn't help when two different spec files collide. Give each file its own form, user or group, or merge them.

**Check that state is really shared before serializing.** List column selections look like per-user state, but the "Change columns" popup writes into the opener's hidden fields, and the choice travels through each POST. Concurrent tests as the same user don't disturb each other's columns.

## Which theme the suite covers

The whole suite runs on either theme. `E2E_THEME` says which, and it's `Anari` unless it says `Lyris`. On Anari, `01-setup/001` switches the new site to Anari straight after the install. On Lyris, the site stays on the theme it was installed with. Either way every screen the setup specs make starts with that theme's settings (its `screen-defaults.json`), so on Lyris the museum's lists open entries in the drawer, show 20 entries a page and keep their row of search boxes hidden until it's opened. CI runs both: see [Concurrency](#concurrency) and [docs/ci.md](../docs/ci.md). `04-appearance/` switches the site to Lyris and back in either run, and the two wizard specs there, 100 and 110, skip on Lyris, since a museum built on Lyris has nothing to move to it.

A spec is written once, for both. Do what a person does through the helpers in the "The site's theme" section of `utils.js`, rather than with one theme's markup:

| To | Use | Why there's a helper |
|---|---|---|
| Follow a menu link | `openMainMenu()`, `goToMenuLink()`, `ensureMainMenuOpen()` | Lyris's sidebar is closed on most pages, and closed it has no width, so its links count as visible to Playwright and a click can't reach them |
| Open an entry from a list, by its row's link or the Add button, or add one with a subform's Add button | `openEntry(page, trigger)` | It opens on the form screen or in the drawer, as the list's `editdestination` says, and it waits for whichever. The trigger can be a function, for a button `clickListAction()` has to find, such as a list's Add multiple entries |
| Look for an entry's elements | `entryArea(page)` | It's the drawer's body while the drawer is open, and the page otherwise. Over a list, `page.getByText()` and `page.getByLabel()` also match the list's cells and its "Search …" boxes, and they count hidden matches too |
| Click an entry's own button without saving, such as Close | `entryButton(page, name)` | In the drawer they're in its footer, outside `entryArea` |
| Save | `saveFormulizeForm()`, `saveAndCloseEntry()` | They save in the drawer when it's open. Save and Close is a link on a form screen and a button in the drawer |
| Go to another page of an entry | `goToEntryPage()` | Tabs are links on a form screen and buttons in the drawer |
| Pick an autocomplete's suggestion | `autocompleteSuggestion()` | The suggestions' menu is at the end of the page, outside the drawer, and the list behind can have the same names |
| Type in a list's search boxes | `showListSearches()` first | Lyris lists start with the row hidden |
| Change columns, Save view, Export, Notifications and the like | `clickListAction()`, `openChangeColumns()` | Anari keeps some behind "...", and Lyris keeps all of them in More actions |
| Page through a list, or change its page size | `goToListPage()`, `goToNextListPage()`, `showEntriesPerPage()`, `LIST_PAGE_SIZE` | The controls differ, and 16 artifacts fit on one Lyris page |
| Read or choose the current view | `currentViewName()`, `loadSavedView()` | Anari has a select, and Lyris a view switcher |
| Sign out, or find the account link | `signOutLink()`, `accountLink()` | Logout and Edit Account in Anari, Sign out and Account in Lyris's header |

Which of a form screen and the drawer an entry opens in is the list's setting, not the theme, so the helpers look at what's on the page. `users.php`, `groups.php` and `master.php` open entries full screen in both themes, and an entry opened by its address (`&ve=`) is always full screen. A spec about form screens, like `02-validate/049`, sets its own list to open entries there.

The drawer keeps the last form it showed when it closes, hidden, and the list only reloads after a close if something was saved. So after closing without saving, look at the list by role, which leaves out what's hidden, rather than with `page.getByText()`.

- When a change branches on the theme, the Lyris run checks the Lyris path, but confirm the Anari path is genuinely untouched too.
- Switch themes through the page with `chooseSiteThemes()` in `utils.js`, as `01-setup/001` and `04-appearance/020` do. Saving a new site theme there also moves every screen's theme, which decides the folder its Templates tab edits. `setSiteThemeInDb()` and `restoreSiteTheme()` leave the screens where they were, so a spec that uses them mustn't touch screen templates.
- A spec that switches the theme puts back the run's theme, `E2E_THEME`, not Anari.

See [themes-anari-and-lyris](themes-anari-and-lyris.md).
