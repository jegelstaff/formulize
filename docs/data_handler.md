---
layout: default
permalink: developers/API/classes/data_handler/
---
{% assign pages = site.pages | where_exp: "page", "page.name contains 'data-'" %}

# Data Handler

## Methods
{% for page in pages %}
{% assign handle = page.name | replace: "data-", "" | replace: ".md", "" %}
[{{handle}}](../data_handler/{{handle}})
{% endfor %}

## Description

This class provides low level methods for interacting directly with the records in the database that have been entered through a given form. Each handler object is associated with a specific form, as declared when the constructor is called.

## Location

/modules/formulize/class/data.php

## Inclusion

This file is normally included as part of any Formulize page load. If it is not available, use `include_once XOOPS_ROOT_PATH.'/modules/formulize/class/data.php';` to include it.

A data handler object can be created using the _new_ keyword in PHP and by passing a form id number to the constructor.

## Example 

~~~
$form_id = 6;
$dataHandler = new formulizeDataHandler($form_id);
~~~

