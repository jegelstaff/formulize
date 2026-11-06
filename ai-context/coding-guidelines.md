# Formulize Coding Guidelines

Standards and practices for developing Formulize and its XOOPS/ImpressCMS core.

---

## General Principles

### 1. Follow Existing Patterns

- **Always read existing code first** before writing new code
- Match the style and patterns already in use in the file/module
- Don't introduce new patterns unless absolutely necessary
- Maintain backward compatibility with XOOPS legacy code

### 2. Security First

- **Never trust user input** - always validate and sanitize
- Use `intval()`, `floatval()` for numeric values from user input
- Escape everything that goes into SQL, including in a Criteria, and everything that goes into HTML. See [escaping](escaping.md)
- Check permissions before allowing actions

### 3. Prefer Handlers Over Direct SQL

```php
// GOOD: Using handler
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$form = $form_handler->get($fid);

// AVOID: Direct SQL (unless performance-critical)
$sql = "SELECT * FROM " . $xoopsDB->prefix('formulize_id') . " WHERE id_form = $fid";
```

If the handler that owns a table has no method for what you need, add one to it rather than writing the query where you are. Raw SQL belongs in low-level data code, such as the handlers and `extract.php`.

---

## Naming Conventions

### Files

- **Classes:** `ClassName.php` or `classname.php` (match existing in module)
- **Handlers:** `elementHandler.php` or `elements.php` (often plural)
- **Functions:** `functions.php`, `formdisplay.php`
- **Smarty templates:** `lowercase_with_underscores.html`

### Variables

```php
// Object variables
$formObject         // camelCase for objects
$element_handler    // snake_case for handlers (common pattern)
$elementHandler     // camelCase also acceptable

// Arrays
$values            // plural for collections
$entry            // singular for single record

// IDs
$fid              // form ID
$frid             // relationship ID
$sid              // screen ID
$uid              // user ID
$ele_id           // element ID
$entry_id         // entry ID
```

### Functions and Classes

Functions use snake_case or camelCase, matching the code around them. Global functions in the module usually start with `formulize_`, such as `formulize_db_escape()`.

Formulize classes usually start with a lowercase `formulize`, with handlers ending in `Handler`:

```php
class formulizeForm extends FormulizeObject { }
class formulizeFormsHandler { }
class formulizeElementsHandler { }
```

---

## File Structure Standards

```php
<?php
/**
 * Brief description of file purpose
 *
 * @package Formulize
 * @subpackage ModuleName
 * @author Author Name
 */

// Security check
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Include dependencies
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/forms.php";

// Class or function definitions
class MyClass {
    // ...
}

// End of file - no closing ?>
```

- Always include the security check at the top of files that shouldn't be requested directly
- No closing `?>` tag at end of PHP files (prevents whitespace issues)
- Use `require_once` for critical dependencies, `include` for optional

---

## Handlers and Objects

