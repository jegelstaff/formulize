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
		    $myts =& MyTextSanitizer::getInstance();

        $block = array();
        $groups = array();
        $block['title'] = ""; //_MB_formulizeMENU_TITLE;
        $block['content'] = "<table cellspacing='0' border='0'><tr><td id=\"mainmenu\">";

	// MODIFIED April 25/05 to handle menu categories

	include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

	// GENERATE THE ID_FORM
	$id_form = ((isset( $_GET['fid'])) AND is_numeric( $_GET['fid'])) ? intval( $_GET['fid']) : "" ;
  $id_form = ((isset($_POST['fid'])) AND is_numeric($_POST['fid'])) ? intval($_POST['fid']) : $id_form ;

	$allowedForms = allowedForms();
  $application_handler = xoops_getmodulehandler('applications', 'formulize');
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$allApplications = $application_handler->getAllApplications();
	$menuTexts = array();
	$i = 0;
  foreach($allApplications as $thisApplication) {
				$menuEntriesToShow = array_intersect($thisApplication->getVar('forms'), $allowedForms);
				$menuTextsFound = getMenuTextsForForms($menuEntriesToShow, $form_handler);
				if(count($menuTextsFound)>0) {
								$menuTexts[$i]['texts'] = $menuTextsFound;
								$menuTexts[$i]['application'] = $thisApplication;
				}
				$i++;
	}
	$formsWithNoApplication = $form_handler->getFormsByApplication(0,true); // true forces ids not objects to be returned
	$menuEntriesToShow = array_intersect($formsWithNoApplication, $allowedForms);
	$menuTextsFound = getMenuTextsForForms($menuEntriesToShow, $form_handler);
	if(count($menuTextsFound)>0) {
				$menuTexts[$i]['texts'] = $menuTextsFound;
				$menuTexts[$i]['application'] = 0;
  }
	if(count($menuTexts) == 0) { // if no menu entries were found, return nothing
				$block['content'] = "";
				return $block;
  }
	$forceOpen = count($menuTexts)==1 ? true : false;
	foreach($menuTexts as $thisMenuData) {
				$block['content'] .= drawMenuSection($thisMenuData['application'], $thisMenuData['texts'], $forceOpen, $form_handler);
	}
	
  $block['content'] .= "</td></tr></table>";

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

function drawMenuSection($application, $menuTexts, $forceOpen, $form_handler) {
				
				
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
				if (!$topwritten) {
								$block = "<a class=\"menuTop\" href=\"$itemurl\">$name</a>";
								$topwritten = 1;
				} else {
             		$block = "<a class=\"menuMain\" href=\"$itemurl\">$name</a>";
				}
				if($forceOpen OR (isset($_GET['id']) AND strstr(getCurrentURL(), "/modules/formulize/application.php") AND $aid == $_GET['id']) OR (strstr(getCurrentURL(), "/modules/formulize/index.php?fid=") AND in_array($_GET['fid'], $forms))) { // if we're viewing this application or a form in this application, or this is the being forced open (only application)...
								foreach($menuTexts as $fid=>$text) {
												$suburl = XOOPS_URL."/modules/formulize/index.php?fid=$fid";
												$block .= "<a class=\"menuSub\" href='$suburl'>$text</a>";
								}
			  }
				return $block;
}
