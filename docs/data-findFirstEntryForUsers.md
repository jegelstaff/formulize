---
layout: default
permalink: developers/API/classes/data_handler/findFirstEntryForUsers/
---

# findFirstEntryForUsers( <span style='font-size: 14pt;'>(int | array) $user_ids</span> )

## Description

Finds the first entry id created by the given user or users, in the data handler's form. Entry ids are an auto incrementing numeric value, so the lowest number will correspond to the first entry.

## Parameters

__$user_ids__ - a user id or an array of user ids

## Return Values

Returns __the first (earliest) entry id found__.

Returns __false__ if the query failed, or if the query found no records in the database.

## Example

~~~
// find the first entry created by user 905, in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entry_id = $dataHandler->findFirstEntryForUsers(905);
~~~
