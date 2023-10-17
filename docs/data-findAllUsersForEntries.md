---
layout: default
permalink: developers/API/classes/data_handler/findAllUsersForEntries/
---

# findAllUsersForEntries( <span style='font-size: 14pt;'>(int | array) $entry_ids, (array) $scope_uids = array()</span> )

## Description

Finds the user ids of the creators of a given entry or entries. The scope can be limited to only certain users by passing an array of allowable user ids as the _$scope_uids_ parameter.

## Parameters

__$user_ids__ - a entry id or array of entry ids<br>
__$scope_uids__ - Optional. an array of allowable user ids. Results will be limited to user ids that match one of the declared ids in the array.

## Return Values

Returns __an array of user ids__, or __false__ if the query failed.

## Example

~~~
// find the users who created the declared series of entries in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entries = array(19, 20, 21);
$creation_user_ids = $dataHandler->findAllUsersForEntries($entries);
~~~

~~~
// find the users who created the declared series of entries in form 6
// only if the users are in the declared 'managers' array
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entries = array(19, 20, 21);
$managers = array(105, 121, 287);
$creation_user_ids = $dataHandler->findAllUsersForEntries($entries, $managers);
~~~
 
