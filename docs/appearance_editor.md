---
layout: default
permalink: documentation/appearance_editor/
title: Looks and the Appearance editor
---

# Looks and the Appearance editor

A **look** is how your site's pages look: the logo and favicon, the colours, the fonts, how wide the
pages can get, and the size of everything, from text to fields, buttons and list rows. Each theme
has its own looks. The site uses one of them, and a screen can use another.

Looks are managed on **Admin > Appearance > Looks**, and changed in the **Appearance editor**, which
opens from there. Only Formulize admins can use them.

## The Looks tab

The tab lists a theme's looks, and everything you can do to them except change how one looks.

- **Theme** chooses whose looks you are seeing. The site's theme is marked *(active)*. Choosing
  another theme here doesn't change which theme the site uses: that is under **Admin > Appearance >
  Settings**.
- **Site look** is the look every page uses, except a screen set to a look of its own. It is beside
  the theme, with **Change site look**, whenever the theme has more than one look, and its row in the
  list is tinted, with a *Site look* badge.

### The list

Each look is a row. A look based on another is listed under it, indented, with a line joining them.

- **Based on**: the look it is based on, or *Nothing*.
- **Changes**: what the look changes itself, in a word or two each:
  - *Branding*, when it has its own logo, favicon, colours or fonts;
  - its layout, named by what it is: a size (*Compact*, *Comfortable*, *Standard*), *Custom size*,
    *Page width*, or *Layout* for both;
  - *Parts*, and how many, for the parts of the interface changed one by one in the advanced editor.

  Click it to see the details: the looks it is based on, and each change, with the logo and favicon,
  the colours as swatches, and the fonts, size and parts by name.
- **Used by**: *The site*, if it is the site look, and how many screens are set to it. *+N
  indirectly* counts the screens that use a look based on it, which a change to it reaches too.
  Click it to see them, with a link to each screen's Appearance tab.
- **Edit** opens the look in the **simple editor** or the **advanced editor** (see below).
- **⋯** has everything else you can do to a look:
  - **Create look based on this**.
  - **Duplicate**: a look with the same base and the same changes as this one. It looks the same,
    but it doesn't follow this look afterwards, and the looks based on this one aren't copied.
  - **Rename**. Screens and looks that use it keep using it.
  - **Change base**: base it on another look, or on nothing. It keeps its own changes and takes
    everything else from its new base; any of its changes that are the same as the new base's are
    dropped. The looks based on it move with it. It can't be based on itself, or on a look based
    on it.
  - **Set as site look**.
  - **Delete**. The site look can't be deleted, and neither can a look that other looks are based
    on, until they are based on another. Screens set to a look you delete use the site look
    instead.

### Making a look

**Create new look** asks for:

- a **name**;
- what it is **based on**: one of the theme's looks, or *Nothing*, to start from the theme's own
  appearance;
- its **size**: the same as its base, or *Standard*, *Compact* or *Comfortable* (see Sizes below).

A new look changes nothing yet, apart from its size, so it looks just like its base until you edit it.

## How looks build on each other

A theme starts with one look, **Default**, which is based on nothing. Default is an ordinary look:
you can rename it, change it, or delete it, once another look is the site look and no look is based
on it.

A look records only what it changes itself. Everything else comes from the look it is based on, and
from that look's base, and so on. So:

- **A change to a look reaches every look based on it**, except where that look changes the same
  thing itself. Change the logo on Default, and every look based on Default has the new logo, unless
  it has a logo of its own.
- **A look can change anything**, its branding included. Most looks keep their base's logo and
  colours and change only sizes or parts. A look with its own logo or colours is for when some screens
  should look different: a public form with a partner's branding, say. Its Changes say *Branding*.

## Sizes

**Standard**, **Compact** and **Comfortable** are sizes that come with Formulize, for themes built on
Formulize UI's component sizes (Lyris is):

- **Standard** is the theme's own sizes.
- **Compact** fits more on the screen: smaller text, shorter fields, buttons and list rows, and less
  space in forms, cards and the drawer.
- **Comfortable** is larger and roomier, and easier to read and to tap.

A size isn't a look. Choosing one, when you make a look or in the simple editor, writes its values
into the look, and nothing keeps a link to it: you can then change the sizes one by one in the
advanced editor. A look whose sizes have been changed that way has a *Custom* size.

## The Appearance editor

**Edit** on the Looks tab opens the editor on that look, in the simple editor or the advanced one. It
changes that look, and nothing else. **Back to Looks** returns to the tab.

The editor shows the look on a live preview of sample screens (a form and a list, and in Lyris also
an entry open in the drawer, a set of cards, a page of text, and the side menu), with its settings beside it. **Desktop** and
**Phone** switch the preview's width.

**Simple** and **Advanced** at the top switch between the two editors at any time, keeping whatever
you haven't saved. Themes that don't use Formulize UI's component sizes, such as Anari, have the
simple editor only.

Everything is measured against the look's base. A setting the look doesn't change says it comes from
its base (*from Default*), and one it does change has a way back to its base's (*Use Default's*, or
**Reset**). For a look based on nothing, it is measured against the theme's own appearance.

### The simple editor

The look's **logo** and **favicon**, its **colours**, its main and secondary **fonts**, its
**size**, and its **page width**.

- **Size** offers the same as its base, *Standard*, *Compact* or *Comfortable*. When its sizes have
  been changed one by one in the advanced editor, it says *Custom*, and choosing a size asks before
  replacing them.
