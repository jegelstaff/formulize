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
global $xoopsDB;

// need to listen for $_GET['aid'] later so we can limit this to just the application that is requested
$aid = intval($_GET['aid']);
$application_handler = xoops_getmodulehandler('applications','formulize');
// get a list of all applications
$allApps = $application_handler->getAllApplications();

if($aid == 0) {
	$appName = "Forms with no app"; 
} else {
	$appObject = $application_handler->get($aid);
	$appName = $appObject->getVar('name');
}

$elements = array();
if($_GET['fid'] != "new") {
  $fid = intval($_GET['fid']);
  $form_handler = xoops_getmodulehandler('forms', 'formulize');
  $formObject = $form_handler->get($fid);
  $formName = $formObject->getVar('title');
  $singleentry = $formObject->getVar('single');
  $tableform = $formObject->getVar('tableform');
  $headerlist = $formObject->getVar('headerlist');
  $headerlistArray = explode("*=+*:",trim($headerlist,"*=+*:"));
  $defaultform = $formObject->getVar('defaultform');
  $defaultlist = $formObject->getVar('defaultlist');
  $menutext = $formObject->getVar('menutext');
  $form_handle = $formObject->getVar('form_handle');
  
  $element_handler = xoops_getmodulehandler('elements', 'formulize');
  $elementObjects = $element_handler->getObjects2(null, $fid);
  $elements = array();
  $elementHeadings = array();
  $formApplications = array();
  // $elements array is going to be used to populate accordion sections, so it must contain the following:
  // a 'name' key and a 'content' key for each form that is found
  // Name will be the heading of the section, content is data used in the template for each section
  $i = 1; 
  foreach($elementObjects as $thisElement) {
    $elementCaption = strip_tags($thisElement->getVar('ele_caption'));
		$colhead = strip_tags($thisElement->getVar('ele_colhead'));
		$cleanType = convertTypeToText($thisElement->getVar('ele_type'), $thisElement->getVar('ele_value'));
    $ele_id = $thisElement->getVar('ele_id');
		$ele_handle = $thisElement->getVar('ele_handle');
		$nameText = $colhead ? printSmart($colhead,55) : printSmart($elementCaption,55);
    $elements[$i]['name'] = "$nameText - $cleanType - $ele_handle";
    $elements[$i]['content']['ele_id'] = $ele_id;
    $elements[$i]['content']['ele_handle'] = $ele_handle;
    $ele_type = $thisElement->getVar('ele_type');
    switch($ele_type) {
      case("text"):
        $converttext = "Convert to multi-line text box";
        $linktype = "textarea";
        break;
      case("textarea"):
        $converttext = "Convert to single-line text box";
        $linktype = "text";
        break;
      case("radio"):
        $converttext = "Convert to check boxes";
        $linktype = "checkbox";
        break;
      case("checkbox"):
        $converttext = "Convert to radio buttons";
        $linktype = "radio";
        break;
      default:
        $converttext = "";
        $linktype = "";
    }
    $elements[$i]['content']['converttext'] = $converttext;
    $elements[$i]['content']['linktype'] = $linktype;
    $elements[$i]['content']['ele_type'] = $cleanType;
    $elements[$i]['content']['ele_req'] = removeNotApplicableRequireds($thisElement->getVar('ele_type'), $thisElement->getVar('ele_req'));
    $ele_display = $thisElement->getVar('ele_display');
    $multiGroupDisplay = false;
		if(substr($ele_display, 0, 1) == ",") {
			$multiGroupDisplay = true;
      $fs_member_handler =& xoops_gethandler('member');
      $fs_xoops_groups =& $fs_member_handler->getGroups();
      $displayGroupList = explode(",", trim($ele_display, ","));
      $check_display = '';
      foreach($displayGroupList as $groupList) {
				if($groupList != "") {
		      if($check_display != '') { $check_display .= ", "; }
					$group_display = $fs_member_handler->getGroup($groupList);
					if(is_object($group_display)) {
						$check_display .= $group_display->getVar('name');
					} else {
						$check_display .= "???";
					}
				}                               
      }
      $check_display = '<a class=info href="" onclick="return false;" alt="' . $check_display . '" title="' . $check_display . '">' . _AM_FORM_DISPLAY_MULTIPLE . '</a>';
    } else {
      $check_display = $ele_display;
    }
    $elements[$i]['content']['ele_display'] = $check_display;
    $elements[$i]['content']['ele_private'] = $thisElement->getVar('ele_private');
    $elementHeadings[$i]['text'] = $colhead ? printSmart($colhead) : printSmart($elementCaption);
    $elementHeadings[$i]['ele_id'] = $ele_id;
    $elementHeadings[$i]['selected'] = in_array($ele_id, $headerlistArray) ? " selected" : "";
    $i++;
  }
  // add in the metadata headers
  $creator_email_selected = (in_array('creator_email', $headerlistArray)) ? " selected" : "";
  array_unshift($elementHeadings,array('text'=>_formulize_DE_CALC_CREATOR_EMAIL, 'ele_id'=>'creator_email', 'selected'=>$creator_email_selected));

  $mod_datetime_selected = (in_array('mod_datetime', $headerlistArray) OR in_array('mod_date', $headerlistArray)) ? " selected" : "";
  array_unshift($elementHeadings,array('text'=>_formulize_DE_CALC_MODDATE, 'ele_id'=>'mod_date', 'selected'=>$mod_datetime_selected));
  
  $creation_datetime_selected = (in_array('creation_datetime', $headerlistArray) OR in_array('creation_date', $headerlistArray)) ? " selected" : "";
  array_unshift($elementHeadings,array('text'=>_formulize_DE_CALC_CREATEDATE, 'ele_id'=>'creation_datetime', 'selected'=>$creation_datetime_selected));

  $mod_uid_selected = (in_array('mod_uid', $headerlistArray) OR in_array('proxyid', $headerlistArray)) ? " selected" : "";
  array_unshift($elementHeadings,array('text'=>_formulize_DE_CALC_MODIFIER, 'ele_id'=>'mod_uid', 'selected'=>$mod_uid_selected));
 
  $creation_uid_selected = (in_array('creation_uid', $headerlistArray) OR in_array('uid', $headerlistArray)) ? " selected" : "";
  array_unshift($elementHeadings,array('text'=>_formulize_DE_CALC_CREATOR, 'ele_id'=>'creation_uid', 'selected'=>$creation_uid_selected));
  
  // get a list of applications this form is involved with
  $thisFormApplications = $application_handler->getApplicationsByForm($fid);
  foreach($thisFormApplications as $thisApp) {
    $formApplications[] = $thisApp->getVar('appid');
  }
  
  // get permission data for this form
  // get group lists
  $groupListSQL = "SELECT gl_id, gl_name, gl_groups FROM ".$xoopsDB->prefix("group_lists")." ORDER BY gl_name";
  $grouplists = array();
  if(isset($_POST['grouplistname']) AND $_POST['grouplistname']) {
    $selectedGroupList = $_POST['grouplistname'];
  } elseif(isset($_POST['loadthislist']) AND $_POST['loadthislist']) {
    $selectedGroupList = intval($_POST['loadthislist']);
  } elseif(isset($_POST['useselection'])) {
    $selectedGroupList = 0;
  } elseif(isset($_POST['grouplists'])) {
    $selectedGroupList = intval($_POST['grouplists']);
  }
  $grouplists[0]['id'] = 0;
  $grouplists[0]['name'] = "No group list selected";
  $grouplists[0]['selected'] = $selectedGroupList ? "" : " selected";
  if($result = $xoopsDB->query($groupListSQL)) {
    while($array = $xoopsDB->fetchArray($result)) {
      $grouplists[$array['gl_id']]['id'] = $array['gl_id'];
      $grouplists[$array['gl_id']]['name'] = $array['gl_name'];
      if((is_numeric($selectedGroupList) AND $array['gl_id'] == $selectedGroupList) OR $array['gl_name'] === $selectedGroupList) {
        $glSelectedText = " selected";
        $selectedGroups = explode(",",$array['gl_groups']);
      } else {
        $glSelectedText = "";
      }
      $grouplists[$array['gl_id']]['selected'] = $glSelectedText;
    }
  }
  
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
  
  // get all the permissions for the selected groups for this form
  $gperm_handler =& xoops_gethandler('groupperm');
  $formulize_permHandler = new formulizePermHandler($fid);
  $filterSettings = $formObject->getVar('filterSettings');
  $groupperms = array();
  $groupfilters = array();
  $i = 0;
  foreach($selectedGroups as $thisGroup) {
    // get all the permissions this group has on this form
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
      // group-specific-scope
      $scopeGroups = $formulize_permHandler->getGroupScopeGroupIds($groupObject->getVar('groupid'));
      if($scopeGroups===false) {
        $groupperms[$i]['groupscope_choice'][0] = " selected";
      } else {
        foreach($scopeGroups as $thisScopeGroupId) {
          $groupperms[$i]['groupscope_choice'][$thisScopeGroupId] = " selected";
        }
      }
      // per-group-filters
      $filterSettingsToSend = isset($filterSettings[$thisGroup]) ? $filterSettings[$thisGroup] : "";
      $htmlFormId = $tableform ? "form-2" : "form-3"; // the form id will vary depending on the tabs, and tableforms have no elements tab
      $groupperms[$i]['groupfilter'] = formulize_createFilterUI($filterSettingsToSend, $fid."_".$thisGroup."_filter", $fid, $htmlFormId, "oom");
      $groupperms[$i]['hasgroupfilter'] = $filterSettingsToSend ? " checked" : "";
      $i++;
  		unset($criteria);
  }

} else {
  $fid = $_GET['fid'];
  if($_GET['tableform']) {
    $newtableform = true;
  }
  $formName = "New form";
  $singleentry = "off"; // need to send a default for this
  $defaultform = 0;
  $defaultlist = 0;
  $menutext = "Use the form's title";
  $form_handle = "";
  if($_GET['aid']) {
	$formApplications = array(intval($_GET['aid']));
  } 
}

