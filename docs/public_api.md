---
layout: default
permalink: developers/Public_API/
title: Public API
---

# Public API

Formulize has a public API that currently behaves in a REST-like manner. The public API supports these operations:

* [api status](status)
* [queue processing](queue)
* [reading entries from a form](read)

## Enabling the Public API

The Public API must be enabled through the Formulize preferences, and a rewrite rule needs to be added to your server, so that API requests are routed to the correct place.

If you are also using Alternate URLs in your Formulize instance, you need to ensure that the Alternate URL rewrite rules don't interfere with the Public API rewrite rules. This generally means that you should handle the Public API rules first, and then the Alternate URL rules second.

Rewrite rules for the Public API need to look like this:

```
RewriteEngine On

RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

RewriteCond %{REQUEST_URI} ^/formulize-public-api/ [NC]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-l
RewriteRule ^(.*)$ /modules/formulize/public_api/index.php?apiPath=$1 [L,B,QSA]
```

## The Authorization header

The two lines above that mention `Authorization` are there to make **API keys** work. Many server configurations - CGI, and some FastCGI and PHP-FPM setups - **remove that header before PHP ever sees it**, unless they are explicitly told not to. This is a property of the web server, not of Formulize, and it affects any application that authenticates this way.

### Checking whether your server passes it

The status endpoint reports this directly. Send it a request with any `Authorization` header:

```
curl -H "Authorization: Bearer anything" https://yoursite.org/formulize-public-api/v1/status
```

```json
{
    "status": "healthy",
    "timestamp": 1789260647,
    "version": "1",
    "authorization_header_received": true
}
```

If `authorization_header_received` is `false`, your server is stripping the header and no API key
will ever work. The key itself does not need to be valid for this check - the status endpoint does
not authenticate, it only reports whether the header arrived.

Formulize also checks this for you. If it finds the header is being stripped, a warning appears
in the Formulize settings under the Public API setting, and on the **API keys** page, with a
button that re-runs the test after you have changed your server configuration. A request that is
refused on permissions while carrying no `Authorization` header also gets an extra `hint` field
in its error response explaining both possibilities.

### Fixing it

The two `RewriteCond`/`RewriteRule` lines shown above are the recommended fix. They copy the
header into an environment variable that Formulize also reads, they need nothing beyond
`mod_rewrite` - which the Public API already requires - and they are safe on every Apache
version.

If your server still strips it, the other approach on Apache 2.4.13 and later is:

```
CGIPassAuth On
```

in the same `.htaccess` file. This is cleaner where it works, but it requires `AllowOverride` to
permit `AuthConfig`, and it will produce a 500 error on older Apache versions, which is why the
rewrite form is given first.

On nginx, the equivalent is to make sure the `HTTP_AUTHORIZATION` fastcgi parameter is passed
through to PHP:

```
fastcgi_param HTTP_AUTHORIZATION $http_authorization;
```

Note that if your site sits behind a proxy, load balancer, or CDN, that layer can strip the
header too, before the request ever reaches your web server. Formulize's own check cannot see
that, because it tests by making a request to itself and never goes out through the proxy. If
the check says the header is fine but your keys still do not work, look there next.

### What is not affected

- **Requests from pages on your own site.** Browser Javascript on this site is authenticated by
  the visitor's existing login session, and never sends an API key.
- **Anonymous access.** Forms you have opened to the Anonymous group are readable with no key at
  all.
- **Keys passed in a URL**, such as the `makecsv.php?key=...` address used for Google Sheets.
  Those are query string parameters, not headers.
- **The embedded AI Assistant**, which runs inside Formulize using your own login.

The **MCP Server** setting *is* affected, and more strictly: an external AI assistant has no way
to sign in other than an API key in this header, so Formulize will not let that setting be turned
on at all until the header gets through.
