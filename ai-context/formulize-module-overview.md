# Formulize Module Overview

How Formulize behaves, and why it's organized the way it is. For where things are in the code, and the path of a request, see [architecture-overview](architecture-overview.md).

**Formulize** began as a module within XOOPS CMS and has evolved into a complete database application creation environment. It relies on the underlying XOOPS/ImpressCMS system for user and group management, permissions, templating and session management, and it has grown to be more complex than that foundation.

---

## Core Philosophy: Smart Functions, Not Controllers

### The Controller-Free Architecture

Formulize has no controller layer, no routes and no form handlers to write. This is unusual, and it's easy to go looking for something that isn't there. The design comes from an early PHP instinct: say what page you want, and let the system handle everything else.

**Every page submits back to itself.** A search, a saved form and a change of columns in a list are all the same kind of request, and each one goes through the same steps:

1. **Save:** `readelements.php` saves anything that was submitted.
2. **Gather:** the display function works out what the request is asking for, and gathers the data.
3. **Render:** the page is drawn again, taking into account whatever just happened.

**The display functions do their own controlling.** `initialize.php` only works out which screen or display function to call. The most basic example of the rest is `displayEntries()`, which checks whether the request is asking to open an entry, or is coming back from saving and closing one. It hands off to a form screen or `displayForm()` if it needs to, and otherwise draws the list. There may be a more standard controller at the point of `initialize.php` in future, but not today.

**A working application needs no submission handling or controller logic.** At the PHP level, all it takes is `mainfile.php`, `header.php`, the Formulize `index.php` with a `sid` in `$_GET`, and `footer.php`. Saving, gathering data, checking permissions and re-rendering all come with that.

When you change how a page behaves, look in the display functions and `readelements.php`, not for a route or a controller. A form that's included in a page is saved when the page is submitted, so it doesn't need a save handler of its own.

### Smart Functions

`displayForm()` and `displayEntries()` are where this design comes from, and where most of the work still happens, so reading them is the way to understand how Formulize works and why. Either one can be dropped into any page, and works completely on its own:

```php
<?php
include "mainfile.php";
include XOOPS_ROOT_PATH . "/header.php";

displayForm(12);  // That's it! This is a fully functional form.

include XOOPS_ROOT_PATH . "/footer.php";
```

They check the current user's permissions, read the configuration from the database, handle state through POST, apply validation, control which elements can be seen and edited, and reload themselves in the right state. If a form allows one entry per user, `displayForm()` shows the user's existing entry. If the user can't create entries, the elements are disabled.

**That explains how Formulize works. It isn't how to use it.** Calling these functions directly is anachronistic, and an agent shouldn't write code like the example above. New code goes through a screen's `render()` method, which calls them with the right settings. See [Rendering a screen in code](#rendering-a-screen-in-code).

---

## The Display Functions

Both take a `$formframe` and a `$mainform`, a convention you'll see throughout the older code. `$formframe` is a form id. For a form shown through a relationship, it's the relationship id instead, and `$mainform` is the form id. These are a legacy of the oldest parts of the API, and they can also hold names, handles or titles. To turn them into real ids, use `getFormFramework($formframe, $mainform)`, at the top of `include/functions.php`, which returns `array($fid, $frid)`.

### displayForm()

In `include/formdisplay.php`. It works out which entry and elements to show, loads the saved values, turns each element's configuration into a form element object, applies permissions and conditions, renders through the `formulize_themeForm` class and the screen templates, and writes hidden fields that tell the next request what state the page was in. The functions that do these jobs are listed under `formdisplay.php` in [architecture-overview](architecture-overview.md#inside-modulesformulizeinclude).

### displayEntries()

In `include/entriesdisplay.php`. The function has numbered `STAGE` comments that explain its operation, so read those before changing it. The stages cover deleting and cloning entries, deleting, saving and loading views, working out columns and scope, processing custom buttons, jumping to an entry if one was requested, gathering the data with `getData()`, and drawing the page with `drawInterface()`, `drawEntries()` and the list templates.

---


## Data Saving: readelements.php

**Location:** `include/readelements.php`

**Critical:** this file is included **before** rendering begins, so any submitted form data is saved first.

