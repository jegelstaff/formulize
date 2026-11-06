# Derived values and custom code

People write PHP in Formulize: derived value formulas, form procedures, code in text and static content elements, and application code. That code is kept in files, not the database.

## Where the code lives

Everything here is in `modules/formulize/code/`, which holds only `index.html` in the repository. Each site's files are its own.

| What | File |
|---|---|
| Derived value formula | `derived_<handle>.php` |
| Code in a text, textarea, number or static content element | `<ele_type>_<handle>.php` |
| Form procedures | `on_before_save_<form handle>.php`, `on_after_save_<form handle>.php`, `on_delete_<form handle>.php`, `custom_edit_check_<form handle>.php` |
| Application code, which runs on every page of the application | `application_custom_code_<application id>.php` |

A template screen's code is different. It's in `templates/screens/<theme>/<sid>/code.php`, with the screen's other templates.

**The file is the source of truth.** `getVar()` reads the file, and saving writes it through `formulize_writeCodeToFile()`. For elements, a copy can still be in `ele_value`, which `getVar()` uses only when there's no file. Code can be in `ele_value` with no file, for example after `cloneForm()`, which copies element rows and no files. That's why a handler's `clearEleValueFileKeysForInsert` only blanks the database copy when `codeFileHasContentForElement()` says the file holds it. Otherwise a save that had nothing to do with the code would destroy the only copy.

**Files arrive by routes Formulize knows nothing about,** such as a deploy or a restored backup. So form procedures aren't cached by file date. The wrapper Formulize generates around a procedure is named with a hash of its source, in the ICMS cache folder, so a change of source can't run stale code. See `formulizeForm::procedure_cache_filename()`.

**Renaming an element rewrites these files.** See `formulizeElementsHandler::handleRewritesInFiles()` and [element-references](element-references.md).

**The code and the database belong together.** Without its code files, a database from a site that went through the 8.2 migration falls back to the old formula text in `ele_value`, in which element handles are quoted. They run as literal strings, so a save writes the handle names into the derived columns. When entries show handle names as values, or seem to disappear from a sorted list, look for missing `derived_*.php` files before suspecting the save. Test a site's data with that site's code folder. See also [update patches](update-patches.md#when-a-patch-cant-simply-be-run-again).

## How a derived value is stored

A derived value is written to the entry's column in the data table, like any other value. Lists read that stored value, and nothing calculates it when a list is drawn. So **a changed formula changes nothing on screen until the values are recomputed.** The exception is the `debugDerivedValues` module preference, which recomputes on every query.

Values are only calculated while `$GLOBALS['formulize_forceDerivedValueUpdate']` is set. If the new value matches the stored one, nothing is written. Writes skip the entry's modification date and user.

`gatherDataset()` won't derive anything when it's given a list of elements. The entries it returns are incomplete, and a formula would calculate from missing values and save the result.

### Recomputing

- **Saves:** `readelements.php` recomputes what it wrote. Code that writes entries itself has to call `formulize_updateDerivedValues()`, because `formulize_writeEntry()` doesn't. See [Writing Entries](database-patterns.md#writing-entries).
- **In code:** `formulize_updateDerivedValues($entry_id_or_filter, $fid, $frid)`.
- **For a whole form:** the derived element's admin page has an update button. It calls the `update_derived_value` op in `formulize_xhr_responder.php` in batches of 50, until a batch comes back empty.
- **After a default value is applied:** `formulize_getFormsWithDependentDerivedValues()` finds the forms with formulas that refer to an element, for the same batched update.

A recompute through a relationship also recomputes every derived value in the connected forms, so it runs every one of those formulas. One that fatals stops the whole batch.

## How a formula runs

`formulize_includeDerivedValueFormulas()` in `include/extract.php` wraps each formula in a function, in `modules/formulize/cache/Derived_value_formula_for_*.php`. It writes the file when a request needs it, and clears files more than an hour old, so don't edit them.

- A formula sets `$value`, which starts as the stored value.
- **A variable named after an element handle or a metadata field is filled in for you,** from the entry, with `getValue()`. Any other variable is an ordinary PHP variable. `$value`, `$entry`, `$form_id`, `$entry_id` and `$relationship_id` are reserved.
- A formula with no semicolons at all gets one added to each line.
- A formula's output is HTML, and it's purified when shown. See [xss-and-purification](xss-and-purification.md).

Don't write markup into a formula that the purifier will remove. See [what the filter keeps](xss-and-purification.md#what-the-filter-keeps).

Tests: `tests/element_code_files_test.php` and `02-validate/041-static-content-code-files`.
