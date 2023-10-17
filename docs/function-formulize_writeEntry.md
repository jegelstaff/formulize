---
layout: default
permalink: developers/API/functions/formulize_writeEntry/
---

# formulize_writeEntry( <span style='font-size: 14pt;'>(array) $values, (int | string) $entry_id = "new", $action, (int) $proxyUser, (bool) $forceUpdate = false, (bool) $writeOwnerInfo = true</span> ) 

## Location

/modules/formulize/include/functions.php

## Description

Writes values to the database for a given record, or for a new record. Values are meant to be the database-ready values, ie: foreign keys rather than human-readable values in the case of linked elements, multiple values joined with a separator string in the case of checkboxes, etc.

Also writes the entry ownership information to the database when the entry being written is a new record.

Note that the form id does not need to be specified, since element ids and handles are globally unique, so they identify the form implicitly.

This function calls the [writeEntry](../../classes/data_handler/writeEntry/) method and the [setEntryOwnerGroups](../../classes/data_handler/setEntryOwnerGroups/) methods of the [data handler](../../classes/data_handler/) class.

## Parameters

__$values__ - An array of the values to write to the database. The keys must be the element ids or element handles. "{WRITEASNULL}" indicates to write null as the value of the element. Setting actual NULL will work as well.<br>
__$entry_id__ - Optional. The entry id to write the values to. If omitted then a new entry is written to the database.<br>
__$action__ - Deprecated. Has no effect. Left in as a placeholder due to legacy code.<br>
__$proxyUser__ - Optional. An alternate user id to use as the creator and owner of _new_ entries. By default, the creator is the user of the active session. This has no effect on existing entries.<br>
__$forceUpdate__ - Optional. A boolean to indicate whether the query should be performed on GET requests. By default data can only be written to the database through a POST request.<br>
__$writeOwnerInfo__ - Optional. A boolean to indicate whether the ownership information should be written to the database. Has an effect only when $entry_id is "new". The only time you would want to set this to false, is if you are writing multiple entries to the database, and are going to use the [setEntryOwnerGroups](../../classes/data_handler/setEntryOwnerGroups/) method of the [data handler](../../classes/data_handler/) class at a later point in time, to set the ownership for all the entries at once. It is more efficient to write the ownership information all at once for multiple new entries, than to write them one at a time when the entries are inserted into the database.

## Return Values

Returns the __entry id of the entry that was written__ to the database.

Returns __nothing__ if no data was written to the database. Nothing will be written if all the values in the $values array match the existing values in the database. In this case, a notice is written to the error log.

If the first key of the $values array is invalid, then page execution terminates and backtrace information is displayed.

If the query fails, then page execution terminates and an error message is displayed. The This will occur if the second key in the $values array, or any subsequent key, is invalid.

## Examples

~~~
// write a new entry in the database
$values = array(
    'survey_first_name'=>'John',
    'survey_last_name'='Smith'
);
formulize_writeEntry($values);
~~~

~~~
// Update entry 6 in the survey form with a new value for first name
$values = array(
    'survey_first_name'=>'Jonathan'
);
formulize_writeEntry($values, 6);
~~~

~~~
// get the entry id of the 'Toronto' entry in form 6 (a catalogue of cities),
// and write a new entry in a survey form, with 'Toronto' as the value for a
// linked element that points to form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entry_id = $dataHandler->findFirstEntryWithValue('cities_name', 'Toronto');
formulize_writeEntry(array('survey_city_name'=>$entry_id));
~~~

~~~
// Write a new entry to the database, and record the owner as user 24
$values = array(
    'survey_first_name'=>'John',
    'survey_last_name'='Smith'
);
formulize_writeEntry($values, proxyUser: 24);
~~~

~~~
// Write a new entry to the database as part of a regular page load that is
// not the result of a form submission
$values = array(
    'survey_first_name'=>'John',
    'survey_last_name'='Smith'
);
formulize_writeEntry($values, forceUpdate: true);
~~~
