---
layout: default
permalink: developers/API/classes/data_handler/getElementValueInEntry/
---

# getElementValueInEntry( <span style='font-size: 14pt;'>(int) $entry_id, (int | string | object) $element_identifier, (array)&nbsp;$scope_uids = array(), (array) $scope_group_ids = array()</span> )

## Description

Returns the raw database value of a given element in a given entry. Can be limited in scope, so that it only returns values if the entry was created by a given set of users, or a by a given set of groups.

## Parameters

__$entry_id__ - an entry id to query<br>
__$element_identifier__ - either an element id, an element handle, or a Formulize element object<br>
__$scope_uids__ - Optional. An array of user ids that should be used to limit the query<br>
__$scope_group_ids__ - Optional. An array of group ids that should be used to limit the query. _Has no effect if_ ___$scope_uids___ _are specified_.

## Return Values

Returns the __raw value from the database__ stored in the given entry for the given form element. The raw database values may not be human-readable and/or may contain metadata, etc, depending on the element type.

Returns __false__ if the query failed or if the element identifier is invalid, or if the query found no records in the database.

## Example

~~~
// get the value of the order_details element from entry 19 in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entry_id = 19;
$element_handle = "order_details";
$order_details = $dataHandler->getElementValueInEntry($entry_id, $element_handle);
~~~

~~~
// get the value of order_customer from entry 19 in form 6
// imagine order_customer is a linked selectbox pointing to the customer name in a customer form 
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entry_id = 19;
$element_handle = "order_customer";
$order_customer = $dataHandler->getElementValueInEntry($entry_id, $element_handle);

// $order_customer will be a foreign key, the entry id of a record in another form
// imagine the foreign key is entry id number 56 in the customer form
// this will display the number 56:
var_dump($order_customer); 

// convert the raw database value to a human readable value
$order_customer = prepValues($order_customer, $element_handle, $entry_id);

var_dump($order_customer); // $order_customer is now the name from entry 56 of the customer form 
~~~

~~~
// get the value of order_categories from entry 19 in form 6
// imagine order_categories is a checkbox series where multiple categories can be selected
// imagine the categories 'Rush Order' and 'Repeat Customer' are selected
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entry_id = 19;
$element_handle = "order_categories";
// order_categories will be a raw value containing all the categories selected
$order_categories = $dataHandler->getElementValueInEntry($entry_id, $element_handle);

var_dump($order_categories); // will display: *=+*:Rush Order*=+*:Repeat Customer

// convert the raw database value to a human readable value
// in this case it will return an array, since the raw value for a checkbox
// contains multiple individual selections
$order_categories = prepValues($order_categories, $element_handle, $entry_id);

var_dump($order_categories); // will display: array('Rush Order', 'Repeat Customer')
~~~

