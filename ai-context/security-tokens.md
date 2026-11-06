# Security tokens

Formulize protects against cross-site request forgery with tokens from `icms_core_Security` (`libraries/icms/core/Security.php`). The same object is available as `icms::$security` and `$GLOBALS['xoopsSecurity']`.

## How a token works

- `createToken($timeout, $name)` makes a token. `getTokenHTML($name)` prints it as a hidden field, named `<name>_REQUEST`. The default name makes that `XOOPS_TOKEN_REQUEST`.
- Each token is a file in the `tokens/` folder at the site root, filed under the visitor's session. Its life is the session's length, unless a timeout is given.
- `check($clearIfValid, $token, $name)` calls `validateToken()`. With no `$token`, it reads the value from `$_REQUEST`. The name has to match the one the token was made with.
- The token is a hash that includes the browser's user agent, so it only works from the browser it was issued to.
- **A token is used up when it's checked,** unless the request is an AJAX one sent with `X-Requested-With: XMLHttpRequest`. A plain `fetch()` doesn't send that header, so it uses the token up, and jQuery does send it, so it doesn't.
- With nothing to file a token under, `createToken()` returns nothing, so the page's submission is refused. It doesn't fall back to a token anyone could use.

For a page in another site's frame, see [CSRF tokens](embedding-screens.md#csrf-tokens).

## Tokens for any purpose

The system is general. CSRF protection is its main use, but a token is just a proof that this browser was given something earlier, and it works for anything that needs that. A token's `$name` can be anything safe to use in a file name, and `$timeout` can be anything you like, so a feature can have tokens of its own that nothing else can redeem.

```php
$token = icms::$security->createToken(300, $name); // good for five minutes
...
if (!icms::$security->validateToken($submittedToken, true, $name)) {
    // refuse
}
```

Existing uses beyond form submissions:

- **2FA and login:** `include/2fa/challenge.php` and `confirm.php` name a five-minute token after the email or phone number being confirmed, so the token proves that step was passed for that contact, not just that it was passed.
- **Links:** `lostpass.php` and `masquerade.php` take a token from the URL, so a link can be followed once by the browser it was made for.
- **A unique name:** the error page uses a token to name its error report file.

Consider this before building a new one. It's already bound to one browser, expires, and cleans up after itself. Entry locks are the exception to know about: `getEntryLockSecurityToken()` is a separate mechanism with its own files in `modules/formulize/temp/`, not this system.

## Where it's checked

**Saving through `readelements.php`.** `formulize_readElementsSecurityCheckPassed()` runs at the first cue in the submission, before anything is acted on, because preparing values already moves uploaded files and writes accounts. It checks once per request and remembers the answer, because checking uses the token up. If it fails, the person sees a message and nothing is saved. An element saved on its own, by `displayElement()`, carries its own token under the name `formulize_display_element_token`, in a `detoken_` field.

**Actions in a list.** `displayEntries()` checks the token before it deletes or clones entries, changes saved views, or changes who owns an entry.

**A screen can turn this off.** Both checks use the screen's `useToken` setting, or the `useToken` module preference when there's no screen.

**Pages with their own token.** The Appearance pages check tokens with names of their own, such as `formulize_looks_token`. Follow `admin/looks.php` for a new page that writes something outside these paths.

```php
// drawing the page
print $GLOBALS['xoopsSecurity']->getTokenHTML('formulize_my_feature_token');

// handling the POST
if (!$GLOBALS['xoopsSecurity']->check(true, false, 'formulize_my_feature_token')) {
    // refuse, and say why
}
```

A form that stays on the page after saving needs a new token for the next save. `admin/themeeditor.php` hands one back in its response.

A token isn't a permission check. A request can hold a valid token and still have no right to do what it asks, so check that too.

## Tokens on form pages

Form pages don't put the token in the field. The field goes out empty, and it's filled in 269ms after a `focusin`, `click` or `touchstart`. See `renderValidationJS()` in `include/formdisplay.php`, and the drawer's version in `include/formdisplay-elementsonly.php`.

Two things follow from that:

- **Any script that fills a token must look inside its own form.** A selector across the whole page copies one form's token into another. That once made drawer saves fail: a click in the drawer put the page's token in the drawer's form, the first save used it up, and the next was refused.
- **A test that clicks and then saves within 269ms sends an empty token.** Wait for it first, with `waitForFormulizeFormToken()` in `tests/e2e/utils.js`. It looks at `#formulize_mainform`, so a form with another id needs its own wait.

A person can't save that fast, so this is a wait for something real. See [testing-robust-specs](testing-robust-specs.md).
