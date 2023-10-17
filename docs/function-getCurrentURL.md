---
layout: default
permalink: developers/API/functions/getCurrentURL/
---

# getCurrentURL()

## Location

/modules/formulize/include/functions.php

## Description

Gets the current URL, including all port information, anchor values, etc. Puts together values from multiple places _that cannot always be referenced through a single variable in PHP_, so it's guaranteed to give you the current URL regardless of the site/server setup.

## Parameters

None.

## Return Values

Returns __the URL of the current page__.

## Examples

~~~
// check if the current URL includes sid=25
if(strstr(getCurrentURL(), 'sid=25')) {
    echo "screen 25 is the requested page (but not necessarily the displayed screen,
        since it could be a list and someone has drilled down into an entry)";
}
~~~
