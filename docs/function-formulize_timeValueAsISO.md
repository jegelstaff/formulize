---
layout: default
permalink: documentation/functions/formulize_timeValueAsISO/
title: formulize_timeValueAsISO
---

# formulize_timeValueAsISO( <span style='font-size: 14pt;'>$value, $formats = null</span> )

## Location

/modules/formulize/include/functions.php

## Description

Turns a time into the 24-hour form _HH:MM:SS_, such as _13:30:00_, whatever format it was shown in.

Time elements show times in your site's 12-hour or 24-hour time format, which you can change under __Date & time formats__ in the settings. A time in a derived value, or from [getValue](../getValue), comes in that format. Use this function on it before you work out the time between two times, or compare times, so your code keeps working if the format changes.

## Parameters

__$value__ - the time, as a time element shows it, such as _1:30pm_, or as it's stored, such as _13:30_<br>
__$formats__ - Optional. The time formats to read the time in, as one [PHP date format](https://www.php.net/manual/en/datetime.format.php) or an array of them, tried in order. By default, your site's 12-hour format and then its 24-hour format.

## Return Values

Returns __the time as HH:MM:SS__.

Returns __the value unchanged__ if it isn't a time in any of the formats, such as when it's blank.

## Examples

~~~php
// a derived value: the minutes between the times in the shift_start and shift_end elements
$start = formulize_timeValueAsISO($shift_start);
$end = formulize_timeValueAsISO($shift_end);
if($start AND $end) {
    $value = (strtotime("2000-01-01 $end") - strtotime("2000-01-01 $start")) / 60;
}
~~~
