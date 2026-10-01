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
`css/reset.css`, if you have one, then your `css/style.css`, and then any colours, font and logo set on
the Appearance page. It does not load your `theme.html` or your script. The `<body>` has the class
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

## 5. Offer the Appearance page's size settings (optional)

The Appearance page's [size settings](/documentation/appearance_sizes/) change Formulize UI's
component size tokens (`--fz-field-height`, `--fz-row-height`, `--fz-title-text` and the rest). They
only do anything in a theme whose own CSS sizes things with those tokens, so a theme says when it
does, and only then does the Appearance page offer them.

**Opt in** by declaring `--formulize-size-tokens` on `:root` in your `css/tokens.css`:

```css
:root {
  --formulize-size-tokens: 1;
}
```

**Provide sample screens** for the advanced size editor's preview, in an `appearance_preview` folder
in your theme. Each is an HTML file named after a screen: `form.html`, `list.html`, `drawer.html` and
`cards.html`. Provide the ones that suit your theme; the editor shows the ones it finds, and without
any, the Appearance page doesn't link to it. A sample is the markup your theme puts in `<body>` for
that kind of page, written out with sample content, and it is shown with your `css/reset.css`, your
`css/style.css` and your generated appearance stylesheet, in a `<body>` with the id `formulize` and
the class `formulize-screen`. No scripts run in it.

Mark each part of the sample that can be selected with `data-fz-part`, naming the part:

```html
<input type="button" class="formulize-form-submit-button" value="Save" data-fz-part="button">
```

The parts, and the tokens each one is sized by, are the `components` in
`modules/formulize/include/appearance_tokens.json`: `page`, `tabs`, `title`, `form`, `label`,
`field`, `value` (a read-only value), `options` (radio buttons and checkboxes), `help`, `button`,
`toolbar`, `menu`, `header` (column headings), `row`, `card` and `drawer`.

Pieces shared between samples go in files starting with an underscore, and are included by name in
double braces: `{{list}}` is the contents of `_list.html`. `{{logo_url}}` and `{{site_name}}` are your
logo and the site's name. Clicking an element with `data-fz-toggle="some-id"` in the preview toggles
the class `open` on the element with that id, for showing a menu.

Lyris's samples, in `themes/Lyris/appearance_preview/`, are a complete example.

## Checking your theme

Open a long form in your theme, scroll down, and save it. You should be returned to where you were
rather than to the top of the page. If you are not, the theme is either not firing
`formulize_pageShown` or scrolling an element Formulize could not find.
