---
layout: default
permalink: developers/API/functions/getValue/
title: getValue
---

# getValue( <span style='font-size: 14pt;'>(array) $entry, (string) $handle = "", (int) $datasetKey = null, (int) $localEntryId = null, (boolean) $raw = false</span> )

## Location

/modules/formulize/include/extract.php

## Description

Get the value of a particular form element in a particular entry in a dataset. Dataset entries may be made up of underlying entries from one or more forms. Create datasets using the [gatherDataset](../gatherDataset) function.

## Parameters

__$entry__ - A single item in a dataset, generally one entry in the main form of the dataset, plus any connected entries from other forms in the relationship the dataset was based on. An entire dataset can be passed, and then the _datasetKey_ parameter must be used to specify which item in the dataset you want to get a value from.<br>
__$handle__ - The element handle of the element in the dataset that you want to get the value for.<br>
__$datasetKey__ - Optional. If an entire dataset is passed in, then the number of the item in the dataset that you want to get ids for, must be specified. Items are numbered sequentially from 0. Generally this is not necessary and not used, because normally individual items within a dataset are passed to this function, see the examples.<br>
__$localEntryId__ - Optional. If specified, the values included will be limited to the values from this particular underlying entry id in the form that the _handle_ belongs to. By default, all values of the specified _handle_ from all entries that are part of this dataset item will be returned.<br>
__$raw__ - Optional. A flag to indicate if the raw value from the database should be returned, or whether the database value should be prepped for human readability. Some database values are encoded for the computer to use. By default, values are prepped for readability and returned. If you are gathering data for use in subsequent queries of some kind, you may want or need the raw value from the database.

## Return Values

Returns the value of _handle_ in the dataset item that was passed to the function. If there is only a single value of _handle_ then a single value is returned. If there are multiple values for _handle_ then an array of values is returned.

There can be multiple values for _handle_ if that form element supports multiple values being selected, ie: if it is a checkbox series (unless raw values are being returned in which case the single database representation of all the selections would be returned).

There can also be multiple values for _handle_ if there are multiple underlying entries from a form within the dataset. In that case, the values for _handle_ from all the entries are collected into an array and returned.

## Examples

~~~php
// gather all the data in form 6 alone, no connections
$formId = 6;
$data = gatherDataset($formId, frid: 0);

// Iterate through the data and for each entry in a dataset, print out the value of 'fruit_name'
// Dataset entries may be made up of underlying entries from one or more forms
foreach($data as $entry) {
	print getValue($entry, 'fruit_name');
}
~~~

~~~php
// Gather all the data in form 11 (orders), plus connected forms in the Primary Relationship
// Print out all the fruit from each order (there may be multiple fruit entries in each item
// in the dataset)
$orderFormId = 11;
$data = gatherDataset($orderFormId);
foreach($data as $entry) {
	$fruit = getValue($entry, 'fruit_name');
	if(is_array($fruit)) {
		print "Fruit in order: ".implode(", ", $fruit);
	} else {
		print "Fruit in order: $fruit";
	}
}
~~~

~~~php
// Gather all the data in form 6 (fruit), plus connected forms in the Primary Relationship.
// For each fruit, print out some order info for the first order in the dataset associated with
// that fruit. Isolate the first order based on the underlying entry ids of the Orders form,
// within each item in the dataset.
$fruitFormId = 6;
$orderFormId = 11;
$data = gatherDataset($fruitFormId);
foreach($data as $entry) {
	$orderFormEntryIds = getEntryids($entry, $orderFormId);
	$firstOrderEntryId = $orderFormEntryIds[0];
	$firstOrderDate = getValue($entry, 'order_date', localEntryId: $firstOrderEntryId);
	$firstOrderIncoTerms = getValue($entry, 'order_incoterms', localEntryId: $firstOrderEntryId);
	print "First order date and incoterms: $firstOrderDate, $firstOrderIncoTerms";
}
~~~

~~~php
// Gather only the fruit that was selected for each order, no other information.
// Imagine the fruit element in each order is linked to the Fruit form. Get the raw
// value of that element which will be the entry id of the Fruit record in the database
// (a foreign key).
$orderFormId = 11;
$data = gatherDataset($orderFormId, array($orderFormId=>array('order_fruit')), frid: 0);
foreach($data as $entry) {
	$fruitEntryId = getValue($entry, 'order_fruit', raw: true);
	print "The entry id in the database of the fruit that was ordered, is: $fruitEntryId";
}
~~~
