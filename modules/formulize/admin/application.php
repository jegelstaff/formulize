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

// this file gets all the data about applications, so we can display the Settings/forms/relationships tabs for applications

// need to listen for $_GET['aid'] later so we can limit this to just the application that is requested
$aid = intval($_GET['aid']);
$framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$application_handler = xoops_getmodulehandler('applications','formulize');

$screen_handler = xoops_getmodulehandler('screen', 'formulize');

$menulinks = array();
$formscreens = array();
$options = array();

if ($aid == 0) {
    $appName = "Forms with no app";
    $appDesc = "";
    $appForms = array();
    $formObjects = $form_handler->getFormsByApplication(0); // returns array of objects
    $appLinks = $application_handler->getMenuLinksForApp(0,true);
} else {
    $appObject = $application_handler->get($aid);
    $appName = $appObject->getVar('name');
    $appDesc = $appObject->getVar('description');
    $appLinks = $appObject->getVar('all_links');
}

// get list of all the links

$index = 0;
foreach ($appLinks as $menulink) {
    $menulinks[$index]['menu_id'] = $menulink->getVar('menu_id'); //Oct 2013 W. R.
    $menulinks[$index]['url'] = $menulink->getVar('url', 'raw') ? $menulink->getVar('url', 'raw') : "http://";
    $menulinks[$index]['link_text'] = $menulink->getVar('link_text');
    $menulinks[$index]['screen'] = $menulink->getVar('screen');
    $menulinks[$index]['rank'] = $menulink->getVar('rank');
    $menulinks[$index]['name'] = $menulink->getVar('name');
    $menulinks[$index]['text'] = $menulink->getVar('text');
    $menulinks[$index]['permissions'] = $menulink->getVar('permissions');
    $menulinks[$index]['default_screen'] = $menulink->getVar('default_screen'); //Oct 2013 W.R.
    $menulinks[$index]['note']=$menulink->getVar('note');//Jan 2015 Jinfu
    $index ++;
}

$formObjects = $form_handler->getFormsByApplication($aid);
// get list of all the forms and screens
$allFormObjects = $form_handler->getAllForms();
$forms = array();
$forms[''] = "Select the form or screen:";
foreach($allFormObjects as $thisFormObject) {
    $allForms[$thisFormObject->getVar('id_form')]['name'] = $thisFormObject->getVar('title');
    // settings tab uses id
    $allForms[$thisFormObject->getVar('id_form')]['id'] = $thisFormObject->getVar('id_form');
    $forms['fid='.$thisFormObject->getVar('id_form')] = $thisFormObject->getVar('title');
    $screens = $screen_handler->getObjects(null,$thisFormObject->getVar('id_form'));
    foreach($screens as $screen) {
        $forms['sid='.$screen->getVar('sid')] = "&nbsp;&nbsp;   ". $screen->getVar('title');
    }
}
$forms['url'] = "A URL";


// get list of group ids that have no default screen set
$groupsWithDefaultScreen = $application_handler->getGroupsWithDefaultScreen();

// get the list of groups
$member_handler = xoops_gethandler('member');
$allGroups = $member_handler->getGroups();
$groups = array();
if (!isset($selectedGroups)) {
    $selectedGroups = isset($_POST['groups']) ? $_POST['groups'] : array();
}
$orderGroups = isset($_POST['order']) ? $_POST['order'] : "creation";
foreach($allGroups as $thisGroup) {
    $groups[$thisGroup->getVar('name')]['id'] = $thisGroup->getVar('groupid');
    $groups[$thisGroup->getVar('name')]['name'] = $thisGroup->getVar('name');
    $groups[$thisGroup->getVar('name')]['selected'] = in_array($thisGroup->getVar('groupid'), $selectedGroups) ? " selected" : "";
}
if ($orderGroups == "alpha") {
    ksort($groups);
}

$options['listsofscreenoptions'] = $forms;

