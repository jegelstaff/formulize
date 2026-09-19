---
layout: default
permalink: documentation/classes/data_handler/deleteEntries/
redirect_from:
 - developers/classes/data_handler/deleteEntries/
 - developers/API/classes/data_handler/deleteEntries/
title: deleteEntries
---

# <span class="sig-name">deleteEntries</span>( <span class="sig-arg"><span class="sig-type">(int | array)</span> $entry_ids</span> )

## Description

Deletes an entry or entries in a form. Only works when called during POST requests. It will delete the associated ownership information for the entries, and also record the deletion in the deletion log if revision history is turned on for a form.

## Parameters

__$entry_ids__ - an entry id or an array of entry ids

## Return Values

Returns __true__ if the entry or entries were deleted. Returns __false__ if the deletion SQL query failed, including if it was called during a non-POST request. It will print out error messages to the screen if deleting ownership information failed, and/or if adding to the deletion log failed.

## Examples

~~~php
// delete entry 19 in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$dataHandler->deleteEntries(19);
~~~

~~~php
// delete a series of entries in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entries = array(19, 20, 21);
$dataHandler->deleteEntries($entries);
~~~


