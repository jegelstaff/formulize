<?php
/**
 * The Menu tab of the admin UI: the top level of the menu, in order. The applications, "Forms with no app" and the
 * links that are beside the applications rather than in one, which can be put in order, and the top-level links,
 * which can be added, changed and deleted here. The links in an application are changed on its Menu Entries tab.
 * Saved by admin/save/site_menu_save.php. See ai-context/menus.md.
 *
 * @package Formulize
 */

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}

$application_handler = xoops_getmodulehandler('applications', 'formulize');

$targets = $application_handler->getMenuLinkTargetOptions();
$groupOptions = $application_handler->getMenuLinkGroupOptions();
$groupNames = array();
foreach ($groupOptions as $group) {
	$groupNames[$group['id']] = $group['name'];
}

/**
 * The groups, each marked with whether it is among the given groups that can see a link, and those it lands on.
 * @param array $groupOptions from getMenuLinkGroupOptions()
 * @param string $canSee The menu link's 'permissions': group ids, separated by commas
 * @param string $landsOn The menu link's 'default_screen': group ids, separated by commas
 * @return array The groups, each with 'canSee' and 'landsOn'
 */
function formulize_siteMenuGroupChoices($groupOptions, $canSee = '', $landsOn = '') {
	$canSee = array_map('intval', array_filter(explode(',', (string) $canSee)));
	$landsOn = array_map('intval', array_filter(explode(',', (string) $landsOn)));
	foreach ($groupOptions as $index => $group) {
		$groupOptions[$index]['canSee'] = in_array($group['id'], $canSee);
		$groupOptions[$index]['landsOn'] = (in_array($group['id'], $landsOn) && in_array($group['id'], $canSee));
	}
	return $groupOptions;
}

/**
 * Where a menu link goes, in words, for the read-only list of an application's links.
 * @param object $menuLink A formulizeApplicationMenuLink
 * @param array $targets from getMenuLinkTargetOptions()
 * @return string Escaped for HTML
 */
function formulize_siteMenuTargetLabel($menuLink, $targets) {
	$screen = $menuLink->getVar('screen', 'n');
	if ($screen AND $screen != 'url' AND isset($targets[$screen])) {
		return trim(str_replace('&nbsp;', '', $targets[$screen]));
	}
	return htmlspecialchars($menuLink->getExpandedUrl());
}

/**
 * The groups that can see a menu link, in words.
 * @param object $menuLink A formulizeApplicationMenuLink
 * @param array $groupNames Group names, escaped for HTML, keyed by group id
 * @return string Escaped for HTML
 */
function formulize_siteMenuSeenBy($menuLink, $groupNames) {
	$names = array();
	foreach (array_filter(explode(',', (string) $menuLink->getVar('permissions', 'n'))) as $groupId) {
		if (isset($groupNames[intval($groupId)])) {
			$names[] = $groupNames[intval($groupId)];
		}
	}
	return $names ? sprintf(_AM_SITEMENU_SEEN_BY, implode(', ', $names)) : _AM_SITEMENU_SEEN_BY_NOBODY;
}

/**
 * How many links, in words.
 * @param int $count
 * @return string
 */
function formulize_siteMenuLinkCount($count) {
	return $count == 1 ? _AM_SITEMENU_LINK_COUNT_ONE : sprintf(_AM_SITEMENU_LINK_COUNT, $count);
}

$items = array();
foreach ($application_handler->getTopLevelMenu(true) as $menuItem) {
	if ($menuItem['type'] == 'link') {
		$menuLink = $menuItem['link'];
		$screen = $menuLink->getVar('screen', 'n');
		$url = $menuLink->getVar('url', 'n');
		$target = ($screen AND $screen != 'url') ? $screen : ($url ? 'url' : ''); // a link to a URL has 'url', or nothing, as its screen
		$items[] = array(
			'type' => 'link',
			'key' => 'link-' . $menuItem['id'],
			'menu_id' => $menuItem['id'],
			'title' => $menuLink->getVar('text'),
			'moveLabel' => sprintf(_AM_SITEMENU_MOVE, $menuLink->getVar('text')),
			'meta' => _AM_SITEMENU_KIND_LINK,
			'link_text' => $menuLink->getVar('link_text', 'e'),
			'target' => $target,
			'url' => $url ? $menuLink->getVar('url', 'e') : 'https://',
			'note' => $menuLink->getVar('note', 'e'),
			'goesTo' => sprintf(_AM_SITEMENU_GOES_TO, formulize_siteMenuTargetLabel($menuLink, $targets)),
			'groups' => formulize_siteMenuGroupChoices($groupOptions, $menuLink->getVar('permissions', 'n'), $menuLink->getVar('default_screen', 'n')),
		);
		continue;
	}
	$links = array();
	foreach ($menuItem['links'] as $menuLink) {
		$links[] = array(
			'text' => $menuLink->getVar('text'),
			'goesTo' => sprintf(_AM_SITEMENU_GOES_TO, formulize_siteMenuTargetLabel($menuLink, $targets)),
			'seenBy' => formulize_siteMenuSeenBy($menuLink, $groupNames),
		);
	}
	if ($menuItem['type'] == 'noapp') {
		$title = _AM_CATGENERAL;
		$meta = formulize_siteMenuLinkCount(count($links));
		$manageText = _AM_SITEMENU_MANAGE_NOAPP;
	} else {
		$title = $menuItem['application']->getVar('name');
		$meta = count($links) ? _AM_SITEMENU_KIND_APP . ' · ' . formulize_siteMenuLinkCount(count($links)) : _AM_SITEMENU_NO_LINKS;
		$manageText = _AM_SITEMENU_MANAGE;
	}
	$items[] = array(
		'type' => $menuItem['type'],
		'key' => 'application-' . $menuItem['id'],
		'title' => $title,
		'moveLabel' => sprintf(_AM_SITEMENU_MOVE, $title),
		'meta' => $meta,
		'links' => $links,
		'manageUrl' => XOOPS_URL . '/modules/formulize/admin/ui.php?page=application&aid=' . intval($menuItem['id']) . '&tab=menu%20entries',
		'manageText' => $manageText,
		'description' => $menuItem['type'] == 'noapp' ? _AM_APP_FORMWITHNOAPP : '',
	);
}

$adminPage['sitemenu'] = array(
	'items' => $items,
	'targets' => $targets,
	'newLink' => array('link_text' => '', 'target' => '', 'url' => 'https://', 'note' => ''),
	'newLinkGroups' => formulize_siteMenuGroupChoices($groupOptions),
	'landingHelp' => sprintf(_AM_SITEMENU_FIELD_LANDING_HELP, htmlspecialchars(XOOPS_URL . '/modules/system/admin.php?fct=preferences&op=show&confcat_id=1')),
	'uiStylesheet' => XOOPS_URL . formulize_uiStylesheetPath(),
	'uiScript' => XOOPS_URL . formulize_uiScriptPath(),
	'textScript' => XOOPS_URL . formulize_textScriptPath(),
	'movedText' => htmlspecialchars(_AM_SITEMENU_MOVED),
	'scriptText' => json_encode(formulize_scriptText('_AM_SITEMENU_JS_'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
);
$adminPage['home_tabs'] = getHomeTabs('site-menu');
$adminPage['pagetitle'] = _AM_SITEMENU_TAB;
$adminPage['needsave'] = true;
$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = _AM_SITEMENU_TAB;
