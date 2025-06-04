---
layout: default
title: Functions
permalink: developers/API/functions/
---
{% assign pages = site.pages | where_exp: "page", "page.name contains 'function-'" %}

# Functions

{% for page in pages %}
{% assign handle = page.name | replace: "function-", "" | replace: ".md", "" %}
[{{handle}}](../functions/{{handle}})
{% endfor %}

Formulize has a lot of functions that you can use. The original code made hardly any use of classes and objects (the original Formulize was compatible with PHP 4). Therefore, some of the most fundamental operations were written in functions that could be called from anywhere, much like how some classes use static methods these days.

Almost all the functions are contained in the large file located at __/modules/formulize/include/functions.php__. The location of each function is shown on its documentation page.

You can use any Formulize function throughout Formulize, for example in derived value formulas, or in screen templates. If you are writing your own PHP file and want to use Formulize functions, you just need to make sure you have bootstrapped the system with __mainfile.php__ in the root foolder, and included the __/modules/formulize/include/common.php__ file. See below for an example.

Examples:

~~~php
// bootstrap the system
// imagine we're in the root folder so we can reference mainfile simply by name
require_once "mainfile.php";
// include common.php
require_once XOOPS_ROOT_PATH."/modules/formulize/include/common.php";

// formulize_writeEntry function - write a new entry to an activity log form
// (only works when the request method is POST, or forceUpdate is true)
$values = array(
    "activity_date"=>"2023-05-09",
    "activity_name"=>"Birthday Party";
    "activity_attendees"=>99
);
$entry_id = formulize_writeEntry($values, forceUpdate: true);
~~~

~~~php
// getCurrentURL function - check the current URL for a particular string
if(strstr(getCurrentURL(), 'fid=6')) {
    print "This page is displaying form 6";
}
~~~





