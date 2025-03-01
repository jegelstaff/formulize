---
layout: default
permalink: developers/API/classes/queue_handler/delete/
---

# delete( <span style='font-size: 14pt;'>(string) $queue_handle</span> )

## Description

Removes all the files in the modules/formulize/queue folder that are part of the specified queue.

## Parameters

__$queue_handle__ - a string used to identify the queue

## Return Values

Returns true (!).

## Example

~~~
$queueHandler = xoops_getModuleHandler('queue', 'formulize');
$queueHandler->delete('my-queue');
~~~
