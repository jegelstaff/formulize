<?php
// Copyright (c) 2004 Freeform Solutions and Marcel Widmer (for the original
// mymenu module).
// ------------------------------------------------------------------------- //
//                XOOPS - PHP Content Management System                      //
//                       <http://www.xoops.org/>                             //
// ------------------------------------------------------------------------- //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
// ------------------------------------------------------------------------- //

function block_formulizeMENU_show() {
	global $xoopsDB, $xoopsUser, $xoopsModule, $myts;
	$myts = MyTextSanitizer::getInstance();

	if (!defined('_AM_NOFORMS_AVAIL')) {
		include_once XOOPS_ROOT_PATH . '/modules/formulize/language/english/main.php';
	}

	$block = array();
	$block['title'] = "";

	include_once XOOPS_ROOT_PATH . '/modules/formulize/include/functions.php';

	$id_form = ((isset($_GET['fid'])) AND is_numeric($_GET['fid'])) ? intval($_GET['fid']) : "";
	$id_form = ((isset($_POST['fid'])) AND is_numeric($_POST['fid'])) ? intval($_POST['fid']) : $id_form;

	$application_handler = xoops_getmodulehandler('applications', 'formulize');
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	// the top level of the menu, in order: the applications, "Forms with no app", and the links beside them.
	// See ai-context/menus.md.
	$menuTexts = array();
	foreach ($application_handler->getTopLevelMenu() as $menuItem) {
		if ($menuItem['type'] == 'link') {
			$menuTexts[] = array('link' => $menuItem['link']);
		} elseif (count((array)$menuItem['links']) > 0) {
			$menuTexts[] = array('application' => ($menuItem['type'] == 'noapp' ? 0 : $menuItem['application']), 'links' => $menuItem['links']);
		}
	}

	list($ugContent, $ugData) = drawUsersAndGroupsMenuSection();
	list($aiContent, $aiData) = drawAIAssistantMenuSection();
	list($installContent, $installData) = formulize_pwaInstallMenuSection();

	$hasMenuEntries = count((array)$menuTexts) > 0;
	$hasUgSection = ($ugContent !== false);
	$hasAiSection = ($aiContent !== false);

	if ($hasMenuEntries || $hasUgSection || $hasAiSection) {
		$innerContent = "";
		$menuData = array();

		if ($hasMenuEntries) {
			// a menu with one application in it shows its links already open
			$applicationSections = count(array_filter($menuTexts, function ($thisMenuData) { return isset($thisMenuData['links']); }));
			$forceOpen = $applicationSections == 1;
			foreach ($menuTexts as $thisMenuData) {
				if (isset($thisMenuData['link'])) {
					list($content, $data) = drawTopLevelMenuLink($thisMenuData['link']);
					if ($content === false) {
						continue;
					}
				} else {
					list($content, $data) = drawMenuSection($thisMenuData['application'], $thisMenuData['links'], $forceOpen, $form_handler);
				}
				$innerContent .= $content;
				$menuData[] = $data;
			}
		}

		if ($hasUgSection) {
			$innerContent .= $ugContent;
			$menuData[] = $ugData;
		}

		if ($hasAiSection) {
			$innerContent .= $aiContent;
			$menuData[] = $aiData;
		}

		// last, but never a reason to show the menu on its own (excluded from If above)
		if ($installContent !== false) {
			$innerContent .= $installContent;
			$menuData[] = $installData;
		}

		$block['content'] = "<table cellspacing='0' border='0'><tr><td id=\"mainmenu\">" . $innerContent . "</td></tr></table>";

		$module_handler = xoops_gethandler('module');
		$config_handler = xoops_gethandler('config');
		$formulizeModule = $module_handler->getByDirname("formulize");
		$formulizeConfig = $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
		if ($formulizeConfig['f7MenuTemplate']) {
			$block['content'] = $menuData;
		}
	} else {
		$block['content'] = _AM_NOFORMS_AVAIL;
	}

	return $block;
}

