---
layout: default
permalink: developers/themes/
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
named `formulize-embed-theme.marker` in the theme folder to mark it as one.

The simplest way to make your own is to copy the `themes/formulize_embed` folder. Keep two things in
your copy: that marker file, and the script at the bottom of `theme.html`, which is how an embedded
screen reports its height to the page hosting it.

## Checking your theme

Open a long form in your theme, scroll down, and save it. You should be returned to where you were
rather than to the top of the page. If you are not, the theme is either not firing
`formulize_pageShown` or scrolling an element Formulize could not find.
