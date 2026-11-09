# Testing users, groups and accounts

What specs need to know about entries-are-users forms, `groups.php` and the user account elements. These come mostly from `01-setup/005-create-users-and-groups` and `006-validate-useraccount-rules`. For what these forms are, see [entries-are-users-and-groups](entries-are-users-and-groups.md).

## Filling in an entries-are-users form

The Staff form gets its user account elements when `entries_are_users` is turned on:

| Element caption | ARIA label | `userProperty` → DB column | Notes |
|---|---|---|---|
| Username | "Username *" | `login_name` | The login credential, NOT `uname` |
| Password | "Password *" | `pass` (hashed) | |
| Email Address | "Email Address *" | `email` | |
| Full Name | (derived, read-only) | computed from First + Last | NOT a textbox, do not fill it |
| First Name | "First Name *" | `uname` (first part) | |
| Last Name | "Last Name *" | `uname` (last part) | |

So First Name "Curator" and Last Name "One" gives `uname = 'Curator One'`. See [The name columns in the users table](entries-are-users-and-groups.md#the-name-columns-in-the-users-table).

Formulize also creates a plain text "Login name" element, separate from the Username element. It's the form's principal identifier, shown in the list of entries. **Both "Username" and "Login name" must be filled** when creating a Staff entry.

**Required elements have `*` in their ARIA label,** so `{ exact: true }` won't find them. Use substring matching for textboxes:

```javascript
await page.getByRole('textbox', { name: 'Username' }).fill(value);
```

When writing these tests, check the `userProperty` of each element before deciding what to fill, and which column gets set.

User account values aren't in the form's data table. Username, email, password and phone live in `users` and `profile_profile`, and the form stores only the uid.

## What groups.php shows

- **Flat groups:** one `<tr class='entry-row'>` each. The Members column shows `uname`, the full name, not `login_name`.
- **Template groups:** one merged row per entries-are-groups form, not one per category. The name cell has `<a class='template-group-form-name'>` with the categories in `<ul class='main-cell-list'>`. The Members column shows counts per entry, such as "Ancient History — 3 members", never names.
- **Entry groups** are not shown. The `formulize_compositeDataMode = 'groups'` filter leaves them out.

The table's `<th scope='col'>` header cells make Playwright split it into two ARIA rowgroups. Use `page.getByRole('rowgroup').last()` for the data rows.

To verify entry group membership, open the user's entry and look at the group tags:

```javascript
await page.goto(`/modules/formulize/master.php?fid=${staffFid}`);
await page.getByRole('row', { name: /ahstaff/ }).getByRole('link').first().click();
const groupTags = page.locator(`p.auto_multi_formulize_user_account_groupmembership_${staffFid}`);
await expect(groupTags.filter({ hasText: 'Ancient History - Staff' })).toBeVisible();
```

The system groups admin page (`/modules/system/admin.php?fct=groups`) lists every group, including entry groups, by their real names, so it's useful for asserting on group names.

## Which memberships can be removed

With the conditions the suite builds, every automatically assigned membership is enforced, so none of the suite's four users has a Remove button in their groups. See [Enforced memberships](entries-are-users-and-groups.md#enforced-memberships).

To test a removable member, create a membership that isn't enforced: add `ahstaff` by hand to All Curators through the "Add Members" tab. `ahstaff` then has a Remove button and `curator1` doesn't. Remove `ahstaff` afterwards to restore the state.

## Save buttons on form screens

`formulize_multipageButtonSet()` decides the buttons. These are the default labels for a single-page screen, and a screen can set its own:

| Button | Label | Action |
|---|---|---|
| `savePageButton` | "Save" | Saves and reloads the page, staying on the entry |
| `nextPageButton` | "Save and Finish" | Saves and returns to the list |
| `previousPageButton` | "Save and Close" | Saves and returns to the list |
| `closePageButton` | "Close" | Closes without saving |

From the form's list page (`master.php?fid=...`):

- `#formulize_addButton` calls `addNew('single')`, which goes straight to `displayForm()`, not the form screen. The button is "Save".
- A row's view link posts `ventry`, which goes through the form's `defaultform` screen. The buttons are "Save" and "Save and Finish".

So `saveFormulizeForm(page, 'Save')` works in both.

## Running part of 005

The later tests in `01-setup/005` read form ids from a `phase1` object that earlier tests in the file fill in, so running one with `-g` gives `fid=null`. Run the whole file.

## Admin details

- `#entries_are_users_default_groups_container` starts as `display: none` on the Settings tab, and JavaScript shows it. Wait for it to be visible before clicking the toggles inside it.
- In the screen admin's page dialog, the element list is `#dialog-page-settings-content select[multiple]`. The close button is `.ui-dialog:has(#dialog-page-settings-content) .ui-dialog-titlebar-close`. A new screen starts with one empty page.

## userAccount validation

`006-validate-useraccount-rules.spec.js` opens the Add Staff dialog, enters one invalid state, clicks Save and asserts the `window.alert()`. Validation fails, so no entry is created and there's nothing to tidy up. The spec finds the form id from the "Staff" link on `/modules/formulize/`, rather than hardcoding it.

**Validation runs in form-field order:** First Name, Last Name, Username (required, then unique), Password (required, then match), the Email/Phone block (at least one, email format, 10-digit phone, email unique, phone unique), Login name, then the 2FA, notification, timezone and status selects.

- To test one validator, every field ahead of it must hold a valid value.
- Nothing validates unless `formulizechanged` is truthy, and filling any field sets it.

**Every alert fires on one Save click.** The uniqueness checks (`check_for_unique_value`) send a request, return false, and resubmit when the response arrives, so the alert appears a moment after the click. Register a `page.on('dialog', ...)` recorder, click Save once, and use `expect.poll()` on the messages. Don't use `saveFormulizeForm()` for failing cases, because its wait for the saving message never completes.

**The alert strings:**

- `Please enter a password for the account.`
- `The passwords do not match. Please try again.`
- `The value you entered for 'Username' has been entered already.  You must type a unique value.`
- `The value you entered for 'email address' has been entered already.  You must type a unique value.`
- `The email address you have entered is not valid.`
- `Please enter a phone number with 10 digits, ie: XXX-XXX-XXXX`, built from the configured phone format
- `Please enter either an email address or a phone number.`

**Uniqueness is enforced in PHP.** `check_for_unique_value` in `formulize_xhr_responder.php` queries `users` and `profile_profile` directly. It checks `login_name`, `email` and `2faphone`. `userAccountUsername::$userProperty` is `login_name`, so checking `users.uname` would never match. Don't rely on the `UNIQUE` index on `login_name`, because the schema can drift between installs. `uname` has no uniqueness check, on purpose: users may share a display name.
