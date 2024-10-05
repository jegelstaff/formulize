---
layout: default
permalink: developers/API/classes/data_handler/setEntryOwnerGroups/
---

# setEntryOwnerGroups( <span style='font-size: 14pt;'>(int | array) $user_ids, (int | array) $entry_ids</span> ) 

## Description

Sets the entry ownership information for the specified entry or entries. Ownership details are based on the corresponding user id or ids (the user ids and entry ids passed must be in parallel).

All of a user's groups are associated with their entries in Formulize, even if some of the groups have no permissions on the form. This is so that in the future if a group gets permission to a form, we have a record of which entries that group is associated with.

The association between groups and entries is recorded at the time the entry is created. If a user changes group memberships, the entries they have created are still associated with their old group memberships. This is so that historical records remain belonging to the groups that created them, even if a user is promoted, for example from a volunteer group to a manager group, or from a Toronto group to a Boston group.

## Parameters

__$user_ids__ - A user id number or an array of user id numbers. Must have an equal number of values as the entry_ids paremeter.<br> 
__$entry_ids__ - A entry id number or an array of entry id numbers. Must have an equal number of values as the uids parameter<br>

## Return Values

Returns __true__ if the operation is successful.

Returns __false__ if the uids and entry_ids parameters do not have an equal number of values. Returns false if a query failed. 


## Examples

~~~
// Update the entry ownership information for entry 71 in form 6, to be user 399
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$dataHandler->setEntryOwnerGroups(399, 71);
~~~

~~~
// Update the entry ownership information for entries 71 through 75 in form 6, to all be user 399
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$uids = array(399, 399, 399, 399, 399);
$entry_ids = array(71, 72, 73, 74, 75);
$dataHandler->setEntryOwnerGroups($uids, $entry_ids);
~~~

~~~
// Make a new entry and set its ownership to user 12
// ** It's easier to just use the formulize_writeEntry function instead **
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$values = array(
    'first_name' => 'John',
    'last_name' => 'Smith'
);
$entry_id = $dataHandler->writeEntry('new', $values, forceUpdate: true);
$dataHandler->setEntryOwnerGroups(12, $entry_id);
~~~


