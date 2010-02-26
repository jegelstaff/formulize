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

// this file gets all the data about applications, so we can display the Settings/forms/relationships tabs for applications

include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";

// need to listen for $_GET['aid'] later so we can limit this to just the application that is requested
$aid = intval($_GET['aid']);
$appName = "All forms"; // needs to be set based on aid in future
$elements = array();
if($_GET['fid'] != "new") {
  $fid = intval($_GET['fid']);
  $form_handler = xoops_getmodulehandler('forms', 'formulize');
  $formObject = $form_handler->get($fid);
  $formName = $formObject->getVar('title');
  $singleentry = $formObject->getVar('single');
  //$screen_handler = xoops_getmodulehandler('screen', 'formulize');
} else {
  $fid = $_GET['fid'];
}

// common values should be assigned to all tabs
$common['name'] = $screenName;
$common['sid'] = $sid;

// screen settings data
$settings = array();
$settings['hello'] = "Hello Screen settings World";

// display data
$display = array();
$display['hello'] = "Hello Screen Display World";

// custom button data
$custom = array();
$custom['hello'] = "Hello Custom button World";

// conditions data
$conditions = array();
$conditions['hello'] = "Hello Conditions World";

// define tabs for screen sub-page
$adminPage['tabs'][1]['name'] = "Settings";
$adminPage['tabs'][1]['template'] = "db:admin/screen_settings.html";
$adminPage['tabs'][1]['content'] = $settings + $common;

$adminPage['tabs'][2]['name'] = "Display";
$adminPage['tabs'][2]['template'] = "db:admin/screen_display.html";
$adminPage['tabs'][2]['content'] = $display + $common;

$adminPage['tabs'][3]['name'] = "Conditions";
$adminPage['tabs'][3]['template'] = "db:admin/screen_conditions.html";
$adminPage['tabs'][3]['content'] = $conditions + $common;

$adminPage['tabs'][4]['name'] = "Custom buttons";
$adminPage['tabs'][4]['template'] = "db:admin/screen_custom.html";
$adminPage['tabs'][4]['content'] = $custom + $common;

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['url'] = "page=application&aid=$aid";
$breadcrumbtrail[2]['text'] = $appName;
$breadcrumbtrail[3]['text'] = $formName;
$breadcrumbtrail[4]['text'] = $screenName;

