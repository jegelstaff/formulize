# The right slide-out drawer

The drawer shows an entry's form over the page, without leaving it. It's also where the AI assistant opens.

**This part of the system is changing quickly, so check this note against `drawer.js` before relying on it.**

## The pieces

| Piece | Job |
|---|---|
| `modules/formulize/include/js/drawer.js` | The drawer itself: its markup, opening and closing, loading forms, paging, going into sub entries, saving. Its header comment lists the public API, `window.formulize.drawer`. |
| `modules/formulize/include/formdisplay-elementsonly.php` | Draws a form as a fragment for the drawer, and handles the writes that draw the form again. Its header comment lists the parameters. |
| `footer.php` | Adds `drawer.js` to every page, with the drawer's words from the language files. |
| `templates/screens/default/drawer/` | The wrapper around a form in the drawer. A theme can override it, as `templates/screens/Lyris/default/drawer/` does. |
| `.formulize-drawer` in `templates/css/formulize.css` | The base styling. A theme skins the classes and custom properties. |

The drawer belongs to Formulize, not to a theme. It builds its markup the first time it's opened, so a theme doesn't have to add anything to get one. It uses the form's own screen and templates, so a form looks the same as it does full screen. Don't write a parallel set of "form in the drawer" styles.

## What opens it

- **A list.** The list screen's `editdestination` setting is `screen` or `drawer`. With `drawer`, `entriesdisplay.php` calls `initListView()`, which replaces `goDetails()` and `addNew()`, so the edit icon and Add button open the drawer with no change to the list's markup. The default, `screen`, adds nothing.
- **A subform.** `goSubModal()` in `formdisplay.php` opens a sub entry with `openEntry()`. The subform element's settings say when: `ele_value[3]` of 2 or 3 for viewing entries, and `new_entry_mode` of `drawer` for new ones.
- **The AI assistant.** A link with the `js-open-ai` class and a real `href` to `/ai/`.

An entry opens in the same screen it would open in full screen.

## Saving

There are two paths, and which one a button uses depends on what happens next.

| Button | Sent to | Then |
|---|---|---|
| Save and stay, and changing pages | `formdisplay-elementsonly.php`, with `formulize_save`, by `fetch()` | The endpoint includes `readelements.php`, then draws the form again. A new entry comes back as the saved entry. |
| Save and leave | `readelements.php`, by `jQuery.post()` | Closes the drawer and refreshes the list, or goes back to the parent entry. |

This difference matters for [tokens](security-tokens.md): the `fetch()` path uses up the form's token, and the jQuery one doesn't.

The form's write addresses aren't in the page when it loads. `action` and `data-save-action` get their real values when someone first interacts with the form, and the token follows. So always write through `writeAddress(form)` and `saveAddress(form)`. The `ENDPOINT` constant is for loading forms only.

If `readelements.php` refuses a save, its message is printed first in the response, and the drawer shows it. `include/common.php` turns an error during a drawer request into a message too, because the drawer shows whatever comes back in place of the form.

`readelements.php` has to return an empty body, because inline editing in a list shows any response as an error. So the drawer reports saves to the assistant's activity log from the browser.

## Why it needs its own code

Drawing a form in elements-only mode skips `drawJavascript()`, so the endpoint has to supply what a form needs itself: the rich text editor setup, the office use toggle, validation, and removing entry locks. If something works full screen and not in the drawer, look there first. Check what the endpoint prints against what `drawJavascript()` prints.

**Fragments are put in the page with `injectFragment()`,** which recreates script tags so they run in order. Scripts run again for every form shown, so anything they set up has to cope with that. Each form replaces `removeDrawerEntryLocks()`, for example, while the `pagehide` listener is added once.

**Subform buttons call functions the page may already define.** The endpoint wraps `add_sub`, `goSub`, `goSubModal`, `sub_del` and `sub_clone`. While the drawer is open they go to the drawer, and otherwise to the page's own function, so a form screen with a drawer over it keeps working.

**Going into a sub entry** keeps a stack of frames, so Back returns to the parent. If the parent has changes, they're saved first, as they are full screen.

**`$formulize_displayingSubform = true` in the endpoint has to stay.** It stops `displayForm()` from sending the page to a subform screen, or jumping into a new sub entry's multipage screen, which the drawer's Add would otherwise cause.

**`window.formulizechanged` is shared with the page behind.** The drawer resets it for each form, so a host that opens the drawer has to put its own value back, as `goSubModal()` does with `onDrawerClosed`.

**Conditional elements** are drawn by the outermost form, which here is the drawer's. See [conditions](conditions.md#which-form-draws-the-javascript).

Elements-only validation doesn't run unique value checks.
