---
layout: default
permalink: developers/API/classes/data_handler/entryExists/
---

# entryExists( <span style='font-size: 14pt;'>(int) $entry_id</span> )

## Description

Checks if a given entry id exists

## Parameters

__$id__ - an entry id to look for

## Return Values

Returns __true__ if the entry exists, __false__ if the entry does not exist.

## Example

~~~
// does entry 19 exist in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
if($dataHandler->entryExists(19)) {
    echo "It exists!";
}
~~~
