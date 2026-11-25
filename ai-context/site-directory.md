# The site's own files

A site's own files are kept apart from the code that ships with Formulize, so the code can be read-only, as in a container image, and a site can be backed up or moved as its database plus its files. Every folder a site writes to at runtime, or that holds files its people wrote, is named once, in `include/sitepaths.php`.

**Never build one of these folders from `XOOPS_ROOT_PATH` or `XOOPS_URL`.** Use the constant. A path typed out from the root reads and writes inside the code, wherever the site's files really are. `tests/site_paths_test.php` fails when one appears.

## The site folder

A site installed since the site folder came in keeps everything in one folder, `FORMULIZE_SITE_PATH`:

```
<site>/
  settings.php   SDATA_DB_* constants, each taken from an environment variable when one is set
  install.lock
  .htaccess      refuses everything
  private/       XOOPS_TRUST_PATH: code, screens, config, language, logs, queue
  uploads/       ICMS_UPLOAD_PATH, served. Its .htaccess lets files through and keeps scripts from running
  var/           FORMULIZE_VAR_PATH: cache, templates_c, tokens, htmlpurifier, formulize-cache, temp, export
```

**How a request finds it.** `mainfile.php` is tracked and never edited. It requires `include/site.php`, which:

1. Sets `FORMULIZE_SITE_PATH` from a `FORMULIZE_SITE_PATH` variable in `$_SERVER` (Apache's `SetEnv`, maybe with a `REDIRECT_` prefix) or the environment, or to `XOOPS_ROOT_PATH/sites/default`.
2. Includes `<site>/settings.php`. With none, it sends the visitor to the installer, unless `install.lock` is in the root: that's a site installed before, whose own `mainfile.php` was replaced, and the installer would install over its database, so it says what's wrong instead.
3. Defines what the old `mainfile.php` did: `XOOPS_TRUST_PATH` as `<site>/private`, `XOOPS_URL`, the `XOOPS_DB_*` constants from the `SDATA_*` ones, the group ids. `XOOPS_URL` is `FORMULIZE_URL` if `settings.php` or the environment sets it. Otherwise it's worked out from the request, and from X-Forwarded-Host and X-Forwarded-Port only when `FORMULIZE_TRUST_PROXY_HEADERS` is on. X-Forwarded-Proto is always used, as it always was.

`$xoopsOption['nocommon']` before including `mainfile.php` gives the settings and those constants without the rest of the system. `formulize_deleteEntryLock.php` does that and then includes `sitepaths.php`, so it finds the lock folder without starting a session.

**A site installed before** keeps its own `mainfile.php`, which defines no `FORMULIZE_SITE_PATH`, so `sitepaths.php` uses the folders in the code. Everything works as it always did.

**The installer** shows the site folder rather than asking for a trust path, writes `settings.php` from `install/templates/settings.dist.php` (mode 0640), writes `<site>/.htaccess`, copies the shipped `uploads/` into the site's uploads, and puts `install.lock` in the site folder. The only thing it still writes in the code is `modules/protector`, copied from `install/modules/` when it isn't there yet.

**Uploads have a different URL in a site folder.** When the site folder is in the web root, `ICMS_UPLOAD_URL` is its real location, `/sites/default/uploads`, so nothing has to rewrite it, on Apache or nginx. `sites/.htaccess` and `<site>/.htaccess` refuse the rest of the folder, and `uploads/.htaccess` has `Require all granted`, which wins in that subfolder, while its `<FilesMatch>` still refuses scripts. When the site folder is outside the web root, the URL stays `/uploads` and the web server needs an alias. So never write `/uploads/...` as a URL: use `ICMS_UPLOAD_URL`, or `formulize_uploadsUrlPath()` for a path relative to the site's URL.

**Folders are made when they're missing.** For a site folder, `sitepaths.php` makes any folder it names that doesn't exist, on every request. So `var/` can be emptied, and the folders never need making by hand.

## The constants

`include/constants.php` loads `include/sitepaths.php`, after `XOOPS_ROOT_PATH`, `XOOPS_TRUST_PATH` and `XOOPS_URL` are known. Each constant is guarded with `!defined`, so `settings.php` can set any of them first. Paths and URLs have no trailing slash.

| Constant | Holds | In a site folder | Before the site folder |
|---|---|---|---|
| `FORMULIZE_CODE_PATH` | Custom code and authored text, see [derived-values-and-custom-code](derived-values-and-custom-code.md) | `private/code` | `modules/formulize/code` |
| `FORMULIZE_SCREEN_TEMPLATES_PATH` | A screen's own templates, `<theme>/<sid>/` | `private/screens` | `modules/formulize/templates/screens` |
| `FORMULIZE_SCREEN_DEFAULTS_PATH` | The shipped templates, `default/` and `<theme>/default/`. Part of the code | `modules/formulize/templates/screens` | same |
| `FORMULIZE_CONFIG_PATH` | `forms.json`, for configuration as code | `private/config` | `modules/formulize/config` |
| `FORMULIZE_SITE_LANGUAGE_PATH` | The site's own mail templates, `<language>/mail_template/`, and `<language>/formulize_dateformats.php` | `private/language` | `modules/formulize/language` |
| `FORMULIZE_LOG_PATH` | The default for the log folder setting | `private/logs` | `logs` |
| `FORMULIZE_QUEUE_PATH` | Queued jobs, not yet run | `private/queue` | `modules/formulize/queue` |
| `FORMULIZE_NOTIFICATION_QUEUE_PATH` | Notifications not yet sent, and their history | `private/queue/notifications` | `modules/formulize/cache` |
| `FORMULIZE_CACHE_PATH` | Formulize's generated files, such as derived value formula wrappers | `var/formulize-cache` | `modules/formulize/cache` |
| `FORMULIZE_TEMP_PATH` | Entry locks and their token lists, queue locks, files being extracted | `var/temp` | `modules/formulize/temp` |
| `FORMULIZE_EXPORT_PATH` | Synchronization exports | `var/export` | `modules/formulize/export` |
| `FORMULIZE_DOWNLOADS_PATH`, `_URL` | Generated files people download by URL, such as spreadsheet exports | `uploads/downloads` | `cache` |
| `ICMS_UPLOAD_PATH`, `_URL` | Uploaded files, and appearance files in `appearance/<theme>/` | `uploads` | `uploads` |
| `ICMS_CACHE_PATH` | The core's generated files, and Formulize's query and option caches | `var/cache` | `cache` |
| `ICMS_COMPILE_PATH` | Compiled Smarty templates | `var/templates_c` | `templates_c` |
| `ICMS_TOKEN_PATH` | Security tokens | `var/tokens` | `tokens` |
| `ICMS_PURIFIER_CACHE` | HTML Purifier's definitions | `var/htmlpurifier` | `<trust>/cache/htmlpurifier` |

`FORMULIZE_VAR_PATH` moves all of `var/` at once, such as onto a disk of the server's own. With more than one web server, `var/` has to be shared, for the tokens and locks.

## Traps

- **A screen's own templates and the shipped ones are in two folders**, which are the same in a site installed before the site folder. Look for a screen's template in `FORMULIZE_SCREEN_TEMPLATES_PATH`, and for a default in `FORMULIZE_SCREEN_DEFAULTS_PATH`. `formulize_screenTemplateMatchesDefault()` compares a screen's template with the default for its theme, so it builds the default's path from the defaults folder, not from the folder beside the screen's.
- **Appearance files leave the theme.** In a site folder, `formulize_getAppearanceDir()` is `uploads/appearance/<theme>`, and the looks a theme ships in its own `appearance/looks/` are copied there by `formulize_seedAppearanceLooks()` the first time the theme's looks are read. Build appearance URLs with `formulize_getAppearanceUrl()`, never by taking the root off a path.
- **Mail templates come from two places.** The mailer is given one folder and a file name. `formulize_mailTemplateFolder()` gives the folder that holds a template, preferring the site's own over the one shipped, and `formulize_siteMailTemplateFolder()` gives the folder to write a template to, such as a digest or an error report. The core notification handler asks the first for Formulize's notifications.
- **Some scripts run without the bootstrap.** `templates/css/build-drupal-css.php` runs for the Drupal integration and still writes into the module's cache folder, because it rewrites `url()`s relative to it.
- **A PHP file a site writes can be run by an old copy in opcache.** Call `formulize_phpFileChanged()` after writing custom code or a screen template. Generated wrappers are named for what's in them instead, so a change is a new file: see `formulizeForm::procedure_cache_filename()` and `formulize_includeDerivedValueFormulas()`.
- **Specs find the site's files with `tests/e2e/site-paths.js`,** which asks the running site, so they work whichever way it keeps them.
- **Not moved yet:** graphs made with `displayBarGraph()` and the like write into `modules/formulize/images/graphs/`, which doesn't ship, and the theme editor (`admin/themeeditor.php`) edits the shipped themes' files in place.
