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
##  URL: http://www.freeformsolutions.ca/formulize                           ##
##  Project: Formulize                                                       ##
###############################################################################

// this file gets all the data about a particular page of a screen, so it can be edited

require_once "../../../mainfile.php";

include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";
include_once XOOPS_ROOT_PATH."/class/xoopsformloader.php";

// setup a smarty object that we can use for templating our own pages

require_once XOOPS_ROOT_PATH.'/class/template.php';
require_once XOOPS_ROOT_PATH.'/class/theme.php';
require_once XOOPS_ROOT_PATH.'/class/theme_blocks.php';
$xoopsThemeFactory = new xos_opal_ThemeFactory();
$xoTheme =& $xoopsThemeFactory->createInstance();
$xoopsTpl =& $xoTheme->template;

$pageIndex = intval($_GET['page']);
$sid = intval($_GET['sid']);
$screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
$screen = $screen_handler->get($sid);
if(!is_object($screen)) {
  return "Error: could not load information for the specified page";
}


// setup all the elements in this form for use in the listboxes
include_once XOOPS_ROOT_PATH . "/modules/formulize/class/forms.php";
$fid = $screen->getVar('fid');
$options = getElementCaptions($fid);

// add in elements from other forms in the framework, by looping through each link in the framework and checking if it is a display as one, one-to-one link
// added March 20 2008, by jwe
$frid = $screen->getVar("frid");
if($frid) {
  $framework_handler =& xoops_getModuleHandler('frameworks', 'formulize');
  $frameworkObject = $framework_handler->get($frid);
  
  foreach($frameworkObject->getVar("links") as $thisLinkObject) {
    if($thisLinkObject->getVar("unifiedDisplay") AND $thisLinkObject->getVar("relationship") == 1) {
      $thisFid = $thisLinkObject->getVar("form1") == $fid ? $thisLinkObject->getVar("form2") : $thisLinkObject->getVar("form1");
      $options = getElementCaptions($thisFid, $options);
    }
  }
}

// get page titles
$pageTitles = $screen->getVar("pagetitles");
$elements = $screen->getVar("pages");
$conditions = $screen->getVar("conditions");

$pageTitle = $pageTitles[$pageIndex];
$pageNumber = $pageIndex+1;
$pageElements = $elements[$pageIndex];
$filterSettingsToSend = count($conditions[$pageIndex] > 0) ? $conditions[$pageIndex] : "";
  if(isset($filterSettingsToSend['details'])) { // if this is in the old format (pre-version 4, these conditions used a non-standard syntax), convert it!
    $newFilterSettingsToSend = array();
    $newFilterSettingsToSend[0] = $filterSettingsToSend['details']['elements'];
    $newFilterSettingsToSend[1] = $filterSettingsToSend['details']['ops'];
    $newFilterSettingsToSend[2] = $filterSettingsToSend['details']['terms'];
    $filterSettingsToSend = $newFilterSettingsToSend;      
  }
$pageConditions = formulize_createFilterUI($filterSettingsToSend, "pagefilter_".$pageIndex, $screen->getVar('fid'), "popupform");

$xoopsTpl->assign("pageTitle",$pageTitle);
$xoopsTpl->assign("pageNumber",$pageNumber);
$xoopsTpl->assign("pageIndex",$pageIndex);
$xoopsTpl->assign("pageElements",$pageElements);
$xoopsTpl->assign("pageConditions",$pageConditions);
$xoopsTpl->assign("options",$options);
$xoopsTpl->assign("sid",$sid);
$xoopsTpl->display("db:admin/screen_multipage_pages_settings.html");
