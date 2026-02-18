<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2011 Freeform Solutions                  ##
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

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php"; // you need to make sure the base element class has been read in first!
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/userAccountElement.php";
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/autocompleteElement.php";

class formulizeUserAccountGroupMembershipElement extends formulizeUserAccountElement {

		var $excludeTemplateGroups; // whether to exclude template groups from the list, or include all groups. Default is to exclude template groups since they are not meant to be assigned to users.

    function __construct() {
			parent::__construct();
			$this->name = "User Account Group Membership";
			$this->excludeTemplateGroups = true; // default to excluding template groups since they are not meant to be assigned to users, but this can be overridden by setting this property to false
			// Note: no userProperty defined because group membership is stored in groups_users_link table, not on the user object
		}

}

#[AllowDynamicProperties]
class formulizeUserAccountGroupMembershipElementHandler extends formulizeUserAccountElementHandler {

	function create() {
		return new formulizeUserAccountGroupMembershipElement();
	}

	// Override loadValue since we're not reading from a user property
	// Group membership data comes from the groups_users_link table
	function loadValue($element, $value, $entry_id) {
		$member_handler = xoops_gethandler('member');
		$dataHandler = new formulizeDataHandler($element->getVar('fid'));
		$userIdElementHandle = 'formulize_user_account_uid_'.$element->getVar('fid');
		if(!$userId = intval($dataHandler->getElementValueInEntry($entry_id, $userIdElementHandle))
		OR !$userObject = $member_handler->getUser($userId)) {
			return array(); // No user associated yet, or id is invalid, return empty array
		}
		return array(ELE_VALUE_SELECT_OPTIONS => $member_handler->getGroupsByUser($userId), ELE_VALUE_SELECT_MULTIPLE => 1); // unusual use of ELE_VALUE_SELECT_OPTIONS to store the selected group ids, but we will always gather the full set below in the render method and need a way of knowing which are selected
	}

