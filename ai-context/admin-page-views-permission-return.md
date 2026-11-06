# Admin page views and the permission return

`modules/formulize/admin/configsubject.php` is the generic wrapper for registry-declared
subject tabs (`include/configsettings_registry.php`). For `type => 'page'` views it does
`include $pageFile;` where `$pageFile` is an existing admin controller (e.g.
`managekeys.php`, `managetokens.php`, `managepermissions.php`, `mailusers.php`,
`looks.php`, `themeeditor.php`) that enforces its own permission model via a bare top-level `return;`
when denying access.

**The trap:** a bare `return;` inside an *included* file only exits that file. Execution
resumes in the parent (`configsubject.php`) right after the `include` statement. So when a
page's own permission check denies access, `configsubject.php` would carry on building the
subnav, breadcrumbs and `$adminPage['subview_template']`, and render the page shell anyway.
Non-webmasters could then load webmaster-only pages.

**What the wrapper does:** it captures the include's return value — `$pageResult = include $pageFile;`. PHP
returns `int(1)` from a file that runs to completion without an explicit `return`, but
`NULL` from a bare `return;`. So `if($pageResult === null) { return; }` right after the
include correctly detects a denied controller and stops `configsubject.php` before any
chrome is built. This relies on each page-type controller having **exactly one** top-level
bare `return;` (for its permission check) and no other unconditional top-level bare
returns.

**How to apply:** any time a new `type => 'page'` view is added to
`configsettings_registry.php`, its controller file must keep this convention (single
top-level bare `return;` for permission denial, no other bare top-level returns) or the
include-return detection in `configsubject.php` will misfire.
