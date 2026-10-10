---
layout: default
permalink: documentation/appearance_editor/
title: The Appearance Editor
---

# The Appearance editor

**Admin > Appearance > Styles and Colors** opens the Appearance editor: a live preview of the
theme's sample screens (a form and a list, and in Lyris also an entry open in the drawer, and a set of
cards), with the settings beside it. Only webmasters can open it. It opens on the site's theme; the
theme picker at the top switches between the themes that can be edited, without changing which theme
the site uses (that is in **Admin > Appearance > Settings**). **Back to Appearance** returns to the
Appearance tab you came from.

If the theme's appearance folder can't be written, or the theme doesn't use these settings, the
editor says so at the top for as long as that is the case.

## Looks

A **look** is how the site looks: its logo and favicon, colours, fonts and page width, and how big and
roomy everything is. One look is applied to the site.

- **Default** is the foundation. Every other look builds on it: whatever a look doesn't change comes
  from Default, and follows Default when it changes.
- **Compact** fits more on the screen: smaller text, shorter fields, buttons and list rows, and less
  space in forms, cards and the drawer.
- **Comfortable** is larger and roomier, and easier to read and to tap.
- **Your own looks**, made in advanced mode, can change anything.

Default, Compact and Comfortable come with Formulize. Compact and Comfortable only change sizes, so
your logo and colours stay as they are whichever of them is applied.

## Simple mode and advanced mode

The editor is in **simple mode** or **advanced mode**, for the whole site, switched at the top. In
advanced mode the header is tinted, with a coloured band along its top, so it is always clear which
mode you are in. Anari has simple mode only.

### Simple mode

The **Look** choice, and the site's logo and favicon, colours, fonts and page width. Choosing a look
shows it in the preview, and keeps whatever else you have changed; nothing changes on the site until
you **Save**, which applies the look with the rest of your changes. Until then the choice is listed
under **Changes**, with **Undo**, and the menu marks the look the site has as **(applied)**.

A change goes where the setting comes from: to the look being applied, if that look sets it, and to Default
otherwise. Set your colours with Comfortable applied and they are Default's, so they are still there
with Compact, and in advanced mode. If a look of your own sets its own colours, changing them here
changes that look's, which are the ones the site is showing.

### Advanced mode

Everything in simple mode, for any look, and every part of it. The menu at the top (**Editing:
Default ▾**) has every look: the ones that come with Formulize, marked **Built in**, and your own. The
look applied to the site is marked **Applied**. The editor opens on it.

While you edit a look other than Default, the preview shows the site with the look applied. Everything
the look changes is marked, with **Reset** to go back to Default's; everything else says "from
Default". A look can set something to the same as Default has it (Compact sets the label weight
Default has, for one): it is still marked as the look's, and stays as it is when Default changes,
until it is reset. With nothing selected in the preview, the **Site-wide** tab has the look's logo and
favicon, colours, fonts and page width.

The **Changes** tab has two lists:

- **Not saved yet**: what you have changed since the last save, each with what it was when saved,
  **Undo**, and **Show** to select the part it belongs to. Opening a look, or switching to another,
  starts with nothing here.
- **Changed on this site**: everything that differs from the theme's own appearance, saved or not,
  such as your logo and colours, each with what the theme's is and **Reset** to go back to it. For
  Compact and Comfortable, it is what differs from the look as it came with Formulize.

### One part at a time (advanced mode)

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

- **New look…** makes a look that changes nothing yet: it is the same as Default until you change
  something in it.
- **Duplicate** makes a copy of the look you are editing, to change as you like. A copy isn't linked
  to the look it came from: changing one doesn't change the other.
- **Rename** and **Delete** are for your own looks. Deleting the look applied to the site puts the
  site back on Default.
- **Revert** is for a built-in look that has been changed on the site: it puts the look back the way
  it came with Formulize, and for Default, the theme's own appearance, logo and favicon included.
  Your own looks have no Revert, since there is nothing they came as.
- **Apply to the site** applies the look you are editing. Save it first.

The built-in looks are changed the same way as your own: the changed one is kept for the site, and
the one that came with Formulize is kept as it is, for reverting to.

Each look is kept in its own stylesheet in the theme's `appearance/looks` folder: what it changes, and
the theme's appearance with it applied.

### Saving

**Save** saves what you are editing. Saving the look applied to the site changes the site straight
away; saving one that isn't applied doesn't. The editor stays as it was, on the same screen, width and
selection, and so does switching looks, applying one, or switching mode.

What a save or a change to the looks did is said in a note over the bottom of the preview, which goes
after a few seconds (not while the pointer is on it). A note about a problem stays until you close it.

**Reset changes** throws away what you haven't saved. To take back one of a look's own changes, use
**Reset** beside the setting.

## One screen with its own look or width

Each screen has an **Appearance** tab, between **Settings** and **Relationships**. On it:

- **Look** shows the screen with a look other than the site's: Compact for a long list, say. *Site
  default* follows the site, whichever look is applied to it. The link under it opens this editor on
  the look, to change it; a change to a look changes every screen that uses it.
- **Page width** lets the screen use the full width of the window on a large screen, whatever the
  look's page width is. A list with many columns usually needs this.
- **Introductory text** is shown above the screen: instructions, or anything people should read
  first. It is all shown, however long it is. In a list it sits above the entries, and scrolls away
  as people scroll down to them, leaving the column headings at the top. Write it in the rich text
  editor, or choose **Edit as code** to write the HTML yourself. If you have changed the screen's
  templates, it shows only where one of your templates has `$introductoryText`. Template screens
  don't have it, since their template is the whole screen.
- **Templates**, on the sub-tab beside the options, are the screen's templates. Template screens keep
  their templates on a tab of their own.

The page width a new screen starts with is in **Admin > Appearance > Settings**, under **Default
Screen Widths**: one choice for each type of screen. Lists start at the full width of the window, and
the other types at the look's page width, until you change it. Screens you have already made keep the
width they have.

## For theme authors

A theme is edited here when it provides sample screens for the preview. Advanced mode, and Compact and
Comfortable, are for a theme that also declares `--formulize-size-tokens` in its tokens. See [What Formulize expects from a theme](/documentation/themes/).

The settings themselves are the component tokens of [Formulize UI](/documentation/formulize_ui/):
use them in your own CSS (`height: var(--fz-control-height)`, `background: var(--fz-button-bg)`,
`font-family: var(--fz-label-font)`) and your styles follow these settings too.
