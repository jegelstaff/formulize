---
layout: default
permalink: developers/API/classes/queue_handler/get/
title: get
---

# get( <span style='font-size: 14pt;'>(string) $queue_handle</span> )

## Description

Gets a queue object corresponding to the queue_handle passed to it, that will include all the items already in the queue as part of its _items_ property.

If no queue by that name exists, it will create one.

A queue is a collection of files in the modules/formulize/queue folder, which contain PHP code. Each file will be executed in sequence when the queue is processed.

## Parameters

__$queue_handle__ - a string used to identify the queue

## Return Values

Returns [an object](../../queue_object) representing the queue that was created.

## Example

~~~
$queueHandler = xoops_getModuleHandler('queue', 'formulize');
$queue = $queueHandler->get('my-queue');
~~~
