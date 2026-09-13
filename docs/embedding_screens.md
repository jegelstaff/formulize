---
layout: default
permalink: developers/embedding_screens/
title: Embedding Screens in Another Website
---

# Embedding Formulize screens in another website

A Formulize screen can appear inside a page on another website, and it works exactly as it does on
your Formulize site: conditional elements, subforms, multi-page navigation, saving, and all the
protections against spam submissions. Any kind of screen can be embedded, not just forms.

Visitors see the screen and nothing else — no menus, header or footer — styled by your site's theme
so it still looks like your site. The same address opened directly still shows your whole site
normally.

## Quick start

There are four steps. The third is the one that takes real work, so read it before you start.

### 1. Turn embedding on

In the Formulize preferences, go to **Settings → Advanced → Embedding in other websites** and set
**Allow this site to be embedded in other websites** to Yes.

Until you do, no website can display this site in a frame, and nothing else here will work.

### 2. Say which website may embed the screen

Open the screen's settings, find **Embedding this screen in another website**, and write the address
of the website that will display it:

```
www.example.com
```

One website per line. Until you list one, no other website can embed that screen.

### 3. Give Formulize an address on the same domain as the host page

**This is the part that needs a server change, and the whole thing depends on it.**

The address in the iframe has to be on the same domain as the page it appears in. If the host page
is on `www.example.com`, the screen has to come from something like `forms.example.com`.

If you point the iframe at a different domain, visitors will see the screen but will not be able to
submit anything. Browsers do not send your site's cookies into a frame on an unrelated website, so
there is no session, and without a session there is nothing to save into. No setting in Formulize
changes this — it is the browser's decision, not ours.

You have two ways to get an address on the right domain. Either is fine:

**Point a name at the Formulize server.** Create `forms.example.com` as a CNAME or A record aiming
at the server, and get a TLS certificate for that name.

**Or proxy it.** Have `forms.example.com` pass requests through to the Formulize server. Two things
matter if you do:

- Proxy the **whole hostname**. Something like `example.com/forms/` will not work, because the links
  and buttons Formulize generates point outside that path.
- Pass the original hostname through:
  - nginx: `fastcgi_param SERVER_NAME $host;` and `proxy_set_header X-Forwarded-Proto $scheme;`
  - Apache: `UseCanonicalName Off`

Your Formulize site stays reachable at its own address as well, and opening it directly still works
normally.

### 4. Paste the code into the host page

The screen's settings page shows you the exact code for that screen, under **Code for the other
website's page**. It looks like this:

```html
<iframe data-formulize-embed src="https://forms.example.com/contact-us?formulize_embed=1"
        title="Contact us"></iframe>
<script src="https://forms.example.com/modules/formulize/libraries/embed/formulize-embed.js"></script>
```

Copy it from the settings page rather than from here — it has your screen's real address in it
already. Include the script once per page, however many screens that page shows.

That is everything. The screen sizes itself to its content, so there is no height to guess at.

## Who can use an embedded screen

Embedding does not change who can use a screen. It changes who is looking.

By default, **an embedded screen is always anonymous**, no matter who is looking at it. Browsers do
not send your site's session cookie into a frame on another website, so as far as Formulize is
concerned nobody is signed in. That means:

- A screen the Anonymous group can view works normally.
- A screen that requires a login shows a short message and a link to open it in a new window, where
  signing in works.
- A screen protected by a passcode still asks for the passcode, and that works inside the frame.

If you are embedding into a system your users are already signed in to — a learning management
system, an intranet portal — and you want them to see their own data in the frame, you need the
session cookie to travel with them. Change **SameSite** under **Settings → Advanced → Sessions &
cookies** to `None`.

Do read what the preferences page tells you when you do. With `None`, every website in your
embedding lists can display signed-in pages of your site inside a page of their own. List only
websites you control and trust.

## Choosing which websites may embed

Write one website per line. All of these work:

```
example.com                     a domain on its own, matching http or https
www.example.com
https://campaign.example.com    include https:// to require a secure connection
*.example.com                   any subdomain, but not example.com itself
example.com:8443                include a port if the site uses one
```

You can paste a page address straight from your browser — the page part is ignored and only the
website it belongs to is used. You can separate entries with commas instead of line breaks.

If you type something that is not a website address, it stays in the box and the settings page tells
you it is being ignored, so you can correct it. Nothing you type is thrown away.

### Embedding the whole site

If you need to display your entire Formulize site inside a frame — inside an LMS, say, rather than
embedding individual screens — name that system under **Websites allowed to display this site in a
frame**, in the preferences beside the on/off switch.

Websites named there can frame every page, including the login page. You do not need to repeat them
on the individual screens they embed: a screen's own list adds to them.

For embedding individual screens, leave this blank and use the setting on each screen.

## Changing how an embedded screen looks

An embedded screen borrows your site's own theme stylesheet, so it looks like the rest of your site
with no work at all. If you change your site's theme, embedded screens follow.

To design the embedded appearance yourself, copy the `themes/formulize_embed` folder to a new name.
Keep two things in your copy:

- the `formulize-embed-theme.marker` file, which is what tells Formulize this is an embed theme
- the script at the bottom of `theme.html`, which is how the screen reports its height to the host page

Your copy then appears in **Theme for embedded screens**, in the preferences beside the on/off
switch. Nothing to edit in `mainfile.php`.

## For the developer of the host page

Mark each iframe with `data-formulize-embed` and include `formulize-embed.js` once per page. The
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

The screen fires DOM events on the iframe, so you can react without modifying the script:

| Event | When it fires |
|---|---|
| `formulize:ready` | The screen is visible. Carries its height. |
| `formulize:resize` | The height changed — a conditional element appeared, a validation message was added, or a page turned. |
| `formulize:scroll` | The screen is asking for a position to be brought into view. |
| `formulize:sessionUnavailable` | The visitor's browser is refusing cookies for this screen, so submitting would fail. |

The script ignores messages from anywhere other than the iframes it is managing.

If the visitor's browser refuses cookies for the embedded screen, they get an "Open this form in a
new window" link, and the form works normally in that tab. That is what you will see if the iframe
address is not on the same domain as the page — see step 3 above.

## Troubleshooting

**The frame is empty.** The website showing it is not in the screen's list, or embedding is turned
off for the site. Check both, then reload.

**The screen appears with your site's menus, header and footer around it.** The browser did not tell
Formulize it was in a frame, and the address in the iframe is missing `?formulize_embed=1`. Copy the
code from the screen's settings page again — it includes the parameter.

**The form displays but will not submit.** The iframe address is not on the same domain as the host
page. See step 3.

**Everyone sees the screen as anonymous, but they are signed in to the LMS.** That is the default.
See "Who can use an embedded screen" above.

**If you run a CDN or caching server in front of your site**, set it to vary on the `Sec-Fetch-Dest`
header. The same address returns both the embedded version and the normal one, and a cache that
ignores this will serve people the wrong one.
