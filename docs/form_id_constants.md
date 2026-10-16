---
layout: default
permalink: documentation/form_id_constants/
redirect_from:
 - developers/form_id_constants/
 - developers/API/form_id_constants/
title: Form ID constants
---

# Form ID constants

## Description

Every form has a PHP constant that holds its id number, named after the form's handle, such as ORDERS_FORM_ID for a form with the handle ```orders```. Use these constants in your code wherever a function or method asks for a form id.

The constant's name is the form's handle in capital letters, followed by `_FORM_ID`:

| Form handle | Constant |
|---|---|
| `orders` | `ORDERS_FORM_ID` |
| `fruit` | `FRUIT_FORM_ID` |
| `workshop_bookings` | `WORKSHOP_BOOKINGS_FORM_ID` |

With the constants, your code says which form it means, instead of using a number that only makes sense if you look it up.

~~~php
// hard to read: which form is 11?
$data = gatherDataset(11);

// clear: the Orders form
$data = gatherDataset(ORDERS_FORM_ID);
~~~

## Where they are available

The constants are created at the start of every page load, so you can use them anywhere you can write PHP in Formulize. That includes derived value formulas, the On Before Save and On After Save procedures, application code, screen templates, and your own PHP files that bootstrap the system with _mainfile.php_.

There is a constant for every form, whether or not the current user has permission to see it. The constant only gives you the id number. Permission checks still apply when you read or write data.

## Finding a form's handle

The form handle is shown on the _Settings_ tab of the form in the admin side of Formulize, in the field labelled _Form handle_. If you use the MCP server, the `list_forms` tool also shows each form's handle.

Form handles only contain lowercase letters, numbers and underscores. When a handle is saved, Formulize turns spaces and hyphens into underscores and removes any other characters. So the constant name is always the handle you see on the _Settings_ tab, in capital letters, plus `_FORM_ID`.

## Examples

~~~php
// gather all the orders, plus connected forms in the Primary Relationship
$data = gatherDataset(ORDERS_FORM_ID);
~~~

~~~php
// work with the entries in the Fruit form directly
$dataHandler = new formulizeDataHandler(FRUIT_FORM_ID);
$entryIds = $dataHandler->findAllEntriesWithValue('fruit_name', 'Strawberries');
~~~

~~~php
// limit a dataset to certain elements from two connected forms
// the keys of the array are form ids
$elementHandles = array(
	FRUIT_FORM_ID => array('fruit_name'),
	ORDERS_FORM_ID => array('order_date')
);
$data = gatherDataset(FRUIT_FORM_ID, $elementHandles);
~~~

~~~php
// check whether the current user can see the Orders form
if (security_check(ORDERS_FORM_ID)) {
	print "You can see the orders";
}
~~~

## Things to watch for

__If you change a form's handle, the constant's name changes too.__ Code that still uses the old name will stop with an "Undefined constant" error. After changing a handle, search your code for the old constant name and update it.

__A handle that starts with a number makes a constant that PHP can't read by name.__ For example, the handle `2026_survey` makes the constant `2026_SURVEY_FORM_ID`. Read it with the `constant` function instead:

~~~php
$surveyFormId = constant('2026_SURVEY_FORM_ID');
~~~

__A form created during the current page load has no constant until the next page load.__ If your code creates a form and then works with it straight away, use the id number that you got back when you created the form.

__To check whether a form exists__, use PHP's `defined` function:

~~~php
if (defined('ORDERS_FORM_ID')) {
	$data = gatherDataset(ORDERS_FORM_ID);
}
~~~
