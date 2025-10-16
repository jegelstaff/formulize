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

class formulizeListboxElement extends formulizeSelectElement {

	function __construct() {
		parent::__construct();
		$this->name = "Listbox";
		$this->hasData = true; // set to false if this is a non-data element, like the subform or the grid
		$this->needsDataType = false; // set to false if you're going force a specific datatype for this element using the overrideDataType
		$this->overrideDataType = "text"; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
		$this->adminCanMakeRequired = true; // set to true if the webmaster should be able to toggle this element as required/not required
		$this->alwaysValidateInputs = false; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
		// $this->canHaveMultipleValues = false; // set by setCanHaveMultipleValues method in the parent class, which is called as part of 'get' operation (in _setElementProperties method in elements.php)
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
		$descriptionAndExamples = "
**Element:** Listbox (listbox)
**Description:** A box that shows a list of options, allowing users to select one or more options from the list. The user experience with Listboxes is generally poor. Use Radio Buttons, Checkboxes, Dropdown Lists, or Autocomplete Lists instead, unless there's a specific reason to use a Listbox or the user has speciically requested one.
**Properties:**
- all the common properties for List elements, plus:
- allowMultipleSelections (optional, a 1/0 indicating if multiple selections should be allowed in the listbox. For Listboxes, the default is 1. Set to 0 to allow only a single selection.)
**Example:**
- A list of favorite colors, and only one choice is allowed: { options: [ 'red', 'blue', 'green', 'yellow', 'purple', 'orange' ], allowMultipleSelections: 0 }";
		return $descriptionAndExamples;
	}

}

#[AllowDynamicProperties]
class formulizeListboxElementHandler extends formulizeSelectElementHandler {

	function create() {
		return new formulizeListboxElement();
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
		list($ele_value, $ele_uitext, $ele_delim) = array_values(formulizeBaseClassForListsElementHandler::validateEleValuePublicAPIProperties($properties, $ele_value)); // array_values will take the values in the associative array and assign them to the list variables correctly, since list expects numeric keys
		$ele_value[ELE_VALUE_SELECT_NUMROWS] = count($ele_value[ELE_VALUE_SELECT_OPTIONS]) < 10 ? count($ele_value[ELE_VALUE_SELECT_OPTIONS]) : 10;
		$ele_value[ELE_VALUE_SELECT_NUMROWS] = $ele_value[ELE_VALUE_SELECT_NUMROWS] < 1 ? 1 : $ele_value[ELE_VALUE_SELECT_NUMROWS];
		if(isset($properties['allowMultipleSelections'])) {
			$ele_value[ELE_VALUE_SELECT_MULTIPLE] = $properties['allowMultipleSelections'];
		}
		return [
			'ele_value' => $ele_value,
			'ele_uitext' => $ele_uitext,
			'ele_delim' => $ele_delim
		];
	}

	public function getDefaultEleValue() {
		$ele_value = array();
		$ele_value[ELE_VALUE_SELECT_NUMROWS] = 10; // the number of rows to show in the listbox
		$ele_value[ELE_VALUE_SELECT_MULTIPLE] = 1; // a 1/0 indicating if multiple selections should be allowed
		$ele_value[ELE_VALUE_SELECT_OPTIONS] = array(); // an array of options for the select box
		$ele_value[ELE_VALUE_SELECT_AUTOCOMPLETE] = 0; // a 1/0 indicating if this is an autocomplete box
		$ele_value[ELE_VALUE_SELECT_RESTRICTSELECTION] = 0; // 0/1/2/3 indicating restrictions on how many times an option can be picked. 0 - no limit, 1 - only once, 2 - once per user, 3 - once per group
		return $ele_value;
	}

}


