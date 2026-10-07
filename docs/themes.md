---
layout: default
permalink: documentation/themes/
redirect_from:
 - developers/themes/
title: What Formulize Expects From a Theme
---

# What Formulize expects from a theme

A Formulize theme is an ordinary ImpressCMS theme, and most of it is yours to design however you
like. There are three things Formulize looks for. Get these right and forms, lists and maps behave
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
that it has to know which element scrolls.

**Usually you don't need to do anything.** Formulize looks for the nearest ancestor of the form that
is actually scrollable — an element taller than its own box with `overflow-y` set to `auto` or
`scroll` — and falls back to the window. A theme that scrolls the window, and a theme that scrolls a
main content pane, both work without being told.

Declare it when that guess would be wrong — several nested scrollable elements, say. Put the selector
on your `<body>` tag:

```html
<body data-formulize-scroll-container=".my-main-pane">
```

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

## 5. Work with the Appearance editor (optional)

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

## 6. Offer the Page width setting (optional)

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

Then use it as the maximum width of your content. Lyris makes list and form screens a column of that
width, centred in the window, with each part of the screen a card in it: a list's title bar, its
entries, a form, and a floating bar at the bottom for a list's pagination or a form's buttons, with
the title bar, a form's page tabs and the bottom bar pinned while the page scrolls under them. The
column is centred in the window rather than beside the sidebar, so opening the sidebar doesn't move
it unless it has to; the header's links stay at the window's edge; and phones are left full width. See
the "Content width" section at the end of `themes/Lyris/css/style.css`.

## Checking your theme

Open a long form in your theme, scroll down, and save it. You should be returned to where you were
rather than to the top of the page. If you are not, the theme is either not firing
`formulize_pageShown` or scrolling an element Formulize could not find.
