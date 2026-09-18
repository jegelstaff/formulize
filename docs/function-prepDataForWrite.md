---
layout: default
permalink: documentation/functions/prepDataForWrite/
redirect_from:
 - developers/functions/prepDataForWrite/
 - developers/API/functions/prepDataForWrite/
title: prepDataForWrite
---

# prepDataForWrite( <span class="sig-type">(object | string | int)</span> $element_identifier, <span class="sig-type">(array | string | int)</span> $value, <span class="sig-type">(int)</span> $entry_id )

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

~~~php
// radio buttons submit the ordinal number of the option that was selected
// convert a 3 to the text for the third radio button option
// entry id we are going to write to is 27
$value = prepDataForWrite('fruit_choices', 3, 27);
~~~

~~~php
// take a date value and prepare it for saving into a date element in a new entry in the database
$value = "May 9, 1969";
$value = prepDataForWrite('date_element_handle', $value, 'new');
~~~
