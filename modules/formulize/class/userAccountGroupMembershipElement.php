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

    function __construct() {
			parent::__construct();
			$this->name = "User Account Group Membership";
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
		global $xoopsDB;
		$member_handler = xoops_gethandler('member');
		$dataHandler = new formulizeDataHandler($element->getVar('fid'));

		// Get the user ID from the entry's uid element
		$userId = intval($dataHandler->getElementValueInEntry($entry_id, 'formulize_user_account_uid_'.$element->getVar('fid')));
		if(!$userId) {
			return array(); // No user associated yet, return empty array
		}

		// Get the user's current group memberships
		$userObject = $member_handler->getUser($userId);
		if(!$userObject) {
			return array();
		}

		// Return the array of group IDs the user belongs to
		return $userObject->getGroups();
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

		// Build the group list from the groups table, excluding template groups
		// Format: "GroupID - GroupName" as the option text
		list($groupOptionList, $groupUITextList) = self::getAvailableGroupsForOptions();

		// Use the existing autocomplete element to render the group selection UI
		$autocompleteHandler = xoops_getmodulehandler('autocompleteElement', 'formulize');
		$autocompleteElement = $autocompleteHandler->create();
		$autocomplete_ele_value = $autocompleteHandler->getDefaultEleValue();
		$autocomplete_ele_value[ELE_VALUE_SELECT_MULTIPLE] = 1; // Allow multiple group selections
		$autocompleteElement->setVar('ele_uitext', $groupUITextList);

		// Mark currently selected groups (from ele_value which was set by loadValue)
		$selectedGroupIds = is_array($ele_value) ? $ele_value : array();
		foreach($selectedGroupIds as $selectedGroupId) {
			if(isset($groupOptionList[$selectedGroupId])) {
				$groupOptionList[$selectedGroupId] = 1; // Mark as selected
			}
		}
		$autocomplete_ele_value[ELE_VALUE_SELECT_OPTIONS] = $groupOptionList;

		// Render the autocomplete element
		$form_ele = $autocompleteHandler->render($autocomplete_ele_value, $caption, $markupName, $isDisabled, $autocompleteElement, $entry_id, $screen, $owner);
		return $form_ele;
	}

	// this method returns any custom validation code (javascript) that should figure out how to validate this element
	// 'myform' is a name enforced by convention that refers to the form where this element resides
	// use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
	function generateValidationCode($caption, $markupName, $element, $entry_id=false) {
		$validationCode = array();
		$ele_value = $element->getVar('ele_value');
		$eltname = $markupName;
		$eltcaption = $caption;
		$eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, strip_tags(htmlspecialchars_decode($eltcaption, ENT_QUOTES)));
		$eltmsg = str_replace('"', '\"', stripslashes( $eltmsg ) );
		$validationCode[] = "\nif ( window.document.getElementsByName('{$eltname}[]').length == 0 ) {\n window.alert(\"{$eltmsg}\");\n myform.{$eltname}_user.focus();\n return false;\n }\n";
		return $validationCode;
	}

	/**
	 * Get all available groups that are not template groups, formatted for autocomplete options
	 * Format: "GroupID - GroupName" as the key, 0 as value (for ele_value format)
	 * @return array Option strings as keys, 0 as values
	 */
	static function getAvailableGroupsForOptions() {
		global $xoopsDB;
		$groupOptionList = array();
		$groupUITextList = array();

		$sql = "SELECT groupid, name FROM " . $xoopsDB->prefix('groups') . " WHERE is_group_template = 0 ORDER BY name";
		$result = $xoopsDB->query($sql);
		if($result) {
			while($row = $xoopsDB->fetchArray($result)) {
				// Format: "ID - Name" so we can parse the ID when saving
				$label = $row['groupid'] . ' - ' . $row['name'];
				$groupUITextList[$row['groupid']] = $label;
				$groupOptionList[$row['groupid']] = 0; // 0 means not selected by default
			}
		}

		return array($groupOptionList, $groupUITextList);
	}

	/**
	 * Get a map of group IDs to group names
	 * @return array Group ID => Group Name
	 */
	function getGroupIdToNameMap() {
		global $xoopsDB;
		static $map = null;

		if($map === null) {
			$map = array();
			$sql = "SELECT groupid, name FROM " . $xoopsDB->prefix('groups') . " WHERE is_group_template = 0";
			$result = $xoopsDB->query($sql);
			if($result) {
				while($row = $xoopsDB->fetchArray($result)) {
					$map[$row['groupid']] = $row['name'];
				}
			}
		}

		return $map;
	}

	/**
	 * Parse a group option string to extract the group ID
	 * @param string $optionString Format: "GroupID - GroupName"
	 * @return int|false The group ID or false if parsing failed
	 */
	static function parseGroupIdFromOption($optionString) {
		$parts = explode(' - ', $optionString, 2);
		if(count($parts) >= 1 && is_numeric($parts[0])) {
			return intval($parts[0]);
		}
		return false;
	}

}