$screen_handler = xoops_getmodulehandler('screen', 'formulize');
$gperm_handler = xoops_gethandler('groupperm');
$adminLayoutTopAndLeftForForms = $application_handler->getAdminLayoutTopAndLeftForForms($aid);
$formsInApp = array();
global $xoopsUser;
foreach($formObjects as $thisFormObject) {
    if (!$gperm_handler->checkRight("edit_form", $thisFormObject->getVar('id_form'), $xoopsUser->getGroups(), getFormulizeModId())) {
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
    if (is_object($defaultFormObject)) {
        $defaultFormName = $defaultFormObject->getVar('title');
    }
    $defaultListObject = $screen_handler->get($defaultListScreen);
    if (is_object($defaultListObject)) {
        $defaultListName = $defaultListObject->getVar('title');
    }
    $formsInApp[$thisFormObject->getVar('id_form')]['defaultformscreenid'] = $defaultFormScreen;
    $formsInApp[$thisFormObject->getVar('id_form')]['defaultlistscreenid'] = $defaultListScreen;
    $formsInApp[$thisFormObject->getVar('id_form')]['defaultformscreenname'] = $defaultFormName;
    $formsInApp[$thisFormObject->getVar('id_form')]['defaultlistscreenname'] = $defaultListName;
    $formsInApp[$thisFormObject->getVar('id_form')]['lockedform'] = $thisFormObject->getVar('lockedform');
    $formsInApp[$thisFormObject->getVar('id_form')]['istableform'] = $thisFormObject->getVar('tableform');
    $formsInApp[$thisFormObject->getVar('id_form')]['top'] = (isset($adminLayoutTopAndLeftForForms[$thisFormObject->getVar('id_form')]['top']) AND $adminLayoutTopAndLeftForForms[$thisFormObject->getVar('id_form')]['top']) ? $adminLayoutTopAndLeftForForms[$thisFormObject->getVar('id_form')]['top']: '0px';
    $formsInApp[$thisFormObject->getVar('id_form')]['left'] = (isset($adminLayoutTopAndLeftForForms[$thisFormObject->getVar('id_form')]['left']) AND $adminLayoutTopAndLeftForForms[$thisFormObject->getVar('id_form')]['left']) ? $adminLayoutTopAndLeftForForms[$thisFormObject->getVar('id_form')]['left']: '0px';
}


$allRelationships = array($framework_handler->get(-1)); // start with primary relationship
foreach($formObjects as $thisForm) {
    $allRelationships = array_merge($allRelationships, $framework_handler->getFrameworksByForm($thisForm->getVar('id_form'))); // returns array of objects
    if ($aid) {
        // package up the info we need for drawing the list of forms in the app
        $allForms[$thisForm->getVar('id_form')]['selected'] = " selected";
    }
}
$relationships = $framework_handler->formatFrameworksAsRelationships($allRelationships);

$all_screens = array();
$screen_types = array("form" => "Single Page", "multiPage" => "Multi-page", "listOfEntries" => "List of Entries");
$screen_sort = $_GET['sort'];
$screen_sort_order = $_GET['order'];
$screen_page = intval($_GET['nav']);
$screen_limit = 20;
foreach ($screen_handler->getObjects(null, null, $aid, $screen_sort, $screen_sort_order, true, $screen_page, $screen_limit) as $key => $value) {
    $sid = $value->getVar("sid");
    $all_screens[$sid] = array(
        'sid'       => $sid,
        'title'     => $value->getVar("title"),
        'fid'       => $value->getVar("fid"),
        'formname'  => $allForms[$value->getVar("fid")]["name"],
        'type'      => $screen_types[$value->getVar("type")],
    );
}

$common['screenSort'] = $screen_sort;
$common['order'] = $screen_sort_order;
if ($screen_sort_order == "DESC") {
	$common['nextOrder'] = "ASC";
} else {
	$common['nextOrder'] = "DESC";
}

$screen_page = $screen_page < 1 ? 1 : $screen_page;
$resultNum = count((array) $screen_handler->getObjects(null, null, $aid, $screen_sort, $screen_sort_order));
$pageNumbers = ceil($resultNum / $screen_limit);

$pageNav = "";

if($pageNumbers > 1) {
	if($pageNumbers > 9) {
		if($screen_page < 6) {
			$firstDisplayPage = 1;
			$lastDisplayPage = 9;
		} elseif($screen_page + 4 > $pageNumbers) { // too close to the end
			$firstDisplayPage = $screen_page - 4 - ($screen_page+4-$pageNumbers); // the previous four, plus the difference by which we're over the end when we add 4
			$lastDisplayPage = $pageNumbers;
		} else { // somewhere in the middle
			$firstDisplayPage = $screen_page - 4;
			$lastDisplayPage = $screen_page + 4;
		}
	} else {
		$firstDisplayPage = 1;
		$lastDisplayPage = $pageNumbers;
	}

	$pageNav .= "<p><div class=\"formulize-page-navigation\"><span class=\"page-navigation-label\">". _AM_FORMULIZE_LOE_ONPAGE."</span>";
	if ($screen_page > 1) {
		$pageNav .= "<a href=\"?page=application&aid=". $aid ."&tab=screens&sort=". $screen_sort ."&order=". $screen_sort_order ."&nav=". ($screen_page - 1) ."\" class=\"page-navigation-prev\" >"._AM_FORMULIZE_LOE_PREVIOUS."</a>";
	}
	if($firstDisplayPage > 1) {
		$pageNav .= "<a href=\"?page=application&aid=". $aid ."&tab=screens&sort=". $screen_sort ."&order=". $screen_sort_order ."&nav=1\">1</a><span class=\"page-navigation-skip\">—</span>";
	}
	for($i = $firstDisplayPage; $i <= $lastDisplayPage; $i++) {
		$pageNav .= "<a href=\"?page=application&aid=". $aid ."&tab=screens&sort=". $screen_sort ."&order=". $screen_sort_order ."&nav=". $i ."\" class=\"page-navigation-active\">$i</a>";
	}
	if($lastDisplayPage < $pageNumbers) {
		$lastPageStart = ($pageNumbers * $numberPerPage) - $numberPerPage;
		$pageNav .= "<span class=\"page-navigation-skip\">—</span><a href=\"?page=application&aid=". $aid ."&tab=screens&sort=". $screen_sort ."&order=". $screen_sort_order ."&nav=". $pageNumbers ."\">" . $pageNumbers . "</a>";
	}
	if ($screen_page < $pageNumbers) {
		$pageNav .= "<a href=\"?page=application&aid=". $aid ."&tab=screens&sort=". $screen_sort ."&order=". $screen_sort_order ."&nav=". ($screen_page + 1) ."\" class=\"page-navigation-next\">"._AM_FORMULIZE_LOE_NEXT."</a>";
	}
	$pageNav .= "</div><span class=\"page-navigation-total\">".
			"Total entries: ".$resultNum."</span></p>\n";
}

$common['pageNav'] = $pageNav;

$common['aid'] = $aid;
$common['name'] = $appName;

$allFidsToUse = array_keys($formsInApp);
include XOOPS_ROOT_PATH.'/modules/formulize/admin/generateTemplateElementHandleHelp.php';
$variableHelp['variabletemplatehelp'] = $listTemplateHelp;

// adminPage tabs sections must contain a name, template and content key
// content is the data the is available in the tab as $content.foo
// any declared sub key of $content, such as 'forms' will be assigned to accordions
// accordion content is available as $sectionContent.foo

$i=0;
if ($aid > 0) {
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
$adminPage['tabs'][$i]['name'] = "Menu Entries";
$adminPage['tabs'][$i]['template'] = "db:admin/application_menu_entries.html";
$adminPage['tabs'][$i]['content'] = $options + $common;
$adminPage['tabs'][$i]['content']['links'] = $menulinks;
$adminPage['tabs'][$i]['content']['groups'] = $groups;
$adminPage['tabs'][$i]['content']['groupsWithDefaultScreen'] = $groupsWithDefaultScreen;

//this new part creates an object for application_code_save.php if user is allowed to use custom code
if (is_object($appObject)){
    $i++;
    $adminPage['tabs'][$i]['name'] = "Code";
    $adminPage['tabs'][$i]['template'] = "db:admin/application_code.html";
    $adminPage['tabs'][$i]['content'] = $variableHelp + $common;
    $adminPage['tabs'][$i]['content']['custom_code'] = $appObject->getVar("custom_code");
}

$adminPage['pagetitle'] = _AM_APP_APPLICATION.$appName;
$adminPage['needsave'] = true;

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = $appName;
