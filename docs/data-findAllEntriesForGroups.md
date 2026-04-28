---
layout: default
permalink: developers/API/classes/data_handler/findAllEntriesForGroups/
title: findAllEntriesForGroups
---

# findAllEntriesForGroups( <span style='font-size: 14pt;'>(int | array) $group_ids</span> )

## Description

Finds all the entry ids created by the given group or groups, in the data handler's form.

Entries are associated with all the groups that the creator of the entry is a member of, regardless of whether or not those groups have permission to access the form.

The group associations are a snapshot. If the creator changes groups, the entries they have created __do not__ receive new associations. Users with permission to update the ownership information for an entry can assign a new creator to an entry.

## Parameters

__$group_ids__ - a group id or an array of group ids

## Return Values

Returns __an array of the entry ids found__, or an empty array if none were found.

Returns __false__ if the query failed.

## Example

~~~php
// find all the entry ids in form 6, belonging to either group 499 or 526
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entry_id = $dataHandler->findAllEntriesForGroups(array(499, 526));
~~~
