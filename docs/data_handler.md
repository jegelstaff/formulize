---
layout: default
permalink: documentation/classes/data_handler/
redirect_from:
 - developers/classes/data_handler/
 - developers/API/classes/data_handler/
title: Data Handler
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

A data handler object can be created using the _new_ keyword in PHP and by passing a form id number to the constructor. Use the form's [ID constant](/documentation/form_id_constants/) for the id number, such as _ORDERS_FORM_ID_ for a form with the handle _orders_. If there is no form with that id, the constructor throws an exception.

## Example

~~~php
$dataHandler = new formulizeDataHandler(ORDERS_FORM_ID);
~~~

## Metadata fields

Every form's entries have the same metadata fields: _entry_id_, _creation_datetime_, _creation_uid_, _creator_email_, _mod_datetime_, _mod_uid_ and _owner_groups_. You don't need a data handler to use them.

~~~php
$fields = formulizeDataHandler::getMetadataFields(); // the field names
$types = formulizeDataHandler::getMetadataFieldTypes(); // field names as keys, 'text' or 'date' as values
if (formulizeDataHandler::isMetadataField('creation_uid')) { ... }
~~~

