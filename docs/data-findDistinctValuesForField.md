---
layout: default
permalink: documentation/classes/data_handler/findDistinctValuesForField/
title: findDistinctValuesForField
---

# <span class="sig-name">findDistinctValuesForField</span>( <span class="sig-arg"><span class="sig-type">(string)</span> $element_handle</span>, <span class="sig-arg"><span class="sig-type">(string)</span> $term <span class="sig-default">= ""</span></span>, <span class="sig-arg"><span class="sig-type">(int)</span> $maxValues <span class="sig-default">= 0</span></span>, <span class="sig-arg"><span class="sig-type">(array)</span> $scope_group_ids <span class="sig-default">= array()</span></span>, <span class="sig-arg"><span class="sig-type">(array)</span> $scope_uids <span class="sig-default">= array()</span></span>, <span class="sig-arg"><span class="sig-type">(bool)</span> $usePerGroupFilters <span class="sig-default">= false</span></span> )

## Description

Find the different values stored for a form element, each one once, in alphabetical order. Optionally, only the values that contain some text, and only so many of them. Like [findAllValuesForField](../findAllValuesForField), the values can be limited by user, by group, and by group permission filters.

Use this rather than findAllValuesForField when you only need some of the values, such as the ones that match what someone is typing. The database does the matching and the counting, so it doesn't read every value in the form.

## Parameters

__$element_handle__ - the element handle to get values for<br>
__$term__ - Optional. Only values that contain this text. Its % and _ characters are matched as they are. Default is any value.<br>
__$maxValues__ - Optional. At most this many values. Default is 0, for no limit.<br>
__$scope_group_ids__ - Optional. An array of group ids that should be used to limit the query.<br>
__$scope_uids__ - Optional. An array of user ids that should be used to limit the query<br>
__$usePerGroupFilters__ -  Optional.  A boolean to indicate whether the current user's group permission filters should be used to limit the query. The group permission filters are set in the permissions for the form, and can be used to restrict the entries that users can access according to arbitrary criteria.

## Return Values

Returns __an array__ of the values found, without blank values. If no values match, the array will be empty.

Returns __false__ if the query fails.

## Examples

~~~php
// the first 20 cities in the Customers form that contain "san"
$dataHandler = new formulizeDataHandler(CUSTOMERS_FORM_ID);
$cities = $dataHandler->findDistinctValuesForField('cities', 'san', 20);
~~~

~~~php
// every city, once each, in entries created by groups 5 and 10
$dataHandler = new formulizeDataHandler(CUSTOMERS_FORM_ID);
$cities = $dataHandler->findDistinctValuesForField('cities', scope_group_ids: array(5, 10));
~~~
