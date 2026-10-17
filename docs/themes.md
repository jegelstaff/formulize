---
layout: default
permalink: documentation/themes/
redirect_from:
 - developers/themes/
title: What Formulize Expects From a Theme
---

# What Formulize expects from a theme

A Formulize theme is an ordinary ImpressCMS theme, and most of it is yours to design however you
like. There are a few things Formulize looks for. Get these right and forms, lists and maps behave
correctly in your theme; miss them and those things quietly stop working rather than raising an
error.

## 1. Fire `formulize_pageShown` when the page is revealed

Most themes hide the body while the page assembles and reveal it once everything has loaded. When you
reveal it, dispatch this event on `window`:

```javascript
window.dispatchEvent(new CustomEvent('formulize_pageShown'));
```

Formulize waits for it before putting a reader back where they were on a form, a list or a map. If
your theme never fires it, a reader who saves a long form is returned to the top of it every time.

If you don't hide and reveal the body, fire it once the page has loaded anyway.

## 2. Say what scrolls, if it isn't obvious

Formulize saves a reader's scroll position when they save a form, and puts it back afterwards. To do
that it has to know which element scrolls. And when a theme names the element, the mouse wheel or
trackpad scrolls it from anywhere on the page, not only with the pointer over it.

**Usually you don't need to do anything.** Formulize looks for the nearest ancestor of the form that
is actually scrollable — an element taller than its own box with `overflow-y` set to `auto` or
`scroll` — and falls back to the window. A theme that scrolls the window, and a theme that scrolls a
main content pane, both work without being told.

Declare it when that guess would be wrong — several nested scrollable elements, say. Put the selector
on your `<body>` tag:

```html
<body data-formulize-scroll-container=".my-main-pane">
```

The selector can name several elements, for a theme that scrolls a different one at different
widths, or on different screens. Formulize uses the first of them that is scrolling at the time, and
if none is, looks for one as if nothing were named. Lyris scrolls a list's entries or a form's card on
wide screens, and the page on phones:

```html
<body data-formulize-scroll-container=".lyris-list__body, .lyris-form-screen, .lyris-main">
```

