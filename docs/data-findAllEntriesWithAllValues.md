---
layout: default
permalink: developers/API/classes/data_handler/findAllEntriesWithAllValues/
---

# findAllEntriesWithAllValues( <span style='font-size: 14pt;'>(array) $elementsAndValues, (string | array) $operator = "=", (string) $fieldsToReturn = "entry_id" </span> )

## Description

Gets all the entries that match all of the values specified. Values must match the raw value stored in the database, and won't necessarily be human readable (could be an id number, etc). Returns an array of results.

## Parameters

__$elementsAndValues__ - an array of key=>value pairs, where the keys are the element identifiers and the values are the values to look for. Only entries that match every pair will be returned.<br>
__$operator__ - Optional. the operator to use in when querying for the values. If this is a string, then that same operator is used for all key=>value pairs. If it is an array of strings, then each string is used for the corresponding item in the _$elementsAndValues_ array, ie: first operator for first element/value pair, second operator for second pair, etc. Defaults to equals. Any valid SQL operator can be used. If LIKE is used, _then the values will be automatically wrapped in % signs_ to support pattern matching. If the IN operator is used, the values must be a valid comma separated set of values that will work in a SQL statement. This means strings need to be quoted.
__$fieldsToReturn__ - Optional. the fields to select out of the database. Defaults to _entry_id_. Separate multiple fields with commas, refer to them by their element handles.

## Return Values

Returns __an array of results__, where each item in the array is one record, and each record contains an array where the keys are the field names requested (defaults to _entry_id_) and the values are the values from the database. If _entry_id_ was one of the fields returned, the keys for each record will be the entry ids, otherwise they are sequential from zero.

Returns __false__ if the query fails, or if the query finds no entries that match the values.

## Example

~~~
// find all entries created that have 'blue' as the value for the 'colour' element,
// and 'hot' as the value for the 'temperature' element, in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entries = $dataHandler->findAllEntriesWithAllValues(array(
    'colour'=>'blue',
    'temperature'=>'hot'
));
~~~

~~~
// find all the entries created where the value for element 99 is 'goals' or 'assists'
// uses the IN operator to simulate 'or'
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$values = array(
    99=>"'goals','assists'";
);
$entries = $dataHandler->findFirstEntryWithAllValues($values, "IN");
~~~

~~~
// return all the entries in form 6 where the city is Toronto, and include the entry_id and player fields
// Note the operator will be = implicitly, because that parameter has been skipped when the method is called
// Note also that the third parameter has to be named specifically in order to skip the operator parameter
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$values = array(
    'city'=>'Toronto';
);
$fieldsToReturn = 'entry_id, player'
$entries = $dataHandler->findFirstEntryWithAllValues($values, fieldsToReturn: $fieldsToReturn);

var_dump($entries);
// will output an array something like this (note the entry ids are used as keys since entry_id was a requested field):
// 34=>array('entry_id'=>34, 'player'=>'Matthews'),
// 125=>array('entry_id'=>125, 'player'=>'Timashov')
~~~
