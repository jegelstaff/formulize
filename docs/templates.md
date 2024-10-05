---
layout: default
permalink: developers/templates/
---

# Working With Templates

Templates use the [Smarty](http://www.smarty.net/) templating system.

## Changes Not Showing?

If you've made changes to a template, but the changes do not appear in the page, it's probably because the template was already compiled, and it has not been recompiled to reflect your changes. The .html template files are compiled into .php files in the /templates\_c folder. By default, templates are only compiled the first time the template is encountered. There are two ways to force a template to recompile. Either delete the compiled version in the templates_c folder, or change the setting which causes the templating system to check templates and recompile them automatically. This option requires a Webmaster account, and it can be found in the administrative control panel. Look in the top menu under System » Site Configuration » Preferences » General Settings. The relevant option is labelled "Check templates for modifications?".

## Inspecting Template Variables

Any variables can be made available in a template. Sometimes, determining exactly which variables are available and how they are named is very helpful. To do this, add the following tag into the template file you are working on:

    <{ debug }>

The next time the page is refreshed, a popup window will appear listing all of the available variables and showing what they contain.

## Urls

The site can be installed in the root of a website, or in a subfolder. For example, the site might be located at http://formulize.com/ or that might be a blog and the Formulize portion of the website might be located at http://formulize.com/demo/. For this reason, when adding links in templates, use the base url variable like this:

    <a href="<{$icms_url}>/my/page.html">My Page</a>

## CSS Rules

You may need to style new elements you add to a template. Most likely the element you add will be specific to Formulize, so the CSS rules should go into the Formulize CSS files. ~~Formulize uses [SASS/SCSS](http://sass-lang.com/), which has a very similar syntax to CSS. The rules are broken into several files. One for front-end pages, one for admin pages, one for colours and function definitions, and one for CSS rules that are shared by both the front-end and admin sections~~ Much of the necessary CSS to make Formulize work is in the style.css file in the applicable theme (under the /theme/ folder). CSS organization will be rationalized at some point in the future.