Naming it also means a reader can scroll it from anywhere on the page: over the margins beside a
narrow column, a title bar, a bar of buttons. The wheel scrolls the named element unless the pointer
is already over it, or over something else that scrolls on its own, such as an open menu or a wide
table. It is also left alone over a modal and its backdrop (anything with `aria-modal="true"` or
`role="dialog"`, such as the entry drawer), so the page behind a modal stays where it is, and over a
menu or list of options opened outside the element (`role="menu"`, `role="listbox"`, or jQuery UI's
`.ui-menu`, as an autocomplete's suggestions are). This is `include/js/scroll_container.js`, which
Formulize adds to every page; a theme that names nothing isn't affected.

Use `none` when nothing in the page scrolls, because something outside it does:

```html
<body data-formulize-scroll-container="none">
```

Then Formulize will not try to save or restore a position at all.

## 3. Embed themes need a marker file

A theme meant for [screens embedded in another website](../embedding_screens/) is chosen from a
separate list in the Formulize preferences, and is kept out of the normal theme pickers. Put a file
named `formulize-embed-theme.marker` in the theme folder to mark it as one. A theme without the
marker is not used for embedded screens, even if it is chosen in the preferences.

The simplest way to make your own is to copy the `themes/formulize_embed` folder. Keep these in your
copy:

- the marker file
- the short script just after the `<body>` tag in `theme.html`, which turns off the page's own
  scrolling when it is inside a frame
- the script at the bottom of `theme.html`, which is how an embedded screen reports its height to
  the page hosting it
- `session-timeout-warning.html`, including its `session-timeout-warning` id, which the script at the
  bottom uses to bring the warning into view

## 4. Style screens so they still work without your header and menus

An embedded screen borrows your theme's styling without its page layout. It loads your
`css/reset.css`, if you have one, then your `css/style.css`, and then any colours, font and logo set in
the Appearance editor. It does not load your `theme.html` or your script. The `<body>` has the class
`formulize-inline`.

Two optional files let you adjust how your theme looks when embedded:

- **`embed-content.html`**: the elements your theme puts around the page content, with
  `<{$icms_contents}>` inside them and your header and menus left out. Add it if your stylesheet
  styles screens through those elements. Without it, the screen is drawn inside a plain `<main>`.

  ```html
  <div class="my-layout">
    <main class="my-main-pane">
      <{$icms_contents}>
    </main>
  </div>
  ```

- **`css/embed.css`**: loaded after your other stylesheets, only on embedded screens. Use it for
  anything that assumes your page fills the window. An embedded screen is exactly as tall as its
  content, so a content area that fills the window and scrolls on its own should just be as tall as
  its content:

  ```css
  .my-main-pane {
    height: auto;
    overflow: visible;
  }
  ```

The Anari theme has both files, if you want an example.

## 5. Give every page the same space at its edges

Every page should have the same space between its content and the edges of the window, your header
and your menu, whatever is on the page. Formulize has several types of screen (list of entries, form,
template, calendar and map), and more will be added. None of them should look different from the
others in this, and neither should the pages that are not screens, such as an application's menu
page.

Formulize doesn't supply that space, and no type of screen brings its own. Your theme does, in one
place: put a wrapper around all the content in your `theme.html`, and give it padding.

```html
<main class="my-main">
  <div class="my-page">
    <{$icms_contents}>
  </div>
</main>
```

```css
.my-page {
  padding: var(--fz-page-padding);
}
```

`--fz-page-padding` is the Appearance editor's Page padding, so the space follows that setting. Then
there is nothing more to do: a new type of screen gets the space without any change to your theme,
and a screen inside another (a template screen's code can call `displayForm` or `displayEntries`) is
not inset twice, because neither has space of its own.

Make the wrapper its own element, inside the part of your page that scrolls, and leave the scrolling
part without padding. Then the wrapper is also the place to keep pages to a maximum width (section
7), and anything your theme pins to the top or bottom of the scrolling part is not pushed in by it.

Don't put the space on the parts of a screen instead, in your screen templates. A screen drawn with
other templates would have none, and a screen inside another would have it twice.

On a phone you may want less space, or want your list and form screens to use the whole width of the
screen. Make the padding smaller there, and take it off only for the pages you mean. Lyris does both:
see "The page frame" and "Mobile" in `themes/Lyris/css/style.css`. Content should never touch the
edge of the screen, so anything that reaches the edge needs padding of its own inside it.

If your embed stylesheet or your `embed-content.html` (above) is used, leave the space out there:
the page your screen is embedded in supplies it.

## 6. Work with the Appearance editor (optional)

The [Appearance editor](/documentation/appearance_editor/) edits a theme's appearance on a preview of
sample screens the theme provides (below): a theme without them can't be edited there. In simple
mode it changes the logo, colours, fonts and page width, which every theme that calls
`formulize_renderAppearanceHead()` follows.

Its advanced mode, and the Compact and Comfortable looks, change Formulize UI's component tokens: sizes (`--fz-field-height`, `--fz-row-height`, `--fz-title-text`),
fonts (`--fz-label-font`), colours (`--fz-button-bg`, `--fz-header-bg`) and corners
(`--fz-field-radius`), and the rest. They only do anything in a theme whose own CSS styles things with
those tokens, so a theme says when it does, and only then does the editor offer them. Each
colour and font token defaults to the palette colour or font it stands for, so a theme that uses them
looks the same until one is changed.

**Opt in** by declaring `--formulize-size-tokens` on `:root` in your `css/tokens.css`:

```css
:root {
  --formulize-size-tokens: 1;
}
```

**If your theme has a bar across the top of every page with the logo in it**, give the bar
`height: var(--fz-topbar-height)` and size the logo from it, so the advanced editor's Top bar
height makes the logo larger too. Lyris's logo is the bar's height less 10px above and below:

```css
.my-topbar { height: var(--fz-topbar-height); }
.my-topbar img { height: calc(var(--fz-topbar-height) - 1.25rem); width: auto; }
```

**If your theme's own font comes from Google Fonts**, say which weights of it to download, with your
other tokens:

```css
:root {
  --fz-font-sans: Poppins, Helvetica, sans-serif;
  --formulize-font-weights: 400 500 600;
}
```

`--formulize-font-weights` is a list of numbers with spaces between them. Each number is a
`font-weight` that your stylesheet uses: 400 is regular, 700 is bold, and the others are the steps
around them. A webfont is a separate file for each weight, and only the weights you list are
downloaded.

- List every weight your CSS uses. Text set in a weight you left out is shown in the nearest weight
  you did list: with `400 500 600`, bold (700) text is shown at 600.
- List only weights the font has. Its page on Google Fonts shows them. If you list one it doesn't
  have, the font isn't loaded at all.
- Leave out the weights you don't use. Each one is another file for the browser to fetch.

The font that is loaded is the first one named in `--fz-font-sans`, so that name has to be its name
on Google Fonts. It is loaded on every page that has your theme's appearance: the pages your
`theme.html` draws, the popups of the list screens, and screens embedded in another site. When
another font is chosen in the Appearance editor, yours is no longer loaded.

Don't load the font in `theme.html` as well. A theme that leaves `--formulize-font-weights` out gets
Geist, the font Lyris uses.

**Provide sample screens** for the editor's preview, in an `appearance_preview` folder
in your theme. Each is an HTML file named after a screen: `form.html`, `list.html`, `drawer.html` and
`cards.html`. Provide the ones that suit your theme; the editor shows the ones it finds, and without
any, the theme can't be edited there. A sample is the markup your theme puts in `<body>` for
that kind of page, written out with sample content, and it is shown with your `css/reset.css`, your
`css/style.css` and your generated appearance stylesheet, in a `<body>` with the id `formulize` and
the class `formulize-screen`. No scripts run in it.

Mark each part of the sample that can be selected with `data-fz-part`, naming the part:

```html
<input type="button" class="formulize-form-submit-button" value="Save" data-fz-part="button">
```

The parts, and the tokens of each one, are the `components` in
`modules/formulize/include/appearance_tokens.json`: `logo` (the link around your logo image),
`page`, `tabs`, `title`, `form`, `label`, `field`, `value` (a read-only value), `options` (radio
buttons and checkboxes), `help`, `button`, `toolbar`, `menu`, `header` (column headings), `row`,
`card` and `drawer`. The editor shows a new logo by changing the `src` of the image inside the
`logo` part.

Pieces shared between samples go in files starting with an underscore, and are included by name in
double braces: `{{list}}` is the contents of `_list.html`. `{{logo_url}}` and `{{site_name}}` are your
logo and the site's name. Clicking an element with `data-fz-toggle="some-id"` in the preview toggles
the class `open` on the element with that id, for showing a menu.

Lyris's samples, in `themes/Lyris/appearance_preview/`, are a complete example. A theme edited in simple
mode only, such as Anari (`themes/Anari/appearance_preview/`), only needs to mark its logo, as the
`logo` part.

## 7. Offer the Page width setting (optional)

The Page width setting, in the Appearance editor's site-wide settings, keeps
pages to a maximum width on a wide screen, or lets them use the full width of the window. It sets
`--formulize-content-max-width`: a width in pixels, or `100%` for full width. Laying the page out to
that width is up to the theme, so a theme says when it does, and only then is the setting offered.

**Opt in** by declaring `--formulize-content-max-width` on `:root` in your `css/tokens.css`. What you
declare is your theme's own width, which is what the setting starts at and what a reset goes back to:
a width in pixels, or `100%` to start at full width. Lyris declares 1200 pixels:

```css
:root {
  --formulize-content-max-width: 1200px;
}
```

Then use it as the maximum width of your content, on the wrapper that has the space at the page's
edges (section 5), so that every type of screen is kept to it and none needs anything of its own.
Lyris makes its pages a column of that width, centred in the window. In it, each part of a list or a
form is a card: a list's title bar, its entries, a form, and a floating bar at the bottom for a
list's pagination or a form's buttons. The column is centred in the window rather than beside the
sidebar, so opening the sidebar doesn't move it unless it has to; the header's links stay at the
window's edge; and phones are left full width. See the "Content width" section at the end of
`themes/Lyris/css/style.css`.

If your theme has a menu beside the content and you want the column centred in the window, not in
the space beside the menu, the wrapper is what makes that possible. The room to leave either side
depends on how wide the content area is, and in CSS a percentage in an element's padding is measured
against its parent, not itself. So the content area can't work it out for itself, but the wrapper
inside it can: on the wrapper, `100%` is the content area's width.

A page that fills the window and scrolls inside itself, as Lyris's lists and forms do, needs the
wrapper to be exactly as tall as the scrolling part. Give the wrapper `min-height: 100%` for every
page, and `height: 100%` only on the pages that fill the window. Every other page is then as tall as
its content, and the page scrolls.

On paper, take the wrapper's padding and height off again, with the rest of your theme's layout (see
Printing, under For theme authors, in [Formulize UI](/documentation/formulize_ui/)).

A screen can be set to use the full width whatever the site's setting is, on the screen's Appearance
tab. Formulize sets `--formulize-content-max-width` to `100%` on that screen's page, so a theme that
uses the property as above has nothing more to do.

## 8. Look right when the site is installed as an app (optional)

People can [install a Formulize site as an app](/documentation/install_as_an_app/) on their phone or
computer. It then opens in a window of its own, with no address bar or browser buttons around it.

**What every theme gets without doing anything.** Formulize adds these to the head of every page,
through `<{$icms_module_header}>`, which every theme prints:

- the link to the app's manifest, with the site's name, icons and colours
- the title bar colour, from the Appearance settings of the look the page is drawn with
- the icon for iPhones and iPads
- the service worker, which shows a page saying the device is offline when a page can't be loaded
- in a window with no browser buttons, as on an iPhone or iPad, a thin bar across the top of the
  screen while the next page loads, in the accent colour

Chrome and Edge open the app with back and reload buttons in its title bar. Safari opens it with no
buttons at all, so the page has to supply a way back, which is point 3 below.

Don't add a manifest link, a `theme-color` meta tag or a service worker of your own. They would
compete with Formulize's, and an embedded screen must not get any of them, which Formulize already
takes care of.

**What to add so it looks finished.** Without these, an installed app works, but looks plainer and
can be harder to find your way around in:

1. **Let the page use the whole screen.** Add `viewport-fit=cover` to the viewport meta tag:

   ```html
   <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
   ```

2. **Keep clear of the phone's notch and home bar.** With `viewport-fit=cover`, the page runs under
   them, so pad your header, your sides and your bottom edge by the Formulize UI tokens
   `--fz-safe-area-top`, `--fz-safe-area-right`, `--fz-safe-area-bottom` and `--fz-safe-area-left`.
   They are 0 wherever there is nothing to keep clear of, so the padding only appears where it is
   needed:

   ```css
   .mytheme-header {
     padding-top: var(--fz-safe-area-top);
   }
   ```

   Anything fixed to an edge of the window, such as a menu that slides in under the header, has to
   move by the same amount.

3. **Give the app a back button.** On an iPhone or iPad the app's window has no back button, and
   there is no back gesture. Put this in your header. `fz-show-in-app` shows it only in a window
   with no browser buttons, so not in Chrome or Edge, which have their own. Formulize removes
   `hidden` once there is a page to go back to, and makes it go back:

   ```html
   <button type="button" class="fz-btn fz-btn--ghost fz-btn--icon fz-show-in-app" data-formulize-app-back aria-label="Back" hidden>
     <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
       <polyline points="15 18 9 12 15 6"/>
     </svg>
   </button>
   ```

4. **Offer to install it.** Put an Install app item with your account links, styled like them.
   Formulize removes `hidden` where the browser can install the site, and makes it install the site,
   or show the steps to follow on an iPhone, an iPad or a Mac:

   ```html
   <a class="mytheme-account-link" href="#" role="button" data-formulize-install-app hidden>Install app</a>
   ```

   If your link's class gives it a `display` of its own, add `.mytheme-account-link[hidden] { display: none; }`,
   or the browser's rule for `hidden` loses to it.

Anari and Lyris do all four. See "A phone's notch and home bar" and "The header on phones" in each theme's `css/style.css`.

On a phone, a header has little room, and the app makes that more noticeable, since the header is
the only bar the app's window has. On phones Anari and Lyris put their links (the account links, the
inbox, Install app) at the top of their menu instead, and arrange their header as phone apps do: the
back button at the left, where people look for it, the logo in the middle, and the menu button at
the right, at the other end from back, with the menu sliding in from that side. See "The header on
phones" in each theme's `css/style.css`.

## Checking your theme

Open a long form in your theme, scroll down, and save it. You should be returned to where you were
rather than to the top of the page. If you are not, the theme is either not firing
`formulize_pageShown` or scrolling an element Formulize could not find.

Open a screen of each type you have, and an application's menu page. On every one, the content should
have the same space around it, not sit against the edge of the window or your header. If you have a
template screen with a form or a list inside it, check that the form or list lines up with the rest of
that page, not further in.