$i = 1;
$applications = array();
foreach($allApps as $thisApp) {
  $applications[$i]['appid'] = $thisApp->getVar('appid');
  $applications[$i]['text'] = printSmart($thisApp->getVar('name'),50);
  if(isset($formApplications)) {
    $applications[$i]['selected'] = in_array($thisApp->getVar('appid'),$formApplications) ? " selected" : "";
  } else {
    $applications[$i]['selected'] = "";
  }
  $i++;
}

// common values should be assigned to all tabs
$common['name'] = $formName;
$common['fid'] = $fid;
$common['aid'] = $aid;
$common['defaultform'] = $defaultform;
$common['defaultlist'] = $defaultlist;

$permissions = array();
$permissions['hello'] = "Hello Permission World";

// need to get screen data so this can be populated properly
$screens = array();
$screen_handler = xoops_getmodulehandler('screen', 'formulize');
$criteria_object = new CriteriaCompo(new Criteria('type','multiPage'));
$criteria_object->add(new Criteria('type','form'), 'OR');
$mulitPageAndFormScreens = $screen_handler->getObjects($criteria_object,$fid);
$i = 1;
foreach($mulitPageAndFormScreens as $screen) {
  $screens['screens'][$i]['sid'] = $screen->getVar('sid');
  $screens['screens'][$i]['title'] = $screen->getVar('title');
  $screens['screens'][$i]['type'] = $screen->getVar('type');
  $i++;
}
$listOfEntriesScreens = $screen_handler->getObjects(new Criteria('type','listOfEntries'),$fid);
$i = 1;
foreach($listOfEntriesScreens as $screen) {
  $screens['listOfEntries'][$i]['sid'] = $screen->getVar('sid');
  $screens['listOfEntries'][$i]['title'] = $screen->getVar('title');
  $i++;
}

