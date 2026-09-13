---
layout: default
permalink: developers/embedding_screens/
title: Embedding Screens in Another Website
---

# Embedding Formulize screens in another website

A Formulize screen can appear inside a page on another website, and it works exactly as it does on
your Formulize site: conditional elements, subforms, multi-page navigation, saving, and all the
protections against spam submissions.

The other website's page needs an iframe and one script:

```html
<iframe data-formulize-embed src="https://forms.example.com/contact-us"></iframe>
<script src="https://forms.example.com/modules/formulize/libraries/embed/formulize-embed.js"></script>
```

Any kind of screen can be embedded this way, not just forms.

## The one rule: use an address on the same domain

**The address in the iframe has to be on the same domain as the page it appears in.** If the page is
on `www.example.com`, the screen has to come from something like `forms.example.com`.

If you point the iframe at a different domain, visitors will be able to see the form but not submit
it. Browsers deliberately prevent it, so there is no setting in Formulize that can change this.

You have two ways to give Formulize an address on the right domain, and either is fine:

1. **Point a name at the Formulize server.** Create `forms.example.com` as a CNAME or A record
   aiming at the server, and get a TLS certificate for that name.
2. **Proxy it.** Have `forms.example.com` pass requests through to the Formulize server.

If you proxy, proxy the **whole hostname** — something like `example.com/forms/` will not work,
because the links and buttons Formulize generates will point outside that path. Your proxy also has
to pass the original hostname through:

- nginx: `fastcgi_param SERVER_NAME $host;` and `proxy_set_header X-Forwarded-Proto $scheme;`
- Apache: `UseCanonicalName Off`

The screen stays reachable at its own address as well, and opening it directly still works normally.

Embedding does not change who can use a screen. A visitor who is not logged in can only use a screen
the Anonymous group is allowed to view, and a screen protected by a passcode still asks for one.

## What visitors see

An embedded screen appears without your site's menus, header or footer — just the screen itself,
sized to fit the page around it. There is nothing to configure: Formulize can tell it is being
embedded. The same address opened directly still shows your site normally.

If an embedded screen appears *with* your site's menus, the visitor's browser is an old one that
cannot tell Formulize what it is doing. Add `?formulize_embed=1` to the address in the iframe and it
will render correctly for them too.

An embedded screen is styled by your site's theme, so it looks like the rest of your Formulize site.
If your site uses a theme other than Anari, name it in `mainfile.php`:

```php
define('FORMULIZE_EMBED_BASE_THEME', 'Lyris');
```

To design the embedded appearance yourself, copy the `themes/formulize_embed` folder, keep the
script at the bottom of its `theme.html` file, and name your copy:

```php
define('FORMULIZE_EMBED_THEME', 'my_embed_theme');
```

If someone is part-way through a long form when their session is about to expire, a warning appears
at the top of the screen. On an embedded screen it stays at the top rather than following them down
the page.

**If you run a CDN or caching server in front of your site**, set it to vary on the
`Sec-Fetch-Dest` header. The same address has to be able to return both the embedded version and the
normal one, and a cache that ignores this will serve people the wrong one.

## Turning embedding on

Embedding is off until you turn it on. In the Formulize preferences, under **Settings → Advanced →
Embedding in other websites**, set **Allow this site to be embedded in other websites** to Yes.

While it is off, no website can display this site or any screen in it inside a frame, whatever any
individual screen says. Turning it off again later blocks everything at once, and leaves the lists
below untouched, so turning it back on restores exactly what you had.

## Choosing which websites may embed a screen

Open the screen's settings and find **Which other websites can embed this screen?**. Write one
website per line:

```
example.com                     a domain on its own
www.example.com
https://campaign.example.com    include https:// to require a secure connection
*.example.com                   any subdomain
example.com:8443                include a port if the site uses one
```

You can paste a page address straight from your browser — the page part is ignored, and only the
website it belongs to is used. Put each website on its own line, or separate them with commas.

If you type something that isn't a website address, it stays in the box and the settings page tells
you it is being ignored, so you can correct it. Nothing you type is thrown away.

### Full embedding of the site

If you need to display your entire Formulize website inside an iframe on another website, such as inside an LMS, name that system in the Formulize preferences, under **Websites allowed to display this site in a frame**, in the same place as the switch above.

Websites named there can frame every page of the site, so you do not need to repeat them on the individual screens they embed. A screen's own list adds to them.

## For the developer of the host page

Mark the iframe with `data-formulize-embed` and include `formulize-embed.js` once per page. The
script finds every marked iframe, keeps each one as tall as its content, and scrolls the page when
the screen needs something brought into view.

| Attribute | Effect |
|---|---|
| `data-formulize-embed` | Required. Marks the iframe for the script. |
| `data-formulize-embed-height` | Height in pixels to start at, before the screen reports its own. Defaults to 600. |
| `data-formulize-embed-fallback` | Set to `off` to suppress the "open in a new window" link. |
| `data-formulize-embed-fallback-text` | Wording for that link. |

While a screen is loading, its iframe has the class `formulize-embed--loading`, so you can show a
spinner behind it and have it disappear when the screen arrives.

The screen also fires DOM events on the iframe, so you can react without modifying the script:

| Event | When it fires |
|---|---|
| `formulize:ready` | The screen is visible. Carries its height. |
| `formulize:resize` | The height changed — a conditional element appeared, a validation message was added, or a page turned. |
| `formulize:scroll` | The screen is asking for a position to be brought into view. |
| `formulize:sessionUnavailable` | The visitor's browser is refusing cookies for this screen, so submitting would fail. |

The script ignores messages from anywhere other than the iframes it is managing.

If the visitor's browser refuses cookies for the embedded screen, they get an "Open this form in a
new window" link, and the form works normally in that tab. This is what happens when the iframe
address is not on the same domain as the page.

## After upgrading Formulize

Run the module update once, in **Modules administration → update Formulize**. The setting for
choosing which websites may embed a screen is not available until you do.
