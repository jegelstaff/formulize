<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2010 Freeform Solutions                  ##
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
##  URL: http://www.formulize.org                           ##
##  Project: Formulize                                                       ##
###############################################################################

// this file gets all the data about a particular page of a screen, so it can be edited

require_once "../../../mainfile.php";
icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}

include_once("admin_header.php");

include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";
include_once XOOPS_ROOT_PATH."/class/xoopsformloader.php";

// setup a smarty object that we can use for templating our own pages

global $icmsConfig;
require_once XOOPS_ROOT_PATH.'/class/template.php';
require_once XOOPS_ROOT_PATH.'/class/theme.php';
require_once XOOPS_ROOT_PATH.'/class/theme_blocks.php';
$xoopsThemeFactory = new icms_view_theme_Factory();
$xoopsThemeFactory->allowedThemes = $icmsConfig['theme_set_allowed'];
$xoopsThemeFactory->defaultTheme = $icmsConfig['theme_set'];
$xoTheme =& $xoopsThemeFactory->createInstance();
$xoopsTpl =& $xoTheme->template;


$pageIndex = intval($_GET['page']);
$sid = intval($_GET['sid']);
$screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
$screen = $screen_handler->get($sid);
if (!is_object($screen)) {
  return "Error: could not load information for the specified page";
}


// setup all the elements in this form for use in the listboxes
include_once XOOPS_ROOT_PATH . "/modules/formulize/class/forms.php";
$frid = $screen->getVar("frid");
$fid = $screen->getVar('fid');
$elementOptions = multiPageScreen_addToOptionsList($fid, $frid);
$screenOptions = multiPageScreenMakeScreenOptionsList($fid, $frid, $sid);

// get page titles
$pageTitles = $screen->getVar("pagetitles");
$elements = $screen->getVar("pages");
$conditions = $screen->getVar("conditions");

$pageTitle = $pageTitles[$pageIndex];
$pageNumber = $pageIndex+1;
$pageElements = $elements[$pageIndex];

$pit = $screen->determinePageItemType($pageElements);

$filterSettingsToSend = (is_array($conditions) AND isset($conditions[$pageIndex]) AND count($conditions[$pageIndex]) > 0) ? $conditions[$pageIndex] : "";
if (isset($filterSettingsToSend['details'])) { // if this is in the old format (pre-version 4, these conditions used a non-standard syntax), convert it!
    $newFilterSettingsToSend = array();
    $newFilterSettingsToSend[0] = $filterSettingsToSend['details']['elements'];
    $newFilterSettingsToSend[1] = $filterSettingsToSend['details']['ops'];
    $newFilterSettingsToSend[2] = $filterSettingsToSend['details']['terms'];
    $filterSettingsToSend = $newFilterSettingsToSend;
}
$pageConditions = formulize_createFilterUI($filterSettingsToSend, "pagefilter_".$pageIndex, $screen->getVar('fid'), "popupform", $frid);

// make isSaveLocked preference available to template
$content['isSaveLocked'] = sendSaveLockPrefToTemplate();

global $easiestml_lang;
define('XOOPS_LOCALE', $easiestml_lang);

$xoopsTpl->assign("content",$content);
$xoopsTpl->assign("pageTitle",$pageTitle);
$xoopsTpl->assign("pageNumber",$pageNumber);
$xoopsTpl->assign("pageIndex",$pageIndex);
$xoopsTpl->assign("pageElements",$pageElements);
$xoopsTpl->assign("pageConditions",$pageConditions);
$xoopsTpl->assign("pit", $pit);
$xoopsTpl->assign("elementOptions",$elementOptions);
$xoopsTpl->assign("screenOptions",$screenOptions);
$xoopsTpl->assign("sid",$sid);
$xoopsTpl->display("db:admin/screen_multipage_pages_settings.html");

