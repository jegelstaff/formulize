# Escaping for SQL and HTML

This covers how to escape. For deciding whether a value should be escaped or purified, see [xss-and-purification](xss-and-purification.md).

## SQL: Criteria objects do not escape values

`icms_db_criteria_Item::render()` puts values into SQL as they are. That's a deliberate contract, not a gap: callers escape values before building a Criteria, and if `render()` escaped them too, they'd be escaped twice. Escape anything from user input first.

An escaping avenue is always reachable, so don't conclude that a class "cannot escape" because it has no injected `$db` property:

- **Formulize code:** `formulize_db_escape($v)`. It runs `$xoopsDB->quote()` and takes off the outer quotes, so the result goes inside quotes you write yourself. It needs no handle.
- **ICMS core code in `libraries/icms`:** use `global $xoopsDB` directly. Don't call `formulize_db_escape()` from core.

```php
$name = formulize_db_escape($_POST['name']);
$criteria = new icms_db_criteria_Item('name', $name);

$safe_value = $xoopsDB->quoteString($user_input);  // adds the quotes itself
$fid = intval($_GET['fid']);
```

Don't use `htmlspecialchars()` to make a value safe for SQL. It's not an SQL escaper.

**`IN`:** callers hand `render()` a finished, escaped `(...)` list. User-typed `IN` filtering is escaped separately at the data layer, by `formulizeDataHandler::prepareValueForInOperator()`, and doesn't go through Criteria at all.

When you're judging what's safe to change, argue from the contract, double escaping and how much code is affected, not from whether a handle is available.

## HTML

Write `htmlspecialchars($value)`, without `ENT_QUOTES`. As of PHP 8.1 the default flags are `ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401`, so single quotes are already escaped, and passing the flag is redundant. `$myts->htmlSpecialChars()` (`icms_core_DataFilter::htmlSpecialChars`) uses those same defaults.

The canonical safe-output idiom is to normalize, then escape once:

```php
$myts->htmlSpecialChars(undoAllHTMLChars($value))
```

`getVar()` escapes for you. See [Getting and Setting Values](database-patterns.md#getting-and-setting-values).

Other contexts:

```php
echo urlencode($user_input);    // URL parameters
echo json_encode($user_input);  // JavaScript strings
```