function drawMenuSection($application, $menulinks, $forceOpen, $form_handler){

	global $formulizeCanonicalURI, $xoopsUser;
	$data = array();
	if(!is_object($application)) {
		$aid = 0;
		$name = _AM_CATGENERAL;
		$forms = $form_handler->getFormsByApplication(0,true); // true forces ids, not objects, to be returned
	} else {
		$aid = intval($application->getVar('appid'));
		$name = printSmart($application->getVar('name'), 200);
		$forms = $application->getVar('forms');
	}

	$itemurl = XOOPS_URL."/modules/formulize/application.php?id=$aid";

	$getMenuId = isset($_GET['menuid']) ? $_GET['menuid'] : null;
	$getSid = isset($_GET['sid']) ? $_GET['sid'] : null;
	$getFid = isset($_GET['fid']) ? $_GET['fid'] : null;

	$menuActive = '';
	if($forceOpen
		OR (
			isset($_GET['id'])
			AND strstr(getCurrentURL(), "/modules/formulize/application.php")
			AND $aid == $_GET['id']
			)
		OR (
			strstr(getCurrentURL(), "/modules/formulize/index.php?fid=")
			AND in_array($getFid, $forms)
			)
		){
			$menuActive=' menuActive';
	}


	$block = "<a class=\"".formulize_menuItemClass()."$menuActive\" href=\"$itemurl\">$name</a>";

	$data = array('url'=>$itemurl, 'title'=>$name, 'active'=>($menuActive ? 1 : 0), 'target'=>'', 'icon'=>'');

	$isThisSubMenu = false;

	include_once XOOPS_ROOT_PATH."/modules/formulize/class/applications.php";
	list($defaultFid,$defaultSid,$defaultURL) = formulizeApplicationMenuLinksHandler::getDefaultScreenForUser();

	foreach($menulinks as $menulink) {
		$url = buildMenuLinkURL($menulink);
		if($menulink->getVar("menu_id") == $getMenuId
			OR $menulink->getVar("screen") == 'sid='.$getSid
			OR $menulink->getVar("screen") == 'fid='.$getFid
			OR getCurrentURL() == $url
			OR trim(XOOPS_URL.'/'.$formulizeCanonicalURI, '/') == trim($url, '/')
			OR (
				getCurrentURL() == XOOPS_URL.'/modules/formulize/'
				AND (
					$menulink->getVar("screen") == 'sid='.$defaultSid
					OR $menulink->getVar("screen") == 'fid='.$defaultFid
			))
			OR (
				substr($menulink->getVar("screen"), 0, 4) == 'fid='
				AND $getSid == determineScreenForUserFromFid(substr($menulink->getVar("screen"), 4))
			)){
				$isThisSubMenu = true;
		}
	}

	// Determine if this section should start expanded (legacy behavior, used for initial state)
	$shouldShowSubs = ($forceOpen
		OR (
			isset($_GET['id'])
			AND strstr(getCurrentURL(), "/modules/formulize/application.php")
			AND $aid == $_GET['id']
			)
		OR (
			strstr(getCurrentURL(), "/modules/formulize/index.php?fid=")
			AND in_array($getFid, $forms)
			)
		OR $isThisSubMenu);


	foreach($menulinks as $menulink) {
		$url = buildMenuLinkURL($menulink);
		$suburl = resolveMenuLinkURL($menulink);
		if($suburl === false) { // link points to a form that no longer exists - omit it from the menu
			continue;
		}
		$target = (!$url OR strstr($url, XOOPS_URL)) ? "" : " target='_blank' ";
		$menuSubActive = formulize_menuLinkIsActive($menulink, $url, $suburl, $defaultFid, $defaultSid) ? " menuSubActive" : "";
		$text = $menulink->getVar("text");
		$data['subs'][] = array('url'=>$suburl, 'title'=>$text, 'active'=>($menuSubActive ? 1 : 0), 'target'=>$target);
		if($shouldShowSubs) { // Build legacy HTML-string output (for non-template mode)
			$block .= "<a class=\"menuSub$menuSubActive\" $target href='$suburl'>".$text."</a>";
		}
	}

	$data['expanded'] = $shouldShowSubs ? 1 : 0;

	return array($block, $data);
}

/**
 * The legacy menu's class for the next item at the top level: menuTop for the first one drawn, menuMain for the rest.
 * @return string The class
 */
function formulize_menuItemClass() {
	static $topWritten = false;
	if (!$topWritten) {
		$topWritten = true;
		return 'menuTop';
	}
	return 'menuMain';
}

/**
 * Whether a menu link goes to the page being shown, or to the user's start page when that is what is being shown.
 * @param object $menulink A formulizeApplicationMenuLink
 * @param string $url The link's URL, from buildMenuLinkURL()
 * @param string $suburl The link's URL, from resolveMenuLinkURL()
 * @param int $defaultFid The form of the user's start page, from getDefaultScreenForUser()
 * @param int $defaultSid The screen of the user's start page, from getDefaultScreenForUser()
 * @return bool True if it is the link to the page being shown
 */
function formulize_menuLinkIsActive($menulink, $url, $suburl, $defaultFid, $defaultSid) {
	global $formulizeCanonicalURI;
	return (getCurrentURL() == XOOPS_URL.'/modules/formulize/index.php?'.$menulink->getVar("screen")
		OR trim(getCurrentURL(), '/') == trim($suburl, '/')
		OR getCurrentURL() == $url
		OR trim(XOOPS_URL.'/'.$formulizeCanonicalURI, '/') == trim($url, '/')
		OR (getCurrentURL() == XOOPS_URL.'/modules/formulize/'
			AND (
				$menulink->getVar("screen") == 'sid='.$defaultSid
				OR $menulink->getVar("screen") == 'fid='.$defaultFid
			)));
}

/**
 * Draw a link at the top level of the menu, beside the applications. See ai-context/menus.md.
 * @param object $menulink A formulizeApplicationMenuLink whose appid is FORMULIZE_MENU_TOP_LEVEL
 * @return array The legacy menu's HTML and the data for the menu template (with no subs, so it is drawn as a plain
 * link), or false and false when the link goes to a form that no longer exists
 */
function drawTopLevelMenuLink($menulink) {
	include_once XOOPS_ROOT_PATH."/modules/formulize/class/applications.php";
	$url = buildMenuLinkURL($menulink);
	$suburl = resolveMenuLinkURL($menulink);
	if ($suburl === false) {
		return array(false, false);
	}
	list($defaultFid, $defaultSid) = formulizeApplicationMenuLinksHandler::getDefaultScreenForUser();
	$active = formulize_menuLinkIsActive($menulink, $url, $suburl, $defaultFid, $defaultSid);
	$newWindow = ($url AND !strstr($url, XOOPS_URL));
	$text = $menulink->getVar("text");
	$block = "<a class=\"".formulize_menuItemClass().($active ? " menuActive" : "")."\"".($newWindow ? " target='_blank'" : "")." href='".htmlspecialchars($suburl)."'>".$text."</a>";
	$data = array('url' => $suburl, 'title' => $text, 'active' => ($active ? 1 : 0), 'target' => ($newWindow ? '_blank' : ''), 'icon' => '');
	return array($block, $data);
}
