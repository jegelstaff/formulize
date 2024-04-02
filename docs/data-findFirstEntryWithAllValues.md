---
layout: default
permalink: developers/API/classes/data_handler/findFirstEntryWithAllValues/
---

# findFirstEntryWithAllValues( <span style='font-size: 14pt;'>(array) $elementsAndValues, (string | array) $operator = "=", (string) $fieldsToReturn = "entry_id" </span> )

## Description

Gets the first entry id which matches all of the values specified for the corresponding elements specified. Values must match the raw value stored in the database, and won't necessarily be human readable (could be an id number, etc).

## Parameters

__$elementsAndValues__ - an array of key=>value pairs, where the keys are the element identifiers and the values are the values to look for. Only entries that match every pair will be returned.<br>
__$operator__ - Optional. the operator to use in when querying for the values. If this is a string, then that same operator is used for all key=>value pairs. If it is an array of strings, then each string is used for the corresponding item in the _$elementsAndValues_ array, ie: first operator for first element/value pair, second operator for second pair, etc. Defaults to equals. Any valid SQL operator can be used. If LIKE is used, _then the values will be automatically wrapped in % signs_ to support pattern matching. If the IN operator is used, the values must be a valid comma separated set of values that will work in a SQL statement. This means strings need to be quoted.
__$fieldsToReturn__ - Optional. the fields to select out of the database. Defaults to _entry_id_. Separate multiple fields with commas, refer to them by their element handles.

## Return Values

Returns the __first (earliest) entry id found__, or __an alternate field requested with _$fieldsToReturn_.__ If multiple fields were requested then it returns __an array of the values of the requested fields__, where the keys are the element handles requested.

Returns __null__ if no data is found.

Returns __false__ if the query fails.

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
// uses the IN operator to simulate 'or'
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$values = array(
    99=>"'goals','assists'";
);
$entry_id = $dataHandler->findFirstEntryWithAllValues($values, "IN");
~~~

~~~
// return the entry id and the player from the first entry in form 6 where the city is Toronto
// Note the operator will be = implicitly, because that parameter has been skipped when the method is called
// Note also that the third parameter has to be named specifically in order to skip the operator parameter
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$values = array(
    'city'=>'Toronto';
);
$fieldsToReturn = 'entry_id, player'
$values = $dataHandler->findFirstEntryWithAllValues($values, fieldsToReturn: $fieldsToReturn);

var_dump($values); // will output an array something like this: 'entry_id'=>125, 'player'=>'Timashov'
~~~
