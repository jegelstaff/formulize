# Plan: Ensure entries-are-groups forms always have a PI set

Addresses the long-standing TODO ("ensure forms with entries_are_groups always have a PI set")
that was noted in `readelements.php` but never implemented. Entry-group names for an
entries_are_groups (EAG) form are built as `"{PI value} - {Category name}"`, so an EAG form with
no PI (or a PI pointing at a deleted element) cannot create or rename its entry groups —
`syncEntryGroups()` bails out at the "no PI value" / "no PI element" guards and the form is
effectively broken.

## Current state — a PI can be cleared three ways on an EAG form

1. **Settings dropdown → "None".** `primary_identifier_selection.html` renders
   `<option value="">…None…</option>`. Selecting it submits `forms-pi=""`, which is applied via
   `$formObject->setVar('pi', '')` in `formulizeHandler::upsertFormSchemaAndResources()`
   (`modules/formulize/class/formulize.php:341`).
2. **Un-checking "principal identifier"** on the element names page →
   `$formObject->setVar('pi', 0)` (`modules/formulize/admin/save/element_names_save.php:112`).
3. **Deleting the PI element** → element row + data column removed, then
   `$formObject->setVar('pi', 0)` (`modules/formulize/class/elements.php:1120`).

`forms-pi` is persisted through `setVar('pi', …)`, so cases 1 and 2 share a single chokepoint:
the form object's `setVar` override (`modules/formulize/class/forms.php`).

## Goal (invariant)

**An entries_are_groups form always references a valid, existing PI element.**

## Design constraints already established

- `formulizeForm::setVar` is the one chokepoint every PI designation/clearing flows through
  (admin settings dropdown, element names page, upsert, MCP, relationship options). Object
  hydration from the DB uses `assignVar()`, **not** `setVar()`, so guards added to `setVar` never
  fire on load.
- A naive "refuse `pi=0`" guard alone is **not safe for case 3**: on deletion the element is
  already gone by the time `setVar('pi', 0)` runs, so refusing the clear would leave `pi` pointing
  at a **deleted** element — a dangling reference worse than the current "cleanly set to 0".
  Therefore the delete path needs its own guard so it never tries to clear the PI on an EAG form.
- Related, already-completed work this builds on: EAG PI elements are now forced
  `ele_required = 1` (in `formulizeForm::setVar` when `pi` is set on an EAG form), and hyphens in
  EAG PI values are recast to underscores at write time (`formulizeDataHandler::writeEntry`).

## Plan — three guards

### 1. Backend safety net — `setVar` guard (blocks cases 1 & 2)
**File:** `modules/formulize/class/forms.php` (`formulizeForm::setVar`).

At the top of `setVar`, before `parent::setVar`, refuse to clear the PI on an EAG form:

```php
// Do not allow clearing the PI on an entries_are_groups form — its entry-group names depend on it.
if ("pi" == $key AND intval($value) < 1 AND $this->getVar('entries_are_groups')) {
    return; // leave the existing PI in place
}
```

- Silently ignores the clear (leaves `pi` unchanged); the subsequent `$form_handler->insert()`
  persists the form with the old PI intact.
- Covers the settings dropdown "None" and the names-page un-designation.
- Benign edge case: disabling EAG **and** clearing the PI in the same save is order-dependent on
  which property `setVar` receives first; a non-EAG form keeping a stale-but-valid PI is harmless.

### 2. Delete guard (closes case 3 — required so guard #1 doesn't regress deletion)
**File:** `modules/formulize/class/elements.php` (`formulizeElementsHandler::delete`).

At the very top of `delete()` (before any row/column removal), refuse to delete an element that is
the PI of an EAG form:

```php
$form_handler = xoops_getmodulehandler('forms', 'formulize');
if (($parentForm = $form_handler->get($elementObject->getVar('fid')))
    AND $parentForm->getVar('entries_are_groups')
    AND $elementObject->getVar('ele_id') == $parentForm->getVar('pi')) {
    print "Error: cannot delete the principal identifier element of an entries-are-groups form; its entry group names depend on it. Designate a different PI first, or disable entries-are-groups.";
    return false;
}
```

- Uses `print` + `return false`, matching the existing error-reporting style in `delete()`.
- Because deletion is blocked up front, the `setVar('pi', 0)` cleanup at `elements.php:1120` is
  never reached for an EAG PI, so guard #1 cannot produce a dangling reference.

### 3. UI polish — hide "None" for EAG forms
**File:** `modules/formulize/templates/admin/primary_identifier_selection.html`.

Wrap the `None` option so it is not offered when the form is EAG:

```smarty
<{if !$content.entries_are_groups}>
    <option value=""><{$smarty.const._AM_SETTINGS_FORM_PI_NONE}></option>
<{/if}>
```

- `entries_are_groups` is assigned into the settings content in `modules/formulize/admin/form.php`
  (line ~203/990) alongside `pioptions`/`defaultpi`; confirm it is reachable as
  `$content.entries_are_groups` inside this included template before relying on it. If it is not,
  pass it through explicitly from `form_settings.html`'s include.
- This is UX only; the backend guards (#1, #2) remain the authoritative enforcement.

## Open decision (confirm before implementing)

The **delete guard (#2)** is a behavior change beyond the originally proposed two fixes: an admin
who tries to delete the PI field of an EAG form is blocked and must re-designate a PI (or disable
EAG) first. Options:

- **(A) Block deletion** (this plan) — simplest, preserves the invariant, clear error message.
- **(B) Allow deletion but auto-pick another element as the new PI** — more convenient, but the
  auto-choice may be surprising and there may be no other suitable element.
- **(C) Leave deletion as-is** — then case 3 can still clear the PI, and guard #1 must be made
  aware of the delete-cleanup path to avoid the dangling reference. Not recommended.

Default recommendation: **(A)**.

## Verification

- `php -l` on each edited file (`forms.php`, `elements.php`).
- Trace the three clearing paths to confirm each is blocked / not offered.
- e2e (`005-create-users-and-groups.spec.js`) considerations — extend Block L (or add a block) to:
  - assert the settings PI dropdown has no "None" option for the Departments (EAG) form;
  - attempt to delete the Departments PI ("Name") element and assert it is refused and the PI is
    unchanged;
  - (optional) assert the Departments PI element is `required`.
- Note: guard #2 changes deletion behavior for EAG PI elements — check no existing test deletes an
  EAG PI element as part of its flow.

## Touched files summary

- `modules/formulize/class/forms.php` — setVar guard (block clearing PI on EAG).
- `modules/formulize/class/elements.php` — delete guard (block deleting an EAG PI element).
- `modules/formulize/templates/admin/primary_identifier_selection.html` — hide "None" for EAG.
- `tests/e2e/formulize-core/setup/005-create-users-and-groups.spec.js` — coverage (optional but recommended).
