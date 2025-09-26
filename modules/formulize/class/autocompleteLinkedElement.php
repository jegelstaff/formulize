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
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/selectLinkedElement.php";

class formulizeAutocompleteLinkedElement extends formulizeSelectLinkedElement {

	function __construct() {
		parent::__construct();
		$this->name = "Linked Autocomplete List";
		$this->hasData = true; // set to false if this is a non-data element, like the subform or the grid
		$this->needsDataType = false; // set to false if you're going force a specific datatype for this element using the overrideDataType
		$this->overrideDataType = "bigint"; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
		$this->adminCanMakeRequired = true; // set to true if the webmaster should be able to toggle this element as required/not required
		$this->alwaysValidateInputs = false; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
		// $this->canHaveMultipleValues = false; // set by setCanHaveMultipleValues method in the parent class, which is called as part of 'get' operation (in _setElementProperties method in elements.php)
		$this->hasMultipleOptions = true;
		$this->isLinked = true; // set to true if this element can have linked values
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
**Element:** Linked Autocomplete List (autocompleteLinked)
**Description:** A single-line text box that provides autocomplete suggestions from a set of options based on values entered into another form. The user can select one of the suggested options, or if the allowNewValues property is enabled then the user can enter a new value that is not found in the list. Linked Autocomplete Lists can be set to allow multiple selections, with the allowMultipleSelections property. If new values are allowed, the new value will be added as a new entry in the source form, which is very convenient, because otherwise the user would have to go to the other form and enter the value there first.";
		if($commonNotes) {
			$descriptionAndExamples .= "
$commonNotes";
		}
		if($commonProperties) {
			$descriptionAndExamples .= "
$commonProperties
- allowNewValues (optional, a 1/0 indicating if users should be allowed to enter values that are not in the source form already.  Default is 0, meaning users can only select from the existing options. Set to 1 to allow users to enter new values, which will be saved as entries in the source form.)
- allowMultipleSelections (optional, a 1/0 indicating if multiple selections should be allowed. For Autocomplete Lists, the default is 0. Set to 1 to allow multiple selections.)";
		}
		if($commonExamples) {
			$descriptionAndExamples .= "
$commonExamples
- A list of inventory items, drawing options from the Item Name element in a separate Inventory form, and allowing new inventory items to be added: { source_element: 'inventory_item_name', allowNewValues: 1 }
- A list of countries with options drawn from the Name element in a separate Countries form, and multiple selections are allowed: { source_element: 'country_name', allowMultipleSelections: 1 }";
		}
		return $descriptionAndExamples;
	}

}

#[AllowDynamicProperties]
class formulizeAutocompleteLinkedElementHandler extends formulizeSelectLinkedElementHandler {

	function create() {
		return new formulizeAutocompleteLinkedElement();
	}

	/**
	 * Validate properties for this element type, based on the structure used publically (MCP, Public API, etc).
	 * The description in the mcpElementPropertiesDescriptionAndExamples static method on the element class, follows this convention
	 * properties are the contents of the ele_value property on the object
	 * @param array $properties The properties to validate
	 * @param int|string|object|null $elementIdentifier the id, handle, or element object of the element we're preparing properties for. Null if unknown.
	 * @return array An array of properties ready for the object. Usually just ele_value but could be others too.
	 */
	public function validateEleValuePublicAPIProperties($properties, $elementIdentifier = null) {
		list($ele_value) = array_values(formulizeSelectLinkedElementHandler::validateEleValuePublicAPIProperties($properties, $elementIdentifier)); // array_values will take the values in the associative array and assign them to the list variables correctly, since list expects numeric keys
		$ele_value[ELE_VALUE_SELECT_MULTIPLE] = isset($properties['allowMultipleSelections']) ? $properties['allowMultipleSelections'] : 0;
		$ele_value[ELE_VALUE_SELECT_AUTOCOMPLETEALLOWSNEW] = isset($properties['allowNewValues']) ? $properties['allowNewValues'] : 0;
		return [
			'ele_value' => $ele_value
		];
	}

	protected function getDefaultEleValue() {
		return array(
			ELE_VALUE_SELECT_NUMROWS => 1,
			ELE_VALUE_SELECT_MULTIPLE => 0,
			ELE_VALUE_SELECT_LINK_LIMITGROUPS => '',
			ELE_VALUE_SELECT_LINK_USERSGROUPS => 0,
			ELE_VALUE_SELECT_LINK_FILTERS => array(),
			ELE_VALUE_SELECT_LINK_ALLGROUPS => 0,
			ELE_VALUE_SELECT_LINK_USEONLYUSERSENTRIES => 0,
			ELE_VALUE_SELECT_LINK_CLICKABLEINLIST => 0,
			ELE_VALUE_SELECT_AUTOCOMPLETE => 1,
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


