---
layout: default
permalink: developers/API/classes/data_handler/writeEntry/
---

# writeEntry( <span style='font-size: 14pt;'>(int | string) $entry_id, (array) $values, (int) $proxyUser, (bool) $forceUpdate = false, (bool) $update_metadata = true</span> )

## Description

Writes values to the database for a given record, or for a new record. Values are meant to be the database-ready values, ie: foreign keys rather than human-readable values in the case of linked elements, multiple values joined with a separator string in the case of checkboxes, etc. 

Note that the form id does not need to be specified, since element ids and handles are globally unique, so they identify the form implicitly.

This method generates the default values for any elements that are not included in the $values array, if the entry being written is new. This ensures that all entries start out with the correct default values set in the database.

This method calls the "On Before Save" and "On After Save" procedures, if any, for the form where data is being written. On Before Save might modify the values being written to one or more elements.

This method updates the revision table for a form, if the form has the revision table feature turned on.

__This method does _not_ write any entry ownership information to the Entry Owner Groups database table__. Therefore, it is most suitable for surgical modifications to existing entries. If you are writing a new entry to the database, you will need to set the entry owner groups with the [setEntryOwnerGroups](../setEntryOwnerGroups/) method, or you can use the [formulize_writeEntry](../../../functions/formulize_writeEntry/) function instead of this method.

## Parameters

__$entry_id__ - The entry id being updated, or "new" for creating a new entry.<br>
__$values__ - An array of the values to write to the database. The keys must be the element ids or element handles. Values must be the database-ready values for the element. "{WRITEASNULL}" indicates to write null as the value of the element. You can also simply use NULL.<br>
__$proxyUser__ - Optional. An alternate user id to use as the creator of new entries. By default, the creator is the user of the active session.<br>
__$forceUpdate__ - Optional. A boolean to indicate whether the query should be performed on GET requests. By default data can only be written to the database through a POST request.<br>
__$update_metadata__ - Optional. A boolean to indicate whether the modification user id and modification datatime should be updated when writing to an existing entry. Defaults to true. Set to false if you are updating an existing record and you do not want the modification metadata to change. 

## Return Values

Returns __the entry id of the entry that was written__ to the database.

Returns __null__ if no data was written to the database. Nothing will be written if all the values in the $values array match the existing values in the database.

If the query fails, page execution terminates and an error is displayed.

## Examples

~~~
// write a new entry in the database in form 6
$values = array(
    'survey_first_name'=>'John',
    'survey_last_name'='Smith'
);
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entry_id = $dataHandler->writeEntry("new", $values)
~~~

~~~
// Update entry 29 in the survey form with a new value for first name
// Skip updating the modification user and datetime metadata
$values = array(
    'survey_first_name'=>'Jonathan'
);
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
$entry_id = $dataHandler->writeEntry(29, $values, update_metadata: false);
~~~
