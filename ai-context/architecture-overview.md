# Formulize Architecture

Where things are, and the path a request takes through them. For how Formulize behaves and why, see [formulize-module-overview](formulize-module-overview.md).

Formulize is a platform for creating data-driven applications. It is written primarily in PHP, and built on top of the **XOOPS/ImpressCMS** CMS framework.

**Foundation:** XOOPS (eXtendable Object Oriented Portal System) / ImpressCMS
**Database:** MariaDB/MySQL
**Template Engine:** Smarty, except for Formulize screen templates, which are plain PHP
**PHP Version:** 8.1+ required. Some code dates from the time of PHP 4 and 5 but has been updated.

---

## Core XOOPS/ImpressCMS Architecture

### Bootstrap Sequence

Every request needs steps 1 and 2. A normal webpage in the active theme goes through all five steps:

```
1. mainfile.php
   ↓ Defines paths, database credentials, security constants
   ↓ Auto-includes include/common.php

2. include/common.php
   ↓ Loads libraries/icms.php (kernel)
   ↓ Calls icms::setup() → Initialize paths/autoloader
   ↓ Calls icms::boot() → Create database/config/session services
   ↓ Calls icms::launchModule() → Register module classes
   ↓ Includes modules/formulize/include/common.php → Load Formulize classes, functions and display code on every page

3. header.php (optional)
   ↓ Create theme factory
   ↓ Initialize Smarty template engine
   ↓ Load CSS/JavaScript assets
   ↓ Set up block system

4. [Your page logic]
   ↓ Use handlers to access data
   ↓ Assign template variables, print output, or both (printed output is buffered)

5. footer.php (optional)
   ↓ Finalize session
   ↓ Render the page's content template, if it has one
   ↓ Add any buffered output after it
   ↓ Render the theme around that content and output the HTML
```

What this order means in practice:

- **Formulize module functions are defined on every page of the site**, including core pages like
  `user.php`, `edituser.php` and `lostpass.php`, not just pages under `/modules/formulize/`. The
  module bootstrap includes everything in `modules/formulize/class/` and `modules/formulize/include/`
  that the module needs.
- The bootstrap runs **before** any page includes `header.php`, so code in `header.php` can call
  Formulize module functions.
- Individual entry points (`modules/formulize/index.php`, `application.php`, `groups.php`,
  `users.php`, `signup.php`, `edituser.php`, …) *also* include various files explicitly, as a
  fallback in case they're called directly. This is mostly left over from before the module bootstrap
  was part of the core system. Today the `include_once` in the core bootstrap runs on every page that
  includes `mainfile.php`, so don't reason about what's available from the files a single entry point
  includes.
- The theme is resolved eagerly in `icms_view_theme_Factory::createInstance()` when `header.php` is
  included, from `$icmsConfig['theme_set']` (or an explicit `folderName` option). Setting `theme_set`
  after `header.php` has run doesn't change the rendering theme, but it *does* change
  `$xoopsConfig['theme_set']` (the two are the same array by reference, see
  `libraries/icms/config/Handler.php`), which is what per-screen template lookup uses. See
  [embedding-screens](embedding-screens.md).

### Skipping header.php and footer.php

`header.php` and `footer.php` are only needed together when you're outputting a normal webpage. Bootstrapping with `mainfile.php` already brings the system into scope, so endpoints for asynchronous calls often leave one or both out:

| Include | When | Example |
|---|---|---|
| `mainfile.php`, `header.php`, `footer.php` | A normal webpage in the active theme | `modules/formulize/index.php` |
| `mainfile.php`, `header.php` | You need the theme system in scope, but you're outputting something other than a normal page, such as a snippet of HTML | `modules/formulize/include/redrawSubformRow.php` |
| `mainfile.php` | You don't need the theme system, such as when outputting JSON | `modules/formulize/formulize_xhr_responder.php` |

These endpoints usually turn off the logger and clear any output buffers first, so that only their own output is in the response:

```php
require_once "../../mainfile.php";
icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}
```

Include `header.php` after this if you need it. Without `footer.php`, nothing renders the page, and whatever you print is sent as is.

### A Minimal Page

The page's content can come from a Smarty template, from printed output, or from both:

