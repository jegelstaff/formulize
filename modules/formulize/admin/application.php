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

// need to listen for $_GET['aid'] later so we can limit this to just the application that is requested
$aid = intval($_GET['aid']);
$framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$application_handler = xoops_getmodulehandler('applications','formulize');

$screen_handler = xoops_getmodulehandler('screen', 'formulize');
    
$menulinks = array(); // JAKEADDED
$formscreens = array();
$options = array();
    
if($aid == 0) {
	$appName = "Forms with no app";
	$appDesc = "";
	$appForms = array();
	$formObjects = $form_handler->getFormsByApplication(0); // returns array of objects
    $appLinks = $application_handler->getMenuLinksForApp(0,true);	
} else {
	$appObject = $application_handler->get($aid);
	$appName = $appObject->getVar('name');
	$appDesc = $appObject->getVar('description');
	$appLinks = $appObject->getVar('all_links'); // JAKEADDED
}
    
    // get list of all the links
    
    $index = 0; // JAKEADDED
    foreach ($appLinks as $menulink) // JAKEADDED
    {
        $menulinks[$index]['menu_id'] = $menulink->getVar('menu_id'); //Oct 2013 W. R.
        $menulinks[$index]['url'] = $menulink->getVar('url') ? $menulink->getVar('url') : "http://"; // JAKEADDED
        $menulinks[$index]['link_text'] = $menulink->getVar('link_text'); // JAKEADDED
        $menulinks[$index]['screen'] = $menulink->getVar('screen'); // JAKEADDED
        $menulinks[$index]['rank'] = $menulink->getVar('rank');	
        $menulinks[$index]['name'] = $menulink->getVar('name');	
        $menulinks[$index]['text'] = $menulink->getVar('text');	
        $menulinks[$index]['permissions'] = $menulink->getVar('permissions');
        $menulinks[$index]['default_screen'] = $menulink->getVar('default_screen'); //Oct 2013 W.R.
        $index ++; // JAKEADDED
    }
    
    $formObjects = $form_handler->getFormsByApplication($aid);
    // get list of all the forms and screens
    $allFormObjects = $form_handler->getAllForms();
    $forms = array();
    $forms[''] = "Select the form or screen:";
    foreach($allFormObjects as $thisFormObject) {
        $allForms[$thisFormObject->getVar('id_form')]['name'] = $thisFormObject->getVar('title');
        $allForms[$thisFormObject->getVar('id_form')]['id'] = $thisFormObject->getVar('id_form'); // settings tab uses id
        $forms['fid='.$thisFormObject->getVar('id_form')] = $thisFormObject->getVar('title');
        $screens = $screen_handler->getObjects(null,$thisFormObject->getVar('id_form'));
        //echo 'ASSIGNED ' . var_dump($screens);
        foreach($screens as $screen) {
            $forms['sid='.$screen->getVar('sid')] = "&nbsp;&nbsp;   ". $screen->getVar('title');
	}
    }
    $forms['url'] = "An external URL";
    

// get list of group ids that have no default screen set
$groupsWithDefaultScreen = $application_handler->getGroupsWithDefaultScreen();    
    
// get the list of groups
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
    
$options['listsofscreenoptions'] = $forms;
    
$screen_handler = xoops_getmodulehandler('screen', 'formulize');
$gperm_handler = xoops_gethandler('groupperm');
global $xoopsUser;
foreach($formObjects as $thisFormObject) {
	if(!$gperm_handler->checkRight("edit_form", $thisFormObject->getVar('id_form'), $xoopsUser->getGroups(), getFormulizeModId())) {
		continue;
	}
	$formsInApp[$thisFormObject->getVar('id_form')]['name'] = $thisFormObject->getVar('title');
	$formsInApp[$thisFormObject->getVar('id_form')]['fid'] = $thisFormObject->getVar('id_form'); // forms tab uses fid
	$hasDelete = $gperm_handler->checkRight("delete_form", $thisFormObject->getVar('id_form'), $xoopsUser->getGroups(), getFormulizeModId());
	$formsInApp[$thisFormObject->getVar('id_form')]['hasdelete'] = $hasDelete;
	// get the default screens for each form too
	$defaultFormScreen = $thisFormObject->getVar('defaultform');
	$defaultListScreen = $thisFormObject->getVar('defaultlist');
	$defaultFormObject = $screen_handler->get($defaultFormScreen);
	if(is_object($defaultFormObject)) {
		$defaultFormName = $defaultFormObject->getVar('title');
	}
	$defaultListObject = $screen_handler->get($defaultListScreen);
	if(is_object($defaultListObject)) {
		$defaultListName = $defaultListObject->getVar('title');
	}
	$formsInApp[$thisFormObject->getVar('id_form')]['defaultformscreenid'] = $defaultFormScreen;
	$formsInApp[$thisFormObject->getVar('id_form')]['defaultlistscreenid'] = $defaultListScreen;
	$formsInApp[$thisFormObject->getVar('id_form')]['defaultformscreenname'] = $defaultFormName;
	$formsInApp[$thisFormObject->getVar('id_form')]['defaultlistscreenname'] = $defaultListName;
	$formsInApp[$thisFormObject->getVar('id_form')]['lockedform'] = $thisFormObject->getVar('lockedform');
	$formsInApp[$thisFormObject->getVar('id_form')]['istableform'] = $thisFormObject->getVar('tableform');
}


