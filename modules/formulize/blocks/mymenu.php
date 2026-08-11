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

/**
 * Build the site menu for the current user, in both forms it can be consumed in.
 *
 * Returns array($html, $data) where:
 *  - $html is the legacy HTML-string menu, used when the f7MenuTemplate preference is off
 *  - $data is the structured array consumed by the Smarty menu template (templates/blocks/menu.html)
 *    and by the mobile app, which renders it as a native drawer
 *
 * Each entry of $data looks like:
 *   array('url', 'title', 'active', 'target', 'icon', 'expanded', 'subs' => array(array('url', 'title', 'active', 'target')))
 *
 * The website sidebar block and the app bridge endpoint (modules/formulize/app/menu.php) both call
 * this, so the menu a user sees in the app can never drift from the one the website shows them --
 * including the permission filtering, which happens inside the handlers and section builders here.
 *
 * Returns array('', array()) when the user has no menu entries at all; callers decide what to
 * display in that case.
 */
function formulize_buildMenuData() {

	include_once XOOPS_ROOT_PATH . '/modules/formulize/include/functions.php';

	$application_handler = xoops_getmodulehandler('applications', 'formulize');
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$allApplications = $application_handler->getAllApplications();
	$menuTexts = array();
	$i = 0;

	foreach ($allApplications as $thisApplication) {
		$links = $thisApplication->getVar('links');
		if (count((array)$links) > 0) {
			$menuTexts[$i]['application'] = $thisApplication;
			$menuTexts[$i]['links'] = $links;
			$i++;
		}
	}
	$links = $application_handler->getMenuLinksForApp(0);
	if (count((array)$links) > 0) {
		$menuTexts[$i]['links'] = $links;
		$menuTexts[$i]['application'] = 0;
	}

	list($ugContent, $ugData) = drawUsersAndGroupsMenuSection();
	list($aiContent, $aiData) = drawAIAssistantMenuSection();

	$innerContent = "";
	$menuData = array();

	if (count((array)$menuTexts) > 0) {
		$forceOpen = count((array)$menuTexts) == 1;
		foreach ($menuTexts as $thisMenuData) {
			list($content, $data) = drawMenuSection($thisMenuData['application'], $thisMenuData['links'], $forceOpen, $form_handler);
			$innerContent .= $content;
			$menuData[] = $data;
		}
	}

	if ($ugContent !== false) {
		$innerContent .= $ugContent;
		$menuData[] = $ugData;
	}

	if ($aiContent !== false) {
		$innerContent .= $aiContent;
		$menuData[] = $aiData;
	}

	return array($innerContent, $menuData);
}

function block_formulizeMENU_show() {
	global $myts;
	$myts = MyTextSanitizer::getInstance();

	if (!defined('_AM_NOFORMS_AVAIL')) {
		include_once XOOPS_ROOT_PATH . '/modules/formulize/language/english/main.php';
	}

	$block = array();
	$block['title'] = "";

	list($innerContent, $menuData) = formulize_buildMenuData();

	if (count($menuData) > 0) {
		$block['content'] = "<table cellspacing='0' border='0'><tr><td id=\"mainmenu\">" . $innerContent . "</td></tr></table>";

		$config_handler = xoops_gethandler('config');
		$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
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
	static $topwritten = false;

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


	if (!$topwritten) {
		$block = "<a class=\"menuTop$menuActive\" href=\"$itemurl\">$name</a>";
		$topwritten = 1;
	} else {
		$block = "<a class=\"menuMain$menuActive\" href=\"$itemurl\">$name</a>";
	}

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
		$menuSubActive="";
		if(getCurrentURL() == XOOPS_URL.'/modules/formulize/index.php?'.$menulink->getVar("screen")
			OR trim(getCurrentURL(), '/') == trim($suburl, '/')
			OR getCurrentURL() == $url
			OR trim(XOOPS_URL.'/'.$formulizeCanonicalURI, '/') == trim($url, '/')
			OR (getCurrentURL() == XOOPS_URL.'/modules/formulize/'
				AND (
					$menulink->getVar("screen") == 'sid='.$defaultSid
					OR $menulink->getVar("screen") == 'fid='.$defaultFid
				))
			){
			$menuSubActive=" menuSubActive";
		}
		$text = $menulink->getVar("text");
		$data['subs'][] = array('url'=>$suburl, 'title'=>$text, 'active'=>($menuSubActive ? 1 : 0), 'target'=>$target);
		if($shouldShowSubs) { // Build legacy HTML-string output (for non-template mode)
			$block .= "<a class=\"menuSub$menuSubActive\" $target href='$suburl'>".$text."</a>";
		}
	}

	$data['expanded'] = $shouldShowSubs ? 1 : 0;

	return array($block, $data);
}
