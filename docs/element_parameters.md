---
layout: default
permalink: documentation/element_parameters/
title: Declaring parameters that take elements
---

# Declaring parameters that take elements

When a function or method in Formulize takes an element as one of its parameters, by its handle, its id
or the element object, its docblock says so with an `@formulizeElement` line. Anyone adding a function
or method like that, person or AI, should add the line too.

Formulize reads these lines. When someone renames an element, it updates the handle wherever code passes
it, as a quoted string, to a parameter declared this way: in custom code files, formulas, save
procedures, screen templates and code kept in the database. A parameter without the line is invisible to
this, so after a rename, code that calls it with the old handle is left calling it with a handle that no
longer exists.

## How to write it

Put one line for each parameter after the `@param` lines, naming the parameter and the shape the
elements take in it:

```php
/**
 * Retrieve data from forms, based on their connections ...
 * @param int fid - The id number of the main form ...
 * @param array elementHandles - Optional. An array of arrays, that defines the form elements to include ...
 * @param string sortField - Optional. The element that should be used to sort the entries ...
 * @return array Returns the dataset as an array ...
 * @formulizeElement $elementHandles lists
 * @formulizeElement $sortField commas
 */
function gatherDataset($fid, $elementHandles = array(), ..., $sortField = "", ...) {
```

The shapes are:

| Shape | The argument is | For example |
|---|---|---|
| `one` | one element | `display($entry, 'year')` |
| `commas` | a string of elements separated by commas | `gatherDataset(5, sortField: 'year, price')` |
| `list` | an array of elements | `array('year', 'price')` |
| `lists` | an array of arrays of elements, keyed by form id | `gatherDataset(5, array(5 => array('year', 'price')))` |
| `keys` | an array whose keys are elements | `formulize_writeEntry(array('year' => 1990))` |
| `map` | an array whose keys and values are both elements | `$dataHandler->cloneData(5, array('year' => 'year_copy'))` |

A parameter that can be given more than one kind of argument is declared with the shape that has elements
in it. For example, the `$operator` of
[findFirstEntryWithAllValues](/documentation/classes/data_handler/findFirstEntryWithAllValues/) is either
one operator as a string, or an array of operators keyed by element. It is declared `keys`. A shape only
applies when the argument is written that way, so a string passed to it is left as it is.

## When to declare a parameter

- **When every quoted string that can be passed to it is an element.** An element's handle, or one of the
  metadata fields such as `entry_id` or `creation_uid`, which are never an element's handle. A parameter
  that sometimes takes something else, like a column of another table or free text, is not declared.
- **When the method name is unique.** The method must be identifiable by its name alone, so if any
  other method in Formulize has the same name, do not declare `@formulizeElement` in its docblock. For
  example, `get()` is a common method name used by several classes, so it cannot take a
  `@formulizeElement` declaration. `writeEntry()` is a name only the data handler uses, so it can.
- **When the code is in one of the locations Formulize pays attention to.** Declarations are only read from `modules/formulize/class/`,
  `modules/formulize/include/` and `modules/formulize/include/functions/`.

## What a rename leaves for a person

Only a quoted string written right there in the call is updated:

```php
formulize_writeEntry(array('year') => 1990));
```

If code builds the argument first and passes it in, it is not updated:

```php
$values = array('year' => 1990);
formulize_writeEntry($values);
```

In this case, the array might be used for something else as well, and the code analysis quickly becomes intractable. Instead, the file will be listed in the notice shown after the rename so someone can update it manually as appropriate.

## ID numbers are never changed

Only element handles are updated. Element ID numbers are always left alone.

## Checking your declarations

Run the logic test from the root of the repository on your command line:

```bash
php tests/element_references_test.php
```

It fails if a declaration names a parameter the function doesn't have, or a shape that doesn't exist, or
if a declared method shares its name with another method. It also has a list of the functions and
methods that must be declared. If you add one that should always be declared, add it to that list, so
the declaration can't be lost by accident.

## Other things with handles

For now this is only for elements. Forms, screens and other things that have handles may get tags of
their own in time, following the same pattern.
