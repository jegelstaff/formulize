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

**This is the part that needs a server change.** The address in the iframe has to be on the same
domain as the page it appears in, over `https`. If the host page is on `www.example.com`, the screen
has to come from something like `forms.example.com`.

Three things have to be true:

1. `forms.example.com` resolves to a server that will answer for it.
2. That server serves your existing Formulize site under the new name — an **alias** of the site you
   already have, sharing the same files and database, not a new site.
3. There is a TLS certificate for `forms.example.com` on that server.

If both machines have a control panel like cPanel or Plesk, that is a CNAME record on the host
domain, a domain alias on the Formulize server, and the panel's SSL button — no configuration files
involved.

[Giving Formulize an address on the host website's domain](../embedding_screens_setup/) has the
steps: the control-panel route, the server-configuration route, and the reverse proxy alternative.

Your Formulize site stays reachable at its own address as well, and opening it directly still works
normally.

**You can skip this step if every visitor to the embedded screen will be anonymous, and you are
willing to accept that some browsers will not be able to submit.** Read
[Embedding on a different domain](#embedding-on-a-different-domain) before you decide.

### 4. Paste the code into the host page

The screen's settings page shows you the exact code for that screen, under **Code for the other
website's page**. It looks like this:

```html
<iframe data-formulize-embed src="https://forms.example.com/modules/formulize/index.php?sid=12&formulize_embed=1"
        title="Contact us"
        style="width:100%;min-width:min(400px,100vw);height:600px;border:0"></iframe>
<script src="https://forms.example.com/modules/formulize/libraries/embed/formulize-embed.js"></script>
```

Copy it from the settings page rather than from here — it has your screen's real address in it
already. Include the script once per page, however many screens that page shows.

That is everything. The screen sizes itself to its content, so there is no height to guess at, and
it takes the width your page gives it — see [How wide the screen will be](#how-wide-the-screen-will-be)
if it comes out narrower than you expected.

## Who can use an embedded screen

Embedding does not change who can use a screen. What changes is whether the visitor is signed in
while they use it — and that depends entirely on **where** the screen is embedded.

With the settings at their defaults, browsers send your site's session cookie into a frame only when
the page doing the framing is on the same domain as the screen, over the same https. So:

**Embedded on the same domain** — the arrangement step 3 sets up, `forms.example.com` inside
`www.example.com`. The session travels with the visitor. The screen shows them their own data and
behaves exactly as it does on your own site, submitting included. This is the normal case, and it is
why step 3 matters.

**Embedded on a different domain** — `forms.example.com` inside `someone-else.com`. See below.

The screen's settings page tells you which of the two you have. It reads the websites you have
listed, compares each against your own address, and says which will be signed in and which will be
anonymous.

## Embedding on a different domain

Skipping step 3 means the browser sends your site's session cookie nowhere near the frame. Whoever is
looking at the screen, Formulize sees an anonymous visitor with no session. With the settings at
their defaults:

- A screen the Anonymous group can view **displays normally**.
- **Anonymous visitors can submit it**, in browsers that allow an embedded page to keep a cookie of
  its own. Formulize keeps one small cookie for this, holding nothing but an unguessable value, so
  that it can tell a submission came from the browser the form was drawn in. Your Formulize site has
  to be `https` for it — see below for the browsers this does not cover.
- A screen that requires a login shows a short message and a link to open it in a new window, where
  signing in works. Signed-in visitors are still anonymous inside the frame; only step 3 or the
  SameSite change below alters that.
- A screen protected by a passcode accepts the passcode and opens, then **asks for it again** on the
  next page or when the visitor tries to save. The passcode is held in the session, which is not
  reaching the frame.
- An anonymous visitor who saved an entry earlier **can return to it on the same website that
  embedded it**, but not by visiting your Formulize site directly afterwards. The cookie that
  remembers their entry is filed under the website doing the embedding, and is not visible anywhere
  else.

**Where submitting still does not work:** browsers that refuse third-party cookies outright rather
than keeping them separated per website. Safari does by default. Those visitors get an **Open this
form in a new window** link under the screen, and the form works normally in that tab. Step 3 is
still the only arrangement that works in every browser.

### Making signed-in visitors and passcode screens work on a different domain

Anonymous submitting is covered above and needs nothing set here. This is for the rest: visitors
seeing their own data, and passcode screens.

Change **SameSite** under **Settings → Advanced → Sessions & cookies** to `None`. Then, for visitors
whose browser accepts third-party cookies:

- Passcode screens work.
- Signed-in visitors see their own data, which is what a learning management system or a portal
  needs.

Two things to know before you do it:

- **Some browsers refuse third-party cookies whatever this is set to.** Safari does by default. Those
  visitors see the screen as anonymous, and are offered the link to open it in a new window. There is
  no way to tell in advance how many of your visitors this will be. Step 3 is the only arrangement
  that works in every browser.
- With `None`, every website in your embedding lists can display signed-in pages of your site inside
  a page of their own. That is a much wider grant than anonymous submitting needs, which is why it is
  not required for it. List only websites you control and trust.

## Choosing which websites may embed

Write one website per line. All of these work:

```
example.com                     a domain on its own, matched only under this site's own scheme
www.example.com
https://campaign.example.com    include https:// to require a secure connection
http://localhost:4000           include http:// for a host that is not using https
*.example.com                   any subdomain, but not example.com itself
example.com:8443                include a port if the site uses one
```

You can paste a page address straight from your browser — the page part is ignored and only the
website it belongs to is used. You can separate entries with commas instead of line breaks.

If you type something that is not a website address, it stays in the box and the settings page tells
you it is being ignored, so you can correct it. Nothing you type is thrown away.

**Leaving out the scheme assumes `https`** If your host is running only under `https://` then you need to include `http://` ie: `http://localhost:4000`.

### Embedding the whole site

If you need to display your entire Formulize site inside a frame — inside an LMS, say, rather than
embedding individual screens — name that system under **Websites allowed to display this site in a
frame**, in the preferences beside the on/off switch.

Websites named there can frame every page, including the login page. You do not need to repeat them
on the individual screens they embed: a screen's own list adds to them.

For embedding individual screens, leave this blank and use the setting on each screen.

## How wide the screen will be

The screen fills whatever container you put the iframe in. A container 700px wide gives you a 700px
screen, and there is nothing to set — that is the usual case and it needs no attention.

There is a floor under it, at 400px, so it cannot collapse. Some layouts size a box to fit whatever
is inside it — a float, an `inline-block`, a flex or grid item, a table cell, `width: fit-content` —
and inside one of those a percentage width has nothing to resolve against, so browsers fall back to
the 300px they give any element with no size of its own. The result is a usable page with a sliver
of a form in it and no error anywhere to explain why. The floor stops that happening.

The floor gives way on a narrow screen rather than pushing a horizontal scrollbar onto your page: on
a phone the frame is as wide as the window. Set `data-formulize-embed-min-width` to change it, or to
`off` to remove it, if you have a column narrower than 400px and you would rather the screen fit
inside it.

A narrow frame gets the narrow layout — the same one a phone gets, which in the standard theme means
anything under 768px — because a frame has a viewport of its own and the screen is measuring the
frame rather than the monitor. So a screen in a narrow column stays usable; it just reads like a
phone. If a form looks cramped in your page, widen the container around the iframe rather than the
iframe itself.

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
script finds every marked iframe, keeps each one as tall as its content, holds it to a sensible
width, and scrolls the page when the screen needs something brought into view.

| Attribute | Effect |
|---|---|
| `data-formulize-embed` | Required. Marks the iframe for the script. |
| `data-formulize-embed-height` | Height in pixels to start at, before the screen reports its own. Defaults to 600. |
| `data-formulize-embed-min-width` | Narrowest the frame may become, in pixels. Defaults to 400. `off` removes the floor. Capped at the width of the window either way. |
| `data-formulize-embed-scroll-margin` | Room left above the screen when it is brought into view, in pixels. Defaults to two lines of your page's text. Raise it if your page has a sticky header the screen would otherwise scroll underneath; `0` removes it. |
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

**The browser reports a connection error inside the frame** — something like "refused to connect" —
even though the same address opens fine in its own tab. This is a blocked frame, not a real network
problem. Check if the host page is using `http://` while the Formulize site uses `https://`. If there's a mismatch there, make sure to include `http://` excplicitly when specifying the allowed website in the Formulize settings. See
[Choosing which websites may embed](#choosing-which-websites-may-embed).

**The form is a narrow sliver.** The iframe is in a container that sizes itself to its contents, and
the version of `formulize-embed.js` on your Formulize site predates the floor described under
[How wide the screen will be](#how-wide-the-screen-will-be). Take the code from the screen's settings
page again — it now carries a `style` that sets the width whether or not the script has loaded.

**The screen appears with your site's menus, header and footer around it.** The browser did not tell
Formulize it was in a frame, and the address in the iframe is missing `?formulize_embed=1`. Copy the
code from the screen's settings page again — it includes the parameter.

**The form displays but will not submit**, and the visitor sees *Error: the data you submitted could
not be saved in the database.* The browser is not keeping any cookie for the screen. Check these in
order:

1. **Is your Formulize site `https`?** The cookie that lets an anonymous visitor submit from inside a
   frame cannot be set over plain `http` — browsers require it to be marked secure, and they only
   accept that over `https`.
2. **Is it Safari, or a browser set to block third-party cookies?** Then it cannot work on a different
   domain at all, and step 3 is the answer.
3. **If the iframe address *is* on the same domain as the host page**, one of the setup steps is
   incomplete. Open the iframe address directly in a browser: if the address bar moves to a different
   name as you click around, the server is not passing the requested hostname to Formulize. See
   [the setup steps](../embedding_screens_setup/).

**The form will not submit for some people but works for others.** Those people's browsers refuse
third-party cookies, Safari's default. See
[Embedding on a different domain](#embedding-on-a-different-domain).

**A passcode screen keeps asking for the passcode.** The passcode is kept in the session, and the
session is not reaching the frame. This one needs SameSite set to `None`, or step 3.

**An anonymous visitor cannot get back to the entry they saved** after going to your Formulize site
directly. That is expected: the cookie remembering their entry is filed under the website that
embedded the screen, and is deliberately not readable from anywhere else. Returning to the entry on
the website that embedded it does work. Only step 3 makes the two the same place.

**Everyone sees the screen as anonymous, but they are signed in to the LMS.** That is the default.
See "Who can use an embedded screen" above.

**If you run a CDN or caching server in front of your site**, set it to vary on the `Sec-Fetch-Dest`
header. The same address returns both the embedded version and the normal one, and a cache that
ignores this will serve people the wrong one.