$settings = array();
$settings['singleentry'] = $singleentry;
$settings['menutext'] = $menutext;
$settings['form_handle'] = $form_handle;
$settings['istableform'] = ($tableform OR $newtableform) ? true : false;

$i = 1;
$adminPage['tabs'][$i]['name'] = "Settings";
$adminPage['tabs'][$i]['template'] = "db:admin/form_settings.html";
$adminPage['tabs'][$i]['content'] = $settings + $common;
$adminPage['tabs'][$i]['content']['applications'] = $applications;
if(isset($elementHeadings)) {
  $adminPage['tabs'][$i]['content']['elementheadings'] = $elementHeadings;
}
if(isset($formApplications)) {
  $adminPage['tabs'][$i]['content']['formapplications'] = $formApplications;
}
$i++;

if($fid != "new") {
  
	$advanced_calculations = array();
	$advanced_calculation_handler = xoops_getmodulehandler('advancedCalculation', 'formulize');
	$advanced_calculations['advanced_calculations'] = $advanced_calculation_handler->getList($fid);
	
  if(!$tableform AND !$newtableform) {
    $adminPage['tabs'][$i]['name'] = "Elements";
    $adminPage['tabs'][$i]['template'] = "db:admin/form_elements.html";
    $adminPage['tabs'][$i]['content'] = $common;
    if(isset($elements)) {
      $adminPage['tabs'][$i]['content']['elements'] = $elements;
    }
    $i++;
  }
  
  $adminPage['tabs'][$i]['name'] = "Permissions";
  $adminPage['tabs'][$i]['template'] = "db:admin/form_permissions.html";
  $adminPage['tabs'][$i]['content'] = $common;
  $adminPage['tabs'][$i]['content']['groups'] = $groups;
  $adminPage['tabs'][$i]['content']['grouplists'] = $grouplists;
  $adminPage['tabs'][$i]['content']['order'] = $orderGroups;
  $adminPage['tabs'][$i]['content']['samediff'] = $_POST['same_diff'] == "same" ? "same" : "different";
  $adminPage['tabs'][$i]['content']['groupperms'] = $groupperms;
  
  $i++;
  
  $adminPage['tabs'][$i]['name'] = "Screens";
  $adminPage['tabs'][$i]['template'] = "db:admin/form_screens.html";
  $adminPage['tabs'][$i]['content'] = $screens + $common;
  $i++;
  
	$adminPage['tabs'][$i]['name'] = "Procedures";
  $adminPage['tabs'][$i]['template'] = "db:admin/form_advanced_calculations.html";
  $adminPage['tabs'][$i]['content'] = $advanced_calculations + $common;
  $i++;
	
}

$adminPage['pagetitle'] = "Form: ".$formName;
$adminPage['needsave'] = true;

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['url'] = "page=application&aid=$aid&tab=forms";
$breadcrumbtrail[2]['text'] = $appName;
$breadcrumbtrail[3]['text'] = $formName;

function removeNotApplicableRequireds($type, $req) {
  switch($type) {
    case "text":
    case "textarea":
    case "select":
    case "radio":
		case "checkbox":
    case "date":
      return $req;
  }
  return false;
}
