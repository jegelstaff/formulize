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

// Use fid 1 *temporary*
$fid = 1;

// Get all the permissions for the selected groups for this form
$gperm_handler =& xoops_gethandler('groupperm');
$formulize_permHandler = new formulizePermHandler($fid);
$formObject = $form_handler->get($fid);
$filterSettings = $formObject->getVar('filterSettings');
$groupperms = array();
$groupfilters = array();
$i = 0;
foreach($selectedGroups as $thisGroup) {
	// Get all the permissions this group has on this form.
  	$criteria = new CriteriaCompo(new Criteria('gperm_groupid', $thisGroup));
  	$criteria->add(new Criteria('gperm_itemid', $fid));
  	$criteria->add(new Criteria('gperm_modid', getFormulizeModId()));
  	$perms = $gperm_handler->getObjects($criteria, true);
    
    $groupObject = $member_handler->getGroup($thisGroup);
    $groupperms[$i]['name'] = $groupObject->getVar('name');
    $groupperms[$i]['id'] = $groupObject->getVar('groupid');
  	foreach($perms as $perm) {
        $groupperms[$i][$perm->getVar('gperm_name')] = " checked";
  	}
    // Group-specific scope.
    $scopeGroups = $formulize_permHandler->getGroupScopeGroupIds($groupObject->getVar('groupid'));
    if($scopeGroups===false) {
        $groupperms[$i]['groupscope_choice'][0] = " selected";
      } else {
        foreach($scopeGroups as $thisScopeGroupId) {
          $groupperms[$i]['groupscope_choice'][$thisScopeGroupId] = " selected";
        }
    }
    // Per-group filters.
    $filterSettingsToSend = isset($filterSettings[$thisGroup]) ? $filterSettings[$thisGroup] : "";
    $htmlFormId = $tableform ? "form-2" : "form-3"; // the form id will vary depending on the tabs, and tableforms have no elements tab
    $groupperms[$i]['groupfilter'] = formulize_createFilterUI($filterSettingsToSend, $fid."_".$thisGroup."_filter", $fid, $htmlFormId, "oom");
    $groupperms[$i]['hasgroupfilter'] = $filterSettingsToSend ? " checked" : "";
    $i++;
    
  	unset($criteria);
  }

// Common values are assigned to all tabs.
$common['aid'] = $aid;

$adminPage['tabs'][1]['name'] = _AM_MULTIPLE_FORM_PERMISSIONS;
$adminPage['tabs'][1]['template'] = "db:admin/multiple_permissions.html";
$adminPage['tabs'][1]['content'] = $common;
$adminPage['tabs'][1]['content']['groups'] = $groups;
$adminPage['needsave'] = true;

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['url'] = "page=application&aid=$aid&tab=forms";
$breadcrumbtrail[2]['text'] = $appName;
