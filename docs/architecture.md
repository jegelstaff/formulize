---
layout: default
permalink: documentation/architecture/
title: How Formulize Is Built
description: How a Formulize application fits together, what you write, what you don't, and where your code goes, for developers coming from a web framework.
---

# How Formulize is built

If you've come from a web framework, Formulize won't look the way you expect. There are no routes to
define, no controllers, and no submit handlers. You configure the application, and the system runs it.

This page explains what that means in practice: what you write, what you get without writing it, and
where your code goes when you want something different. For a file-by-file walk through a request,
see [The flow through the code](../code_flow/).

## An application is configuration

A Formulize application is defined by settings stored in the database, not by code written for that
application. These are the parts you configure:

| Part | What it is |
|---|---|
| Form | A set of data. Each form has its own database table. |
| Element | A field in a form. Each element that stores data is a column in that table. |
| Relationship | The connections between forms, described from the point of view of an entry. See [How forms connect](/documentation/relationships/). |
| Screen | A configured way of showing a form: as a form, a list of entries, a calendar, a map, or a template of your own. A form can have many screens. |
| Permissions | What each group can do with each form and its entries, down to conditions on individual entries. |
| Application | A set of forms and screens, with a menu. |

The rules of the application live in these settings: which elements appear and when, who can see which
entries, and what happens when an entry is saved. Every page follows them, so you don't have to
enforce them again in each place.

## There are no controllers

Every Formulize page submits back to itself. A search, a saved form and a change of columns in a list
are all the same kind of request, and each one goes through the same steps:

1. **Save.** Anything that was submitted is saved.
2. **Gather.** Formulize works out what the request is asking for, and gathers the data.
3. **Render.** The page is drawn again, reflecting whatever just happened.

So a complete working application needs one address:

```
/modules/formulize/index.php?sid=10
```

That's screen 10, with saving, searching, permissions and navigation all working. If you want a
screen in a page of your own, this is the whole page:

```php
<?php
include "mainfile.php";

$formulize_screen_id = 10;
include XOOPS_ROOT_PATH . "/modules/formulize/index.php";

include XOOPS_ROOT_PATH . "/footer.php";
```

| You don't write | Because |
|---|---|
| A route for each page | The `sid` in the address says which screen to show. |
| A submit handler | Any Formulize form on a page is saved when the page is submitted. |
| Permission checks | Screens, forms and data retrieval check them for the current user. |
| Validation and conditional logic | They come from the configuration of the elements. |
| Code to recalculate values | Derived values and notifications are handled when entries are saved. |
| Code to pass state between pages | State is posted with each request, and the page picks up where it left off. |

## How a request runs

There's no separate controller, so the logic you'd normally look for there is in the display
functions. `displayEntries()`, which draws a list, also checks whether the request is asking to open
an entry, or is coming back from saving one. If it needs to, it hands off to a form screen or
`displayForm()`.

1. **`index.php`** starts the system and the theme, and includes `initialize.php`.
2. **`initialize.php`** works out which screen to use, and checks access.
3. **`readelements.php`** saves anything that was submitted.
4. **The screen's `render()` method** turns the screen's settings into parameters for a display function.
5. **The display functions**, such as `displayEntries()`, `displayFormPages()` and `displayForm()`,
   gather the data and work out the values for the page.
6. **The templates** turn those values into markup.
7. **`footer.php`** puts the page in the theme and sends it.

When you're working out why a page behaves as it does, look in the display functions in
`modules/formulize/include/`, and in `readelements.php` in the same folder.

Reading those functions is the way to understand how Formulize works, but don't call them from your
own code. They're what a screen hands off to, so go through a screen. It doesn't have to be a saved
one: you can create a screen object in code, set it up with the default settings, change the ones
you want, and call its `render()` method. Formulize's own pages for managing users and groups do
this, in `modules/formulize/users.php` and `groups.php`.

## Saving data

`readelements.php` runs on every page load, before anything is drawn. It finds the submitted values for
every Formulize element on the page, wherever they appeared, and checks the security token. Then it
writes the entries the user is allowed to edit, updates derived values, and sends notifications.

This is why a form in a page doesn't need a handler of its own. To save entries from your own code
instead, use [`formulize_writeEntry()`](../functions/formulize_writeEntry/). It records the groups
that own a new entry, which the data handler's own `writeEntry()` doesn't do. It doesn't check
permissions or update derived values, so do those yourself:

```php
$entryId = formulize_writeEntry(array('first_name' => 'Ada'), 'new');
formulize_updateDerivedValues($entryId, $fid);
```

## Reading data

`gatherDataset()` is how you get data out of forms. Lists of entries, the Public API and the AI tools
all use the same layer underneath, so you get the same results, with the same joins and access rules.

```php
list($scope) = buildScope('group', $xoopsUser, $fid);
$data = gatherDataset($fid, scope: $scope);

foreach($data as $entry) {
    $firstName = getValue($entry, 'first_name');
}
```

Access rules apply in two ways:

- **Scope** limits the entries to the ones the user can see. Create it with
  [`buildScope()`](../functions/buildScope/).
- **Group filters** are the conditions on entry access set in the form's permissions. These always
  apply.

If you leave out the scope, there's no scope limit, so always pass one when the data is going to a user.

See [`gatherDataset()`](../functions/gatherDataset/) for filtering, sorting and choosing which forms to
include, and [`getValue()`](../functions/getValue/) for working with the results.

## Where your code goes

Configuration covers a lot, but it isn't the limit. When you want something it doesn't offer, this is
where to do it:

| To do this | Use this |
|---|---|
| Change how a list or form is laid out | The screen's [templates](../templates/). They're PHP, and the values for the page are available as ordinary variables. |
| Change how the whole site looks | The [Appearance editor](../appearance_editor/), or [your own theme](../themes/). |
| Style anything users see | The [Formulize UI](../formulize_ui/) classes and tokens. Templates and themes are built with them. |
| Build a page that isn't a list or a form | A template screen. It's yours to fill with whatever you need. To show a form or list inside it, call a screen's `render()` method. Template screens may also call `displayForm()` directly, but `render()` is preferred. |
| Calculate a value from other values | A derived value element. |
| Run code when an entry is saved | The form's custom code that runs before and after saving. |
| Add a new kind of field | [Your own element class](../custom_form_elements/). |
| Keep configuration in version control | [Configuration as code](../configuration_as_code/). |

Formulize runs on a fork of ImpressCMS, which is part of the project. The code in `libraries/icms/` is
yours to change like any other.

## Using Formulize from somewhere else

Pick the option that matches what you're trying to do:

| You want to | Use this |
|---|---|
| Have people use a form or list on another website | [Embedding](../embedding_screens/). The screen works as it does in Formulize: conditional elements, subforms, multi-page navigation and saving. |
| Read data into another system | The [Public API](../Public_API/). |
| Connect an AI assistant | The [MCP server](../../ai/mcp-reference/). |

The API gives you data, but not the behaviour around it. Conditional elements, multi-page navigation
and the rest of how a form behaves are part of its screens. If people will be filling in a form,
embed the screen rather than rebuilding it on top of the API.

## Next steps

- [Set up a local development environment](../deploying_locally/).
- Read [The flow through the code](../code_flow/) to follow a request file by file.
- Browse the [functions](../functions/) and [classes](../classes/) you can use in your own code.
