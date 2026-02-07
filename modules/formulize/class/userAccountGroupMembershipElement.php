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
		$member_handler = xoops_gethandler('member');
		$dataHandler = new formulizeDataHandler($element->getVar('fid'));
		$userIdElementHandle = 'formulize_user_account_uid_'.$element->getVar('fid');
		if(!$userId = intval($dataHandler->getElementValueInEntry($entry_id, $userIdElementHandle))
		OR !$userObject = $member_handler->getUser($userId)) {
			return array(); // No user associated yet, or id is invalid, return empty array
		}
		return $member_handler->getGroupsByUser($userId);
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
		$autocomplete_ele_value[ELE_VALUE_SELECT_MULTIPLE] = 1; // Allow multiple group selections
		list($autocomplete_ele_value[ELE_VALUE_SELECT_OPTIONS], $groupUITextList) = self::getAvailableGroupsForOptions($ele_value);
		$autocompleteElement->setVar('ele_uitext', $groupUITextList);
		$autocompleteElement->useOptionsAsValues = true; // will use group ids as the values in the HTML markup, and then we can just save those values directly without having to do extra work to figure out which options were selected based on their ordinal position in the list, which would introduce race conditions too!
		return $autocompleteHandler->render($autocomplete_ele_value, $caption, $markupName, $isDisabled, $autocompleteElement, $entry_id, $screen, $owner);
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
	 * Get all available groups that are not template groups
	 * Exclude registered users group since it is always enforced on users when data is saved
	 * @param array $currentlySelectedGroupIds Optional array of group IDs that should be marked as selected in the options list
	 * @return array Option strings as keys, 0 as values
	 */
	static function getAvailableGroupsForOptions($currentlySelectedGroupIds = []) {
		global $xoopsDB, $xoopsUser;
		$groupOptionList = array();
		$groupUITextList = array();
		$webmasterGroupExclusion = "";
		if(!in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
			$webmasterGroupExclusion = " AND groupid != ".XOOPS_GROUP_ADMIN;
		}
		$sql = "SELECT groupid, name FROM " . $xoopsDB->prefix('groups') . " WHERE is_group_template = 0 AND groupid != ".XOOPS_GROUP_USERS." AND groupid != ".XOOPS_GROUP_ANONYMOUS." $webmasterGroupExclusion ORDER BY name";
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

}