```php
<?php
// 1. Bootstrap
include "mainfile.php";  // Loads DB, paths, common.php

// 2. Get data using handlers
$member_handler = xoops_gethandler('member');
$user = $member_handler->getUser(1);

// 3. Declare the page's Smarty template, if it has one. This has to happen before header.php
$xoopsOption['template_main'] = 'modulename_welcome.html';

// 4. Initialize theme/template
include "header.php";

// 5. Display content
if ($user) {
    // Assign the values that the template needs. In the template: <{$username}>
    $xoopsTpl->assign('username', $user->getVar('uname'));

    // Or print the content.
    // If a template is also being used, anything printed appears after the template's output
    echo "<h1>Welcome, " . $user->getVar('uname') . "</h1>";
}

// 6. Render and output
include "footer.php";
```

Leave out step 3 if the page only prints its content. A new Smarty template has to be registered, as explained in [Template Registration](formulize-module-overview.md#template-registration).

### Global Services

Once `mainfile.php` is included, `icms::$db`, `icms::$user` and `icms::$config` are available, along with the globals listed in the [Quick Reference](coding-guidelines.md#quick-reference). `$xoopsTpl` and `$xoopsTheme`, and their `$icms` equivalents, are available after `header.php`.

Core events fire through `icms::$preload->triggerEvent()`, and the handlers are in `plugins/preloads/`.

---

## File Structure

```
/
├── mainfile.php              # Bootstrap entry point. The tracked copy is an uninstalled placeholder
├── header.php                # Theme/template initialization
├── footer.php                # Output rendering
├── index.php                 # Homepage routing
├── include/
│   ├── common.php            # Core initialization
│   ├── functions.php         # Global helper functions
│   ├── constants.php         # Path constants
│   └── 2fa/                  # Two-factor authentication
├── libraries/
│   ├── icms.php              # Kernel service container (the icms class)
│   └── icms/                 # The core system's classes, see below
├── kernel/                   # Older XOOPS class names that wrap libraries/icms
│   ├── object.php            # XoopsObject, extends icms_core_Object
│   └── user.php              # XoopsUser, extends icms_member_user_Object
├── class/
│   └── database/             # Older database class names that wrap libraries/icms/db
├── modules/
│   ├── formulize/            # Main Formulize module
│   └── system/               # Core system module
├── themes/                   # Visual themes
├── install/                  # The installer
├── ai/                       # The embedded AI assistant
├── mcp/                      # The MCP server, tools in tools.php
├── integration_api.php       # For host systems that call Formulize, such as Drupal. Its functions aren't dead code
├── tests/                    # PHP tests, and the Playwright suite in tests/e2e/
├── docs/                     # The formulize.org website
├── docker/                   # The local development environment
└── trust/                    # Secure config (outside webroot in production)
```

### Inside libraries/icms

`libraries/icms/` is the core system. When you trace a call into the core, this is usually where it ends up, because the XOOPS-era names in `kernel/` and `class/` mostly wrap these classes. See [icms-core-is-ours-to-edit](icms-core-is-ours-to-edit.md) for how to treat this code.

Class names map directly to file paths, with each underscore becoming a folder: `icms_db_criteria_Item` is in `libraries/icms/db/criteria/Item.php`. `Autoloader.php` loads classes on demand, so you don't need to include them.

| Folder | What's in it |
|---|---|
| `core/` | The foundations: `Object.php` and `ObjectHandler.php` (the base classes for all objects and handlers), `Session.php`, `Security.php` (tokens and referer checks), `Password.php`, `Logger.php`, `HTMLFilter.php`, `DataFilter.php`, `Textsanitizer.php`, `Filesystem.php` |
| `db/` | Database connections. `criteria/` builds query conditions, and `legacy/` is the code behind `$xoopsDB` |
| `member/` | Users, groups and group permissions (`user/`, `group/`, `groupperm/`) |
| `config/` | System preferences (`category/`, `item/`, `option/`) |
| `module/` | Installed module records |
| `view/` | Output: `Tpl.php` (the Smarty wrapper), `theme/`, `block/`, `template/`, `PageBuilder.php` |
| `form/` | Form classes, with field types in `elements/` |
| `auth/` | Login methods: `Xoops.php` (local accounts), `Ldap.php`, `Ads.php` (Active Directory), `Openid.php` |
| `preload/` | Runs the event handlers in `plugins/preloads/`, which is how the core is extended at set points. `Event.php` is the event system itself |
| `messaging/` | Email and SMS |
| `data/` | Core records: avatars, comments, files, notifications, pages, private messages, URL links |
| `file/` | Uploads, downloads, and tar and zip handling |
| `image/` | Images, with their categories and sets |
| `plugins/` | Editor and plugin loading |
| `sys/` | `autotasks/`, for scheduled tasks |
| `feeds/` | RSS |
| `ipf/` | The ImpressCMS Persistable Framework, a higher-level object and handler layer. The system, profile and content modules use it, but Formulize doesn't |

### Where database credentials are

`mainfile.php` names a trust file, in a folder that should be outside the webroot in production. It holds the database credentials as `SDATA_DB_*` constants, which `mainfile.php` copies to `XOOPS_DB_*`. Never change `SDATA_DB_SALT` after an install. Every installation has its own table prefix, so don't hardcode one.

---

## Formulize Code Organization

### Module Structure

```
modules/formulize/
├── index.php             # Entry point (includes initialize.php)
├── initialize.php        # Main routing logic
├── master.php            # Alternate starting point that ignores screens
├── class/
│   ├── formulize.php     # formulizeHandler, with static methods for groups, elements and more
│   ├── forms.php         # Form handler & object
│   ├── elements.php      # Element handler & object
│   ├── data.php          # Entry data handler
│   ├── frameworks.php    # Form relationships
│   ├── screen.php        # Screen handler & object
│   ├── applications.php  # Menu/application handler
│   ├── elementrenderer.php # Converts Formulize elements to XOOPS form elements
│   ├── *Screen.php       # Screen type classes (listOfEntries, multiPage, etc.)
│   └── *Element.php      # Element type classes (text, select, etc.)
├── include/              # All functions that aren't part of a class, see below
│   ├── functions/        # Newer groups of functions, one file per topic
│   └── js/               # JavaScript for the front end
├── admin/
│   ├── ui.php            # Main admin interface
│   ├── form.php          # Form management UI
│   ├── element.php       # Element management UI
│   ├── screen.php        # Screen management UI
│   ├── save.php          # Receives admin saves, and hands off to save/
│   ├── save/             # Admin save handlers
│   └── patches/          # Update patches
├── code/                 # Custom code, such as derived value formulas and form procedures
├── language/             # All the words Formulize shows, see docs/translating_text.md
├── templates/
│   ├── admin/            # Admin interface templates (Smarty)
│   ├── blocks/           # Block templates (Smarty)
│   ├── css/              # Stylesheets, including formulize-ui.css
│   └── screens/          # Screen templates (PHP, not Smarty)
└── sql/
    └── mysql.sql         # Database schema
```

What Formulize calls a Form Screen today uses `multiPageScreen.php`, whether it has one page or many. `formScreen.php` is a legacy class, not in use except on very old systems.

### Inside modules/formulize/include

This folder holds most of the logic of the module. It's a mix of function libraries and scripts that run as pages or endpoints of their own. See [docs/code_flow.md](../docs/code_flow.md) for how a request moves through these files.

**Startup**
- `common.php`: the module bootstrap, loaded on every page. It includes most of the libraries below and defines a `{FORM_HANDLE}_FORM_ID` constant for each form.
- `constants.php`: shared constants.
- `customCodeForApplications.php`: loads each application's custom code from `modules/formulize/code/`.

**Reading and saving data**
- `readelements.php`: reads form submissions and saves them to the database, on every page load, before anything is rendered. See [Data Saving](formulize-module-overview.md#data-saving-readelementsphp).
- `extract.php`: the universal data extraction layer. See [Data Extraction](formulize-module-overview.md#data-extraction-extractphp).
- `readentries.php`: the shared core for reading entries, behind the Public API and the MCP `get_entries_from_form` tool.
- `subformSaveFunctions.php`: writes new subform entries.
- `import_functions.php`: processes CSV imports.

**Rendering**
- `formdisplay.php`: `displayForm()` and the `formulize_themeForm` class, plus `getEntryValues()`, `compileElements()`, `loadValue()`, `writeHiddenSettings()` and `drawJavascript()`.
- `formdisplaypages.php`: `displayFormPages()`, the page structure of form screens. It hands off to `formdisplay.php`.
- `multipage_boilerplate.php`: the JavaScript for submitting form screens and moving between pages.
- `elementdisplay.php`: `displayElement()`, which checks whether the user can see an element in an entry and hands off to `class/elementrenderer.php`.
- `griddisplay.php`: `displayGrid()`, for elements laid out as a table.
- `entriesdisplay.php`: `displayEntries()`, plus `drawInterface()`, `drawEntries()`, saved views and custom buttons.
- `calendardisplay.php`, `mapdisplay.php`, `graphdisplay.php`: rendering for calendars, maps and graphs.
- `js/`: `autocomplete.js`, `conditional.js`, `drawer.js` (the right slide-out drawer), `filterpopovers.js`, `scroll_container.js`, `pwa.js`, `formulize-app.js` and `appearance_editor.js`.

**General functions**
- `functions.php`: a giant set of functions for all kinds of operations, some tiny, some huge. Check here before writing a new helper.
- `functions/`: where `functions.php` is being split up, one file per topic, such as `pwa.php` and `linked_sources.php`. Look in both places. Put new self-contained groups of functions here rather than in `functions.php`, but don't move untouched code. The `require_once` lines at the end of `functions.php` load these files, and nothing else includes them directly, so add a line there for any new file.
- `usersAndGroups.php`: the users and groups management feature, including entries-are-users and entries-are-groups forms.
- `primary_relationship_functions.php`: sets up and maintains the Primary Relationship.
- `writeToFormulizeLog.php`: the Formulize activity log.

**Popups for lists of entries**
- `changecols.php`: change columns.
- `changescope.php`: change scope.
- `pickcalcs.php`, `pickadvcalcs.php`: choose calculations and advanced calculations.
- `save.php`: save and publish views. It doesn't save entries.
- `export.php`, `import.php`: export and import data.
- `setnot.php`: set notifications.
- `moreinfo.php`: show more information about an element.
- `popup_functions.php`: what these popups have in common, including their Formulize UI markup.

**Asynchronous endpoints and background work**
- `redrawSubformRow.php`: redraws a row of a subform.
- `formdisplay-elementsonly.php`: draws an elements-only form for AJAX requests, for everything the right slide-out drawer shows.
- `fileintake.php`: receives files for File Upload elements other than through a browser form, such as through the MCP tools.
- `formulize_quickselect.php`: looks up matching options for autocomplete elements.
- `queue.php`: processes a queue of tasks. `class/queue.php` starts it.

**Admin and configuration**
- `configsettings.php`, `configsettings_registry.php`: the settings on the Formulize admin homepage.
- `synchronization.php`, `synccompare.php`: export and compare data for synchronizing between systems.
- `formulizeConfigSync.php`, `formulizeConfigSyncElementValueProcessor.php`: configuration as code.
- `aiadminconfig.php`: configuration and key storage for the AI Assistant.
- `on_update.php`: runs when the module is updated.
- `notification.inc.php`: supplies item information for notifications.
- `mime_types_map.php`: a map of file extensions to MIME types.
- `appearance.php`, `appearance_tokens.php`, `appearance_tokens.json`, `appearance_sizes/`: the looks a theme's pages are shown with, and the tokens they can change.
- `ui_catalog.php`: the reference for Formulize UI.
- `uploads_execution_probe.php`: checks whether the web server will execute scripts saved in the uploads folder.

### Formulize Code Execution Flow

```
User visits: /modules/formulize/index.php?fid=5&sid=10

index.php
  ↓ Includes mainfile.php and header.php
  ↓ Includes initialize.php

initialize.php
  ↓ Parses fid (form ID) and sid (screen ID)
  ↓ If neither was given, uses the user's default screen. A form asked for by an anonymous visitor resolves to its default screen too
  ↓ Checks that the user can view the form
  ↓ Includes include/readelements.php, which saves any submitted data before anything is rendered
  ↓ If there's a screen: calls the render() method of the screen's handler
  ↓ If there's no screen: uses the form's default list or form screen, based on the user's permissions
  ↓ If the form has no default screen: calls displayEntries() or displayForm() directly

Screen handler's render() method (in class/*Screen.php)
  ↓ Works out the parameters and settings to use, based on the screen's configuration
  ↓ Usually calls the underlying display function
  ↓ Sometimes outputs content directly instead, as Template screens do

Display functions (in include/)
  ↓ Gather the data and work out the runtime values, such as buttons, rows and rendered elements
  ↓ displayForm() calls displayElement() for each element, by way of compileElements()
  ↓ Pass those values to the screen templates

Screen templates (in templates/screens/)
  ↓ Run as PHP, with the runtime values available as ordinary variables
  ↓ Generate the markup and print it to the buffer

index.php
  ↓ Includes footer.php, which renders the page
```

If `$formulize_screen_id` is set before `index.php` is included, the file doesn't include `mainfile.php` or `footer.php`. That's how a screen is embedded in a PHP page. See [Using Formulize from your own PHP](formulize-module-overview.md#using-formulize-from-your-own-php).

| Screen type | What `render()` does | Function is in |
|---|---|---|
| `listOfEntries` | Calls `displayEntries()` | `include/entriesdisplay.php` |
| `multiPage` | Calls `displayFormPages()`, which hands off to `displayForm()` | `include/formdisplaypages.php` |
| `form` (legacy) | Calls `displayForm()` | `include/formdisplay.php` |
| `calendar` | Calls `displayCalendar()` | `include/calendardisplay.php` |
| `map` | Calls `displayMap()` | `include/mapdisplay.php` |
| `template` | Runs the screen's own template and outputs the result, with no display function of its own. The template can call display functions itself | `class/templateScreen.php` |

See [docs/code_flow.md](../docs/code_flow.md) for the flow in more detail.

### Screen Templates

Screen templates are **PHP files, not Smarty**. The display functions work out the values, and these templates turn them into markup. Smarty syntax doesn't work in them. Smarty is used elsewhere in the module, for the `.html` templates in `templates/admin/`, `templates/blocks/` and the top level of `templates/`.

For each template, Formulize uses the first of these that has content:

1. `<theme>/<screen id>/`: the screen's own template
2. `<theme>/default/<screen type>/`: the active theme's default
3. `default/<screen type>/`: the system default

```
modules/formulize/templates/screens/
├── Lyris/
│   ├── default/
│   │   ├── drawer/           # The right slide-out drawer
│   │   ├── form/             # Forms with no screen, and legacy form screens
│   │   ├── listOfEntries/
│   │   ├── map/
│   │   └── multiPage/        # Form screens
│   ├── 123/                  # Custom templates for screen ID 123
│   │   └── toptemplate.php
│   └── 456/
├── Anari/                    # The same layout, with no drawer/ folder
└── default/                  # Global fallback templates
    ├── drawer/
    ├── form/
    ├── listOfEntries/
    ├── map/
    └── multiPage/
```

**List of entries:** `toptemplate.php` (content above the list), `openlisttemplate.php` (opens the list container), `listtemplate.php` (each row), `closelisttemplate.php` (closes the container) and `bottomtemplate.php` (content below the list).

**Form screens, in `multiPage/`:** `toptemplate.php` (title, tabs, navigation), `elementcontainero.php` and `elementcontainerc.php` (open and close the container around an element), `elementtemplate1.php` (single-column layout), `elementtemplate2.php` (two-column layout, which collapses to one on narrow screens) and `bottomtemplate.php`.

A custom template that holds nothing but an opening PHP tag counts as empty, so the next one along is used. `formulize_themeForm::processTemplate()` runs form templates with `eval()`, with the screen's template variables in scope.

The markup and styling in the templates and the themes follow Formulize UI. See [formulize-ui](formulize-ui.md) for where its reference is.

---

## Configuration Access

```php
global $icmsConfig;

$icmsConfig['sitename']      // Site name
$icmsConfig['theme_set']     // Active theme
$icmsConfig['startpage']     // Homepage (can be per-group array)
$icmsConfig['language']      // Site language
$icmsConfig['gzip_compression']  // Compression enabled?

global $icmsModuleConfig;
$icmsModuleConfig['setting_name']  // Settings of the current module
```

Use `XOOPS_URL` for the site's address, and `XOOPS_ROOT_PATH` for its path.

---

## Reference

The developer documentation is in the repository's `docs/` folder, so read it there instead of fetching formulize.org. [docs/developer.html](../docs/developer.html) lists the pages. [docs/deploying_locally.md](../docs/deploying_locally.md) covers setting up a local development environment.
