---
layout: default
permalink: documentation/appearance_editor/
title: The Appearance Page and Its Advanced Editor
---

# The Appearance page and its advanced editor

The Appearance page (Admin > Appearance) sets how the interface looks, for each theme: the logo and
favicon, the colours, the fonts, and how big and how roomy everything is. Its **advanced editor** does
all of that too, on a live preview, and can also change one part of the interface at a time, such as
buttons, field labels or list rows.

Lyris can be edited in the advanced editor. Anari can't, and doesn't offer the Size preset either:
its colours, fonts and logo are set on the Appearance page.

## The Size preset

**Size preset** sets every size at once:

- **Compact** fits more on the screen: smaller text, shorter fields, buttons and list rows, and less
  space in forms, cards and the drawer.
- **Default** is the theme's own sizes.
- **Comfortable** is larger and roomier, and easier to read and to tap.

Save the Appearance page to apply it across the site.

## The advanced editor

**Open the advanced editor**, at the top of the Appearance page, opens a live preview of the theme's
sample screens (a form, a list, an entry open in the drawer, and a set of cards), with the settings
beside it. Only webmasters can open it. Save any changes on the Appearance page first: opening the
editor leaves the page, and the page asks before losing them.

### Site-wide settings

With nothing selected in the preview, the **Site-wide** tab has the Appearance page's own settings:
the logo and favicon, the colours, the main and secondary fonts (including any Google Font, by name),
and the Size preset. Changes show in the preview as you make them. Every part of the interface follows
these, except for anything changed on the part itself.

The **Changes** tab lists everything that differs from the theme's own: the site-wide settings first,
then each part's own settings, each with **Reset**, and **Show** to select the part it belongs to.

### One part at a time

- **Click anything in the preview** to select it: a button, a form field, a label, a list row, a
  title, the logo. Every one of that thing is outlined, and its settings appear on the right.
- **A change applies to every one of them on the site**, not just the one you clicked. Changing the
  button height changes the buttons in lists, in forms and in the drawer. The panel lists the sample
  screens the part appears on; click one to see it there.
- **The spacing of a form is on its fields.** Select a field to change the space between fields;
  help text, for the space above it; choice options, for the space between them; a label, for the
  space below it.
- **Each size has a range**, and the slider only offers that range. Some sizes can't be smaller than
  the text inside them: a row can't be shorter than its text, and a list's title bar can't be shorter
  than its buttons. If a larger text size needs more room, the height grows to fit, and the panel
  says so.
- **Desktop and Phone** switch the preview's width. A size with its own value on phones, such as the
  title size, shows both, and the one for the preview's width is highlighted. On phones, Lyris keeps
  fields at least 44px tall so they are easy to tap, and the panel says when that applies.
- A part's sizes stay as you set them when you pick another Size preset.

Each part's **Not adjustable yet** list says which of its settings can't be changed here yet, such as
corner radii, small and large buttons, and the fixed spacing at phone widths.

### Saving

**Save** saves everything in the editor: the site-wide settings, any new logo or favicon, and the
parts' own settings. **Reset changes** puts everything back to the theme's own, logo and favicon
included, in the editor; nothing changes on the site until you save.

A reset on the Appearance page clears the parts' own settings too. While parts have settings of their
own, the Appearance page names them, since its settings don't change those.

## For theme authors

A theme opts in by declaring `--formulize-size-tokens` in its tokens, and provides the sample screens
for the preview. See [What Formulize expects from a theme](/documentation/themes/).

The settings themselves are the component tokens of [Formulize UI](/documentation/formulize_ui/):
use them in your own CSS (`height: var(--fz-control-height)`) and your styles follow these settings
too.
