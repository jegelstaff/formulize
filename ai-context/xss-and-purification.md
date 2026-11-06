# XSS: what gets shown and what gets filtered

Almost everything Formulize displays was typed by someone, and what matters is who. An admin who can edit a form can already put anything they like on the page, so what they wrote is shown as written. What other people typed can't be trusted.

The difficulty is that one piece of content can hold both. A caption, a block of static content or a derived value formula is written by an admin, but it can use **references**, which pull in what other people typed. So the question for any authored content is: is what's about to be shown exactly what the admin wrote, or has anything else got into it? Much of the rendering pipeline exists to answer that, and to respond correctly when the answer is yes.

Smarty doesn't escape anything automatically here, so a value is only safe if code made it safe.

For the mechanics of escaping, see [escaping](escaping.md).

## The three treatments

- **Escape** (`$myts->htmlSpecialChars()`): markup shows as literal text. For plain values.
- **Purify** (`formulize_purifyHtmlValue()`): an allow-list filter through HTML Purifier, in `include/functions.php`. Formatting survives, and scripts and event handlers don't. For values that are meant to be HTML.
- **Leave alone:** for what a person wrote while they had the right to edit the form or screen. They could put anything they wanted there anyway.

## References: how typed data gets into authored content

A reference is an admin's way of saying "put this entry's value here". There are several kinds, and they don't all work the same way.

