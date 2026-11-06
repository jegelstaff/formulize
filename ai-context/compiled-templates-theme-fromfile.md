# Compiled templates: `theme_fromfile` and `templates_c`

Smarty compiles every template (module templates stored in the database, and theme files such as
`themes/<theme>/theme.html`) into a PHP file in `templates_c/`. Whether it ever checks a compiled
file against its source is decided by the system setting **`theme_fromfile`** (Settings, Debugging
section), which sets Smarty's `compile_check` in `libraries/icms/view/Tpl.php`.

A fresh install sets `theme_fromfile` to 0. With 0, a compiled file is used for as long as it
exists, however much its template has changed since, on disk or in the `tplfile`/`tplsource`
tables.

## What clears `templates_c`

- **Updating the Formulize module** empties it on every update (`formulize_patch_002_always_run()`
  in `modules/formulize/admin/patches/002_always_run.php`).
- **Nothing else does.** Reinstalling (the 001 install specs), restoring a database snapshot, or
  editing a template by hand leaves the old compiled files in place.

So after a reinstall or a snapshot restore on a development site, the database has current
templates while `templates_c` still serves copies compiled days earlier. CI never sees this: it
starts from a fresh checkout with an empty `templates_c`.

## Symptoms

Markup on the page that doesn't match the template, such as attributes the template adds that are
missing from the page, or a theme edit that doesn't show up. A test that fails locally but passes in
CI on the same commit is the usual way it surfaces.

## What to do

1. Check the setting: `SELECT conf_value FROM <prefix>_config WHERE conf_name='theme_fromfile'`
   (see Database Access in [local-environment-and-testing](local-environment-and-testing.md)).
2. When it is 0, delete `templates_c/*.php` (keep `index.html`) after any reinstall, restore or
   template edit, or run the module update. The compiled files are rebuilt on the next page load.
3. Don't change the setting itself to work around this without asking: it is the site's setting, and
   turning it on costs every page a check of every template.

Compare `templates_c` file dates with when the database was rebuilt to confirm the cause. Compiled
files older than the rebuild are suspect.