	// this method renders the element for display in a form
	// the caption has been pre-prepared and passed in separately from the element object
	// if the element is disabled, then the method must take that into account and return a non-interactable label with some version of the element's value in it
	// $ele_value is the options for this element - which will either be the admin values set by the admin user, or will be the value created in the loadValue method
	// $caption is the prepared caption for the element
	// $markupName is what we have to call the rendered element in HTML
	// $isDisabled flags whether the element is disabled or not so we know how to render it
	// $element is the element object
	// $entry_id is the ID number of the entry where this particular element comes from
	// $screen is the screen object that is in effect, if any (may be null)
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen, $owner) {
		// Setup an autocomplete element to render the group selection UI, and render that
		$autocompleteHandler = xoops_getmodulehandler('autocompleteElement', 'formulize');
		$autocompleteElement = $autocompleteHandler->create();
		$autocomplete_ele_value = $autocompleteHandler->getDefaultEleValue();
		$autocomplete_ele_value[ELE_VALUE_SELECT_MULTIPLE] = $ele_value[ELE_VALUE_SELECT_MULTIPLE];
		list($autocomplete_ele_value[ELE_VALUE_SELECT_OPTIONS], $groupUITextList) = self::getAvailableGroupsForOptions($ele_value[ELE_VALUE_SELECT_OPTIONS], $element->excludeTemplateGroups);
		$autocompleteElement->setVar('ele_uitext', $groupUITextList);
		$autocompleteElement->useOptionsAsValues = true; // will use group ids as the values in the HTML markup, and then we can just save those values directly without having to do extra work to figure out which options were selected based on their ordinal position in the list, which would introduce race conditions too!
		return $autocompleteHandler->render($autocomplete_ele_value, $caption, $markupName, $isDisabled, $autocompleteElement, $entry_id, $screen, $owner);
	}

	// this method returns any custom validation code (javascript) that should figure out how to validate this element
	// 'myform' is a name enforced by convention that refers to the form where this element resides
	// use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
	function generateValidationCode($caption, $markupName, $element, $entry_id=false) {
		$validationCode = array();
		return $validationCode;
	}

	/**
	 * Get all available groups that are not template groups
	 * Exclude registered users group since it is always enforced on users when data is saved
	 * @param array $currentlySelectedGroupIds Optional array of group IDs that should be marked as selected in the options list
	 * @param bool $excludeTemplateGroups Optional flag to exclude template groups (default is true, which excludes template groups)
	 * @return array Option strings as keys, 0 as values
	 */
	static function getAvailableGroupsForOptions($currentlySelectedGroupIds = [], $excludeTemplateGroups = true) {
		global $xoopsDB, $xoopsUser;
		$groupOptionList = array();
		$groupUITextList = array();
		$webmasterGroupExclusion = "";
		$templateGroupExclusion = "";
		if(!in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
			$webmasterGroupExclusion = " AND groupid != ".XOOPS_GROUP_ADMIN;
		}
		if($excludeTemplateGroups) {
			$templateGroupExclusion = " AND is_group_template = 0 ";
		}
		$sql = "SELECT groupid, name FROM " . $xoopsDB->prefix('groups') . " WHERE groupid != ".XOOPS_GROUP_USERS." AND groupid != ".XOOPS_GROUP_ANONYMOUS." $webmasterGroupExclusion $templateGroupExclusion ORDER BY name";
		$result = $xoopsDB->query($sql);
		if($result) {
			$currentlySelectedGroupIds = is_array($currentlySelectedGroupIds) ? $currentlySelectedGroupIds : [];
			while($row = $xoopsDB->fetchArray($result)) {
				$groupUITextList[$row['groupid']] = $row['name'];
				$groupOptionList[$row['groupid']] = in_array($row['groupid'], $currentlySelectedGroupIds) ? 1 : 0; // Mark as selected if in currently selected group IDs
			}
		}
		return array($groupOptionList, $groupUITextList);
	}

	/**
	 * Update the group membership element's help text (ele_desc) to indicate which default groups are enforced.
	 * Reads all necessary data from the form object: default groups, conditions, and element links.
	 * Called from upsertFormSchemaAndResources so that any change to form settings triggers the update.
	 *
	 * @param object $formObject The form object with entries_are_users settings
	 */
	static function updateGroupMembershipDescription($formObject) {
		$fid = $formObject->getVar('fid');
		$groupMembershipHandle = 'formulize_user_account_groupmembership_' . $fid;
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		$groupMembershipElement = $element_handler->get($groupMembershipHandle);
		if (!$groupMembershipElement) {
			return;
		}

		include_once XOOPS_ROOT_PATH . '/modules/formulize/class/formulize.php';

		$defaultGroups = $formObject->getVar('entries_are_users_default_groups');
		$allConditions = $formObject->getVar('entries_are_users_conditions');
		$sanitizedLinks = $formObject->getVar('entries_are_users_default_groups_element_links');

		if (!is_array($defaultGroups)) {
			$defaultGroups = array();
		}
		if (!is_array($allConditions)) {
			$allConditions = array();
		}
		if (!is_array($sanitizedLinks)) {
			$sanitizedLinks = array();
		}

		if (empty($defaultGroups)) {
			$groupMembershipElement->setVar('ele_desc', '');
			$element_handler->insert($groupMembershipElement);
			return;
		}

		$member_handler = xoops_gethandler('member');
		$templateGroupMetadata = formulizeHandler::getTemplateGroupMetadataForForm($fid);
		$groupDescriptions = array();
		foreach ($defaultGroups as $groupId) {
			$groupId = intval($groupId);
			if (isset($sanitizedLinks[$groupId]) && isset($templateGroupMetadata[$groupId])) {
				// Template group - build description with "for the X selected in Y" text
				$meta = $templateGroupMetadata[$groupId];
				$conditionDesc = '';
				if (isset($allConditions[$groupId]) && !empty($allConditions[$groupId])) {
					$conditionDesc = self::describeConditions($allConditions[$groupId], $element_handler);
				}
				foreach ($meta['linkedElements'] as $linkedElement) {
					if (in_array(intval($linkedElement['ele_id']), $sanitizedLinks[$groupId])) {
						$caption = $linkedElement['caption'];
						if (!empty($linkedElement['formName'])) {
							$caption = sprintf(_AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_TEMPLATE_ELEMENT_IN_FORM, $caption, $linkedElement['formName']);
						}
						$desc = sprintf(
							_AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_TEMPLATE,
							$meta['categoryName'],
							$meta['formSingular'],
							$caption
						);
						if ($conditionDesc) {
							$desc .= sprintf(_AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_CONDITIONAL, $conditionDesc);
						}
						$groupDescriptions[] = $desc;
					}
				}
			} else {
				// Regular group - use literal name, with condition qualifier if applicable
				$groupObject = $member_handler->getGroup($groupId);
				if ($groupObject) {
					$desc = $groupObject->getVar('name');
					if (isset($allConditions[$groupId]) && !empty($allConditions[$groupId])) {
						$conditionDesc = self::describeConditions($allConditions[$groupId], $element_handler);
						if ($conditionDesc) {
							$desc .= sprintf(_AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_CONDITIONAL, $conditionDesc);
						}
					}
					$groupDescriptions[] = $desc;
				}
			}
		}
		if (!empty($groupDescriptions)) {
			$count = count($groupDescriptions);
			if ($count > 1) {
				$toggleId = 'groupmembership_details_' . $fid;
				$showText = _AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_SHOW;
				$hideText = _AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_HIDE;
				$toggleLink = '<a href="javascript:void(0)" onclick="jQuery(\'#' . $toggleId . '\').slideToggle(200);jQuery(this).text(jQuery(this).text()==\'' . $showText . '\'?\'' . $hideText . '\':\'' . $showText . '\')">' . $showText . '</a>';
				$descList = $toggleLink . '<ul id="' . $toggleId . '" class="form-help-text" style="display:none"><li>' . implode('</li><li>', $groupDescriptions) . '</li></ul>';
			} else {
				$descList = $groupDescriptions[0];
			}
			$groupMembershipElement->setVar('ele_desc', sprintf(_AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC, $formObject->getVar('form_title'), $descList));
		} else {
			$groupMembershipElement->setVar('ele_desc', '');
		}
		$element_handler->insert($groupMembershipElement);
	}

	/**
	 * Describe a set of filter conditions as a human-readable string.
	 * $conditions is expected to have parallel arrays at indexes:
	 *   0 = element IDs/handles, 1 = operators, 2 = terms, 3 = match modes ('oom' or 'all') per condition.
	 * Conditions with 'all' match mode are joined with "and", conditions with 'oom' are joined with "or".
	 * The two groups are then combined with "and" between them.
	 */
	private static function describeConditions($conditions, $element_handler = null) {
		if(!is_array($conditions) || empty($conditions) || !isset($conditions[0])) {
			return '';
		}
		$elements = $conditions[0];
		$ops = isset($conditions[1]) ? $conditions[1] : array();
		$terms = isset($conditions[2]) ? $conditions[2] : array();
		$matchModes = isset($conditions[3]) ? $conditions[3] : array();
		$allParts = array();
		$oomParts = array();
		foreach($elements as $i => $eleIdOrHandle) {
			$elementLabel = $eleIdOrHandle;
			if($element_handler) {
				$elementObject = $element_handler->get($eleIdOrHandle);
				if($elementObject) {
					$elementLabel = strip_tags($elementObject->getVar('ele_caption'));
				}
			}
			$op = isset($ops[$i]) ? $ops[$i] : '=';
			$term = isset($terms[$i]) ? $terms[$i] : '';
			$part = sprintf(_AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_CONDITION_ITEM, $elementLabel, $op, $term);
			$mode = isset($matchModes[$i]) ? $matchModes[$i] : 'all';
			if($mode === 'oom') {
				$oomParts[] = $part;
			} else {
				$allParts[] = $part;
			}
		}
		$groups = array();
		if(!empty($allParts)) {
			$groups[] = implode(' and ', $allParts);
		}
		if(!empty($oomParts)) {
			$groups[] = implode(' or ', $oomParts);
		}
		return implode(' and ', $groups);
	}

}
