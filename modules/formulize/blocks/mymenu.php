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

    $application_handler = xoops_getmodulehandler('applications', 'formulize');
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$allApplications = $application_handler->getAllApplications();
	$menuTexts = array();
	$i = 0;
    
        foreach($allApplications as $thisApplication) {
        
        		$links = $thisApplication->getVar('links');
        
        		if(count($links) > 0){
            
            			$menuTexts[$i]['application'] = $thisApplication;
            
            			$menuTexts[$i]['links'] = $links;
            
            			$i++;
            
            		}
 	}
	$links = $application_handler->getMenuLinksForApp(0);
	if(count($links)>0) {
        $menuTexts[$i]['links'] = $links;
        $menuTexts[$i]['application'] = 0;
  }
	if(count($menuTexts) == 0) { // if no menu entries were found, return nothing
				$block['content'] = _AM_NOFORMS_AVAIL;
				return $block;
  }
	$forceOpen = count($menuTexts)==1 ? true : false;
	foreach($menuTexts as $thisMenuData) {
				$block['content'] .= drawMenuSection($thisMenuData['application'], $thisMenuData['links'], $forceOpen, $form_handler);
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

function drawMenuSection($application, $menulinks, $forceOpen, $form_handler){
        
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
        
        $isThisSubMenu = false;
        
		include_once XOOPS_ROOT_PATH."/modules/formulize/class/applications.php";
		list($defaultFid,$defaultSid) = formulizeApplicationMenuLinksHandler::getDefaultScreenForUser();
		
        foreach($menulinks as $menulink) {
		
            if($menulink->getVar("menu_id") == $_GET['menuid']
				OR $menulink->getVar("screen") == 'sid='.$_GET['sid']
				OR $menulink->getVar("screen") == 'fid='.$_GET['fid']
				OR (
				getCurrentURL() == XOOPS_URL.'/modules/formulize/' AND (
				$menulink->getVar("screen") == 'sid='.$defaultSid
				OR $menulink->getVar("screen") == 'fid='.$defaultFid
				))
				){
                
                $isThisSubMenu = true;
    
            }
            
        }        
		
    if($forceOpen OR (isset($_GET['id']) AND strstr(getCurrentURL(), "/modules/formulize/application.php") AND $aid == $_GET['id']) OR (strstr(getCurrentURL(), "/modules/formulize/index.php?fid=") AND in_array($_GET['fid'], $forms)) OR $isThisSubMenu ) { // if we're viewing this application or a form in this application, or this is the being forced open (only application)...
        
		foreach($menulinks as $menulink) {
			$suburl = XOOPS_URL."/modules/formulize/index.php?".$menulink->getVar("screen");
			$url = $menulink->getVar("url");
			$target = "";
			if(strlen($url) > 0){
                if(substr($url, 0, 1)=="/") {
                    $url = XOOPS_URL.$url;
                } else {
       				$pos = strpos($url,"://");
       				if($pos === false){
       					$url = "http://".$url;
       				}
                }
				$suburl = $url;
                $target = strstr($url, XOOPS_URL) ? "" : " target='_blank' ";
			}
			$block .= "<a class=\"menuSub\" $target href='$suburl'>".$menulink->getVar("text")."</a>";
		}	
	}
	return $block;
}
