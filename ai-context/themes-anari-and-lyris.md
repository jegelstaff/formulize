# Themes: Lyris, Anari, and not coupling to either

`themes/` holds these, and a site can add its own:

| Theme | What it is |
|---|---|
| **Lyris** | The default theme. Mobile-native, built on Formulize UI, and fully editable in the Appearance editor. |
| **Anari** | The legacy theme. Existing sites still use it, so it stays supported. |
| `formulize_embed` | For screens embedded in other websites. It's kept out of the normal theme pickers by a `formulize-embed-theme.marker` file, and it borrows the site theme's styling. See [embedding-screens](embedding-screens.md). |

For what a theme has to supply, read [What Formulize expects from a theme](../docs/themes.md). For looks, read [Looks and the Appearance editor](../docs/appearance_editor.md). For markup and styling, see [formulize-ui](formulize-ui.md). This note covers what isn't in those.

## Where the two themes differ

| | Lyris | Anari |
|---|---|---|
| **Screen templates** | Own set in `templates/screens/Lyris/default/`, including `drawer/` | Own set in `templates/screens/Anari/default/`, with no `drawer/`, so it uses the global one |
| **Appearance editor** | Simple and advanced editors, and sizes. It opts in with `--formulize-size-tokens`. | Simple editor only |
| **Showing the page** | Doesn't hide the body. `script.js` fires `formulize_pageShown`. | Hides the body and reveals it from `theme.html`, which fires `formulize_pageShown` |
| **Scrolling** | Names its scrolling elements on `<body>` | Names none, and relies on Formulize's fallback |
| **AI assistant link** | In the top bar | In the menu block, from `drawAIAssistantMenuSection()` |
| **Sidebar** | Only drawn when the left column has blocks (`<{if $xoBlocks.canvas_left}>`) | Always drawn, even when it has nothing in it |
| **No header or menus** | `?fz_inline=1` leaves them out, and adds the `formulize-inline` body class | No equivalent |
| **New screens** (`screen-defaults.json`) | Lists open entries in the drawer, hide the search row and show 20 entries. Forms have one column | Lists open entries full screen, show the search row and show 10 entries. Forms have two columns, with captions at 20% |

A theme's `screen-defaults.json`, in its folder, holds every setting with a fixed value that new screens start with. Each theme's file lists all of them, even where its value is the standard one, and `tests/screen_defaults_test.php` fails if a theme's file leaves one out. When you add a setting to `modules/formulize/include/screen_defaults.json`, which has Formulize's standard values and fills in for a theme without a file, add it to every theme's file too. Words, which are in the site's language, and values from the form stay in each type's `setDefault…ScreenVars()`.

The 8.30 update wizard reads Lyris's file through `getThemeScreenDefaults()` on the screen handler, so don't rename either one without changing the wizard. It offers only the settings it has a label for, so a setting that matters there needs a label in the wizard. See [Choose the settings new screens start with](../docs/themes.md) for the format, and `applyDefaultScreenVars()` in `class/screen.php` for how they're applied.

