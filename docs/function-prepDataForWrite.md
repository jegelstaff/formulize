---
layout: default
permalink: developers/API/functions/prepDataForWrite/
---

# prepDataForWrite( <span style='font-size: 14pt;'>(object | string | int) $element_identifier, (array | string | int) $value, (int) $entry_id</span> )

## Location

/modules/formulize/include/functions.php

## Description

Prepares a value submitted through a form, for saving into the database. Values submitted from a form do not always conform to the storage format that is used in the database, or may need validation.

Values passed to this function do not have to be from a form. Values must simply conform to the format that would be provided by a form submission.

## Parameters

__$element_identifier__ - either an element id, an element handle, or a Formulize element object<br>
__$value__ - the value to be prepared<br>
__$entry_id__ - The entry id for which the value is being prepared, or "new" for a new entry. This does not always have an effect, but in some situations the particular entry might affect what happens to the value.

## Return Values

Returns __the prepared value__.

Returns __false__ if the element_identifier is invalid.

## Examples

~~~
// radio buttons submit the ordinal number of the option that was selected
// convert a 3 to the text for the third radio button option
// entry id we are going to write to is 27
$value = prepDataForWrite('fruit_choices', 3, 27);
~~~

~~~
// take a date value and prepare it for saving into a date element in a new entry in the database
$value = "May 9, 1969";
$value = prepDataForWrite('date_element_handle', $value, 'new');
~~~
