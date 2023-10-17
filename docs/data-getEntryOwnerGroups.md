---
layout: default
permalink: developers/API/classes/data_handler/getEntryOwnerGroups/
---

# getEntryOwnerGroups( <span style='font-size: 14pt;'>(int) $entry_id = 0</span> ) 

## Description

Gets all the groups that are associated with a given entry, or if no entry is specified then returns all the groups that are associated with entries in the form.

## Parameters

__$entry_id__ - Optional. An entry id number.

## Return Values

Returns __an array of distinct group ids__. Returns __false__ if the query failed. 


## Examples

~~~
// Get all the groups associated with entry 44 in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$group_ids = $dataHandler->getEntryOwnerGroups(44);
~~~

~~~
// Get all the groups associated with any entry in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$group_ids = $dataHandler->getEntryOwnerGroups();
~~~
