---
layout: default
permalink: developers/API/classes/data_handler/findFirstEntryForGroups/
---

# findFirstEntryForGroups( <span style='font-size: 14pt;'>(int | array) $group_ids</span> )

## Description

Finds the first entry id created by the given group or groups, in the data handler's form. Entry ids are an auto incrementing numeric value, so the lowest number will correspond to the first entry.

Entries are associated with all the groups that the creator of the entry is a member of, regardless of whether or not those groups have permission to access the form.

The group associations are a snapshot. If the creator changes groups, the entries they have created __do not__ receive new associations. Users with permission to update the ownership information for an entry can assign a new creator to an entry by selecting a different user when they save the entry, and then the group associations will be updated.

## Parameters

__$group_ids__ - a group id or an array of group ids

## Return Values

Returns __the first (earliest) entry id found__.

Returns __false__ if the query failed, or if the query found no records in the database.

## Example

~~~
// find the first entry belonging to either group 499 or 526, in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entry_id = $dataHandler->findFirstEntryForGroups(array(499, 526));
~~~
