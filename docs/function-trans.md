---
layout: default
permalink: developers/API/functions/trans/
---

# trans( <span style='font-size: 14pt;'>(string) $string, (string) $lang = null</span> )

## Location

/modules/formulize/include/functions.php

## Description

Removes the language tags from a string, and leaves only the active language text, or the specified language text if one is specified.

Users generally have some mechanism to control which language is active at the time. There is some browser language detection in effect also, which can make an initial determination automatically sometimes.

Users can force a certain language to be active by appending `&lang=fr` for example to the URL.

## Parameters

__$string__ - The string containing the language text, ie: [en]English text[/en][fr]texte fran&ccedil;ais[/fr]<br>
__$lang__ - Optional. The langauge tag of the language you wish to keep in the text.

## Return Values

Returns __the string with the text for the other language(s) removed__.

## Examples

~~~
// convert the string to the currently active language
$string = trans("[en]English text[/en][fr]texte fran&ccedil;ais[/fr]");
~~~

~~~
// convert the string to the French, regardless of the active language
$string = trans("[en]English text[/en][fr]texte fran&ccedil;ais[/fr]", "fr");
~~~

~~~
// convert the string to the currently active language, then truncate it to five characters
$string = trans("[en]English text[/en][fr]texte fran&ccedil;ais[/fr]");
$string = printSmart($string, 5);
~~~


