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

List searches are different code again: they go through `formulize_parseFilter()`, so a fix to conditions doesn't fix searches. Dates are the exception, see below.

## Dates

Conditions, searches, per-group filters and the check against one entry all read dates the same way, so each rule is in one function in `include/functions.php`. Change the rule there, and not in the code that calls it.

| Rule | Function |
|---|---|
| Which span a term names: a year, a month, or a day on `creation_datetime` and `mod_datetime` | `formulize_dateSpanInTerm()` |
| What each operator means against a span | `formulize_dateSpanComparison()`. Its output goes in SQL and in the PHP that `buildEvaluationCondition()` makes, so keep it to comparisons that mean the same in both. |
| LIKE also matching the date as the site shows it | `formulize_dateLikeComparison()`. This one is SQL only, and `buildEvaluationCondition()` does the same with `stristr()`. |
| A term that isn't a date | `formulize_dateComparedWithTermThatIsNotADate()` |
| What blank is | `formulize_blankValueComparison()`, and `formulize_blankDateComparison()` for dates. Nothing is before or after a blank, on either side, for any kind of field: conditions leave blanks out with `formulize_blankValueComparison()` when `formulize_operatorComparesOrder()` is true. |

The callers differ in one way on purpose: a negative condition or search takes in entries with no date, and a negative per-group filter doesn't, so that a permission filter is never widened.

## Times

Times are stored as HH:MM:SS, and shown in the element's 12-hour or 24-hour format. Datasets hold the time as it's shown, so the check against one entry reads it back with `formulize_timeValueAsISO()` before comparing. MariaDB reads text it can't make a time of as midnight, `''` and `bread` alike, so those can't go into SQL as they are. Each rule is in one function:

| Rule | Function |
|---|---|
| What LIKE finds: the term in the time as it's shown, an hour when the term starts with one (`5:`), or a whole time typed either way | `formulize_timeSearchTerm()`, then `formulize_timeLikeComparison()` in SQL and `formulize_timeMatchesSearch()` in PHP |
| A term that isn't a time | `formulize_termIsATime()`, then the same operator rule as dates, `formulize_dateComparedWithTermThatIsNotADate()`. IN lists keep only their times |
| What blank is | NULL, and only NULL, in `formulize_blankValueComparison()` |
| A range in a search whose From is later than its To goes past midnight | `formulize_timeRangeAcrossMidnight()`, which makes the two terms an OR'd set. It works out `{NOW}` first, since a range like the next hour only crosses midnight at some times of day |

## Which form draws the JavaScript

The code that shows and hides conditional elements is drawn once, by the outermost form on the page. `displayForm()` skips it inside an inline subform render, when `$GLOBALS['formulize_inlineSubformFrid']` is set, and for elements-only forms, unless the form is in the [drawer](drawer.md), where the elements-only form is the outermost one.

Nested renders reach that point first, and their output is buffered into the outer form's markup. If one of them drew the code, `conditional.js` would land after the init code that needs it. What it needs is kept in globals, so the single block covers every form in the tree.

A page with two or more top-level `displayForm()` calls gets one block for each, so a later block binds the earlier forms' change handlers again, and their conditions are checked twice. That doesn't change the result.

Keep `drawJavascriptForConditionalElements()` printing its output. Custom code on sites calls it directly.

For testing conditional elements in specs, see [Conditional elements](testing-robust-specs.md#conditional-elements).
