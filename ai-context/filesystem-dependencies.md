# Filesystem dependencies

Formulize keeps some working state in files on purpose. It runs on an ordinary LAMP stack with no extra services, and at the scale it's used, tens to hundreds of people on a form at once, races on these files are not a practical problem. Don't propose Redis, a locks table or similar unless the user asks.

| Where | What |
|---|---|
| `modules/formulize/temp/` | Entry lock files and lock token files |
| `modules/formulize/cache/` | Cached data, such as compiled derived value formulas |
| `cache/` | Cached data, such as linked element option lists |
| `modules/formulize/code/` | Custom code. See [derived-values-and-custom-code](derived-values-and-custom-code.md). |
| `modules/formulize/templates/screens/<theme>/<sid>/` | A screen's own templates |

The web server must be able to write to these. `writableFilesAndFolders.json` at the repo root is the full list of what must be writable. See [Writable files and folders](../docs/writable_folders.md).

**Who can delete a lock:** `formulize_deleteEntryLock.php` only deletes a lock whose uid matches `$userPassedUid`. `ignore_editing_lock` skips the lock check but not the write, so a webmaster overwrites the lock with their own uid. Without the uid check, the regular user's token could clear the webmaster's lock and let both edit at once.

## Uploaded files

`uploads/.htaccess` stops uploaded files from running. **`php_admin_flag` and `php_admin_value` are not allowed in `.htaccess`.** Under mod_php they cause a 500 for every request to the folder, images and PDFs included, even inside an `<IfModule mod_php.c>` guard. Use `php_flag` and `php_value`.

`formulizeFileUploadElementHandler::BLOCKED_EXTENSIONS` is the master list of file types refused on upload. The same list is in `uploads/.htaccess`, `include/uploads_execution_probe.php` and `docs/uploads_not_executable.md`, and `tests/blocked_extensions_sync_test.php` fails if they drift apart. `formulize_probeUploadsExecution()` is what warns an admin when the folder can run programs.
