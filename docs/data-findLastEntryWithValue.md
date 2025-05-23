---
layout: default
permalink: developers/API/classes/data_handler/findLastEntryWithValue/
title: findLastEntryWithValue
---

# findLastEntryWithValue( <span style='font-size: 14pt;'>(int | string | object) $element_identifier, (string) $value, (string)&nbsp;$operator&nbsp;= "=", (array) $scope_uids = array()</span> )

## Description

Gets the last entry id where the value for a given element matches the value specified. Entry ids are an auto incrementing numeric value, so the highest number will correspond to the last entry.

The value must match the raw value stored in the database, and won't necessarily be human readable (could be an id number, etc).

## Parameters

__$element_identifier__ - either an element id, an element handle, or a Formulize element object<br>
__$value__ - the value to look for<br>
__$operator__ -  Optional.  the operator to use when querying for the value. Defaults to equals. Any valid SQL operator can be used. If LIKE is used, _then the value will be automatically wrapped in % signs_ to support pattern matching.<br>
__$scope_uids__ - Optional. an array of allowable user ids. Results will be limited to user ids that match one of the declared ids in the array.<br>

## Return Values

Returns the __last (most recent) entry id found__.

Returns __false__ if the element identifier is invalid, or if the query fails, or if the query finds no entries that match the value.

## Example

~~~php
// find the last entry created that has 'blue' as the value for the 'colour' element, in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entry_id = $dataHandler->findLastEntryWithValue('colour', 'blue');
~~~

~~~php
// find the last entry created where the value for element 33 is greater than 100, in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entry_id = $dataHandler->findLastEntryWithValue(33, 100, ">");
~~~

~~~php
// find the last entry created where the order_customer value is 56, in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entry_id = $dataHandler->findLastEntryWithValue('order_customer', 56);
~~~
