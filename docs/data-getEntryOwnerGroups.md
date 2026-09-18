---
layout: default
permalink: developers/classes/data_handler/getEntryOwnerGroups/
redirect_from:
 - developers/API/classes/data_handler/getEntryOwnerGroups/
title: getEntryOwnerGroups
---

# getEntryOwnerGroups( <span class="sig-type">(int)</span> $entry_id <span class="sig-default">= 0</span> )

## Description

Gets all the groups that are associated with a given entry, or if no entry is specified then returns all the groups that are associated with entries in the form.

## Parameters

__$entry_id__ - Optional. An entry id number.

## Return Values

Returns __an array of distinct group ids__. Returns __false__ if the query failed.


## Examples

~~~php
// Get all the groups associated with entry 44 in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$group_ids = $dataHandler->getEntryOwnerGroups(44);
~~~

~~~php
// Get all the groups associated with any entry in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$group_ids = $dataHandler->getEntryOwnerGroups();
~~~
