---
layout: default
permalink: documentation/functions/printSmart/
redirect_from:
 - developers/functions/printSmart/
 - developers/API/functions/printSmart/
title: printSmart
---

# <span class="sig-name">printSmart</span>( <span class="sig-arg"><span class="sig-type">(string)</span> $value</span>, <span class="sig-arg"><span class="sig-type">(int)</span> $chars <span class="sig-default">= 35</span></span> )

## Location

/modules/formulize/include/functions.php

## Description

Truncates a string to a given length, and appends "..." onto the end if necessary.

Take care when using this function with text that contains HTML tags, since you can end up removing the closing tags, and the entire page may not render in the browser! It is probably best to remove the HTML tags first, and convert any HTML special chars.

Take care when using this function with text that contains language tags (ie: [en]English text[/en][fr]texte fran&ccedil;ais[/fr]), since you can cut off the closing tags and the entire remainder of the page may be removed by the language tag processing function. Use the [trans](../trans/) function on the text first.

This function is multibyte aware and should operate OK with text that contains emojis, and Chinese, Japanese, and Korean characters.

## Parameters

__$value__ - the text to truncate.<br>
__$chars__ - Optional. The number of characters to keep, before adding "...", defaults to 35.

## Return Values

Returns the __truncated string__, or the __original string__ if it is shorter than the number of chars.

## Examples

~~~php
// Shorten a string
$string = "Now is the time for all good men to come to the aid of the party";
$string = printSmart($string);
~~~

~~~php
// Shorten a string only if it is longer than 100 characters
$string = "Now is the time for all good men to come to the aid of the party";
$string = printSmart($string, 100);
~~~

~~~php
// Remove HTML tags and convert special chars
// before running text through printSmart
$html = "<strong>Now&#039;s the time for<br>all good men"
    . " to come to the aid of the party</strong>";
$string = htmlspecialchars_decode(strip_tags($html), ENT_QUOTES);
$string = printSmart($string);
~~~

~~~php
// Translate language strings before running text through printSmart
$string = trans(
    "[en]Now is the time for all good men to come to the aid of the party[/en]"
    . "[fr]Il est maintenant temps pour tous les bons hommes"
    . " de venir en aide au parti politique[/fr]."
);
$string = printSmart($string);
~~~
