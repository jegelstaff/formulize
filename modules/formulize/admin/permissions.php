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

include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";

// Listen for $_GET['aid'] so we can limit this to just the application that is requested.
$aid = intval($_GET['aid']);
$application_handler = xoops_getmodulehandler('applications','formulize');
// Get a list of all applications
$allApps = $application_handler->getAllApplications();

if(0 == $aid = intval($_GET['aid'])) {
    $appName = _AM_APP_FORMWITHNOAPP;
} else {
    $framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
    $appObject = $application_handler->get($aid);
    $appName = $appObject->getVar('name');
}

// Get a list of all groups.
$member_handler = xoops_gethandler('member');
$allGroups = $member_handler->getGroups();
$groups = array();
if(!isset($selectedGroups)) {
    $selectedGroups = isset($_POST['groups']) ? $_POST['groups'] : array();  
}
$orderGroups = isset($_POST['order']) ? $_POST['order'] : "creation";
foreach($allGroups as $thisGroup) {
    $groups[$thisGroup->getVar('name')]['id'] = $thisGroup->getVar('groupid');
    $groups[$thisGroup->getVar('name')]['name'] = $thisGroup->getVar('name');
    $groups[$thisGroup->getVar('name')]['selected'] = in_array($thisGroup->getVar('groupid'), $selectedGroups) ? " selected" : "";
}
if($orderGroups == "alpha") {  
    ksort($groups);
}

// Common values are assigned to all tabs.
$common['aid'] = $aid;

$adminPage['tabs'][1]['name'] = "Multiple Form Permissions";
$adminPage['tabs'][1]['template'] = "db:admin/multiple_permissions.html";
$adminPage['tabs'][1]['content'] = $common;
$adminPage['tabs'][$i]['content']['groups'] = $groups;
$adminPage['needsave'] = true;

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['url'] = "page=application&aid=$aid&tab=forms";
$breadcrumbtrail[2]['text'] = $appName;
