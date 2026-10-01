---
layout: default
permalink: documentation/appearance_sizes/
title: Sizes on the Appearance Page
---

# Sizes on the Appearance page

The Appearance page (Admin > Appearance) sets how big and how roomy the interface is, for each theme
that supports it. Lyris does; Anari does not, so the Size settings don't appear for it.

## The Size preset

**Size preset** sets every size at once:

- **Compact** fits more on the screen: smaller text, shorter fields, buttons and list rows, and less
  space in forms, cards and the drawer.
- **Default** is the theme's own sizes.
- **Comfortable** is larger and roomier, and easier to read and to tap.

Save the Appearance page to apply it across the site.

## Adjusting individual sizes

**Adjust individual sizes**, under the Size preset, opens the advanced size settings: a live preview
of the theme's sample screens (a form, a list, an entry open in the drawer, and a set of cards), with
the settings beside it. Only webmasters can open it.

- **Click anything in the preview** to select it: a button, a form field, a label, a list row, a
  title. Every one of that thing is outlined, and its sizes appear on the right.
- **A change applies to every one of them on the site**, not just the one you clicked. Changing the
  button height changes the buttons in lists, in forms and in the drawer. The panel lists the sample
  screens the part appears on; click one to see it there.
- **Each size has a range**, and the slider only offers that range. Some sizes can't be smaller than
  the text inside them: a row can't be shorter than its text, and a list's title bar can't be shorter
  than its buttons. If a larger text size needs more room, the height grows to fit, and the panel
  says so.
- **Desktop and Phone** switch the preview's width. A size with its own value on phones, such as the
  title size, shows both, and the one for the preview's width is highlighted. On phones, Lyris keeps
  fields at least 44px tall so they are easy to tap, and the panel says when that applies.
- **With nothing selected**, the panel lists every size you have changed, each with **Show**, to
  select the part it belongs to, and **Reset**.
- The **Size preset** at the top works the same as on the Appearance page. Changed sizes stay changed
  when you pick another preset.

**Save** keeps the preset and the changed sizes. The Appearance page's other settings (colours,
fonts, logo) are not touched, so save any changes to those on the Appearance page first. A reset on
the Appearance page clears the changed sizes too.

Each part's **Not adjustable yet** list says which of its sizes can't be changed here yet, such as
corner radii, small and large buttons, and the fixed spacing at phone widths.

## For theme authors

A theme opts in by declaring `--formulize-size-tokens` in its tokens, and provides the sample screens
for the preview. See [What Formulize expects from a theme](/documentation/themes/).

The sizes themselves are the component size tokens of [Formulize UI](/documentation/formulize_ui/):
use them in your own CSS (`height: var(--fz-control-height)`) and your styles follow these settings
too.
