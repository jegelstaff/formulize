---
layout: default
permalink: documentation/classes/data_handler/findAllEntriesWithAllValues/
redirect_from:
 - developers/classes/data_handler/findAllEntriesWithAllValues/
 - developers/API/classes/data_handler/findAllEntriesWithAllValues/
title: findAllEntriesWithAllValues
---

# <span class="sig-name">findAllEntriesWithAllValues</span>( <span class="sig-arg"><span class="sig-type">(array)</span> $elementsAndValues</span>, <span class="sig-arg"><span class="sig-type">(string | array)</span> $operator <span class="sig-default">= "="</span></span>, <span class="sig-arg"><span class="sig-type">(string)</span> $fieldsToReturn <span class="sig-default">= "entry_id"</span></span> )

## Description

Gets all the entries that match all of the values specified. Values must match the raw value stored in the database, and won't necessarily be human readable (could be an id number, etc). Returns an array of results.

## Parameters

__$elementsAndValues__ - an array of key=>value pairs, where the keys are the element identifiers and the values are the values to look for. Only entries that match every pair will be returned.<br>
__$operator__ - Optional. the operator to use in when querying for the values. If this is a string, then that same operator is used for all key=>value pairs. To use different operators for different elements, pass an array of operators keyed by the same element handles or ids as _$elementsAndValues_, ie: `array('colour'=>'=', 'price'=>'>')`. This is the recommended way. The keys must be the same kind as the keys of _$elementsAndValues_: handles for handles, ids for ids. An array without those keys is matched up by position instead: first operator for first element/value pair, second operator for second pair, etc. Don't match by position when the elements are referred to by id, because an operator's position can be taken for an element id. Defaults to equals. Any valid SQL operator can be used. If LIKE is used, _then the values will be automatically wrapped in % signs_ to support pattern matching. If the IN operator is used, the values must be a valid comma separated set of values that will work in a SQL statement. This means strings need to be quoted.<br>
__$fieldsToReturn__ - Optional. the fields to select out of the database. Defaults to _entry_id_. Separate multiple fields with commas, refer to them by their element handles.

## Return Values

Returns __an array of results__, where each item in the array is one record, and each record contains an array where the keys are the field names requested (defaults to _entry_id_) and the values are the values from the database. If _entry_id_ was one of the fields returned, the keys for each record will be the entry ids, otherwise they are sequential from zero.

Returns __null__ if no data is found.

Returns __false__ if the query fails.

## Example

~~~php
// find all entries created that have 'blue' as the value for the 'colour' element,
// and 'hot' as the value for the 'temperature' element, in the Products form
$dataHandler = new formulizeDataHandler(PRODUCTS_FORM_ID);
$entries = $dataHandler->findAllEntriesWithAllValues(array(
    'colour'=>'blue',
    'temperature'=>'hot'
));
~~~

~~~php
// find all the entries created where the value for element 99 is 'goals' or 'assists'
// uses the IN operator to simulate 'or'
$dataHandler = new formulizeDataHandler(PLAYERS_FORM_ID);
$values = array(
    99=>"'goals','assists'"
);
$entries = $dataHandler->findAllEntriesWithAllValues($values, "IN");
~~~

~~~php
// find all the entries where the 'colour' element is 'blue'
// and the 'price' element is more than 100, in the Products form
// each operator is keyed by the element it applies to
$dataHandler = new formulizeDataHandler(PRODUCTS_FORM_ID);
$values = array(
    'colour'=>'blue',
    'price'=>100
);
$operators = array(
    'colour'=>'=',
    'price'=>'>'
);
$entries = $dataHandler->findAllEntriesWithAllValues($values, $operators);
~~~

~~~php
// return all the entries in the Players form where the city is Toronto,
// and include the entry_id and player fields
// Note the operator will be = implicitly, because that parameter
// has been skipped when the method is called
// Note also that the third parameter has to be named specifically
// in order to skip the operator parameter
$dataHandler = new formulizeDataHandler(PLAYERS_FORM_ID);
$values = array(
    'city'=>'Toronto'
);
$fieldsToReturn = 'entry_id, player';
$entries = $dataHandler->findAllEntriesWithAllValues($values, fieldsToReturn: $fieldsToReturn);

var_dump($entries);
// will output an array something like this (note the entry ids are used
// as keys since entry_id was a requested field):
// 34=>array('entry_id'=>34, 'player'=>'Matthews'),
// 125=>array('entry_id'=>125, 'player'=>'Timashov')
~~~
