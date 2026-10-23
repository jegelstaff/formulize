<?php
/**
 * Which groups can see menu items, and Anonymous's access to the Form Menu block.
 *
 * Menu items are shown to the groups listed for them in formulize_menu_permissions, which is Formulize's
 * own table rather than the core permission system, because each row also says whether the item is that
 * group's start page. The block that draws the menu is a core block, though, and anonymous visitors only
 * see it if Anonymous has block_read on it. formulizeApplicationsHandler::syncAnonymousAccessToFormMenuBlock()
 * keeps the two in step - Anonymous can read the block if, and only if, at least one menu item is visible
 * to Anonymous - and every method that changes which groups see an item calls it.
 *
 * Why this exists: until 2026-10 only adding and deleting items kept them in step. Changing an existing
 * item's groups did not, so giving Anonymous an item through the admin UI left anonymous visitors without
 * the menu, and taking the last one away left them an empty one. Nothing on screen says which side is out
 * of step, so this checks every way in.
 *
 * Like saved_views_test.php, this boots the application and uses the live database, so CI runs it after
 * the Playwright suite. It builds its own menu item, in "forms with no app", inside a transaction that is
 * rolled back, and the checks on Anonymous only run when Anonymous has no menu items of its own, so they
 * have a known starting point.
 *
 * WHAT THIS DOES NOT COVER
 * - insertMenuLink()'s $force writes during a GET request. The request method is fixed when the application
 *   boots, so one run can't be both; this runs as a POST, where forced and unforced writes both go through.
 * - What anonymous visitors actually see. 02-validate/046-anonymous-menu-access.spec.js checks that in the
 *   browser.
 *
 * Usage:
 *   docker exec formulize-web-1 php /var/www/html/tests/menu_permissions_test.php
 *
 * @package Formulize
 * @subpackage tests
 */

// Command-line / CI only. A web request just 404s - this ships in the repo and must never be a live
// endpoint, and it writes to the database (inside a transaction, but still).
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$_SERVER['REQUEST_METHOD'] = 'POST'; // writes reach the database only on POST - see icms_core_Security::service(), which defines XOOPS_DB_PROXY on anything else and makes query() refuse to write
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';

$root = dirname(__DIR__);
if (!is_file($root . '/mainfile.php')) {
    fwrite(STDERR, "Cannot find mainfile.php at: $root/mainfile.php - is Formulize installed?\n");
    exit(2);
}
include $root . '/mainfile.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/applications.php';

global $xoopsDB;
$applicationsHandler = xoops_getmodulehandler('applications', 'formulize');
$menuLinksHandler = xoops_getmodulehandler('applicationMenuLinks', 'formulize');
$gpermHandler = xoops_gethandler('groupperm');
if (!$applicationsHandler OR !$menuLinksHandler OR !$gpermHandler) {
    fwrite(STDERR, "Could not load the applications, menu links or group permission handlers - the module bootstrap did not complete.\n");
    exit(2);
}

$GLOBALS['__pass'] = 0;
$GLOBALS['__fail'] = 0;

function check($label, $got, $want) {
    if ($got === $want) {
        $GLOBALS['__pass']++;
        return true;
    }
    $GLOBALS['__fail']++;
    printf("  FAIL %s\n       got:  %s\n       want: %s\n", $label, var_export($got, true), var_export($want, true));
    return false;
}

const FORM_MENU_BLOCK_ID = 24; // enforced by the override script in the installer
const TEST_LINK_TEXT = 'zz menu permissions test zz';
const TEST_LINK_URL = '/zz-menu-permissions-test';

/**
 * The "::" delimited string insertMenuLink() and updateMenuLink() take: menu id, text, screen, url, groups
 * that can see it, groups it is the start page for, note.
 */
function menuItem($menuId, array $groups, array $startPageGroups = array()) {
    return implode('::', array($menuId, TEST_LINK_TEXT, '', TEST_LINK_URL, implode(',', $groups), implode(',', $startPageGroups), ''));
}

/** The test's menu item, found through the menu links handler. */
function testMenuId() {
    foreach ($GLOBALS['menuLinksHandler']->get(0, true) as $link) {
        if ($link->getVar('link_text', 'n') === TEST_LINK_TEXT) {
            return intval($link->getVar('menu_id'));
        }
    }
    return 0;
}

function anonCanReadMenuBlock() {
    return $GLOBALS['gpermHandler']->checkRight('block_read', FORM_MENU_BLOCK_ID, XOOPS_GROUP_ANONYMOUS, 1, false);
}

$anon = intval(XOOPS_GROUP_ANONYMOUS);
$admins = intval(XOOPS_GROUP_ADMIN);
$users = intval(XOOPS_GROUP_USERS);
$anonStartedWithMenuItems = $menuLinksHandler->groupCanSeeAnyMenuLink($anon);
$anonStartedWithBlock = anonCanReadMenuBlock();

