# Bootstrap include order

`mainfile.php` sets up the XOOPS_URL, XOOPS_ROOT_PATH, and database connection constants, and includes `include/common.php` which bootstraps the core ICMS system, and includes `modules/formulize/include/common.php` near its end, which is the module bootstrap for Formulize and includes all the module's classes and functions, which are in the `modules/formulize/class` and `modules/formulize/include` directories.

Consequences that are easy to get wrong:

- **Formulize module functions are defined on every page of the site**, including core pages like
  `user.php`, `edituser.php` and `lostpass.php` — not just pages under `/modules/formulize/`.
- The bootstrap runs **before** any page includes `header.php`, so code in `header.php` can call Formulize module
  functions.
- Individual entry points (`modules/formulize/index.php`, `application.php`, `groups.php`,
  `users.php`, `signup.php`, `edituser.php`, …) *also* include various files explicitly as a fallback in case they are called directly, but this is mostly a historical anachronism from before the module bootstrap was well defined and included in the core system. Today, the
  `include_once` from the core bootstrap already runs on every page as long as `mainfile.php` has been included, so do not reason about availability from the files included by a single entry point.

The theme is resolved eagerly in `icms_view_theme_Factory::createInstance()` when `header.php` is included,
from `$icmsConfig['theme_set']` (or an explicit `folderName` option). Setting `theme_set` after
`header.php` has run does not change the rendering theme — but it *does* change
`$xoopsConfig['theme_set']` (the two are the same array by reference, see
`libraries/icms/config/Handler.php`), which is what per-screen template lookup uses. See
[embedding-screens](embedding-screens.md).
