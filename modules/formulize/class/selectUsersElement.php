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

// There is a corresponding admin template for this element type in the templates/admin folder

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php"; // you need to make sure the base element class has been read in first!
require_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/selectElement.php";

class formulizeSelectUsersElement extends formulizeSelectElement {

	function __construct() {
		parent::__construct();
		$this->name = "Dropdown List of Users";
		$this->hasData = true; // set to false if this is a non-data element, like the subform or the grid
		$this->needsDataType = false; // set to false if you're going force a specific datatype for this element using the overrideDataType
		$this->overrideDataType = "bigint"; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
		$this->adminCanMakeRequired = true; // set to true if the webmaster should be able to toggle this element as required/not required
		$this->alwaysValidateInputs = false; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
		$this->canHaveMultipleValues = false;
		$this->hasMultipleOptions = true;
		$this->isLinked = false; // set to true if this element can have linked values
	}

	/**
	 * Static function to provide the mcp server with the schema for the properties that can be used with the create_form_element and update_form_element tools
	 * Concerned with the properties for the ele_value property of the element object
	 * Follows the convention of properties used publically (MCP, Public API, etc).
	 * @param bool|int $update True if this is being called as part of building the properties for Updating, as opposed to properties for Creating. Default is false (Creating).
	 * @return string The schema for the properties that can be used with the create_form_element and update_form_element tools
	 */
	public static function mcpElementPropertiesDescriptionAndExamples($update = false) {
		list($commonNotes, $commonProperties, $commonExamples) = formulizeHandler::mcpElementPropertiesBaseDescriptionAndExamplesForLinked($update);
		$descriptionAndExamples = "
**Element:** Dropdown List of Users (selectUsers).
**Description:** A dropdown list where the options are the users from one or more groups, or the entire system. The user can select one choice.";
		if($commonNotes) {
			$descriptionAndExamples .= "
$commonNotes";
		}
		if($commonProperties) {
			$descriptionAndExamples .= "
$commonProperties";
		}
		if($commonExamples) {
			$descriptionAndExamples .= "
$commonExamples";
		}
		return $descriptionAndExamples;
	}

}

#[AllowDynamicProperties]
class formulizeSelectUsersElementHandler extends formulizeSelectElementHandler {

	function create() {
		return new formulizeSelectUsersElement();
	}

	/**
	 * Validate properties for this element type, based on the structure used publically (MCP, Public API, etc).
	 * The description in the mcpElementPropertiesDescriptionAndExamples static method on the element class, follows this convention
	 * properties are the contents of the ele_value property on the object
	 * @param array $properties The properties to validate
	 * @param array $ele_value The ele_value settings for this element, if applicable. Should be set by the caller, to the current ele_value settings of the element, if this is an existing element.
	 * @param int|string|object $elementIdentifier The element id, handle or object of the element for which we're validating the properties.
	 * @return array An array of properties ready for the object. Usually just ele_value but could be others too.
	 */
	public function validateEleValuePublicAPIProperties($properties, $ele_value = [], $elementIdentifier = null) {
		$groupIds = (isset($properties['sourceGroups']) AND is_array($properties['sourceGroups'])) ? array_unique($properties['sourceGroups']) : [];
		global $xoopsDB;
		if(count($groupIds) > 0) {
			$sql = "SELECT groupid FROM ".$xoopsDB->prefix("groups")." WHERE groupid IN (".implode(',', array_map('intval', $groupIds)).")";
			$foundGroupIds = 0;
			if($result = $xoopsDB->query($sql)) {
				$foundGroupIds = $xoopsDB->getRowsNum($result);
			}
			if(count($groupIds) != $foundGroupIds) {
				throw new Exception("You must provide a valid sourceGroups property for the linked dropdown list element. One or more of the group ids you provided do not exist.");
			}
		}
		if(is_array($groupIds) AND count($groupIds) > 0) {
			$ele_value[ELE_VALUE_SELECT_LINK_LIMITGROUPS] = implode(',', array_map('intval', $groupIds));
		}
		return [
			'ele_value' => $ele_value,
		];
	}

	public function getDefaultEleValue() {
		return array(
			ELE_VALUE_SELECT_NUMROWS => 1,
			ELE_VALUE_SELECT_MULTIPLE => 0,
			ELE_VALUE_SELECT_OPTIONS => array('{USERNAMES}' => 0),
			ELE_VALUE_SELECT_LINK_LIMITGROUPS => 'all',
			ELE_VALUE_SELECT_LINK_USERSGROUPS => 0,
			ELE_VALUE_SELECT_LINK_FILTERS => array(),
			ELE_VALUE_SELECT_LINK_ALLGROUPS => 0,
			ELE_VALUE_SELECT_LINK_USEONLYUSERSENTRIES => 0,
			ELE_VALUE_SELECT_LINK_CLICKABLEINLIST => 0,
			ELE_VALUE_SELECT_AUTOCOMPLETE => 0,
			ELE_VALUE_SELECT_RESTRICTSELECTION => 0,
			ELE_VALUE_SELECT_LINK_ALTLISTELEMENTS => array(),
			ELE_VALUE_SELECT_LINK_ALTEXPORTELEMENTS => array(),
			ELE_VALUE_SELECT_LINK_SORT => 0,
			ELE_VALUE_SELECT_LINK_DEFAULTVALUE => array(),
			ELE_VALUE_SELECT_LINK_SHOWDEFAULTWHENBLANK => 0,
			ELE_VALUE_SELECT_LINK_SORTORDER => 1,
			ELE_VALUE_SELECT_AUTOCOMPLETEALLOWSNEW => 0,
			ELE_VALUE_SELECT_LINK_ALTFORMELEMENTS => array(),
			ELE_VALUE_SELECT_LINK_SNAPSHOT => 0,
			ELE_VALUE_SELECT_LINK_ALLOWSELFREF => 0,
			ELE_VALUE_SELECT_LINK_LIMITBYELEMENT => 0,
			ELE_VALUE_SELECT_LINK_LIMITBYELEMENTFILTER => array(),
			ELE_VALUE_SELECT_LINK_SOURCEMAPPINGS => array(),
		);
	}

}


