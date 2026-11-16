---
layout: default
permalink: documentation/functions/formulize_aesKeySQL/
title: formulize_aesKeySQL
---

# <span class="sig-name">formulize_aesKeySQL</span>( )

## Location

/modules/formulize/include/functions.php

## Description

What to write in your own SQL where the key for encrypted elements goes, when you read or write an encrypted element's field directly. It's the name of a database session variable that holds the key, so the key itself is never in your query. Your query can be logged, shown in the Developer Dashboard or printed in an error message without giving the key away.

Write it as it is, **without quotes**. It's the name of a variable, not a string.

Most code never needs this. [gatherDataset](../gatherDataset), [getValue](../getValue) and the data handler give you the values people typed, and [formulize_writeEntry](../formulize_writeEntry) encrypts what you save.

### Replacing getAESPassword()

`getAESPassword()` has been removed. It returned the key itself, so any query that used it had the key in it. If your custom code calls it, the page stops with an error saying the function is undefined. Change each call to `formulize_aesKeySQL()`, and take away the quotes around it:

~~~php
// before
$sql = "SELECT AES_DECRYPT(`notes`, '" . getAESPassword() . "') FROM " . $xoopsDB->prefix("formulize_clients");

// after
$sql = "SELECT AES_DECRYPT(`notes`, " . formulize_aesKeySQL() . ") FROM " . $xoopsDB->prefix("formulize_clients");
~~~

If you leave the quotes, the database is given the variable's name as the key. Nothing you read decrypts, and anything you write can't be read back.

## Parameters

None.

## Return Values

Returns the text `@formulize_aes_key`. The first time it's called, it also puts the key in that variable for the current database connection. If it can't, it stops with an error, so nothing can be saved with no key.

## Examples

~~~php
// the clients whose encrypted notes mention a phone number
$sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_clients") . " WHERE AES_DECRYPT(`notes`, " . formulize_aesKeySQL() . ") LIKE '%phone%'";
$res = $xoopsDB->query($sql);
~~~