- **Page width** is how wide pages can get on a wide screen: a maximum width, which keeps forms and
  lists to a column of that width in the middle of the window, or the full width of the window.

### The advanced editor

Everything in the simple editor, except the size, and every part of the interface, one at a time.

- **Click anything in the preview** to select it: a button, a form field, a label, a list row, a
  title, the logo. Every one of that thing is outlined, and its settings appear on the right, in up
  to five groups: **Text** (its font, size, weight and colour), **Colours** (its background,
  border and the like), **Corners**, **Lines** (the thickness of a line, such as a separator), and
  **Size and spacing**.
- **A change applies to every one of them in the look**, not just the one you clicked. Changing the
  button height changes the buttons in lists, in forms and in the drawer. The panel lists the sample
  screens the part appears on; click one to see it there.
- **A part's font** is the main font or the secondary font. Titles and labels start on the secondary
  font, everything else on the main one.
- **A part's colours** start as one of the look's colours, such as Primary or Muted text, and keep
  following it when the colours change. Pick another of the look's colours, or a colour of the part's
  own. The panel warns when text on a background would be hard to read. The main button's hover
  colour is a darker shade of whatever colour it is given.
- **Corners** can be anything from square to well rounded; buttons and page tabs can be fully
  rounded too.
- **The logo is as tall as the top bar allows.** Select the logo, or the page, to change the top
  bar's height: a taller bar has a larger logo. On phones, Lyris keeps the bar and the logo at their
  usual size.
- **Written content has a page of its own, Text.** It has the parts of the text a page shows:
  paragraphs, headings at each of their six levels, links, bulleted and numbered lists, block quotes
  and separator lines. They are the ones in a page's content, such as a text element on a form, a
  screen template or a page of instructions, and not those that are part of something else, such as
  a card's title or a menu. Paragraphs set the size, line height, font and colour of all the running
  text. The headings share a font, a colour, a line height and the space above and below them, and
  each level has its own size and weight. A link's hover colour is a darker shade of its colour.
- **The side menu has a page of its own, Side menu.** Select the menu for its width, background,
  border and padding; a link, for the font, size, weight, colour, padding and corners of every link
  and group name, and the space between them; a link in a group, for their size, colour and indent, and the line beside them; and
  the link to the page you're on, for how it stands out: its background (on its own, and in a group),
  text colour, weight, and the short line beside it.
- **The spacing of a form is on its fields.** Select a field to change the space between fields;
  help text, for the space above it; choice options, for the space between them; a label, for the
  space below it.
- **Each size has a range**, and the slider only offers that range. Some sizes can't be smaller than
  the text inside them: a row can't be shorter than its text, and a list's title bar can't be shorter
  than its buttons. If a larger text size needs more room, the height grows to fit, and the panel
  says so.
- **A size with its own value on phones**, such as the title size, shows both, and the one for the
  preview's width is highlighted. On phones, Lyris keeps fields at least 44px tall so they are easy
  to tap, and the panel says when that applies.

Each part's **Not adjustable yet** list says which of its settings can't be changed here yet, such as
shadows, hover colours other than the main button's, small and large buttons, and the fixed spacing
at phone widths.

### Changes

With nothing selected, the **Changes** tab has two lists:

- **Not saved yet**: what you have changed since the last save, each with what it was, **Undo**, and
  in the advanced editor **Show**, to select the part it belongs to.
- **What the look changes**: everything that differs from its base (or from the theme's own
  appearance), saved or not, each with what the base has and **Reset** to go back to it.

### Saving

**Save** saves the look. When the look is in use, the editor says what a save will change, beside
Save: *Saving changes the site and 3 screens*, say. It counts the site when the look is the site look
or one the site look is based on, and the screens set to the look or to a look based on it. When
nothing uses the look, it says nothing, and a save changes nothing anyone sees until the look is
used.

**Reset changes** throws away what you haven't saved.

What a save did is said in a note over the bottom of the preview, which goes after a few seconds (not
while the pointer is on it). A note about a problem stays until you close it.

## One screen with its own look or width

Each screen has an **Appearance** tab, between **Settings** and **Relationships**. On it:

- **Look** shows the screen with one of the theme's looks, listed as on the Looks tab, each under the
  look it is based on. The site look is marked *(active Look for site)*: choosing it means the screen
  follows the site, whichever look that is. Choose another for a screen that should look different:
  Compact for a long list, or a look with another logo for a public form. The link under it opens
  the editor on the look; a change to a look changes everywhere it is used.
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

A theme has looks, and can be edited here, when it provides sample screens for the preview. The
advanced editor, and the sizes, are for a theme that also declares `--formulize-size-tokens` in its
tokens. See [What Formulize expects from a theme](/documentation/themes/).

Each look is kept in the theme's folder, in `appearance/looks`, as a stylesheet of its own: a block
at the top recording its name, what it is based on and what it changes, then the CSS of the look as
pages are shown with it. The theme's `appearance/appearance.css` records which look the site uses,
and is that look's CSS. A theme loads them by calling `formulize_renderAppearanceHead()` in its
`theme.html`, which links the right one for each page.

The settings themselves are the component tokens of [Formulize UI](/documentation/formulize_ui/):
use them in your own CSS (`height: var(--fz-control-height)`, `background: var(--fz-button-bg)`,
`font-family: var(--fz-label-font)`) and your styles follow every look too.
