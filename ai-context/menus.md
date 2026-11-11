# The menu: applications, their links, and the top level

The menu is built from menu links (`formulize_menu_links`), each shown to the groups in `formulize_menu_permissions`. Which page a link goes to is `screen` (`fid=N` or `sid=N`) or `url`. The code is in `modules/formulize/class/applications.php`. For who can see the menu block itself, and why menu permissions aren't core permissions, see [Signed-out visitors](themes-anari-and-lyris.md#signed-out-visitors-the-login-block-and-the-open-menu).

## Where a link is: its appid

| `appid` | Where the link is |
|---|---|
| An application's id | In that application. Its `rank` is its place in the application. |
| `0` | In "Forms with no app", the catch-all that has no row in `formulize_applications`. The menu calls it General Forms (`_AM_CATGENERAL`). |
| `-1` (`FORMULIZE_MENU_TOP_LEVEL`) | At the top level of the menu, beside the applications, in no application. Its `rank` isn't used. |

`appid 0` is a real container: the code that moves a form's links between applications (`relocateMenuLinksForForm()`, `moveMenuLink()`, `copyMenuLink()`) accepts it, and treats negative ids as invalid. So top-level links never move when a form changes application.

## The order of the top level

The top level of the menu is the applications, "Forms with no app" and the top-level links, in one order. `formulize_menu_order` is the only record of it: rows of `item_type` (`application` or `link`), `item_id` (the `appid`, 0 for "Forms with no app", or the `menu_id`) and `position`.

**Anything the table doesn't list comes after what it does:** applications by name, then top-level links by `menu_id`, then "Forms with no app". So an empty table is the order the menu always had, a new application or top-level link appears at the end without anything being written, and a site never needs the table filled in. Rows for things that have been deleted are ignored, and deleting an application or a link removes its row anyway.

- **`getTopLevelMenu($all)` is the one place that works out the order.** The menu block (`blocks/mymenu.php`), `application.php?id=all`, `app_list.php`, the admin Menu tab and the MCP tools all use it. Don't sort applications yourself, and don't use `getAllApplications()` for the menu: it sorts by name, which is right for the admin lists that use it.
- **`saveTopLevelMenuOrder()` refuses a list that isn't the whole top level,** every application and top-level link once each, and "Forms with no app" while it has links. The Menu tab and MCP's `change_menu_order` both go through it.
- "Forms with no app" is only in the menu, and the Menu tab, when it has links. It always has a position, so it doesn't jump when its first link is added.

## Landing pages follow the menu

`getDefaultScreenForUser()` takes, of the links the user's groups land on, the first in the menu: by the position of its application (or its own position, for a top-level link), then by its `rank` in the application, then by `menu_id`. Before the top level had an order this used `rank` alone, which is only meaningful within one application.

## Where it's managed

- The **Menu tab** of the admin UI (`admin/site_menu.php`, `templates/admin/site_menu.html`, saved by `admin/save/site_menu_save.php`) puts the top level in order, and adds, changes and deletes top-level links. Its slug is `site-menu`, since `admin/menu.php` is the admin's flyout menu. It is built with Formulize UI, including the `fz-accordion` component, which is how its items are dragged into order.
- An application's **Menu Entries tab** (`admin/application.php`, saved by `admin/save/application_menu_entries_save.php`) manages the links in that application, and their order.
- **MCP:** `create_menu_item` takes `top_level`, `change_menu_order` orders the top level, and `list_menu_items` reports the applications in menu order with the top-level items beside them.