Because every page submits back to itself, this happens "for free" on every page load. Any form included in a page is simply saved when the page is submitted, with no other code needed. See [The Controller-Free Architecture](#the-controller-free-architecture).

**How It Gets Run:**
- `initialize.php` includes it on every Formulize page.
- `displayForm()` and pages that show forms outside of `initialize.php`, such as `users.php`, `groups.php` and `signup.php`, include it as well, just in case.
- AJAX requests can post to it directly, such as for inline editing in a list of entries, and saves from the drawer.

It only runs once per request, and it should be included from the global scope, because its variables are meant to be global.

**What It Does:**
1. Finds the submitted values in POST. Each one has a `decue_` cue, and element fields are named `de_[form]_[entry]_[elementid]`. They're processed no matter where they appeared on the page.
2. Checks the [security token](security-tokens.md) at the first cue. If the check fails, nothing is saved and the user sees a message saying so.
3. Prepares each value for writing with `prepDataForWrite()`.
4. Writes each new or updated entry with `formulize_writeEntry()`, if the user can edit it. The form's before and after save code runs as part of this.
5. Handles subform entries and one-to-one connections between forms.
6. Updates the derived values of everything that was saved.
7. Applies group memberships for entries-are-users forms. See [entries-are-users-and-groups](entries-are-users-and-groups.md).
8. Sends notifications.

---

## Screens

A **screen** is a configured way to display a form or list. It packages up the settings for calling the display functions, and a form can have many of them.

| Screen | Internal type | Class file |
|---|---|---|
| List of Entries | `listOfEntries` | `listOfEntriesScreen.php` |
| Form, with one or more pages | `multiPage` | `multiPageScreen.php` |
| Legacy Form. Not in use except on very old systems | `form` | `formScreen.php` |
| Calendar | `calendar` | `calendarScreen.php` |
| Map | `map` | `mapScreen.php` |
| Template, a display written in PHP | `template` | `templateScreen.php` |

Each type's handler has a `render()` method that works out the parameters from the screen's settings and calls the display function. `initialize.php` gets the base screen handler, reads the screen's type, and uses that type's handler:

```php
$screen_handler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
$screen = $screen_handler->get($screen_id);
$screen_handler->render($screen, $entry_id, $loadThisView);
```

See [Screen Templates](architecture-overview.md#screen-templates) for how their markup is found.

### Rendering a screen in code

A screen doesn't have to be one that's saved in the database. You can build a screen object in code, and render whatever you need. It has to have a value for each of its settings, so start from the defaults, then change the ones you want. `modules/formulize/users.php` and `groups.php` do this for their lists of users and groups.

```php
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($fid);

$screen_handler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
$screen = $screen_handler->create();
$screen_handler->setDefaultListScreenVars($screen, $viewEntryScreenId, $formObject);

$screen->setVar('title', _MY_FEATURE_LIST_TITLE);
$screen->setVar('frid', 0);       // the default is the Primary Relationship
$screen->setVar('useclone', '');  // empty text removes a button
$screen->setVar('hiddencolumns', array('some_handle'));

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/readelements.php';
$screen_handler->render($screen, '', '');
```

- **Use the handler for the screen's type,** so that `create()` gives you the right class. Don't call `insert()`, and the screen exists only for this request.
- **Include `readelements.php` before rendering.** `initialize.php` does this for ordinary pages, and on your own page nothing is saved without it.
- **The defaults come from `setDefaultListScreenVars($screen, $viewEntryScreenId, $formObject)` and `setDefaultFormScreenVars($screen, $formObject)`.** They're what a new form's default screens are set up with. Template, calendar and map screens have `setDefaultScreenVars($screen, $formObject)`. Legacy form screens have none, because no new ones are made. The settings with fixed values come from the site theme's `screen-defaults.json`, so read that file to see what's available to change, and set explicitly any value your code depends on, such as `editdestination`, because another theme may start it differently.
- **A form screen's defaults are one empty page,** so set `pages` and `pagetitles` as well.
- **A form screen's buttons are switched on and off by `showprevbutton`, `showsavebutton`, `showclosebutton` and `shownextbutton`,** and its tabs by `showtabs`. A blank label in `buttontext` gets the standard text, so it doesn't hide anything. `edituser.php` and `signup.php` show how to leave only the Save button.
- **Template screens are the exception.** Their code may call `displayForm()` directly, and `templateScreen.php` allows for that, but a screen's `render()` method is preferred.
- **`render()` takes different parameters for each type.** A list takes `($screen, $entry, $loadThisView)`, and a form takes `($screen, $entry, $settings, $elements_only)`.

---

## Data Extraction: extract.php

**Location:** `include/extract.php`

`gatherDataset()` is the way to get data out of Formulize forms. It calls `getData()`, which calls `dataExtraction()`, the fundamental function that retrieves the data. This is the universal data extraction layer, used by lists of entries, the Public API and the MCP tools, among others, and it's very fundamental to keeping the system consistent.

It retrieves data based on whatever filtering, searching and joins are specified, by the user and by the configuration of the screen.

**Access Rules:** it enforces these in two ways, either or both of which can apply:
- **Scope:** the `$scope` parameter limits which entries are included. `buildScope()` generates it, based on the user's permissions.
- **Group filters:** the conditions on entry access for specific groups, set in the permission system. `dataExtraction()` applies these itself, for each form in the query.

An empty scope means no scope limit, so always set one when the data is going to a user. `buildScope()` returns two values, the scope and the current view, so take the scope out of what it returns. Passing the whole return value treats it as a list of groups, and the wrong entries come back:

```php
list($scope) = buildScope('group', $xoopsUser, $fid);
$data = gatherDataset($fid, scope: $scope);
```

Use `getValue()` and `getEntryIds()` to work with the result.

**Two ways to build the query.** When the main form has one connected form or none, `dataExtraction()` runs one query, with every sort and filter in it. When it has more than one (`count($linkformids) > 1`), it puts the main form's entry ids into a temporary table, `formulize_temp_extract_<timestamp>`, in sorted order, then runs one query for each connected form, joined to that table and ordered by its `mastersort` column. Anything the temporary table can't hold, such as a sort on a connected form's field, has to be added to each connected form's own query, or it's lost. Adding a connection to a form can switch its lists from one way to the other, so a list can behave differently with no change to the list itself. To see which way a list takes, log the queries. See [Trace a Query](coding-guidelines.md#trace-a-query).

---

## State Management Through HTTP POST

Formulize maintains UI state through hidden form fields and POST requests. This is how clicking buttons, changing filters and sorting columns all work without JavaScript-based state management. `writeHiddenSettings()` writes the fields for forms, and each request reads them back to reconstruct its state.

Users can also save their column, search and filter preferences as **saved views**, in the `formulize_saved_views` table, through `formulizeSavedViewsHandler` in `class/savedViews.php`. When a view is loaded, its settings override the default screen configuration.

---

## Permissions System

Formulize uses the XOOPS group permission system. Note how [webmasters bypass it](webmaster-permission-bypass.md).

### Form-Level Permissions

```php
$gperm_handler = xoops_gethandler('groupperm');
$groups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
$mid = getFormulizeModId();

$can_view = $gperm_handler->checkRight("view_form", $form_id, $groups, $mid);
$can_add = $gperm_handler->checkRight("add_own_entry", $form_id, $groups, $mid);

// Scope permissions
$view_globalscope = $gperm_handler->checkRight("view_globalscope", $form_id, $groups, $mid);
$view_groupscope = $gperm_handler->checkRight("view_groupscope", $form_id, $groups, $mid);
```

### Entry-Level Permissions

```php
// Can this user see this entry? In include/functions.php
if (security_check($form_id, $entry_id)) { }

// Can this user edit or delete it?
if (formulizePermHandler::user_can_edit_entry($form_id, $user_id, $entry_id)) { }
if (formulizePermHandler::user_can_delete_entry($form_id, $user_id, $entry_id)) { }
```

### Element-Level Permissions

Elements can have individual permissions controlling visibility and editability per entry.

---

## Advanced Features

- **Relationships (frameworks)** describe how forms connect, from the point of view of an entry in a form. They aren't SQL joins, and there is no many-to-many. See [relationships](relationships.md).
- **Custom buttons** on list screens update values in selected entries, run custom PHP code, navigate to other screens, or send notifications.
- **Derived elements** hold values calculated from other elements by custom PHP code. See [derived-values-and-custom-code](derived-values-and-custom-code.md).
- **Subform elements** display entries from another form inline within a parent form.
- **Conditions** decide which pages and elements are shown, and much more. See [conditions](conditions.md).

---

## Using Formulize from your own PHP

To show something that no saved screen covers, see [Rendering a screen in code](#rendering-a-screen-in-code).

### Display a Saved Screen

```php
<?php
include "mainfile.php";
$formulize_screen_id = 45;  // Set BEFORE including index.php
include XOOPS_ROOT_PATH . "/modules/formulize/index.php";
// index.php won't include footer.php when $formulize_screen_id is set
```

`index.php` skips `mainfile.php` and `footer.php` when `$formulize_screen_id` is set, so that a screen can be included in another page.

### Embed in a Block

```php
<?php
$formulize_screen_id = 45;
ob_start();
include XOOPS_ROOT_PATH . "/modules/formulize/index.php";
$content = ob_get_clean();
return $content;
```

### Embed in Another Website

To show a screen on a site that isn't running Formulize, see [embedding-screens](embedding-screens.md).

---

## Template Registration

**When creating new Smarty template files in Formulize**, you must register them with the other template registrations in `modules/formulize/xoops_version.php`. PHP screen templates are not registered.

```php
$modversion['templates'][] = array(
	'file' => 'admin/your_new_template.html',
	'description' => '');
```

XOOPS/ImpressCMS reads the templates into the database from this list, so Smarty can't find one that isn't on it.
