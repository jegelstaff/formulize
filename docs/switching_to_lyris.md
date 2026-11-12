---
layout: default
permalink: documentation/switching_to_lyris/
title: Switching Your Site to Lyris
---

# Switching your site to Lyris

Lyris is Formulize's standard theme from version 8.30. It works well on phones, opens entries in a drawer beside the list, and its colours, fonts, logo and icon can all be set in [Appearance](../appearance_editor/). New sites start on Lyris. A site that uses Anari, or a theme of its own, keeps it until you switch, and Anari still works.

## When you update to 8.30

If your site uses a theme other than Lyris, such as Anari, the update asks you a few questions before it runs. Log in as a webmaster, and click **Update Formulize** on the update page. Nothing changes until you click **Update Formulize now** at the end. If you click **Cancel**, nothing changes, and you can open the questions again.

If someone who isn't a webmaster runs the update, they aren't asked anything. The update runs, and your site stays on its theme until a webmaster switches it.

### Choose what to do with the theme

You'll see sample screens in your site's theme and in Lyris, side by side. If your theme has no sample screens, you'll see Lyris on its own. Then choose one of these:

- **Switch my site to Lyris.** Everyone sees the site in Lyris.
- **Try Lyris myself first.** Webmasters see the site in Lyris, and everyone else still sees your current theme. Switch the whole site later, when you're ready. If webmasters see Lyris already, this isn't offered.
- **Keep** your current theme **for now**, such as **Keep Anari for now**. Nothing about the theme changes, and the update doesn't ask about looks, screen settings or templates.

### Choose a look

Lyris comes with several looks, each with its own colours, fonts, corners and spacing, such as Altan, Varetta and Lorestone. If you choose Lyris, the next step shows them, and the one you pick is the look your site starts with. All of them are on **Appearance > Looks** afterwards, where you can change any of them or switch to another.

### Your logo and icon come along

If your site shows its own logo or browser icon in its current theme, Lyris shows them too. You can change them any time in **Appearance > Looks**.

### Give your screens Lyris's settings

New screens start with different settings in Lyris. List screens open entries in the drawer, hide the search boxes until they're opened, and show 20 entries per page. Form screens put labels above the fields.

The update lists the screens that still have the settings they started with, and you can give them Lyris's settings. Check the boxes for the screens and settings you want. A setting you've changed on a screen yourself isn't listed, so it's kept. If you switch the whole site, every box starts checked. Otherwise they start unchecked. The drawer works in any theme.

### Bring custom templates along

Each theme has its own set of a screen's templates. If a screen has custom templates in your current theme, it uses Lyris's standard templates in Lyris, unless it has templates there too.

The update lists the screens with custom templates. Checked screens get a copy of their templates for Lyris. The copies keep your current theme's markup, so look at those screens in Lyris afterwards, because Lyris may not style them completely. Unchecked screens use Lyris's standard templates.

Template screens always get a copy of their code and template for Lyris, because they have nothing else to use there.

### After the update

The update's results end with **What to do next**, for the choice you made. If you switched your site, it lists any screens with custom templates you didn't copy, with links to their Templates tabs.

## Switching later

Go to **Appearance > Settings**:

- To switch the whole site, set both the **Default theme** and the **Admin theme** to Lyris.
- To try Lyris yourself first, set only the **Admin theme** to Lyris. Webmasters always see the admin theme, so you see the whole site in Lyris, and everyone else still sees the default theme.

When you change the default theme:

- every user's own theme changes to it
- every screen's Templates tab moves to it, so you edit the templates the site uses
- template screens take their code and template along to it, if they have none there yet

### The first time you switch to Lyris

If Lyris has never been your site's default theme, clicking **Save your changes** with Lyris chosen as the **Default theme** opens a few questions first. They're the same ones the 8.30 update asks once you've chosen Lyris: [the look your site starts with](#choose-a-look), [Lyris's settings for your screens](#give-your-screens-lyriss-settings), and [which screens bring their custom templates along](#bring-custom-templates-along). Your logo and icon come along too. A step is left out when it has nothing to ask. Every screen's settings box starts checked.

- Click **Switch to Lyris** at the end to make those changes, switch the theme, and save your other settings.
- Click **Switch without these changes** to switch the theme and save your settings, with no change to your look or your screens.
- Click **Cancel** to go back to the settings without saving anything.

If something can't be done, such as copying a screen's templates, the questions say what went wrong, and your site stays on its current theme. Fix the problem and click **Switch to Lyris** again. What was done already isn't offered again.

You're asked only once. After that, Lyris switches like any other theme. To be asked again, delete `themes/Lyris/appearance/activated.lock` on your server.

## Custom templates in Lyris

A screen's **Templates** tab shows the templates for one theme at a time. The theme is chosen at the top of the tab. When it isn't the theme your site uses, the tab says so.

If a screen has no custom templates in the theme shown, but it does in another theme, the tab has a button to copy them, such as **Copy this screen's templates from Anari**. Or click **Customize the Templates** to start from Lyris's standard templates.

You can prepare Lyris templates for a screen before you switch: choose Lyris at the top of its Templates tab, and copy or customize them there.

## Switching back

Set the **Default theme** and the **Admin theme** back to Anari on **Appearance > Settings**. Your screens' Anari templates are still there, and their Templates tabs move back to Anari. Every user's own theme changes back too.

Settings the update gave your screens, such as opening entries in the drawer, stay as they are. Change them on each screen's settings if you want to.
