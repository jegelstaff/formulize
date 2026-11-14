---
layout: default
permalink: documentation/templates/
redirect_from:
 - developers/templates/
title: Templates
---

# Working With Templates

Templates use the [Smarty](http://www.smarty.net/) templating system.

<strong>Note:</strong> the open/close tag is non-standard: `<{ }>`

## Changes Not Showing?

If you've made changes to a template, but the changes do not appear in the page, it's probably because the template was already compiled, and it has not been recompiled to reflect your changes. The .html template files are compiled into .php files in the /templates\_c folder. By default, templates are only compiled the first time the template is encountered. There are two ways to force a template to recompile. Either delete the compiled version in the templates_c folder, or change the setting which causes the templating system to check templates and recompile them automatically. This option requires a Webmaster account, and it can be found in the administrative control panel. Look in the top menu under System » Site Configuration » Preferences » General Settings. The relevant option is labelled "Check templates for modifications?".

## Inspecting Template Variables

Any variables can be made available in a template. Sometimes, determining exactly which variables are available and how they are named is very helpful. To do this, add the following tag into the template file you are working on:

    <{ debug }>

The next time the page is refreshed, a popup window will appear listing all of the available variables and showing what they contain.

## A List's Variable Templates

In Lyris, three small parts of a list of entries are drawn by templates of their own, called variable templates:

- `$currentViewList`, the switcher for saved views, beside the list's title
- `$moreActionsButton`, the button that opens the list's other actions
- `$pageNavControls`, the page controls at the bottom of the list

When you click **Customize the Templates** on a list screen's Templates tab, the screen gets its own copy of these as well as of its main templates. A **Variables** tab then appears beside Templates, on the screen's Appearance tab, with a box for each one. Edit a box and click **Save your changes**, and the list shows your version of that part.

- The Variables tab shows the files for the theme picked on the Templates tab. Anari has no variable templates, so a list screen there has no Variables tab.
- Empty a box and save, and that part of the list goes back to the default. Once every box is empty, the Variables tab goes away.
- While the screen still has variable templates of its own, its Templates tab doesn't offer **Customize the Templates**, even if you've emptied every main template. Empty the boxes on the Variables tab first.
- To put a screen back on the default templates completely, empty every box on both the Templates and the Variables tabs, and save.

## Urls

The site can be installed in the root of a website, or in a subfolder. For example, the site might be located at http://formulize.com/ or that might be a blog and the Formulize portion of the website might be located at http://formulize.com/demo/. For this reason, when adding links in templates, use the base url variable like this:

    <a href="<{$icms_url}>/my/page.html">My Page</a>

## CSS Rules

Before writing CSS for something you add to a template, look for it in [Formulize UI](../formulize_ui/): the classes for laying out and styling markup in templates, screens and themes. They follow the theme and the Appearance settings, so what you build with them keeps matching the rest of the site.

You may need to style new elements you add to a template. Most likely the element you add will be specific to Formulize, so the CSS rules should go into the Formulize CSS files. Much of the necessary CSS to make Formulize work is in the style.css file in the applicable theme (under the /theme/ folder). CSS organization will be rationalized at some point in the future.
