---
layout: default
permalink: developers/Public_API/
title: Public API
---

# Public API

Formulize has a public API that currently behaves in a REST-like manner. There are two initial operations that the public API supports:

* [api status](status)
* [queue processing](queue)

## Enabling the Public API

The Public API must be enabled through the Formulize preferences, and a rewrite rule needs to be added to your server, so that API requests are routed to the correct place.

If you are also using Alternate URLs in your Formulize instance, you need to ensure that the Alternate URL rewrite rules don't interfere with the Public API rewrite rules. This generally means that you should handle the Public API rules first, and then the Alternate URL rules second.

Rewrite rules for the Public API need to look like this:

```
RewriteEngine On<br>
RewriteCond %{REQUEST_URI} ^/formulize-public-api/ [NC]
RewriteCond %{REQUEST_FILENAME} !-f<br>
RewriteCond %{REQUEST_FILENAME} !-d<br>
RewriteCond %{REQUEST_FILENAME} !-l<br>
RewriteRule ^(.*)$ /modules/formulize/public_api/index.php?apiPath=$1 [L]
```
