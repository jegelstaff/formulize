---
layout: default
permalink: developers/API/classes/queue_handler/process/
title: process
---

# process( <span style='font-size: 14pt;'>(object|string) $queue_or_queue_handle</span> )

## Description

Triggers the processing of the items in the specified queue. If the _exec_ function is available in PHP, then the queue will be processed asynchronously, otherwise, the queue will be processed for approximately 60 seconds as part of the current http request. Once PHP has executed a file in the queue, the file is deleted.

Queue processing can be triggered by the [Public API](../../../../Public_API).

If the queue is processed asynchronously, certain PHP environment constraints may timeout the processing after many minutes, depending on your server configuration. If this is a problem, you should work with your server admin to remove or extend the timeout limits.

If the queue processing times out, triggering the processing of the queue again will pick up where the previous process left off. It is possible that the last item from the timed out processing will be processed again when the queue resumes.

## Parameters

__$queue_or_queue_handle__ - [a queue object](../../queue_object), or a string used to identify the queue

## Return Values

Returns __true__ if asynchronous queue processing was triggered. Returns __an array__ of the filenames that were processed, if the queue was triggered synchronously as part of the http request.

## Examples

~~~
$queueHandler = xoops_getModuleHandler('queue', 'formulize');
$queueHandler->process('my-queue');
~~~
