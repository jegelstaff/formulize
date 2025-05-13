---
layout: default
permalink: developers/API/classes/queue_object/
title: Queue Object
---

# Queue Object

## Properties

* __queue_handle__ - string - the name of the queue, used to refer to the queue, and used in the filenames of the code snippets that are put into the queue
* __items__ - array - an array of the filenames that exist as part of this queue, in the modules/formulize/queue folder

## Description

This object represents a queue, which is a series of files containing PHP code, which will be executed when the queue is processed. See the [queue handler](../queue_handler) for details about working with queue objects.

## Location

/modules/formulize/class/queue.php
