---
layout: default
permalink: developers/API/functions/gatherDataset/
title: gatherDataset
---

# gatherDataset( <span style='font-size: 14pt;'>(int) $fid, (array) $elementHandles = array(), (int|string|array) $filter = "", (string) $andOr = "AND", (array|string) $scope = "", (int) $limitStart = null, (int) $limitSize = null, (string) $sortField = "", (string) $sortOrder = ASC", (int) $frid = -1</span> )

## Location

/modules/formulize/include/extract.php

## Description

Gathers a dataset from the database, in which the declared form is the "main form" and data from other forms directly connected to the main form will be included as well, based on the connections, if any, defined in the specified relationship.

Only forms directly connected to the main form will be included in the dataset, all other forms in the relationship will be ignored.

The resulting dataset will be organized into an array, in which every item represents one entry in the main form. Each item in the dataset will contain all the data in that main form entry <b>plus</b> all the data from connected entries in other forms that are part of the dataset. If there are one-to-many connections in the relationship, then multiple entries from other forms might be part of each item in the dataset.

For example, if you have a Countries form connected to a Cities form, and you gather a dataset based on the Countries form, the dataset will look like this:

Canada<br>
&nbsp;&nbsp;|_ Toronto<br>
&nbsp;&nbsp;|_ Halifax<br>
Argentina<br>
&nbsp;&nbsp;|_ Buenos Aires<br>
&nbsp;&nbsp;|_ Puerto Iguazu

The dataset above will have two entries, or items, in it. Each one one is made up of three underlying entries from the database: one country entry, and two cities entries.

If you gather the same dataset, but based on the Cities form, the dataset will look like this:

Toronto - Canada<br>
Halifax - Canada<br>
Buenos Aires - Argentina<br>
Puerto Iguazu - Argentina

That dataset will have four entries, or items, and each one is made up of two underlying entries from the database: one city entry and one country entry. Importantly, the same underlying Country entry is repeated within the dataset because it connects to multiple entries in the main form.

The number of top level items in the dataset corresponds to the number of entries in the main form that are part of the dataset. Each item in the dataset is made up of data from multiple underlying, individual entries in the database, from all the forms that are connected to the main form through the relationship.

You can interate over the items in a dataset with __foreach__ in PHP, and you can interact with each item in the dataset using the functions [getValue](../getValue/) and [getEntryIds](../getEntryIds/). You can create a scope for _gatherDataset_ to use, based on the current user's permissions, using the [buildScope](../buildScope) function.

## Parameters

__$fid__ - The id number of the main form in the dataset.<br>
__$elementHandles__ - Optional. An array of element handles to include in the result set. By default all elements are included. To limit the dataset to only certain elements, use a multidimensional array, where the top level keys are form ids, and the values are an array of element handles in that form.<br>
__$filter__ - Optional. An entry id in the main form of a single entry to gather, or a valid filter string or array of strings. See the examples for more details. Defaults to no filter.<br>
__$andOr__ - Optional. A boolean operator to use between multiple filters. Defaults to _AND_.<br>
__$scope__ - Optional. An array of group ids that should be used to limit the data included in the dataset. Can also be an arbitrary SQL string that will be appended to the end of the query. Defaults to an empty string (so there will be no scope restrictions by default). Use the function [buildScope](../buildScope) to create a scope based on a particular user's permissions.<br>
__$limitStart__ - Optional. The value to use in a LIMIT statement as the starting record. Defaults to null and has no effect unless _limitSize_ is specified as well.<br>
__$limitSize__ - Optional. The value to use in a LIMIT statement as the size of the resulting dataset. Defaults to null and has no effect unless a number is specified.<br>
__$sortField__ - Optional. The element handle of the field by which the dataset should be sorted. Can be on the main form or any connected form. By default, data is sorted in creation order, oldest to newest.<br>
__$sortOrder__ - Optional. The direction to use for sorting. Defaults to ascending order.<br>
__$frid__ - Optional. The relationship to use for gathering the dataset. All forms in the relationship that are directly connected to the main form will be included. Defaults to the _Primary Relationship_ (-1) which is a catalogue of all the connections in the system. Specify 0 to use no relationship and only gather data from the main form.

## Return Values

Returns a dataset that includes all the entries in the main form, according to any _filter_ and _scope_ specified, plus all connected entries from forms in the specified relationship. Returns an empty array if no data was gathered.

You can interate over the items in a dataset with __foreach__ in PHP, and you can interact with each item in the dataset using the functions [getValue](../getValue/) and [getEntryIds](../getEntryIds/).

## Examples

~~~php
// gather all the data in form 6, plus connected forms in the Primary Relationship
$formId = 6;
$data = gatherDataset($formId);
~~~

~~~php
// gather all the data in form 6 for only entry 99, plus connected forms in the Primary Relationship
// filter can be an ID number to isolate one entry
$formId = 6;
$entryId = 99;
$data = gatherDataset($formId, filter: $entryId);
~~~

~~~php
// gather all the data in form 6, plus connected forms in the Primary Relationship
// limit the data to only data belonging to group 17
$formId = 6;
$groupId = 17;
$scope = array($groupId);
$data = gatherDataset($formId, scope: $scope);
~~~

~~~php
// gather all the data in form 6, plus connected forms in the Primary Relationship
// limit the data to only data where the value of the element 'fruit_name' is 'Strawberries' or 'Blueberries'
// filter strings use /**/ to separate component parts
$formId = 6;
$filter = "fruit_name/**/berries";
$data = gatherDataset($formId, filter: $filter);
~~~

