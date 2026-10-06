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

Lyris can be edited in the advanced editor. Anari can't, and only has the Default look: its colours,
fonts and logo are set on the Appearance page.

## Looks

The settings on the Appearance page are the site's own appearance. A **look** is a set of changes to
them, and one look is applied to the site, chosen with **Look** at the top of the Appearance page:

- **Default** changes nothing: the site looks the way the settings on the page have it.
- **Compact** fits more on the screen: smaller text, shorter fields, buttons and list rows, and less
  space in forms, cards and the drawer.
- **Comfortable** is larger and roomier, and easier to read and to tap.

These three come with Formulize. Whatever a look doesn't change comes from the settings on the
Appearance page: applying Compact keeps your colours, fonts and logo. Save the Appearance page to apply
a look across the site.

Looks are changed, and new ones made, in the advanced editor (see below). Looks of your own are
offered here too.

## The advanced editor

**Open the advanced editor**, at the top of the Appearance page, opens a live preview of the theme's
sample screens (a form, a list, an entry open in the drawer, and a set of cards), with the settings
beside it. Only webmasters can open it. Save any changes on the Appearance page first: opening the
editor leaves the page, and the page asks before losing them.

The editor edits **looks**. It opens on the look applied to the site, which is Default on a site
that hasn't chosen another. The menu at the top (**Editing: Default ▾**) has every look: the ones that
come with Formulize, marked **Built in**, and your own. The look applied to the site is marked
**Applied**.

While you edit a look, the preview shows the site with the look applied. Everything the look changes
is marked, with **Reset** to go back to the Appearance page's setting; everything else says "from the
Appearance page", and follows the Appearance page when it changes.

### Site-wide settings

With nothing selected in the preview, the **Site-wide** tab has what the look changes for the whole
site: the logo and favicon, the colours, the main and secondary fonts (including any Google Font, by
name), and the page width. They start as the Appearance page's. A look can have a logo and favicon of
its own, or use the Appearance page's. Every part of the interface follows these, except for anything
the look changes on the part itself.

The **Changes** tab lists everything the look changes: the site-wide settings first, then each part's
own settings, each with **Reset**, and **Show** to select the part it belongs to.

### One part at a time

- **Click anything in the preview** to select it: a button, a form field, a label, a list row, a
  title, the logo. Every one of that thing is outlined, and its settings appear on the right, in up
  to four groups: **Text** (its font, size, weight and colour), **Colours** (its background,
  border and the like), **Corners**, and **Size and spacing**.
- **A change applies to every one of them on the site**, not just the one you clicked. Changing the
  button height changes the buttons in lists, in forms and in the drawer. The panel lists the sample
  screens the part appears on; click one to see it there.
- **A part's font** is the main font or the secondary font, whichever the Site-wide tab sets them
  to. Titles and labels start on the secondary font, everything else on the main one.
- **A part's colours** start as one of the site's colours, such as Primary or Muted text, and keep
  following it when the site's colours change. Pick another of the site's colours, or a colour of the
  part's own. The panel warns when text on a background would be hard to read. The main button's
  hover colour is a darker shade of whatever colour it is given.
- **Corners** can be anything from square to well rounded; buttons and page tabs can be fully
  rounded too.
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

Each part's **Not adjustable yet** list says which of its settings can't be changed here yet, such as
shadows, hover colours other than the main button's, small and large buttons, and the fixed spacing
at phone widths.

### Making and managing looks

From the menu:

- **New look…** makes a look that changes nothing yet: it is the same as the Appearance page's
  settings until you change something in it.
- **Duplicate** makes a copy of the look you are editing, to change as you like. A copy isn't linked
  to the look it came from: changing one doesn't change the other.
- **Rename** and **Delete** are for your own looks. Deleting the look applied to the site puts the
  site back on Default.
- **Revert** is for a built-in look that has been changed on the site: it puts the look back the way
  it came with Formulize. Your own looks have no Revert, since there is nothing they came as.
- **Apply to the site** applies the look you are editing. Save it first.

The built-in looks are changed the same way as your own: the changed one is kept for the site, and
the one that came with Formulize is kept as it is, for reverting to.

Each look is kept in its own stylesheet in the theme's `appearance/looks` folder: what it changes, and
the theme's appearance with it applied.

### Saving

**Save** saves the look you are editing, keeping only what differs from the Appearance page's
settings. Saving the look applied to the site changes the site straight away; saving one that isn't
applied doesn't. The editor stays as it was, on the same screen, width and selection.

**Reset changes** takes back everything the look changes, in the editor, so it is the same as the
Appearance page's settings. Nothing changes until you save.

## For theme authors

A theme opts in by declaring `--formulize-size-tokens` in its tokens, and provides the sample screens
for the preview. See [What Formulize expects from a theme](/documentation/themes/).

The settings themselves are the component tokens of [Formulize UI](/documentation/formulize_ui/):
use them in your own CSS (`height: var(--fz-control-height)`, `background: var(--fz-button-bg)`,
`font-family: var(--fz-label-font)`) and your styles follow these settings too.
