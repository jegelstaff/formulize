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
`fz-inline`.

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

## Checking your theme

Open a long form in your theme, scroll down, and save it. You should be returned to where you were
rather than to the top of the page. If you are not, the theme is either not firing
`formulize_pageShown` or scrolling an element Formulize could not find.
