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

		if(!defined('_AM_NOFORMS_AVAIL')) {
				include_once XOOPS_ROOT_PATH.'/modules/formulize/language/english/main.php';
		}


        $block = array();
        $groups = array();
        $block['title'] = ""; //_MB_formulizeMENU_TITLE;
        $block['content'] = "<table cellspacing='0' border='0'><tr><td id=\"mainmenu\">";

	// MODIFIED April 25/05 to handle menu categories

	include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

	// GENERATE THE ID_FORM
	$id_form = ((isset( $_GET['fid'])) AND is_numeric( $_GET['fid'])) ? intval( $_GET['fid']) : "" ;
    $id_form = ((isset($_POST['fid'])) AND is_numeric($_POST['fid'])) ? intval($_POST['fid']) : $id_form ;

    $application_handler = xoops_getmodulehandler('applications', 'formulize');
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$allApplications = $application_handler->getAllApplications();
	$menuTexts = array();
	$i = 0;

        foreach($allApplications as $thisApplication) {

        		$links = $thisApplication->getVar('links');

        		if(count((array) $links) > 0){

            			$menuTexts[$i]['application'] = $thisApplication;

            			$menuTexts[$i]['links'] = $links;

            			$i++;

            		}
 	}
	$links = $application_handler->getMenuLinksForApp(0);
	if(count((array) $links)>0) {
        $menuTexts[$i]['links'] = $links;
        $menuTexts[$i]['application'] = 0;
  }
	if(count((array) $menuTexts) == 0) { // if no menu entries were found, return nothing
				$block['content'] = _AM_NOFORMS_AVAIL;
				return $block;
  }
	$forceOpen = count((array) $menuTexts)==1 ? true : false;
    $menuData = array();
	foreach($menuTexts as $thisMenuData) {
				list($content, $data) = drawMenuSection($thisMenuData['application'], $thisMenuData['links'], $forceOpen, $form_handler);
                $block['content'] .= $content;
                $menuData[] = $data;
	}

  $block['content'] .= "</td></tr></table>";

  $module_handler = xoops_gethandler('module');
  $config_handler = xoops_gethandler('config');
  $formulizeModule = $module_handler->getByDirname("formulize");
  $formulizeConfig = $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
  if($formulizeConfig['f7MenuTemplate']) {
    $block['content'] = $menuData;
  }

  return $block;

}

function getMenuTextsForForms($forms, $form_handler) {
				$menuTexts = array();
				foreach($forms as $thisForm) {
								$thisFormObject = $form_handler->get($thisForm);
								if($menuText = $thisFormObject->getVar('menutext')) {
												$menuTexts[$thisFormObject->getVar('id_form')] = html_entity_decode($menuText, ENT_QUOTES) == "Use the form's title" ? $thisFormObject->getVar('title') : $menuText;
								}

				}
				return $menuTexts;
}

function drawMenuSection($application, $menulinks, $forceOpen, $form_handler){

	global $formulizeCanonicalURI, $xoopsUser;
	$data = array();
	if($application == 0) {
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

	$getMenuId = isset($_GET['menuid']) ? $_GET['menuid'] : null;
	$getSid = isset($_GET['sid']) ? $_GET['sid'] : null;
	$getFid = isset($_GET['fid']) ? $_GET['fid'] : null;

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

	if(
		$forceOpen
		OR (
			isset($_GET['id'])
			AND strstr(getCurrentURL(), "/modules/formulize/application.php")
			AND $aid == $_GET['id']
			)
		OR (
			strstr(getCurrentURL(), "/modules/formulize/index.php?fid=")
			AND in_array($getFid, $forms)
			)
		OR $isThisSubMenu
	) { // if we're viewing this application or a form in this application, or this is the being forced open (only application)...

		$screen_handler = xoops_getmodulehandler('screen', 'formulize');
		$gperm_handler = xoops_gethandler('groupperm');
		$mid = getFormulizeModId();
		$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
		foreach($menulinks as $menulink) {
			$sid = strstr($menulink->getVar("screen"), 'sid=') ? intval(str_replace('sid=', '', $menulink->getVar("screen"))) : 0;
			$fid = strstr($menulink->getVar("screen"), 'fid=') ? intval(str_replace('fid=', '', $menulink->getVar("screen"))) : 0;
			if($url = buildMenuLinkURL($menulink)) {
				$suburl = $url;
				$rewriteruleAddress = null;
			} else {
				if($sid) {
					$menuLinkScreenId = $sid;
				}
				if($fid) {
					$menulinkFormObject = $form_handler->get($fid);
					$singleEntry = $menulinkFormObject->getVar('single');
					$view_globalscope = $gperm_handler->checkRight("view_globalscope", $fid, $groups, $mid);
					$view_groupscope = $gperm_handler->checkRight("view_groupscope", $fid, $groups, $mid);
					if((!$singleEntry AND $xoopsUser) OR $view_globalscope OR ($view_groupscope AND $singleEntry != "group")) {
						$menuLinkScreenId = $menulinkFormObject->getVar('defaultlist');
					} else {
						$menuLinkScreenId = $menulinkFormObject->getVar('defaultform');
					}
				}
				$menuLinkScreen = $menuLinkScreenId ? $screen_handler->get($menuLinkScreenId) : null;
				$rewriteruleAddress = $menuLinkScreen ? $menuLinkScreen->getVar('rewriteruleAddress') : null;
				if($rewriteruleAddress) {
					$suburl = XOOPS_URL ."/".$rewriteruleAddress;
				} else {
					$suburl = XOOPS_URL."/modules/formulize/index.php?".$menulink->getVar("screen");
				}
			}
			$target = (!$url OR strstr($url, XOOPS_URL)) ? "" : " target='_blank' ";
			$menuSubActive="";
			if(getCurrentURL() == XOOPS_URL.'/modules/formulize/index.php?'.$menulink->getVar("screen")
				OR ($rewriteruleAddress AND trim(getCurrentURL(), '/') == trim(XOOPS_URL.'/'.$rewriteruleAddress, '/'))
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
			$block .= "<a class=\"menuSub$menuSubActive\" $target href='$suburl'>".$text."</a>";
			$data['subs'][] = array('url'=>$suburl, 'title'=>$text, 'active'=>($menuSubActive ? 1 : 0), 'target'=>$target);
		}
	}
	return array($block, $data);
}
