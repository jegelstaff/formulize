---
layout: default
permalink: developers/API/classes/data_handler/findAllValuesForField/
---

# findAllValuesForField( <span style='font-size: 14pt;'>(string) $element_handle, (string) $sort = "", (array)&nbsp;$scope_group_ids&nbsp;=&nbsp;array(), (array) $scope_uids = array(), (bool) $usePerGroupFilters = false</span> )

## Description

Find all the values for a given form element. Optionally, limit the values returned by user, by group, and by group permission filters.

## Parameters

__$element_handle__ - the element handle to get values for<br>
__$sort__ - Optional. The sort direction for the values. Valid values are ASC and DESC. Default is no sort, so values will be returned in creation order.<br>
__$scope_group_ids__ - Optional. An array of group ids that should be used to limit the query.<br>
__$scope_uids__ - Optional. An array of user ids that should be used to limit the query<br>
__$usePerGroupFilters__ -  Optional.  A boolean to indicate whether the current user's group permission filters should be used to limit the query. The group permission filters are set in the permissions for the form, and can be used to restrict the entries that users can access according to arbitrary criteria.

## Return Values

Returns __an array__ containing the values found. _The keys are the entry ids of the records that each value is from_. If no entries are found that match the criteria, the array will be empty. 

Returns __false__ if the query fails.

## Examples

~~~
// find the all the values for the 'cities' element in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$values = $dataHandler->findAllValuesForField('cities');
~~~

~~~
// find the all the values for the 'cities' element in form 6, created by groups 5 and 10.
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$scope_group_ids = array(5, 10);
$values = $dataHandler->findAllValuesForField('cities', scope_group_ids: $scope_group_ids);
~~~

~~~
// same as above, but in ascending order
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$scope_group_ids = array(5, 10);
$values = $dataHandler->findAllValuesForField('cities', 'ASC', $scope_group_ids);
~~~