Screen templates are the difference that catches people out. A change to the default markup of a screen may have to be made in the Lyris, Anari and global `default/` folders, because each has its own copy. See [Screen Templates](architecture-overview.md#screen-templates).

## Work in both themes

Lyris is where new front-end work is aimed, but it should benefit Anari wherever the two meet: a page both themes draw, a shared stylesheet, a core feature both use.

- Check front-end work in both themes, not only the one the site is running.
- Don't report a shortfall in Anari as "pre-existing" and move on. Fix it, or if the fix is a real design decision, say so and recommend one.
- Prefer a fix at the shared source to a patch in each theme.
- Anari's own design choices still stand. Formulize UI classes take on Anari's colours and fonts there, so don't override those from core to make a symptom go away.

## Don't build on a theme's internals

Themes are interchangeable, and sites add their own. Anything derived from one theme's class names, layout or conventions is wrong on every other theme. Depend on Formulize-level contracts instead: Appearance settings, platform state (`isAIAssistantEnabled()`, `notify_method`, `isAdmin()`, the application handlers) and the documented events, such as `formulize_pageShown`. That event is a good example of the difference. The event is a contract, and hiding the body first is one theme's implementation detail.

**Watch for theme checks in shared code.** `drawAIAssistantMenuSection()` in `include/functions.php` returns nothing when `$xoopsConfig['theme_set'] == 'Lyris'`, because Lyris shows the AI link itself. Any check like that inside a data builder is a trap: the data silently disappears for callers that have nothing to do with that theme. Don't add new ones.

## Seeing a page in another theme

Change the two settings on **Admin > Appearance > Settings**: the default theme (`theme_set`) and the admin theme (`theme_admin_set`). Webmasters always get the admin theme, so for them nothing else works, including posting `theme_select` or changing the allowed themes. Note the original values first, and put them back afterwards.

Someone who isn't a webmaster can be switched for a session with `theme_select`, if the theme is one of the allowed themes.

## Signed-out visitors: the Login block and the open menu

Both themes draw the system **Login block** in the sidebar (the left block column), and their header
Login / Sign in link (`.js-open-login`) opens the sidebar on that block when the sidebar has one,
or follows its own address when it doesn't. What decides whether a page has the block is the
block's own settings, not the theme. Check them before reasoning about where the login form is.

- **Which pages a block shows on** is a `(module_id, page_id)` pair in `block_module_link`, set in
  the block's admin page (`modules/system/admin.php?fct=blocksadmin&op=mod&bid=<id>`). `(0, 0)` is
  all pages and `(0, 1)` is the top page only (`getAllByGroupModule()` in
  `libraries/icms/view/block/Handler.php`).
- **The installer puts the Login block (bid 2) on the top page only** (`install/sql/pdo.mysql.formulize_standalone.sql`).
  On every other page, a signed-out visitor's sidebar has no login form. `/user.php` draws its own
  login form in the page body instead.
- **Who sees a block** is the `block_read` group permission. The Formulize menu block (bid 24, fixed
  by the installer) shows on all pages, and Formulize manages Anonymous's `block_read` on it:
  signed-out visitors see the menu if, and only if, some menu item is granted to them. When none
  is, Lyris draws no sidebar on most pages, and Anari's is just the logo and close button.
  - `syncAnonymousAccessToFormMenuBlock()` in `modules/formulize/class/applications.php` enforces
    this. It is called after every change to which groups can see menu items: `insertMenuLink()`,
    `updateMenuLink()` and `deleteMenuLinkById()`.
  - To ask which groups can see a menu item, or any menu item, use the menu links handler's
    `getGroupIds($menuId = 0, $onlyGroupIds = null)`
    (`xoops_getmodulehandler('applicationMenuLinks', 'formulize')`), or
    `groupCanSeeAnyMenuLink($groupId)` on the same handler, which is built on it. When you only
    care about some groups, pass them as `$onlyGroupIds` so the query filters them, rather than
    fetching every group and filtering in PHP.
    Menu permissions are in `formulize_menu_permissions`, not the core `group_permission` table,
    so the core permission handler's lookups don't cover them.
  - That is deliberate. Each row also carries `default_screen`, which marks the item as the
    group's start page. It is not a yes/no permission: `getDefaultScreenForUser()` picks the
    first flagged item by menu order across all of a user's groups (see [menus](menus.md)), and the flag only means
    something alongside the visibility grant on the same row. As core permissions it would pick
    up `checkRight()`'s webmaster shortcut, lose the menu-order resolution, and split into rows
    that could disagree. Site sync's `group_permission` metadata in `xoops_version.php` also
    assumes Formulize permissions are about forms.
  - Code that writes `formulize_menu_permissions` some other way should call it too.
- **The top page opens the menu itself.** `index.php` assigns `openMenuClass`
  (`site-layout__sidebar--open`) when it draws the top page, so the one page that has the Login
  block already shows it. `modules/formulize/application.php` is drawn with the menu open too
  (`theme.html`).

So don't add client-side logic that opens the menu for signed-out visitors on the assumption that
the login block is in it. On a phone, that would cover `/user.php`'s own login form and any public
page a visitor landed on, so neither theme opens its sidebar unless asked. On a wide screen, Lyris
opens its sidebar for signed-out visitors by default, beside the page rather than over it. If a
feature depends on the login block's location, test for the form in the sidebar
(`input[name="uname"]` inside `.js-flyout-menu` or `#lyris-sidebar`), as the `.js-open-login`
handlers do.

For which theme the e2e tests run on, see
[Which theme the suite covers](testing-robust-specs.md#which-theme-the-suite-covers).
