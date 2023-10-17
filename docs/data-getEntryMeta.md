---
layout: default
permalink: developers/API/classes/data_handler/getEntryMeta/
---

# getEntryMeta( <span style='font-size: 14pt;'>(int) $entry_id, (bool) $updateCache = false</span> )

## Description

Gets the metadata for a given entry: __creation datetime__, __last modification datetime__, __creation user id__, and __last modification user id__. The method caches the results to avoid repeated queries for the same data. The cache can be bypassed/updated through the _$updateCache_ parameter.

Dates are easier to work with if you know the timezone name the server is located in, and then you can convert the dates to proper PHP DateTime objects.

## Parameters

__$entry_id__ - an entry id to look for<br>
__$updateCache__ - Optional. if true then the internal cache of entry metadata is bypassed and updated

## Return Values

Returns __an array of the metadata, with keys 0, 1, 2, and 3 corresponding to the creation datetime__, __last modification datetime__, __creation user id__, and __last modification user id__. The datetimes will be readable values in Y-m-d H:m:s format, ie: 2023-07-21 15:06:59

Note that the datetimes contain no timezone information, and will have been based on the timezone the database is set to use. Normally this would be the system time of the underlying operating system.

Returns __false__ if the query failed.

## Example

~~~
// get metadata for entry 19 exist in form 6
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
list($creation_datetime, $mod_datetime, $creation_uid, $mod_uid) = $dataHandler->getEntryMeta(19);

// convert the creation datetime to a PHP DateTime object, based on the timezone of the server
// (UTC in this example, your server may be different)
$creationDateTimeObject = new DateTime($creation_datetime, new DateTimeZone('UTC'));
~~~
 
