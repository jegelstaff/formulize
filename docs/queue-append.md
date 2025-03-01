---
layout: default
permalink: developers/API/classes/queue_handler/append/
---

# append( <span style='font-size: 14pt;'>(object|string) $queue_or_queue_handle, (string) $code, (string), $item = '', (boolean) $allowDuplicates = false</span> )

## Description

Creates a file in the modules/formulize/queue folder containing the PHP code passed to it. The file is named with the current timestamp, the queue handle, and the item description, if any.

## Parameters

__$queue_or_queue_handle__ - [a queue object](../../queue_object), or a string used to identify the queue<br>
__$code__ - a string of PHP code that will be executed when the queue is processed. The code will be executed in the context of a fully bootstrapped Formulize system, but it will not have access to any of the local or global variables currently in scope at the moment the _append_ method is called. Therefore you must include any variable declarations in the code, or you must ensure that literal values are included in the string. See example below.<br>
__$item__ - Optional - a string description of this particular code snippet. If present, this will be used in the filename in the modules/formulize/queue folder that contains the code, so it is easier to identify the files. This will also show up in Formulize logs if you have logging turned on in the Formulize preferences.<br>
__$allowDuplicates__ - Optional - if this is set to _true_, then the code will be added to the queue, regardless of whether a file already exists with the same _queue_handle_ and _item_ description. By default, duplicates are not added to the queue.

## Return Values

Returns __null__ if nothing was written to the queue because an item with that description already exists in the queue.

Returns __the number of bytes written to the file__ if writing to the queue was successful, or __false__ if the writing failed or if the code failed a PHP syntax check prior to writing (syntax check is only performed if your server supports doing so via shell_exec).

## Examples

~~~
// loop through a series of entries and add code to the queue that will
// update the derived values for each one

$formId = 6;
$dataHandler = new formulizeDataHandler($formId);
$userIds = array(24, 36, 38);
$entryIds = $dataHandler->findAllEntriesForUsers($userIds);

$queueHandler = xoops_getModuleHandler('queue', 'formulize');
$queue = $queueHandler->create('my-queue');

foreach($entryIds as $entryId) {
    // Note that $entryId and $formId will be inserted into this string as their 
    // literal values because variables in double quotes are evaluated by PHP when
    // strings are assigned.
    // This is important because when the queue is processed, only the code in the 
    // string is executed, and the declared value of the variables above will not 
    // be available, so by ensuring the literal values are part of the string,
    // the code will explicitly reference the correct entry and form.
    $code = "formulize_updateDerivedValues($entryId, $formId)";
    $queueHandler->append($queue, $code, "Updating derived values in entry $entryId in form $formId");
}
~~~
