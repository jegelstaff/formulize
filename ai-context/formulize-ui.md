# Formulize UI: the rules for front-facing markup and styling

Any markup or styling for something users see in Formulize follows the rules and principles of
**Formulize UI**, the `fz-` classes and tokens in `modules/formulize/templates/css/formulize-ui.css`.
Read its reference before writing the HTML or CSS, every time, without being asked.

This note only says where the reference is. It does not repeat it, and you should not work from a
remembered summary of it: the reference changes, and every copy of it is generated from one source.

## Where the reference is

- **The source** is `modules/formulize/include/ui_catalog.php`, the one place the classes and tokens
  are documented. Start with its first section, "How it works": the rules, the naming, what is not
  there compared with Tailwind CSS, and where the classes can be used. The other sections are Tokens,
  Layout, Components, Utilities, Recipes and For theme authors.
- **The whole reference as one Markdown document**, which is the easiest thing to read:

      php modules/formulize/include/ui_catalog.php --markdown

  Without PHP installed, run it in the local development environment's web container:

      docker exec formulize-web-1 php -d xdebug.mode=off /var/www/html/modules/formulize/include/ui_catalog.php --markdown

  In Git Bash on Windows, put `MSYS_NO_PATHCONV=1` in front of that, or Git Bash turns the `/var/www/...` path into a Windows path and the file isn't found.

  It is long, so write it to a temporary file and read the parts you need.
- **On the documentation site** it is [Formulize UI](https://formulize.org/documentation/formulize_ui/),
  at `/documentation/formulize_ui/`. Those pages are generated from the catalog on every build by
  `docs/_plugins/formulize_ui_pages.rb`. They are not files under `docs/`, so searching `docs/` for a
  class name finds nothing.
- **Hand-written pages around it:** `docs/templates.md`, `docs/themes.md` (what a theme supplies: the
  space at the edges of a page, the page width, printing, the Appearance editor) and
  `docs/appearance_editor.md`.
- **In a running site** the style guide at `/modules/formulize/styleguide.php` (webmasters only) shows
  every example in that site's own theme and Appearance settings. Over MCP the reference is the
  `formulize_ui` topic of the `get_documentation` tool.

## What it covers

Screen templates and template screens, derived values and text elements that output HTML, application
custom code that outputs HTML, the front-end output of the module itself, and a theme's templates and
stylesheets.

The admin UI (`modules/formulize/templates/admin/`) has its own styles and does not use the `fz-`
classes. Ask before restyling an admin page with Formulize UI. For the admin UI's JavaScript rules see
[coding-guidelines](coding-guidelines.md).

## When the reference doesn't have what you need

If the thing is general (a kind of component, a layout, a utility), add it to `formulize-ui.css` and
document it in the catalog. Don't write a one-off version for one screen or one site, even when the work is for one site: put it in core, so every site gets it. After changing
either file, run the catalog's check, which the `formulize-ui-check` workflow also runs on pull
requests:

    php modules/formulize/include/ui_catalog.php --check

## Say so in your report

When you report on work where you followed the Formulize UI guidelines, say that you did. That is how
the person reading the report knows the reference was found and used when it was needed.

Say it only when it is true and you read the reference in that session. If part of the work departs
from it (something the reference has no class for, an inline style, a theme's own class), say which
part and why.

See also [themes-anari-and-lyris](themes-anari-and-lyris.md).
