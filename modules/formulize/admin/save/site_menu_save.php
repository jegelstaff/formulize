<?php
/**
 * Saves the Menu tab of the admin UI (admin/site_menu.php): the links at the top level of the menu, added, changed
 * and deleted, and the order of the top level. save.php has already checked that the user is a Formulize module
 * admin and that saving isn't locked. See ai-context/menus.md.
 *
 * @package Formulize
 */

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}

// if we aren't coming from what appears to be save.php, then return nothing
if (!isset($processedValues)) {
	return;
}

$application_handler = xoops_getmodulehandler('applications', 'formulize');
$menuLinkHandler = xoops_getmodulehandler('applicationMenuLinks', 'formulize');
$topLevelLinks = $menuLinkHandler->get(FORMULIZE_MENU_TOP_LEVEL, true);
$reload = false;
$messages = array();

// every group whose menu permissions this save touches, before and after, so template groups among them can pass
// their settings on to their entry groups afterwards, as the Menu Entries tab of an application does
$touchedMenuGroupIds = array();
$groupIdsIn = function ($groupIdString) {
	return array_filter(array_map('intval', explode(',', (string) $groupIdString)));
};

// the order first, since it lists what was on the page: a link added in this save isn't in it, and goes at the end
if (isset($_POST['menuorder']) AND $_POST['menuorder'] !== '') {
	$order = array();
	foreach (explode(',', (string) $_POST['menuorder']) as $key) {
		if (preg_match('/^(application|link)-(\d+)$/', $key, $matches)) {
			$order[] = array($matches[1], intval($matches[2]));
		} else {
			$order = false;
			break;
		}
	}
	if ($order === false OR !$application_handler->saveTopLevelMenuOrder($order)) {
		// something was added to or taken from the menu since the page was loaded, perhaps in another tab, so the
		// order on the page isn't the whole menu any more
		$messages[] = _AM_SITEMENU_ORDER_NOT_SAVED;
		$reload = true;
	}
}

$menuItems = (isset($_POST['menu_items']) AND is_array($_POST['menu_items'])) ? $_POST['menu_items'] : array();
foreach ($menuItems as $menuItem) {
	$linkValues = explode('::', (string) $menuItem);
	// 0 menu id ("null" for a new link), 1 text, 2 screen, 3 url, 4 groups that can see it, 5 groups that land on it, 6 note
	if (count($linkValues) != 7) {
		continue;
	}
	$target = $linkValues[2];
	if ($target != 'url' AND !preg_match('/^(fid|sid)=\d+$/', $target)) {
		continue; // the page doesn't send a link without somewhere to go
	}
	if ($target != 'url') {
		$linkValues[3] = '';
	}
	$linkValues[4] = implode(',', $groupIdsIn($linkValues[4])) ?: 'null';
	$linkValues[5] = implode(',', $groupIdsIn($linkValues[5])) ?: 'null';
	$touchedMenuGroupIds = array_merge($touchedMenuGroupIds, $groupIdsIn($linkValues[4]), $groupIdsIn($linkValues[5]));
	if ($linkValues[0] === 'null') {
		$application_handler->insertMenuLink(FORMULIZE_MENU_TOP_LEVEL, implode('::', $linkValues));
		$reload = true;
	} else {
		$menuId = intval($linkValues[0]);
		// the link was deleted since the page was loaded, so leave it alone and show the menu as it is now
		if (!isset($topLevelLinks[$menuId])) {
			$reload = true;
			continue;
		}
		$linkValues[0] = $menuId;
		$touchedMenuGroupIds = array_merge($touchedMenuGroupIds, $groupIdsIn($topLevelLinks[$menuId]->getVar('permissions', 'n')), $groupIdsIn($topLevelLinks[$menuId]->getVar('default_screen', 'n')));
		$application_handler->updateMenuLink(FORMULIZE_MENU_TOP_LEVEL, implode('::', $linkValues));
		if ($topLevelLinks[$menuId]->getVar('link_text', 'n') != $linkValues[1]) {
			$reload = true; // its name on the page changes
		}
	}
}

if (isset($_POST['deletemenuitem']) AND $_POST['deletemenuitem']) {
	$menuId = intval($_POST['deletemenuitem']);
	// only a link at the top level, since that's all this page deletes
	if (isset($topLevelLinks[$menuId])) {
		$touchedMenuGroupIds = array_merge($touchedMenuGroupIds, $groupIdsIn($topLevelLinks[$menuId]->getVar('permissions', 'n')), $groupIdsIn($topLevelLinks[$menuId]->getVar('default_screen', 'n')));
		$application_handler->deleteMenuLinkById($menuId);
	}
	$reload = true;
}

if (count($touchedMenuGroupIds) > 0) {
	formulizeHandler::propagateTemplateGroupPermissions(array_unique($touchedMenuGroupIds));
}

if ($reload) {
	$script = '';
	foreach ($messages as $message) {
		$script .= 'alert(' . json_encode($message) . ');';
	}
	print "/* eval */ " . $script . "reloadWithScrollPosition();";
}
