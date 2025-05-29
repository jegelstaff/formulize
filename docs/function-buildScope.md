---
layout: default
permalink: developers/API/functions/buildScope/
title: buildScope
---

# buildScope( <span style='font-size: 14pt;'>(string|int) $currentView, (object|int) $userIdOrObject, (int) $fid</span> )

## Location

/modules/formulize/include/functions.php

## Description

Creates a valid scope that can be passed to the [gatherDataset](../gatherDataset/) function, for retrieving data from the database according to the specified user's permissions.

## Parameters

__$currentView__ - Can be one of the strings: _mine_, _group_, or _all_, signifying "the user's entries," or "their group's entries," or "all entries." Can be a comma separated list of group ids instead, or a single group id, to declare a specific scope based on that particular set of groups.<br>
__$userIdOrObject__ - The user id or object that represents the user for whom the scope is being built. Their permissions on the form will be taken into account to ensure the scope is limited to the data they have access to.<br>
__$fid__ - The id number of the form for which the scope is being built.<br>

## Return Values

Returns an array with two values in it. Key zero is the scope which will be an array of groups, or arbitrary SQL to append to a database query. Key one is the value of currentView, which may have changed if the specified user did not have permission for the requested currentView value.

Key zero, the returned scope, can be passed directly into the [gatherDataset](../gatherDataset/) function.

## Examples

~~~php
// Create the most permissive scope possible for user 12 on form 6, based on their permissions
$userId = 12;
$formId = 6;
$currentView = 'all'; // try to go big
list($scope, $currentView) = buildScope($currentView, $userId, $formId);
if($currenView != 'all') {
	print "Scope was reduced from 'all' based on the user's permissions";
}
~~~

~~~php
// Find the group ids that make up 'group scope' for user 12 on form 6
// ie: which groups are 'their groups' for the purposes of seeing entries in form 6
// Note: if the user does not have any group level visibility permission on the form,
// then the scope will be reduced to only their own entries
$userId = 12;
$formId = 6;
$currentView = 'group';
list($scope, $currentView) = buildScope($currentView, $userId, $formId);
print "'Group Scope' groups for $userId on $formId are:";
print_r($scope);
~~~

~~~php
// Print out the order numbers from the Orders form, for all the orders that belong
// to the currently logged in user's group(s)
global $xoopsUser;
$userId = $xoopsUser ? $xoopsUser->getVar('uid') : 0; // current user or zero if there isn't one
$orderFormId = 6;
$currentView = 'group';
list($scope, $currentView) = buildScope($currentView, $userId, $orderFormId);
$data = gatherDataset($orderFormId, scope: $scope);
foreach($data as $entry) {
	print "Order Number: ".getValue($entry, 'order_number');
}
~~~
