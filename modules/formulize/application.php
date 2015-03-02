<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2005 Freeform Solutions                  ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

include 'header.php';

$xoopsOption['template_main'] = 'formulize_application.html';

require(XOOPS_ROOT_PATH."/header.php");

global $xoopsDB;

$form_handler = xoops_getmodulehandler('forms', 'formulize');
$application_handler = xoops_getmodulehandler('applications', 'formulize');
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
$allowedForms = allowedForms();

if(isset($_GET['id']) AND $_GET['id'] === "all") {
    $applicationsToDraw = $application_handler->getAllApplications();
    $applicationsToDraw[] = 0; // add in forms with no app at the end of the list
} else {
    $aid = (isset($_GET['id'])) ? intval($_GET['id']) : 0 ;
    $applicationsToDraw = array($aid);
}

$allAppData = array();
foreach($applicationsToDraw as $aid) {
    if(is_object($aid)) {
        $aid = $aid->getVar('appid'); // when 'all' is requested, the array will be of objects, not ids
    }
    //checkMenuLinks($aid);

    $links;
    if($aid) {
        $links = $application_handler->getMenuLinksForApp($aid);
        $application = $application_handler->get($aid);
        $app_name = $application->getVar('name');
    } else {
        $links = $application_handler->getMenuLinksForApp(0);
        $app_name = _AM_CATGENERAL;
    }
    $formsToSend = getNavDataForForms($links);
    if(count($formsToSend)==0) {
        $noforms =  _AM_NOFORMS_AVAIL;
    } else {
        $noforms = 0;
    }
    $allAppData[] = array('app_name'=>$app_name, 'noforms'=>$noforms, 'formData'=>$formsToSend);
}

$xoopsTpl->assign("allAppData", $allAppData);

require(XOOPS_ROOT_PATH."/footer.php");

// $forms must be an array of form ids
function getNavDataForForms($links) {
    $formsToSend = array();
    $i=0;
    foreach($links as $link) {
        $suburl = XOOPS_URL."/modules/formulize/index.php?".$link->getVar("screen");
        $url = $link->getVar("url");
        if(strlen($url) > 0){
            $pos = strpos($url,"://");
            if($pos === false){
                $url = "http://".$url;
            }
            $suburl = $url;
        }
        $formsToSend[$i]['url'] = $suburl;
        $formsToSend[$i]['title'] = $link->getVar("text");
        $i++;
    }
    return $formsToSend;
}



/*This function checks if the menu link is still valid
 *added by Jian Feb 2015
 *not done yet. I left this function in case of we want to check menu links when loading the page.
 *

 *you can check ApplicationHandler->deleteMenuLinkByScreen($screen)
 * *form_screens_save.php  uses this function means to delete menu links when deleting a screen
 * *application_forms_save.php uses this function means to delete menu links when deleting a form,this is just for making sure links to form get deleted
 * *forms.php also using this function because forms.php will internally delete some screen and other stuff with out using form_screens_save.php
 * *
 
function checkMenuLinks($aid){
    $application_handler = xoops_getmodulehandler('applications', 'formulize');
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $screen_handler = xoops_getmodulehandler('screen', 'formulize');
    $appLinks = $application_handler->getMenuLinksForApp($aid);

    $menulinks=array(); 
    $index = 0;
    foreach ($appLinks as $menulink){
        $menulinks[$index] = $menulink->getVar('screen');
        if(preg_match("/^fid=.*$/",$menulinks[$index])){
            error_log("a fid");
            $fid=intval(substr($menulinks[$index],4));
            $form_object=$form_handler->get($fid);
            if($form_object==null){
                echo("fid= ".$fid." not found");
            }else {
                error_log("fid found");
            }
        }else if(preg_match("/^sid=.*$/",$menulinks[$index])){
            error_log("a sid");
            $sid=intval(substr($menulinks[$index],4));
            $screen_object=$screen_handler->get($sid);
            if($screen_object==null){
                echo("sid= ".$sid." not found");
                //$application_handler->deleteMenuLink($aid, $menuitem);  
            }else {
                error_log("sid found");
            }
        }
        $index ++;
    }
    error_log("linsScreen ".print_r($menulinks));
}
*/
