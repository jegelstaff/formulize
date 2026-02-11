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

		// Ensure helper functions are available
		include_once XOOPS_ROOT_PATH . '/modules/formulize/include/functions.php';
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
		// Collect template group categories keyed by element.
		// Within each element, separate unconditional categories from conditional ones,
		// so they can be combined into a single natural-language description per element.
		$templateByElement = array();
		foreach ($defaultGroups as $groupId) {
			$groupId = intval($groupId);
			if (isset($sanitizedLinks[$groupId]) && isset($templateGroupMetadata[$groupId])) {
				$meta = $templateGroupMetadata[$groupId];
				$conditionDesc = '';
				if (isset($allConditions[$groupId]) && !empty($allConditions[$groupId])) {
					$conditionDesc = formulize_describeConditions($allConditions[$groupId], $element_handler);
				}
				foreach ($meta['linkedElements'] as $linkedElement) {
					if (in_array(intval($linkedElement['ele_id']), $sanitizedLinks[$groupId])) {
						$eleId = intval($linkedElement['ele_id']);
						if (!isset($templateByElement[$eleId])) {
							$caption = $linkedElement['caption'];
							if (!empty($linkedElement['formName'])) {
								$caption = sprintf(_AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_TEMPLATE_ELEMENT_IN_FORM, $caption, $linkedElement['formName']);
							}
							$templateByElement[$eleId] = array(
								'unconditional' => array(),
								'conditional' => array(),
								'formSingular' => $meta['formSingular'],
								'caption' => $caption
							);
						}
						if ($conditionDesc) {
							$templateByElement[$eleId]['conditional'][$conditionDesc][] = $meta['categoryName'];
						} else {
							$templateByElement[$eleId]['unconditional'][] = $meta['categoryName'];
						}
					}
				}
			} else {
				// Regular group - use literal name, with condition qualifier if applicable
				$groupObject = $member_handler->getGroup($groupId);
				if ($groupObject) {
					$desc = $groupObject->getVar('name');
					if (isset($allConditions[$groupId]) && !empty($allConditions[$groupId])) {
						$conditionDesc = formulize_describeConditions($allConditions[$groupId], $element_handler);
						if ($conditionDesc) {
							$desc .= sprintf(_AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_CONDITIONAL, $conditionDesc);
						}
					}
					$groupDescriptions[] = $desc;
				}
			}
		}
		// Build descriptions for template groups, one bullet per element
		foreach ($templateByElement as $eleData) {
			$uncond = array_unique($eleData['unconditional']);
			$cond = $eleData['conditional'];
			if (!empty($uncond)) {
				$desc = sprintf(
					count($uncond) > 1 ? _AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_TEMPLATE_PLURAL : _AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_TEMPLATE,
					formulize_listWithAnd($uncond),
					$eleData['formSingular'],
					$eleData['caption']
				);
				foreach ($cond as $condDesc => $cats) {
					$cats = array_unique($cats);
					$desc .= sprintf(
						count($cats) > 1 ? _AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_CONDITIONAL_AND_PLURAL : _AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_CONDITIONAL_AND,
						formulize_listWithAnd($cats),
						$condDesc
					);
				}
			} else {
				$first = true;
				$desc = '';
				foreach ($cond as $condDesc => $cats) {
					$cats = array_unique($cats);
					if ($first) {
						$desc = sprintf(
							count($cats) > 1 ? _AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_TEMPLATE_PLURAL : _AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_TEMPLATE,
							formulize_listWithAnd($cats),
							$eleData['formSingular'],
							$eleData['caption']
						);
						$desc .= sprintf(_AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_CONDITIONAL, $condDesc);
						$first = false;
					} else {
						$desc .= sprintf(
							count($cats) > 1 ? _AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_CONDITIONAL_AND_PLURAL : _AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_CONDITIONAL_AND,
							formulize_listWithAnd($cats),
							$condDesc
						);
					}
				}
			}
			$groupDescriptions[] = $desc;
		}
		if (!empty($groupDescriptions)) {
			$descList = count($groupDescriptions) > 1 ? '</p><ul class="form-help-text"><li>' . implode('</li><li>', $groupDescriptions) . '</li></ul><p>' : $groupDescriptions[0];
			$groupMembershipElement->setVar('ele_desc', sprintf(_AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC, $formObject->getVar('form_title'), $descList));
		} else {
			$groupMembershipElement->setVar('ele_desc', '');
		}
		$element_handler->insert($groupMembershipElement);
	}

}
