---
layout: default
permalink: developers/API/classes/data_handler/findAllEntriesWithValue/
---

# findAllEntriesWithValue( <span style='font-size: 14pt;'>(int | string | object) $element_identifier, (string) $value, (array)&nbsp;$scope_uids&nbsp;=&nbsp;array(), (array) $scope_group_ids = array(), (string) $operator = "="</span> ) 

## Description

Find all the entries which have a given value in the database. The value must match the raw value stored in the database, and won't necessarily be human readable (could be an id number, etc).

## Parameters

__$element_identifier__ - either an element id, an element handle, or a Formulize element object<br>
__$value__ - the value to look for<br>
__$scope_uids__ - Optional. An array of user ids that should be used to limit the query<br>
__$scope_group_ids__ - Optional. An array of group ids that should be used to limit the query. _Has no effect if_ ___$scope_uids___ _are specified_.<br>
__$operator__ -  Optional.  the operator to use in when querying for the values. Defaults to equals. Any valid SQL operator can be used. If LIKE is used, you must add % to the start and/or end of your value if you want a pattern match on either side.

## Return Values

Returns __an array__ containing the entry ids found. If no entries matched the value, the array will be empty. If the scope parameters exclude all matching entries, the array will be empty.

Returns __false__ if the query fails, or the element identifier is invalid.

## Example

~~~
// find the entries that have 'foo' as the value for the element 'bar', in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entry_ids = $dataHandler->findAllEntriesWithValue('bar', 'foo');
~~~

~~~
// find the entries that contain 'foo' in the value for the element 'bar', in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entry_ids = $dataHandler->findAllEntriesWithValue('bar', '%foo%', operator: 'LIKE');
~~~

~~~
// find the entries that contain 'foo' in the value for the element 'bar',
// created by either group 526 or 707, in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$scope_group_ids = array(526, 707);
$entry_ids = $dataHandler->findAllEntriesWithValue(
    element_identifier: 'bar',
    value: '%foo%',
    scope_group_ids: $scope_group_ids,
    operator: 'LIKE'
);
~~~