~~~php
// Gather all the data in form 6, plus connected forms in the Primary Relationship
// Limit the data to only data where the value of the element 'fruit_name' is like 'berries'
// Include only the elements 'order_date' and 'fruit_name' in the dataset.
// The elements are in  different forms.
$fruitFormId = 6;
$orderFormId = 11;
$elementHandles = array(
	$fruitFormId=>array(
		'fruit_name'
	),
	$orderFormId=>array(
		'order_date'
	)
);
$filter = "fruit_name/**/Berries";
$data = gatherDataset($fruitFormId, $elementHandles, $filter);
// no named parameter necessary because those are the first three params in order
~~~

~~~php
// gather all the data in form 6 only, no other forms included
$formId = 6;
$data = gatherDataset($formId, frid: 0);
~~~

~~~php
// Gather all the data in form 11, plus connected forms in the Primary Relationship
// Note the inverse main form compared to prior examples
$fruitFormId = 6;
$orderFormId = 11;
$filter = "fruit_name/**/berries";
$data = gatherDataset($orderFormId, filter: $filter);

// Loop through the dataset and generate a set of prices for orders, and the berries included in them
$orderPriceAndContents = array();
foreach($data as $entry) {
	$orderPriceAndContents[getValue($entry, 'order_date')] = array(
		'price'=>getValue($entry, 'order_price'),
		'contents'=>getValue($entry, 'fruit_name')
	);
}
// the 'contents' will be an array, if there were multiple fruit records in the dataset for that order
~~~

~~~php
// gather all the data in form 6, plus connected forms in the Primary Relationship
// limit the data to only data where the value of the element 'fruit_name' is precisely 'Strawberries'
// filter strings use /**/ to separate component parts
// the final component is the operator. Defaults to LIKE if not specified.
$formId = 6;
$filter = "fruit_type/**/Strawberries/**/=";
$data = gatherDataset($formId, filter: $filter);
~~~

~~~php
// gather all the data in form 6, plus connected forms in the Primary Relationship
// limit the data to only data where the value of the element 'order_price' is higher than 100
// filter strings use /**/ to separate component parts
// the final component is the operator. Defaults to LIKE if not specified.
$formId = 6;
$filter = "order_price/**/100/**/>";
$data = gatherDataset($formId, filter: $filter);
~~~

~~~php
// Gather all the data in form 6, plus connected forms in the Primary Relationship
// Limit the data to only data where the value of the element 'order_price' is higher than 100
// and lower than 200
// Filter strings use /**/ to separate component parts
// The final component is the operator. Defaults to LIKE if not specified.
// Multiple filter strings can be concatenated with ][
$formId = 6;
$filter = "order_price/**/100/**/>][order_price/**/200/**/<";
$data = gatherDataset($formId, filter: $filter);
~~~

~~~php
// Gather all the data in form 6, plus connected forms in the Primary Relationship
// Limit the data to only data where the value of the element 'fruit_name' is
// 'Bananas' or 'Oranges'
// Filter strings use /**/ to separate component parts
// The final component is the operator. Defaults to LIKE if not specified.
// Multiple filter strings can be concatenated with ][
// The andOr parameter controls how multiple terms are interpretted
$formId = 6;
$filter = "fruit_name/**/Bananas/**/=][fruit_name/**/Oranges/**/=";
$data = gatherDataset($formId, filter: $filter, andOr: "OR");
~~~

~~~php
// Gather all the data in form 6, plus connected forms in the Primary Relationship
// Limit the data to only data where the value of the element 'order_date' is
// in the year 2029
// Filter strings use /**/ to separate component parts
// The final component is the operator. Defaults to LIKE if not specified.
$formId = 6;
$filter = "order_date/**/2029";
$data = gatherDataset($formId, filter: $filter);
~~~

~~~php
// Gather all the data in form 6, plus connected forms in the Primary Relationship
// Limit the data to only data where the value of the element 'order_date' is
// between Jan 1 2029 and Mar 31 2029
// Filter strings use /**/ to separate component parts
// The final component is the operator. Defaults to LIKE if not specified.
// Multiple filter strings can be concatenated with ][
$formId = 6;
$filter = "order_date/**/2029-01-01/**/>=][order_date/**/2029-03-31/**/<=";
$data = gatherDataset($formId, filter: $filter);
~~~

~~~php
// Gather all the data in form 6, plus connected forms in the Primary Relationship
// Limit the data to only data where the value of the element 'order_date' is
// between Jan 1 2029 and Mar 31 2029, or where the status is pending
// Filter strings use /**/ to separate component parts
// The final component is the operator. Defaults to LIKE if not specified.
// Multiple filter strings can be concatenated with ][
// To handle different boolean operators at different levels of a complex filter
// use an array structure as shown below.
// The andOr parameter is used between the different parts of the array, and
// each array's own 0 key determines how multiple terms within that filter
// are interpreted.
$formId = 6;
$statusFilter = "order_status/**/Pending/**/=";
$dateFilter = "order_date/**/2029-01-01/**/>=][order_date/**/2029-03-31/**/<=";
$filter = array(
	0 => array("AND", $statusFilter),
	1 => array("AND", $dateFilter)
);
$data = gatherDataset($formId, filter: $filter, andOr: "OR");

//essentially, this results in the query structure:
// ( order_status = Pending OR ( order_date >= Jan 1 2029 AND order_date <= Mar 31 2029 ) )
~~~