echo "\nmenu permissions: which groups see a menu item, and Anonymous's access to the menu block\n\n";

$xoopsDB->queryF("START TRANSACTION");

try {
    $applicationsHandler->insertMenuLink(0, menuItem('null', array($admins, $users)));
    $menuId = testMenuId();
    check('a new menu item can be found again', $menuId > 0, true);

    // getGroupIds(), and the filter that lets the database answer for only some groups
    $groupIds = $menuLinksHandler->getGroupIds($menuId);
    sort($groupIds);
    check('the groups that can see an item are the ones it was given', $groupIds, array($admins, $users));
    check('asking about some groups only answers for those', $menuLinksHandler->getGroupIds($menuId, array($users, 999999)), array($users));
    check('a single group can be asked about without a list', $menuLinksHandler->getGroupIds($menuId, $users), array($users));
    check('a group that cannot see the item is not in the answer', $menuLinksHandler->getGroupIds($menuId, 999999), array());
    check('an empty list of groups is an empty answer, not every group', $menuLinksHandler->getGroupIds(0, array()), array());
    check('with no item, the groups that can see any item are listed', in_array($users, $menuLinksHandler->getGroupIds()), true);
    check('a group with an item can see a menu link', $menuLinksHandler->groupCanSeeAnyMenuLink($users), true);
    check('a group with none cannot', $menuLinksHandler->groupCanSeeAnyMenuLink(999999), false);

    if ($anonStartedWithMenuItems) {
        echo "  SKIP the checks on Anonymous: this site already gives Anonymous menu items, so there is no known starting point\n";
    } else {
        check('to start with, Anonymous sees no menu items and cannot read the menu block', $anonStartedWithBlock, false);

        $applicationsHandler->updateMenuLink(0, menuItem($menuId, array($admins, $users, $anon)));
        check('giving Anonymous an existing item lets Anonymous read the menu block', anonCanReadMenuBlock(), true);

        $applicationsHandler->updateMenuLink(0, menuItem($menuId, array($admins, $users)));
        check('taking it away again takes the menu block away', anonCanReadMenuBlock(), false);

        $applicationsHandler->insertMenuLink(0, menuItem('null', array($users, $anon)));
        check('a new item for Anonymous lets Anonymous read the menu block', anonCanReadMenuBlock(), true);
        $anonMenuId = 0;
        foreach ($menuLinksHandler->get(0, true) as $link) {
            if ($link->getVar('link_text', 'n') === TEST_LINK_TEXT AND intval($link->getVar('menu_id')) !== $menuId) {
                $anonMenuId = intval($link->getVar('menu_id'));
            }
        }
        $applicationsHandler->updateMenuLink(0, menuItem($menuId, array($admins, $users)));
        check('changing an item Anonymous never had leaves the menu block alone', anonCanReadMenuBlock(), true);

        $applicationsHandler->deleteMenuLinkById($anonMenuId);
        check('deleting the only item for Anonymous takes the menu block away', anonCanReadMenuBlock(), false);

        $applicationsHandler->insertMenuLink(0, menuItem('null', array($anon)), true);
        check('a forced insert for Anonymous lets Anonymous read the menu block too', anonCanReadMenuBlock(), true);

        // Start pages. The CLI has no user, so this asks for Anonymous, which has no start page of its own here.
        $applicationsHandler->updateMenuLink(0, menuItem($menuId, array($users, $anon), array($anon)));
        list($fid, $sid, $url) = formulizeApplicationMenuLinksHandler::getDefaultScreenForUser();
        check('an item that is Anonymous\'s start page is where Anonymous starts', $url, XOOPS_URL . TEST_LINK_URL);
    }

} catch (Throwable $t) {
    $GLOBALS['__fail']++;
    printf("  FAIL the checks could not be completed\n       %s: %s @ %s:%s\n",
        get_class($t), $t->getMessage(), $t->getFile(), $t->getLine());
}

$xoopsDB->queryF("ROLLBACK");

// Checked rather than assumed: everything above wrote to real tables.
check('rollback: the menu items this test made are not in the database', testMenuId(), 0);
check('rollback: whether Anonymous sees menu items is as it was', $menuLinksHandler->groupCanSeeAnyMenuLink($anon), $anonStartedWithMenuItems);
check('rollback: Anonymous\'s access to the menu block is as it was', anonCanReadMenuBlock(), $anonStartedWithBlock);

echo "\n";
printf("RESULT: %d passed, %d failed\n", $GLOBALS['__pass'], $GLOBALS['__fail']);
exit($GLOBALS['__fail'] === 0 ? 0 : 1);