$allRelationships = array();
foreach($formObjects as $thisForm) {
	$allRelationships = array_merge($allRelationships, $framework_handler->getFrameworksByForm($thisForm->getVar('id_form'))); // returns array of objects
	if($aid) {
		// package up the info we need for drawing the list of forms in the app
		$allForms[$thisForm->getVar('id_form')]['selected'] = " selected";
	}
}
$relationships = $framework_handler->formatFrameworksAsRelationships($allRelationships);

$all_screens = array();
$screen_types = array("form" => "Single Page", "multiPage" => "Multi-page", "listOfEntries" => "List of Entries");
foreach ($screen_handler->getObjects(null, null) as $key => $value) {
    $sid = $value->getVar("sid");
    $all_screens[$sid] = array(
        'sid'       => $sid,
        'title'     => $value->getVar("title"),
        'fid'       => $value->getVar("fid"),
        'formname'  => $allForms[$value->getVar("fid")]["name"],
        'type'      => $screen_types[$value->getVar("type")],
    );
}

$common['aid'] = $aid;
$common['name'] = $appName;

// adminPage tabs sections must contain a name, template and content key
// content is the data the is available in the tab as $content.foo
// any declared sub key of $content, such as 'forms' will be assigned to accordions
// accordion content is available as $sectionContent.foo

$i=0;
if($aid > 0) {
    $i++;
    $adminPage['tabs'][$i]['name'] = _AM_APP_SETTINGS;
    $adminPage['tabs'][$i]['template'] = "db:admin/application_settings.html";
    $adminPage['tabs'][$i]['content'] = $common;
    $adminPage['tabs'][$i]['content']['description'] = $appDesc;
    $adminPage['tabs'][$i]['content']['forms'] = $allForms;
}

$i++;
$adminPage['tabs'][$i]['name'] = "Forms";
$adminPage['tabs'][$i]['template'] = "db:admin/application_forms.html";
$adminPage['tabs'][$i]['content'] = $common;
$adminPage['tabs'][$i]['content']['forms'] = $formsInApp;

$i++;
$adminPage['tabs'][$i]['name'] = "Screens";
$adminPage['tabs'][$i]['template'] = "db:admin/application_screens.html";
$adminPage['tabs'][$i]['content'] = $common;
$adminPage['tabs'][$i]['content']['screens'] = $all_screens;

$i++;
$adminPage['tabs'][$i]['name'] = _AM_APP_RELATIONSHIPS;
$adminPage['tabs'][$i]['template'] = "db:admin/application_relationships.html";
$adminPage['tabs'][$i]['content'] = $common;
$adminPage['tabs'][$i]['content']['relationships'] = $relationships; 

$i++;
$adminPage['tabs'][$i]['name'] = " Menu Entries";
$adminPage['tabs'][$i]['template'] = "db:admin/application_menu_entries.html";
$adminPage['tabs'][$i]['content'] = $options + $common;
$adminPage['tabs'][$i]['content']['links'] = $menulinks;
$adminPage['tabs'][$i]['content']['groups'] = $groups;
$adminPage['tabs'][$i]['content']['groupsWithDefaultScreen'] = $groupsWithDefaultScreen;

//this new part creates an object for application_code_save.php if user is allowed to use custom code
if(is_object($appObject)){
    $i++;
    $adminPage['tabs'][$i]['name'] = "Code";
    $adminPage['tabs'][$i]['template'] = "db:admin/application_code.html";
    $adminPage['tabs'][$i]['content'] = $common;
    $adminPage['tabs'][$i]['content']['custom_code'] = $appObject->getVar("custom_code");
}


$adminPage['pagetitle'] = _AM_APP_APPLICATION.$appName;
$adminPage['needsave'] = true;

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = $appName;

