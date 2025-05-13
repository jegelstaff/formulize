---
layout: default
permalink: developers/API/classes/queue_handler/create/
title: create
---

# create( <span style='font-size: 14pt;'>(string) $queue_handle</span> )

## Description

Creates a queue with the declared handle. A queue is a collection of files in the modules/formulize/queue folder, which contain PHP code. Each file will be executed in sequence when the queue is processed.

## Parameters

__$queue_handle__ - a string used to identify the queue

## Return Values

Returns [an object](../../queue_object) representing the queue that was created.

## Example

~~~
$queueHandler = xoops_getModuleHandler('queue', 'formulize');
$queue = $queueHandler->create('my-queue');
~~~
