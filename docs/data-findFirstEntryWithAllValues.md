---
layout: default
permalink: developers/API/classes/data_handler/findFirstEntryWithAllValues/
---

# findFirstEntryWithAllValues( <span style='font-size: 14pt;'>(array) $elementsAndValues, (string | array) $operator = "="</span> )

## Description

Gets the first entry id which matches all of the values specified for the corresponding elements specified. Values must match the raw value stored in the database, and won't necessarily be human readable (could be an id number, etc).

## Parameters

__$elementsAndValues__ - an array of key=>value pairs, where the keys are the element identifiers and the values are the values to look for. Only entries that match every pair will be returned.<br>
__$operator__ -  Optional.  the operator to use in when querying for the values. If this is a string, then that same operator is used for all key=>value pairs. If it is an array of strings, then each string is used for the corresponding item in the _$elementsAndValues_ array, ie: first operator for first element/value pair, second operator for second pair, etc. Defaults to equals. Any valid SQL operator can be used. If LIKE is used, _then the values will be automatically wrapped in % signs_ to support pattern matching. If the IN operator is used, the values must be a valid comma separated set of values that will work in a SQL statement. This means strings need to be quoted.

## Return Values

Returns the __first (earliest) entry id found__.

Returns __false__ if the query fails, or if the query finds no entries that match the values.

## Example

~~~
// find the first entry created that has 'blue' as the value for the 'colour' element,
// and 'hot' as the value for the 'temperature' element, in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entry_id = $dataHandler->findFirstEntryWithAllValues(array(
    'colour'=>'blue',
    'temperature'=>'hot'
));
~~~

~~~
// find the first entry created where the value for element 33 contains 'foo'
// and the value for element 99 contains 'bar', in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$values = array(
    33=>'foo',
    99=>'bar'
);
$entry_id = $dataHandler->findFirstEntryWithAllValues($values, "LIKE");
~~~

~~~
// find the first entry created where the value for element 99 is 'goals' or 'assists'
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$values = array(
    99=>"'goals','assists'";
);
$entry_id = $dataHandler->findFirstEntryWithAllValues($values, "IN");
~~~
