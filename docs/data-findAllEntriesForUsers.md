---
layout: default
permalink: documentation/classes/data_handler/findAllEntriesForUsers/
redirect_from:
 - developers/classes/data_handler/findAllEntriesForUsers/
 - developers/API/classes/data_handler/findAllEntriesForUsers/
title: findAllEntriesForUsers
---

# <span class="sig-name">findAllEntriesForUsers</span>( <span class="sig-arg"><span class="sig-type">(int | array)</span> $user_ids</span>, <span class="sig-arg"><span class="sig-type">(array)</span> $scope_uids <span class="sig-default">= array()</span></span>, <span class="sig-arg"><span class="sig-type">(array)</span> $scope_group_ids <span class="sig-default">= array()</span></span> )

## Description

Finds the entry id or ids for the given users in the data handler's form. Can be limited to only find entries if they were created by a given set of users, or a by a given set of groups.

## Parameters

__$user_ids__ - a user id or an array of user ids<br>
__$scope_uids__ - Optional. An array of user ids that should be used to limit the query. Somewhat nonsensical to use this.<br>
__$scope_group_ids__ - Optional. An array of group ids that should be used to limit the query. __Has no effect if__ _$scope_uids_ __are specified__.

## Return Values

Returns __an array containing the entries found__. If only one entry id is found the array will have one value.

Returns __false__ if the query failed or if the element identifier is invalid, or if the query found no records in the database.

## Example

~~~php
// find the entries created by a user 29 in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entry_ids = $dataHandler->findAllEntriesForUsers(29);
~~~