See [Writing a New Object and Handler](database-patterns.md#writing-a-new-object-and-handler) for how to structure them, and [Getting Handlers](database-patterns.md#getting-handlers) for how to get one. Handlers are already cached per request, so don't cache them yourself.

---

## Admin Pages and Saves

The admin UI has no redirect after save, and pages don't post to themselves the way the front end does. "Save your changes" posts each `.formulize-admin-form` on the page to `admin/save.php`, one at a time. That file checks that the user is a module admin and the system isn't save locked, sorts the fields named `class-property` into `$processedValues`, and includes `admin/save/<formulize_admin_handler>_save.php`.

A save handler checks its own permissions, such as `edit_form`, and reports back by what it prints:

| It prints | What happens |
|---|---|
| Nothing | Success |
| `/* eval */` and JavaScript | The JavaScript runs after all the forms have been saved. It's usually `reloadWithScrollPosition()` |
| `/* evalnow */` and JavaScript | The JavaScript runs straight away, and saving continues |
| `/* evalnowandreturn */` and JavaScript | The JavaScript runs straight away, and saving stops |
| Anything else | It's shown to the user in an alert |

See [admin-save-error-masking](admin-save-error-masking.md) for how that can hide an error.

---

## Database Best Practices

Cast or escape anything from user input, use the table prefix, and check the result:

```php
$fid = intval($_GET['fid']);
$name = formulize_db_escape($_POST['name']);

$sql = "SELECT * FROM " . $xoopsDB->prefix('formulize_id') . " WHERE id_form = $fid";
if (!$result = $xoopsDB->query($sql)) {
    return false;
}
```

See [escaping](escaping.md) for escaping, including in a Criteria, and [Direct Database Access](database-patterns.md#direct-database-access-when-needed) for more on queries.

---

## Output Escaping

Use `getVar()`, which escapes by default. See [Getting and Setting Values](database-patterns.md#getting-and-setting-values) for all the formats.

```php
// For HTML display (escaped)
echo $form->getVar('form_title');  // Default 's' format

// For form inputs (HTML escaped, so it's safe inside an attribute)
echo '<input value="' . $form->getVar('form_title', 'e') . '">';
```

For everything else, see [escaping](escaping.md).

---

## Permission Checking

See [Permissions System](formulize-module-overview.md#permissions-system) for how Formulize permissions work.

```php
global $icmsUser;

// Check if logged in. $icmsUser isn't an object for anonymous visitors, so check before calling methods on it
if (!$icmsUser) {
    redirect_header('user.php', 3, 'Please log in');
    exit();
}

// Check if admin
if (!$icmsUser->isAdmin()) {
    redirect_header('index.php', 3, 'Access denied');
    exit();
}

// Check form/entry-level permissions (Formulize) - security_check() is in modules/formulize/include/functions.php
if (!security_check($fid, $entry_id)) {
    redirect_header('index.php', 3, 'Access denied');
    exit();
}
```

---

## Error Handling

```php
// Check handler returns
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$form = $form_handler->get($fid);
if (!$form) {
    redirect_header('index.php', 3, 'Form not found');
    exit();
}

// Check database operations
if (!$form_handler->insert($form)) {
    print "Error saving form";
}
```

---

## Template Integration

### Assign Variables to Smarty

```php
global $xoopsTpl;

// Assign single value
$xoopsTpl->assign('form_title', $form->getVar('form_title'));

// Assign array
$xoopsTpl->assign('form', $form->getValues());

// Assign collection
$elements = $element_handler->getObjects(null, $fid);
$element_array = array();
foreach ($elements as $element) {
    $element_array[] = $element->getValues();
}
$xoopsTpl->assign('elements', $element_array);
```

### In Smarty Template

The ICMS system uses non-standard tags for Smarty variables and control structures: `<{ }>`

```smarty
<h1><{$form_title}></h1>

<{foreach from=$elements item=element}>
    <div class="element">
        <label><{$element.ele_caption}></label>
        <input type="text" name="<{$element.ele_handle}>">
    </div>
<{/foreach}>
```

Array access with a variable key looks like this: `$content.per_group_conditions_ui[$group.id]`.

### JavaScript in Admin Templates

**The admin UI and the front end run different versions of jQuery. Check which one your code will run in before you write it.**

| | jQuery | jQuery UI | Loaded by |
|---|---|---|---|
| **Admin UI** | **1.4.2** | **1.8.2** | `templates/admin/ui.html` |
| **Front end** | 1.12.4 | 1.11.4 | `header.php`, which also loads `jquery-migrate` |

These values are surprising because the site itself ships jQuery 1.11.1 at `libraries/jquery/jquery.js`, but nothing loads it. `header.php` gets jQuery from Google's CDN. The shipped version should be removed in future work. Admin templates execute against 1.4.2 no matter what the page may have loaded earlier. Confirm it on any admin page by looking at the order of the `<script>` tags, not at which files exist on disk.

There is also a **conditional fallback** in `modules/formulize/include/functions.php` that injects 1.4.2 and UI 1.8.2 only when `jQuery` or `jQuery.ui` is undefined. It is a safety net for pages that load no jQuery of their own, and it means some front-end contexts could theoretically end up on 1.4.2 as well, however this is extremely unlikely in real world situations.

#### Writing for jQuery 1.4.2

The traps are mostly methods that feel old enough to be safe and are not - each of these was added *after* 1.4.2, so calling one throws `has no method`:

```javascript
// ❌ NOT AVAILABLE in 1.4.2              // ✅ use instead
$.ajax(...).done(fn).fail(fn)             $.ajax({ success: fn, error: fn })    // deferreds: 1.5+
$("#el").on('click', fn)                  $("#el").bind('click', fn)            // .on(): 1.7+
$(".cls").on('click', sel, fn)            $(".cls").live('click', fn)           // delegation
$("#el").prop('checked', true)            $("#el").attr('checked', 'checked')   // .prop(): 1.6+
$("#el").prop('disabled', true)           $("#el").attr('disabled', 'disabled')
$("#el").prop('checked', false)           $("#el").removeAttr('checked')
```

Two that are easy to get wrong because the method *does* exist, but does something else:

- **`.error()` is an event binder, not an ajax handler.** `$("img").error(fn)` works. But `$.getJSON(...).error(fn)` does not, because in 1.4.2 an ajax call returns a bare `XMLHttpRequest` rather than a promise. Always pass `error:` inside `$.ajax`.
- **`.data('foo')` does NOT read a `data-foo` attribute.** Automatic parsing of HTML5 data attributes arrived in 1.4.3. Read them with `.attr('data-foo')`; `.data()` here only returns what `.data()` set.

`.is(':checked')`, `.slideUp()`, `.slideDown()`, `.each()`, `.bind()`, `.live()` and `.delegate()` all behave as expected.

**One failing ready handler stops all the later ones.** Under 1.4.2, an exception in any `jQuery(document).ready` handler aborts every handler after it. So shared front-end JavaScript that loads in the admin, such as `include/js/autocomplete.js`, has to stay 1.4.2-safe, or it silently kills things like the CodeMirror setup.

#### Admin UI Dialogs

jQuery UI 1.8.2 `.dialog()` is available and already used in the admin - see `#cloneElementFormChoice` and `#elementUsageDialog` in `templates/admin/form_elements.html`. Prefer it over `alert()`/`confirm()` for anything with more than a sentence of content. `dialog('option', 'buttons', {...})` works, which is how a dialog can swap its buttons once loaded content arrives.

---

## Performance Optimization

### Batch Loading

```php
// Bad: N+1 queries
foreach ($form_ids as $fid) {
    $form = $form_handler->get($fid);
}

// Good: Single query
$forms = $form_handler->getAllForms(false, $form_ids);
```

---

## Testing and Review

To test a change, use the application. See [testing-through-the-application](testing-through-the-application.md). When adding a feature, try it as an anonymous visitor, a regular user and an admin, and with invalid input, missing parameters and ids that don't exist.

For what to check before you finish, see [Before you finish a change](../AGENTS.md#before-you-finish-a-change).

---

## Debugging

Debug on a local copy, never on a live site. You only see what you read back, so start with the log: it doesn't need a browser and it can't disturb the page. See [local-environment-and-testing](local-environment-and-testing.md) for the Docker setup, database access and debugging Playwright failures, and [docs/debugging.md](../docs/debugging.md) for stepping through code with XDebug.

### Read the Log

```bash
docker logs --tail 200 formulize-web-1
```

The container's name starts with the name of the folder the repository is in. When `mainfile.php` defines `ICMS_ERROR_LOG_SEVERITY`, PHP errors appear here as `icms_core_Logger::handleError(...)` lines. The installer's template sets it to `E_WARNING`, so warnings and errors are logged, but notices and deprecations are not.

### Trace Values

Use `error_log()`, which writes to the same log:

```php
error_log('fid: ' . $fid);
error_log(print_r($variable, true));
error_log(print_r($object->getValues(), true));

$result = $xoopsDB->query($sql);
if (!$result) {
    error_log('Query failed: ' . $xoopsDB->error() . ' SQL: ' . $sql);
}
```

Avoid `echo`, `print_r()` and `var_dump()` to the page for this. Extra output can break redirects and admin saves, and you only see it if you fetch the page.

### Turn On the Developer Dashboard

The **Developer Dashboard** system preference adds a report of PHP errors and queries to each page. Set it to **Enable debug (inline mode)** on a local copy, read the page in a browser or fetch it with `curl` or Playwright, and turn it off when you're done. Only groups with the `enable_debug` permission see the report, and webmasters always do.

Don't set `error_reporting()` or `display_errors` in `mainfile.php` instead. When the Developer Dashboard is off, `plugins/preloads/debug_mode.php` overrides those settings during bootstrap. ICMS also handles warnings and notices with its own error handler, so they never reach PHP's display anyway.

---

## Version Control

Work in a branch off `master`, with one feature or fix per branch, and open a pull request. See [docs/version_control.md](../docs/version_control.md).

Commit messages are short and say what changed, such as `Fix drawer saves refused when the entry behind it opened full screen`. Include the issue or area where it helps, such as `Lyris: size the subform count box (#932)`.

Pushing to `master` runs the test suite, and the `e2e-test-manual.yml` workflow can be started by hand for a branch. Put `[SKIP TESTS]` in a commit message to skip the run, for changes that can't affect the code, such as documentation.

Tests go in the same branch as the code they cover.

---

## Quick Reference

### Common Global Variables

Both names in a row hold the same thing, so declare whichever one you use with `global`. `$xoopsDB` has no ICMS name.

| ICMS | Older XOOPS name | Holds |
|---|---|---|
| | `$xoopsDB` | Database connection |
| `$icmsUser` | `$xoopsUser` | Current user object |
| `$icmsConfig` | `$xoopsConfig` | System configuration array |
| `$icmsTpl` | `$xoopsTpl` | Smarty template engine, available after `header.php` |
| `$icmsModule` | `$xoopsModule` | Current module object |
| `$icmsModuleConfig` | `$xoopsModuleConfig` | Module configuration |

### Common Functions

```php
icms::handler($name)                       // Get core handler
xoops_gethandler($name)                    // Older wrapper for icms::handler()
icms_getModuleHandler($name, $module)      // Get module handler
xoops_getmodulehandler($name, $module)     // Older wrapper for icms_getModuleHandler()
redirect_header($url, $time, $message)     // Redirect with message
formulize_db_escape($string)               // Escape for SQL
intval($value)                             // Cast to integer
htmlspecialchars($string)                  // Escape for HTML
```
