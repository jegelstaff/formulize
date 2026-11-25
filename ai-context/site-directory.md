# The site's own files

A site's own files are kept apart from the code that ships with Formulize, so the code can be read-only, as in a container image, and a site can be backed up or moved as its database plus its files. Every folder a site writes to at runtime, or that holds files its people wrote, is named once, in `include/sitepaths.php`.

**Never build one of these folders from `XOOPS_ROOT_PATH` or `XOOPS_URL`.** Use the constant. A path typed out from the root reads and writes inside the code, wherever the site's files really are. `tests/site_paths_test.php` fails when one appears.

## The constants

`include/constants.php` loads `include/sitepaths.php`, after `XOOPS_ROOT_PATH`, `XOOPS_TRUST_PATH` and `XOOPS_URL` are known. Each constant is guarded with `!defined`, so `mainfile.php`, or anything it includes, can set any of them first. Paths and URLs have no trailing slash.

| Constant | Holds | Backed up? | Default |
|---|---|---|---|
| `FORMULIZE_CODE_PATH` | Custom code and authored text, see [derived-values-and-custom-code](derived-values-and-custom-code.md) | Yes | `modules/formulize/code` |
| `FORMULIZE_SCREEN_TEMPLATES_PATH` | A screen's own templates, `<theme>/<sid>/` | Yes | `modules/formulize/templates/screens` |
| `FORMULIZE_SCREEN_DEFAULTS_PATH` | The shipped templates, `default/` and `<theme>/default/`. Part of the code, never the site | n/a | `modules/formulize/templates/screens` |
| `FORMULIZE_CONFIG_PATH` | `forms.json`, for configuration as code | Yes | `modules/formulize/config` |
| `FORMULIZE_SITE_LANGUAGE_PATH` | The site's own mail templates, `<language>/mail_template/`, and `<language>/formulize_dateformats.php` | Yes | `modules/formulize/language` |
| `FORMULIZE_LOG_PATH` | The default for the log folder setting | Yes | `logs` |
| `FORMULIZE_QUEUE_PATH` | Queued jobs, not yet run | Yes | `modules/formulize/queue` |
| `FORMULIZE_NOTIFICATION_QUEUE_PATH` | Notifications not yet sent, and their history | Yes | `modules/formulize/cache` |
| `FORMULIZE_CACHE_PATH` | Formulize's generated files, such as derived value formula wrappers | No | `modules/formulize/cache` |
| `FORMULIZE_TEMP_PATH` | Entry locks and their token lists, queue locks, files being extracted | No | `modules/formulize/temp` |
| `FORMULIZE_EXPORT_PATH` | Synchronization exports | No | `modules/formulize/export` |
| `FORMULIZE_DOWNLOADS_PATH`, `_URL` | Generated files people download by URL, such as spreadsheet exports | No | `cache` |
| `ICMS_UPLOAD_PATH`, `_URL` | Uploaded files, served at `/uploads` | Yes | `uploads` |
| `ICMS_CACHE_PATH` | The core's generated files, and Formulize's query and option caches | No | `cache` |
| `ICMS_COMPILE_PATH` | Compiled Smarty templates | No | `templates_c` |
| `ICMS_TOKEN_PATH` | Security tokens | No | `tokens` |
| `ICMS_PURIFIER_CACHE` | HTML Purifier's definitions | No | `<trust>/cache/htmlpurifier` |

The defaults are the folders inside the code that Formulize has always used, so a site whose `mainfile.php` sets none of them works as it always has.

## Traps

- **A screen's own templates and the shipped ones are in two folders**, which happen to be the same by default. Look for a screen's template in `FORMULIZE_SCREEN_TEMPLATES_PATH`, and for a default in `FORMULIZE_SCREEN_DEFAULTS_PATH`. `formulize_screenTemplateMatchesDefault()` compares a screen's template with the default for its theme, so it builds the default's path from the defaults folder, not from the folder beside the screen's.
- **URLs stay the same wherever the files are.** An uploaded file is at `/uploads/...` whether the uploads folder is in the code or not. Code that has a path relative to the site's URL and needs the file finds it with `formulize_fileForUrlPath()`, never with `XOOPS_ROOT_PATH . $path`. `formulize_get_file_version()` does this already.
- **Mail templates come from two places.** The mailer is given one folder and a file name. `formulize_mailTemplateFolder()` gives the folder that holds a template, preferring the site's own over the one shipped, and `formulize_siteMailTemplateFolder()` gives the folder to write a template to, such as a digest or an error report. The core notification handler asks the first for Formulize's notifications.
- **Some scripts run without the bootstrap**, so the constants aren't there. `formulize_deleteEntryLock.php` releases locks as a page goes away, and loading `mainfile.php` there would save the session in parallel with the next page, so `formulize_entryLockFolder()` falls back to the module's temp folder. `templates/css/build-drupal-css.php` runs for the Drupal integration and still writes into the module's cache folder, because it rewrites `url()`s relative to it.
- **A PHP file a site writes can be run by an old copy in opcache.** Call `formulize_phpFileChanged()` after writing custom code or a screen template. Generated wrappers are named for what's in them instead, so a change is a new file: see `formulizeForm::procedure_cache_filename()` and `formulize_includeDerivedValueFormulas()`.
- **Graphs** made with `displayBarGraph()` and the like write into `modules/formulize/images/graphs/`, which doesn't ship. That hasn't been moved.
