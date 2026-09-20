---
layout: default
permalink: documentation/embedding_screens/setup/
redirect_from:
 - developers/embedding_screens_setup/
 - documentation/embedding_screens_setup/
title: Giving Formulize an Address on the Host Website's Domain
---

# Giving Formulize an address on the host website's domain

This is step 3 of [Embedding screens in another website](../). Do it when signed-in
visitors need to use an embedded screen, or when anonymous visitors need to be able to submit one in
every browser.

The result is that your Formulize site answers at a second address, one that belongs to the website
doing the embedding. Both addresses serve the same site and the same data. Nothing is copied and
nothing is installed twice.

## Names used on this page

Replace these with your own throughout.

| | Example | What it is |
|---|---|---|
| **Formulize address** | `forms.mycompany.com` | Where Formulize runs now |
| **Host website** | `www.example.com` | The page that will show the screen |
| **Embedding address** | `forms.example.com` | The new name you are about to create |

The embedding address is a name on the **host website's** domain that reaches **your** server. That
is the part people find strange. It is correct, and it is the whole point of this step.

Both addresses must be `https`.

## Which address goes in the allowed-websites list

The screen's list of allowed websites takes the **host website** — `www.example.com`. It does not
take the embedding address. The iframe's `src` is the one that uses `forms.example.com`.

---

## What has to be true

Three things, however you go about arranging them:

1. **`forms.example.com` resolves to a server that will answer for it.**
2. **That server serves your existing Formulize site under the new name** — the same files and the
   same database, reached by a second name. An *alias* of the site you already have, not a new site.
3. **There is a TLS certificate for `forms.example.com`**, installed on whichever server answers for
   it.

And one rule that decides whether it works once it is set up:

> **Formulize builds every link from the hostname in the request.** Whatever name the visitor's
> browser asked for is the name Formulize puts in its links, forms and redirects. So the web server
> has to pass the requested hostname through to PHP. Get this wrong and the screen loads at
> `forms.example.com`, then the first click sends the visitor to `forms.mycompany.com` and they are
> on a different domain again.
>
> Apache does this correctly by default. nginx needs one line added — see below.

There are two ways to arrange it. **Method A** points the name at the Formulize server.
**Method B** has the host website's server forward the traffic. Either works. Do not do both.

---

## Method A — point the name at the Formulize server

Three parts, in this order. The name has to resolve before a certificate can be issued for it.

### A1. Add a DNS record on the host website's domain

Whoever runs `example.com` adds one record, in their DNS host's control panel or zone editor:

| Type | Name | Points to |
|---|---|---|
| `CNAME` | `forms` | `forms.mycompany.com` |

If their DNS cannot use a CNAME at that name, an `A` record with the Formulize server's IP address
does the same job — plus an `AAAA` record if the server has an IPv6 address.

Check it before going on:

```
dig +short forms.example.com
```

You should see the Formulize server. DNS changes can take a while to appear, so wait for this to
answer correctly rather than moving on.

### A2. Serve the existing site under the new name

**This is the step where it is easy to make the wrong thing.** You are adding a second name to the
site that already exists. You are not creating a second website.

#### If the Formulize server has a control panel

This is a few clicks. What the control panel calls it varies, but you are always looking for the
option that **shares the document root with the existing site**.

**cPanel** — *Domains* → *Create A Domain*:

- Domain: `forms.example.com`
- Document Root: specify the document root of the Formulize system that is being embedded. In some configurations, this is simply a checkbox labelled something like **Share document root with "forms.mycompany.com"**.

Sharing the document root is the whole point. If the domain gets a new, separate document root, this whole setup will not work and `forms.example.com` will lead people to a blank page instead of Formulize.

On older cPanel versions the same option lives under *Aliases* (called *Parked Domains* on versions
older still). Add `forms.example.com` there. Do **not** use *Addon Domains* — that is the one that
creates a separate website.

**Plesk** — *Websites & Domains* → the existing Formulize site → *Domain Aliases* → *Add Domain
Alias*, and enter `forms.example.com`. Do **not** use *Add Domain* or *Add Subdomain*.

**DirectAdmin** — *Domain Setup* → *Additional Domains*, added as a **pointer** (alias) to the
existing domain rather than as a new domain.

Whatever the panel, the test is the same: the new name must end up with the **same document root** as
the site you already have.

#### If you are editing the server configuration directly

**Apache** — add a `ServerAlias` to the existing virtual host:

```apache
<VirtualHost *:443>
    ServerName   forms.mycompany.com
    ServerAlias  forms.example.com
    DocumentRoot /var/www/formulize

    # ...your existing SSL and PHP configuration...
</VirtualHost>
```

Apache passes the requested hostname to PHP by default, so there is normally nothing else to do.
The one thing that breaks it is `UseCanonicalName On` somewhere in your configuration — if that is
set, either remove it or set `UseCanonicalName Off` in this virtual host.

**nginx** — add the name to `server_name`, and add one line so PHP is told which name was asked for:

