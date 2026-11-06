# Conditions

Conditions are rules like "Status is Active" or "Date is after today". The same system is used all over Formulize, and its uses keep expanding. This is only a partial list: when an element or a form page is shown, per-group permission filters, which options a linked element offers, which entries a subform shows, which entries create user accounts, plus many more.

## The format

A set of conditions is a standard array with four parallel lists: `[elements[], ops[], terms[], types[]]`. Each position in the lists is one condition.

## The filter UI

- `formulize_createFilterUI()` generates the HTML for editing conditions.
- `parseSubmittedConditions()` parses what comes back in the POST data.
- Several filters on one page need unique filter names. The form permissions page is an example.

## Two paths evaluate conditions

Conditions are evaluated in two separate places, and they need to agree:

| Path | Use it to | How it works |
|---|---|---|
| `checkConditionsAgainstAnEntry()` | Ask whether one entry passes | `gatherDataset()`, then `buildEvaluationCondition()`, then `eval()`. It takes a `$frid`, as described in [relationships](relationships.md), and returns 1 or 0. No conditions means 1. |
| `buildConditionsFilterSQL()` | Find all the entries that pass | Builds SQL, with `_buildConditionsFilterSQL()` handling each condition. |

All of these are in `include/functions.php`. If you change how a condition is treated in one path, check the other path too.

List searches are different code again: they go through `formulize_parseFilter()`, so a fix to conditions doesn't fix searches.

## Which form draws the JavaScript

The code that shows and hides conditional elements is drawn once, by the outermost form on the page. `displayForm()` skips it inside an inline subform render, when `$GLOBALS['formulize_inlineSubformFrid']` is set, and for elements-only forms, unless the form is in the [drawer](drawer.md), where the elements-only form is the outermost one.

Nested renders reach that point first, and their output is buffered into the outer form's markup. If one of them drew the code, `conditional.js` would land after the init code that needs it. What it needs is kept in globals, so the single block covers every form in the tree.

A page with two or more top-level `displayForm()` calls gets one block for each, so a later block binds the earlier forms' change handlers again, and their conditions are checked twice. That doesn't change the result.

Keep `drawJavascriptForConditionalElements()` printing its output. Custom code on sites calls it directly.

For testing conditional elements in specs, see [Conditional elements](testing-robust-specs.md#conditional-elements).
