---
layout: default
permalink: documentation/classes/data_handler/entryExists/
redirect_from:
 - developers/classes/data_handler/entryExists/
 - developers/API/classes/data_handler/entryExists/
title: entryExists
---

# <span class="sig-name">entryExists</span>( <span class="sig-arg"><span class="sig-type">(int)</span> $entry_id</span> )

## Description

Checks if a given entry id exists

## Parameters

__$id__ - an entry id to look for

## Return Values

Returns __true__ if the entry exists, __false__ if the entry does not exist.

## Example

~~~php
// does entry 19 exist in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
if($dataHandler->entryExists(19)) {
    echo "It exists!";
}
~~~
