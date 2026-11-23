# The right slide-out drawer

The drawer shows an entry's form over the page, without leaving it. It's also where the AI assistant opens.

**This part of the system is changing quickly, so check this note against `drawer.js` before relying on it.**

## The pieces

| Piece | Job |
|---|---|
| `modules/formulize/include/js/drawer.js` | The drawer itself: its markup, opening and closing, loading forms, paging, going into sub entries, saving. Its header comment lists the public API, `window.formulize.drawer`. |
| `modules/formulize/include/formdisplay-elementsonly.php` | Draws a form as a fragment for the drawer, and handles the writes that draw the form again. Its header comment lists the parameters. |
| `footer.php` | Adds `drawer.js` to every page, with the drawer's words from the language files. |
| `templates/screens/default/drawer/` | The drawer's templates. `toptemplate.php` and `bottomtemplate.php` wrap the form. `headertemplate.php` and `footertemplate.php` place the header's and footer's controls while an entry is showing; each control is a placeholder that `drawer.js` swaps for the real one, and the drawer falls back to its built in layout if a template is missing or fails. A theme overrides any of them in `templates/screens/<Theme>/default/drawer/`, as Lyris does for the wrapper and Anari does for the footer. They're separate from the full screen form's templates, so changing the button order on both means changing both. |
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

**Functions that an element's markup calls go in `formulize_elementScripts()`,** in `include/functions/element_scripts.php`, such as the date element's `check_date_limits()`. Full screen forms, lists (for editing in the list) and the endpoint all print it, so the element works whichever drew it, and over any page. Don't define them in `drawJavascript()` alone.

**Fragments are put in the page with `injectFragment()`,** which recreates script tags so they run in order. Inline scripts run again for every form shown, so anything they set up has to cope with that. Each form replaces `removeDrawerEntryLocks()`, for example, while the `pagehide` listener is added once. A script file runs once in a page, so one the page already has, or an earlier form brought, is skipped: don't rely on a file, or a library's callback when it loads, to set up the form it comes with. The page's `load` event has already happened, so a script that has to wait uses `jQuery(document).ready()`, which runs at once.

**Subform buttons call functions the page may already define.** The endpoint wraps `add_sub`, `goSub`, `goSubModal`, `sub_del` and `sub_clone`. While the drawer is open they go to the drawer, and otherwise to the page's own function, so a form screen with a drawer over it keeps working.

**Going into a sub entry** keeps a stack of frames, so Back returns to the parent. If the parent has changes, they're saved first, as they are full screen.

**The endpoint marks its request as the drawer's,** and `formulize_renderingInDrawer()` in `formdisplay.php` says whether a request is. It's true for every form drawn in the request, including sub entries drawn inside the drawer's form, so check `$GLOBALS['formulize_inlineSubformFrid']` too for something that is only for the drawer's own form, as the drawer's wrapper template is. It also keeps `displayForm()` out of full screen's subform navigation, because the drawer opens sub entries itself, through `subformAction()`, and never posts `goto_sfid`.

**`window.formulizechanged` is shared with the page behind.** The drawer resets it for each form, so a host that opens the drawer has to put its own value back, as `goSubModal()` does with `onDrawerClosed`.

**Conditional elements** are drawn by the outermost form, which here is the drawer's. See [conditions](conditions.md#which-form-draws-the-javascript). Each one comes with the screen and relationship of the form it's in, because the page's `FORMULIZE` object has only the page's. Don't read `FORMULIZE.SCREEN_ID` or `FORMULIZE.FRID` for something in a form, and don't replace the object for the drawer.

**Validation is full screen's code.** Elements write their validation for `xoopsFormValidate_<form>(leave, myform)`, and the endpoint's function takes the same arguments. The drawer passes `'drawer'` as `leave`. A check the server has to answer, such as a unique value check (`formulize_uniqueValueValidationCode()`), sends `leave` with its request and fails validation until the answer comes. `formulize_xhr_return()` then calls `window.formulize.drawer.retryAfterValueCheck()` rather than full screen's `validateAndSubmit()`, and the drawer does the action again. So every drawer action that validates passes itself to `validateCurrentForm()` as the action to retry.

**The checks before submitting are full screen's too.** They're in `formulize_submitStepJs()`, in `include/functions/element_scripts.php`: waiting for conditional elements that are still on their way, asking about letters in number boxes, running the validation and then the form's extra validation function (`formulizeExtraFormValidation`), and copying the rich text editors into the fields that are saved. `validateAndSubmit()` and `validateCurrentForm()` both call them, so add a check there and not in either one. Any drawer action that sends the form's fields has to go through `validateCurrentForm()`, or its rich text fields are saved empty. The drawer keeps its form's extra validation function with the form, because the page behind can have its own on `window`.

**An element that submits the form itself,** as the 2FA element does, calls `window.formulize.drawer.save()` when its form is in the drawer.
