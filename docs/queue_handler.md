---
layout: default
permalink: developers/API/classes/queue_handler/
title: Queue Handler
---
{% assign pages = site.pages | where_exp: "page", "page.name contains 'queue-'" %}

# Queue Handler

## Methods
{% for page in pages %}
{% assign handle = page.name | replace: "queue-", "" | replace: ".md", "" %}
[{{handle}}](../queue_handler/{{handle}})
{% endfor %}

## Description

This class provides methods for setting up and interacting with queues of PHP code that you want to run outside a normal Formulize page loading process.

## Location

/modules/formulize/class/queue.php

## Inclusion

This file is normally included as part of any Formulize page load. If it is not available, use `include_once XOOPS_ROOT_PATH.'/modules/formulize/class/queue.php';` to include it.

A queue handler object can be created using the _xoops_getModuleHandler_ function like below:

## Example

~~~
$queueHandler = xoops_getModuleHandler('queue', 'formulize');
~~~

