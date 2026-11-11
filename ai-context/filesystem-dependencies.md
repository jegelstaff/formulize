# Filesystem dependencies

Formulize keeps some working state in files on purpose. It runs on an ordinary LAMP stack with no extra services, and at the scale it's used, tens to hundreds of people on a form at once, races on these files are not a practical problem. Don't propose Redis, a locks table or similar unless the user asks.

| Where | What |
|---|---|
| `modules/formulize/temp/` | Entry lock files and lock token files |
| `modules/formulize/cache/` | Cached data, such as compiled derived value formulas |
| `cache/` | Cached data, such as linked element option lists |
| `modules/formulize/code/` | Custom code, and the text and HTML admins write for static content and form screens. Its `.htaccess` refuses every web request, because a file run on its own would run outside Formulize. See [derived-values-and-custom-code](derived-values-and-custom-code.md). |
| `modules/formulize/templates/screens/<theme>/<sid>/` | A screen's own templates |

The web server must be able to write to these. `writableFilesAndFolders.json` at the repo root is the full list of what must be writable. See [Writable files and folders](../docs/writable_folders.md).

**Who can delete a lock:** a lock file holds the uid, the token of the page load that drew the entry, and the username, and `include/entryLocks.php` is the only code that names, writes or reads one. A release only deletes a lock its own page load still holds: the uid and the token both have to match.
- A page's release is sent as the page goes away, after the server has drawn the next page. A reload, or a link to the same entry, is a new page load that has already rewritten the lock with its own token, so the old page's late release leaves it alone. Matching on the uid alone used to let that release delete the new page's lock, and then a second person could open the entry for editing as well.
- `ignore_editing_lock` skips the lock check but not the write, so a webmaster overwrites the lock and takes it over. The other person's release doesn't match it any more.
- An element redrawn on its own, such as a conditional element, is part of a page that's still showing. It leaves a lock the person already has, and a lock it writes has no token, which any of that person's page loads that drew the entry can release. Locks written before the token was added work the same way.

## Uploaded files

`uploads/.htaccess` stops uploaded files from running. **`php_admin_flag` and `php_admin_value` are not allowed in `.htaccess`.** Under mod_php they cause a 500 for every request to the folder, images and PDFs included, even inside an `<IfModule mod_php.c>` guard. Use `php_flag` and `php_value`.

`formulizeFileUploadElementHandler::BLOCKED_EXTENSIONS` is the master list of file types refused on upload. The same list is in `uploads/.htaccess`, `include/uploads_execution_probe.php` and `docs/uploads_not_executable.md`, and `tests/blocked_extensions_sync_test.php` fails if they drift apart. `formulize_probeUploadsExecution()` is what warns an admin when the folder can run programs.
