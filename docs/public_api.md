---
layout: default
permalink: documentation/Public_API/
redirect_from:
 - developers/Public_API/
title: Public API
---

# Public API

Formulize has a public API that currently behaves in a REST-like manner. The public API supports these operations:

* [api status](status)
* [queue processing](queue)
* [reading entries from a form](read)

Requests that choose which entries to work with use the same [filter format](filters).

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

These rules go in the `.htaccess` file in the root folder of your website, the folder that contains `mainfile.php`.

If your website is in a subfolder of its domain, such as `https://example.org/system`, then two of the lines need that subfolder at the start of their paths:

```
RewriteCond %{REQUEST_URI} ^/system/formulize-public-api/ [NC]
...
RewriteRule ^(.*)$ /system/modules/formulize/public_api/index.php?apiPath=$1 [L,B,QSA]
```

Until the Public API is enabled, the Formulize preferences page shows these rules under the Public API setting, with the correct paths for your website already filled in.

## Authentication

A request either carries an API key or it does not.

__With an API key__, the request runs as the user the key belongs to, and sees exactly the data that user can see. Send the key as an `Authorization: Bearer` header. Create keys on the __Manage API Keys__ page in the Formulize admin.

```
Authorization: Bearer 8f3ca19d...
```

__Without an API key__, the request runs as the anonymous user. This is a supported way to publish data, not an error. It returns nothing at all unless an administrator has granted the Anonymous group permission to view the form, so nothing is exposed by accident.

An API key gives access to Formulize in exactly the same way as logging in with that user's username and password, and anyone who can see the key can use it. A key in the Javascript of a web page can be read by everyone who loads that page. So give the key to a user who can see only what those visitors should see, or rely on anonymous access if the data is meant for the public.

Use `POST` rather than `GET` when you send a key, where the endpoint allows it. Some servers and proxies record full URLs in their logs, and a browser keeps them in its history, so anything in a query string can end up stored somewhere you did not intend.

## The Authorization header

The two lines above that mention `Authorization` are there to make **API keys** work. Many server configurations - CGI, and some FastCGI and PHP-FPM setups - **remove that header before PHP ever sees it**, unless they are explicitly told not to. This is a property of the web server, not of Formulize, and it affects any application that authenticates this way.

### Checking whether your server passes it

Formulize checks this for you. If it finds that your server is stripping the header, a warning
appears in the Formulize settings under the Public API setting, and on the **API keys** page, with
a button that re-runs the test after you have changed your server configuration.

No warning means the test did not find a problem. The test is a request your server makes to
itself, though, so it cannot see a proxy, load balancer, or CDN in front of your site, and those
can strip the header too. If API keys still do not work, see the troubleshooting steps below.

### Troubleshooting

To check by hand, send the status endpoint a request with any `Authorization` header:

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

A request that is refused on permissions while carrying no `Authorization` header also gets an
extra `hint` field in its error response, explaining that the request was handled as anonymous,
or that the server may have stripped the key.

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

### What is not affected by the Authorization Header

- **Requests from pages on your own site.** Browser Javascript on this site is authenticated by
  the visitor's existing login session, and never sends an API key.
- **Anonymous access.** Forms you have opened to the Anonymous group are readable with no key at
  all.
- **Keys passed in a URL**, such as the `makecsv.php?key=...` address used for Google Sheets.
  Those are query string parameters, not headers.
- **The embedded AI Assistant**, which runs inside Formulize using your own login.

The **External AI** setting *is* affected, and strictly: an external AI assistant has no way
to sign in other than an API key in this header, so Formulize will not let the _External AI_ setting be turned
on at all until the header gets through.
