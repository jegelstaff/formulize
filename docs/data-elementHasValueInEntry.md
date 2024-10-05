---
layout: default
permalink: developers/API/classes/data_handler/elementHasValueInEntry/
---

# elementHasValueInEntry( <span style='font-size: 14pt;'>(int) $entry_id, (int | string | object) $element_identifier</span> )

## Description

Checks if the given element has a value in the given entry.

## Parameters

__$entry_id__ - an entry id to query<br>
__$element_identifier__ - either an element id, an element handle, or a Formulize element object

## Return Values

Returns __true__ if the given element has a value in the given entry. Having a value means not being equal to an empty string, ie: `$value != ""`

Note that this is a non strict comparison in PHP so null values and zeros will return false, the same as empty strings.

Returns __false__ if the given element does not have a value, or if the query fails, or if the element identifier is invalid.

## Example

~~~
// check if the order_details form element has a value in entry 19 in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entry_id = 19;
$element_handle = "order_details";
if($dataHandler->elementHasValueInEntry($entry_id, $element_handle)) {
    echo "The order has details";
}
~~~

