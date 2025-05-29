---
layout: default
permalink: developers/API/functions/getEntryIds/
title: getEntryIds
---

# getEntryIds( <span style='font-size: 14pt;'>(array) $entry, (int|string) $formIdOrHandle = "", (int) $datasetKey = null, (boolean) $fidAsKeys = false</span> )

## Location

/modules/formulize/include/extract.php

## Description

Gets the entry ids of the constituent entries that make up part of a dataset. Datasets can be made up of multiple entries, sometimes from multiple forms, and/or sometimes including multiple entries from the same form. For more information, see [gatherDataset](../gatherDataset/).

## Parameters

__$entry__ - A single item in a dataset, generally one entry in the main form of the dataset, plus any connected entries from other forms in the relationship the dataset was based on. An entire dataset can be passed, and then the _datasetKey_ parameter must be used to specify which item in the dataset you want to get entries for.<br>
__$formIdOrHandle__ - Optional. A form id or handle representing the form in the dataset that you want to get entry ids for. If not specified, entry ids of all entries in all forms that are part of this item are returned.<br>
__$datasetKey__ - Optional. If an entire dataset is passed in, then the number of the item in the dataset that you want to get ids for, must be specified. Items are numbered sequentially from 0. Generally this is not necessary and not used, because individual items within a dataset are passed to this function, see the examples.<br>
__$fidAsKeys__ - Optional. If _formIdOrHandle_ was specified, then this will have no effect, because only that form's entry ids will be returned. If the entry ids of multiple forms are returned, then setting this to _true_ will cause the result array to be keyed with form ids. By default this is _false_ and the result array is keyed with form handles.<br>

## Return Values

Returns an array of the entry ids that make up this item in the dataset. If _formIdOrHandle_ is specified, the array will be a single dimension array where every value is an entry id. If entry ids are being gathered from all forms in the dataset item, then the array will be multidimensional, and the outer level keys will be the form handles (or form ids if _fidAsKeys_ is _true_), and then for each form there will be an array of the entry ids included in the dataset item from that form.

## Examples

~~~php
// gather all the data in form 6, plus connected forms in the Primary Relationship
$formId = 6;
$data = gatherDataset($formId);

// iterate through the data and for each item in the dataset, print out the entry ids from each form that make up each item
foreach($data as $entry) {
	$entryIds = getEntryIds($entry);
	foreach($entryIds as $formHandle=>$theseEntryIds) {
		print "$formHandle is part of this item in the dataset, and the entry ids from that form which are included are:"
		print implode(", ", $theseEntryIds); // turn the entry ids into a comma separated string
	}
}
~~~

~~~php
// gather all the data in form 6, plus connected forms in the Primary Relationship
$formId = 6;
$data = gatherDataset($formId);

// interate through the data and for each item in the dataset, get the entry ids that are included from form 11
foreach($data as $entry) {
	$form11EntryIds = getEntryIds($entry, 11);
}
~~~
