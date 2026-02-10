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

include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";
global $xoopsDB;

// need to listen for $_GET['aid'] later so we can limit this to just the application that is requested
$aid = intval($_GET['aid']);
$application_handler = xoops_getmodulehandler('applications','formulize');
// get a list of all applications
$allApps = $application_handler->getAllApplications();

if ($aid == 0) {
    $appName = _AM_APP_FORMWITHNOAPP;
} else {
    $appObject = $application_handler->get($aid);
    $appName = $appObject->getVar('name');
}

$elements = array();
if ($_GET['fid'] != "new") {
    $fid = intval($_GET['fid']);
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($fid);
    $formName = $formObject->getVar('title');
		$singular = $formObject->getVar('singular');
		$plural = $formObject->getVar('plural');
    $singleentry = $formObject->getVar('single') ? $formObject->getVar('single') : 'off';
    $tableform = $formObject->getVar('tableform');
    $headerlist = $formObject->getVar('headerlist');
    $headerlistArray = explode("*=+*:",trim($headerlist,"*=+*:"));
    $defaultform = $formObject->getVar('defaultform');
    $defaultlist = $formObject->getVar('defaultlist');
    $menutext = $formObject->getVar('menutext');
    $form_handle = $formObject->getVar('form_handle');
    $store_revisions = $formObject->getVar('store_revisions');
    $note = $formObject->getVar('note');
    $send_digests = $formObject->getVar('send_digests');
		$defaultpi = $formObject->getVar('pi');
		$pioptions = array();
		$entries_are_users = $formObject->getVar('entries_are_users');
		$entries_are_users_conditions = $formObject->getVar('entries_are_users_conditions');
		$entries_are_users_conditions_ui = formulize_createFilterUI($entries_are_users_conditions, "entriesareusersconditions", $fid, "form-1");
		$entries_are_users_default_groups = $formObject->getVar('entries_are_users_default_groups');
		$entries_are_users_default_groups_ui = formulize_renderDefaultGroupsUI($entries_are_users_default_groups);
		$entries_are_users_default_groups_selected = array();
		if (is_array($entries_are_users_default_groups) && !empty($entries_are_users_default_groups)) {
			$group_handler = xoops_gethandler('group');
			foreach ($entries_are_users_default_groups as $gid) {
				$groupObj = $group_handler->get(intval($gid));
				if ($groupObj) {
					$entries_are_users_default_groups_selected[] = array('id' => intval($gid), 'name' => $groupObj->getVar('name'));
				}
			}
		}
		// Build template group metadata for explanatory descriptions in the UI
		$template_group_metadata = array();
		$tgRawMetadata = formulizeHandler::getTemplateGroupMetadataForForm($fid);
		foreach ($tgRawMetadata as $tgGroupId => $tgInfo) {
			if (!empty($tgInfo['linkedElements'])) {
				$elementRefs = array();
				foreach ($tgInfo['linkedElements'] as $linkedEl) {
					if ($linkedEl['formName']) {
						$elementRefs[] = sprintf(_AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_TEMPLATE_ELEMENT_REF_IN_FORM, $linkedEl['caption'], $linkedEl['formName']);
					} else {
						$elementRefs[] = sprintf(_AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_TEMPLATE_ELEMENT_REF, $linkedEl['caption']);
					}
				}
				$description = sprintf(_AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_TEMPLATE_DESC, $tgInfo['categoryName'], strtolower($tgInfo['formSingular']), implode(' or ', $elementRefs));
			} else {
				$description = sprintf(_AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_TEMPLATE_DESC_FALLBACK, $tgInfo['categoryName'], strtolower($tgInfo['formPlural']));
			}
			$template_group_metadata[$tgGroupId] = array('description' => $description);
		}

		// Add description to pre-populated selected groups
		foreach ($entries_are_users_default_groups_selected as &$selectedGroup) {
			if (isset($template_group_metadata[$selectedGroup['id']])) {
				$selectedGroup['description'] = $template_group_metadata[$selectedGroup['id']]['description'];
			} else {
				$selectedGroup['description'] = '';
			}
		}
		unset($selectedGroup);

		$entries_are_groups = $formObject->getVar('entries_are_groups');

		// Load group categories from stored mapping on the form object
		// This is an array of groupid => categoryName for existing categories
		$group_categories = array();
		if ($entries_are_groups) {
			$storedMapping = $formObject->getVar('group_categories');
			if (is_array($storedMapping)) {
				foreach ($storedMapping as $groupid => $categoryName) {
					// Skip "All Users" since it's always displayed as a fixed base category
					if ($categoryName !== _AM_SETTINGS_FORM_GROUP_CATEGORIES_ALL_USERS) {
						$group_categories[$groupid] = $categoryName;
					}
				}
			}
		}

		$framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
		$connections = $framework_handler->formatFrameworksAsRelationships(null, $fid);

    $elementObjects = $element_handler->getObjects(null, $fid);
		$elementIdsWithData = $formObject->getVar('elementsWithData');
    $elements = array();
    $elementHeadings = array();
    $formApplications = array();
    // $elements array is going to be used to populate accordion sections, so it must contain the following:
    // a 'name' key and a 'content' key for each form that is found
    // Name will be the heading of the section, content is data used in the template for each section
    $i = 1;
		$elementsInRelationshipLinks = getElementsInRelationshipLinks($elementObjects);
    foreach($elementObjects as $thisElement) {
        $elementCaption = trans(strip_tags($thisElement->getVar('ele_caption')));
        $colhead = trans(strip_tags($thisElement->getVar('ele_colhead')));
        $cleanType = convertTypeToText($thisElement->getVar('ele_type'), $thisElement->getVar('ele_value'));
        $ele_id = $thisElement->getVar('ele_id');
				$elements[$i]['content']['hasData'] = 0;
				if(isset($elementIdsWithData[$ele_id])) {
					$pioptions[$ele_id] = $colhead ? $colhead : $elementCaption;
					$elements[$i]['content']['hasData'] = 1;
				}
        $ele_handle = $thisElement->getVar('ele_handle');
        $nameText = $colhead ? printSmart($colhead,55) : printSmart($elementCaption,55);
        $elements[$i]['name'] = "<span style='font-size: 125%;'>$nameText</span><br>$cleanType - $ele_handle";
        $elements[$i]['content']['ele_id'] = $ele_id;
        $elements[$i]['content']['ele_handle'] = $ele_handle;
				$elements[$i]['content']['inLink'] = in_array($ele_id, $elementsInRelationshipLinks);
        $ele_type = $thisElement->getVar('ele_type');
        switch($ele_type) {
          case("text"):
            $converttext = _AM_ELE_CONVERT_ML;
            $linktype = "textarea";
            break;
          case("textarea"):
            $converttext = _AM_ELE_CONVERT_SL;
            $linktype = "text";
            break;
          case("radio"):
            $converttext = _AM_ELE_CONVERT_CB;
            $linktype = "checkbox";
            break;
          case("checkbox"):
            $converttext = _AM_ELE_CONVERT_RB;
            $linktype = "radio";
            break;
          case("select"):
            $converttext = _AM_ELE_CONVERT_CB;
            $linktype = "checkboxfromsb";
        break;
          default:
            $converttext = "";
            $linktype = "";
        }
				$elements[$i]['content']['isSystemElement'] = $thisElement->isSystemElement;
        $elements[$i]['content']['converttext'] = $converttext;
        $elements[$i]['content']['linktype'] = $linktype;
        $elements[$i]['content']['ele_type'] = $cleanType;
        $elements[$i]['content']['ele_required'] = removeNotApplicableRequireds($thisElement->getVar('ele_type'), $thisElement->getVar('ele_required'));
        $ele_display = $thisElement->getVar('ele_display');
        $multiGroupDisplay = false;
        if (substr($ele_display, 0, 1) == ",") {
            $multiGroupDisplay = true;
            $fs_member_handler =& xoops_gethandler('member');
            $fs_xoops_groups =& $fs_member_handler->getGroups();
            $displayGroupList = explode(",", trim($ele_display, ","));
            $check_display = '';
            foreach($displayGroupList as $groupList) {
                if ($groupList != "") {
                    if ($check_display != '') {
                        $check_display .= ", ";
                    }
                    $group_display = $fs_member_handler->getGroup($groupList);
                    if (is_object($group_display)) {
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
    if (isset($_POST['grouplistname']) AND $_POST['grouplistname']) {
        $selectedGroupList = $_POST['grouplistname'];
    } elseif (isset($_POST['loadthislist']) AND $_POST['loadthislist']) {
        $selectedGroupList = intval($_POST['loadthislist']);
    } elseif (isset($_POST['useselection']) || isset($_POST['search_by_user'])) {
        $selectedGroupList = 0;
    } elseif (isset($_POST['grouplists'])) {
        $selectedGroupList = intval($_POST['grouplists']);
    }
    $grouplists[0]['id'] = 0;
    $grouplists[0]['name'] = "No group list selected";
    $grouplists[0]['selected'] = $selectedGroupList ? "" : " selected";
    if ($result = $xoopsDB->query($groupListSQL)) {
        while($array = $xoopsDB->fetchArray($result)) {
            $grouplists[$array['gl_id']]['id'] = $array['gl_id'];
            $grouplists[$array['gl_id']]['name'] = $array['gl_name'];
            if ((is_numeric($selectedGroupList) AND $array['gl_id'] == $selectedGroupList) OR $array['gl_name'] === $selectedGroupList) {
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
    $submitted_user = "";
    if (!isset($selectedGroups)) {
			$selectedGroups = array();
			if ($_POST['search_by_user']) {
            $searchUser = $_POST['submitted_user'];
            $requestedUser = $member_handler->getUsers(new Criteria('uid', formulize_db_escape($searchUser)));
				if (is_object($requestedUser[0])) {
					$selectedGroups = $requestedUser[0]->getGroups();
                $submitted_user = $requestedUser[0]->getVar('uname');
				} else {
                $submitted_user = _formulize_NO_MATCH_FOUND;
				}
			} else {
				$selectedGroups = (isset($_POST['groups']) AND is_array($_POST['groups'])) ? $_POST['groups'] : array();
			}
    }
		$selectedGroups = array_filter($selectedGroups, 'is_numeric');
    $orderGroups = isset($_POST['order']) ? $_POST['order'] : "creation";
    foreach($allGroups as $thisGroup) {
        $groups[$thisGroup->getVar('name')]['id'] = $thisGroup->getVar('groupid');
        $groups[$thisGroup->getVar('name')]['name'] = $thisGroup->getVar('name');
        $groups[$thisGroup->getVar('name')]['selected'] = in_array($thisGroup->getVar('groupid'), $selectedGroups) ? " selected" : "";
    }
    if ($orderGroups == "alpha") {
        ksort($groups);
    }

    // setup the user selection list
    // 1. Make Formulize Autocomplete Users Element Object - config settings for element
    $autocompleteUsersHandler = xoops_getmodulehandler('autocompleteUsersElement', 'formulize');
    $autocompleteUsersObject = $autocompleteUsersHandler->create();
    $autocompleteUsersObject->setVar('ele_value', $autocompleteUsersHandler->getDefaultEleValue());
    $autocompleteUsersObject->setVar('ele_handle', 'permissionUserList');
    $autocompleteUsersObject->setVar('ele_type', 'autocompleteUsers');
    $autocompleteUsersObject->setVar('ele_display', 1);
    // 2. Render the element to get a xoopsform element object
    $userSelectionList = $autocompleteUsersHandler->render($autocompleteUsersObject->getVar('ele_value'), '', 'submitted_user', isDisabled: false, element: $autocompleteUsersObject, entry_id: 'new');
    // 3. Render the xoopsform element object to get the markup
    $userSelectionList = $userSelectionList->render();

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
        if($groupObject = $member_handler->getGroup($thisGroup)) {

        $groupperms[$i]['name'] = $groupObject->getVar('name');
        $groupperms[$i]['id'] = $groupObject->getVar('groupid');
        foreach($perms as $perm) {
            $groupperms[$i][$perm->getVar('gperm_name')] = " checked";
        }
        // group-specific-scope
        $scopeGroups = $formulize_permHandler->getGroupScopeGroupIds($groupObject->getVar('groupid'));
        if ($scopeGroups===false) {
            $groupperms[$i]['groupscope_choice'][0] = " selected";
        } else {
            foreach($scopeGroups as $thisScopeGroupId) {
            $groupperms[$i]['groupscope_choice'][$thisScopeGroupId] = " selected";
            }
        }
        // per-group-filters
        $filterSettingsToSend = isset($filterSettings[$thisGroup]) ? $filterSettings[$thisGroup] : "";
        $htmlFormId = $tableform ? "form-2" : "form-3"; // the form id will vary depending on the tabs, and tableforms have no elements tab
        $groupperms[$i]['groupfilter'] = formulize_createFilterUI($filterSettingsToSend, $fid."_".$thisGroup."_filter", $fid, $htmlFormId, 0, "oom");
        $groupperms[$i]['existingFilter'] = getExistingFilter($filterSettingsToSend, $fid."_".$thisGroup."_filter", $fid, $htmlFormId, "oom");
        $groupperms[$i]['hasgroupfilter'] = $filterSettingsToSend ? " checked" : "";
        $i++;
        }
        unset($criteria);
    }

    // get all the permissions for the submitted_user
    if ($_POST['search_by_user']) {
        // Initialize $userperms
        // The basics
        $userperms['view_form'] = array();
        $userperms['add_own_entry'] = array();
        $userperms['update_own_entry'] = array();
        $userperms['update_group_entries'] = array();
        $userperms['update_other_entries'] = array();
        $userperms['delete_own_entry'] = array();
        $userperms['delete_group_entries'] = array();
        $userperms['delete_other_entries'] = array();

        // Visibility
        $userperms['view_private_elements'] = array();
        $userperms['view_their_own_entries'] = array(); //always on
        $userperms['view_globalscope'] = array();

        $userperms['view_groupscope'] = array();
        foreach($groups as $group) {
            $userperms['view_groupscope'][$group['id']] = array();
        }

        $userperms['view_groupfilter'] = array();
        $userperms['view_groupfilter']['all'] = array();
        $userperms['view_groupfilter']['oom'] = array();

        // Publishing 'Saved Views' of form entries
        $userperms['manage_own'] = array(); //always on
        $userperms['publish_reports'] = array();
        $userperms['publish_globalscope'] = array();
        $userperms['update_other_reports'] = array();
        $userperms['delete_other_reports'] = array();

        // Advanced options
        $userperms['import_data'] = array();
        $userperms['set_notifications_for_others'] = array();
        $userperms['add_proxy_entries'] = array();
        $userperms['update_entry_ownership'] = array();
        $userperms['ignore_editing_lock'] = array();
        $userperms['edit_form'] = array();
        $userperms['delete_form'] = array();

        foreach($groupperms as $groupperm) {
            // The basics
            if ($groupperm['view_form'] == " checked") {
                array_push($userperms['view_form'], $groupperm['name']);
            }
            if ($groupperm['add_own_entry'] == " checked") {
                array_push($userperms['add_own_entry'], $groupperm['name']);
            }
            if ($groupperm['update_own_entry'] == " checked") {
                array_push($userperms['update_own_entry'], $groupperm['name']);
            }
            if ($groupperm['update_group_entries'] == " checked") {
                array_push($userperms['update_group_entries'], $groupperm['name']);
            }
            if ($groupperm['update_other_entries'] == " checked") {
                array_push($userperms['update_other_entries'], $groupperm['name']);
            }
            if ($groupperm['delete_own_entry'] == " checked") {
                array_push($userperms['delete_own_entry'], $groupperm['name']);
            }
            if ($groupperm['delete_group_entries'] == " checked") {
                array_push($userperms['delete_group_entries'], $groupperm['name']);
            }
            if ($groupperm['delete_other_entries'] == " checked") {
                array_push($userperms['delete_other_entries'], $groupperm['name']);
            }

            // Visibility
            if ($groupperm['view_private_elements'] == " checked") {
                array_push($userperms['view_private_elements'], $groupperm['name']);
            }

            array_push($userperms['view_their_own_entries'], $groupperm['name']); //always on

            if ($groupperm['view_globalscope'] == " checked") {
                array_push($userperms['view_globalscope'], $groupperm['name']);
            }

            foreach($groups as $group) {
								if ($groupperm['groupscope_choice'][0] == " selected" AND $groupperm['name'] == $group['name']) {
   								$userperms['view_groupscope']["checked"] = true;
									array_push($userperms['view_groupscope'][$group['id']], '');
								}
                if ($groupperm['groupscope_choice'][$group['id']] == " selected") {
                  $userperms['view_groupscope']["checked"] = true;
                  array_push($userperms['view_groupscope'][$group['id']], $groupperm['name']);
                }
            }

            if ($groupperm['existingFilter']['all']) {
                foreach($groupperm['existingFilter']['all'] as $filter) {
                    if (!$userperms['view_groupfilter']['all'][$filter]) {
                        $userperms['view_groupfilter']['all'][$filter] = array();
                    }
                    array_push($userperms['view_groupfilter']['all'][$filter], $groupperm['name']);
                }
            }

            if ($groupperm['existingFilter']['oom']) {
                foreach($groupperm['existingFilter']['oom'] as $filter) {
                    if (!$userperms['view_groupfilter']['oom'][$filter]) {
                        $userperms['view_groupfilter']['oom'][$filter] = array();
                    }
                    array_push($userperms['view_groupfilter']['oom'][$filter], $groupperm['name']);
                }
            }

            // Publishing 'Saved Views' of form entries
            array_push($userperms['manage_own'], $groupperm['name']); //always on

            if ($groupperm['publish_reports'] == " checked") {
                array_push($userperms['publish_reports'], $groupperm['name']);
            }
            if ($groupperm['publish_globalscope'] == " checked") {
                array_push($userperms['publish_globalscope'], $groupperm['name']);
            }
            if ($groupperm['update_other_reports'] == " checked") {
                array_push($userperms['update_other_reports'], $groupperm['name']);
            }
            if ($groupperm['delete_other_reports'] == " checked") {
                array_push($userperms['delete_other_reports'], $groupperm['name']);
            }

            // Advanced options

            if ($groupperm['import_data'] == " checked") {
                array_push($userperms['import_data'], $groupperm['name']);
            }
            if ($groupperm['set_notifications_for_others'] == " checked") {
                array_push($userperms['set_notifications_for_others'], $groupperm['name']);
            }
            if ($groupperm['add_proxy_entries'] == " checked") {
                array_push($userperms['add_proxy_entries'], $groupperm['name']);
            }
            if ($groupperm['update_entry_ownership'] == " checked") {
                array_push($userperms['update_entry_ownership'], $groupperm['name']);
            }
            if ($groupperm['ignore_editing_lock'] == " checked") {
                array_push($userperms['ignore_editing_lock'], $groupperm['name']);
            }
            if ($groupperm['edit_form'] == " checked") {
                array_push($userperms['edit_form'], $groupperm['name']);
            }
            if ($groupperm['delete_form'] == " checked") {
                array_push($userperms['delete_form'], $groupperm['name']);
            }
        }
    }
} else {
    $fid = $_GET['fid']; // guaranteed to be 'new' based on if condition
    if ($_GET['tableform']) {
    $newtableform = true;
    }
    $formName = "";
		$singular = "";
		$plural = "";
    $singleentry = "off"; // need to send a default for this
    $defaultform = 0;
    $defaultlist = 0;
    $menutext = _AM_APP_USETITLE;
    $form_handle = "";
    $store_revisions = 0;
		$send_digests = 0;
		$defaultpi = 0;
		$pioptions = array();
		$entries_are_users = 0;
		$entries_are_users_conditions_ui = ""; // Don't show conditions UI for new forms - no elements exist yet
		$entries_are_users_default_groups_ui = formulize_renderDefaultGroupsUI(array());
		$entries_are_users_default_groups_selected = array();
		$template_group_metadata = array();
		$entries_are_groups = 0;
		$group_categories = array();
    if ($_GET['aid']) {
        $formApplications = array(intval($_GET['aid']));
    }
    $groupsCanEditDefaults = $xoopsUser->getGroups();
    $regUserGroupKey = array_search(2, $groupsCanEditDefaults);
    if(count((array) $groupsCanEditDefaults)>1 AND $regUserGroupKey !== false) {
        unset($groupsCanEditDefaults[$regUserGroupKey]); // don't give edit_form perm to registered users group unless it is the only group the user is a member of
    }
    $member_handler = xoops_gethandler('member');
    $allGroups = $member_handler->getGroups();
    foreach($allGroups as $thisGroup) {
        $groupsCanEditOptions[$thisGroup->getVar('groupid')] = $thisGroup->getVar('name');
    }
		$connections = array();
}

// get a list of all the custom element types that are present
// custom element classes must contain "Element.php" as the final part of the filename
$classFiles = scandir(XOOPS_ROOT_PATH."/modules/formulize/class/");
$customElements = array();
$i = 0;
foreach($classFiles as $thisFile) {
	if (substr($thisFile, -11)=="Element.php") {
			$customType = substr($thisFile, 0, strpos($thisFile, "Element.php"));
			$customElementHandler = xoops_getmodulehandler($customType."Element", "formulize");
			$customElementObject = $customElementHandler->create();
			if(!$customElementObject->isSystemElement) {
			$customElements[$i]['type'] = $customType;
			$customElements[$i]['name'] = $customElementObject->name;
			$i++;
		}
	}
}


$i = 1;
$applications = array();
foreach($allApps as $thisApp) {
    $applications[$i]['appid'] = $thisApp->getVar('appid');
    $applications[$i]['text'] = printSmart($thisApp->getVar('name'),50);
    if (isset($formApplications)) {
        $applications[$i]['selected'] = in_array($thisApp->getVar('appid'),$formApplications) ? " selected" : "";
    } else {
        $applications[$i]['selected'] = "";
    }
    $i++;
}

// common values should be assigned to all tabs
$common['name'] = $formName;
$common['singular'] = $singular;
$common['plural'] = $plural;
$common['fid'] = $fid;
$common['aid'] = $aid;
$common['defaultform'] = $defaultform;
$common['defaultlist'] = $defaultlist;
$common['form_object'] = $formObject;
$common['note'] = $note;
$common['defaultpi'] = $defaultpi;
$common['pioptions'] = $pioptions;
$common['formTitle'] = "this form"; // used to refer to the form in the primary identifier selection UI
$common['standardTypes'] = formulizeHandler::getStandardElementTypes();
if($fid != "new") {
	$common['allFormTitles'] = $form_handler->getAllFormTitles();
	$common['allFormTitles'][$fid] = _AM_ELE_CLONE_TO_FORM_THISFORM;
}

$permissions = array();
$permissions['hello'] = "Hello Permission World";

// need to get screen data so this can be populated properly
$screens = array();
$screen_handler = xoops_getmodulehandler('screen', 'formulize');
$criteria_object = new Criteria('type','multiPage');
$multiPageFormScreens = $screen_handler->getObjects($criteria_object,$fid);
$i = 1;
foreach($multiPageFormScreens as $screen) {
    $screens['screens'][$i]['sid'] = $screen->getVar('sid');
    $screens['screens'][$i]['title'] = $screen->getVar('title');
    $i++;
}
$listOfEntriesScreens = $screen_handler->getObjects(new Criteria('type','listOfEntries'),$fid);
$i = 1;
foreach($listOfEntriesScreens as $screen) {
    $screens['listOfEntries'][$i]['sid'] = $screen->getVar('sid');
    $screens['listOfEntries'][$i]['title'] = $screen->getVar('title');
    $i++;
}
$templateScreens = $screen_handler->getObjects(new Criteria('type','template'),$fid);
$i = 1;
foreach($templateScreens as $screen) {
    $screens['template'][$i]['sid'] = $screen->getVar('sid');
    $screens['template'][$i]['title'] = $screen->getVar('title');
    $i++;
}
$calendarScreens = $screen_handler->getObjects(new Criteria('type','calendar'),$fid);
$i=1;
foreach($calendarScreens as $screen) {
    $screens['calendar'][$i]['sid'] = $screen->getVar('sid');
    $screens['calendar'][$i]['title'] = $screen->getVar('title');
    $i++;
}
$criteria_object = new Criteria('type','form');
$legacyFormScreens = $screen_handler->getObjects($criteria_object,$fid);
$i = 1;
foreach($legacyFormScreens as $screen) {
    $screens['legacy'][$i]['sid'] = $screen->getVar('sid');
    $screens['legacy'][$i]['title'] = $screen->getVar('title');
    $i++;
}

$settings = array();
$settings['singleentry'] = $singleentry;
$settings['menutext'] = $menutext;
$settings['form_handle'] = $form_handle;
$settings['send_digests'] = $send_digests;
$settings['store_revisions'] = $store_revisions;
$settings['revisionsDisabled'] = formulizeRevisionsForAllFormsIsOn() ? 'disabled="disabled"' : '';
$settings['istableform'] = ($tableform OR $newtableform) ? true : false;
$settings['entries_are_users'] = $entries_are_users;
$settings['entries_are_users_conditions_ui'] = $entries_are_users_conditions_ui;
$settings['entries_are_users_default_groups_ui'] = $entries_are_users_default_groups_ui;
$settings['entries_are_users_default_groups_selected'] = $entries_are_users_default_groups_selected;
$settings['template_group_metadata_json'] = json_encode($template_group_metadata);
$settings['entries_are_groups'] = $entries_are_groups;
$settings['group_categories'] = $group_categories;
$settings['connections'] = $connections[0]['content']; // 0 will be first, ie: primary, relationship. 'content' for that will include all the links, which is what template looks for

// Check if we should show the user mapping UI
// Default to true, and only set to false if conditions indicate we shouldn't show it
$settings['show_user_mapping_ui'] = true;
if($fid != "new" && $entries_are_users == 1) {
	// The user account uid element has the handle formulize_user_account_uid_X where X is the form id
	$userAccountUidHandle = 'formulize_user_account_uid_' . $fid;

	// Single query to check: total entries, and max value in the user account uid field
	$checkSql = "SELECT COUNT(*) as total_entries, MAX(`" . $userAccountUidHandle . "`) as max_uid FROM " . $xoopsDB->prefix("formulize_" . $form_handle);
	$checkResult = $xoopsDB->query($checkSql);
	$checkRow = $xoopsDB->fetchArray($checkResult);
	$totalEntries = intval($checkRow['total_entries']);
	$maxUid = intval($checkRow['max_uid']);

	// Hide UI if there are no entries, OR if entries are already associated with users
	if($totalEntries == 0 || $maxUid > 0) {
		$settings['show_user_mapping_ui'] = false;
	}
}
if (isset($groupsCanEditOptions)) {
    $settings['groupsCanEditOptions'] = $groupsCanEditOptions;
    $settings['groupsCanEditDefaults'] = $groupsCanEditDefaults;
}

$i = 1;
$adminPage['tabs'][$i]['name'] = _AM_APP_SETTINGS;
$adminPage['tabs'][$i]['template'] = "db:admin/form_settings.html";
$adminPage['tabs'][$i]['content'] = $settings + $common;
$adminPage['tabs'][$i]['content']['applications'] = $applications;
if (isset($elementHeadings)) {
    $adminPage['tabs'][$i]['content']['elementheadings'] = $elementHeadings;
}
if (isset($formApplications)) {
    $adminPage['tabs'][$i]['content']['formapplications'] = $formApplications;
}
$i++;

if ($fid != "new") {
    $advanced_calculations = array();
    $advanced_calculation_handler = xoops_getmodulehandler('advancedCalculation', 'formulize');
    $advanced_calculations['advanced_calculations'] = $advanced_calculation_handler->getList($fid);

		$form_id = $fid;
		$selectedFramework = 0;
		include XOOPS_ROOT_PATH.'/modules/formulize/admin/generateTemplateElementHandleHelp.php';
		$advanced_calculations['variabletemplatehelp'] = $listTemplateHelp;

    if (!$tableform AND !$newtableform) {
        $adminPage['tabs'][$i]['name'] = "Elements";
        $adminPage['tabs'][$i]['template'] = "db:admin/form_elements.html";
        $adminPage['tabs'][$i]['content'] = $common;
        if (isset($elements)) {
            $adminPage['tabs'][$i]['content']['elements'] = $elements;
        }
        if (count((array) $customElements)>0) {
            $adminPage['tabs'][$i]['content']['customElements'] = $customElements;
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
    $adminPage['tabs'][$i]['content']['submitted_user'] = $submitted_user;
    $adminPage['tabs'][$i]['content']['userSelectionList'] = $userSelectionList;
    $adminPage['tabs'][$i]['content']['userperms'] = $userperms;
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
$adminPage['pagetitle'] = _AM_APP_FORM.$formName;
$adminPage['needsave'] = true;

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['url'] = "page=application&aid=$aid&tab=forms";
$breadcrumbtrail[2]['text'] = $appName;
$breadcrumbtrail[3]['text'] = $formName;
