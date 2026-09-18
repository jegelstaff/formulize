---
layout: default
permalink: documentation/classes/data_handler/findAllUsersForEntries/
redirect_from:
 - developers/classes/data_handler/findAllUsersForEntries/
 - developers/API/classes/data_handler/findAllUsersForEntries/
title: findAllUsersForEntries
---

# findAllUsersForEntries( <span class="sig-type">(int | array)</span> $entry_ids, <span class="sig-type">(array)</span> $scope_uids <span class="sig-default">= array()</span> )

## Description

Finds the user ids of the creators of a given entry or entries. The scope can be limited to only certain users by passing an array of allowable user ids as the _$scope_uids_ parameter.

## Parameters

__$user_ids__ - a entry id or array of entry ids<br>
__$scope_uids__ - Optional. an array of allowable user ids. Results will be limited to user ids that match one of the declared ids in the array.

## Return Values

Returns __an array of user ids__, or __false__ if the query failed.

## Example

~~~php
// find the users who created the declared series of entries in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entries = array(19, 20, 21);
$creation_user_ids = $dataHandler->findAllUsersForEntries($entries);
~~~

~~~php
// find the users who created the declared series of entries in form 6
// only if the users are in the declared 'managers' array
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entries = array(19, 20, 21);
$managers = array(105, 121, 287);
$creation_user_ids = $dataHandler->findAllUsersForEntries($entries, $managers);
~~~