| Reference | Where it's used | How the value gets in |
|---|---|---|
| `{handle}` in text | Captions, help text, static content, grid headings, text box defaults | Substituted into the text, by `formulize_replaceCurlyBracketVariables()` |
| `{handle}` in PHP | Static content containing `$value=`, text box defaults containing `$default`, a `<?php ?>` region in help text | Bound to a variable, by `bindReferencesForPHPEval()` |
| `$handle` in a derived value formula | Derived value elements | Assigned with `getValue()`, in the generated formula function. See [How a formula runs](derived-values-and-custom-code.md#how-a-formula-runs). |
| `$handle`, `$entry` and `$templateScreenData` | Template screens | Set up by `templateScreen.php` before the screen's code runs |

Every `{handle}` resolves through `resolveReferenceValue()`, so a reference gives the same value in text and in PHP. It uses `getValue()`, then applies the element's display text and number formatting. A term that isn't a real element is left as it is. While a conditional element is being redrawn, a reference can give a value that's on screen and not saved yet, from `$GLOBALS['formulize_asynchronousFormDataInAPIFormat']`, so even an unsaved value can reach this path. `getValue()` returns the raw value, so nothing that comes out of a reference is safe to display as it stands.

A reference creates two separate risks, and each has its own protection.

### Keeping data out of code

Entry data must never become part of the code that runs. A value spliced into PHP as text could break out of a string and run as code, and escaping can't fix that, because a reference may not be inside quotes at all: `$default = {a} + {b};`.

So references in PHP are rewritten to variables, and the value is supplied when the code runs. `formulize_bindCurlyReferencesInPHPCode()` reads PHP's tokens, because what a reference has to become depends on where it sits:

| Where | Written as | Becomes |
|---|---|---|
| Bare code | `{a}` | `$__formulizeRef_a` |
| Double quoted, or a heredoc | `"x {a}"` | `"x {$__formulizeRef_a}"` |
| Single quoted | `'x {a}'` | `('x ' . $__formulizeRef_a)` |
| Comments, nowdocs, inline HTML | | Left alone |

- **Run authored PHP through `evalAdminPHPWithReferences()`.** The order of its steps matters, so don't assemble them yourself.
- **Never use `formulize_replaceCurlyBracketVariables()` on code.** It's for display text.
- **Decide whether content is code before any references are resolved,** on the admin's original text. Static content and text box defaults both do this, so a stored value that happens to contain `$value=` can't cause an `eval()`.

### Keeping scripts out of the page

The code tracks whether anything got in. Then there are two outcomes:

- **Nothing got in.** No `{handle}` was substituted (`$substitutionHappened`), or no reference was bound (`$__formulizeRefValues` is empty). The content is exactly what the admin wrote, so it's left alone, including buttons, `onclick` and anything else the filter would remove.
- **Something got in.** The whole result is purified, including the admin's own markup around the value.

Filtering the combined result is deliberate. The purifier sees the actual HTML, so a value that lands inside an attribute the admin opened, as in `<div title="{handle}">`, is treated as the attribute it is. A value checked on its own gives no hint of where it will end up.

This has a consequence that can look like a bug: **adding a reference to content can remove markup that was there before.** A block of static content with a button keeps it, until a `{handle}` is added, and then the filter removes it.

For PHP, the check has to be on the code's output. References reach the code as variables, so `return "Hi " . $some_field;` substitutes no text at all, and the check inside the substitution would never fire.

**Derived values have no "nothing got in" case.** A formula's result is stored, and by the time it's displayed nothing can tell what went into it, so it's always purified when shown. Only the display is filtered, and the stored value stays as the formula produced it.

**Template screens** compare instead. Each value set up for the screen is purified, unless `formatDataForList()` has already made it safe. A value the screen's code replaced counts as that code's own output, and is left alone.

### When the result isn't for display

Some callers compute a value, not display text. `interpretTextboxValue()` is the main one: what it returns is also stored as the entry's default, and filtering it would save escaped markup as data. Those callers pass `$makeValuesSafeForDisplay = false`, and whatever shows the value has to make it safe. For a text box, that's `icms_form_elements_Text::render()`.

## How the choice is made

| What's being shown | Treatment | Where |
|---|---|---|
| A plain value in a list | Escape | `formatDataForList()` |
| A value that's meant to be HTML in a list, such as a derived value or rich text | Purify | `formatDataForList()`, when `treatDataAsHtml()` is true |
| A value in an editable field | Escape, exactly once | `icms_form_elements_Text::render()` and `Textarea::render()` |
| A value in a disabled element or the print view | Purify | `makeValueSafeForReadOnlyDisplay()` |
| The label of a linked option, which comes from another form's entries | Escape | The element's render code |
| A caption, help text or static content with no `{reference}` | Leave alone | |
| The same text with a `{reference}` filled in | Purify the whole result | `formulize_replaceCurlyBracketVariables()` |
| Static content or other authored PHP that uses no `{reference}` | Leave alone | |
| Authored PHP that uses a `{reference}` | Purify its output | `evalAdminPHPWithReferences()` |
| Element values and `$entry` given to a template screen | Purify, unless already formatted or changed by the screen's code | `templateScreen.php`, with `formulize_purifyHtmlValueDeep()` |
| What a template screen's own code builds | Leave alone | |

Three rules explain that table:

**Editable means escape, read-only means purify.** Someone editing a value needs to see exactly what they typed, so markup has to appear as text. When nothing is being edited, purifying is safer and shows the same value the way a list does, instead of a wall of tags.

**Authored content stops being trusted once data is mixed into it.** An admin wrote `<li>$name</li>`, but `$name` came from a text box. See [References](#references-how-typed-data-gets-into-authored-content).

**`getValue()` returns the raw value.** Don't treat what it returns as safe for display.

## Elements: `$dataIsHtml`

Each element type's `formatDataForList()` sets `$this->dataIsHtml`, and `formatDataForList()` in `class/elements.php` does the rest, in this order:

1. Escape or purify. Every value goes through this, with no way to opt out.
2. `composeMarkupForList()` wraps the safe value in the element's own markup.
3. Truncate. HTML is never truncated, because `cutString()` would cut tags in half.
4. Make links clickable.

`$dataIsHtml` describes the data, not how the element looks. An element that wraps its value in a link leaves it `false`, and builds the link in `composeMarkupForList()`. That method gets the raw value as well, for looking something up or building an `href`. Escape anything you take from it. Never escape `$value` again in there. An element type that sets nothing is escaped, so a new type is safe by default.

Pass data to `makeValueSafeForReadOnlyDisplay()`, not markup you've already built. The purifier removes `<form>`, `<input>`, `<button>` and `<select>`, so a control would disappear. Make the value safe first, then build around it. For a multi-value element, pass an array and a joiner, so the parts are filtered separately and your joiner isn't.

## Filtering and reporting

Purification follows the **Filter unsafe HTML from displayed data?** preference, `formulizeEnforceHtmlPurification`.

- On, which is the default for a new site, the filtered value is shown.
- Off, the original value is shown, and Formulize logs which elements the filter would have changed. A site upgrading from before this setting starts off, through patch 007, because filtering can remove markup a site relies on. This lets a site see what would change before turning it on.

If the setting can't be read, filtering is on. If the purifier isn't available, or is turned off in the ICMS preferences, the value is escaped instead. Unfiltered HTML is never the fallback.

**Don't log values.** Log the element and entry that a problem is in, because a value could be malicious markup and logs aren't a safe place to display it. `formulize_logPurifyEvent()` is the pattern.

## What the filter keeps

The allow-list is in the ICMS purifier preferences, so a site can change it. The defaults are in `install/makedata.php` and `install/sql/pdo.mysql.formulize_standalone.sql`.

- **`class` survives only on `a`, `div`, `img`, `span`, `table`, `tr`, `th` and `td`.** It's removed from `p`, headings, lists and `dl`.
- **`style`** survives on more elements, but not with `var(--…)` in it.
- **`hidden`, `data-*`, `onclick`, `<script>` and `<style>`** are removed, along with `<button>`, `<form>`, `<input>`, `<iframe>` and `<svg>`.

To style filtered markup, put the `fz-` class on a wrapping `div` or `span`: `<div class="fz-callout__title">`, not `<p class="fz-callout__title">`. That matters for derived values above all. See [formulize-ui](formulize-ui.md).

Adding to the list on existing sites takes a patch. See `009_purifier_allow_hr.php`.

## Testing

The specs `02-validate/033` to `040` cover this, and each names the part of the display path it tests. When you add a place that shows data:

- **Assert on the page, never on the source.** Finding the payload in the HTML proves nothing, because that passes whether it's harmless text or a live element. Check that no dialog fired, that the injected element doesn't exist, and that a flag the payload would set stayed unset.
- **Check both halves:** the attack is gone, and the legitimate content is still there. A filter that removes everything is safe too.
- **Check that a value is escaped exactly once.** `toHaveValue(payload)` does this for an editable field, because a double-escaped value doesn't match.
- **Editable and disabled are different paths.** Test each one.
- **For a reference, test the text path and the PHP path.** They're separate code. `036` covers `{handle}` in captions and help text, `040` covers PHP that uses a referenced value, and `034` covers a derived value.

`tests/normalize_then_escape_test.php` covers the escaping in the form fields, and `tests/reference_binding_test.php` covers rewriting references in PHP, including injection attempts. The specs run with filtering on.