```nginx
server {
    listen 443 ssl;
    server_name forms.mycompany.com forms.example.com;
    root /var/www/formulize;

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SERVER_NAME $host;        # required, and must come after the include
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }

    # ...your existing configuration...
}
```

The `fastcgi_param SERVER_NAME $host;` line is not optional on nginx. The stock `fastcgi_params`
file sets `SERVER_NAME` from the `server_name` directive, which means every request would be told it
is `forms.mycompany.com` — the first name in the list — whichever name the visitor actually used.
The line has to come **after** `include fastcgi_params;`, or the include overwrites it.

Reload the web server.

### A3. Get a certificate for the new name

The certificate for `forms.example.com` is issued to, and installed on, **your** server. This is
normal, and it is what makes `https` work inside the frame.

**With a control panel**, the certificate is usually issued automatically once the name resolves and
the alias exists. In cPanel it is *SSL/TLS Status* → *Run AutoSSL*; in Plesk, the *SSL/TLS
Certificates* → *Install a free basic certificate* (or the *SSL It!* panel) on the alias. Give it a
few minutes and check the new name shows a valid certificate.

**With certbot**, add the name to the certificate the site already uses:

```
certbot --apache -d forms.mycompany.com -d forms.example.com
```

or, on nginx:

```
certbot --nginx -d forms.mycompany.com -d forms.example.com
```

Either way the name must already resolve to this server, from step A1, or issuance fails.

### A4. Check it

Open `https://forms.example.com/` directly in a browser. You should see:

- your Formulize site, not an empty folder or a "new site" placeholder — if you get one of those,
  the alias in A2 has its own document root instead of sharing the existing one
- a valid certificate, with no warning
- the address bar **staying on `forms.example.com`** as you sign in and click around — if it moves to
  `forms.mycompany.com`, the requested hostname is not reaching PHP; see the nginx line, or
  `UseCanonicalName`, in A2

---

## Method B — forward from the host website's server

Use this when the host website's own server handles the address. Nothing changes on the Formulize
server.

This route generally needs access to the web server configuration on the host's side. Some control
panels expose it — Plesk has *Apache & nginx Settings* → *Additional nginx directives* — but many do
not, in which case Method A is the easier path.

### B1. Point the name at the host website's server

`forms.example.com` resolves to the **host website's** server, which is usually where
`www.example.com` already points.

### B2. Forward the whole hostname

**nginx:**

```nginx
server {
    listen 443 ssl;
    server_name forms.example.com;

    location / {
        proxy_pass https://forms.mycompany.com;
        proxy_set_header Host              $host;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
    }
}
```

**Apache:**

```apache
<VirtualHost *:443>
    ServerName forms.example.com

    SSLProxyEngine    On
    ProxyPreserveHost On
    ProxyPass         / https://forms.mycompany.com/
    ProxyPassReverse  / https://forms.mycompany.com/
    RequestHeader set X-Forwarded-Proto "https"

    # ...your SSL configuration for forms.example.com...
</VirtualHost>
```

Two things decide whether this works:

- **Forward the whole hostname, not a path.** `www.example.com/forms/` does not work. The links and
  buttons Formulize generates point outside that path.
- **Pass `Host` and `X-Forwarded-Proto` through**, as shown. Leave them out and Formulize builds its
  links with the original address, or with `http`, and the visitor ends up on a different site again.

### B3. Get a certificate for the new name

Issued to and installed on the **host website's** server, for `forms.example.com`.

### B4. Check it

The same checks as A4.

---

## After either method

Point the iframe at the new address. The screen's settings page shows the code to use. There are two versions, one for anonymous users, and one for when you have set up an embedding address to use in order to preserve user sessions in the embedded screens.

Since Formulize has no way to know the embedding address you're using, replace `{embedding-address}` in the example code with the address you are using.

If you don't need to preserve user sessions, you can just use the regular code with the normal site address of your Formulize system.

The address must use the screen's plain `index.php?sid=` location, not any Alternate URL you may have given it. The rewrite rules that serve those addresses are not part of this arrangement, and nobody sees the address inside an iframe anyway.

Your original address keeps working. Opening it directly still shows the whole site normally.

**If a CDN or caching server sits in front of your site**, set it to vary on the `Sec-Fetch-Dest`
header. The same address returns both the embedded version of a page and the normal one, and a cache
that ignores this serves people the wrong one.

## Checking that it worked

Open the host page and use the embedded screen. If the visitor is signed in to your Formulize site in
another tab, the screen should show their own data and save normally.

If an **Open this form in a new window** link appears under the screen, the browser is not keeping
cookies for it. First check that the iframe uses the embedding address, not the ordinary address.
Then work back through the checks in A4: the embedding address is not on the same domain as the host
page, is not `https`, or the links inside the frame are going back to the original address.

## If you cannot create an address on the host website's domain

See [Embedding on a different domain](../#embedding-on-a-different-domain) for what
works and what does not without this step.
